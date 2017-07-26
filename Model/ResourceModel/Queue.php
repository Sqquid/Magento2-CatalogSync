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
     * @param $type_id
     */
    public function insertOrUpdate($key, $value, $type_id)
    {
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
     * @param $id
     * @param int $value
     * @return bool
     */
    public function setProcessing($id, $value = 1)
    {
        if ($id) {
            $this->getConnection()->update(
                $this->getMainTable(),
                ['processing' => $value],
                ['id = ?' => (int)$id]
            );

            return true;
        }

        return false;
    }

    public function getProcessing($id)
    {
        $select = $this->getConnection()->select()->from(
            $this->getMainTable(),
            'processing'
        )->where(
            'id = :id'
        );
        return $this->getConnection()->fetchOne($select, [':id' => $id]);
    }
}
