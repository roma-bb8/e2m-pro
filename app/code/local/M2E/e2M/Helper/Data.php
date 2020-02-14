<?php

class M2E_e2M_Helper_Data extends Mage_Core_Helper_Abstract {

    const PREFIX = 'm2e/e2m/';
    const EBAY_DOWNLOAD_INVENTORY = 'ebay/download/inventory';
    const MAGENTO_IMPORT_INVENTORY = 'magento/import/inventory';

    private $stores = array();

    const MARKETPLACE_CUSTOM_CODE_TITLE = 'Custom';
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
    const MARKETPLACE_RU_TITLE = 'Russia';
    const MARKETPLACE_SG_TITLE = 'Singapore';
    const MARKETPLACE_SP_TITLE = 'Spain';
    const MARKETPLACE_CH_TITLE = 'Switzerland';
    const MARKETPLACE_UK_TITLE = 'United Kingdom';
    const MARKETPLACE_US_TITLE = 'United States';

    private $marketplaceTitles = array(
        M2E_e2M_Helper_Full::MARKETPLACE_CUSTOM_CODE_ID => self::MARKETPLACE_CUSTOM_CODE_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_AU_ID => self::MARKETPLACE_AU_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_AT_ID => self::MARKETPLACE_AT_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_BE_DU_ID => self::MARKETPLACE_BE_DU_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_BE_FR_ID => self::MARKETPLACE_BE_FR_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_CA_ID => self::MARKETPLACE_CA_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_CA_FR_ID => self::MARKETPLACE_CA_FR_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_MOTORS_ID => self::MARKETPLACE_MOTORS_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_FR_ID => self::MARKETPLACE_FR_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_DE_ID => self::MARKETPLACE_DE_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_HK_ID => self::MARKETPLACE_HK_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_IN_ID => self::MARKETPLACE_IN_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_IE_ID => self::MARKETPLACE_IE_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_IT_ID => self::MARKETPLACE_IT_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_MY_ID => self::MARKETPLACE_MY_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_NL_ID => self::MARKETPLACE_NL_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_PH_ID => self::MARKETPLACE_PH_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_PL_ID => self::MARKETPLACE_PL_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_RU_ID => self::MARKETPLACE_RU_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_SG_ID => self::MARKETPLACE_SG_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_SP_ID => self::MARKETPLACE_SP_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_CH_ID => self::MARKETPLACE_CH_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_UK_ID => self::MARKETPLACE_UK_TITLE,
        M2E_e2M_Helper_Full::MARKETPLACE_US_ID => self::MARKETPLACE_US_TITLE
    );

    const MAX_UPDATE_DATE = 360;




    private $url;

private $resultAttributes = array();
private $ebayFields = array();
private $attributeSet = array();

    public function getMemberUrl($ebayMemberId, $accountMode = M2E_e2M_Helper_eBay_Account::MODE_PRODUCTION) {
        $domain = 'ebay.com';
        if ($accountMode == M2E_e2M_Helper_eBay_Account::MODE_SANDBOX) {
            $domain = 'sandbox.' . $domain;
        }

        return 'http://' . $domain . '/' . (string)$ebayMemberId;
    }


    private $header = array();
    private $body = array();

    /**
     * @param array $header
     *
     * @return $this
     */
    public function setHeader($header) {
        $this->header = $header;

        return $this;
    }

    /**
     * @param string $url
     *
     * @return $this
     */
    public function setURL($url) {
        $this->url = $url;

        return $this;
    }

    /**
     * @param string $body
     *
     * @return $this
     */
    public function setBody($body) {
        $this->body = $body;

        return $this;
    }

    public function getResponse() {

        $cURL = curl_init();
        curl_setopt_array($cURL, array(
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 15,
            CURLOPT_TIMEOUT => 600,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $this->body,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $this->header($this->header),
        ));

        $response = curl_exec($cURL);
        list($header, $body) = explode("\r\n\r\n", $response, 2);

        curl_close($cURL);

        return $body;
    }

    private function header(array $parameters) {
        $queryParameters = array();
        foreach ($parameters as $key => $value) {
            $queryParameters[] = "{$key}: {$value}";
        }
        return $queryParameters;
    }

    public function getMarketplaceTitle($marketplaceId) {
        return $this->marketplaceTitles[$marketplaceId];
    }

    public function getEbayAllFields() {
        if (!empty($this->ebayFields)) {
            return $this->ebayFields;
        }

        return $this->ebayFields = array(
            'identifiers][item_id' => 'Item ID',
            'identifiers][sku' => 'SKU',
            'identifiers][ean' => 'EAN',
            'identifiers][upc' => 'UPC',
            'identifiers][isbn' => 'ISBN',
            'identifiers][epid' => 'EPID',
            'identifiers][brand_mpn][mpn' => '(Brand) MPN',
            'identifiers][brand_mpn][brand' => '(Brand) Brand',

            'marketplace_id' => '(Site) Marketplace ID',
            'categories][primary][id' => '(Category) Primary ID',
            'categories][secondary][id' => '(Category) Secondary ID',
            'store][categories][primary][id' => '(Store) Category ID',
            'store][categories][secondary][id' => '(Store) Category 2 ID',

            'description][title' => 'Title',
            'description][subtitle' => 'SubTitle',
            'description][description' => 'Description',

            'price][start' => 'Start Price',
            'price][current' => 'Current Price',
            'price][buy_it_now' => 'Buy It Now Price',
            'price][original' => 'Original Price',
            'price][map][value' => '(Discount Price Info) Minimum Advertised Price',
            'price][map][exposure' => '(Discount Price Info) Minimum Advertised Price Exposure',
            'price][stp][value' => '(Discount Price Info) Original Retail Price',

            'qty][total' => 'Quantity',

            'shipping][dispatch_time' => 'Dispatch Time',
            'shipping][package][dimensions][depth' => '(Dimensions) Depth',
            'shipping][package][dimensions][length' => '(Dimensions) Length',
            'shipping][package][dimensions][width' => '(Dimensions) Width',
            'shipping][package][dimensions][unit_type' => 'Unit Type',

            'condition][type' => 'Condition ID'
        );
    }

    public function getMagentoAllAttributes() {
        if (!empty($this->resultAttributes)) {
            return $this->resultAttributes;
        }

        $attributeCollection = Mage::getResourceModel('catalog/product_attribute_collection')
            ->addVisibleFilter()
            ->setOrder('frontend_label', Varien_Data_Collection_Db::SORT_ORDER_ASC);

        $resultAttributes = array();
        foreach ($attributeCollection->getItems() as $attribute) {
            $resultAttributes[$attribute['attribute_code']] = $attribute['frontend_label'];
        }

        return $this->resultAttributes = $resultAttributes;
    }

    public function getAllAttributeSet() {
        if (!empty($this->attributeSet)) {
            return $this->attributeSet;
        }

        $entityType = Mage::getModel('catalog/product')->getResource()->getTypeId();
        $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection');
        $attributeSetCollection->setEntityTypeFilter($entityType);
        foreach ($attributeSetCollection as $attributeSet) {
            $name = $attributeSet->getAttributeSetName();
            $attributeSetId = (int)$attributeSet->getId();

            $this->attributeSet[$attributeSetId] = $name;
        }

        return $this->attributeSet;
    }

    public function getMagentoStores() {
        if (!empty($stores)) {
            return $this->stores;
        }

        foreach (Mage::app()->getStores(true) as $store) {
            /** @var Mage_Core_Model_Store $store */
            $this->stores[$store->getId()] = $store->getName();
        }

        return $this->stores;
    }
}
