<?php
/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * Class M2E_E2M_Model_Cron_Task_Completed
 */
class M2E_E2M_Model_Cron_Task_Completed implements M2E_E2M_Model_Cron_Task {

    /**
     * @inheritDoc
     */
    public function process($taskId, $data) {

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        /** @var M2E_E2M_Helper_Progress $progressHelper */
        $progressHelper = Mage::helper('e2m/Progress');

        $resource = Mage::getSingleton('core/resource');

        $connWrite = $resource->getConnection('core_write');
        $connRead = $resource->getConnection('core_read');

        $cronTasksInProcessingTableName = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');

        //----------------------------------------

        if ($progressHelper->isCompletedProgressByTag(M2E_E2M_Model_Cron_Task_Ebay_DownloadInventory::TAG)) {

            $taskId = $connRead->select()->from($cronTasksInProcessingTableName, array('id'))
                ->where('instance = ?', 'Cron_Task_Ebay_DownloadInventory')->query()->fetchColumn();

            if ($taskId) {
                $connWrite->delete($cronTasksInProcessingTableName, array(
                    'id = ?' => $taskId
                ));

                $dataHelper->logReport($taskId, 'Finish task of Downloading Inventory from eBay.');
            }
        }

        if ($progressHelper->isCompletedProgressByTag(M2E_E2M_Model_Cron_Task_Magento_ImportInventory::TAG)) {

            $taskId = $connRead->select()->from($cronTasksInProcessingTableName, array('id'))
                ->where('instance = ?', 'Cron_Task_Magento_ImportInventory')->query()->fetchColumn();

            if ($taskId) {
                $connWrite->delete($cronTasksInProcessingTableName, array(
                    'id = ?' => $taskId
                ));

                $dataHelper->logReport($taskId, 'Finish task of Import Inventory from Magento.');
            }
        }

        return array();
    }
}
