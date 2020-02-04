<?php

class M2E_e2M_Helper_Full extends Mage_Core_Helper_Abstract {

    // ########################################

    const LISTING_STATUS_ACTIVE    = 'Active';
    const LISTING_STATUS_ENDED     = 'Ended';
    const LISTING_STATUS_COMPLETED = 'Completed';

    const FORMAT_TYPE_STANDARD    = 'standard';
    const FORMAT_TYPE_SIMPLE      = 'simple';
    const FORMAT_TYPE_FULL        = 'full';
    const FORMAT_TYPE_CHANGE      = 'change';

    const SHIPPING_TYPE_FLAT       = 'flat';
    const SHIPPING_TYPE_CALCULATED = 'calculated';
    const SHIPPING_TYPE_FREIGHT    = 'freight';
    const SHIPPING_TYPE_LOCAL      = 'local';

    const EBAY_SHIPPING_TYPE_FREIGHT    = 'FreightFlat';
    const EBAY_SHIPPING_TYPE_FLAT       = 'Flat';
    const EBAY_SHIPPING_TYPE_CALCULATED = 'Calculated';
    const EBAY_SHIPPING_TYPE_FLAT_DOMESTIC_CALC_INTERNATIONAL = 'FlatDomesticCalculatedInternational';
    const EBAY_SHIPPING_TYPE_CALC_DOMESTIC_FLAT_INTERNATIONAL = 'CalculatedDomesticFlatInternational';

    const MEASUREMENT_SYSTEM_ENGLISH = 'English';
    const MEASUREMENT_SYSTEM_METRIC  = 'Metric';

    const TYPE_AUCTION = 'Chinese';
    const TYPE_FIXED   = 'FixedPriceItem';

    const BRAND_UNBRANDED    = 'Unbranded';
    const MPN_DOES_NOT_APPLY = 'Does Not Apply';

    const RETURNS_ACCEPTED_OPTION_YES = 'ReturnsAccepted';
    const RETURNS_ACCEPTED_OPTION_NO  = 'ReturnsNotAccepted';


    private $isFullVariationsMode = false;

    // ########################################

    public function setFullVariationsMode($isFull = true)
    {
        $this->isFullVariationsMode = $isFull;
        return $this;
    }

    public function isFullVariationsMode()
    {
        return $this->isFullVariationsMode;
    }

    // ########################################

    protected function getPreparedListingStatus($status, $startTime = NULL, $endTime = NULL)
    {
        if ($status == self::LISTING_STATUS_ACTIVE || is_null($startTime) || is_null($endTime)) {
            return $status;
        }

        $status = self::LISTING_STATUS_COMPLETED;

        $startTime = new DateTime($startTime, new DateTimeZone('UTC'));
        $endTime = new DateTime($endTime, new DateTimeZone('UTC'));

        $dateTimeInterval = $startTime->diff($endTime);
        $availableDaysIntervals = array(1,3,5,7,10,30);

        if ($dateTimeInterval->h == 0 && $dateTimeInterval->i == 0 &&
            in_array($dateTimeInterval->d, $availableDaysIntervals)) {

            $status = self::LISTING_STATUS_ENDED;
        }

        return $status;
    }

    protected function getPreparedListingType($type)
    {
        // sometimes eBay returns this value instead of fixedPriceItem
        if ($type == 'StoresFixedPrice') {
            $type = self::TYPE_FIXED;
        }

        // unknown listing type
        if (!in_array($type,array(self::TYPE_AUCTION, self::TYPE_FIXED))) {
            $type = false;
        }

        return $type;
    }

    // ########################################


    // ########################################

