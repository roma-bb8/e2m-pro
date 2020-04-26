<?php

class M2E_E2M_Observer_Ebay_DownloadInventory {

    public function process() {

        $resource = Mage::getSingleton('core/resource');
        $sites = $resource->getConnection('core_read')->select()
            ->from($resource->getTableName('m2e_e2m_ebay_items'), 'site')
            ->group('site')->query()->fetchAll(PDO::FETCH_ASSOC);

        $marketplaces = array();
        foreach ($sites as $item) {
            $marketplaces[] = $item['site'];
        }

        Mage::helper('e2m/Config')->set(
            M2E_E2M_Helper_Ebay::XML_PATH_AVAILABLE_MARKETPLACES,
            $marketplaces,
            true
        );
    }
}
