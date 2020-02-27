<?php

class M2E_E2M_Model_Cron_Task_Ebay_DownloadInventory implements M2E_E2M_Model_Cron_Task {

    const CACHE_ID = M2E_E2M_Helper_Data::PREFIX . self::class;

    //########################################

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

        $dataHelper->setConfig(M2E_E2M_Helper_Data::XML_PATH_EBAY_DOWNLOAD_INVENTORY, true, true);

        $dataHelper->logReport($taskId, $dataHelper->__('Finish task of Downloading Inventory from eBay.'));

        Mage::dispatchEvent('m2e_e2m_cron_task_ebay_inventory_download_completed');
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

        /** @var M2E_E2M_Model_Proxy_Ebay_Account $eBayAccount */
        $eBayAccount = Mage::getSingleton('e2m/Proxy_Ebay_Account');

        /** @var M2E_E2M_Model_Proxy_Ebay_Api $eBayApi */
        $eBayApi = Mage::getSingleton('e2m/Proxy_Ebay_Api');

        /** @var M2E_E2M_Model_Adapter_Ebay_Item $eBayItemAdapter */
        $eBayItemAdapter = Mage::getSingleton('e2m/Adapter_Ebay_Item');

        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');

        $connWrite = $resource->getConnection('core_write');
        $connRead = $resource->getConnection('core_read');

        $inventoryTableName = $resource->getTableName('m2e_e2m_inventory_ebay');
        $cronTasksTableName = $resource->getTableName('m2e_e2m_cron_tasks');

        //----------------------------------------

        $dateTimeZone = new DateTimeZone('UTC');
        $dateTimeObj = new DateTime('now', $dateTimeZone);

        $fromDateTime = clone $dateTimeObj;
        $fromDateTime->setTimestamp($data['from']);

        $toDateTime = clone $dateTimeObj;
        $toDateTime->setTimestamp($data['to']);

        //----------------------------------------

        $request = 0;
        while ($request < self::MAX_REQUESTS && $fromDateTime->getTimestamp() < $toDateTime->getTimestamp()) {

            $tmpDateTime = clone $fromDateTime;
            $tmpDateTime->modify('+' . self::MAX_DAYS . ' days');

            //----------------------------------------

            $response = $eBayApi->sendRequest(array(
                'command' => array('inventory', 'get', 'items'),
                'data' => array(
                    'account' => $eBayAccount->getToken(),
                    'realtime' => true,
                    'since_time' => $fromDateTime->format('Y-m-d H:i:s'),
                    'to_time' => $tmpDateTime->format('Y-m-d H:i:s'),
                    'format_type' => 'full'
                )
            ));

            //----------------------------------------

            $items = array();
            foreach ($response['items'] as $item) {
                $item = $eBayItemAdapter->process($item);
                $items[$item['identifiers_item_id']] = $item;
            }

            //----------------------------------------

            $itemIDs = array();
            $rows = $connRead->select()->from($inventoryTableName, 'item_id')
                ->where('item_id IN (?)', array_keys($items))->query()->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $itemIDs[] = $row['item_id'];
            }

            foreach ($itemIDs as $itemId) {
                $connWrite->update($inventoryTableName, array(
                    'data' => $coreHelper->jsonEncode($items[$itemId])
                ), array('item_id = ?' => $itemId));

                unset($items[$itemId]);
            }

            //----------------------------------------

            foreach ($items as $item => $data) {
                $connWrite->insert($inventoryTableName, array(
                    'marketplace_id' => $data['marketplace_id'],
                    'item_id' => $item,
                    'variation' => !empty($data['variations']),
                    'data' => $coreHelper->jsonEncode($data)
                ));
            }

            //----------------------------------------

            /**
             ** Dirty hack **
             *      by pagination responses data
             * app/code/Component/Ebay/Model/Command/Request/Auth/Trading/Paginated/Serial/Abstract.php:16
             * getPaginatedOutputData:40
             */
            is_array($response['to_time']) && $response['to_time'] = array_pop($response['to_time']);

            $toTime = new DateTime($response['to_time'], $dateTimeZone);
            $fromDateTime = $toTime;
            $request++;
        }

        $process = $this->getProcessAsPercentage($fromDateTime, $toDateTime);
        $dataHelper->setCacheValue(self::CACHE_ID, $process);

        //----------------------------------------

        $data['from'] = $fromDateTime->getTimestamp();

        $connWrite->update($cronTasksTableName, array(
            'data' => $coreHelper->jsonEncode($data),
            'progress' => $dataHelper->getCacheValue(self::CACHE_ID)
        ), array('instance = ?' => self::class));

        //----------------------------------------

        Mage::dispatchEvent('m2e_e2m_cron_task_ebay_inventory_download_after');

        //----------------------------------------

        return array(
            'process' => $dataHelper->getCacheValue(self::CACHE_ID, 0),
            'total' => $dataHelper->getCacheValue(M2E_E2M_Helper_Data::CACHE_ID_EBAY_INVENTORY_TOTAL_COUNT, 0),
            'variation' => $dataHelper->getCacheValue(M2E_E2M_Helper_Data::CACHE_ID_EBAY_INVENTORY_VARIATION_COUNT, 0),
            'simple' => $dataHelper->getCacheValue(M2E_E2M_Helper_Data::CACHE_ID_EBAY_INVENTORY_SIMPLE_COUNT, 0)
        );
    }
}
