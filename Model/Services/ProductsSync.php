<?php

namespace Sqquid\Sync\Model\Services;

use Magento\Catalog\Model\Product\Type as ProductType;

class ProductsSync
{

    protected $logger;
    protected $productFactory;
    protected $resourceConnection;
    protected $productRepository;
    protected $registry;
    protected $transactionFactory;
    protected $stockItemRepository;

    protected $sqquidHelper;
    protected $visibility;
    protected $visibilityOverride;
    protected $assignTaxClass;
    protected $assignTaxClassOverride;
    protected $attributesSync;
    protected $storeId;
    protected $storeUrlKeys;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Sqquid\Sync\Helper\Data $sqquidHelper,
        \Sqquid\Sync\Model\Services\AttributesSync $attributesSync,
        \Magento\Eav\Api\AttributeRepositoryInterface $eavAttributeRepository,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository
    )
    {
        $this->storeId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        $this->logger = $logger;
        $this->transactionFactory = $transactionFactory;

        $this->productFactory = $productFactory;
        $this->stockItemRepository = $stockItemRepository;
        $this->sqquidHelper = $sqquidHelper;
        $this->attributesSync = $attributesSync;
        $this->eavAttributeRepository = $eavAttributeRepository;
        $this->resourceConnection = $resourceConnection;

        $this->productRepository = $productRepository;
        $this->registry = $registry;

        $this->visibility = $this->sqquidHelper->getStoreConfigValue('sqquid_general/visiblity/visibility_id');
        $this->visibilityOverride = $this->sqquidHelper->getStoreConfigValue('sqquid_general/visiblity/overwritevisibility');
        $this->assignTaxClass = $this->sqquidHelper->getStoreConfigValue('sqquid_sync/taxclass/assigntaxclass');
        $this->assignTaxClassOverride = $this->sqquidHelper->getStoreConfigValue('sqquid_sync/taxclass/overwritetaxclass');

        $this->initStoreUrlKeys();
    }


    /**
     * This builds up the product keys in memory for us to check if for duplicate URLs
     * TODO: figure this out without a resourceConnection
     *
     * Dear other developers, I wasn't sure how to get all the actual values of $attribute based on store ID.
     * Maybe I missed something but I couldn't find what method I should use to retrieve this information using the
     * attribute model below ($attribute). In the meantime I'm just doing a simple select.
     *
     */
    public function initStoreUrlKeys()
    {
        $this->storeUrlKeys = [];
        $attribute = $this->eavAttributeRepository->get(\Magento\Catalog\Api\Data\ProductAttributeInterface::ENTITY_TYPE_CODE, 'url_key');
        $connection = $this->resourceConnection->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        $results = $connection->fetchAll('SELECT * FROM `catalog_product_entity_varchar` WHERE store_id=' . $this->storeId . ' AND attribute_id=' . $attribute->getId());

        foreach ($results as $result) {
            if (!isset($this->storeUrlKeys[$result['value']])) {
                $this->storeUrlKeys[$result['value']] = $result['entity_id'];
            }
        }

    }

    public function productSave(\Magento\Catalog\Model\Product $product, $num = 0)
    {
        if ($num !== 0) {
            $oldKey = $product->getUrlKey();
            $product->setUrlKey($oldKey . '-' . $num);
        }

        try {

            $saveTransaction = $this->transactionFactory->create();
            $saveTransaction->addObject($product);
            $saveTransaction->save();

            //$product->save();

        } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {


            /**
             * TODO: Make this better somehow
             *
             * Dear other developers. At this point in the game I want to know if the product URL key is conflicting with anything else.
             * It's just that I want to know if the exception is "AlreadyExistsException".. I also need to know if this exception
             * pertains to the URL key. What's the best way to do this?
             *
             */
            if ($e->getMessage() == 'URL key for specified store already exists.') {

                // this means is conflicting with another URL in the catalog.. maybe CMS.. maybe Categories
                $num++;
                $product = $this->productSave($product, $num);

            } else {
                throw $e; // get caught up the stream

            }
        }

        return $product;

    }

    /**
     * Create or update a simple product, return the product
     */
    public function createOrUpdate($data, $isAssociatedProduct, $configurableProductsData = null, $categoryIds = null)
    {
        if (!isset($data['sku']) || !isset($data['name'])) {
            throw new \InvalidArgumentException('Data is Missing.');
        }

        $product = $this->productFactory->create();

        if ($productId = $product->getIdBySku($data['sku'])) {
            $product->load($productId);
            $product->setIsObjectNew(false);

            if ($product->getSqquidInclude() === "0") {
                return false; // we need to skip it
            }

        } else {
            $product->setIsObjectNew(true);
        }

        //general stuff
        $product
            ->setStoreId($this->storeId)
            ->setAttributeSetId(4)
            ->setWebsiteIds([1])
            ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);

        //actual data from our json
        $product
            ->setName($data['name'])
            ->setSku($data['sku'])
            ->setShortDescription($data['shortDescription'])
            ->setDescription($data['description'])
            ->setPrice($data['price'])
            ->setWeight($data['weight']);

        $product->setSpecialPrice($data['priceSpecial']);

        $product = $this->setVisibility($product, $isAssociatedProduct);
        $product = $this->setTaxClassId($product);

        if ($categoryIds && is_array($categoryIds)) {
            $product->setCategoryIds($categoryIds);
        }

        $product = $this->setUrlKey($product);

        if ((!$configurableProductsData || count($configurableProductsData) == 0)) {

            $product->setTypeId(ProductType::TYPE_SIMPLE);

            if (isset($data['qty']) && !is_null($data['qty'])) {
                $product = $this->setInventory($product, $isAssociatedProduct);
            }

            return $this->productSave($product);
        }

        // CONFIGURABLE STUFF !!!!

        $product->setTypeId(\Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE);
        $product->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1]);

        $product = $this->attributesSync->attachAttributesFromChildData($product, $configurableProductsData);
        $product = $this->productSave($product);

        if ($product->setIsObjectNew() == false) {
            $this->removeOldAssociatedProducts($product, array_keys($configurableProductsData));
        }

        $this->storeUrlKeys[$product->getUrlKey()] = $product->getId(); // so we can check this later since the list of keys is cached.

        return $product;

    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param array $correctChildIds
     */
    private function removeOldAssociatedProducts(\Magento\Catalog\Model\Product $product, $correctChildIds)
    {
        $children = $product->getTypeInstance()->getUsedProducts($product);

        foreach ($children as $child) {
            if (in_array($child->getId(), $correctChildIds)) {
                continue; // cool. no need to do anything.. we like this product.
            }
            $this->registry->register('isSecureArea', true);
            $this->productRepository->delete($child);
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\Catalog\Model\Product
     */
    private function setUrlKey(\Magento\Catalog\Model\Product $product)
    {

        $key = $product->formatUrlKey($product->getName());

        if (!isset($this->storeUrlKeys[$key])) {
            return $product; // no conflict
        }

        if ($this->storeUrlKeys[$key] == $product->getId()) {
            return $product; // no conflict we're updated the product
        }

        $newKey = $product->formatUrlKey($product->getName()) . '-1';
        $product->setUrlKey($newKey);

        return $product;

    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\Catalog\Model\Product
     */
    private function setTaxClassId(\Magento\Catalog\Model\Product $product)
    {

        if ($product->getIsObjectNew() == true || !$this->assignTaxClassOverride) {
            $product->setTaxClassId($this->assignTaxClass);
        }

        return $product;

    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param bool $isAssociatedProduct
     * @return \Magento\Catalog\Model\Product
     */
    private function setVisibility(\Magento\Catalog\Model\Product $product, $isAssociatedProduct)
    {
        if ($isAssociatedProduct) {
            $product->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE);
            return $product;
        }

        if ($product->getIsObjectNew() == true || !$this->visibilityOverride) {
            $product->setVisibility($this->visibility);
            return $product;
        }

        return $product;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param $qty
     * @return \Magento\Catalog\Model\Product
     */
    private function setInventory(\Magento\Catalog\Model\Product $product, $qty)
    {
        if ($product->getId() && $product->getIsObjectNew() != true) {

            $productStock = $this->stockItemRepository->get($product->getId());
            if ($productStock->getQty() == $qty){
                return $product;
            } else {
                return $this->setStockData($product, $qty);
            }

        } else {

            return $this->setStockData($product, $qty);

        }

    }


    private function setStockData(\Magento\Catalog\Model\Product $product, $qty)
    {
        $product
            ->setQuantityAndStockStatus(['qty' => $qty, 'is_in_stock' => 1])
            ->setStockData(array(
                'use_config_manage_stock' => 0, //'Use config settings' checkbox
                'manage_stock' => 1, //manage stock
                'is_in_stock' => 1, //Stock Availability
                'qty' => $qty));

        return $product;
    }



}

