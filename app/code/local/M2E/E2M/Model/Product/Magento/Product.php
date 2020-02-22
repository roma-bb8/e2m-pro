<?php

/**
 * Class M2E_E2M_Model_Product_Magento_Product
 */
abstract class M2E_E2M_Model_Product_Magento_Product extends Mage_Core_Model_Abstract {

    /** @var M2E_E2M_Helper_eBay_Config $eBayConfig */
    protected $eBayConfig;

    /** @var Mage_Catalog_Model_Product $product */
    protected $product;

    /** @var Mage_Core_Helper_Data $coreHelper */
    private $coreHelper;

    /** @var int $groupId */
    private $groupId;

    /** @var int $taskId */
    protected $taskId;

    /** @var array $attributeSetTmp */
    private $attributeSetTmp = array();

    //########################################

    /**
     * @param string $value
     * @param string $attributeCode
     * @param int $storeId
     *
     * @return Mage_Catalog_Model_Product
     */
    private function loadProductBy($value, $attributeCode, $storeId) {

        $products = Mage::getResourceModel('catalog/product_collection');
        $products->addAttributeToSelect('*');
        $products->addStoreFilter($storeId);
        $products->addAttributeToFilter($attributeCode, $value);
        $products->setCurPage(1)->setPageSize(1);
        $products->load();

        /** @var Mage_Catalog_Model_Product $product */
        $product = $products->getFirstItem();
        if (!$product->getId()) {
            return null;
        }

        return $product;
    }

    /**
     * @return int
     * @throws Exception
     */
    private function loadEbayGroup() {

        if (!empty($this->groupId)) {
            return $this->groupId;
        }

        $groups = Mage::getModel('eav/entity_attribute_group')->getResourceCollection()
            ->addFilter('attribute_group_name', 'eBay')
            ->addFilter('attribute_set_id', $this->eBayConfig->getAttributeSet())
            ->getItems();

        $group = array_shift($groups);
        if ($group) {
            return $this->groupId = $group->getId();
        }

        $group = Mage::getModel('eav/entity_attribute_group');
        $group->setAttributeGroupName('eBay')
            ->setAttributeSetId($this->eBayConfig->getAttributeSet());
        $group->save();

        $this->addLog('Create eBay Group in Attribute Set ID:' . $this->eBayConfig->getAttributeSet());

        return $this->groupId = $group->getId();
    }

    /***
     * @param Mage_Eav_Model_Entity_Attribute_Abstract $attribute
     *
     * @return bool
     * @throws Exception
     */
    protected function checkAssignedAttributes($attribute) {

        $attributeCode = $attribute->getData('attribute_code');
        if (isset($this->attributeSetTmp[$attributeCode])) {
            return true;
        }

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        $attributes = $dataHelper->getMagentoAttributes($this->eBayConfig->getAttributeSet());
        foreach ($attributes as $code => $item) {
            if ($code === $attributeCode) {
                $this->attributeSetTmp[$attributeCode] = true;
                return true;
            }
        }

        $attributes = $dataHelper->getMagentoAttributes($this->eBayConfig->getAttributeSet(), true);
        foreach ($attributes as $code => $item) {
            if ($code === $attributeCode) {
                $this->attributeSetTmp[$attributeCode] = true;
                return true;
            }
        }

        $attribute->setData('attribute_set_id', $this->eBayConfig->getAttributeSet());
        $attribute->setData('attribute_group_id', $this->loadEbayGroup());
        $attribute->save();

        $this->addLog('add attribute: "' . $attribute->getName() . '" to eBay Group');

        return false;
    }

    //########################################

    /**
     * @param string $description
     * @param int $type
     */
    protected function addLog($description, $type = M2E_E2M_Helper_Data::TYPE_REPORT_SUCCESS) {

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        $dataHelper->logReport($this->taskId, $description, $type);
    }

    //########################################

    /**
     * @param Mage_Eav_Model_Entity_Attribute_Abstract $attribute
     * @param string|int $option
     * @param int $storeId
     *
     * @return int|null
     * @throws Exception
     */
    protected function addAttributeValue($attribute, $option, $storeId) {

        $optionId = Mage::getModel('eav/entity_attribute_source_table')
            ->setAttribute($attribute)
            ->getOptionId($option);
        if ($optionId) {
            return $optionId;
        }

        try {

            $attribute->setData('option', array('value' => array('option' => array(
                Mage_Core_Model_App::ADMIN_STORE_ID => $option,
                $storeId => $option
            ))));
            $attribute->save();

            $this->addLog('Add new value: "' . $option . '" in Attribute: "' . $attribute->getName() . '"');

        } catch (Exception $e) {
            Mage::helper('e2m')->logException($e);

            $this->addLog('Not add value: "' . $option . '" in Attribute: "' . $attribute->getName() . '"');

            return null;
        }

        return Mage::getModel('eav/entity_attribute_source_table')
            ->setAttribute($attribute)
            ->getOptionId($option);
    }

