<?php

/**
 * Class M2E_e2M_Model_Parser_eBay_Item
 */
class M2E_e2M_Model_Parser_eBay_Item extends Mage_Core_Helper_Abstract {

    const MARKETPLACE_CUSTOM_CODE_ID = 'custom_code';
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
    const MARKETPLACE_RU_ID = 215;
    const MARKETPLACE_SG_ID = 216;

    const MARKETPLACE_CUSTOM = 'CustomCode';
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
    const MARKETPLACE_RU = 'Russia';
    const MARKETPLACE_SG = 'Singapore';
    const MARKETPLACE_SP = 'Spain';
    const MARKETPLACE_CH = 'Switzerland';
    const MARKETPLACE_UK = 'UK';
    const MARKETPLACE_US = 'US';

    //########################################

    public static $MARKETPLACES_CODE_ID = array(
        self::MARKETPLACE_CUSTOM => self::MARKETPLACE_CUSTOM_CODE_ID,
        self::MARKETPLACE_AU => self::MARKETPLACE_AU_ID,
        self::MARKETPLACE_AT => self::MARKETPLACE_AT_ID,
        self::MARKETPLACE_BE_DU => self::MARKETPLACE_BE_DU_ID,
        self::MARKETPLACE_BE_FR => self::MARKETPLACE_BE_FR_ID,
        self::MARKETPLACE_CA => self::MARKETPLACE_CA_ID,
        self::MARKETPLACE_CA_FR => self::MARKETPLACE_CA_FR_ID,
        self::MARKETPLACE_MOTORS => self::MARKETPLACE_MOTORS_ID,
        self::MARKETPLACE_FR => self::MARKETPLACE_FR_ID,
        self::MARKETPLACE_DE => self::MARKETPLACE_DE_ID,
        self::MARKETPLACE_HK => self::MARKETPLACE_HK_ID,
        self::MARKETPLACE_IN => self::MARKETPLACE_IN_ID,
        self::MARKETPLACE_IE => self::MARKETPLACE_IE_ID,
        self::MARKETPLACE_IT => self::MARKETPLACE_IT_ID,
        self::MARKETPLACE_MY => self::MARKETPLACE_MY_ID,
        self::MARKETPLACE_NL => self::MARKETPLACE_NL_ID,
        self::MARKETPLACE_PH => self::MARKETPLACE_PH_ID,
        self::MARKETPLACE_PL => self::MARKETPLACE_PL_ID,
        self::MARKETPLACE_RU => self::MARKETPLACE_RU_ID,
        self::MARKETPLACE_SG => self::MARKETPLACE_SG_ID,
        self::MARKETPLACE_SP => self::MARKETPLACE_SP_ID,
        self::MARKETPLACE_CH => self::MARKETPLACE_CH_ID,
        self::MARKETPLACE_UK => self::MARKETPLACE_UK_ID,
        self::MARKETPLACE_US => self::MARKETPLACE_US_ID
    );

    //########################################

    /**
     * @param SimpleXMLElement $item
     *
     * @return mixed|string
     */
    private function getMarketplace(SimpleXMLElement $item) {
        if (!isset($item->Site)) {
            return self::MARKETPLACE_CUSTOM_CODE_ID;
        }

        return static::$MARKETPLACES_CODE_ID[(string)$item->Site];
    }

    /**
     * @param SimpleXMLElement $item
     *
     * @return array
     */
    private function getIdentifiers(SimpleXMLElement $item) {
        $identifiers = array();

        $identifiers['item_id'] = isset($item->ItemID) ? (double)$item->ItemID : null;
        $identifiers['sku'] = isset($item->SKU) ? (string)$item->SKU : null;
        if (!isset($item->ProductListingDetails)) {
            $identifiers['ean'] = null;
            $identifiers['upc'] = null;
            $identifiers['isbn'] = null;
            $identifiers['epid'] = null;
            $identifiers['brand_mpn']['brand'] = null;
            $identifiers['brand_mpn']['mpn'] = null;

            return $identifiers;
        }

        $identifiers['ean'] = isset($item->ProductListingDetails->EAN) ? (string)$item->ProductListingDetails->EAN : null;
        $identifiers['upc'] = isset($item->ProductListingDetails->UPC) ? (string)$item->ProductListingDetails->UPC : null;
        $identifiers['isbn'] = isset($item->ProductListingDetails->ISBN) ? (string)$item->ProductListingDetails->ISBN : null;
        $identifiers['epid'] = isset($item->ProductListingDetails->ProductReferenceID) ? (string)$item->ProductListingDetails->ProductReferenceID : null;
        $identifiers['brand_mpn']['brand'] = isset($item->ProductListingDetails->BrandMPN->Brand) ? (string)$item->ProductListingDetails->BrandMPN->Brand : null;
        $identifiers['brand_mpn']['mpn'] = isset($item->ProductListingDetails->BrandMPN->MPN) ? (string)$item->ProductListingDetails->BrandMPN->MPN : null;

        return $identifiers;
    }

