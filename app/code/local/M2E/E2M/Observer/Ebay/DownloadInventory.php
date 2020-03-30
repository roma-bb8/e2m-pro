<?php

class M2E_E2M_Observer_Ebay_DownloadInventory {

    public function process() {

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        $resource = Mage::getSingleton('core/resource');

        $connRead = $resource->getConnection('core_read');

        $inventoryEbayTableName = $resource->getTableName('m2e_e2m_ebay_items');

        //----------------------------------------

        $marketplaces = array();
        foreach ($connRead->select()->from($inventoryEbayTableName, 'site')
                     ->group('site')->distinct()->query()->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $marketplaces[] = $row['site'];
        }

        //----------------------------------------

        $dataHelper->setConfig(M2E_E2M_Helper_Data::XML_PATH_EBAY_AVAILABLE_MARKETPLACES, $marketplaces, true);

        Mage::dispatchEvent('m2e_e2m_available_marketplaces');
    }
}
