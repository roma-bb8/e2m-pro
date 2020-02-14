<?php

class M2E_e2M_Model_Cron_Task_Magento_ImportInventory implements M2E_e2M_Model_Cron_Task {

    const MAX_LIMIT = 100;

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

        /** @var M2E_e2M_Model_Product_Magento_Builder $productMagentoBuilder */
        $productMagentoBuilder = Mage::getModel('e2m/Product_Magento_Builder');
        $query = $connRead->select()
            ->from($inventoryTableName)
            ->where('id > ?', $data['last_import_id'])
            ->limit(self::MAX_LIMIT)
            ->query();

        $processLastImportId = $data['last_import_id'];
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $rowData = $coreHelper->jsonDecode($row['data']);

            if (false === (bool)$row['variation']) {
                $product = $productMagentoBuilder->buildProduct($rowData);
                $update = empty($product->getId());
                $product->getResource()->save($product);
                if ($productMagentoBuilder->isImportQty() &&
                    ($productMagentoBuilder->isActionFoundIgnore() && $update)) {
                    $productMagentoBuilder->importQty(
                        $product,
                        $rowData['qty']
                    );
                }
            } else {
                $productMagentoBuilder->buildConfigurableProduct($rowData);
            }

            $processLastImportId = $row['id'];
        }

        //----------------------------------------

        $data['last_import_id'] = $processLastImportId;

        $connWrite->update($cronTasksInProcessingTableName, array(
            'data' => Mage::helper('core')->jsonEncode($data)
        ), array('instance = ?' => 'Cron_Task_Magento_ImportInventory'));

        //----------------------------------------

        $process = $this->getProcessAsPercentage($processLastImportId);

        /** @var M2E_e2M_Helper_Progress $progressHelper */
        $progressHelper = Mage::helper('e2m/Progress');
        $progressHelper->setProgressByTag(
            M2E_e2M_Helper_Data::MAGENTO_IMPORT_INVENTORY,
            $process
        );

        //----------------------------------------

        return array(
            'process' => $progressHelper->getProgressByTag(M2E_e2M_Helper_Data::MAGENTO_IMPORT_INVENTORY)
        );
    }
}
