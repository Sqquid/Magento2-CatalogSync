<?php

namespace Sqquid\Sync\Model;

use \Magento\Framework\Model\AbstractModel;

class Queue extends AbstractModel
{
    /**
     * Initialize resource model
     * @return void
     */
    public function _construct()
    {
        $this->_init('Sqquid\Sync\Model\ResourceModel\Queue');
    }

    /**
     * Insert a new key/value pair into the queue. If it exists, overwrite the value and update the created at date
     *
     * @param $key
     * @param $value
     * @param $type_id
     * @return mixed
     */
    public function insertOrUpdate($key, $value, $type_id) {
        return $this->_getResource()->insertOrUpdate($key, $value, $type_id);
    }

    /**
     * Set the item in the queue as processing
     *
     * @param $id
     * @return mixed
     */


    public function setProcessing($id, $value = 1){
        return $this->_getResource()->setProcessing($id, $value);
    }


}

