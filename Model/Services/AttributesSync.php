<?php

namespace Sqquid\Sync\Model\Services;

class AttributesSync
{

    protected $productAttributeRepositoryInterface;
    protected $productAttributeInterfaceFactory;
    protected $tableFactory;
    protected $attributeOptionManagement;
    protected $optionLabelFactory;
    protected $attributeOptionInterfaceFactory;
    protected $productResource;
    protected $optionsFactory;
    protected $attributeFactory;
    protected $attributeGroupInterfaceFactory;
    protected $attributeGroupInterface;
    protected $entityType;
    protected $entityTypeCode;
    protected $entityTypeId;
    protected $attributeSetId;

    protected $sqquidHelper;
    protected $sqquidGroup;
    protected $groupCode;
    protected $cacheAttributeData;
    protected $cacheAttributeValueData;
    protected $productAttributeData;

    protected $groupName;


    public function __construct(
        \Sqquid\Sync\Helper\Data $sqquidHelper,
        \Magento\Eav\Model\Entity\Attribute\Source\TableFactory $tableFactory,
        \Magento\Eav\Api\AttributeOptionManagementInterface $attributeOptionManagement,
        \Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory $optionLabelFactory,
        \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory $attributeOptionInterfaceFactory,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $productAttributeRepositoryInterface,
        \Magento\Catalog\Api\Data\ProductAttributeInterfaceFactory $productAttributeInterfaceFactory,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\ConfigurableProduct\Helper\Product\Options\Factory $optionsFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Eav\Api\Data\AttributeGroupInterfaceFactory $attributeGroupInterfaceFactory,
        \Magento\Eav\Api\Data\AttributeGroupInterface $attributeGroupInterface,
        \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory
    )
    {

        $this->attributeGroupInterfaceFactory = $attributeGroupInterfaceFactory;
        $this->attributeGroupInterface = $attributeGroupInterface;
        $this->attributeFactory = $attributeFactory;

        $this->sqquidHelper = $sqquidHelper;

        $this->productResource = $productResource;
        $this->productAttributeRepositoryInterface = $productAttributeRepositoryInterface;
        $this->productAttributeInterfaceFactory = $productAttributeInterfaceFactory;
        $this->tableFactory = $tableFactory;
        $this->attributeOptionManagement = $attributeOptionManagement;
        $this->optionLabelFactory = $optionLabelFactory;
        $this->attributeOptionInterfaceFactory = $attributeOptionInterfaceFactory;
        $this->optionsFactory = $optionsFactory;

        $this->cacheAttributeData = [];
        $this->cacheAttributeValueData = [];

        /*
        * We need to check if our group is on the default attribute set.. if not we should create
        */
        $this->groupName = "Sqquid";
        $this->groupCode = $this->sqquidHelper->convertStringToCode($this->groupName);
        $this->entityTypeId = 4;  // \Magento\Catalog\Model\Product::ENTITY
        $this->attributeSetId = $productFactory->create()->getDefaultAttributeSetId();

        $this->sqquidGroup = $this->attributeGroupInterface->load($this->groupName, 'attribute_group_name');

        if (!$this->sqquidGroup->itemExists()) {

            $group = $this->attributeGroupInterfaceFactory->create();
            $group->setAttributeSetId($this->attributeSetId)
                ->setAttributeGroupName($this->groupName)
                ->setSortOrder(100)
                ->setAttributeGroupCode($this->groupCode);

            $group->save();

            $this->sqquidGroup = $group;

        }

        $this->setExclusionAttribute();

    }

    protected function setExclusionAttribute()
    {
      $this->findOrCreateAttribute('Exclude', 'boolean', 'Magento\Eav\Model\Entity\Attribute\Source\Boolean');
    }


    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param array $data
     * @return array|bool
     */
    public function processAttributes(\Magento\Catalog\Model\Product $product, $data)
    {

        if (!isset($data['Attributes'])) {
            return false;
        }

        $productAttributeData = [];

        foreach ($data['Attributes'] as $attribute) {


            if (is_null($attribute['value'])) {
                continue;
            }

            $type = null;

            if (isset($attribute['type'])) {
                $type = $attribute['type'];
            }


            if ($result = $this->setAttributeData($product, $attribute['label'], $attribute['value'], $type)) {
                $productAttributeData[] = $result;
            }


        }

        return $productAttributeData;

    }


