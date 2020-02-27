<?php

class M2E_E2M_Observer_Ebay_StatisticsInventory {

    public function process() {

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        $resource = Mage::getSingleton('core/resource');

        $connRead = $resource->getConnection('core_read');

        $inventoryEbayTableName = $resource->getTableName('m2e_e2m_inventory_ebay');

        $select = $connRead->select()->from($inventoryEbayTableName, 'COUNT(*)');

        //----------------------------------------

        $variation = (int)(clone $select)->where('variation = ?', true)->query()->fetchColumn();
        $simple = (int)(clone $select)->where('variation = ?', false)->query()->fetchColumn();
        $total = (int)(clone $select)->query()->fetchColumn();

        //----------------------------------------

        $dataHelper->setCacheValue(M2E_E2M_Helper_Data::CACHE_ID_EBAY_INVENTORY_VARIATION_COUNT, $variation);
        $dataHelper->setCacheValue(M2E_E2M_Helper_Data::CACHE_ID_EBAY_INVENTORY_SIMPLE_COUNT, $simple);
        $dataHelper->setCacheValue(M2E_E2M_Helper_Data::CACHE_ID_EBAY_INVENTORY_TOTAL_COUNT, $total);
    }
}
