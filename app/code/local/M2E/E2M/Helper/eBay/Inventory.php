<?php

/**
 * Class M2E_E2M_Helper_eBay_Inventory
 */
class M2E_E2M_Helper_eBay_Inventory {

    const PREFIX = M2E_E2M_Helper_Data::PREFIX . 'inventory/';

    const PATH_ITEMS_COUNT_TOTAL = 'items/count/total';
    const PATH_ITEMS_COUNT_VARIATION = 'items/count/variation';
    const PATH_ITEMS_COUNT_SIMPLE = 'items/count/simple';
    const PATH_MARKETPLACES = 'marketplaces';

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

    //########################################

    private $marketplaceTitles = array(
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_CUSTOM_CODE_ID => self::MARKETPLACE_CUSTOM_CODE_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_AU_ID => self::MARKETPLACE_AU_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_AT_ID => self::MARKETPLACE_AT_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_BE_DU_ID => self::MARKETPLACE_BE_DU_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_BE_FR_ID => self::MARKETPLACE_BE_FR_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_CA_ID => self::MARKETPLACE_CA_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_CA_FR_ID => self::MARKETPLACE_CA_FR_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_MOTORS_ID => self::MARKETPLACE_MOTORS_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_FR_ID => self::MARKETPLACE_FR_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_DE_ID => self::MARKETPLACE_DE_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_HK_ID => self::MARKETPLACE_HK_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_IN_ID => self::MARKETPLACE_IN_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_IE_ID => self::MARKETPLACE_IE_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_IT_ID => self::MARKETPLACE_IT_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_MY_ID => self::MARKETPLACE_MY_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_NL_ID => self::MARKETPLACE_NL_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_PH_ID => self::MARKETPLACE_PH_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_PL_ID => self::MARKETPLACE_PL_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_RU_ID => self::MARKETPLACE_RU_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_SG_ID => self::MARKETPLACE_SG_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_SP_ID => self::MARKETPLACE_SP_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_CH_ID => self::MARKETPLACE_CH_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_UK_ID => self::MARKETPLACE_UK_TITLE,
        M2E_E2M_Model_Parser_eBay_Item::MARKETPLACE_US_ID => self::MARKETPLACE_US_TITLE
    );

    private $eBayFields = array(
        'identifiers_item_id' => 'Item ID',
        'identifiers_sku' => 'SKU',
        'identifiers_ean' => 'EAN',
        'identifiers_upc' => 'UPC',
        'identifiers_isbn' => 'ISBN',
        'identifiers_epid' => 'EPID',
        'identifiers_brand_mpn_mpn' => '(Brand) MPN',
        'identifiers_brand_mpn_brand' => '(Brand) Brand',

        'marketplace_id' => '(Site) Marketplace ID',
        'categories_primary_id' => '(Category) Primary ID',
        'categories_secondary_id' => '(Category) Secondary ID',
        'store_categories_primary_id' => '(Store) Category ID',
        'store_categories_secondary_id' => '(Store) Category 2 ID',

        'description_title' => 'Title',
        'description_subtitle' => 'SubTitle',
        'description_description' => 'Description',

        'price_start' => 'Start Price',
        'price_current' => 'Current Price',
        'price_buy_it_now' => 'Buy It Now Price',
        'price_original' => 'Original Price',
        'price_map_value' => '(DPI) Minimum Advertised Price',
        'price_map_exposure' => '(DPI) Minimum Advertised Price Exposure',
        'price_stp_value' => '(Discount Price Info) Original Retail Price',

        'qty_total' => 'Quantity',

        'shipping_dispatch_time' => 'Dispatch Time',
        'shipping_package_dimensions_depth' => '(Dimensions) Depth',
        'shipping_package_dimensions_length' => '(Dimensions) Length',
        'shipping_package_dimensions_width' => '(Dimensions) Width',
        'shipping_package_dimensions_unit_type' => 'Unit Type',

        'condition_type' => 'Condition ID'
    );

    /** @var Mage_Core_Model_Resource $resource */
    private $resource;

    /** @var string $coreConfigDataTableName */
    private $coreConfigDataTableName;

    /** @var string $inventoryTableName */
    private $inventoryTableName;

    /** @var Mage_Core_Helper_Data */
    private $coreHelper;

    /** @var array $inventory */
    private $inventory = array();

    //########################################

    /**
     * @return array
     */
    public function getEbayFields() {
        return $this->eBayFields;
    }

    /**
     * @param int $marketplaceId
     *
     * @return string
     */
    public function getMarketplaceTitle($marketplaceId) {
        return $this->marketplaceTitles[$marketplaceId];
    }

