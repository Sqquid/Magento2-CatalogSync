<?php

namespace Sqquid\Sync\Model\ResourceModel\Queue;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Initialize resource collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Sqquid\Sync\Model\Queue', 'Sqquid\Sync\Model\ResourceModel\Queue');
    }

    /**
     * Get next items in the queue based on batch size
     *
     * @param int $batchSize
     * @param int $type_id
     * @return $this
     */
    public function getNext($batchSize, $type_id)
    {
        $collection = $this
            ->addFieldToSelect('*')
            ->addFieldToFilter('processing', 0)
            ->addFieldToFilter('type_id', $type_id)
            ->setOrder('id', 'asc');

        $collection->getSelect()->limit($batchSize);
        return $collection;
    }
}