    /**
     * @param SimpleXMLElement $item
     *
     * @return array
     */
    private function getPrice(SimpleXMLElement $item) {
        $price = array();

        $price['currency'] = isset($item->Currency) ? (string)$item->Currency : null;
        $price['start'] = isset($item->StartPrice) ? (float)$item->StartPrice : null;
        $price['buy_it_now'] = isset($item->BuyItNowPrice) ? (float)$item->BuyItNowPrice : null;

        if (isset($item->SellingStatus)) {
            $price['current'] = isset($item->ProductListingDetails->EAN) ? (float)$item->SellingStatus->CurrentPrice : null;
            $price['original'] = isset($item->ProductListingDetails->EAN) ? (float)$item->SellingStatus->OriginalPrice : null;
        } else {
            $price['current'] = null;
            $price['original'] = null;
        }

        if (isset($item->DiscountPriceInfo)) {
            $price['map']['value'] = isset($item->ProductListingDetails->EAN) ? (float)$item->DiscountPriceInfo->MinimumAdvertisedPrice : null;
            $price['map']['exposure'] = isset($item->ProductListingDetails->EAN) ? (string)$item->DiscountPriceInfo->MinimumAdvertisedPriceExposure : null;
            $price['stp']['value'] = isset($item->ProductListingDetails->EAN) ? (float)$item->DiscountPriceInfo->OriginalRetailPrice : null;
        } else {
            $price['map']['value'] = null;
            $price['map']['exposure'] = null;
            $price['stp']['value'] = null;
        }

        return $price;
    }

    /**
     * @param SimpleXMLElement $item
     *
     * @return array
     */
    private function getQty(SimpleXMLElement $item) {
        $qty = array();

        $qty['total'] = isset($item->Quantity) ? (int)$item->Quantity : null;

        return $qty;
    }

    /**
     * @param SimpleXMLElement $item
     *
     * @return array
     */
    private function getDescription(SimpleXMLElement $item) {
        $description = array();

        $description['title'] = isset($item->Title) ? (string)$item->Title : null;
        $description['subtitle'] = isset($item->SubTitle) ? (string)$item->SubTitle : null;
        $description['description'] = isset($item->Description) ? (string)$item->Description : null;

        return $description;
    }

    /**
     * @param SimpleXMLElement $item
     *
     * @return array
     */
    private function getImages(SimpleXMLElement $item) {
        $images = array();

        if (!isset($item->PictureDetails)) {
            $images['gallery_type'];
            $images['photo_display'];
            $images['urls'] = array();

            return $images;
        }

        $images['gallery_type'] = isset($item->PictureDetails->GalleryType) ? (string)$item->PictureDetails->GalleryType : null;
        $images['photo_display'] = isset($item->PictureDetails->PhotoDisplay) ? (string)$item->PictureDetails->PhotoDisplay : null;
        $images['urls'] = array();
        if (isset($item->PictureDetails->PictureURL)) {
            foreach ($item->PictureDetails->PictureURL as $pictureURL) {
                $images['urls'][] = (string)$pictureURL;
            }
        }

        return $images;
    }

    /**
     * @param SimpleXMLElement $item
     *
     * @return array
     */
    private function getCondition(SimpleXMLElement $item) {
        $condition = array();

        $condition['type'] = isset($item->ConditionID) ? (string)$item->ConditionID : null;
        $condition['name'] = isset($item->ConditionDisplayName) ? (string)$item->ConditionDisplayName : null;
        $condition['description'] = isset($item->ConditionDescription) ? (string)$item->ConditionDescription : null;

        return $condition;
    }

