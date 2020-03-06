<?php

class M2E_E2M_Model_Adapter_Ebay_Item {

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

    public static $MARKETPLACE_ID = array(
        self::MARKETPLACE_US => self::MARKETPLACE_US_ID,
        self::MARKETPLACE_CA => self::MARKETPLACE_CA_ID,
        self::MARKETPLACE_UK => self::MARKETPLACE_UK_ID,
        self::MARKETPLACE_AU => self::MARKETPLACE_AU_ID,
        self::MARKETPLACE_AT => self::MARKETPLACE_AT_ID,
        self::MARKETPLACE_BE_FR => self::MARKETPLACE_BE_FR_ID,
        self::MARKETPLACE_FR => self::MARKETPLACE_FR_ID,
        self::MARKETPLACE_DE => self::MARKETPLACE_DE_ID,
        self::MARKETPLACE_MOTORS => self::MARKETPLACE_MOTORS,
        self::MARKETPLACE_IT => self::MARKETPLACE_IT_ID,
        self::MARKETPLACE_BE_DU => self::MARKETPLACE_BE_DU_ID,
        self::MARKETPLACE_NL => self::MARKETPLACE_NL_ID,
        self::MARKETPLACE_SP => self::MARKETPLACE_SP_ID,
        self::MARKETPLACE_CH => self::MARKETPLACE_CH_ID,
        self::MARKETPLACE_HK => self::MARKETPLACE_HK_ID,
        self::MARKETPLACE_IN => self::MARKETPLACE_IN_ID,
        self::MARKETPLACE_IE => self::MARKETPLACE_IE_ID,
        self::MARKETPLACE_MY => self::MARKETPLACE_MY_ID,
        self::MARKETPLACE_CA_FR => self::MARKETPLACE_CA_FR_ID,
        self::MARKETPLACE_PH => self::MARKETPLACE_PH_ID,
        self::MARKETPLACE_PL => self::MARKETPLACE_PL_ID,
        self::MARKETPLACE_SG => self::MARKETPLACE_SG_ID
    );

    //########################################

    private function getIdentifiers(array $item) {

        $identifiers = array();

        $identifiers['identifiers_item_id'] = $item['identifiers']['item_id'];
        $identifiers['identifiers_sku'] = $item['identifiers']['sku'];
        $identifiers['identifiers_ean'] = $item['identifiers']['ean'];
        $identifiers['identifiers_upc'] = $item['identifiers']['upc'];
        $identifiers['identifiers_isbn'] = $item['identifiers']['isbn'];
        $identifiers['identifiers_epid'] = $item['identifiers']['epid'];
        $identifiers['identifiers_brand_mpn_brand'] = $item['identifiers']['brand_mpn']['brand'];
        $identifiers['identifiers_brand_mpn_mpn'] = $item['identifiers']['brand_mpn']['mpn'];

        return $identifiers;
    }

    private function getPrice(array $item) {

        $price = array();

        $price['price_currency'] = $item['price']['currency'];
        $price['price_start'] = $item['price']['start'];
        $price['price_buy_it_now'] = $item['price']['buy_it_now'];
        $price['price_current'] = $item['price']['current'];
        $price['price_map_value'] = $item['price']['map']['value'];
        $price['price_map_exposure'] = $item['price']['map']['exposure'];
        $price['price_stp_value'] = $item['price']['stp']['value'];

        return $price;
    }

    private function getQty(array $item) {

        $qty = array();

        $qty['qty_total'] = $item['qty']['total'];

        return $qty;
    }

    private function getDescription(array $item) {

        $description = array();

        $description['description_title'] = $item['description']['title'];
        $description['description_subtitle'] = $item['description']['subtitle'];
        $description['description_description'] = $item['description']['description'];

        return $description;
    }

    private function getImages(array $item) {

        $images = array();

        $images['images_gallery_type'] = $item['images']['gallery_type'];
        $images['images_photo_display'] = $item['images']['photo_display'];
        $images['images_urls'] = $item['images']['urls'];

        return $images;
    }

    private function getCondition(array $item) {

        $condition = array();

        $condition['condition_type'] = $item['condition']['type'];
        $condition['condition_name'] = $item['condition']['name'];
        $condition['condition_description'] = $item['condition']['description'];

        return $condition;
    }

    private function getCategories(array $item) {

        $categories = array();

        $categories['categories_primary_id'] = $item['categories']['primary']['id'];
        $categories['categories_primary_name'] = $item['categories']['primary']['name'];
        $categories['categories_secondary_id'] = $item['categories']['secondary']['id'];
        $categories['categories_secondary_name'] = $item['categories']['secondary']['name'];

        return $categories;
    }

    private function getStore(array $item) {

        $store = array();

        $store['store_categories_primary_id'] = $item['store']['categories']['primary']['id'];
        $store['store_categories_primary_name'] = $item['store']['categories']['primary']['name'];
        $store['store_categories_secondary_id'] = $item['store']['categories']['secondary']['id'];
        $store['store_categories_secondary_name'] = $item['store']['categories']['secondary']['name'];
        $store['store_url'] = $item['store']['url'];

        return $store;
    }

    private function getShipping(array $item) {

        $shippingData = array();

        $shippingData['shipping_dispatch_time'] = $item['shipping']['dispatch_time'];
        $shippingData['shipping_package_dimensions_depth'] = $item['shipping']['package']['dimensions']['depth'];
        $shippingData['shipping_package_dimensions_length'] = $item['shipping']['package']['dimensions']['length'];
        $shippingData['shipping_package_dimensions_width'] = $item['shipping']['package']['dimensions']['width'];

        return $shippingData;
    }

    //########################################

    /**
     * @param array $data
     *
     * @return array
     */
    public function process(array $data) {

        $item = array();
        $item['marketplace_id'] = self::$MARKETPLACE_ID[$data['marketplace']];
        $item = array_merge(
            $item,
            $this->getIdentifiers($data),
            $this->getPrice($data),
            $this->getQty($data),
            $this->getDescription($data),
            $this->getImages($data),
            $this->getCondition($data),
            $this->getCategories($data),
            $this->getStore($data),
            $this->getShipping($data)
        );

        $specifics = array();
        if (!empty($data['item_specifics'])) {
            foreach ($data['item_specifics'] as $nameSpecific => $specificData) {
                $specifics[$specificData['name']] = $specificData['value'];
            }
        }
        $item['specifics'] = $specifics;
        $item['variations'] = $data['variations'];

        return $item;
    }
}
