<?php

/**
 * Class M2E_e2M_Model_Cron_Task_Completed
 */
class M2E_e2M_Model_Cron_Task_Completed implements M2E_e2M_Model_Cron_Task {

    /**
     * @inheritDoc
     */
    public function process($taskId, $data) {

        /** @var M2E_e2M_Helper_Progress $progressHelper */
        $progressHelper = Mage::helper('e2m/Progress');

        $resource = Mage::getSingleton('core/resource');
        $connWrite = $resource->getConnection('core_write');
        $cronTasksInProcessingTableName = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');

        if ($progressHelper->isCompletedProgressByTag(M2E_e2M_Model_Cron_Task_eBay_DownloadInventory::TAG)) {
            $connWrite->delete($cronTasksInProcessingTableName, array(
                'instance = ?' => 'Cron_Task_eBay_DownloadInventory',
                'is_running = ?' => 0
            ));
        }

        if ($progressHelper->isCompletedProgressByTag(M2E_e2M_Model_Cron_Task_Magento_ImportInventory::TAG)) {
            $connWrite->delete($cronTasksInProcessingTableName, array(
                'instance = ?' => 'Cron_Task_Magento_ImportInventory',
                'is_running = ?' => 0
            ));
        }

        return array();
    }
}