    public function parseItem(SimpleXMLElement $item)
    {
        $eBayItemInfo = array();

        // ----------------------------------------
        $eBayItemInfo['marketplace']        = $this->getMarketplace($item);
        $eBayItemInfo['identifiers']        = $this->getIdentifiers($item);
        $eBayItemInfo['format']             = $this->getFormat($item);
        $eBayItemInfo['price']              = $this->getPrice($item);
        $eBayItemInfo['qty']                = $this->getQty($item);
        $eBayItemInfo['description']        = $this->getDescription($item);
        $eBayItemInfo['images']             = $this->getImages($item);
        $eBayItemInfo['condition']          = $this->getCondition($item);
        $eBayItemInfo['categories']         = $this->getCategories($item);
        $eBayItemInfo['selling']            = $this->getSelling($item);
        $eBayItemInfo['store']              = $this->getStore($item);
        $eBayItemInfo['payment']            = $this->getPayment($item);
        $eBayItemInfo['return']             = $this->getReturn($item);
        $eBayItemInfo['shipping']           = $this->getShipping($item);
        $eBayItemInfo['policies']           = $this->getPolicies($item);
        $eBayItemInfo['other']              = $this->getOther($item);
        $eBayItemInfo['variations']         = $this->getVariations($item);
        $eBayItemInfo['item_specifics']     = $this->getSpecifics($item);
        $eBayItemInfo['compatibility_list'] = $this->getCompatibilityList($item);
        // ----------------------------------------

        if ($eBayItemInfo['format']['type'] === false) {
            return false;
        }

        return $eBayItemInfo;
    }

    // ########################################

    protected function getMarketplace(SimpleXMLElement $item)
    {
        return (string)$item->Site;
    }

    protected function getIdentifiers(SimpleXMLElement $item)
    {
        $identifiers = array();

        // ----------------------------------------
        $identifiers['item_id'] = (double)$item->ItemID;
        $identifiers['sku']     = (string)$item->SKU;

        $identifiers['ean']                = (string)$item->ProductListingDetails->EAN;
        $identifiers['upc']                = (string)$item->ProductListingDetails->UPC;
        $identifiers['epid']               = (string)$item->ProductListingDetails->ProductReferenceID;
        $identifiers['isbn']               = (string)$item->ProductListingDetails->ISBN;
        $identifiers['brand_mpn']['brand'] = (string)$item->ProductListingDetails->BrandMPN->Brand;
        $identifiers['brand_mpn']['mpn']   = (string)$item->ProductListingDetails->BrandMPN->MPN;
        // ----------------------------------------
        return $identifiers;
    }

    protected function getFormat(SimpleXMLElement $item)
    {
        $format = array();
        // ----------------------------------------
        $format['type']       = $this->getPreparedListingType((string)$item->ListingType);
        $format['duration']   = (string)$item->ListingDuration;
        $format['is_private'] = $this->isTrueProperty($item->PrivateListing);
        // ----------------------------------------
        return $format;
    }

    /**
     * Returns 1 if xml property is 'true', otherwise  - 0
     * @param  $element
     * @return int
     */
    public function isTrueProperty($element)
    {
        if (!isset($element)) {
            return 0;
        }

        return (string)$element == 'true' ? 1 : 0;
    }

    protected function getPrice(SimpleXMLElement $item)
    {
        $price = array();
        // ----------------------------------------
        $price['currency']             = (string)$item->Currency;
        $price['start']                = (float)$item->StartPrice;
        $price['reserve']              = (float)$item->ReservePrice;
        $price['buy_it_now']           = (float)$item->BuyItNowPrice;
        $price['current']              = (float)$item->SellingStatus->CurrentPrice;
        $price['map']['value']         = (float)$item->DiscountPriceInfo->MinimumAdvertisedPrice;
        $price['map']['exposure']      = (string)$item->DiscountPriceInfo->MinimumAdvertisedPriceExposure;
        $price['stp']['value']         = (float)$item->DiscountPriceInfo->OriginalRetailPrice;
        $price['stp']['sold_on_ebay']  = $this->isTrueProperty($item->DiscountPriceInfo->SoldOneBay);
        $price['stp']['sold_off_ebay'] = $this->isTrueProperty($item->DiscountPriceInfo->SoldOffeBay);
        // ----------------------------------------
        return $price;
    }

    protected function getQty(SimpleXMLElement $item)
    {
        $qty = array();
        // ----------------------------------------
        $qty['total']        = (int)$item->Quantity;
        $qty['sold']         = (int)$item->SellingStatus->QuantitySold;
        // ----------------------------------------
        return $qty;
    }

    protected function getDescription(SimpleXMLElement $item)
    {
        $description = array();
        // ----------------------------------------
        $description['title']       = (string)$item->Title;
        $description['subtitle']    = (string)$item->SubTitle;
        $description['description'] = (string)$item->Description;

        $description['enhancement'] = array();
        if ($item->ListingEnhancement) {
            foreach ($item->ListingEnhancement as $enhancement) {
                $description['enhancement'][] = (string)$enhancement;
            }
        }
        // ----------------------------------------

        return $description;
    }

