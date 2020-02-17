<?php

/**
 * Class M2E_e2M_Model_Cron_Task_Magento_ImportInventory
 */
class M2E_e2M_Model_Cron_Task_Magento_ImportInventory implements M2E_e2M_Model_Cron_Task {

    const TAG = 'magento/import/inventory';

    const MAX_LIMIT = 500;

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
        $percentage > 100 && $percentage = 100;

        return $percentage;
    }

    //########################################

    /**
     * @param array $data
     *
     * @return array
     * @throws Zend_Db_Statement_Exception
     * @throws Exception
     */
    public function process($data) {

        $coreHelper = Mage::helper('core');
        $resource = Mage::getSingleton('core/resource');

        $connRead = $resource->getConnection('core_read');
        $connWrite = $resource->getConnection('core_write');
        $inventoryTableName = $resource->getTableName('m2e_e2m_inventory_ebay');
        $cronTasksInProcessingTableName = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');

        //----------------------------------------

        /** @var M2E_e2M_Model_Product_Magento_Configurable $productMagentoConfigurable */
        $productMagentoConfigurable = Mage::getModel('e2m/Product_Magento_Configurable');

        /** @var M2E_e2M_Model_Product_Magento_Simple $productMagentoSimple */
        $productMagentoSimple = Mage::getModel('e2m/Product_Magento_Simple');

        $query = $connRead->select()
            ->from($inventoryTableName)
            ->where('id > ?', $data['last_import_id'])
            ->limit(self::MAX_LIMIT)
            ->query();

        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $rowData = $coreHelper->jsonDecode($row['data']);
            (bool)$row['variation'] ? $productMagentoConfigurable->process($rowData)
                : $productMagentoSimple->process($rowData);

            $data['last_import_id'] = $row['id'];
            $connWrite->update($cronTasksInProcessingTableName, array(
                'data' => Mage::helper('core')->jsonEncode($data)
            ), array('instance = ?' => 'Cron_Task_Magento_ImportInventory'));
        }

        $process = $this->getProcessAsPercentage($data['last_import_id']);

        /** @var M2E_e2M_Helper_Progress $progressHelper */
        $progressHelper = Mage::helper('e2m/Progress');
        $progressHelper->setProgressByTag(self::TAG, $process);

        //----------------------------------------

        return array(
            'process' => $progressHelper->getProgressByTag(self::TAG)
        );
    }
}
