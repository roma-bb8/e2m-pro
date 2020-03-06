<?php

class M2E_E2M_Helper_Magento_Attribute {

    /** @var int $taskId */
    private $taskId;

    /** @var M2E_E2M_Helper_Data $dataHelper */
    private $dataHelper;

    private $updateResource;

    /** @var Mage_Catalog_Model_Product_Action $productAction */
    private $productAction;

    /** @var int $storeId */
    private $storeId;

    /** @var int $attributeSetId */
    private $attributeSetId;

    /** @var Mage_Catalog_Model_Product $product */
    private $product;

    /** @var string $groupName */
    private $groupName;

    /** @var int $groupIds */
    private $groupIds;

    /** @var bool $rename */
    private $rename;

    /** @var bool $text */
    private $text;

    /** @var string $title */
    private $title;

    /** @var string $code */
    private $code;

    /** @var string $value */
    private $value;

    /** @var array $attributeSets */
    private $attributeSets = array();

    //########################################

    /**
     * @param $attribute
     *
     * @return mixed
     */
    private function addValue($attribute) {

        if ($this->text) {
            $this->updateResource->updateAttributes(
                array($this->product->getId()),
                array($attribute->getAttributeCode() => $this->value),
                $this->storeId
            );

            return $attribute;
        }

        $optionId = Mage::getModel('eav/entity_attribute_source_table')
            ->setAttribute($attribute)
            ->getOptionId($this->value);

        if (!$optionId) {

            $attribute->setData('option', array('value' => array('option' => array(
                Mage_Core_Model_App::ADMIN_STORE_ID => $this->value,
                $this->storeId => $this->value
            ))));
            $attribute->save();

            $this->dataHelper->logReport($this->taskId, sprintf(
                'New value: "%s" adding in attribute "%s"',
                $this->value,
                $this->code
            ));

            $optionId = Mage::getModel('eav/entity_attribute_source_table')
                ->setAttribute($attribute)
                ->getOptionId($this->value);
        }

        $this->productAction->updateAttributes(array($this->product->getId()), array(
            $this->code => $optionId
        ), $this->storeId);

        return $attribute;
    }

    //########################################

    /**
     * @param $attribute
     *
     * @return mixed
     * @throws Exception
     */
    private function assignGroup($attribute) {

        if (isset($this->attributeSets[$this->code])) {
            return $attribute;
        }

        $attributes = $this->dataHelper->getMagentoAttributes($this->attributeSetId);
        foreach ($attributes as $code => $item) {
            if ($this->code === $code) {
                $this->attributeSets[$this->code] = true;
                return $attribute;
            }
        }

        $attributes = $this->dataHelper->getMagentoAttributes($this->attributeSetId, true);
        foreach ($attributes as $code => $item) {
            if ($this->code === $code) {
                $this->attributeSets[$this->code] = true;
                return $attribute;
            }
        }

        $attribute->setData('attribute_set_id', $this->attributeSetId);
        $attribute->setData('attribute_group_id', $this->loadAttributeGroupIdByName($this->groupName));
        $attribute->save();

        $this->dataHelper->logReport($this->taskId, sprintf(
            'Attribute: "%s" add to "%s" group',
            $this->code,
            $this->groupName
        ));

        return $attribute;
    }

    /**
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     *
     * @return mixed
     */
    private function renameAttribute($attribute) {

        $frontendLabel = $attribute->getFrontendLabel();
        if ($frontendLabel === $this->title) {
            return $attribute;
        }

        $storeLabels = $attribute->getStoreLabels();
        if ($storeLabels[$this->storeId] === $this->title) {
            return $attribute;
        }

        $storeLabels[Mage_Core_Model_App::ADMIN_STORE_ID] = $frontendLabel;
        $storeLabels[$this->storeId] = $this->title;

        $attribute->setData('frontend_label', $storeLabels);
        $attribute->save();

        $this->dataHelper->logReport($this->taskId, sprintf(
            'Attribute: "%s" update title: "%s" Store Id: "%s"',
            $this->code,
            $this->title,
            $this->storeId
        ));

        return $attribute;
    }

    /**
     * @param string $name
     *
     * @return mixed
     * @throws Exception
     */
    private function loadAttributeGroupIdByName($name) {

        if (!empty($this->groupIds[$name])) {
            return $this->groupIds[$name];
        }

        $groups = Mage::getModel('eav/entity_attribute_group')->getResourceCollection()
            ->addFilter('attribute_group_name', $name)
            ->addFilter('attribute_set_id', $this->attributeSetId)
            ->getItems();

        $group = array_shift($groups);
        if ($group) {
            return $this->groupIds[$name] = $group->getId();
        }

        $group = Mage::getModel('eav/entity_attribute_group');

        $group->setAttributeGroupName($name);
        $group->setAttributeSetId($this->attributeSetId);
        $group->save();

        $this->dataHelper->logReport($this->taskId, sprintf(
            'Create new group: "%s" Attribute Set Id: "%s"',
            $name,
            $this->attributeSetId
        ));

        return $this->groupIds[$name] = $group->getId();
    }