    protected function getImages(SimpleXMLElement $item)
    {
        $images = array();
        // ----------------------------------------
        $images['gallery_type']  = (string)$item->PictureDetails->GalleryType;
        $images['photo_display'] = (string)$item->PictureDetails->PhotoDisplay;
        $images['urls']          = array();
        if ($item->PictureDetails->PictureURL) {
            foreach ($item->PictureDetails->PictureURL as $pictureURL) {
                $images['urls'][] = (string)$pictureURL;
            }
        }
        // ----------------------------------------
        return $images;
    }

    protected function getCondition(SimpleXMLElement $item)
    {
        $condition = array();
        // ----------------------------------------
        $condition['type']        = (string)$item->ConditionID;
        $condition['name']        = (string)$item->ConditionDisplayName;
        $condition['description'] = (string)$item->ConditionDescription;
        // ----------------------------------------
        return $condition;
    }

    protected function getCategories(SimpleXMLElement $item)
    {
        $categories = array();
        // ----------------------------------------
        $categories['primary']['id']     = (string)$item->PrimaryCategory->CategoryID;
        $categories['primary']['name']   = (string)$item->PrimaryCategory->CategoryName;
        $categories['secondary']['id']   = (string)$item->SecondaryCategory->CategoryID;
        $categories['secondary']['name'] = (string)$item->SecondaryCategory->CategoryName;
        // ----------------------------------------
        return $categories;
    }

    protected function getSelling(SimpleXMLElement $item)
    {
        $selling = array();
        // ----------------------------------------
        $selling['bid_count'] = (int)$item->SellingStatus->BidCount;

        $startTime = (string)$item->ListingDetails->StartTime;
        $selling['start_time'] = Mage::getModel('m2i/Api_Ebay')->ebayTimeToString($startTime);

        $endTime = (string)$item->ListingDetails->EndTime;
        $selling['end_time'] = Mage::getModel('m2i/Api_Ebay')->ebayTimeToString($endTime);

        $selling['status'] = $this->getPreparedListingStatus((string)$item->SellingStatus->ListingStatus,
                                                              $selling['start_time'], $selling['end_time']);

        $selling['is_tax_table_enabled'] = false;
        if ($item->ShippingDetails->TaxTable) {
            $selling['is_tax_table_enabled'] = true;
        }

        $selling['best_offer']['is_enabled'] = false;
        if ($item->BestOfferDetails->BestOfferEnabled) {
            $selling['best_offer']['is_enabled'] = $this->isTrueProperty($item->BestOfferDetails->BestOfferEnabled);
        }

        $selling['best_offer']['auto_accept_price'] = 0;
        if ($item->ListingDetails->BestOfferAutoAcceptPrice) {
            $selling['best_offer']['auto_accept_price'] = (float)$item->ListingDetails->BestOfferAutoAcceptPrice;
        }

        $selling['best_offer']['min_price'] = 0;
        if ($item->ListingDetails->MinimumBestOfferPrice) {
            $selling['best_offer']['min_price'] = (float)$item->ListingDetails->MinimumBestOfferPrice;
        }

        $selling['vat_percent'] = (float)$item->VATDetails->VATPercent;
        // ----------------------------------------

        return $selling;
    }

    protected function getStore(SimpleXMLElement $item)
    {
        $store = array();
        // ----------------------------------------
        $store['categories']['primary']['id']     = (string)$item->Storefront->StoreCategoryID;
        $store['categories']['primary']['name']   = (string)$item->Storefront->StoreCategoryName;
        $store['categories']['secondary']['id']   = (string)$item->Storefront->StoreCategory2ID;
        $store['categories']['secondary']['name'] = (string)$item->Storefront->StoreCategory2Name;
        $store['url']                             = (string)$item->Storefront->StoreURL;
        // ----------------------------------------
        return $store;
    }

    protected function getPayment(SimpleXMLElement $item)
    {
        $payment = array();
        // ----------------------------------------
        $payment['methods'] = array();
        if ($item->PaymentMethods) {
            foreach ($item->PaymentMethods as $paymentMethod) {
                $payment['methods'][] = (string)$paymentMethod;
            }
        }
        $payment['paypal']['email']             = (string)$item->PayPalEmailAddress;
        $payment['paypal']['immediate_payment'] = $this->isTrueProperty($item->AutoPay);
        // ----------------------------------------
        return $payment;
    }

