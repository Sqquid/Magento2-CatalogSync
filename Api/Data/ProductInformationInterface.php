<?php

namespace Sqquid\Sync\Api\Data;

/**
 * Interface for json object 'products' from Sqquid
 */
interface ProductInformationInterface
{

    /**
     * Gets the sku.
     * @return string
     */
    public function getSku();

    /**
     * Sets the sku.
     *
     * @api
     * @param string $sku
     * @return void
     */
    public function setSku($sku);

    /**
     * Gets the quantity.
     * @return int
     */
    public function getQty();

    /**
     * Sets the quantity.
     *
     * @api
     * @param string $qty
     * @return void
     */
    public function setQty($qty);

    /**
     * Set parent_id
     * @param type $parentId
     * @return void
     */
    public function setParentId($parentId);

    /**
     * Gets parent_id
     * @return int
     */
    public function getParentId();

    /**
     * Sets Category Id
     * @param int $catalogId
     * @return void
     */
    public function setCatalogId($catalogId);

    /**
     * @return int Category Id
     */
    public function getCatalogId();

    /**
     * Sets product name
     * @param string $name
     * @return void
     */
    public function setName($name);

    /**
     * Gets Product name
     * @return string product name
     */
    public function getName();

    /**
     * Sets Description
     * @param string $description
     * @return void
     */
    public function setDescription($description);

    /**
     * Gets Descrption
     * @return string Description
     */
    public function getDescription();

    /**
     * Sets short descrption of product
     * @param string $shortDescription
     * @return void
     */
    public function setShortDescription($shortDescription);

    /**
     * Gets short description
     * @return string short description
     */
    public function getShortDescription();

    /**
     * Sets product type id
     * @param int $typeId
     * @return void
     */
    public function setTypeId($typeId);

    /**
     * Gets Product Type
     * @return int Type Id
     */
    public function getTypeId();

    /**
     * Sets Weight of product
     * @param int $weight
     * @return void
     */
    public function setWeight($weight);

    /**
     * Gets weight of product
     * @return int Weight
     */
    public function getWeight();

    /**
     * Gets Attributes value
     * @return mixed attributes
     */
    public function getAttributes();

    /**
     * Sets Attribute value
     * @param mixed $attributes
     * @return void
     */
    public function setAttributes($attributes);

    /**
     * Sets brand of product
     * @param string $brand
     * @return void
     */
    public function setBrand($brand);

    /**
     * Gets brand of product
     * @return string brand
     */
    public function getBrand();

    /**
     * Sets visibility of product
     * @param int $visibilty
     * @return void
     */
    public function setVisibility($visibility);

    /**
     * Gets visibility of product
     * @return int visibility
     */
    public function getVisibility();

    /**
     * Sets Product Price
     * @param float $price
     * @return void
     */
    public function setPrice($price);

    /**
     * Gets Product price
     * @return float price
     */
    public function getPrice();

    /**
     * Sets Product special price
     * @param float $priceSpecial
     * @return void
     */
    public function setPriceSpecial($priceSpecial);

    /**
     * Gets Special Price of product
     * @return float special price
     */
    public function getPriceSpecial();

    /**
     * Sets MSRP price of product
     * @param float $priceMsrp
     * @return void
     */
    public function setPriceMsrp($priceMsrp);

    /**
     * Gets MSRP price
     * @return float msrp price
     */
    public function getPriceMsrp();

    /**
     * Sets Data
     * @param string[] $data
     * @return void
     */
    public function setData($data);

    /**
     * Gets data
     * @return string[] data
     */
    public function getData();

    /**
     * Sets Source
     * @param string $source
     * @return void
     */
    public function setSource($source);

    /**
     * Gets Source
     * @return string source
     */
    public function getSource();

    /**
     * Sets Created at
     * @param string $createdAt
     * @return void
     */
    public function setCreatedAt($createdAt);

    /**
     * Gets Created at
     * @return string created at
     */
    public function getCreatedAt();

    /**
     * Sets Updated at
     * @param string $updatedAt
     * @return void
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get updated at
     * @return string Updated at
     */
    public function getUpdatedAt();

    /**
     * Sets categories
     * @param string[] 
     * @return void
     */
    public function setCategories($categories);

    /**
     * Gest Categories
     * @return string[]|null
     */
    public function getCategories();

    /**
     * Sets Children
     * @param \Sqquid\Sync\Api\Data\ProductInformationInterface[]
     * @return void
     */
    public function setChildren($children);

    /**
     * Gets Children of products
     * @return \Sqquid\Sync\Api\Data\ProductInformationInterface[]
     */
    public function getChildren();
}
