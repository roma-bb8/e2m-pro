<?php

class M2E_E2M_Model_Cron_Job_Ebay_DownloadInventory {

    const XML_PATH_WORK_DOWNLOAD_INVENTORY = M2E_E2M_Helper_Data::PREFIX . 'job/ebay/download/inventory/work';
    const XML_PATH_PROCESS_DOWNLOAD_INVENTORY = M2E_E2M_Helper_Data::PREFIX . 'job/ebay/download/inventory/process';
    const XML_PATH_FROM_DOWNLOAD_INVENTORY = M2E_E2M_Helper_Data::PREFIX . 'job/ebay/download/inventory/from';
    const XML_PATH_TO_DOWNLOAD_INVENTORY = M2E_E2M_Helper_Data::PREFIX . 'job/ebay/download/inventory/to';

    const PERCENTAGE_COMPLETED = 100;

    const MAX_DOWNLOAD_TIME = 946684800;
    const MAX_REQUESTS = 4;
    const MAX_DAYS = 118;

    //########################################

    /** @var M2E_E2M_Model_Lock_Item */
    private $lockItem;

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
        $percentage > 100 && $percentage = self::PERCENTAGE_COMPLETED;

        return $percentage;
    }

    //########################################

    /**
     * @throws Exception
     */
    public function process() {

        $isWork = (bool)Mage::helper('e2m/Config')->get(self::XML_PATH_WORK_DOWNLOAD_INVENTORY, false);
        if (!$isWork) {
            return;
        }

        if ($this->lockItem->isLocked()) {
            return;
        }

        $this->lockItem->lockAndActivate();

        $dateTimeZone = new DateTimeZone('UTC');
        $dateTimeObj = new DateTime('now', $dateTimeZone);

        $fromDateTime = clone $dateTimeObj;
        $fromDateTime->setTimestamp(Mage::helper('e2m/Config')->get(self::XML_PATH_FROM_DOWNLOAD_INVENTORY));

        $toDateTime = clone $dateTimeObj;
        $toDateTime->setTimestamp(Mage::helper('e2m/Config')->get(self::XML_PATH_TO_DOWNLOAD_INVENTORY));

        $request = 0;
        while (self::MAX_REQUESTS > $request && $fromDateTime->getTimestamp() < $toDateTime->getTimestamp()) {

            $tmpDateTime = clone $fromDateTime;
            $tmpDateTime->modify('+' . self::MAX_DAYS . ' days');

            try {

                $response = Mage::getSingleton('e2m/Proxy_Ebay_Api')->sendRequest(array(
                    'command' => array('inventory', 'get', 'items'),
                    'data' => array(
                        'account' => Mage::getSingleton('e2m/Proxy_Ebay_Account')->getToken(),
                        'realtime' => true,
                        'since_time' => $fromDateTime->format('Y-m-d H:i:s'),
                        'to_time' => $tmpDateTime->format('Y-m-d H:i:s')
                    )
                ));

                if (!isset($response['to_time'])) {
                    // throw new Exception('"To time" not send server api.');
                    $fromDateTime = $tmpDateTime;

                    continue;
                }

                $this->lockItem->activate();

                if (!empty($response['items'])) {

                    $items = array();
                    foreach ($response['items'] as $index => $item) {

                        $fullItemInfo = Mage::getSingleton('e2m/Proxy_Ebay_Api')->sendRequest(array(
                            'command' => array('item', 'get', 'info'),
                            'data' => array(
                                'account' => Mage::getSingleton('e2m/Proxy_Ebay_Account')->getToken(),
                                'item_id' => $item['id'],
                                'parser_type' => 'full',
                            )
                        ));

                        $items[$item['id']] = $fullItemInfo['result'];

                        if ($index % 5 == 0) {
                            $this->lockItem->activate();
                        }
                    }

                    if (!empty($items)) {
                        Mage::getSingleton('e2m/Ebay_Inventory')->process($items);

                        $this->lockItem->activate();
                    }
                }

            } catch (Exception $e) {
                Mage::helper('e2m')->writeExceptionLog($e);

                break;
            }

            /**
             ** Dirty hack **
             *      by pagination responses data
             * app/code/Component/Ebay/Model/Command/Request/Auth/Trading/Paginated/Serial/Abstract.php:16
             * getPaginatedOutputData:40
             */
            is_array($response['to_time']) && $response['to_time'] = array_pop($response['to_time']);

            $toTime = new DateTime($response['to_time'], $dateTimeZone);
            $fromDateTime = $toTime;

            !empty($response['items']) && $request++;

            $this->lockItem->activate();
        }

        $percentage = $this->getProcessAsPercentage($fromDateTime, $toDateTime);
        Mage::helper('e2m/Config')->set(self::XML_PATH_PROCESS_DOWNLOAD_INVENTORY, $percentage);
        if (self::PERCENTAGE_COMPLETED === $percentage) {
            Mage::helper('e2m/Config')->set(self::XML_PATH_WORK_DOWNLOAD_INVENTORY, false);
        }

        Mage::helper('e2m/Config')->set(
            self::XML_PATH_FROM_DOWNLOAD_INVENTORY,
            $fromDateTime->getTimestamp(),
            true
        );

        $this->lockItem->unlock();

        Mage::dispatchEvent('m2e_e2m_cron_task_ebay_inventory_download_after');
    }

    /**
     * M2E_E2M_Model_Cron_Job_Ebay_DownloadInventory constructor.
     */
    public function __construct() {
        $this->lockItem = Mage::getModel('e2m/Lock_Item', array('ebay_download_inventory'));
    }
}
