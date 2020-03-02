<?php

class M2E_E2M_Observer_Ebay_Settings {

    public function process() {

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        //----------------------------------------

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

        $isFull = $is && !empty($fieldsAttributes);

        //----------------------------------------

        $dataHelper->setConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_FULL_SET_SETTING, $isFull, true);
    }
}
