<?php

namespace Sqquid\Sync\Model\Config\Source;

class Visibility implements \Magento\Framework\Option\ArrayInterface
{
    protected $visibility;

    public function __construct(
        \Magento\Catalog\Model\Product\Visibility $visibility
    ) {
        $this->visibility = $visibility;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->visibility->getOptionArray();
    }
}
