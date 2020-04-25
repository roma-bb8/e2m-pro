<?php

class M2E_E2M_Helper_Ebay {

    const PREFIX = M2E_E2M_Helper_Data::PREFIX . 'ebay/';

    //########################################

    const MARKETPLACE_CHANNEL_AU = 'Australia';
    const MARKETPLACE_CHANNEL_AT = 'Austria';
    const MARKETPLACE_CHANNEL_BE_DU = 'Belgium_Dutch';
    const MARKETPLACE_CHANNEL_BE_FR = 'Belgium_French';
    const MARKETPLACE_CHANNEL_CA = 'Canada';
    const MARKETPLACE_CHANNEL_CA_FR = 'CanadaFrench';
    const MARKETPLACE_CHANNEL_MOTORS = 'eBayMotors';
    const MARKETPLACE_CHANNEL_FR = 'France';
    const MARKETPLACE_CHANNEL_DE = 'Germany';
    const MARKETPLACE_CHANNEL_HK = 'HongKong';
    const MARKETPLACE_CHANNEL_IN = 'India';
    const MARKETPLACE_CHANNEL_IE = 'Ireland';
    const MARKETPLACE_CHANNEL_IT = 'Italy';
    const MARKETPLACE_CHANNEL_MY = 'Malaysia';
    const MARKETPLACE_CHANNEL_NL = 'Netherlands';
    const MARKETPLACE_CHANNEL_PH = 'Philippines';
    const MARKETPLACE_CHANNEL_PL = 'Poland';
    const MARKETPLACE_CHANNEL_SG = 'Singapore';
    const MARKETPLACE_CHANNEL_SP = 'Spain';
    const MARKETPLACE_CHANNEL_CH = 'Switzerland';
    const MARKETPLACE_CHANNEL_UK = 'UK';
    const MARKETPLACE_CHANNEL_US = 'US';

    const MARKETPLACE_CODE_US = 'US';
    const MARKETPLACE_CODE_CA = 'CA';
    const MARKETPLACE_CODE_UK = 'UK';
    const MARKETPLACE_CODE_AU = 'AU';
    const MARKETPLACE_CODE_AT = 'AT';
    const MARKETPLACE_CODE_BE_FR = 'BE_FR';
    const MARKETPLACE_CODE_FR = 'FR';
    const MARKETPLACE_CODE_DE = 'DE';
    const MARKETPLACE_CODE_MOTORS = 'MOTOR';
    const MARKETPLACE_CODE_IT = 'IT';
    const MARKETPLACE_CODE_BE_DU = 'BE_DU';
    const MARKETPLACE_CODE_NL = 'NL';
    const MARKETPLACE_CODE_SP = 'SP';
    const MARKETPLACE_CODE_CH = 'CH';
    const MARKETPLACE_CODE_HK = 'HK';
    const MARKETPLACE_CODE_IN = 'IN';
    const MARKETPLACE_CODE_IE = 'IE';
    const MARKETPLACE_CODE_MY = 'MY';
    const MARKETPLACE_CODE_CA_FR = 'CA_FR';
    const MARKETPLACE_CODE_PH = 'PH';
    const MARKETPLACE_CODE_PL = 'PL';
    const MARKETPLACE_CODE_SG = 'SG';

