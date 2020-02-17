<?php

class M2E_e2M_Helper_eBay_Config {

    const PREFIX = M2E_e2M_Helper_Data::PREFIX . 'settings/';

    const PATH_MARKETPLACE_TO_STORE_MAP = 'marketplaces/stores/map';

    const PATH_INVENTORY_PRODUCT_IDENTIFIER = 'inventory/product_identifier';
    const VALUE_SKU_PRODUCT_IDENTIFIER = 'SKU';
    const VALUE_MPN_PRODUCT_IDENTIFIER = 'MPN';
    const VALUE_EAN_PRODUCT_IDENTIFIER = 'EAN';
    const VALUE_UPC_PRODUCT_IDENTIFIER = 'UPC';
    const VALUE_GTIN_PRODUCT_IDENTIFIER = 'GTIN';

    const PATH_INVENTORY_ACTION_FOUND = 'inventory/action_found';
    const VALUE_IGNORE_ACTION_FOUND = 'IGNORE';
    const VALUE_UPDATE_ACTION_FOUND = 'UPDATE';

    const PATH_PRODUCT_FIELDS_ATTRIBUTES_MAP = 'product/fields_attributes/map';
    const PATH_PRODUCT_ATTRIBUTE_SET = 'product/attribute_set';
    const PATH_PRODUCT_IMPORT_IMAGE = 'product/import/image';
    const PATH_PRODUCT_IMPORT_QTY = 'product/import/qty';
    const PATH_PRODUCT_GENERATE_SKU = 'product/import/generate_sku';
    const PATH_PRODUCT_DELETE_HTML = 'product/import/generate_sku';

    const PATH_FULL_SETTINGS = 'full';

    //########################################

    /** @var Mage_Core_Model_Resource $resource */
    private $resource;

    /** @var string $coreConfigDataTableName */
    private $coreConfigDataTableName;

    /** @var Mage_Core_Helper_Data */
    private $coreHelper;

    private $settings = array();

    //########################################

    public function setFull() {

        /** @var M2E_e2M_Helper_eBay_Inventory $eBayInventoryHelper */
        $eBayInventoryHelper = Mage::helper('e2m/eBay_Inventory');

        $this->settings['full'] =
            isset($this->settings['product_identifier']) &&
            isset($this->settings['action_found']) &&
            isset($this->settings['attribute_set']) &&
            isset($this->settings['import_image']) &&
            isset($this->settings['import_qty']) &&
            isset($this->settings['generate_sku']) &&
            isset($this->settings['delete_html']) &&
            count($this->settings['marketplaces_stores']) === count($eBayInventoryHelper->getMarketplaces()) &&
            !empty($this->settings['fields_attributes']);
    }

    //########################################

    /**
     * @param array $setting
     *
     * @return $this
     * @throws Exception
     */
    public function setMarketplaceStore($setting) {
        $this->settings['marketplaces_stores'] = $setting;

        return $this;
    }

    /**
     * @param array $setting
     *
     * @return $this
     * @throws Exception
     */
    public function setEbayFieldMagentoAttribute($setting) {
        $this->settings['fields_attributes'] = $setting;

        return $this;
    }

    /**
     * @param string $setting
     *
     * @return $this
     * @throws Exception
     */
    public function setProductIdentifier($setting) {

        if ($setting === self::VALUE_SKU_PRODUCT_IDENTIFIER ||
            $setting === self::VALUE_MPN_PRODUCT_IDENTIFIER ||
            $setting === self::VALUE_GTIN_PRODUCT_IDENTIFIER) {
            $this->settings['product_identifier'] = $setting;
            return $this;
        }

        throw new Exception('Product Identifier incorrect.' . $setting);
    }

    /**
     * @param string $setting
     *
     * @return $this
     * @throws Exception
     */
    public function setActionFound($setting) {

        if ($setting === self::VALUE_IGNORE_ACTION_FOUND || $setting === self::VALUE_UPDATE_ACTION_FOUND) {
            $this->settings['action_found'] = $setting;

            return $this;

        }

        throw new Exception('Action Found incorrect.');
    }

    /**
     * @param string $setting
     *
     * @return $this
     */
    public function setImportImage($setting) {
        $this->settings['import_image'] = (bool)$setting;

        return $this;
    }

    /**
     * @param string $setting
     *
     * @return $this
     */
    public function setDeleteHtml($setting) {
        $this->settings['delete_html'] = (bool)$setting;

        return $this;
    }

    /**
     * @param string $setting
     *
     * @return $this
     */
    public function setImportQty($setting) {
        $this->settings['import_qty'] = (bool)$setting;

        return $this;
    }

    /**
     * @param string $setting
     *
     * @return $this
     */
    public function setGenerateSku($setting) {
        $this->settings['generate_sku'] = (bool)$setting;

        return $this;
    }

