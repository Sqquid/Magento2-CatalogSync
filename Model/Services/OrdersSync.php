<?php

namespace Sqquid\Sync\Model\Services;

use Magento\Catalog\Model\Product\Type as ProductType;

class OrdersSync
{

    protected $sqquidHelper;
    protected $logger;
    protected $orderRepository;
    protected $queue;
    protected $jsonHelper;

    protected $maxPostSize;
    protected $queueItems;
    protected $ordersToSend;
    protected $batchSize;
    protected $date;

    public function __construct(
        \Sqquid\Sync\Helper\Data $sqquidHelper,
        \Sqquid\Sync\Logger\Logger $logger,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepositoryInterface,
        \Sqquid\Sync\Model\ResourceModel\Queue\Collection $queueCollection,
        \Sqquid\Sync\Model\ResourceModel\Queue $queueItem,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    )
    {
        $this->sqquidHelper = $sqquidHelper;
        $this->logger = $logger;
        $this->orderRepository = $orderRepositoryInterface;
        $this->queueItem = $queueItem;
        $this->queueCollection = $queueCollection;
        $this->jsonHelper = $jsonHelper;
        $this->date = $date;

        $this->maxPostSize = $this->sqquidHelper->convertPHPSizeToBytes(ini_get('post_max_size'));
        $this->queueItems = $this->getOrderQueueCollection();
        $this->ordersToSend = [];
        $this->batchSize = 10;
    }


    /**
     * @return string
     *
     * This grabs the order queues and loops through in increments of batchSize and compares
     * how much JSON data we have in bytes compared to the max post size.. and tries not to go over that limit.
     *
     */
    public function getQueuedOrderJSON()
    {
        if (!$this->queueItems) {
            return json_encode(['Orders' => $this->ordersToSend]);
        }

        foreach ($this->queueItems as $item) {

            $error = false;

            try {

                if (strlen($this->jsonHelper->jsonEncode($this->ordersToSend)) >= ($this->maxPostSize - ($this->batchSize * 1000))) {
                    break;
                }

                if ($formattedItem = $this->getFormattedOrderInfo($item)) {
                    $this->ordersToSend[] = $formattedItem;
                }

            } catch (\Exception $e) {

                $error = true;

                $this->logger->error('Order Export Error');
                $this->logger->error('Queue ID# ' . $item->getId());
                $this->logger->error($e->getMessage());
                $this->logger->error($e->getTraceAsString());

            }

            if ($error) {
                $this->queueItem->setProcessing($item->getId(), 2);
            }

        }

        return json_encode(['Orders' => $this->ordersToSend]);

    }

    /**
     * $item->getProcessing == 2 means there was an error
     */
    public function deleteQueuedItems()
    {

        foreach ($this->queueItems as $item) {
            if ($item->getProcessing() == 2) {
                continue;
            }
            $item->delete();
        }

    }


    /**
     * @return bool|collection
     */
    protected function getOrderQueueCollection()
    {

        $queueItems = $this->queueCollection->getNext(2);
        $num = $queueItems->count();

        if ($num == 0) {
            return false;
        }

        return $queueItems;
    }

