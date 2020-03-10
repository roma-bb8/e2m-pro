<?php

class M2E_E2M_Helper_Data extends Mage_Core_Helper_Abstract {

    const PREFIX = 'm2e/e2m/';

    const CACHE_ID_MAINTENANCE = self::PREFIX . 'maintenance';

    const CACHE_ID_EBAY_INVENTORY_VARIATION_COUNT = self::PREFIX . 'ebay/inventory/variation/count';
    const CACHE_ID_EBAY_INVENTORY_SIMPLE_COUNT = self::PREFIX . 'ebay/inventory/simple/count';
    const CACHE_ID_EBAY_INVENTORY_TOTAL_COUNT = self::PREFIX . 'ebay/inventory/total/count';

    const XML_PATH_EBAY_AVAILABLE_MARKETPLACES = self::PREFIX . 'ebay/available/marketplaces';

    const XML_PATH_EBAY_DOWNLOAD_INVENTORY = self::PREFIX . 'ebay/inventory/download';
    const XML_PATH_EBAY_IMPORT_INVENTORY = self::PREFIX . 'ebay/inventory/import';

    const TYPE_REPORT_SUCCESS = 1;
    const TYPE_REPORT_WARNING = 2;
    const TYPE_REPORT_ERROR = 3;

    const MARKETPLACE_AU_TITLE = 'Australia';
    const MARKETPLACE_AT_TITLE = 'Austria';
    const MARKETPLACE_BE_DU_TITLE = 'Belgium Dutch';
    const MARKETPLACE_BE_FR_TITLE = 'Belgium French';
    const MARKETPLACE_CA_TITLE = 'Canada';
    const MARKETPLACE_CA_FR_TITLE = 'Canada French';
    const MARKETPLACE_MOTORS_TITLE = 'eBay Motors';
    const MARKETPLACE_FR_TITLE = 'France';
    const MARKETPLACE_DE_TITLE = 'Germany';
    const MARKETPLACE_HK_TITLE = 'Hong Kong';
    const MARKETPLACE_IN_TITLE = 'India';
    const MARKETPLACE_IE_TITLE = 'Ireland';
    const MARKETPLACE_IT_TITLE = 'Italy';
    const MARKETPLACE_MY_TITLE = 'Malaysia';
    const MARKETPLACE_NL_TITLE = 'Netherlands';
    const MARKETPLACE_PH_TITLE = 'Philippines';
    const MARKETPLACE_PL_TITLE = 'Poland';
    const MARKETPLACE_SG_TITLE = 'Singapore';
    const MARKETPLACE_SP_TITLE = 'Spain';
    const MARKETPLACE_CH_TITLE = 'Switzerland';
    const MARKETPLACE_UK_TITLE = 'United Kingdom';
    const MARKETPLACE_US_TITLE = 'United States';

    private $marketplaceTitle = array(
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_US_ID => self::MARKETPLACE_US_TITLE,
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_CA_ID => self::MARKETPLACE_CA_TITLE,
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_UK_ID => self::MARKETPLACE_UK_TITLE,
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_AU_ID => self::MARKETPLACE_AU_TITLE,
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_AT_ID => self::MARKETPLACE_AT_TITLE,
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_BE_FR_ID => self::MARKETPLACE_BE_FR_TITLE,
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_FR_ID => self::MARKETPLACE_FR_TITLE,
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_DE_ID => self::MARKETPLACE_DE_TITLE,
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_MOTORS_ID => self::MARKETPLACE_MOTORS_TITLE,
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_IT_ID => self::MARKETPLACE_IT_TITLE,
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_BE_DU_ID => self::MARKETPLACE_BE_DU_TITLE,
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_NL_ID => self::MARKETPLACE_NL_TITLE,
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_SP_ID => self::MARKETPLACE_SP_TITLE,
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_CH_ID => self::MARKETPLACE_CH_TITLE,
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_HK_ID => self::MARKETPLACE_HK_TITLE,
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_IN_ID => self::MARKETPLACE_IN_TITLE,
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_IE_ID => self::MARKETPLACE_IE_TITLE,
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_MY_ID => self::MARKETPLACE_MY_TITLE,
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_CA_FR_ID => self::MARKETPLACE_CA_FR_TITLE,
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_PH_ID => self::MARKETPLACE_PH_TITLE,
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_PL_ID => self::MARKETPLACE_PL_TITLE,
        M2E_E2M_Model_Adapter_Ebay_Item::MARKETPLACE_SG_ID => self::MARKETPLACE_SG_TITLE
    );

    private $magentoAttributeSets = array();
    private $magentoAttributes = array();
    private $magentoStores = array();

    //########################################

    /**
     * @param string $id
     * @param mixed $default
     *
     * @return mixed|null
     */
    public function getCacheValue($id, $default = null) {

        $value = Mage::app()->loadCache($id);
        if (!$value) {
            return $default;
        }

        return $value;
    }

    /**
     * @param string $id
     * @param mixed $value
     *
     * @return mixed|null
     */
    public function setCacheValue($id, $value) {

        Mage::app()->saveCache($value, $id);

        $value = Mage::app()->loadCache($id);
        if (!$value) {
            return null;
        }

        return $value;
    }

    /**
     * @param string $path
     * @param mixed|null $default
     *
     * @return mixed|null
     */
    public function getConfig($path, $default = null) {

        try {

            $value = Mage::app()->getStore()->getConfig($path);
            if (!$value) {
                return $default;
            }

        } catch (Mage_Core_Model_Store_Exception $e) {
            return $default;
        }

        /** @var Mage_Core_Helper_Data $coreHelper */
        $coreHelper = Mage::helper('core');

        return $coreHelper->jsonDecode($value);
    }