    /**
     * @return int
     */
    public function getItemsTotal() {
        return $this->inventory['items']['count']['total'];
    }

    /**
     * @return int
     */
    public function getItemsVariation() {
        return $this->inventory['items']['count']['variation'];
    }

    /**
     * @return int
     */
    public function getItemsSimple() {
        return $this->inventory['items']['count']['simple'];
    }

    /**
     * @return string[]
     */
    public function getMarketplaces() {
        return $this->inventory['marketplaces'];
    }

    //########################################

    public function reloadData() {

        $connRead = $this->resource->getConnection('core_read');

        $this->inventory['items']['count']['total'] = (int)$connRead->select()
            ->from($this->inventoryTableName, 'COUNT(*)')
            ->query()->fetchColumn();

        $this->inventory['items']['count']['variation'] = (int)$connRead->select()
            ->from($this->inventoryTableName, 'COUNT(*)')
            ->where('variation = ?', true)->query()->fetchColumn();

        $this->inventory['items']['count']['simple'] = (int)$connRead->select()
            ->from($this->inventoryTableName, 'COUNT(*)')
            ->where('variation = ?', false)->query()->fetchColumn();

        $this->inventory['marketplaces'] = array();
        foreach ($connRead->select()->from($this->inventoryTableName, 'marketplace_id')
                     ->group('marketplace_id')->query()->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->inventory['marketplaces'][] = $row['marketplace_id'];
        }
    }

    //########################################

    /**
     * @return $this
     */
    public function save() {

        $connWrite = $this->resource->getConnection('core_write');

        $connWrite->delete($this->coreConfigDataTableName, array('path IN (?)' => array(
            self::PREFIX . self::PATH_ITEMS_COUNT_TOTAL,
            self::PREFIX . self::PATH_ITEMS_COUNT_VARIATION,
            self::PREFIX . self::PATH_ITEMS_COUNT_SIMPLE,
            self::PREFIX . self::PATH_MARKETPLACES
        )));

        $connWrite->insertMultiple($this->coreConfigDataTableName, array(
            array(
                'path' => self::PREFIX . self::PATH_ITEMS_COUNT_TOTAL,
                'value' => $this->inventory['items']['count']['total']
            ),
            array(
                'path' => self::PREFIX . self::PATH_ITEMS_COUNT_VARIATION,
                'value' => $this->inventory['items']['count']['variation']
            ),
            array(
                'path' => self::PREFIX . self::PATH_ITEMS_COUNT_SIMPLE,
                'value' => $this->inventory['items']['count']['simple']
            ),
            array(
                'path' => self::PREFIX . self::PATH_MARKETPLACES,
                'value' => $this->coreHelper->jsonEncode($this->inventory['marketplaces'])
            )
        ));

        return $this;
    }

    /**
     * M2E_E2M_Helper_eBay_Inventory constructor.
     */
    public function __construct() {

        $this->coreHelper = Mage::helper('core');
        $this->resource = Mage::getSingleton('core/resource');
        $this->coreConfigDataTableName = $this->resource->getTableName('core_config_data');
        $this->inventoryTableName = $this->resource->getTableName('m2e_e2m_inventory_ebay');

        //----------------------------------------

        $this->inventory = array(
            'items' => array(
                'count' => array(
                    'total' => 0,
                    'variation' => 0,
                    'simple' => 0
                )
            ),
            'marketplaces' => array()
        );

        //----------------------------------------

        $rows = $this->resource->getConnection('core_read')->select()
            ->from($this->coreConfigDataTableName)
            ->where('path IN (?)', array(
                self::PREFIX . self::PATH_ITEMS_COUNT_TOTAL,
                self::PREFIX . self::PATH_ITEMS_COUNT_VARIATION,
                self::PREFIX . self::PATH_ITEMS_COUNT_SIMPLE,
                self::PREFIX . self::PATH_MARKETPLACES
            ))
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            switch ($row['path']) {
                case self::PREFIX . self::PATH_ITEMS_COUNT_TOTAL:
                    $this->inventory['items']['count']['total'] = (int)$row['value'];
                    break;
                case self::PREFIX . self::PATH_ITEMS_COUNT_VARIATION:
                    $this->inventory['items']['count']['variation'] = (int)$row['value'];
                    break;
                case self::PREFIX . self::PATH_ITEMS_COUNT_SIMPLE:
                    $this->inventory['items']['count']['simple'] = (int)$row['value'];
                    break;
                case self::PREFIX . self::PATH_MARKETPLACES:
                    $this->inventory['marketplaces'] = $this->coreHelper->jsonDecode($row['value']);
                    break;
            }
        }
    }
}
