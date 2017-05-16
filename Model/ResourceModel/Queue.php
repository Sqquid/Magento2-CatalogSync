<?php

namespace Sqquid\Sync\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Queue extends AbstractDb
{
    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('sqquid_queue', 'id');
    }

    /**
     * Insert or update based on UNIQUE combo 'key-processing'
     *
     * @param $key
     * @param $value
     */
    public function insertOrUpdate($key, $value, $type_id) {
        $connection = $this->getConnection();

        $data = [
            'key' => $key,
            'value' => $value,
            'type_id' => $type_id,
            'created_at' => (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT),
            'processing' => false,
        ];

        $connection->insertOnDuplicate($connection->getTableName('sqquid_queue'), $data, ['value','created_at']);
    }

    /**
     * Set the item in the queue as processing
     *
     * @param $id
     * @return mixed
     */
    public function setProcessing($id) {
        if ($id) {
            $this->getConnection()->update(
                $this->getMainTable(),
                ['processing' => 1],
                ['id = ?' => (int)$id]
            );

            return true;
        }

        return false;
    }
}

