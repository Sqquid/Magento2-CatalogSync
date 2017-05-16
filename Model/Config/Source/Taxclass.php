<?php

namespace Sqquid\Sync\Model\Config\Source;

class Taxclass implements \Magento\Framework\Option\ArrayInterface
{

    protected $_taxClass;

    public function __construct(
    \Magento\Tax\Model\TaxClass\Source\Product $taxClass
    )
    {
        $this->_taxClass = $taxClass;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_taxClass->getAllOptions();
    }
}
