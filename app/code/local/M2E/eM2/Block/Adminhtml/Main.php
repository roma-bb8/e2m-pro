<?php

/*
Step 1.5
Map:
gtin : upc -> ean

еще одна настройка если нашел
update no action


summary
сколько вариационных
сколько симплов

defaul setup value

export import configs
*/
class M2E_e2M_Block_Adminhtml_Main extends Mage_Adminhtml_Block_Widget_Form {

    public function __construct() {
        parent::__construct();
        $this->setTemplate('m2i/main.phtml');
    }

    protected function _beforeToHtml() {

        $data = array(
            'label' => Mage::helper('m2i')->__('Get Token'),
            'onclick' => 'getToken();',
            'class' => 'get_token_button'
        );
        $buttonBlock = $this->getLayout()->createBlock('adminhtml/widget_button')->setData($data);
        $this->setChild('get_token_button', $buttonBlock);

        $resource = Mage::getSingleton('core/resource');
        $connWrite = $resource->getConnection('core_write');
        $coreConfigDataTableName = $resource->getTableName('core_config_data');
        $result = $connWrite->select()
            ->from($coreConfigDataTableName, array('value', 'path'))
            ->where('scope = ?', 'default')
            ->where('scope_id = ?', 0)
            ->where('path LIKE ?', M2e_M2i_Adminhtml_M2iController::CONFIG_PATH . '%')
            ->query();

        while ($row = $result->fetch()) {
            $data = Mage::registry($row['path']);
            if (empty($data)) {
                Mage::register($row['path'], $row['value']);
            } else {
                Mage::unregister($row['path']);
                Mage::register($row['path'], $row['value']);
            }
        }

        $data = array(
            'label' => Mage::helper('m2i')->__('Delete token'),
            'onclick' => 'deleteToken();',
            'class' => 'delete_token_button'
        );
        $buttonBlock = $this->getLayout()->createBlock('adminhtml/widget_button')->setData($data);
        $this->setChild('delete_token_button', $buttonBlock);


        $data = array(
            'label' => Mage::helper('m2i')->__('Start download inventory'),
            'onclick' => 'startDownloadInventory();',
            'class' => 'start_download_inventory_button'
        );
        $buttonBlock = $this->getLayout()->createBlock('adminhtml/widget_button')->setData($data);
        $this->setChild('start_download_inventory_button', $buttonBlock);


        $data = array(
            'label' => Mage::helper('m2i')->__('Map relation product'),
            'onclick' => 'mapRelationProduct();',
            'class' => 'map_relation_product_button'
        );
        $buttonBlock = $this->getLayout()->createBlock('adminhtml/widget_button')->setData($data);
        $this->setChild('map_relation_product_button', $buttonBlock);

        $data = array(
            'label' => Mage::helper('m2i')->__('Start download inventory'),
            'onclick' => 'startDownloadInventory();',
            'class' => 'start_download_inventory_button'
        );
        $buttonBlock = $this->getLayout()->createBlock('adminhtml/widget_button')->setData($data);
        $this->setChild('start_download_inventory_button', $buttonBlock);

        $stores = array();
        foreach (Mage::app()->getStores(true) as $store) {
            /** @var Mage_Core_Model_Store $store */
            $stores[$store->getId()] = $store->getName();
        }

        Mage::unregister(M2e_M2i_Adminhtml_M2iController::CONFIG_PATH . 'stores');
        Mage::register(M2e_M2i_Adminhtml_M2iController::CONFIG_PATH . 'stores', $stores);

        $m2eM2IInventory = $resource->getTableName('m2e_m2i_inventory');
        $marketplaces = array_column($connWrite->select()
            ->from($m2eM2IInventory, array('site'))
            ->group('site')
            ->query()
            ->fetchAll(), 'site');

        Mage::unregister(M2e_M2i_Adminhtml_M2iController::CONFIG_PATH . 'marketplaces');
        Mage::register(M2e_M2i_Adminhtml_M2iController::CONFIG_PATH . 'marketplaces', $marketplaces);

        $data = array(
            'label' => Mage::helper('m2i')->__('Map'),
            'onclick' => 'mappingMarketplacesStores();',
            'class' => 'mapping_marketplaces_stores_button'
        );
        $buttonBlock = $this->getLayout()->createBlock('adminhtml/widget_button')->setData($data);
        $this->setChild('mapping_marketplaces_stores_button', $buttonBlock);

        $resource = Mage::getSingleton('core/resource');
        $connWrite = $resource->getConnection('core_write');
        $coreConfigDataTableName = $resource->getTableName('core_config_data');
        $maps = $connWrite->select()
            ->from($coreConfigDataTableName, 'value')
            ->where('scope = ?', 'default')
            ->where('scope_id = ?', 0)
            ->where('path = ?', M2e_M2i_Adminhtml_M2iController::CONFIG_PATH . 'maps')
            ->query()->fetchColumn();

        Mage::unregister(M2e_M2i_Adminhtml_M2iController::CONFIG_PATH . 'map_attributes');
        Mage::register(M2e_M2i_Adminhtml_M2iController::CONFIG_PATH . 'map_attributes', $maps);

        $data = array(
            'label' => Mage::helper('m2i')->__('Map'),
            'onclick' => 'mappingMangetoToeBayAtt();',
            'class' => 'mapping_mangeto_to_eBay_att_button'
        );
        $buttonBlock = $this->getLayout()->createBlock('adminhtml/widget_button')->setData($data);
        $this->setChild('mapping_mangeto_to_eBay_att_button', $buttonBlock);


        $data = array(
            'label' => Mage::helper('m2i')->__('Start import inventory'),
            'onclick' => 'startImportInventory();',
            'class' => 'start_import_inventory_button'
        );
        $buttonBlock = $this->getLayout()->createBlock('adminhtml/widget_button')->setData($data);
        $this->setChild('start_import_inventory_button', $buttonBlock);
    }
}
