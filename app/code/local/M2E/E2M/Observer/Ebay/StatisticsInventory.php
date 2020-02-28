<?php

class M2E_E2M_Observer_Ebay_StatisticsInventory {

    public function process() {

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        $resource = Mage::getSingleton('core/resource');

        $connRead = $resource->getConnection('core_read');

        $select = $connRead->select()->from($resource->getTableName('m2e_e2m_inventory_ebay'), 'COUNT(*)');

        //----------------------------------------

        $variationSelect = clone $select;
        $variation = (int)$variationSelect->where('variation = ?', true)->query()->fetchColumn();

        $simpleSelect = clone $select;
        $simple = (int)$simpleSelect->where('variation = ?', false)->query()->fetchColumn();

        $totalSelect = clone $select;
        $total = (int)$totalSelect->query()->fetchColumn();

        $dataHelper->setCacheValue(M2E_E2M_Helper_Data::CACHE_ID_EBAY_INVENTORY_VARIATION_COUNT, $variation);
        $dataHelper->setCacheValue(M2E_E2M_Helper_Data::CACHE_ID_EBAY_INVENTORY_SIMPLE_COUNT, $simple);
        $dataHelper->setCacheValue(M2E_E2M_Helper_Data::CACHE_ID_EBAY_INVENTORY_TOTAL_COUNT, $total);
    }
}
