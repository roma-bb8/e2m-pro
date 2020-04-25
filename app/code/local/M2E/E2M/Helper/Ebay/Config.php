<?php

class M2E_E2M_Helper_Ebay_Config extends M2E_E2M_Helper_Config {

    const PREFIX = parent::PREFIX . 'ebay/';

    //########################################

    const XML_PATH_STORE_MAP = self::PREFIX . 'store/map';

    const XML_PATH_PRODUCT_ATTRIBUTE_SET = self::PREFIX . 'product/attribute_set';
    const XML_PATH_PRODUCT_SKU = self::PREFIX . 'product/sku';
    const XML_PATH_PRODUCT_SKU_GENERATE = self::PREFIX . 'product/sku/generate';
    const XML_PATH_PRODUCT_HTML_DELETE = self::PREFIX . 'product/html_delete';

    //########################################

    const DOES_NOT_APPLY = 'does not apply';

    const PRODUCT_IDENTIFIER_SKU = 'ebay_sku';
    const PRODUCT_IDENTIFIER_MPN = 'ebay_mpn';
    const PRODUCT_IDENTIFIER_EAN = 'ebay_ean';
    const PRODUCT_IDENTIFIER_UPC = 'ebay_upc';

    //########################################

    /**
     * @return string
     */
    public function getProductIdentifier() {
        return $this->get(self::XML_PATH_PRODUCT_SKU);
    }

    /**
     * @return bool
     */
    public function isGenerateSku() {
        return (bool)$this->get(self::XML_PATH_PRODUCT_SKU_GENERATE);
    }

    /**
     * @return bool
     */
    public function isDeleteHtml() {
        return (bool)$this->get(self::XML_PATH_PRODUCT_HTML_DELETE);
    }

    //########################################

    /**
     * @param string $marketplaceCode
     *
     * @return bool|int
     */
    public function getStoreIdByMarketplaceCode($marketplaceCode) {

        $map = $this->get(self::XML_PATH_STORE_MAP, array());

        $marketplaceCode = strtoupper($marketplaceCode);
        if (isset($map[$marketplaceCode])) {
            return (int)$map[$marketplaceCode];
        }

        return false;
    }

    /**
     * @return bool|string
     */
    public function getMarketplaceCodeUseAdminStore() {

        $map = $this->get(self::XML_PATH_STORE_MAP, array());
        $site = array_search(M2E_E2M_Helper_Magento::STORE_ADMIN, $map);
        if (!empty($site)) {
            return strtolower($site);
        }

        return false;
    }

    /**
     * @param string $marketplaceCode
     *
     * @return bool
     */
    public function isMarketplaceSkip($marketplaceCode) {
        return M2E_E2M_Helper_Magento::STORE_SKIP === $this->getStoreIdByMarketplaceCode($marketplaceCode);
    }

    //########################################

    /**
     * @param array $item
     *
     * @return mixed
     */
    public function applySettings(array $item) {

        if ($this->isDeleteHtml()) {
            $item['ebay_title'] = strip_tags($item['ebay_title']);
            $item['ebay_subtitle'] = strip_tags($item['ebay_subtitle']);
            $item['ebay_description'] = strip_tags($item['ebay_description']);
        }

        $productSKU = $this->getProductIdentifier();
        if (empty($item[$productSKU]) && $this->isGenerateSku()) {
            $item[$productSKU] = 'SKU_' . md5($item['ebay_item_id'] . $item['item_variation_id']);
        }

        if (self::DOES_NOT_APPLY === strtolower($item[$productSKU]) && $this->isGenerateSku()) {
            $item[$productSKU] = 'DNA_' . md5($item['ebay_item_id'] . $item['item_variation_id']);
        }

        return $item;
    }
}
