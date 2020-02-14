<?php

class M2E_e2M_Helper_eBay_Inventory {

    const PREFIX = M2E_e2M_Helper_Data::PREFIX . 'inventory/';

    const PATH_ITEMS_COUNT_TOTAL = 'items/count/total';
    const PATH_ITEMS_COUNT_VARIATION = 'items/count/variation';
    const PATH_ITEMS_COUNT_SIMPLE = 'items/count/simple';
    const PATH_MARKETPLACES = 'marketplaces';

    //########################################

    /** @var Mage_Core_Model_Resource $resource */
    private $resource;

    /** @var string $coreConfigDataTableName */
    private $coreConfigDataTableName;

    /** @var string $inventoryTableName */
    private $inventoryTableName;

    /** @var Mage_Core_Helper_Data */
    private $coreHelper;

    //########################################

    private $inventory = array();

    //########################################

    /**
     * @return int
     */
    public function getItemsTotal() {
        return (int)$this->inventory['items']['count']['total'];
    }

    /**
     * @return int
     */
    public function getItemsVariation() {
        return (int)$this->inventory['items']['count']['variation'];
    }

    /**
     * @return int
     */
    public function getItemsSimple() {
        return (int)$this->inventory['items']['count']['simple'];
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

        $this->inventory['items']['count']['total'] = $connRead->select()
            ->from($this->inventoryTableName, 'COUNT(*)')
            ->query()->fetchColumn();

        $this->inventory['items']['count']['variation'] = $connRead->select()
            ->from($this->inventoryTableName, 'COUNT(*)')
            ->where('variation = ?', true)->query()->fetchColumn();

        $this->inventory['items']['count']['simple'] = $connRead->select()
            ->from($this->inventoryTableName, 'COUNT(*)')
            ->where('variation = ?', false)->query()->fetchColumn();

        $this->inventory['marketplaces'] = array();
        foreach ($connRead->select()->from($this->inventoryTableName, 'marketplace_id')
                     ->group('marketplace_id')->query()->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->inventory['marketplaces'][] = $row['marketplace_id'];
        }
    }

    //########################################

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
                    continue;
                case self::PREFIX . self::PATH_ITEMS_COUNT_VARIATION:
                    $this->inventory['items']['count']['variation'] = (int)$row['value'];
                    continue;
                case self::PREFIX . self::PATH_ITEMS_COUNT_SIMPLE:
                    $this->inventory['items']['count']['simple'] = (int)$row['value'];
                    continue;
                case self::PREFIX . self::PATH_MARKETPLACES:
                    $this->inventory['marketplaces'] = $this->coreHelper->jsonDecode($row['value']);
                    continue;
            }
        }
    }
}