    /**
     * @param string $path
     * @param mixed $value
     * @param bool $cleanCache
     *
     * @return $this
     */
    public function setConfig($path, $value, $cleanCache = false) {

        $coreHelper = Mage::helper('core');

        $coreConfig = Mage::getModel('core/config');

        $coreConfig->saveConfig($path, $coreHelper->jsonEncode($value));

        $cleanCache && $coreConfig->cleanCache();

        return $this;
    }

    //########################################

    /**
     * @return array
     */
    public function getMagentoStores() {
        if (!empty($this->magentoStores)) {
            return $this->magentoStores;
        }

        foreach (Mage::app()->getStores(true) as $store) {
            /** @var Mage_Core_Model_Store $store */
            $this->magentoStores[$store->getId()] = $store->getName();
        }

        return $this->magentoStores;
    }

    //########################################

    /**
     * @return array
     */
    public function getAllAttributeSet() {
        if (!empty($this->magentoAttributeSets)) {
            return $this->magentoAttributeSets;
        }

        $entityType = Mage::getModel('catalog/product')->getResource()->getTypeId();
        $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection');
        $attributeSetCollection->setEntityTypeFilter($entityType);
        foreach ($attributeSetCollection as $attributeSet) {
            $name = $attributeSet->getAttributeSetName();
            $attributeSetId = (int)$attributeSet->getId();

            $this->magentoAttributeSets[$attributeSetId] = $name;
        }

        return $this->magentoAttributeSets;
    }

    //########################################

    /**
     * @param int $setId
     * @param bool $reload
     *
     * @return array
     */
    public function getMagentoAttributes($setId, $reload = false) {
        if (!empty($this->magentoAttributes[$setId]) && !$reload) {
            return $this->magentoAttributes[$setId];
        }

        /** @var Mage_Eav_Model_Entity_Attribute_Group[] $groups */
        $groups = Mage::getModel('eav/entity_attribute_group')
            ->getResourceCollection()
            ->setAttributeSetFilter($setId)
            ->setSortOrder()
            ->load();

        $sets = array();
        foreach ($groups as $group) {

            $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->setAttributeGroupFilter($group->getId())
                ->addVisibleFilter()
                ->checkConfigurableProducts()
                ->load();

            if ($attributes->getSize() <= 0) {
                continue;
            }

            foreach ($attributes->getItems() as $attribute) {
                $sets[$attribute['attribute_code']] = $attribute['frontend_label'];
            }
        }

        return $this->magentoAttributes[$setId] = $sets;
    }

    //########################################

    /**
     * @return array
     */
    public function getEbayFields() {
        return array(
            'identifiers_item_id' => 'Item ID',
            'identifiers_sku' => 'SKU',
            'identifiers_ean' => 'EAN',
            'identifiers_upc' => 'UPC',
            'identifiers_isbn' => 'ISBN',
            'identifiers_epid' => 'EPID',
            'identifiers_brand_mpn_mpn' => '(Brand) MPN',
            'identifiers_brand_mpn_brand' => '(Brand) Brand',

            'marketplace_id' => '(Site) Marketplace ID',
            'categories_primary_id' => '(Category) Primary ID',
            'categories_secondary_id' => '(Category) Secondary ID',
            'store_categories_primary_id' => '(Store) Category ID',
            'store_categories_secondary_id' => '(Store) Category 2 ID',

            'description_title' => 'Title',
            'description_subtitle' => 'SubTitle',
            'description_description' => 'Description',

            'price_start' => 'Start Price',
            'price_current' => 'Current Price',
            'price_buy_it_now' => 'Buy It Now Price',
            'price_map_value' => '(DPI) Minimum Advertised Price',
            'price_map_exposure' => '(DPI) Minimum Advertised Price Exposure',
            'price_stp_value' => '(Discount Price Info) Original Retail Price',

            'qty_total' => 'Quantity',

            'shipping_package_width' => 'Width',
            'shipping_dispatch_time' => 'Dispatch Time',
            'shipping_package_dimensions_depth' => '(Dimensions) Depth',
            'shipping_package_dimensions_length' => '(Dimensions) Length',

            'condition_type' => 'Condition ID'
        );
    }

    //########################################

    /**
     * @param int $marketplaceId
     *
     * @return string
     */
    public function getMarketplaceTitle($marketplaceId) {
        return $this->marketplaceTitle[$marketplaceId];
    }

    //########################################

    /**
     * @param int $taskId
     * @param string $description
     * @param int $type
     */
    public function logReport($taskId, $description, $type = self::TYPE_REPORT_SUCCESS) {
        $resource = Mage::getSingleton('core/resource');
        $coreConfigDataTableName = $resource->getTableName('m2e_e2m_log');
        $connWrite = $resource->getConnection('core_write');
        $connWrite->insert($coreConfigDataTableName, array(
            'task_id' => $taskId,
            'type' => $type,
            'description' => $description
        ));
    }

    //########################################

    /**
     * @param Exception $e
     */
    public function logException(Exception $e) {

        $type = get_class($e);
        $exceptionInfo = <<<EXCEPTION

-------------------------------- EXCEPTION INFO ----------------------------------
Type: {$type}
File: {$e->getFile()}
Line: {$e->getLine()}
Code: {$e->getCode()}
Message: {$e->getMessage()}
-------------------------------- STACK TRACE INFO --------------------------------
{$e->getTraceAsString()}

###################################################################################
EXCEPTION;

        Mage::log($exceptionInfo, Zend_Log::ERR, 'e2m.log', true);
    }
}
