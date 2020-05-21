<?php

class M2E_E2M_Model_Cron_Job_Ebay_DownloadInventory {

    const XML_PATH_WORK_DOWNLOAD_INVENTORY = M2E_E2M_Helper_Data::PREFIX . 'job/ebay/download/inventory/work';
    const XML_PATH_PROCESS_DOWNLOAD_INVENTORY = M2E_E2M_Helper_Data::PREFIX . 'job/ebay/download/inventory/process';
    const XML_PATH_FROM_DOWNLOAD_INVENTORY = M2E_E2M_Helper_Data::PREFIX . 'job/ebay/download/inventory/from';
    const XML_PATH_TO_DOWNLOAD_INVENTORY = M2E_E2M_Helper_Data::PREFIX . 'job/ebay/download/inventory/to';

    const PERCENTAGE_COMPLETED = 100;

    const MAX_REQUESTS = 4;
    const MAX_SUB_REQUESTS = 3;

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

        $percentage = floor(($from->getTimestamp() / $to->getTimestamp()) * 100);
        $percentage > 100 && $percentage = self::PERCENTAGE_COMPLETED;

        return (int)$percentage;
    }

    //########################################

    /**
     * @throws Exception
     */
    public function process() {

        if (!(bool)Mage::helper('e2m/Config')->get(self::XML_PATH_WORK_DOWNLOAD_INVENTORY, false)) {
            return;
        }

        if ($this->lockItem->isLocked()) {
            return;
        }

        if (!$this->lockItem->lockAndActivate()) {
            Mage::helper('e2m')->writeExceptionLog(new Exception('Lock item not Locked.'));

            return;
        }

        $dateTimeZone = new DateTimeZone('UTC');
        $dateTimeObj = new DateTime('now', $dateTimeZone);

        $fromDateTime = clone $dateTimeObj;
        $fromDateTime->setTimestamp(Mage::helper('e2m/Config')->get(self::XML_PATH_FROM_DOWNLOAD_INVENTORY));

        $toDateTime = clone $dateTimeObj;
        $toDateTime->setTimestamp(Mage::helper('e2m/Config')->get(self::XML_PATH_TO_DOWNLOAD_INVENTORY));

        $request = 0;
        while (self::MAX_REQUESTS > $request && $fromDateTime->getTimestamp() < $toDateTime->getTimestamp()) {

            $this->lockItem->activate();

            $response = Mage::getSingleton('e2m/Proxy_Ebay_Api')->sendRequest(array(
                'command' => array('inventory', 'get', 'items'),
                'data' => array(
                    'account' => Mage::getSingleton('e2m/Proxy_Ebay_Account')->getToken(),
                    'realtime' => true,
                    'since_time' => $fromDateTime->format('Y-m-d H:i:s')
                )
            ));

            $this->lockItem->activate();

            if (empty($response['items'])) {
                $fromDateTime->modify('+1 month');

                continue;
            }

            if (is_array($response['to_time'])) {
                $nextSinceTime = array();
                foreach ($response['to_time'] as $tempToTime) {
                    $nextSinceTime[] = strtotime($tempToTime);
                }
                sort($nextSinceTime, SORT_NUMERIC);
                $nextSinceTime = array_pop($nextSinceTime);
                $nextSinceTime = date('Y-m-d H:i:s', $nextSinceTime);
            } else {
                $nextSinceTime = $response['to_time'];
            }

            $fromDateTime = new DateTime($nextSinceTime, new DateTimeZone('UTC'));
            $fromDateTime->modify('+1 second');

            //------------------------------

            $items = array();
            foreach ($response['items'] as $index => $item) {

                if ($index % 5 == 0) {
                    $this->lockItem->activate();
                }

                $i = 0;
                do {

                    $i++;
                    $fullItemInfo = Mage::getSingleton('e2m/Proxy_Ebay_Api')->sendRequest(array(
                        'command' => array('item', 'get', 'info'),
                        'data' => array(
                            'account' => Mage::getSingleton('e2m/Proxy_Ebay_Account')->getToken(),
                            'item_id' => $item['id'],
                            'parser_type' => 'full',
                        )
                    ));

                } while ($i < self::MAX_SUB_REQUESTS && empty($fullItemInfo['result']));

                if (!empty($fullItemInfo['result'])) {
                    $items[$item['id']] = $fullItemInfo['result'];
                }
            }

            $this->lockItem->activate();

            $items = Mage::getSingleton('e2m/Ebay_Inventory')->updateItems(
                Mage::getSingleton('core/resource'),
                $items
            );

            $this->lockItem->activate();

            Mage::getSingleton('e2m/Ebay_Inventory')->createItems(
                Mage::getSingleton('core/resource'),
                $items
            );

            Mage::helper('e2m/Config')->set(
                self::XML_PATH_FROM_DOWNLOAD_INVENTORY,
                $fromDateTime->getTimestamp()
            );
        }

        $percentage = $this->getProcessAsPercentage($fromDateTime, $toDateTime);
        Mage::helper('e2m/Config')->set(self::XML_PATH_PROCESS_DOWNLOAD_INVENTORY, $percentage);
        if (self::PERCENTAGE_COMPLETED === $percentage) {
            Mage::helper('e2m/Config')->set(self::XML_PATH_WORK_DOWNLOAD_INVENTORY, false);
        }

        Mage::helper('e2m/Config')->set(
            self::XML_PATH_FROM_DOWNLOAD_INVENTORY,
            $fromDateTime->getTimestamp()
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
