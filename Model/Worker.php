<?php

namespace Sqquid\Sync\Model;

class Worker
{

    protected $logger;
    protected $jsonDecoder;
    protected $schedule;

    protected $queueCollection;
    protected $queueItem;

    protected $productsSync;
    protected $attributesSync;
    protected $categoriesSync;

    protected $categories;
    protected $rootCategories;
    protected $defaultCategory;
    protected $sqquidHelper;

    public function __construct(
        \Sqquid\Sync\Model\ResourceModel\Queue\Collection $queueCollection,
        \Sqquid\Sync\Model\ResourceModel\Queue $queueItem,
        \Sqquid\Sync\Logger\Logger $logger,
        \Magento\Framework\Json\Decoder $jsonDecoder,
        \Magento\Framework\App\State $appState,
        \Sqquid\Sync\Helper\Data $sqquidHelper,
        \Sqquid\Sync\Model\Services\ProductsSync $productsSync,
        \Sqquid\Sync\Model\Services\AttributesSync $attributesSync,
        \Sqquid\Sync\Model\Services\CategoriesSync $categoriesSync,
        \Magento\Cron\Model\Schedule $schedule

    )
    {
        $this->productsSync = $productsSync;
        $this->attributesSync = $attributesSync;
        $this->categoriesSync = $categoriesSync;

        $this->queueCollection = $queueCollection;
        $this->queueItem = $queueItem;
        $this->logger = $logger;
        $this->jsonDecoder = $jsonDecoder;
        $this->sqquidHelper = $sqquidHelper;
        $this->schedule = $schedule;

        if (strstr($_SERVER['PHP_SELF'], 'n98-magerun2')) {
            $appState->setAreaCode('adminhtml'); // for CLI testing
        }

    }


    /**
     * Process queue items (Cron)
     */
    public function processQueueItems()
    {

        if (!$this->sqquidHelper->getStoreConfigValue('sqquid_general/setup/enabled')) {
            return $this;
        }

        if ($this->isRunningCurrently()) {
            return $this;
        }

        $batchSize = $this->sqquidHelper->getStoreConfigValue('sqquid_general/advanced/batch_size');

        $this->processBatch($batchSize);

        return $this;
    }

    public function isRunningCurrently()
    {
        $schedule = $this->schedule->getCollection()
            ->addFieldToFilter('job_code', 'sqquid_sync_worker')
            ->addFieldToFilter('status', 'running');

        if ($schedule->count() > 1) { // 1 meaning the current job
            return true;
        }

        return false;

    }


    /**
     * @param int $batchSize
     * @return $this
     */
    protected function processBatch($batchSize = 50)
    {

        $queueItems = $this->queueCollection->getNext(1, $batchSize);
        $totalProcessed = 0;
        if (count($queueItems) == 0) {
            return $this;
        }

        $memoryStart = memory_get_usage();
        $startTime = microtime(true);
        $queueKey = $this->sqquidHelper->nDigitRandom(5);
        $this->logger->info("# " . $queueKey . " | (Starting Queue) | Batch : " . $batchSize);

        foreach ($queueItems as $item) {

            if ($this->queueItem->getProcessing($item->getId()) == 1) {
                continue; // we do this to make sure this isn't being consumed by another parallel cron processes
            }

            $error = false;

            try {

                $this->queueItem->setProcessing($item->getId());
                $this->processQueueItem($item);
                $totalProcessed++;

            } catch (\Exception $e) {

                //TODO: (low priority) try and use transactions in case that the process fails. For now, add good logging for all cases
                $error = true;
                $this->logger->error('------------------------');
                $this->logger->error('Queue ID# ' . $item->getId());
                $this->logger->error($e->getMessage());
                $this->logger->error($e->getTraceAsString());
                //$this->logger->error(mysql_error());

            }

            if (!$error) {
                $item->delete();
            } else {
                $this->queueItem->setProcessing($item->getId(), 2);
            }

        }

        $memoryEnd = memory_get_usage();
        $endTime = microtime(true);
        $timeSpent = $this->sqquidHelper->secondsToTime($endTime - $startTime);
        $memoryUsed = $this->sqquidHelper->formatBytes($memoryEnd - $memoryStart);
        $this->logger->info("# " . $queueKey . " | (Ending Queue) | Batched: " . $batchSize . " | # Processed: " . $totalProcessed . " | Memory Used: " . $memoryUsed . ' | Time: ' . $timeSpent);

        return $this;
    }

    /**
     * @param \Sqquid\Sync\Model\Queue $queueItem
     */
    protected function processQueueItem($queueItem)
    {

        $data = $this->jsonDecoder->decode($queueItem->getValue());
        $configurableProductsData = null;

        if (isset($data['children'])) {

            //Create or Update the simple products
            foreach ($data['children'] as $childData) {

                if (!$product = $this->productsSync->createOrUpdate($childData, true)) {
                    continue;
                }

                if ($attributeData = $this->attributesSync->processAttributes($product, $childData)) {
                    $configurableProductsData[$product->getId()] = $attributeData;
                }

            }

        }

        $categoryIds = $this->categoriesSync->getOrCreateCategoryIds($data);

        if ($product = $this->productsSync->createOrUpdate($data, false, $configurableProductsData, $categoryIds)) {
            $this->attributesSync->processAttributes($product, $data);
        }

        return;
    }


}

