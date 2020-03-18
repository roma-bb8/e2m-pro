<?php

class M2E_E2M_Helper_Data extends Mage_Core_Helper_Abstract {

    const PREFIX = 'm2e/e2m/';

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

    const MARKETPLACE_US_CODE = 'US';
    const MARKETPLACE_CA_CODE = 'CA';
    const MARKETPLACE_UK_CODE = 'UK';
    const MARKETPLACE_AU_CODE = 'AU';
    const MARKETPLACE_AT_CODE = 'AT';
    const MARKETPLACE_BE_FR_CODE = 'BE_FR';
    const MARKETPLACE_FR_CODE = 'FR';
    const MARKETPLACE_DE_CODE = 'DE';
    const MARKETPLACE_MOTORS_CODE = 'MOTOR';
    const MARKETPLACE_IT_CODE = 'IT';
    const MARKETPLACE_BE_DU_CODE = 'BE_DU';
    const MARKETPLACE_NL_CODE = 'NL';
    const MARKETPLACE_SP_CODE = 'SP';
    const MARKETPLACE_CH_CODE = 'CH';
    const MARKETPLACE_HK_CODE = 'HK';
    const MARKETPLACE_IN_CODE = 'IN';
    const MARKETPLACE_IE_CODE = 'IE';
    const MARKETPLACE_MY_CODE = 'MY';
    const MARKETPLACE_CA_FR_CODE = 'CA_FR';
    const MARKETPLACE_PH_CODE = 'PH';
    const MARKETPLACE_PL_CODE = 'PL';
    const MARKETPLACE_SG_CODE = 'SG';

    const MARKETPLACE_AU = 'Australia';
    const MARKETPLACE_AT = 'Austria';
    const MARKETPLACE_BE_DU = 'Belgium_Dutch';
    const MARKETPLACE_BE_FR = 'Belgium_French';
    const MARKETPLACE_CA = 'Canada';
    const MARKETPLACE_CA_FR = 'CanadaFrench';
    const MARKETPLACE_MOTORS = 'eBayMotors';
    const MARKETPLACE_FR = 'France';
    const MARKETPLACE_DE = 'Germany';
    const MARKETPLACE_HK = 'HongKong';
    const MARKETPLACE_IN = 'India';
    const MARKETPLACE_IE = 'Ireland';
    const MARKETPLACE_IT = 'Italy';
    const MARKETPLACE_MY = 'Malaysia';
    const MARKETPLACE_NL = 'Netherlands';
    const MARKETPLACE_PH = 'Philippines';
    const MARKETPLACE_PL = 'Poland';
    const MARKETPLACE_SG = 'Singapore';
    const MARKETPLACE_SP = 'Spain';
    const MARKETPLACE_CH = 'Switzerland';
    const MARKETPLACE_UK = 'UK';
    const MARKETPLACE_US = 'US';

    public static $MARKETPLACE_CODE = array(
        self::MARKETPLACE_US => self::MARKETPLACE_US_CODE,
        self::MARKETPLACE_CA => self::MARKETPLACE_CA_CODE,
        self::MARKETPLACE_UK => self::MARKETPLACE_UK_CODE,
        self::MARKETPLACE_AU => self::MARKETPLACE_AU_CODE,
        self::MARKETPLACE_AT => self::MARKETPLACE_AT_CODE,
        self::MARKETPLACE_BE_FR => self::MARKETPLACE_BE_FR_CODE,
        self::MARKETPLACE_FR => self::MARKETPLACE_FR_CODE,
        self::MARKETPLACE_DE => self::MARKETPLACE_DE_CODE,
        self::MARKETPLACE_MOTORS => self::MARKETPLACE_MOTORS_CODE,
        self::MARKETPLACE_IT => self::MARKETPLACE_IT_CODE,
        self::MARKETPLACE_BE_DU => self::MARKETPLACE_BE_DU_CODE,
        self::MARKETPLACE_NL => self::MARKETPLACE_NL_CODE,
        self::MARKETPLACE_SP => self::MARKETPLACE_SP_CODE,
        self::MARKETPLACE_CH => self::MARKETPLACE_CH_CODE,
        self::MARKETPLACE_HK => self::MARKETPLACE_HK_CODE,
        self::MARKETPLACE_IN => self::MARKETPLACE_IN_CODE,
        self::MARKETPLACE_IE => self::MARKETPLACE_IE_CODE,
        self::MARKETPLACE_MY => self::MARKETPLACE_MY_CODE,
        self::MARKETPLACE_CA_FR => self::MARKETPLACE_CA_FR_CODE,
        self::MARKETPLACE_PH => self::MARKETPLACE_PH_CODE,
        self::MARKETPLACE_PL => self::MARKETPLACE_PL_CODE,
        self::MARKETPLACE_SG => self::MARKETPLACE_SG_CODE
    );