    protected function getReturn(SimpleXMLElement $item)
    {
        $return = array();

        // ----------------------------------------
        $return['accepted']       = (string)$item->ReturnPolicy->ReturnsAcceptedOption;
        $return['option']         = (string)$item->ReturnPolicy->RefundOption;
        $return['within']         = (string)$item->ReturnPolicy->ReturnsWithinOption;
        $return['shipping_cost']  = (string)$item->ReturnPolicy->ShippingCostPaidByOption;
        $return['description'] = (string)$item->ReturnPolicy->Description;
        // ----------------------------------------

        // ----------------------------------------
        $return['international']['accepted']      = (string)$item->ReturnPolicy->InternationalReturnsAcceptedOption;
        $return['international']['option']        = (string)$item->ReturnPolicy->InternationalRefundOption;
        $return['international']['within']        = (string)$item->ReturnPolicy->InternationalReturnsWithinOption;
        $return['international']['shipping_cost'] = (string)$item->ReturnPolicy->InternationalShippingCostPaidByOption;
        // ----------------------------------------

        return $return;
    }

    protected function getShipping(SimpleXMLElement $item)
    {
        $shippingData = array();

        // ----------------------------------------

        $shippingData['address']                   = (string)$item->Location;
        $shippingData['country']                   = (string)$item->Country;
        $shippingData['postal_code']               = (string)$item->PostalCode;
        $shippingData['dispatch_time']             = (int)$item->DispatchTimeMax;
        $shippingData['cash_on_delivery_cost']     = (float)$item->ShippingDetails->CODCost;
        $shippingData['global_shipping_program']   = $this->isTrueProperty($item->ShippingDetails->GlobalShipping);
        $shippingData['click_and_collect_enabled'] = $this->isTrueProperty($item->PickupInStoreDetails->EligibleForPickupDropOff);
        $shippingData['pickup_in_store_enabled'] = $this->isTrueProperty($item->PickupInStoreDetails->EligibleForPickupInStore);

        $shippingData['cross_border_trade']      = array();
        if ($item->CrossBorderTrade) {
            foreach ($item->CrossBorderTrade as $crossBorderTrade) {
                $shippingData['cross_border_trade'][] = (string)$crossBorderTrade;
            }
        }

        $shippingData['rate_table_details']['domestic_rate_table'] =
            (string)$item->ShippingDetails->RateTableDetails->DomesticRateTable;
        $shippingData['rate_table_details']['international_rate_table'] =
            (string)$item->ShippingDetails->RateTableDetails->InternationalRateTable;

        $shippingData['locations'] = array();
        if ($item->ShipToLocations) {
            foreach ($item->ShipToLocations as $shipToLocation) {
                $shippingData['locations'][] = (string)$shipToLocation;
            }
        }

        $shippingData['excluded_locations'] = array();
        if ($item->ShippingDetails->ExcludeShipToLocation) {
            foreach ($item->ShippingDetails->ExcludeShipToLocation as $excludedLocation) {
                $shippingData['excluded_locations'][] = (string)$excludedLocation;
            }
        }

        $shippingData['local']         = $this->getLocalShipping($item);
        $shippingData['international'] = $this->getInternationalShipping($item);

        $shippingData['package']['measurement_system']   = (string)$item->ShippingPackageDetails->WeightMajor['measurementSystem'];
        $shippingData['package']['package']              = (string)$item->ShippingPackageDetails->ShippingPackage;
        $shippingData['package']['weight']['major']      = (int)$item->ShippingPackageDetails->WeightMajor;
        $shippingData['package']['weight']['minor']      = (int)$item->ShippingPackageDetails->WeightMinor;
        $shippingData['package']['dimensions']['depth']  = (int)$item->ShippingPackageDetails->PackageDepth;
        $shippingData['package']['dimensions']['length'] = (int)$item->ShippingPackageDetails->PackageLength;
        $shippingData['package']['dimensions']['width']  = (int)$item->ShippingPackageDetails->PackageWidth;

        // ----------------------------------------

        return $shippingData;
    }

