<?php
/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * Class M2E_E2M_Model_Cron_Task_eBay_DownloadInventory
 */
class M2E_E2M_Model_Cron_Task_Ebay_DownloadInventory implements M2E_E2M_Model_Cron_Task {

    const INSTANCE = 'Cron_Task_Ebay_DownloadInventory';

    const MAX_DOWNLOAD_TIME = 946684800;
    const MAX_REQUESTS = 4;
    const MAX_DAYS = 118;

    //########################################

    /**
     * @param DateTime $from
     * @param DateTime $to
     *
     * @return int
     */
    private function getProcessAsPercentage(DateTime $from, DateTime $to) {

        $fullInterval = $to->getTimestamp() - self::MAX_DOWNLOAD_TIME;
        $downloadInterval = $from->getTimestamp() - self::MAX_DOWNLOAD_TIME;

        $percentage = floor(($downloadInterval / $fullInterval) * 100);
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

        /** @var M2E_E2M_Model_Ebay_Inventory $eBayInventory */
        $eBayInventory = Mage::getSingleton('e2m/Ebay_Inventory');

        $eBayInventory->set(M2E_E2M_Model_Ebay_Inventory::PATH_DOWNLOAD_INVENTORY, true);

        $dataHelper->logReport($taskId, 'Finish task of Downloading Inventory from eBay.');
    }

    //########################################

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function process($taskId, $data) {

        /** @var M2E_E2M_Model_Api_Ebay $eBayAPI */
        $eBayAPI = Mage::getSingleton('e2m/Api_Ebay');

        /** @var M2E_E2M_Model_Ebay_Account $eBayAccount */
        $eBayAccount = Mage::getSingleton('e2m/Ebay_Account');

        /** @var M2E_E2M_Model_Ebay_Config $eBayConfig */
        $eBayConfig = Mage::getSingleton('e2m/Ebay_Config');

        /** @var M2E_E2M_Model_Ebay_Inventory $eBayInventory */
        $eBayInventory = Mage::getSingleton('e2m/Ebay_Inventory');

        $resource = Mage::getSingleton('core/resource');

        $connWrite = $resource->getConnection('core_write');

        $cronTasksInProcessingTableName = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');

        $token = $eBayAccount->get(M2E_E2M_Model_Ebay_Account::TOKEN);
        $mode = $eBayAccount->get(M2E_E2M_Model_Ebay_Account::MODE);

        //----------------------------------------

        if (empty($token)) {
            throw new Exception('eBay Token empty.');
        }

        //----------------------------------------

        $dateTimeObj = new DateTime('now', new DateTimeZone('UTC'));

        $fromDateTime = clone $dateTimeObj;
        $fromDateTime->setTimestamp($data['from']);

        $toDateTime = clone $dateTimeObj;
        $toDateTime->setTimestamp($data['to']);

        //----------------------------------------

        $request = 0;
        while ($request < self::MAX_REQUESTS && $fromDateTime->getTimestamp() < $toDateTime->getTimestamp()) {

            $tmpDateTime = clone $fromDateTime;
            $tmpDateTime->modify('+' . self::MAX_DAYS . ' days');

            $eBayAPI->downloadInventory($mode, $token, $fromDateTime, $tmpDateTime);

            $fromDateTime = $tmpDateTime;
            $request++;
        }

        $process = $this->getProcessAsPercentage($fromDateTime, $toDateTime);

        $data['from'] = $fromDateTime->getTimestamp();

        $connWrite->update($cronTasksInProcessingTableName, array(
            'data' => Mage::helper('core')->jsonEncode($data),
            'progress' => $process
        ), array('instance = ?' => 'Cron_Task_eBay_DownloadInventory'));

        //----------------------------------------

        $eBayInventory->reloadData();
        $eBayConfig->setFull();

        //----------------------------------------

        return array(
            'process' => $process,
            'total' => $eBayInventory->get('items/count/total'),
            'variation' => $eBayInventory->get('items/count/variation'),
            'simple' => $eBayInventory->get('items/count/simple')
        );
    }
}
