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

        /** @var M2E_E2M_Helper_Progress $progressHelper */
        $progressHelper = Mage::helper('e2m/Progress');

        $resource = Mage::getSingleton('core/resource');
        $connWrite = $resource->getConnection('core_write');
        $cronTasksInProcessingTableName = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');

        if ($progressHelper->isCompletedProgressByTag(M2E_E2M_Model_Cron_Task_eBay_DownloadInventory::TAG)) {
            $id = $connWrite->delete($cronTasksInProcessingTableName, array(
                'instance = ?' => 'Cron_Task_eBay_DownloadInventory',
                'is_running = ?' => 0
            ));

            if ($id >= 1) {
                Mage::helper('e2m')->logReport($id, 'Finish task of Downloading Inventory from eBay.',
                    M2E_E2M_Helper_Data::TYPE_REPORT_SUCCESS
                );
            }
        }

        if ($progressHelper->isCompletedProgressByTag(M2E_E2M_Model_Cron_Task_Magento_ImportInventory::TAG)) {
            $id = $connWrite->delete($cronTasksInProcessingTableName, array(
                'instance = ?' => 'Cron_Task_Magento_ImportInventory',
                'is_running = ?' => 0
            ));
            if ($id >= 1) {

                Mage::helper('e2m')->logReport($id, 'Finish task of Import Inventory from Magento.',
                    M2E_E2M_Helper_Data::TYPE_REPORT_SUCCESS
                );
            }

        }

        return array();
    }
}