    protected function setAttributeData(\Magento\Catalog\Model\Product $product, $label, $value, $type = 'select')
    {

        $createType = $type == 'configurable_select' ? 'select' : $type;
        $attribute = $this->findOrCreateAttribute($label, $createType);


        if ($type == 'text') {

            if ($product->getData($attribute->getAttributeCode()) != $value) {
                $product->setData($attribute->getAttributeCode(), $value);
                $this->productResource->saveAttribute($product, $attribute->getAttributeCode());
            }

        }

        if ($type == 'select' || $type == 'configurable_select') {

            $valueId = $this->findOrCreateValue($attribute, $value);

            if ($product->getAttributeText($attribute->getAttributeCode()) != $value) {
                $product->setData($attribute->getAttributeCode(), $valueId);
                $this->productResource->saveAttribute($product, $attribute->getAttributeCode());
            }

            if ($type == 'configurable_select') {
                $configurationData = [
                    'label' => $attribute->getStoreLabel(),
                    'attribute_id' => $attribute->getId(),
                    'attribute_code' => $attribute->getAttributeCode(),
                    'value_index' => $valueId,
                    'value_label' => $value,
                    'pricing_value' => $product->getPrice()
                ];

                return $configurationData;
            }

        }

        return null;
    }


    public function findOrCreateValue($attribute, $value)
    {

        if (!isset($this->cacheAttributeValueData[$attribute->getId()])) {
            $this->cacheAttributeValueData[$attribute->getId()] = []; // just to set it up
        }

        if (isset($this->cacheAttributeValueData[$attribute->getId()][$value])) {
            return $this->cacheAttributeValueData[$attribute->getId()][$value];
        }

        $valueId = $this->findOrCreateValueFromDatabase($attribute, $value);

        $this->cacheAttributeValueData[$attribute->getId()][$value] = $valueId;

        return $valueId;
    }


