<?php

namespace Sqquid\Sync\Model;

class Worker
{

    protected $logger;
    protected $jsonDecoder;

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
        \Sqquid\Sync\Model\Services\CategoriesSync $categoriesSync

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

        //$appState->setAreaCode('adminhtml'); // for CLI testing
    }


    /**
     * Process queue items (Cron)
     */
    public function processQueueItems()
    {

        if (!$this->sqquidHelper->getStoreConfigValue('sqquid_general/setup/enabled')) {
            return $this;
        }

        $batchSize = $this->sqquidHelper->getStoreConfigValue('sqquid_general/advanced/batch_size');

        $this->processBatch($batchSize);

        return $this;
    }


    /**
     * @param int $batchSize
     * @return $this
     */
    protected function processBatch($batchSize = 50)
    {

        $queueItems = $this->queueCollection->getNext($batchSize, 1);

        if (count($queueItems) == 0) {
            return $this;
        }

        $memoryStart = memory_get_usage();
        $startTime = microtime(true);
        $queueKey = $this->sqquidHelper->nDigitRandom(5);
        $this->logger->info("# " . $queueKey . " | (Starting Queue) | Batch : " . $batchSize);

        foreach ($queueItems as $item) {

            try {

                $this->queueItem->setProcessing($item->getId());
                $this->processQueueItem($item);

            } catch (\Exception $e) {

                //TODO: (low priority) try and use transactions in case that the process fails. For now, add good logging for all cases
                $this->logger->error($e->getMessage());

            }

            $item->delete();

        }

        $memoryEnd = memory_get_usage();
        $endTime = microtime(true);
        $timeSpent = $this->sqquidHelper->secondsToTime($endTime - $startTime);
        $memoryUsed = $this->sqquidHelper->formatBytes($memoryEnd - $memoryStart);
        $this->logger->info("# " . $queueKey . " | (Ending Queue) | " . $batchSize . " | Memory Used: " . $memoryUsed. ' | Time: '.$timeSpent);


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
                    $this->logger->error('Child Product not created:: ' . print_r($childData, TRUE));
                    continue;
                }

                if ($attributeData = $this->attributesSync->processAttributes($product, $childData)) {
                    $configurableProductsData[$product->getId()] = $attributeData;
                }

            }

        }

        $categoryIds = $this->categoriesSync->getOrCreateCategoryIds($data); // we do this out here so we can keep the cache alive in this model
        $product = $this->productsSync->createOrUpdate($data, false, $configurableProductsData, $categoryIds);
        $this->attributesSync->processAttributes($product, $data);

        return;
    }


}