    /**
     * @param SimpleXMLElement $item
     *
     * @return array
     */
    private function getCategories(SimpleXMLElement $item) {
        $categories = array();

        if (isset($item->PrimaryCategory)) {
            $categories['primary']['id'] = isset($item->PrimaryCategory->CategoryID) ? (string)$item->PrimaryCategory->CategoryID : null;
            $categories['primary']['name'] = isset($item->PrimaryCategory->CategoryName) ? (string)$item->PrimaryCategory->CategoryName : null;
        } else {
            $categories['primary']['id'] = null;
            $categories['primary']['name'] = null;
        }

        if (isset($item->SecondaryCategory)) {
            $categories['secondary']['id'] = isset($item->SecondaryCategory->CategoryID) ? (string)$item->SecondaryCategory->CategoryID : null;
            $categories['secondary']['name'] = isset($item->SecondaryCategory->CategoryName) ? (string)$item->SecondaryCategory->CategoryName : null;
        } else {
            $categories['secondary']['id'] = null;
            $categories['secondary']['name'] = null;
        }

        return $categories;
    }

    /**
     * @param SimpleXMLElement $item
     *
     * @return array
     */
    private function getStore(SimpleXMLElement $item) {
        $store = array();

        if (!isset($item->Storefront)) {

            $store['categories']['primary']['id'] = null;
            $store['categories']['primary']['name'] = null;
            $store['categories']['secondary']['id'] = null;
            $store['categories']['secondary']['name'] = null;

            return $store;
        }

        $store['categories']['primary']['id'] = isset($item->Storefront->StoreCategoryID) ? (string)$item->Storefront->StoreCategoryID : null;
        $store['categories']['primary']['name'] = isset($item->Storefront->StoreCategoryName) ? (string)$item->Storefront->StoreCategoryName : null;
        $store['categories']['secondary']['id'] = isset($item->Storefront->StoreCategory2ID) ? (string)$item->Storefront->StoreCategory2ID : null;
        $store['categories']['secondary']['name'] = isset($item->Storefront->StoreCategory2Name) ? (string)$item->Storefront->StoreCategory2Name : null;
        $store['url'] = isset($item->Storefront->StoreURL) ? (string)$item->Storefront->StoreURL : null;

        return $store;
    }

    /**
     * @param SimpleXMLElement $item
     *
     * @return array
     */
    private function getShipping(SimpleXMLElement $item) {
        $shippingData = array();

        $shippingData['dispatch_time'] = isset($item->DispatchTimeMax) ? (int)$item->DispatchTimeMax : null;
        if (!isset($item->ShippingPackageDetails)) {
            $shippingData['package']['dimensions']['depth'] = null;
            $shippingData['package']['dimensions']['length'] = null;
            $shippingData['package']['dimensions']['width'] = null;
        } else {
            $shippingData['package']['dimensions']['depth'] = isset($item->ShippingPackageDetails->PackageDepth) ? (int)$item->ShippingPackageDetails->PackageDepth : null;
            $shippingData['package']['dimensions']['length'] = isset($item->ShippingPackageDetails->PackageLength) ? (int)$item->ShippingPackageDetails->PackageLength : null;
            $shippingData['package']['dimensions']['width'] = isset($item->ShippingPackageDetails->PackageWidth) ? (int)$item->ShippingPackageDetails->PackageWidth : null;
        }

        if (!isset($item->UnitInfo)) {
            $shippingData['package']['dimensions']['unit_type'] = null;
        } else {
            $shippingData['package']['dimensions']['unit_type'] = isset($item->UnitInfo->UnitType) ? (string)$item->UnitInfo->UnitType : null;
        }

        return $shippingData;
    }