    /**
     * @param Mage_Eav_Model_Entity_Attribute_Abstract $attribute
     * @param string $title
     * @param int $storeId
     *
     * @return Mage_Eav_Model_Entity_Attribute_Abstract
     */
    protected function updateTitleAttribute($attribute, $title, $storeId) {

        try {

            $frontendLabels = $attribute->getData('frontend_label');
            if (is_array($frontendLabels)) {
                if ($frontendLabels[$storeId] == $title) {
                    return $attribute;
                }

                $frontendLabels[$storeId] = $title;
            } else {
                if ($frontendLabels == $title) {
                    return $attribute;
                }

                $frontendLabels = array(
                    Mage_Core_Model_App::ADMIN_STORE_ID => $attribute->getData('frontend_label')
                );

                $frontendLabels[$storeId] = $title;
            }

            $attribute->setData('frontend_label', $frontendLabels);
            $attribute->save();

            $this->addLog('Update title name in Attribute: "' . $attribute->getName() . '" in Store: "' . $attribute->getStoreId() . '"');

        } catch (Exception $e) {
            Mage::helper('e2m')->logException($e);

            $this->addLog('Not update title name in Attribute: "' . $attribute->getName() . '" in Store: "' . $attribute->getStoreId() . '"',
                M2E_E2M_Helper_Data::TYPE_REPORT_ERROR);
        }

        return $attribute;
    }

    /**
     * @param string $code
     * @param string $title
     * @param int $storeId
     *
     * @return null|Mage_Eav_Model_Entity_Attribute_Abstract
     */
    protected function createAttribute($code, $title, $storeId) {

        try {

            $attribute = Mage::getModel('catalog/resource_eav_attribute');
            $attribute->addData(array(
                'attribute_code' => $code,
                'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                'frontend_input' => 'select',
                'default_value_text' => '',
                'default_value_yesno' => '0',
                'default_value_date' => '',
                'default_value_textarea' => '',
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
                'frontend_label' => array(Mage_Core_Model_App::ADMIN_STORE_ID => $title, $storeId => $title),
                'type' => 'varchar',
                'backend_type' => 'varchar',
                'backend' => 'eav/entity_attribute_backend_array'
            ));
            $attribute->setAttributeSetId($this->eBayConfig->getAttributeSet());
            $attribute->setAttributeGroupId($this->loadEbayGroup());
            $attribute->setEntityTypeId(Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId());
            $attribute->setIsUserDefined(1);
            $attribute->save();

            $this->addLog('Create new Attribute: "' . $title . '" in Attribute Set ID: "' . $this->eBayConfig->getAttributeSet() . '"');

        } catch (Exception $e) {
            Mage::helper('e2m')->logException($e);

            $this->addLog('Not create new Attribute: ' . $title, M2E_E2M_Helper_Data::TYPE_REPORT_ERROR);

            return null;
        }

        return Mage::getModel('eav/config')->getAttribute('catalog_product', $code);
    }