    private $marketplaceTitle = array(
        self::MARKETPLACE_US_CODE => self::MARKETPLACE_US_TITLE,
        self::MARKETPLACE_CA_CODE => self::MARKETPLACE_CA_TITLE,
        self::MARKETPLACE_UK_CODE => self::MARKETPLACE_UK_TITLE,
        self::MARKETPLACE_AU_CODE => self::MARKETPLACE_AU_TITLE,
        self::MARKETPLACE_AT_CODE => self::MARKETPLACE_AT_TITLE,
        self::MARKETPLACE_BE_FR_CODE => self::MARKETPLACE_BE_FR_TITLE,
        self::MARKETPLACE_FR_CODE => self::MARKETPLACE_FR_TITLE,
        self::MARKETPLACE_DE_CODE => self::MARKETPLACE_DE_TITLE,
        self::MARKETPLACE_MOTORS_CODE => self::MARKETPLACE_MOTORS_TITLE,
        self::MARKETPLACE_IT_CODE => self::MARKETPLACE_IT_TITLE,
        self::MARKETPLACE_BE_DU_CODE => self::MARKETPLACE_BE_DU_TITLE,
        self::MARKETPLACE_NL_CODE => self::MARKETPLACE_NL_TITLE,
        self::MARKETPLACE_SP_CODE => self::MARKETPLACE_SP_TITLE,
        self::MARKETPLACE_CH_CODE => self::MARKETPLACE_CH_TITLE,
        self::MARKETPLACE_HK_CODE => self::MARKETPLACE_HK_TITLE,
        self::MARKETPLACE_IN_CODE => self::MARKETPLACE_IN_TITLE,
        self::MARKETPLACE_IE_CODE => self::MARKETPLACE_IE_TITLE,
        self::MARKETPLACE_MY_CODE => self::MARKETPLACE_MY_TITLE,
        self::MARKETPLACE_CA_FR_CODE => self::MARKETPLACE_CA_FR_TITLE,
        self::MARKETPLACE_PH_CODE => self::MARKETPLACE_PH_TITLE,
        self::MARKETPLACE_PL_CODE => self::MARKETPLACE_PL_TITLE,
        self::MARKETPLACE_SG_CODE => self::MARKETPLACE_SG_TITLE
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
            'ebay_item_id' => 'eBya Item ID',
            'site' => 'Site',
            'sku' => 'SKU',
            'upc' => 'UPC',
            'ean' => 'EAN',
            'isbn' => 'ISBN',
            'ePID' => 'ePID',
            'mpn' => 'MPN',
            'brand' => 'Brand',
            'title' => 'Title',
            'subtitle' => 'Subtitle',
            'description' => 'Description',
            'currency' => 'Currency',
            'start_price' => 'Start price',
            'current_price' => 'Current price',
            'buy_it_now' => 'Buy It Now',
            'quantity' => 'Quantity',
            'condition_id' => 'Condition id',
            'condition_name' => 'Condition name',
            'condition_description' => 'Condition description',
            'primary_category_id' => 'Primary category id',
            'primary_category_name' => 'Primary category name',
            'secondary_category_id' => 'Secondary category id',
            'secondary_category_name' => 'Secondary category name',
            'store_category_id' => 'Store category id',
            'store_category_name' => 'Store category name',
            'store_category2_id' => 'Store category2 id',
            'store_category2_name' => 'Store category2 name',
            'weight' => 'Weight',
            'dispatch_time_max' => 'Dispatch time max',
            'dimensions_depth' => 'Dimensions depth',
            'dimensions_length' => 'Dimensions length',
            'dimensions_weight' => 'Dimensions weight',
            'v_hash' => 'Variation eBay Item ID'
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


    public function getCode($code) {
        $code = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $code);
        $code = preg_replace('/[^0-9a-z]/i', '_', $code);
        $code = preg_replace('/_+/', '_', $code);
        $abc = 'abcdefghijklmnopqrstuvwxyz';
        if (preg_match('/^\d/', $code, $matches)) {
            $index = $matches[0];
            $code = $abc[$index] . '_' . $code;
        }
        return strtolower($code);
    }

    public function getFile($name) {
        return str_replace(array(',', '&', '.', "'", '_', ' '), '', strtolower(
            array_shift(explode(':', $name))
        ));
    }

    public function getValue($value) {
        return empty($value) ? '__EMPTY__VALUE__' : '"' . str_replace("\n", '', trim($value)) . '"';
    }
}
