<?php

class M2E_E2M_Helper_Ebay_Config {

    const PREFIX = M2E_E2M_Helper_Data::PREFIX . 'ebay/setting/';

    //########################################

    const XML_PATH_PRODUCT_ATTRIBUTE_SET = self::PREFIX . 'product/attribute_set';
    const XML_PATH_STORE_MAP = self::PREFIX . 'store/map';
    const XML_PATH_PRODUCT_ATTRIBUTE_MAP = self::PREFIX . 'product/attribute/map';
    const XML_PATH_PRODUCT_GENERATE_SKU = self::PREFIX . 'product/generate/sku';
    const XML_PATH_PRODUCT_IDENTIFIER = self::PREFIX . 'product/identifier';
    const XML_PATH_PRODUCT_FOUND = self::PREFIX . 'product/found';
    const XML_PATH_PRODUCT_DELETE_HTML = self::PREFIX . 'product/delete/html';
    const XML_PATH_PRODUCT_IMPORT_IMAGE = self::PREFIX . 'product/import/image';
    const XML_PATH_PRODUCT_IMPORT_QTY = self::PREFIX . 'product/import/qty';
    const XML_PATH_PRODUCT_IMPORT_SPECIFICS = self::PREFIX . 'product/import/specifics';
    const XML_PATH_PRODUCT_IMPORT_RENAME_ATTRIBUTE = self::PREFIX . 'rename/attribute';

    const XML_PATH_FULL_SET_SETTING = self::PREFIX . 'full';

    //########################################

    const STORE_SKIP = -1;

    const PRODUCT_IDENTIFIER_SKU = 'SKU';
    const PRODUCT_IDENTIFIER_MPN = 'MPN';
    const PRODUCT_IDENTIFIER_EAN = 'EAN';
    const PRODUCT_IDENTIFIER_UPC = 'UPC';

    const ACTION_FOUND_IGNORE = 'IGNORE';
    const ACTION_FOUND_UPDATE = 'UPDATE';

    //########################################

    /** @var M2E_E2M_Helper_Data $dataHelper */
    private $dataHelper;

    //########################################

    /**
     * @return bool
     */
    public function isSKUProductIdentifier() {
        return self::PRODUCT_IDENTIFIER_SKU === $this->dataHelper->getConfig(self::XML_PATH_PRODUCT_IDENTIFIER);
    }

    /**
     * @return bool
     */
    public function isMPNProductIdentifier() {
        return self::PRODUCT_IDENTIFIER_MPN === $this->dataHelper->getConfig(self::XML_PATH_PRODUCT_IDENTIFIER);
    }

    /**
     * @return bool
     */
    public function isUPCProductIdentifier() {
        return self::PRODUCT_IDENTIFIER_UPC === $this->dataHelper->getConfig(self::XML_PATH_PRODUCT_IDENTIFIER);
    }

    /**
     * @return bool
     */
    public function isEANProductIdentifier() {
        return self::PRODUCT_IDENTIFIER_EAN === $this->dataHelper->getConfig(self::XML_PATH_PRODUCT_IDENTIFIER);
    }

    /**
     * @return bool
     */
    public function isIgnoreActionFound() {
        return self::ACTION_FOUND_IGNORE === $this->dataHelper->getConfig(self::XML_PATH_PRODUCT_FOUND);
    }

    /**
     * @return bool
     */
    public function isUpdateActionFound() {
        return self::ACTION_FOUND_UPDATE === $this->dataHelper->getConfig(self::XML_PATH_PRODUCT_FOUND);
    }

    /**
     * @return bool
     */
    public function isImportQty() {
        return (bool)$this->dataHelper->getConfig(self::XML_PATH_PRODUCT_IMPORT_QTY);
    }

    /**
     * @return bool
     */
    public function isImportSpecifics() {
        return (bool)$this->dataHelper->getConfig(self::XML_PATH_PRODUCT_IMPORT_SPECIFICS);
    }

    /**
     * @return bool
     */
    public function isImportRenameAttribute() {
        return (bool)$this->dataHelper->getConfig(self::XML_PATH_PRODUCT_IMPORT_RENAME_ATTRIBUTE);
    }

    /**
     * @return bool
     */
    public function isGenerateSku() {
        return (bool)$this->dataHelper->getConfig(self::XML_PATH_PRODUCT_GENERATE_SKU);
    }

    /**
     * @return bool
     */
    public function isImportImage() {
        return (bool)$this->dataHelper->getConfig(self::XML_PATH_PRODUCT_IMPORT_IMAGE);
    }

    /**
     * @return bool
     */
    public function isDeleteHtml() {
        return (bool)$this->dataHelper->getConfig(self::XML_PATH_PRODUCT_DELETE_HTML);
    }

    /**
     * @param int $marketplaceId
     *
     * @return int|null
     */
    public function getStoreForMarketplace($marketplaceId) {

        $marketplacesStores = $this->dataHelper->getConfig(self::XML_PATH_STORE_MAP, array());
        if (isset($marketplacesStores[$marketplaceId])) {
            return (int)$marketplacesStores[$marketplaceId];
        }

        return null;
    }

    /**
     * @param int $marketplaceId
     *
     * @return bool
     */
    public function isSkipStore($marketplaceId) {
        return self::STORE_SKIP === $this->getStoreForMarketplace($marketplaceId);
    }

    //########################################

    /**
     * M2E_E2M_Helper_Ebay_Config constructor.
     */
    public function __construct() {
        $this->dataHelper = Mage::helper('e2m');
    }
}