    public function findOrCreateValueFromDatabase($attribute, $label)
    {
        if (strlen($label) < 1) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Label for %1 must not be empty.', $attribute->getId())
            );
        }

        if ($optionId = $this->getOptionId($attribute, $label)) {
            return $optionId;
        }

        /** @var \Magento\Eav\Model\Entity\Attribute\OptionLabel $optionLabel */
        $optionLabel = $this->optionLabelFactory->create();
        $optionLabel->setStoreId(0);
        $optionLabel->setLabel($label);

        $option = $this->attributeOptionInterfaceFactory->create();
        $option->setLabel($optionLabel);
        $option->setStoreLabels([$optionLabel]);
        $option->setSortOrder(0);
        $option->setIsDefault(false);

        $this->attributeOptionManagement->add(
            \Magento\Catalog\Model\Product::ENTITY,
            $attribute->getId(),
            $option
        );

        // Get the inserted ID. Should be returned from the installer, but it isn't.
        $optionId = $this->getOptionId($attribute, $label);

        return $optionId;
    }


    public function getOptionId($attribute, $label)
    {

        // We have to generate a new sourceModel instance each time through to prevent it from
        // referencing its _options cache. No other way to get it to pick up newly-added values.

        /** @var \Magento\Eav\Model\Entity\Attribute\Source\Table $sourceModel */
        $sourceModel = $this->tableFactory->create();
        $sourceModel->setAttribute($attribute);

        foreach ($sourceModel->getAllOptions() as $option) {
            $this->cacheAttributeValueData[$attribute->getAttributeId()][$option['label']] = $option['value'];
        }

        // Return option ID if exists
        if (isset($this->cacheAttributeValueData[$attribute->getAttributeId()][$label])) {
            return $this->cacheAttributeValueData[$attribute->getAttributeId()][$label];
        }

        // Return false if does not exist
        return false;
    }

    protected function findOrCreateAttribute($label, $type, $source = null)
    {
        $code = $label . '-' . $type;
        if (isset($this->cacheAttributeData[$code])) {
            return $this->cacheAttributeData[$code];
        }

        $attribute = $this->findOrCreateAttributeFromDatabase($label, $type, $source);
        $this->cacheAttributeData[$code] = $attribute;
        return $attribute;

    }


    public function findOrCreateAttributeFromDatabase($label, $attribute_type, $source = null)
    {

        $code = $this->sqquidHelper->convertStringToCode($label);

        $attribute_code = 'sqquid_' . $code;

        $mustCreateAttribute = false;

        try {
            $attribute = $this->productAttributeRepositoryInterface->get($attribute_code);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            // Yes, if there is a NoSuchEntityException, it means that the attributes does not exist
            $mustCreateAttribute = true;
        }

        if (!$mustCreateAttribute) {
            return $attribute;
        }

        $attribute = $this->productAttributeInterfaceFactory->create();
        $attribute->setEntityTypeId($this->entityTypeId);

        $data = [
            'group' => $this->groupName,
            'attribute_code' => $attribute_code,
            'frontend_label' => $label,
            'backend_type' => '',
            'frontend_input' => $attribute_type,
            'is_required' => false,
            'is_unique' => false,
            'is_user_defined' => true,
            'sort_order' => 100,
            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
            'used_in_product_listing' => false,
            'searchable' => true,
            'comparable' => true,
            'visible_on_front' => true,
            'visible' => true
        ];

        if ($source) {
            $data['source'] = $source;
        }

        $attribute->setData($data);


        $this->productAttributeRepositoryInterface->save($attribute);

        $attributeEntity = $this->attributeFactory->create();
        $attributeEntity->setAttributeSetId($this->attributeSetId)
            ->setEntityTypeId($this->entityTypeId)
            ->setAttributeGroupId($this->sqquidGroup->getId())
            ->setAttributeId($attribute->getId())
            ->setSortOrder(0);

        $attributeEntity->save();

        return $attribute;

    }


    public function attachAttributesFromChildData($product, $configurableProductsData)
    {

        if (!$configurableProductsData) {
            return $product;
        }
        $associatedProductIds = array_keys($configurableProductsData);

        $tempValueArray = [];
        $usedValueArray = [];

        $attributeValueData = [];
        $usedAttributeArray = [];


        foreach ($configurableProductsData as $item) { // get unique attribute IDs

            foreach ($item as $data) {

                if (!isset($tempValueArray[$data['attribute_id']])) {
                    $tempValueArray[$data['attribute_id']] = [];
                }

                if (in_array($data['value_index'], $usedValueArray)) {
                    continue;
                }

                $tempValueArray[$data['attribute_id']][] = [
                    'attribute_id' => $data['attribute_id'],
                    'label' => $data['value_label'],
                    'value_index' => $data['value_index'],
                ];

                $usedValueArray[] = $data['value_index'];

            }
        }

        foreach ($configurableProductsData as $item) { // get unique attribute IDs
            foreach ($item as $data) {

                if (in_array($data['attribute_id'], $usedAttributeArray)) {
                    continue;
                }

                $attributeValueData[] = [
                    'attribute_id' => $data['attribute_id'],
                    'code' => $data['attribute_code'],
                    'label' => $data['label'],
                    'position' => '0',
                    'values' => $tempValueArray[$data['attribute_id']],
                ];

                $usedAttributeArray[] = $data['attribute_id'];

            }
        }

        $configurableOptions = $this->optionsFactory->create($attributeValueData);
        $extensionConfigurableAttributes = $product->getExtensionAttributes();
        $extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
        $extensionConfigurableAttributes->setConfigurableProductLinks($associatedProductIds); // associated product IDs
        $product->setExtensionAttributes($extensionConfigurableAttributes);

        return $product;
    }


}