    /**
     * @return Mage_Eav_Model_Entity_Attribute_Abstract
     * @throws Exception
     */
    private function createAttribute() {

        $attribute = Mage::getModel('catalog/resource_eav_attribute');
        $data = array(
            'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
            'attribute_code' => $this->code,
            'frontend_input' => 'select',
            'default_value_yesno' => '0',
            'is_unique' => '0',
            'is_required' => '0',
            'apply_to' => array('simple', 'configurable'),
            'is_configurable' => '1',
            'is_searchable' => '0',
            'is_visible_in_advanced_search' => '1',
            'is_comparable' => '1',
            'is_used_for_price_rules' => '0',
            'is_wysiwyg_enabled' => '0',
            'is_html_allowed_on_front' => '1',
            'is_visible_on_front' => '0',
            'used_in_product_listing' => '0',
            'used_for_sort_by' => '0',
            'type' => 'varchar',
            'backend_type' => 'varchar',
            'backend' => 'eav/entity_attribute_backend_array',
            'frontend_label' => array(
                Mage_Core_Model_App::ADMIN_STORE_ID => $this->title,
                $this->storeId => $this->title
            )
        );

        $this->text && $data['frontend_input'] = 'text';
        $this->text && $data['is_configurable'] = '0';
        // $this->text && $data['apply_to'] = array('simple');

        $attribute->addData($data);
        $attribute->setAttributeSetId($this->attributeSetId);
        $attribute->setAttributeGroupId($this->loadAttributeGroupIdByName($this->groupName));
        $attribute->setEntityTypeId(Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId());
        $attribute->setIsUserDefined(1);
        $attribute->save();

        $this->dataHelper->logReport($this->taskId, sprintf(
            'Create new attribute: "%s" code: "%s" Attribute Set Id: "%s" Group: "%s"',
            $this->title,
            $this->code,
            $this->attributeSetId,
            $this->groupName
        ));

        return Mage::getModel('eav/config')->getAttribute('catalog_product', $this->code);
    }

    /**
     * @param string $code
     *
     * @return Mage_Eav_Model_Entity_Attribute_Abstract
     * @throws Exception
     */
    private function loadAttributeByCode($code) {

        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $code);
        if (!$attribute || !$attribute->getId()) {
            $attribute = $this->createAttribute();
        }
        $attribute->setData('store_id', $this->storeId);

        return $attribute;
    }

    /**
     * @return Mage_Eav_Model_Entity_Attribute_Abstract|mixed
     * @throws Exception
     */
    public function save() {

        $attribute = $this->loadAttributeByCode($this->code);
        $this->rename && $attribute = $this->renameAttribute($attribute);
        $attribute = $this->assignGroup($attribute);
        $attribute = $this->addValue($attribute);

        return $attribute;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setValue($value) {
        $this->value = $value;

        return $this;
    }

    /**
     * @param string $code
     *
     * @return $this
     */
    public function setCode($code) {
        $code = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $code);
        $code = preg_replace('/[^0-9a-z]/i', '_', $code);
        $code = preg_replace('/_+/', '_', $code);
        $abc = 'abcdefghijklmnopqrstuvwxyz';
        if (preg_match('/^\d/', $code, $matches)) {
            $index = $matches[0];
            $code = $abc[$index] . '_' . $code;
        }
        $this->code = strtolower($code);

        return $this;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title) {

        $this->title = $title;

        $this->setCode($title);

        return $this;
    }

    /**
     * @param bool $text
     *
     * @return $this
     */
    public function setText($text) {
        $this->text = $text;

        return $this;
    }

    /**
     * @param bool $rename
     *
     * @return $this
     */
    public function setRename($rename) {
        $this->rename = $rename;

        return $this;
    }

    /**
     * @param string $groupName
     *
     * @return $this
     */
    public function setGroupName($groupName) {
        $this->groupName = $groupName;

        return $this;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return $this
     */
    public function setProduct(Mage_Catalog_Model_Product $product) {
        $this->product = $product;

        return $this;
    }

    /**
     * @param int $attributeSetId
     *
     * @return $this
     */
    public function setAttributeSetId($attributeSetId) {
        $this->attributeSetId = $attributeSetId;

        return $this;
    }

    /**
     * @param int $storeId
     *
     * @return $this
     */
    public function setStoreId($storeId) {
        $this->storeId = $storeId;

        return $this;
    }

    /**
     * @param int $taskId
     *
     * @return $this
     */
    public function setTaskId($taskId) {
        $this->taskId = $taskId;

        return $this;
    }

    //########################################

    public function __construct() {
        $this->updateResource = Mage::getResourceSingleton('catalog/product_action');
        $this->productAction = Mage::getSingleton('catalog/product_action');
        $this->dataHelper = Mage::helper('e2m');
    }
}