    const MARKETPLACE_TITLE_AU = 'Australia';
    const MARKETPLACE_TITLE_AT = 'Austria';
    const MARKETPLACE_TITLE_BE_DU = 'Belgium Dutch';
    const MARKETPLACE_TITLE_BE_FR = 'Belgium French';
    const MARKETPLACE_TITLE_CA = 'Canada';
    const MARKETPLACE_TITLE_CA_FR = 'Canada French';
    const MARKETPLACE_TITLE_MOTORS = 'eBay Motors';
    const MARKETPLACE_TITLE_FR = 'France';
    const MARKETPLACE_TITLE_DE = 'Germany';
    const MARKETPLACE_TITLE_HK = 'Hong Kong';
    const MARKETPLACE_TITLE_IN = 'India';
    const MARKETPLACE_TITLE_IE = 'Ireland';
    const MARKETPLACE_TITLE_IT = 'Italy';
    const MARKETPLACE_TITLE_MY = 'Malaysia';
    const MARKETPLACE_TITLE_NL = 'Netherlands';
    const MARKETPLACE_TITLE_PH = 'Philippines';
    const MARKETPLACE_TITLE_PL = 'Poland';
    const MARKETPLACE_TITLE_SG = 'Singapore';
    const MARKETPLACE_TITLE_SP = 'Spain';
    const MARKETPLACE_TITLE_CH = 'Switzerland';
    const MARKETPLACE_TITLE_UK = 'United Kingdom';
    const MARKETPLACE_TITLE_US = 'United States';

    public static $MARKETPLACE_CODE = array(
        self::MARKETPLACE_CHANNEL_US => self::MARKETPLACE_CODE_US,
        self::MARKETPLACE_CHANNEL_CA => self::MARKETPLACE_CODE_CA,
        self::MARKETPLACE_CHANNEL_UK => self::MARKETPLACE_CODE_UK,
        self::MARKETPLACE_CHANNEL_AU => self::MARKETPLACE_CODE_AU,
        self::MARKETPLACE_CHANNEL_AT => self::MARKETPLACE_CODE_AT,
        self::MARKETPLACE_CHANNEL_BE_FR => self::MARKETPLACE_CODE_BE_FR,
        self::MARKETPLACE_CHANNEL_FR => self::MARKETPLACE_CODE_FR,
        self::MARKETPLACE_CHANNEL_DE => self::MARKETPLACE_CODE_DE,
        self::MARKETPLACE_CHANNEL_MOTORS => self::MARKETPLACE_CODE_MOTORS,
        self::MARKETPLACE_CHANNEL_IT => self::MARKETPLACE_CODE_IT,
        self::MARKETPLACE_CHANNEL_BE_DU => self::MARKETPLACE_CODE_BE_DU,
        self::MARKETPLACE_CHANNEL_NL => self::MARKETPLACE_CODE_NL,
        self::MARKETPLACE_CHANNEL_SP => self::MARKETPLACE_CODE_SP,
        self::MARKETPLACE_CHANNEL_CH => self::MARKETPLACE_CODE_CH,
        self::MARKETPLACE_CHANNEL_HK => self::MARKETPLACE_CODE_HK,
        self::MARKETPLACE_CHANNEL_IN => self::MARKETPLACE_CODE_IN,
        self::MARKETPLACE_CHANNEL_IE => self::MARKETPLACE_CODE_IE,
        self::MARKETPLACE_CHANNEL_MY => self::MARKETPLACE_CODE_MY,
        self::MARKETPLACE_CHANNEL_CA_FR => self::MARKETPLACE_CODE_CA_FR,
        self::MARKETPLACE_CHANNEL_PH => self::MARKETPLACE_CODE_PH,
        self::MARKETPLACE_CHANNEL_PL => self::MARKETPLACE_CODE_PL,
        self::MARKETPLACE_CHANNEL_SG => self::MARKETPLACE_CODE_SG
    );

