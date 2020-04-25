<?php

class M2E_E2M_Helper_Ebay_Config {

    const PREFIX = M2E_E2M_Helper_Data::PREFIX . 'ebay/setting/';

    //########################################

    const XML_PATH_PRODUCT_ATTRIBUTE_MAP = self::PREFIX . 'store/map';

    const XML_PATH_PRODUCT_ATTRIBUTE_SET = self::PREFIX . 'product/attribute_set';
    const XML_PATH_PRODUCT_IDENTIFIER = self::PREFIX . 'product/sku';
    const XML_PATH_PRODUCT_GENERATE_SKU = self::PREFIX . 'product/sku/generate';
    const XML_PATH_PRODUCT_DELETE_HTML = self::PREFIX . 'product/delete_html';

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
        return Mage::helper('e2m')->getConfig(self::XML_PATH_PRODUCT_IDENTIFIER);
    }

    /**
     * @return bool
     */
    public function isGenerateSku() {
        return (bool)Mage::helper('e2m')->getConfig(self::XML_PATH_PRODUCT_GENERATE_SKU);
    }

    /**
     * @return bool
     */
    public function isDeleteHtml() {
        return (bool)Mage::helper('e2m')->getConfig(self::XML_PATH_PRODUCT_DELETE_HTML);
    }

    //########################################

    /**
     * @param array $item
     *
     * @return mixed
     */
    public function applySettings(array $item) {

        if (Mage::helper('e2m/Ebay_Config')->isDeleteHtml()) {
            $item['ebay_title'] = strip_tags($item['ebay_title']);
            $item['ebay_subtitle'] = strip_tags($item['ebay_subtitle']);
            $item['ebay_description'] = strip_tags($item['ebay_description']);
        }

        $productSKU = Mage::helper('e2m/Ebay_Config')->getProductIdentifier();
        if (empty($item[$productSKU]) && Mage::helper('e2m/Ebay_Config')->isGenerateSku()) {
            $item[$productSKU] = 'SKU_' . md5($item['ebay_item_id'] . $item['item_variation_id']);
        }

        if (self::DOES_NOT_APPLY === strtolower($item[$productSKU]) &&
            Mage::helper('e2m/Ebay_Config')->isGenerateSku()) {
            $item[$productSKU] = 'DNA_' . md5($item['ebay_item_id'] . $item['item_variation_id']);
        }

        return $item;
    }
}