    protected function getLocalShipping(SimpleXMLElement $item)
    {
        $localShipping = array(
            'type'                => $this->getLocalShippingType((string)$item->ShippingDetails->ShippingType),
            'discount_enabled'    => $this->isTrueProperty($item->ShippingDetails->PromotionalShippingDiscount),
            'discount_profile_id' => (int)$item->ShippingDetails->ShippingDiscountProfileID,
            'handling_cost'       => (float)$item->ShippingDetails->CalculatedShippingRate->PackagingHandlingCosts
        );

        if ($item->ShippingDetails->ShippingServiceOptions) {

            foreach ($item->ShippingDetails->ShippingServiceOptions as $shippingMethod) {

                $method = array();
                $method['service']         = (string)$shippingMethod->ShippingService;
                $method['cost']            = (float)$shippingMethod->ShippingServiceCost;
                $method['cost_additional'] = (float)$shippingMethod->ShippingServiceAdditionalCost;
                $method['cost_surcharge']  = (float)$shippingMethod->ShippingSurcharge;
                $method['is_free']         = $this->isTrueProperty($shippingMethod->FreeShipping);
                $method['priority']        = (string)$shippingMethod->ShippingServicePriority;

                $localShipping['methods'][] = $method;
            }
        }

        return $localShipping;
    }

    protected function getInternationalShipping(SimpleXMLElement $item)
    {
        $internationalShipping = array(
            'type'                => $this->getInternationalShippingType((string)$item->ShippingDetails->ShippingType),
            'discount_enabled'    => $this->isTrueProperty($item->ShippingDetails->InternationalPromotionalShippingDiscount),
            'discount_profile_id' => (int)$item->ShippingDetails->InternationalShippingDiscountProfileID,
            'handling_cost'       => (float)$item->ShippingDetails->CalculatedShippingRate->InternationalPackagingHandlingCosts
        );

        $shipToLocations = array();

        if ($item->ShipToLocations) {
            foreach ($item->ShipToLocations as $shipToLocation) {

                $tempLocation = (string)$shipToLocation;
                $myCountry    = (string)$item->Country;

                if ($tempLocation == $myCountry) {
                    continue;
                }

                $shipToLocations[] = $tempLocation;
            }
        }

        if (count($shipToLocations) <= 0) {
            $internationalShipping['type'] = NULL;
        }

        if ($item->ShippingDetails->InternationalShippingServiceOption) {

            foreach ($item->ShippingDetails->InternationalShippingServiceOption as $shippingMethod) {

                $method = array();
                $method['service']          = (string)$shippingMethod->ShippingService;
                $method['cost']             = (float)$shippingMethod->ShippingServiceCost;
                $method['cost_additional']  = (float)$shippingMethod->ShippingServiceAdditionalCost;
                $method['cost_surcharge']   = (float)$shippingMethod->ShippingSurcharge;
                $method['priority']         = (string)$shippingMethod->ShippingServicePriority;

                if ($shippingMethod->ShipToLocation) {
                    foreach ($shippingMethod->ShipToLocation as $shipToLocation) {
                        $method['locations'][] = (string) $shipToLocation;
                    }
                }

                $internationalShipping['methods'][] = $method;
            }
        }

        return $internationalShipping;
    }

    protected function getPolicies(SimpleXMLElement $item)
    {
        $policies = array();
        // ----------------------------------------
        $policies['shipping']['id']   = (string)$item->SellerProfiles->SellerShippingProfile->ShippingProfileID;
        $policies['shipping']['name'] = (string)$item->SellerProfiles->SellerShippingProfile->ShippingProfileName;
        $policies['payment']['id']    = (string)$item->SellerProfiles->SellerPaymentProfile->PaymentProfileID;
        $policies['payment']['name']  = (string)$item->SellerProfiles->SellerPaymentProfile->PaymentProfileName;
        $policies['return']['id']     = (string)$item->SellerProfiles->SellerReturnProfile->ReturnProfileID;
        $policies['return']['name']   = (string)$item->SellerProfiles->SellerReturnProfile->ReturnProfileName;
        // ----------------------------------------
        return $policies;
    }

    protected function getOther(SimpleXMLElement $item)
    {
        $other = array();
        // ----------------------------------------
        $other['application_data']        = (string)$item->ApplicationData;
        $other['is_revised']              = (string)$item->ReviseStatus->ItemRevised;
        $other['hit_counter']             = (string)$item->HitCounter;
        $other['url']                     = (string)$item->ListingDetails->ViewItemURL;
        $other['originating_postal_code'] = (string)$item->ShippingDetails->CalculatedShippingRate->OriginatingPostalCode;
        //$other['is_best_offer']         = (string)$item->SellerProfiles->SellerPaymentProfile->PaymentProfileID;
        $other['charity']['id']           = (string)$item->Charity->CharityID;
        $other['charity']['percent']      = (string)$item->Charity->DonationPercent;
        if ($item->ListingEnhancement) {
            foreach ($item->ListingEnhancement as $listingEnhancement) {
                $other['enhancements'][] = (string)$listingEnhancement;
            }
        }
        // ----------------------------------------
        return $other;
    }

