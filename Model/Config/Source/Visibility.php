<?php 

namespace Sqquid\Sync\Model\Config\Source;

class Visibility implements \Magento\Framework\Option\ArrayInterface
{

    protected $_visibility;

    public function __construct(
    \Magento\Catalog\Model\Product\Visibility $visibility
    )
    {
        $this->_visibility = $visibility;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_visibility->getOptionArray();
    }
}
