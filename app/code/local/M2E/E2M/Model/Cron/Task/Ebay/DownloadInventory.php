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

    const TAG = 'ebay/download/inventory';

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
        $percentage > 100 && $percentage = 100;

        return $percentage;
    }

    //########################################

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function process($taskId, $data) {

        /** @var M2E_E2M_Model_Api_Ebay $eBayAPI */
        $eBayAPI = Mage::getSingleton('e2m/Api_Ebay');

        /** @var M2E_E2M_Model_Ebay_Config $eBayConfig */
        $eBayConfig = Mage::getSingleton('e2m/Ebay_Config');

        /** @var M2E_E2M_Model_Ebay_Inventory $eBayInventory */
        $eBayInventory = Mage::getSingleton('e2m/Ebay_Inventory');

        /** @var M2E_e2M_Helper_Progress $progressHelper */
        $progressHelper = Mage::helper('e2m/Progress');

        $resource = Mage::getSingleton('core/resource');

        $connWrite = $resource->getConnection('core_write');

        $cronTasksInProcessingTableName = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');

        $token = Mage::getStoreConfig(
            M2E_E2M_Model_Ebay_Account::PREFIX . '/' . M2E_E2M_Model_Ebay_Account::TOKEN . '/'
        );
        $mode = Mage::getStoreConfig(
            M2E_E2M_Model_Ebay_Account::PREFIX . '/' . M2E_E2M_Model_Ebay_Account::MODE . '/'
        );

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

        $data['from'] = $fromDateTime->getTimestamp();

        $connWrite->update($cronTasksInProcessingTableName, array(
            'data' => Mage::helper('core')->jsonEncode($data)
        ), array('instance = ?' => 'Cron_Task_eBay_DownloadInventory'));

        //----------------------------------------

        $eBayInventory->reloadData();

        $eBayConfig->setFull();

        $process = $this->getProcessAsPercentage($fromDateTime, $toDateTime);

        $progressHelper->setProgressByTag(self::TAG, $process);

        //----------------------------------------

        return array(
            'process' => $progressHelper->getProgressByTag(self::TAG),
            'total' => $eBayInventory->get('items/count/total'),
            'variation' => $eBayInventory->get('items/count/variation'),
            'simple' => $eBayInventory->get('items/count/simple')
        );
    }
}
