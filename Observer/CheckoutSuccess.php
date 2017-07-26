<?php


namespace Sqquid\Sync\Observer;

use Magento\Framework\Event\ObserverInterface;

class CheckoutSuccess implements ObserverInterface
{
    /**
     * Order Model
     *
     * @var \Magento\Sales\Model\Order $order
     */
    protected $objectManager;
    protected $sqquidHelper;
    protected $logger;
    protected $orderRepository;
    protected $queue;
    protected $jsonHelper;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Sqquid\Sync\Helper\Data $sqquidHelper,
        \Sqquid\Sync\Logger\Logger $logger,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepositoryInterface,
        \Sqquid\Sync\Model\ResourceModel\Queue $queue,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->objectManager = $objectManager;
        $this->sqquidHelper = $sqquidHelper;
        $this->logger = $logger;
        $this->orderRepository = $orderRepositoryInterface;
        $this->queue = $queue;
        $this->jsonHelper = $jsonHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->sqquidHelper->getStoreConfigValue('sqquid_general/setup/enabled')) {
            return $this;
        }

        $order_ids = $observer->getEvent()->getOrderIds();
        $order = $this->orderRepository->get($order_ids[0]);

        $hasSqquidProduct = false;

        foreach ($order->getAllVisibleItems() as $item) {
            if ($item->getProduct()->getSqquidInclude() === '1') {
                $hasSqquidProduct = true;
            }
        }

        if (!$hasSqquidProduct) {
            return $this;
        }

        $key = 'order::' . $order_ids[0];
        $this->queue->insertOrUpdate($key, $this->jsonHelper->jsonEncode(['id' => $order_ids[0]]), 2);
        return $this;
    }
}