    /**
     * @param SimpleXMLElement $item
     *
     * @return array
     */
    private function getVariations(SimpleXMLElement $item) {

        if (!isset($item->Variations)) {
            return array();
        }

        $variations = array();
        foreach ($item->Variations->Variation as $singleVariation) {

            $tempVariation = array();

            $tempVariation['sku'] = isset($singleVariation->SKU) ? (string)$singleVariation->SKU : null;
            $tempVariation['price'] = isset($singleVariation->StartPrice) ? (float)$singleVariation->StartPrice : null;
            $tempVariation['quantity'] = isset($singleVariation->Quantity) ? (int)$singleVariation->Quantity : null;
            $tempVariation['image_attribute'] = isset($item->Variations->Pictures->VariationSpecificName) ? (string)$item->Variations->Pictures->VariationSpecificName : null;
            $tempVariation['specifics'] = array();
            $tempVariation['images'] = array();
            $tempVariation['details'] = array();

            if (isset($singleVariation->VariationSpecifics) && isset($singleVariation->VariationSpecifics->NameValueList)) {
                foreach ($singleVariation->VariationSpecifics->NameValueList as $singleSpecific) {
                    if (isset($singleSpecific->Name) && strtolower((string)$singleSpecific->Name) == 'mpn') {
                        continue;
                    }

                    $tempVariation['specifics'][(string)$singleSpecific->Name] = isset($singleSpecific->Value) ? (string)$singleSpecific->Value : null;
                }
            }

            if (isset($singleVariation->VariationProductListingDetails)) {

                $tempVariation['details']['ean'] = isset($singleVariation->VariationProductListingDetails->EAN) ? (string)$singleVariation->VariationProductListingDetails->EAN : null;
                $tempVariation['details']['upc'] = isset($singleVariation->VariationProductListingDetails->UPC) ? (string)$singleVariation->VariationProductListingDetails->UPC : null;
                $tempVariation['details']['isbn'] = isset($singleVariation->VariationProductListingDetails->ISBN) ? (string)$singleVariation->VariationProductListingDetails->ISBN : null;
                $tempVariation['details']['epid'] = isset($singleVariation->VariationProductListingDetails->ProductReferenceID) ? (string)$singleVariation->VariationProductListingDetails->ProductReferenceID : null;
            } else {
                $tempVariation['details']['ean'] = null;
                $tempVariation['details']['upc'] = null;
                $tempVariation['details']['isbn'] = null;
                $tempVariation['details']['epid'] = null;
            }

            if (isset($item->Variations->Pictures->VariationSpecificPictureSet)) {
                foreach ($item->Variations->Pictures->VariationSpecificPictureSet as $pictureSet) {
                    if (isset($pictureSet->VariationSpecificValue) &&
                        in_array((string)$pictureSet->VariationSpecificValue, $tempVariation['specifics']) &&
                        isset($pictureSet->PictureURL)) {
                        foreach ($pictureSet->PictureURL as $url) {
                            $tempVariation['images'][] = (string)$url;
                        }
                    }
                }
            }

            $variations[] = $tempVariation;
        }

        return $variations;
    }

    /**
     * @param SimpleXMLElement $item
     *
     * @return array
     */
    private function getSpecifics(SimpleXMLElement $item) {

        if (!isset($item->ItemSpecifics) || empty($item->ItemSpecifics->NameValueList)) {
            return array();
        }

        $specifics = array();
        foreach ($item->ItemSpecifics->NameValueList as $specific) {
            if (!isset($specific->Name)) {
                continue;
            }

            $specifics[(string)$specific->Name] = array(
                'name' => (string)$specific->Name,
                'value' => isset($specific->Value) ? (string)$specific->Value : null,
                'source' => isset($specific->Source) ? (string)$specific->Source : null,
            );
        }

        return $specifics;
    }

    //########################################

    /**
     * @param SimpleXMLElement $item
     *
     * @return array|bool
     */
    public function process(SimpleXMLElement $item) {

        $eBayItemInfo = array();

        //----------------------------------------

        $eBayItemInfo['marketplace_id'] = $this->getMarketplace($item);
        $eBayItemInfo['identifiers'] = $this->getIdentifiers($item);
        $eBayItemInfo['price'] = $this->getPrice($item);
        $eBayItemInfo['qty'] = $this->getQty($item);
        $eBayItemInfo['description'] = $this->getDescription($item);
        $eBayItemInfo['images'] = $this->getImages($item);
        $eBayItemInfo['condition'] = $this->getCondition($item);
        $eBayItemInfo['categories'] = $this->getCategories($item);
        $eBayItemInfo['store'] = $this->getStore($item);
        $eBayItemInfo['shipping'] = $this->getShipping($item);
        $eBayItemInfo['variations'] = $this->getVariations($item);
        $eBayItemInfo['item_specifics'] = $this->getSpecifics($item);

        //----------------------------------------

        if ($eBayItemInfo['format']['type'] === false) {
            return false;
        }

        return $eBayItemInfo;
    }
}