    /**
     * @param $item
     * @return array|bool
     */
    protected function getFormattedOrderInfo($item)
    {

        $data = json_decode($item->getValue());
        $order = $this->orderRepository->get($data->id);

        if (!$order) {
            return false;
        }

        $streetArray = $order->getBillingAddress()->getStreet();
        $billingStreet1 = (isset($streetArray[0]) ? $streetArray[0] : "");
        $billingStreet2 = (isset($streetArray[1]) ? $streetArray[1] : "");

        $payment = $order->getPayment();
        $cardExpiration = $payment->getCcExpMonth() . $payment->getCcExpYear();
        $cardType = $payment->getCcType();
        $cardLast4 = $payment->getCcLast4();
        $cardTotalAmount = number_format($payment->getAmountOrdered(), 2, '.', '');
        $cardApprovalNumber = $payment->getCcApproval();
        $cardAvsResponse = $payment->getCcAvsStatus();

        //Special case for authorizenet payment method
        if ($order->getPayment()->getMethod() == 'authorizenet' && $ccStorage = $payment->getMethodInstance()->getCardsStorage()) {
            if (is_array($cards = $ccStorage->getCards()) && $ccStorage->getCardsCount() > 0) {
                foreach ($cards as $card) {
                    $cardExpiration = $card->getCcExpMonth() . $card->getCcExpYear();
                    $cardType = $card->getCcType();
                    $cardLast4 = $card->getCcLast4();
                    $cardTotalAmount = number_format($card->getProcessedAmount(), 2, '.', '');
                    $cardApprovalNumber = $card->getLastTransId();
                }
            }

        }

        $billing_data = [];


        $billing_data['ID'] = $order->getIncrementId();
        $billing_data['ID_DB'] = $order->getId();

        $billing_data['Source'] = $order->getRemoteIp();

        $billing_data['Date'] = $this->date->date('D M d H:i:s Y T', strtotime($order->getCreatedAt()));
        $billing_data['Numeric-Time'] = $this->date->timestamp($order->getCreatedAt());

        $billing_data['Tax-Charge'] = number_format($order->getTaxAmount(), 2, '.', '');
        $billing_data['Discount'] = number_format($order->getDiscountAmount(), 2, '.', '');
        $billing_data['Total'] = number_format($order->getGrandTotal(), 2, '.', '');
        $billing_data['Subtotal'] = number_format($order->getSubtotal(), 2, '.', '');

        $billing_data['Bill-Address1'] = $billingStreet1;
        $billing_data['Bill-Address2'] = $billingStreet2;
        $billing_data['Bill-City'] = $order->getBillingAddress()->getCity();
        $billing_data['Bill-Country'] = $order->getBillingAddress()->getCountryId();
        $billing_data['Bill-Email'] = $order->getCustomerEmail();
        $billing_data['Bill-Firstname'] = $order->getBillingAddress()->getFirstname();
        $billing_data['Bill-Lastname'] = $order->getBillingAddress()->getLastname();
        $billing_data['Bill-Name'] = $order->getBillingAddress()->getFirstname() . ' ' . $order->getBillingAddress()->getLastname();
        $billing_data['Bill-Phone'] = $order->getBillingAddress()->getTelephone();
        $billing_data['Bill-State'] = $order->getBillingAddress()->getRegion();
        $billing_data['Bill-Zip'] = $order->getBillingAddress()->getPostcode();

        $billing_data['Card-Expiry'] = $cardExpiration;
        $billing_data['Card-Name'] = $cardType;
        $billing_data['Card-Number'] = $cardLast4;
        $billing_data['CardAuth-Amount'] = $cardTotalAmount;
        $billing_data['CardAuth-Approval-Number'] = $cardApprovalNumber;
        $billing_data['CardAuth-Avs-Response'] = $cardAvsResponse;
        $billing_data['Currency'] = $order->getOrderCurrency()->getCode();

        $n = 0;

        $productData = [];

        foreach ($order->getAllVisibleItems() as $item) {

            $product = $item->getProduct();

            if ($product->getSqquidInclude() === null) {
                continue;
            }

            if ($item->getHasChildren() && $item->getProductType() != ProductType::TYPE_SIMPLE) {

                $n++;
                //Configurable
                foreach ($item->getChildrenItems() as $childItem) {
                    $productData[]= $this->getItemInfo($childItem);
                }

            } elseif ($item->getProductType() === ProductType::TYPE_SIMPLE) {//Simple
                $n++;
                $productData[]= $this->getItemInfo($item);

            }

        }

        $shippingStreet1 = "";
        $shippingStreet2 = "";
        $shippingCity = "";
        $shippingCountryId = "";
        $shippingFirstname = $order->getBillingAddress()->getFirstname();
        $shippingLastname = $order->getBillingAddress()->getLastname();
        $shippingName = $shippingFirstname . ' ' . $shippingLastname;
        $shippingTelephone = "";
        $shippingRegion = "";
        $shippingPostcode = "";

        $shippingAddress = $order->getShippingAddress();

        if ($shippingAddress) {
            $shippingStreetArray = $shippingAddress->getStreet();
            $shippingStreet1 = (isset($shippingStreetArray[0]) ? $shippingStreetArray[0] : "");
            $shippingStreet2 = (isset($shippingStreetArray[1]) ? $shippingStreetArray[1] : "");

            $shippingCity = $shippingAddress->getCity();
            $shippingCountryId = $shippingAddress->getCountryId();
            $shippingFirstname = $shippingAddress->getFirstname();
            $shippingLastname = $shippingAddress->getLastname();
            $shippingName = $shippingFirstname . ' ' . $shippingLastname;
            $shippingTelephone = $shippingAddress->getTelephone();
            $shippingRegion = $shippingAddress->getRegion();
            $shippingPostcode = $shippingAddress->getPostcode();
        }

        $shipping_data = [];

        $shipping_data['Ship-Address1'] = $shippingStreet1;
        $shipping_data['Ship-Address2'] = $shippingStreet2;
        $shipping_data['Ship-City'] = $shippingCity;
        $shipping_data['Ship-Country'] = $shippingCountryId;
        $shipping_data['Ship-Firstname'] = $shippingFirstname;
        $shipping_data['Ship-Lastname'] = $shippingLastname;
        $shipping_data['Ship-Name'] = $shippingName;
        $shipping_data['Ship-Phone'] = $shippingTelephone;
        $shipping_data['Ship-State'] = $shippingRegion;
        $shipping_data['Ship-Zip'] = $shippingPostcode;
        $shipping_data['Shipping'] = $order->getShippingDescription();
        $shipping_data['Shipping-Charge'] = number_format($order->getShippingAmount(), 2, '.', '');

        $results = array_merge($billing_data, $shipping_data);
        $results['Products'] = $productData;

        return $n > 0 ? $results : false;

    }

    /**
     * @param $item
     * @return array
     */
    protected function getItemInfo($item)
    {

        $productData = [];

        $productData['Item-Code'] = $item->getProduct()->getSku();
        $productData['Item-Id'] = $item->getProduct()->getId();
        $productData['Item-Quantity'] = number_format($item->getQtyOrdered(), 0, '', '');
        $productData['Taxable'] = $item->getProduct()->getTaxClassId() == 0 ? "NO" : "YES";
        $productData['Tax'] = number_format($item->getTaxAmount(), 2, '.', '');
        $productData['Total'] = number_format($item->getRowTotalInclTax(), 2, '.', '');
        $productData['Discount'] = number_format($item->getDiscountAmount(), 2, '.', '');
        $productData['Item-Unit-Price'] = number_format($item->getPrice(), 2, '.', '');
        $productData['Item-Url'] = $item->getProduct()->getProductUrl();

        return $productData;

    }



}