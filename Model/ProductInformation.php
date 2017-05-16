<?php namespace Sqquid\Sync\Model;

use \Sqquid\Sync\Api\Data\ProductInformationInterface;

class ProductInformation implements ProductInformationInterface
{

    /**
     * The sku for this cart entry.
     * @var string
     */
    public $sku;

    /**
     * The quantity value for this cart entry.
     * @var int
     */
    public $qty;

    /**
     * The parent_id value for this cart entry.
     * @var int
     */
    public $parentId;

    /**
     * The catalogId value for product
     * @var int
     */
    public $catalogId;

    /**
     * The name of the product
     * @var string
     */
    public $name;

    /**
     * The description of the product
     * @var string
     */
    public $description;

    /**
     * The short description of the product
     * @var string
     */
    public $shortDescription;

    /**
     * The type of the product
     * @var int
     */
    public $typeId;

    /**
     * The weight of the product
     * @var int
     */
    public $weight;

    /**
     * The attributes of product
     * @var array
     */
    public $Attributes;

    /**
     * The brand of product
     * @var string
     */
    public $brand;

    /**
     * The attribute2 of product
     * @var int
     */
    public $visibility;

    /**
     * The price of product
     * @var int
     */
    public $price;

    /**
     * The priceSpecial of product
     * @var int
     */
    public $priceSpecial;

    /**
     * The priceMsrp of product
     * @var int
     */
    public $priceMsrp;

    /**
     * The data of product
     * @var string[]
     */
    public $data;

    /**
     * The source of product
     * @var int
     */
    public $source;

    /**
     * The createdAt of product
     * @var date
     */
    public $createdAt;

    /**
     * The updatedAt of product
     * @var date
     */
    public $updatedAt;

    /**
     * The categories of product
     * @var string[]
     */
    public $categories;

    /**
     * The children of product
     * @var \Sqquid\Sync\Api\Data\ProductInformationInterface[]
     */
    public $children;


    /**
     * Gets the sku.
     *
     * @api
     * @return string
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * Sets the sku.
     *
     * @api
     * @param string $sku
     * @return void
     */
    public function setSku($sku)
    {
        $this->sku = $sku;
    }

    /**
     * Gets the quantity.
     *
     * @api
     * @return int
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * Sets the quantity.
     *
     * @api
     * @param int $qty
     * @return void
     */
    public function setQty($qty)
    {
        $this->qty = $qty;
    }

    /**
     * Set parent_id
     * @param type $parentId
     * @return void
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
    }

    /**
     * Gets parent_id
     * @return int
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Sets Category Id
     * @param int $catalogId
     * @return void
     */
    public function setCatalogId($catalogId)
    {
        $this->catalogId = $catalogId;
    }

    /**
     * Gets catalog id
     * @return int Category Id
     */
    public function getCatalogId()
    {
        return $this->catalogId;
    }

    /**
     * Sets product name
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gets Product name
     * @return string product name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets Description
     * @param string $description
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Gets Descrption
     * @return string Description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets short descrption of product
     * @param string $shortDescription
     * @return void
     */
    public function setShortDescription($shortDescription)
    {
        $this->shortDescription = $shortDescription;
    }

    /**
     * Gets short description
     * @return string short description
     */
    public function getShortDescription()
    {
        return $this->shortDescription;
    }

    /**
     * Sets product type id
     * @param int $typeId
     * @return void
     */
    public function setTypeId($typeId)
    {
        $this->typeId = $typeId;
    }

    /**
     * Gets Product Type
     * @return int Type Id
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * Sets Weight of product
     * @param int $weight
     * @return void
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * Gets weight of product
     * @return int Weight
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Sets Attributes value
     * @param mixed $attributes
     * @return void
     */
    public function setAttributes($attributes)
    {
        $this->Attributes = $attributes;
    }

    /**
     * Gets Attributes value
     * @return mixed attributes
     */
    public function getAttributes()
    {
        return $this->Attributes;
    }


    /**
     * Sets Brand to product
     * @param string $brand
     * @return void
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
    }

    /**
     * Gets brand of product
     * @return string brand
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * Sets visibility of product
     * @param int visibility
     * @return void
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;
    }

    /**
     * Gets visibility of product
     * @return int visibility
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Sets Product Price
     * @param float $price
     * @return void
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * Gets Product price
     * @return float price
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Sets Product special price
     * @param float $priceSpecial
     * @return void
     */
    public function setPriceSpecial($priceSpecial)
    {
        $this->priceSpecial = $priceSpecial;
    }

    /**
     * Gets Special Price of product
     * @return float special price
     */
    public function getPriceSpecial()
    {
        return $this->priceSpecial;
    }

    /**
     * Sets MSRP price of product
     * @param float $priceMsrp
     * @return void
     */
    public function setPriceMsrp($priceMsrp)
    {
        $this->priceMsrp = $priceMsrp;
    }

    /**
     * Gets MSRP price
     * @return float msrp price
     */
    public function getPriceMsrp()
    {
        return $this->priceMsrp;
    }

    /**
     * Sets Data
     * @param string[] $data
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Gets data
     * @return string[] data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Sets Source
     * @param string $source
     * @return void
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * Gets Source
     * @return string source
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Sets Created at
     * @param string $createdAt
     * @return void
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Gets Created at
     * @return string created at
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Sets Updated at
     * @param string $updatedAt
     * @return void
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Get updated at
     * @return string Updated at
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Sets categories
     * @param string[]
     * @return void
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;
    }

    /**
     * Gest Categories
     * @return string[]|null
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Sets Children
     * @param \Sqquid\Sync\Api\Data\ProductInformationInterface[]
     * @return void
     */
    public function setChildren($children)
    {
        $this->children = $children;
    }

    /**
     * Gets Children of products
     * @return \Sqquid\Sync\Api\Data\ProductInformationInterface[]
     */
    public function getChildren()
    {
        return $this->children;
    }
}
