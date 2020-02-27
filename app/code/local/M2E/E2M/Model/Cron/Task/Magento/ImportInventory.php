<?php

class M2E_E2M_Model_Cron_Task_Magento_ImportInventory implements M2E_E2M_Model_Cron_Task {

    const CACHE_ID = M2E_E2M_Helper_Data::PREFIX . self::class;

    const MAX_LIMIT = 20;

    //########################################

    /**
     * @param int $lastImportId
     *
     * @return int
     */
    private function getProcessAsPercentage($lastImportId) {

        $resource = Mage::getSingleton('core/resource');
        $connRead = $resource->getConnection('core_read');
        $inventoryTableName = $resource->getTableName('m2e_e2m_inventory_ebay');
        $total = $connRead->select()->from($inventoryTableName, 'COUNT(*)')->query()->fetchColumn();

        $percentage = floor(100 / ($total / $lastImportId));
        $percentage > 100 && $percentage = M2E_E2M_Model_Cron_Task_Completed::COMPLETED;

        return $percentage;
    }

    //########################################

    /**
     * @inheritDoc
     */
    public function completed($taskId, $data) {

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        $dataHelper->setConfig(M2E_E2M_Helper_Data::XML_PATH_EBAY_IMPORT_INVENTORY, true);

        $dataHelper->logReport($taskId, 'Finish task of Import Inventory from Magento.');
    }

    //########################################

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function process($taskId, $data) {

        /** @var Mage_Core_Helper_Data $coreHelper */
        $coreHelper = Mage::helper('core');

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        /** @var M2E_E2M_Helper_Ebay_Config $eBayConfigHelper */
        $eBayConfigHelper = Mage::helper('e2m/Ebay_Config');

        /** @var M2E_E2M_Model_Product_Magento_Configurable $productMagentoConfigurable */
        $productMagentoConfigurable = Mage::getModel('e2m/Product_Magento_Configurable');

        /** @var M2E_E2M_Model_Product_Magento_Simple $productMagentoSimple */
        $productMagentoSimple = Mage::getModel('e2m/Product_Magento_Simple');

        $resource = Mage::getSingleton('core/resource');

        $connWrite = $resource->getConnection('core_write');
        $connRead = $resource->getConnection('core_read');

        $inventoryTableName = $resource->getTableName('m2e_e2m_inventory_ebay');
        $cronTasksTableName = $resource->getTableName('m2e_e2m_cron_tasks');

        //----------------------------------------

        $productMagentoConfigurable->setTaskId($taskId);
        $productMagentoSimple->setTaskId($taskId);

        //----------------------------------------

        $query = $connRead->select()->from($inventoryTableName)
            ->where('id > ?', $data['last_import_id'])->limit(self::MAX_LIMIT)->query();
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {

            $rowData = $coreHelper->jsonDecode($row['data']);
            if ($eBayConfigHelper->isSkipStore($rowData['marketplace_id'])) {
                $dataHelper->logReport($taskId,
                    'Skip marketplace eBay item: ' . $rowData['identifiers_item_id'],
                    M2E_E2M_Helper_Data::TYPE_REPORT_WARNING
                );

                continue;
            }

            (bool)$row['variation'] ? $productMagentoConfigurable->process($rowData)
                : $productMagentoSimple->process($rowData);

            $data['last_import_id'] = $row['id'];

            $connWrite->update($cronTasksTableName, array(
                'data' => Mage::helper('core')->jsonEncode($data)
            ), array('instance = ?' => self::class));
        }

        //----------------------------------------

        $process = $this->getProcessAsPercentage($data['last_import_id']);
        $dataHelper->setCacheValue(self::CACHE_ID, $process);

        //----------------------------------------

        $connWrite->update($cronTasksTableName, array(
            'progress' => $dataHelper->getCacheValue(self::CACHE_ID)
        ), array('instance = ?' => self::class));

        //----------------------------------------

        return array(
            'process' => $dataHelper->getCacheValue(self::CACHE_ID, 0)
        );
    }
}
