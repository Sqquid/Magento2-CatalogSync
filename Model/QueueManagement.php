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
class QueueManagement implements \Sqquid\Sync\Api\QueueManagementInterface
{

    public $queue;
    public $jsonHelper;
    public $logger;

    protected $sqquidHelper;

    public function __construct(
        \Sqquid\Sync\Model\ResourceModel\Queue $queue,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Sqquid\Sync\Logger\Logger $logger,
        \Sqquid\Sync\Helper\Data $sqquidHelper
    ) {
        $this->queue = $queue;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->sqquidHelper = $sqquidHelper;
    }

    /**
     * Handles sync API calls coming from Sqquid
     *
     * @param \Sqquid\Sync\Api\Data\ProductInformationInterface[] $products
     * @return string
     * @throws InputException
     */
    public function updateQueue($products)
    {

        if (!$this->sqquidHelper->getStoreConfigValue('sqquid_general/setup/enabled')) {
            throw new InputException(__('Error'));
        }

        if (empty($products)) {
            throw new InputException(__('No data found.'));
        }

        //split into configurable products, generate the right key for each, then insert all of them into the queue
        $memoryStart = memory_get_usage();
        $startTime = microtime(true);
        $queueKey = $this->sqquidHelper->nDigitRandom(5);
        $this->logger->info("# ".$queueKey." | Starting to importing  batch size ".count($products));

        //////////////////////
        foreach ($products as $product) {
            $key = 'product::' . $product->getSku();
            $this->queue->insertOrUpdate($key, $this->jsonHelper->jsonEncode($product), 1);
        }
        //////////////////////

        $memoryEnd = memory_get_usage();
        $endTime = microtime(true);
        $timeSpent = $this->sqquidHelper->secondsToTime($endTime - $startTime);
        $memoryUsed = $this->sqquidHelper->formatBytes($memoryEnd - $memoryStart);

        $this->logger->info("# ".$queueKey." | Finished importing batch size ".count($products)." | Memory Used: " . $memoryUsed. ' | Time: '.$timeSpent);

        $result = "CRUSHING IT!";
        return $result;
    }
}