    public static $MARKETPLACE_TITLE = array(
        self::MARKETPLACE_CODE_US => self::MARKETPLACE_TITLE_US,
        self::MARKETPLACE_CODE_CA => self::MARKETPLACE_TITLE_CA,
        self::MARKETPLACE_CODE_UK => self::MARKETPLACE_TITLE_UK,
        self::MARKETPLACE_CODE_AU => self::MARKETPLACE_TITLE_AU,
        self::MARKETPLACE_CODE_AT => self::MARKETPLACE_TITLE_AT,
        self::MARKETPLACE_CODE_BE_FR => self::MARKETPLACE_TITLE_BE_FR,
        self::MARKETPLACE_CODE_FR => self::MARKETPLACE_TITLE_FR,
        self::MARKETPLACE_CODE_DE => self::MARKETPLACE_TITLE_DE,
        self::MARKETPLACE_CODE_MOTORS => self::MARKETPLACE_TITLE_MOTORS,
        self::MARKETPLACE_CODE_IT => self::MARKETPLACE_TITLE_IT,
        self::MARKETPLACE_CODE_BE_DU => self::MARKETPLACE_TITLE_BE_DU,
        self::MARKETPLACE_CODE_NL => self::MARKETPLACE_TITLE_NL,
        self::MARKETPLACE_CODE_SP => self::MARKETPLACE_TITLE_SP,
        self::MARKETPLACE_CODE_CH => self::MARKETPLACE_TITLE_CH,
        self::MARKETPLACE_CODE_HK => self::MARKETPLACE_TITLE_HK,
        self::MARKETPLACE_CODE_IN => self::MARKETPLACE_TITLE_IN,
        self::MARKETPLACE_CODE_IE => self::MARKETPLACE_TITLE_IE,
        self::MARKETPLACE_CODE_MY => self::MARKETPLACE_TITLE_MY,
        self::MARKETPLACE_CODE_CA_FR => self::MARKETPLACE_TITLE_CA_FR,
        self::MARKETPLACE_CODE_PH => self::MARKETPLACE_TITLE_PH,
        self::MARKETPLACE_CODE_PL => self::MARKETPLACE_TITLE_PL,
        self::MARKETPLACE_CODE_SG => self::MARKETPLACE_TITLE_SG
    );

    //########################################

    const XML_PATH_AVAILABLE_MARKETPLACES = self::PREFIX . 'marketplaces';

    const XML_PATH_INVENTORY_VARIATION_COUNT = self::PREFIX . 'inventory/variation/count';
    const XML_PATH_INVENTORY_SIMPLE_COUNT = self::PREFIX . 'inventory/simple/count';
    const XML_PATH_INVENTORY_TOTAL_COUNT = self::PREFIX . 'inventory/total/count';

    //########################################

    /**
     * @param string $code
     *
     * @return string
     */
    public function getMarketplaceTitleByCode($code) {
        return self::$MARKETPLACE_TITLE[$code];
    }

    //########################################

    /**
     * @return array
     * @throws Exception
     */
    public function getExportAttributes() {

        $ebayAttributesExport = Mage::helper('e2m')->getDataCSVFile('ebay_attributes_export.csv');

        $data = array();
        foreach ($ebayAttributesExport as $item) {
            if (empty($item['magento_attribute_code'])) {
                $data[$item['ebay_property_code']] = $item['ebay_property_code'];

                continue;
            }

            $data[$item['ebay_property_code']] = $item['magento_attribute_code'];
        }

        return $data;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getMatchingAttributes() {

        $ebayAttributesMatching = Mage::helper('e2m')->getDataCSVFile('ebay_attributes_matching.csv');

        $data = array();
        foreach ($ebayAttributesMatching as $item) {
            if (!isset($data[$item['name_code']])) {
                $data[$item['name_code']]['type'] = $item['type'];
            }

            $data[$item['name_code']]['name'][$item['site']] = $item['name'];

            if (M2E_E2M_Helper_Magento::TYPE_TEXT === $item['type']) {
                continue;
            }

            if (M2E_E2M_Helper_Magento::TYPE_SELECT === $item['type']) {
                $data[$item['name_code']]['value'][$item['value_code']][$item['site']] = $item['value'];
            }
        }

        return $data;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getExportSpecifics() {

        $ebayAttributesMatching = Mage::helper('e2m')->getDataCSVFile('ebay_attributes_matching.csv');
        $attributesExport = $this->getExportAttributes();

        $data = array();
        foreach ($ebayAttributesMatching as $item) {
            if (isset($attributesExport[$item['name_code']])) {
                $data[$item['name_code']] = $attributesExport[$item['name_code']];

                continue;
            }

            $data[$item['name']] = $item['name_code'];
        }

        return $data;
    }
}
