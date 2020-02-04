<?php

class M2E_e2M_Helper_Data extends Mage_Core_Helper_Abstract {

    const MODE_SANDBOX = 0;
    const MODE_PRODUCTION = 1;

    const RETURN_TYPE_ARRAYS = 2;


    private $url;

    const MARKETPLACE_US_ID = 0;
    const MARKETPLACE_CA_ID = 2;
    const MARKETPLACE_UK_ID = 3;
    const MARKETPLACE_AU_ID = 15;
    const MARKETPLACE_AT_ID = 16;
    const MARKETPLACE_BE_FR_ID = 23;
    const MARKETPLACE_FR_ID = 71;
    const MARKETPLACE_DE_ID = 77;
    const MARKETPLACE_MOTORS_ID = 100;
    const MARKETPLACE_IT_ID = 101;
    const MARKETPLACE_BE_DU_ID = 123;
    const MARKETPLACE_NL_ID = 146;
    const MARKETPLACE_SP_ID = 186;
    const MARKETPLACE_CH_ID = 193;
    const MARKETPLACE_HK_ID = 201;
    const MARKETPLACE_IN_ID = 203;
    const MARKETPLACE_IE_ID = 205;
    const MARKETPLACE_MY_ID = 207;
    const MARKETPLACE_CA_FR_ID = 210;
    const MARKETPLACE_PH_ID = 211;
    const MARKETPLACE_PL_ID = 212;
    const MARKETPLACE_SG_ID = 216;

    public function getMemberUrl($ebayMemberId, $accountMode = M2E_e2M_Helper_Data::MODE_PRODUCTION) {
        $domain = 'ebay.com';
        if ($accountMode == M2E_e2M_Helper_Data::MODE_SANDBOX) {
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

    public function geteBayAttributesAll() {
        return array(
            'marketplace',
            'identifiers -> item_id',
            'identifiers -> sku',
            'identifiers -> ean',
            'identifiers -> upc',
            'identifiers -> epid',
            'identifiers -> isbn',
            'identifiers -> brand_mpn',
            'identifiers -> brand',
            'identifiers -> mpn',
            'format -> type',
            'format -> duration',
            'format -> is_private',
            'price -> currency',
            'price -> start',
            'price -> reserve',
            'price -> buy_it_now',
            'price -> current',
            'price -> map',
            'price -> map -> value',
            'price -> map ->exposure',
            'price -> stp -> value',
            'price -> stp -> sold_on_ebay',
            'price -> stp -> sold_off_ebay',
            'qty -> total',
            'qty -> sold',
            '(qty -> sold) - (qty -> total)',
            'description -> title',
            'description -> subtitle',
            'description -> description',
            'description -> enhancement',
            'images -> gallery_type',
            'images -> photo_display',
            'images -> urls',
            'condition -> type',
            'condition -> name',
            'condition -> description',
            'categories -> primary -> id',
            'categories -> primary -> name',
            'categories -> secondary -> id',
            'categories -> secondary -> name',
            'selling -> bid_count',
            'selling -> start_time',
            'selling -> end_time',
            'selling -> status',
            'selling -> is_tax_table_enabled',
            'selling -> best_offer',
            'selling -> best_offer -> is_enabled',
            'selling -> best_offer -> auto_accept_price',
            'selling -> best_offer -> min_price',
            'selling -> vat_percent',
            'store -> categories -> primary -> id',
            'store -> categories -> primary -> name',
            'store -> categories -> secondary -> id',
            'store -> categories -> secondary -> name',
            'store -> categories -> url',
            'payment -> methods',
            'payment -> paypal',
            'return -> accepted',
            'return -> option',
            'return -> within',
            'return -> shipping_cost',
            'return -> description',
            'return -> international -> accepted',
            'return -> international -> option',
            'return -> international -> within',
            'return -> international -> shipping_cost',
            'shipping -> address',
            'shipping -> country',
            'shipping -> postal_code',
            'shipping -> dispatch_time',
            'shipping -> cash_on_delivery_cost',
            'shipping -> global_shipping_program',
            'shipping -> click_and_collect_enabled',
            'shipping -> pickup_in_store_enabled',
            'shipping -> cross_border_trade -> rate_table_details -> domestic_rate_table',
            'shipping -> cross_border_trade -> rate_table_details -> international_rate_table',
            'shipping -> locations',
            'shipping -> excluded_locations',
            'shipping -> local -> type',
            'shipping -> local -> discount_enabled',
            'shipping -> local -> discount_profile_id',
            'shipping -> local -> handling_cost',
            'shipping -> local -> methods -> service',
            'shipping -> local -> methods -> cost',
            'shipping -> local -> methods -> cost_additional',
            'shipping -> local -> methods -> cost_surcharge',
            'shipping -> local -> methods -> is_free',
            'shipping -> local -> methods -> priority',
            'shipping -> international -> type',
            'shipping -> international -> discount_enabled',
            'shipping -> international -> discount_profile_id',
            'shipping -> international -> handling_cost',
            'shipping -> international -> methods -> service',
            'shipping -> international -> methods -> cost',
            'shipping -> international -> methods -> cost_additional',
            'shipping -> international -> methods -> cost_surcharge',
            'shipping -> international -> methods -> priority',
            'shipping -> international -> methods -> locations',
            'shipping -> package -> measurement_system',
            'shipping -> package -> package',
            'shipping -> package -> weight -> major',
            'shipping -> package -> weight -> minor',
            'shipping -> package -> dimensions -> depth',
            'shipping -> package -> dimensions -> length',
            'shipping -> package -> dimensions -> width',
            'policies -> shipping -> id',
            'policies -> shipping -> name',
            'policies -> payment -> id',
            'policies -> payment -> name',
            'policies -> return -> id',
            'policies -> return -> name',
            'other -> application_data',
            'other -> is_revised',
            'other -> hit_counter',
            'other -> url',
            'other -> originating_postal_code',
            'other -> charity -> id',
            'other -> charity -> percent',
            'variations',
            'item_specifics',
            'compatibility_list',
        );
    }

    public function getMagentoAttributesAll() {
        $attributeCollection = Mage::getResourceModel('catalog/product_attribute_collection')
            ->addVisibleFilter()
            ->setOrder('frontend_label', Varien_Data_Collection_Db::SORT_ORDER_ASC);

        $resultAttributes = array();
        foreach ($attributeCollection->getItems() as $attribute) {
            $resultAttributes[$attribute['attribute_code']] = $attribute['frontend_label'];
        }

        return $resultAttributes;
    }
}
