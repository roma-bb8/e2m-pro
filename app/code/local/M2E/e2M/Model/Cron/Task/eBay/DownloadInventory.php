<?php

/**
 * Class M2E_e2M_Model_Cron_Task_eBay_DownloadInventory
 */
class M2E_e2M_Model_Cron_Task_eBay_DownloadInventory implements M2E_e2M_Model_Cron_Task {

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

        $resource = Mage::getSingleton('core/resource');

        $connWrite = $resource->getConnection('core_write');
        $cronTasksInProcessingTableName = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');

        //----------------------------------------

        /** @var M2E_e2M_Helper_eBay_Account $eBayAccount */
        $eBayAccount = Mage::helper('e2m/eBay_Account');
        if (empty($eBayAccount->getToken())) {
            throw new Exception('eBay Token empty.');
        }

        //----------------------------------------

        $dateTimeObj = new DateTime('now', new DateTimeZone('UTC'));

        $fromDateTime = clone $dateTimeObj;
        $fromDateTime->setTimestamp($data['from']);

        $toDateTime = clone $dateTimeObj;
        $toDateTime->setTimestamp($data['to']);

        //----------------------------------------

        /** @var M2E_e2M_Model_Api_Ebay $eBayAPI */
        $eBayAPI = Mage::getModel('e2m/Api_Ebay');

        $request = 0;
        while ($request < self::MAX_REQUESTS && $fromDateTime->getTimestamp() < $toDateTime->getTimestamp()) {

            $tmpDateTime = clone $fromDateTime;
            $tmpDateTime->modify('+' . self::MAX_DAYS . ' days');

            $eBayAPI->downloadInventory(
                $eBayAccount->getMode(),
                $eBayAccount->getToken(),
                $fromDateTime,
                $tmpDateTime
            );

            $fromDateTime = $tmpDateTime;
            $request++;
        }

        $data['from'] = $fromDateTime->getTimestamp();

        $connWrite->update($cronTasksInProcessingTableName, array(
            'data' => Mage::helper('core')->jsonEncode($data)
        ), array('instance = ?' => 'Cron_Task_eBay_DownloadInventory'));

        //----------------------------------------

        /** @var M2E_e2M_Helper_eBay_Inventory $eBayInventory */
        $eBayInventory = Mage::helper('e2m/eBay_Inventory');
        $eBayInventory->reloadData();
        $eBayInventory->save();

        /** @var M2E_e2M_Helper_eBay_Config $eBayConfig */
        $eBayConfig = Mage::helper('e2m/eBay_Config');
        $eBayConfig->setFull();
        $eBayConfig->save();

        $process = $this->getProcessAsPercentage($fromDateTime, $toDateTime);

        /** @var M2E_e2M_Helper_Progress $progressHelper */
        $progressHelper = Mage::helper('e2m/Progress');
        $progressHelper->setProgressByTag(self::TAG, $process);

        //----------------------------------------

        return array(
            'process' => $progressHelper->getProgressByTag(self::TAG),
            'total' => $eBayInventory->getItemsTotal(),
            'variation' => $eBayInventory->getItemsVariation(),
            'simple' => $eBayInventory->getItemsSimple()
        );
    }
}
