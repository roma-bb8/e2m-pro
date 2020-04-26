<?php

class M2E_E2M_Observer_Ebay_StatisticsInventory {

    public function process() {

        $resource = Mage::getSingleton('core/resource');
        $readConn = $resource->getConnection('core_read');
        $itemsTableName = $resource->getTableName('m2e_e2m_ebay_items');
        $itemVariationsTableName = $resource->getTableName('m2e_e2m_ebay_item_variations');

        //----------------------------------------

        $total = (int)$readConn->select()->from($itemsTableName, 'COUNT(*)')->query()->fetchColumn();

        $simple = (int)$readConn->select()->from(array('i' => $itemsTableName), 'COUNT(*)')
            ->join(array('iv' => $itemVariationsTableName), 'i.id = iv.item_id')
            ->where('iv.item_id IS NULL')
            ->group('i.ebay_item_id')
            ->query()->fetchColumn();

        $variation = (int)$readConn->select()->from(array('i' => $itemsTableName), 'COUNT(*)')
            ->join(array('iv' => $itemVariationsTableName), 'i.id = iv.item_id')
            ->where('iv.item_id IS NOT NULL')
            ->group('i.ebay_item_id')
            ->query()->fetchColumn();

        Mage::helper('e2m/Config')->set(M2E_E2M_Helper_Ebay::XML_PATH_INVENTORY_VARIATION_COUNT, $variation);
        Mage::helper('e2m/Config')->set(M2E_E2M_Helper_Ebay::XML_PATH_INVENTORY_SIMPLE_COUNT, $simple);
        Mage::helper('e2m/Config')->set(M2E_E2M_Helper_Ebay::XML_PATH_INVENTORY_TOTAL_COUNT, $total, true);
    }
}
