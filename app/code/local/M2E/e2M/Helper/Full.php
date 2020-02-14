<?php

class M2E_e2M_Helper_Full extends Mage_Core_Helper_Abstract {

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

        $identifiers['item_id'] = (double)$item->ItemID;
        $identifiers['sku'] = (string)$item->SKU;
        $identifiers['ean'] = (string)$item->ProductListingDetails->EAN;
        $identifiers['upc'] = (string)$item->ProductListingDetails->UPC;
        $identifiers['isbn'] = (string)$item->ProductListingDetails->ISBN;
        $identifiers['epid'] = (string)$item->ProductListingDetails->ProductReferenceID;
        $identifiers['brand_mpn']['brand'] = (string)$item->ProductListingDetails->BrandMPN->Brand;
        $identifiers['brand_mpn']['mpn'] = (string)$item->ProductListingDetails->BrandMPN->MPN;

        return $identifiers;
    }

    /**
     * @param SimpleXMLElement $item
     *
     * @return array
     */
    private function getPrice(SimpleXMLElement $item) {
        $price = array();

        $price['currency'] = (string)$item->Currency;
        $price['start'] = (float)$item->StartPrice;
        $price['buy_it_now'] = (float)$item->BuyItNowPrice;
        $price['current'] = (float)$item->SellingStatus->CurrentPrice;
        $price['original'] = (float)$item->SellingStatus->OriginalPrice;
        $price['map']['value'] = (float)$item->DiscountPriceInfo->MinimumAdvertisedPrice;
        $price['map']['exposure'] = (string)$item->DiscountPriceInfo->MinimumAdvertisedPriceExposure;
        $price['stp']['value'] = (float)$item->DiscountPriceInfo->OriginalRetailPrice;

        return $price;
    }

    /**
     * @param SimpleXMLElement $item
     *
     * @return array
     */
    private function getQty(SimpleXMLElement $item) {
        $qty = array();

        $qty['total'] = (int)$item->Quantity;

        return $qty;
    }

    /**
     * @param SimpleXMLElement $item
     *
     * @return array
     */
    private function getDescription(SimpleXMLElement $item) {
        $description = array();

        $description['title'] = (string)$item->Title;
        $description['subtitle'] = (string)$item->SubTitle;
        $description['description'] = (string)$item->Description;

        return $description;
    }

    /**
     * @param SimpleXMLElement $item
     *
     * @return array
     */
    private function getImages(SimpleXMLElement $item) {
        $images = array();

        $images['gallery_type'] = (string)$item->PictureDetails->GalleryType;
        $images['photo_display'] = (string)$item->PictureDetails->PhotoDisplay;
        $images['urls'] = array();
        if ($item->PictureDetails->PictureURL) {
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

        $condition['type'] = (string)$item->ConditionID;
        $condition['name'] = (string)$item->ConditionDisplayName;
        $condition['description'] = (string)$item->ConditionDescription;

        return $condition;
    }

    /**
     * @param SimpleXMLElement $item
     *
     * @return array
     */
    private function getCategories(SimpleXMLElement $item) {
        $categories = array();

        $categories['primary']['id'] = (string)$item->PrimaryCategory->CategoryID;
        $categories['primary']['name'] = (string)$item->PrimaryCategory->CategoryName;

        $categories['secondary']['id'] = (string)$item->SecondaryCategory->CategoryID;
        $categories['secondary']['name'] = (string)$item->SecondaryCategory->CategoryName;

        return $categories;
    }

    /**
     * @param SimpleXMLElement $item
     *
     * @return array
     */
    private function getStore(SimpleXMLElement $item) {
        $store = array();

        $store['categories']['primary']['id'] = (string)$item->Storefront->StoreCategoryID;
        $store['categories']['primary']['name'] = (string)$item->Storefront->StoreCategoryName;
        $store['categories']['secondary']['id'] = (string)$item->Storefront->StoreCategory2ID;
        $store['categories']['secondary']['name'] = (string)$item->Storefront->StoreCategory2Name;
        $store['url'] = (string)$item->Storefront->StoreURL;

        return $store;
    }

    /**
     * @param SimpleXMLElement $item
     *
     * @return array
     */
    private function getShipping(SimpleXMLElement $item) {
        $shippingData = array();

        $shippingData['dispatch_time'] = (int)$item->DispatchTimeMax;
        $shippingData['package']['dimensions']['depth'] = (int)$item->ShippingPackageDetails->PackageDepth;
        $shippingData['package']['dimensions']['length'] = (int)$item->ShippingPackageDetails->PackageLength;
        $shippingData['package']['dimensions']['width'] = (int)$item->ShippingPackageDetails->PackageWidth;
        $shippingData['package']['dimensions']['unit_type'] = (string)$item->UnitInfo->UnitType;

        return $shippingData;
    }

    /**
     * @param SimpleXMLElement $item
     *
     * @return array
     */
    private function getVariations(SimpleXMLElement $item) {

        if (!$item->Variations) {
            return array();
        }

        $variations = array();
        foreach ($item->Variations->Variation as $singleVariation) {

            $tempVariation = array();

            $tempVariation['sku'] = (string)$singleVariation->SKU;
            $tempVariation['price'] = (float)$singleVariation->StartPrice;
            $tempVariation['quantity'] = (int)$singleVariation->Quantity;
            $tempVariation['image_attribute'] = (string)$item->Variations->Pictures->VariationSpecificName;
            $tempVariation['specifics'] = array();
            $tempVariation['images'] = array();
            $tempVariation['details'] = array();

            if ($singleVariation->VariationSpecifics && $singleVariation->VariationSpecifics->NameValueList) {
                foreach ($singleVariation->VariationSpecifics->NameValueList as $singleSpecific) {
                    if (strtolower((string)$singleSpecific->Name) == 'mpn') {
                        continue;
                    }

                    $tempVariation['specifics'][(string)$singleSpecific->Name] = (string)$singleSpecific->Value;
                }
            }

            if ($singleVariation->VariationProductListingDetails->EAN) {
                $tempVariation['details']['ean'] = (string)$singleVariation->VariationProductListingDetails->EAN;
            }
            if ($singleVariation->VariationProductListingDetails->UPC) {
                $tempVariation['details']['upc'] = (string)$singleVariation->VariationProductListingDetails->UPC;
            }
            if ($singleVariation->VariationProductListingDetails->ISBN) {
                $tempVariation['details']['isbn'] = (string)$singleVariation->VariationProductListingDetails->ISBN;
            }
            if ($singleVariation->VariationProductListingDetails->ProductReferenceID) {
                $tempVariation['details']['epid'] = (string)$singleVariation->VariationProductListingDetails->ProductReferenceID;
            }

            if ($item->Variations->Pictures->VariationSpecificPictureSet) {
                foreach ($item->Variations->Pictures->VariationSpecificPictureSet as $pictureSet) {

                    if (in_array((string)$pictureSet->VariationSpecificValue, $tempVariation['specifics']) && $pictureSet->PictureURL) {
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

        if (empty($item->ItemSpecifics->NameValueList)) {
            return array();
        }

        $specifics = array();
        foreach ($item->ItemSpecifics->NameValueList as $specific) {
            $specifics[(string)$specific->Name] = array(
                'name' => (string)$specific->Name,
                'value' => (string)$specific->Value,
                'source' => (string)$specific->Source,
            );
        }

        return $specifics;
    }

    /**
     * @param SimpleXMLElement $item
     *
     * @return array
     */
    private function getCompatibilityList(SimpleXMLElement $item) {
        if (empty($item->ItemCompatibilityList->Compatibility)) {
            return array();
        }

        $list = array();
        foreach ($item->ItemCompatibilityList->Compatibility as $compatibilityRow) {

            $tempRow = array();
            foreach ($compatibilityRow->NameValueList as $compatibilityValue) {

                $name = (string)$compatibilityValue->Name;
                $value = (string)$compatibilityValue->Value;
                if ($name == '' || $value == '') {
                    continue;
                }

                $tempRow[$name] = $value;
            }

            $tempRow['Notes'] = (string)$compatibilityRow->CompatibilityNotes;
            $list[] = $tempRow;
        }

        return $list;
    }

    //########################################

    /**
     * @param SimpleXMLElement $item
     *
     * @return array|bool
     */
    public function parseItem(SimpleXMLElement $item) {
        $eBayItemInfo = array();

        // ----------------------------------------
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
        $eBayItemInfo['compatibility_list'] = $this->getCompatibilityList($item);
        // ----------------------------------------

        if ($eBayItemInfo['format']['type'] === false) {
            return false;
        }

        return $eBayItemInfo;
    }

    //########################################
}