    protected function getVariations(SimpleXMLElement $item)
    {
        if (!$item->Variations) {
            return array();
        }

        $variations = array();
        foreach ($item->Variations->Variation as $singleVariation) {

            $tempVariation = array();

            $tempVariation['sku']             = (string)$singleVariation->SKU;
            $tempVariation['price']           = (float)$singleVariation->StartPrice;
            $tempVariation['quantity']        = (int)$singleVariation->Quantity;
            $tempVariation['quantity_sold']   = (int)$singleVariation->SellingStatus->QuantitySold;
            $tempVariation['image_attribute'] = (string)$item->Variations->Pictures->VariationSpecificName;
            $tempVariation['specifics']       = array();
            $tempVariation['images']          = array();
            $tempVariation['details']         = array();

            if ($singleVariation->VariationSpecifics && $singleVariation->VariationSpecifics->NameValueList) {
                foreach ($singleVariation->VariationSpecifics->NameValueList as $singleSpecific) {

                    // mpn is considered as fake specific (it is hidden by eBay on frontend)
                    if (strtolower((string)$singleSpecific->Name) == 'mpn' && !$this->isFullVariationsMode()) {
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

    protected function getSpecifics(SimpleXMLElement $item)
    {
        if (empty($item->ItemSpecifics->NameValueList)) {
            return array();
        }

        $specifics = array();
        foreach ($item->ItemSpecifics->NameValueList as $specific) {
            $specifics[(string)$specific->Name] = array(
                'name'   => (string)$specific->Name,
                'value'  => (string)$specific->Value,
                'source' => (string)$specific->Source,
            );
        }

        return $specifics;
    }

    protected function getCompatibilityList(SimpleXMLElement $item)
    {
        if (empty($item->ItemCompatibilityList->Compatibility)) {
            return array();
        }

        $list = array();

        foreach ($item->ItemCompatibilityList->Compatibility as $compatibilityRow) {

            $tempRow = array();
            foreach ($compatibilityRow->NameValueList as $compatibilityValue) {

                $name  = (string)$compatibilityValue->Name;
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

    // ########################################

    protected function getLocalShippingType($type)
    {
        if ($type == self::EBAY_SHIPPING_TYPE_FREIGHT) {
            return self::SHIPPING_TYPE_FREIGHT;
        }

        if ($type == self::EBAY_SHIPPING_TYPE_FLAT) {
            return self::SHIPPING_TYPE_FLAT;
        }

        if ($type == self::EBAY_SHIPPING_TYPE_CALCULATED) {
            return self::SHIPPING_TYPE_CALCULATED;
        }

        if ($type == self::EBAY_SHIPPING_TYPE_FLAT_DOMESTIC_CALC_INTERNATIONAL) {
            return self::SHIPPING_TYPE_FLAT;
        }

        if ($type == self::EBAY_SHIPPING_TYPE_CALC_DOMESTIC_FLAT_INTERNATIONAL) {
            return self::SHIPPING_TYPE_CALCULATED;
        }

        return self::SHIPPING_TYPE_LOCAL;
    }

    protected function getInternationalShippingType($type)
    {
        if ($type == self::EBAY_SHIPPING_TYPE_FLAT) {
            return self::SHIPPING_TYPE_FLAT;
        }

        if ($type == self::EBAY_SHIPPING_TYPE_CALCULATED) {
            return self::SHIPPING_TYPE_CALCULATED;
        }

        if ($type == self::EBAY_SHIPPING_TYPE_FLAT_DOMESTIC_CALC_INTERNATIONAL) {
            return self::SHIPPING_TYPE_CALCULATED;
        }

        if ($type == self::EBAY_SHIPPING_TYPE_CALC_DOMESTIC_FLAT_INTERNATIONAL) {
            return self::SHIPPING_TYPE_FLAT;
        }

        return NULL;
    }

    // ########################################
}
