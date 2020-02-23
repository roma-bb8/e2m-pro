<?php
/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * Class M2E_E2M_Model_Ebay_Config
 */
class M2E_E2M_Model_Ebay_Config extends M2E_E2M_Model_Config {

    const PREFIX = parent::PREFIX . '/settings';

    //########################################

    const PATH_MARKETPLACE_TO_STORE_MAP = 'marketplaces/stores/map';
    const PATH_INVENTORY_PRODUCT_IDENTIFIER = 'inventory/product_identifier';
    const PATH_INVENTORY_ACTION_FOUND = 'inventory/action_found';
    const PATH_PRODUCT_IMPORT_QTY = 'product/import/qty';
    const PATH_PRODUCT_GENERATE_SKU = 'product/generate_sku';
    const PATH_PRODUCT_IMPORT_IMAGE = 'product/import/image';
    const PATH_PRODUCT_DELETE_HTML = 'product/delete_html';
    const PATH_PRODUCT_ATTRIBUTE_SET = 'product/attribute_set';
    const PATH_PRODUCT_FIELDS_ATTRIBUTES_MAP = 'product/fields_attributes/map';
    const PATH_FULL_SETTINGS = 'full';

    const VALUE_SKU_PRODUCT_IDENTIFIER = 'SKU';
    const VALUE_MPN_PRODUCT_IDENTIFIER = 'MPN';
    const VALUE_EAN_PRODUCT_IDENTIFIER = 'EAN';
    const VALUE_UPC_PRODUCT_IDENTIFIER = 'UPC';
    const VALUE_GTIN_PRODUCT_IDENTIFIER = 'GTIN';

    const VALUE_IGNORE_ACTION_FOUND = 'IGNORE';
    const VALUE_UPDATE_ACTION_FOUND = 'UPDATE';

    const SKIP = -1;

    //########################################

    /**
     * @param bool $autoSave
     *
     * @throws Exception
     */
    public function setFull($autoSave = true) {

        /** @var M2E_E2M_Model_Ebay_Inventory $eBayInventory */
        $eBayInventory = Mage::getSingleton('e2m/Ebay_Inventory');

        $productIdentifier = $eBayInventory->get(self::PATH_INVENTORY_PRODUCT_IDENTIFIER);
        $actionFound = $eBayInventory->get(self::PATH_INVENTORY_ACTION_FOUND);
        $attributeSet = $eBayInventory->get(self::PATH_PRODUCT_ATTRIBUTE_SET);
        $importImage = $eBayInventory->get(self::PATH_PRODUCT_IMPORT_IMAGE);
        $importQty = $eBayInventory->get(self::PATH_PRODUCT_IMPORT_QTY);
        $generateSku = $eBayInventory->get(self::PATH_PRODUCT_GENERATE_SKU);
        $deleteHtml = $eBayInventory->get(self::PATH_PRODUCT_DELETE_HTML);
        $marketplacesStores = $eBayInventory->get(self::PATH_MARKETPLACE_TO_STORE_MAP);
        $fieldsAttributes = $eBayInventory->get(self::PATH_PRODUCT_FIELDS_ATTRIBUTES_MAP);
        $is = count($marketplacesStores) ===
            count($eBayInventory->get(M2E_E2M_Model_Ebay_Inventory::PATH_MARKETPLACES));

        $full = isset($productIdentifier) && isset($actionFound) &&
            isset($attributeSet) && isset($importImage) && isset($importQty) &&
            isset($generateSku) && isset($deleteHtml) && isset($deleteHtml) && $is && !empty($fieldsAttributes);

        $eBayInventory->set(self::PATH_FULL_SETTINGS, $full, $autoSave);
    }

    //########################################

    /**
     * @param int $marketplaceId
     *
     * @return int
     */
    public function getStoreForMarketplace($marketplaceId) {

        /** @var M2E_E2M_Model_Ebay_Inventory $eBayInventory */
        $eBayInventory = Mage::getSingleton('e2m/Ebay_Inventory');

        $marketplacesStores = $eBayInventory->get(M2E_E2M_Model_Ebay_Inventory::PATH_MARKETPLACES);
        if (isset($marketplacesStores[$marketplaceId])) {
            return (int)$marketplacesStores[$marketplaceId];
        }

        return Mage_Core_Model_App::ADMIN_STORE_ID;
    }

    /**
     * @param int $marketplaceId
     *
     * @return int
     */
    public function isSkipStore($marketplaceId) {
        return self::SKIP === $this->getStoreForMarketplace($marketplaceId);
    }
}