    /**
     * @param string $setting
     *
     * @return $this
     * @throws Exception
     */
    public function setAttributeSet($setting) {
        $this->settings['attribute_set'] = $setting;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getAttributeSet() {
        return $this->settings['attribute_set'];
    }

    /**
     * @param array $settings
     *
     * @return $this
     * @throws Exception
     */
    public function setSettings($settings) {

        isset($settings['marketplace-store']) && $this->setMarketplaceStore($settings['marketplace-store']);
        isset($settings['product-identifier']) && $this->setProductIdentifier($settings['product-identifier']);
        isset($settings['action-found']) && $this->setActionFound($settings['action-found']);
        isset($settings['import-qty']) && $this->setImportQty($settings['import-qty']);
        isset($settings['generate-sku']) && $this->setGenerateSku($settings['generate-sku']);
        isset($settings['import-image']) && $this->setImportImage($settings['import-image']);
        isset($settings['delete-html']) && $this->setDeleteHtml($settings['delete-html']);
        isset($settings['attribute-set']) && $this->setAttributeSet($settings['attribute-set']);
        isset($settings['ebay-field-magento-attribute']) && $this
            ->setEbayFieldMagentoAttribute($settings['ebay-field-magento-attribute']);

        $this->setFull();

        return $this;
    }

    //########################################

    /**
     * @param null $marketplaceId
     *
     * @return int
     */
    public function getStoreForMarketplace($marketplaceId = null) {
        if (isset($this->settings['marketplaces_stores'][$marketplaceId])) {
            return (int)$this->settings['marketplaces_stores'][$marketplaceId];
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getProductIdentifier() {
        return $this->settings['product_identifier'];
    }

    /**
     * @return bool
     */
    public function isProductIdentifierSKU() {
        return $this->getProductIdentifier() === self::VALUE_SKU_PRODUCT_IDENTIFIER;
    }

    /**
     * @return bool
     */
    public function isProductIdentifierMPN() {
        return $this->getProductIdentifier() === self::VALUE_MPN_PRODUCT_IDENTIFIER;
    }

    /**
     * @return bool
     */
    public function isProductIdentifierUPC() {
        return $this->getProductIdentifier() === self::VALUE_UPC_PRODUCT_IDENTIFIER;
    }

    /**
     * @return bool
     */
    public function isProductIdentifierEAN() {
        return $this->getProductIdentifier() === self::VALUE_EAN_PRODUCT_IDENTIFIER;
    }

    /**
     * @return bool
     */
    public function isProductIdentifierGTIN() {
        return $this->getProductIdentifier() === self::VALUE_GTIN_PRODUCT_IDENTIFIER;
    }

    /**
     * @return string|null
     */
    public function getActionFound() {
        return $this->settings['action_found'];
    }

    /**
     * @return bool
     */
    public function isGenerateSku() {
        return $this->settings['generate_sku'];
    }

    /**
     * @return bool
     */
    public function isDeleteHtml() {
        return $this->settings['delete_html'];
    }

    /**
     * @return bool
     */
    public function isActionFoundIgnore() {
        return $this->getActionFound() === self::VALUE_IGNORE_ACTION_FOUND;
    }

    /**
     * @return string[]
     */
    public function getEbayFieldMagentoAttribute() {
        return $this->settings['fields_attributes'];
    }

    /**
     * @return bool
     */
    public function isImportImage() {
        return $this->settings['import_image'];
    }

    /**
     * @return bool
     */
    public function isImportQty() {
        return $this->settings['import_qty'];
    }

    /**
     * @return bool
     */
    public function isFull() {
        return $this->settings['full'];
    }

    //########################################

    public function save() {

        $connWrite = $this->resource->getConnection('core_write');

        $connWrite->delete($this->coreConfigDataTableName, array('path IN (?)' => array(
            self::PREFIX . self::PATH_MARKETPLACE_TO_STORE_MAP,
            self::PREFIX . self::PATH_INVENTORY_PRODUCT_IDENTIFIER,
            self::PREFIX . self::PATH_INVENTORY_ACTION_FOUND,
            self::PREFIX . self::PATH_PRODUCT_FIELDS_ATTRIBUTES_MAP,
            self::PREFIX . self::PATH_FULL_SETTINGS,
            self::PREFIX . self::PATH_PRODUCT_ATTRIBUTE_SET,
            self::PREFIX . self::PATH_PRODUCT_IMPORT_IMAGE,
            self::PREFIX . self::PATH_PRODUCT_IMPORT_QTY,
            self::PREFIX . self::PATH_PRODUCT_GENERATE_SKU,
            self::PREFIX . self::PATH_PRODUCT_DELETE_HTML
        )));

        $connWrite->insertMultiple($this->coreConfigDataTableName, array(
            array(
                'path' => self::PREFIX . self::PATH_MARKETPLACE_TO_STORE_MAP,
                'value' => $this->coreHelper->jsonEncode($this->settings['marketplaces_stores'])
            ),
            array(
                'path' => self::PREFIX . self::PATH_INVENTORY_PRODUCT_IDENTIFIER,
                'value' => $this->settings['product_identifier']
            ),
            array(
                'path' => self::PREFIX . self::PATH_INVENTORY_ACTION_FOUND,
                'value' => $this->settings['action_found']
            ),
            array(
                'path' => self::PREFIX . self::PATH_PRODUCT_FIELDS_ATTRIBUTES_MAP,
                'value' => $this->coreHelper->jsonEncode($this->settings['fields_attributes'])
            ),
            array(
                'path' => self::PREFIX . self::PATH_FULL_SETTINGS,
                'value' => $this->coreHelper->jsonEncode($this->settings['full'])
            ),
            array(
                'path' => self::PREFIX . self::PATH_PRODUCT_ATTRIBUTE_SET,
                'value' => $this->settings['attribute_set']
            ),
            array(
                'path' => self::PREFIX . self::PATH_PRODUCT_IMPORT_IMAGE,
                'value' => $this->coreHelper->jsonEncode($this->settings['import_image'])
            ),
            array(
                'path' => self::PREFIX . self::PATH_PRODUCT_IMPORT_QTY,
                'value' => $this->coreHelper->jsonEncode($this->settings['import_qty'])
            ),
            array(
                'path' => self::PREFIX . self::PATH_PRODUCT_GENERATE_SKU,
                'value' => $this->coreHelper->jsonEncode($this->settings['generate_sku'])
            ),
            array(
                'path' => self::PREFIX . self::PATH_PRODUCT_DELETE_HTML,
                'value' => $this->coreHelper->jsonEncode($this->settings['delete_html'])
            )
        ));

        return $this;
    }

    public function __construct() {

        $this->resource = Mage::getSingleton('core/resource');
        $this->coreConfigDataTableName = $this->resource->getTableName('core_config_data');
        $this->coreHelper = Mage::helper('core');

        //----------------------------------------

        $this->settings = array(
            'marketplaces_stores' => array(),
            'product_identifier' => M2E_e2M_Helper_eBay_Config::VALUE_SKU_PRODUCT_IDENTIFIER,
            'action_found' => M2E_e2M_Helper_eBay_Config::VALUE_IGNORE_ACTION_FOUND,
            'fields_attributes' => array(),
            'full' => false,
            'attribute_set' => false,
            'import_image' => false,
            'import_qty' => false,
            'generate_sku' => false,
            'delete_html' => false
        );

        //----------------------------------------

        $settings = $this->resource->getConnection('core_read')->select()
            ->from($this->coreConfigDataTableName)
            ->where('path IN (?)', array(
                self::PREFIX . self::PATH_MARKETPLACE_TO_STORE_MAP,
                self::PREFIX . self::PATH_INVENTORY_PRODUCT_IDENTIFIER,
                self::PREFIX . self::PATH_INVENTORY_ACTION_FOUND,
                self::PREFIX . self::PATH_PRODUCT_FIELDS_ATTRIBUTES_MAP,
                self::PREFIX . self::PATH_FULL_SETTINGS,
                self::PREFIX . self::PATH_PRODUCT_ATTRIBUTE_SET,
                self::PREFIX . self::PATH_PRODUCT_IMPORT_IMAGE,
                self::PREFIX . self::PATH_PRODUCT_IMPORT_QTY,
                self::PREFIX . self::PATH_PRODUCT_GENERATE_SKU,
                self::PREFIX . self::PATH_PRODUCT_DELETE_HTML
            ))
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);

        foreach ($settings as $setting) {
            switch ($setting['path']) {
                case self::PREFIX . self::PATH_MARKETPLACE_TO_STORE_MAP:
                    $this->settings['marketplaces_stores'] = $this->coreHelper->jsonDecode($setting['value']);
                    continue;
                case self::PREFIX . self::PATH_INVENTORY_PRODUCT_IDENTIFIER:
                    $this->settings['product_identifier'] = $setting['value'];
                    continue;
                case self::PREFIX . self::PATH_INVENTORY_ACTION_FOUND:
                    $this->settings['action_found'] = $setting['value'];
                    continue;
                case self::PREFIX . self::PATH_PRODUCT_FIELDS_ATTRIBUTES_MAP:
                    $this->settings['fields_attributes'] = $this->coreHelper->jsonDecode($setting['value']);
                    continue;
                case self::PREFIX . self::PATH_FULL_SETTINGS:
                    $this->settings['full'] = $this->coreHelper->jsonDecode($setting['value']);
                    continue;
                case self::PREFIX . self::PATH_PRODUCT_ATTRIBUTE_SET:
                    $this->settings['attribute_set'] = $setting['value'];
                    continue;
                case self::PREFIX . self::PATH_PRODUCT_IMPORT_IMAGE:
                    $this->settings['import_image'] = (bool)$this->coreHelper->jsonDecode($setting['value']);
                    continue;
                case self::PREFIX . self::PATH_PRODUCT_IMPORT_QTY:
                    $this->settings['import_qty'] = (bool)$this->coreHelper->jsonDecode($setting['value']);
                    continue;
                case self::PREFIX . self::PATH_PRODUCT_GENERATE_SKU:
                    $this->settings['generate_sku'] = (bool)$this->coreHelper->jsonDecode($setting['value']);
                    continue;
                case self::PREFIX . self::PATH_PRODUCT_DELETE_HTML:
                    $this->settings['delete_html'] = (bool)$this->coreHelper->jsonDecode($setting['value']);
                    continue;
            }
        }
    }
}
