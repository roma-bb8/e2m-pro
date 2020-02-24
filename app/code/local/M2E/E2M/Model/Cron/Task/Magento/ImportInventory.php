<?php
/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * Class M2E_E2M_Model_Cron_Task_Magento_ImportInventory
 */
class M2E_E2M_Model_Cron_Task_Magento_ImportInventory implements M2E_E2M_Model_Cron_Task {

    const INSTANCE = 'Cron_Task_Magento_ImportInventory';

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

        $dataHelper->logReport($taskId, 'Finish task of Import Inventory from Magento.');
    }

    //########################################

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function process($taskId, $data) {

        /** @var M2E_E2M_Model_Product_Magento_Configurable $productMagentoConfigurable */
        $productMagentoConfigurable = Mage::getModel('e2m/Product_Magento_Configurable');

        /** @var M2E_E2M_Model_Product_Magento_Simple $productMagentoSimple */
        $productMagentoSimple = Mage::getModel('e2m/Product_Magento_Simple');

        /** @var M2E_E2M_Model_Ebay_Config $eBayConfig */
        $eBayConfig = Mage::getModel('e2m/Ebay_Config');

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        $coreHelper = Mage::helper('core');

        $resource = Mage::getSingleton('core/resource');

        $connWrite = $resource->getConnection('core_write');
        $connRead = $resource->getConnection('core_read');

        $inventoryTableName = $resource->getTableName('m2e_e2m_inventory_ebay');
        $cronTasksInProcessingTableName = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');

        //----------------------------------------

        $productMagentoConfigurable->setTaskId($taskId);
        $productMagentoSimple->setTaskId($taskId);

        //----------------------------------------

        $query = $connRead->select()->from($inventoryTableName)
            ->where('id > ?', $data['last_import_id'])->limit(self::MAX_LIMIT)->query();
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {

            $rowData = $coreHelper->jsonDecode($row['data']);
            if ($eBayConfig->isSkipStore($rowData['marketplace_id'])) {
                $dataHelper->logReport(
                    $row['id'], 'Skip marketplace eBay item: ' . $rowData['identifiers_item_id'],
                    M2E_E2M_Helper_Data::TYPE_REPORT_WARNING
                );

                continue;
            }

            (bool)$row['variation'] ? $productMagentoConfigurable->process($rowData)
                : $productMagentoSimple->process($rowData);

            $data['last_import_id'] = $row['id'];

            $connWrite->update($cronTasksInProcessingTableName, array(
                'data' => Mage::helper('core')->jsonEncode($data)
            ), array('instance = ?' => 'Cron_Task_Magento_ImportInventory'));
        }

        //----------------------------------------

        $process = $this->getProcessAsPercentage($data['last_import_id']);

        $connWrite->update($cronTasksInProcessingTableName, array(
            'progress' => $process
        ), array('instance = ?' => 'Cron_Task_Magento_ImportInventory'));

        //----------------------------------------

        return array(
            'process' => $process
        );
    }
}
