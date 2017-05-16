<?php namespace Sqquid\Sync\Api;

interface QueueManagementInterface
{

    /**
     * Do minimal parsing on the JSON received and insert the data into a queue
     * for later processing via a cron worker
     *
     * @param \Sqquid\Sync\Api\Data\ProductInformationInterface[] $products
     * @return mixed
     */
    public function updateQueue($products);
}