    //########################################

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $data
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function importImage($product, $data) {

        try {

            if (empty($data['images_urls'])) {
                return $product;
            }

            $tempMediaPath = Mage::getSingleton('catalog/product_media_config')->getBaseTmpMediaPath();
            $files = array();
            foreach ($data['images_urls'] as $url) {
                $ext = strtolower(substr($url, (strripos($url, '.'))));
                !in_array($ext, array('.png', '.jpg', '.jpeg')) && $ext = '.jpg';
                $fileName = md5($url) . $ext;
                file_put_contents($tempMediaPath . DS . $fileName, file_get_contents($url));

                $files[] = $fileName;
            }

            $gallery = array();

            $imagePosition = 1;
            foreach ($files as $file) {
                if (!is_file($tempMediaPath . DS . $file)) {
                    continue;
                }

                $gallery[] = array(
                    'file' => $file,
                    'label' => '',
                    'position' => $imagePosition++,
                    'disabled' => 0,
                    'removed' => 0
                );
            }

            if (empty($gallery)) {
                return $product;
            }

            $firstImage = reset($gallery);
            $firstImage = $firstImage['file'];

            $product->setData('image', $firstImage);
            $product->setData('thumbnail', $firstImage);
            $product->setData('small_image', $firstImage);
            $product->setData('media_gallery', array(
                'images' => $this->coreHelper->jsonEncode($gallery),
                'values' => $this->coreHelper->jsonEncode(array(
                    'main' => $firstImage,
                    'image' => $firstImage,
                    'small_image' => $firstImage,
                    'thumbnail' => $firstImage
                ))
            ));

        } catch (Exception $e) {
            Mage::helper('e2m')->logException($e);

            $this->addLog('Not Import Images for SKU:' . $product->getSku(), M2E_E2M_Helper_Data::TYPE_REPORT_WARNING);
        }

        return $product;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $data
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function updateImage($product, $data) {

        $galleryImages = $product->getData('media_gallery');
        if (!isset($galleryImages['images']) || !is_array($galleryImages['images'])) {
            return $this->importImage($product, $data);
        }

        $imagesURLs = array();
        $importURLs = array();
        foreach ($data['images_urls'] as $index => $url) {
            $ext = strtolower(substr($url, (strripos($url, '.'))));
            !in_array($ext, array('.png', '.jpg', '.jpeg')) && $ext = '.jpg';
            $imagesURLs[$index] = md5($url) . $ext;
        }

        foreach ($galleryImages['images'] as $galleryImage) {
            if (!isset($galleryImage['file'])) {
                continue;
            }

            if (!$i = array_search($galleryImage['file'], $imagesURLs)) {
                continue;
            }

            $importURLs[] = $data['images_urls'][$i];
        }

        if (!empty($importURLs)) {
            return $this->importImage($product, array('images_urls' => $importURLs));
        }

        return $product;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $data
     *
     * @return Mage_Catalog_Model_Product
     * @throws Exception
     */
    protected function importQty($product, $data) {

        try {

            $qty = (int)$data['qty_total'];

            /** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
            $stockItem = Mage::getModel('cataloginventory/stock_item');
            $stockItem->assignProduct($product);
            $stockItem->addData(array(
                'qty' => $qty,
                'stock_id' => Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID,
                'is_in_stock' => $qty >= 1,
                'is_qty_decimal' => 0
            ));

            $stockItem->save();

        } catch (Exception $e) {
            Mage::helper('e2m')->logException($e);

            $this->addLog('Not Import Qty for SKU:' . $product->getSku(), M2E_E2M_Helper_Data::TYPE_REPORT_WARNING);
        }

        return $product;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $data
     * @param int $storeId
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function loadProduct($product, $data, $storeId) {
        switch (true) {
            case $this->eBayConfig->isProductIdentifierSKU():
                if (!empty($data['identifiers_sku'])) {
                    $product->setData('store_id', $storeId);
                    $product->load($product->getIdBySku($data['identifiers_sku']));
                }

                break;
            case $this->eBayConfig->isProductIdentifierMPN():
                if (!empty($data['identifiers_brand_mpn_mpn'])) {
                    $tmp = $this->loadProductBy($data['identifiers_brand_mpn_mpn'], 'mpn', $storeId);
                    $tmp !== null && $product = $tmp;
                }

                break;

            case $this->eBayConfig->isProductIdentifierUPC():
                if (!empty($data['identifiers_upc'])) {
                    $tmp = $this->loadProductBy($data['identifiers_upc'], 'upc', $storeId);
                    $tmp !== null && $product = $tmp;
                }

                break;

            case $this->eBayConfig->isProductIdentifierEAN():
                if (!empty($data['identifiers_ean'])) {
                    $tmp = $this->loadProductBy($data['identifiers_ean'], 'ean', $storeId);
                    $tmp !== null && $product = $tmp;
                }

                break;

            case $this->eBayConfig->isProductIdentifierGTIN():
                $tmp = null;
                if (!empty($data['identifiers_upc'])) {
                    $tmp = $this->loadProductBy($data['upc'], 'gtin', $storeId);
                }

                if (!empty($data['identifiers_ean']) && $tmp === null) {
                    $tmp = $this->loadProductBy($data['ean'], 'gtin', $storeId);
                }

                $tmp !== null && $product = $tmp;
                break;
        }

        return $product;
    }

    //########################################

    /**
     * @param $taskId
     *
     * @return $this
     */
    public function setTaskId($taskId) {
        $this->taskId = $taskId;

        return $this;
    }

    //########################################

    /**
     * @inheritDoc
     */
    public function __construct() {
        parent::__construct();

        $this->eBayConfig = Mage::helper('e2m/eBay_Config');
        $this->coreHelper = Mage::helper('core');
        $this->product = Mage::getModel('catalog/product');
    }

    //########################################

    /**
     * @param array $data
     * @param bool $save
     *
     * @return Mage_Catalog_Model_Product
     * @throws Exception
     */
    abstract public function process($data, $save = true);
}