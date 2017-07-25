<?php
/**
 * Manages the Queue: based on API calls it will insert items into the queue
 * making sure that any repeat updates that were not processed as of yet will
 * get overwritten instead of simply added as duplicates.
 * This queue is data agnostic although will mostly handle product insert/updates.
 */
namespace Sqquid\Sync\Model;

use Magento\Framework\Exception\InputException;

/**
 * Defines the implementation class of the ProductManagement service.
 */
class OrderManagement implements \Sqquid\Sync\Api\OrderManagementInterface
{

    public $queue;
    public $jsonHelper;
    public $logger;
    public $ordersSync;

    protected $sqquidHelper;

    public function __construct(
        \Sqquid\Sync\Model\ResourceModel\Queue $queue,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Sqquid\Sync\Logger\Logger $logger,
        \Sqquid\Sync\Helper\Data $sqquidHelper,
        \Sqquid\Sync\Model\Services\OrdersSync $ordersSync
    )
    {
        $this->queue = $queue;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->sqquidHelper = $sqquidHelper;
        $this->ordersSync = $ordersSync;
    }

    /**
     * Handles order API calls coming from Sqquid
     *
     * @return string
     * @throws InputException
     */
    public function orderQueue()
    {

        if (!$this->sqquidHelper->getStoreConfigValue('sqquid_general/setup/enabled')) {
            throw new InputException(__('Error'));
        }
        $json = $this->ordersSync->getQueuedOrderJSON();
        $this->ordersSync->deleteQueuedItems();
        return $json;
    }
}
