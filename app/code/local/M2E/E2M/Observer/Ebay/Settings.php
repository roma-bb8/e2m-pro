<?php

class M2E_E2M_Observer_Ebay_Settings {

    public function process() {

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        //----------------------------------------

        $productIdentifier = $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_IDENTIFIER, null);
        $actionFound = $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_FOUND, null);
        $attributeSet = $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET, null);
        $importImage = $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_IMPORT_IMAGE, null);
        $importQty = $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_IMPORT_QTY, null);
        $generateSku = $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_GENERATE_SKU, null);
        $deleteHtml = $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_DELETE_HTML, null);
        $marketplacesStores = $dataHelper->getConfig(
            M2E_E2M_Helper_Ebay_Config::XML_PATH_STORE_MAP,
            array()
        );
        $marketplacesAvailable = $dataHelper->getConfig(
            M2E_E2M_Helper_Data::XML_PATH_EBAY_AVAILABLE_MARKETPLACES,
            array()
        );
        $fieldsAttributes = $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_MAP);
        $is = count($marketplacesStores) === count($marketplacesAvailable);

        $isFull = isset($productIdentifier) &&
            isset($actionFound) &&
            isset($attributeSet) &&
            isset($importImage) &&
            isset($importQty) &&
            isset($generateSku) &&
            isset($deleteHtml) &&
            isset($deleteHtml) &&
            $is &&
            !empty($fieldsAttributes);

        //----------------------------------------

        $dataHelper->setConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_FULL_SET_SETTING, $isFull, true);
    }
}
