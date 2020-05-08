<?php

class M2E_E2M_Observer_Ebay_StatisticsInventory {

    public function process() {

        $readConn = Mage::getSingleton('core/resource')->getConnection('core_read');

        $sql = <<<SQL
SELECT COUNT(`id`)
FROM `m2e_e2m_ebay_items`;
SQL;
        $total = (int)$readConn->query($sql)->fetchColumn();

        $sql = <<<SQL
SELECT COUNT(`m2e_e2m_ebay_items`.`id`)
FROM `m2e_e2m_ebay_items`
         LEFT JOIN `m2e_e2m_ebay_item_variations` on
    `m2e_e2m_ebay_items`.`id` = `m2e_e2m_ebay_item_variations`.`item_id`
WHERE `m2e_e2m_ebay_item_variations`.`item_id` IS NULL;
SQL;
        $simple = (int)$readConn->query($sql)->fetchColumn();

        $sql = <<<SQL
SELECT COUNT(DISTINCT `item_id`)
FROM `m2e_e2m_ebay_item_variations`;
SQL;
        $variation = (int)$readConn->query($sql)->fetchColumn();

        Mage::helper('e2m/Config')->set(M2E_E2M_Helper_Ebay::XML_PATH_INVENTORY_VARIATION_COUNT, $variation);
        Mage::helper('e2m/Config')->set(M2E_E2M_Helper_Ebay::XML_PATH_INVENTORY_SIMPLE_COUNT, $simple);
        Mage::helper('e2m/Config')->set(M2E_E2M_Helper_Ebay::XML_PATH_INVENTORY_TOTAL_COUNT, $total);
    }
}
