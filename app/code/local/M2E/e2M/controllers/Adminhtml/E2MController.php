<?php

class M2E_e2M_Adminhtml_E2MController extends Mage_Adminhtml_Controller_Action {

    public function indexAction() {

        $this->loadLayout();

        $this->_setActiveMenu('e2m');

        $this->getLayout()->getBlock('head')->setTitle(Mage::helper('e2m')->__('eBay Data Import / eM2Pro'));

        $this->getLayout()->getBlock('head')->addCss('e2m/css/main.css');
        $this->getLayout()->getBlock('head')->addJs('e2m/callbacks.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/helper.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/settings.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/cron/task/ebay/downloadInventoryHandler.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/cron.js');

        $this->_addContent($this->getLayout()->createBlock('e2m/adminhtml_main'));

        $this->renderLayout();
    }

    //########################################
    //########################################

    public function beforeEbayGetTokenAction() {

        $coreHelper = Mage::helper('core');

        try {

            $mode = (int)$coreHelper->jsonDecode($this->getRequest()->getParam('mode'));

            /** @var M2e_e2m_Model_Api_Ebay $eBayAPI */
            $eBayAPI = Mage::getModel('e2m/Api_Ebay');
            $eBayAPI->setMode($mode);
            $sessionID = $eBayAPI->getSessionID();

            /** @var M2E_e2M_Helper_eBay_Account $eBayAccount */
            $eBayAccount = Mage::helper('e2m/eBay_Account');
            $eBayAccount->setMode($mode);
            $eBayAccount->setSessionId($sessionID);
            $eBayAccount->save();

            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'auth_url' => $eBayAPI->getAuthURL($this->getUrl('*/e2m/afterEbayGetToken'), $sessionID)
            )));

        } catch (Exception $e) {
            return $this->getResponse()->setHttpResponseCode(500)->setBody($coreHelper->jsonEncode(array(
                'error' => true,
                'message' => $e->getMessage()
            )));
        }
    }

    public function afterEbayGetTokenAction() {

        try {

            /** @var M2E_e2M_Helper_eBay_Account $eBayAccount */
            $eBayAccount = Mage::helper('e2m/eBay_Account');

            /** @var M2e_e2m_Model_Api_Ebay $eBayAPI */
            $eBayAPI = Mage::getModel('e2m/Api_Ebay');
            $eBayAPI->setMode($eBayAccount->getMode());

            $info = $eBayAPI->getInfo($eBayAccount->getSessionId());
            $info['session_id'] = false;

            $eBayAccount->setData($info);
            $eBayAccount->save();

        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }

        return $this->_redirect('*/e2m/index');
    }

    //########################################

    public function deleteEbayTokenAction() {

        $coreHelper = Mage::helper('core');

        try {

            /** @var M2E_e2M_Helper_Progress $progressHelper */
            $progressHelper = Mage::helper('e2m/Progress');
            $progressHelper->setProgressByTag(M2E_e2M_Helper_Data::EBAY_DOWNLOAD_INVENTORY, 0);
            $progressHelper->setProgressByTag(M2E_e2M_Helper_Data::MAGENTO_IMPORT_INVENTORY, 0);

            $resource = Mage::getSingleton('core/resource');
            $connWrite = $resource->getConnection('core_write');
            $connWrite->delete($resource->getTableName('m2e_e2m_cron_tasks_in_processing'), array(
                'instance IN (?)' => array('Cron_Task_eBay_DownloadInventory', 'Cron_Task_Magento_ImportInventory')
            ));
            $connWrite->truncateTable($resource->getTableName('m2e_e2m_inventory_ebay'));

            /** @var M2E_e2M_Helper_eBay_Account $eBayAccount */
            $eBayAccount = Mage::helper('e2m/eBay_Account');
            $eBayAccount->setData(array(
                'mode' => 0,
                'token' => false,
                'expiration_time' => false,
                'user_id' => false,
                'session_id' => false
            ));
            $eBayAccount->save();

            /** @var M2E_e2M_Helper_eBay_Config $eBayConfig */
            $eBayConfig = Mage::helper('e2m/eBay_Config');
            $eBayConfig->setSettings(array(
                'marketplace-store' => array(),
                'product-identifier' => M2E_e2M_Helper_eBay_Config::VALUE_SKU_PRODUCT_IDENTIFIER,
                'action-found' => M2E_e2M_Helper_eBay_Config::VALUE_IGNORE_ACTION_FOUND,
                'attribute-set' => null,
                'ebay-field-magento-attribute' => array()
            ));
            $eBayConfig->save();

            /** @var M2E_e2M_Helper_eBay_Inventory $eBayInventory */
            $eBayInventory = Mage::helper('e2m/eBay_Inventory');
            $eBayInventory->reloadData();
            $eBayInventory->save();

            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'delete' => true
            )));

        } catch (Exception $e) {
            return $this->getResponse()->setHttpResponseCode(500)->setBody($coreHelper->jsonEncode(array(
                'error' => true,
                'message' => $e->getMessage()
            )));
        }
    }

    //########################################
    //########################################

    public function sendSettingsAction() {

        $coreHelper = Mage::helper('core');

        try {

            $settings = $coreHelper->jsonDecode($this->getRequest()->getParam('settings'));

            /** @var M2E_e2M_Helper_eBay_Config $eBayConfigHelper */
            $eBayConfigHelper = Mage::helper('e2m/eBay_Config');

            $eBayConfigHelper->setSettings($settings);
            $eBayConfigHelper->save();

            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'settings' => $settings
            )));

        } catch (Exception $e) {
            return $this->getResponse()->setHttpResponseCode(500)->setBody($coreHelper->jsonEncode(array(
                'error' => true,
                'message' => $e->getMessage()
            )));
        }
    }

    //########################################
    //########################################

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Zend_Controller_Response_Exception
     */
    public function startTaskDownloadInventoryAction() {

        $coreHelper = Mage::helper('core');

        try {

            $resource = Mage::getSingleton('core/resource');
            $connWrite = $resource->getConnection('core_write');
            $cronTasksInProcessingTableName = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');

            $connWrite->delete($cronTasksInProcessingTableName, array(
                'instance = ?' => 'Cron_Task_eBay_DownloadInventory'
            ));

            /** @var M2E_e2M_Helper_eBay_Inventory $eBayInventory */
            $eBayInventory = Mage::helper('e2m/eBay_Inventory');
            $eBayInventory->save();

            $toDateTime = new DateTime('now', new DateTimeZone('UTC'));

            $fromDatetime = clone $toDateTime;
            $fromDatetime->setTimestamp(M2E_e2M_Model_Cron_Task_eBay_DownloadInventory::MAX_DOWNLOAD_TIME);

            $connWrite->insert($cronTasksInProcessingTableName, array(
                'instance' => 'Cron_Task_eBay_DownloadInventory',
                'data' => $coreHelper->jsonEncode(array(
                    'from' => $fromDatetime->getTimestamp(),
                    'to' => $toDateTime->getTimestamp()
                ))
            ));

            /** @var M2E_e2M_Helper_Progress $progressHelper */
            $progressHelper = Mage::helper('e2m/Progress');
            $progressHelper->setProgressByTag(M2E_e2M_Helper_Data::EBAY_DOWNLOAD_INVENTORY, 0);

            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'message' => 'Start task of Downloading Inventory from ebay...',
                'data' => array(
                    'process' => 0,
                    'total' => $eBayInventory->getItemsTotal(),
                    'variation' => $eBayInventory->getItemsVariation(),
                    'simple' => $eBayInventory->getItemsSimple()
                )
            )));

        } catch (Exception $e) {
            return $this->getResponse()->setHttpResponseCode(500)->setBody($coreHelper->jsonEncode(array(
                'error' => true,
                'message' => $e->getMessage()
            )));
        }
    }

    //########################################

    /**
     * @throws Zend_Controller_Response_Exception
     */
    public function startTaskImportInventoryAction() {

        $coreHelper = Mage::helper('core');

        try {

            $resource = Mage::getSingleton('core/resource');
            $connWrite = $resource->getConnection('core_write');
            $cronTasksInProcessingTableName = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');

            $connWrite->delete($cronTasksInProcessingTableName, array(
                'instance = ?' => 'Cron_Task_Magento_ImportInventory'
            ));

            $connWrite->insert($cronTasksInProcessingTableName, array(
                'instance' => 'Cron_Task_Magento_ImportInventory',
                'data' => $coreHelper->jsonEncode(array(
                    'last_import_id' => 0
                ))
            ));

            /** @var M2E_e2M_Helper_Progress $progressHelper */
            $progressHelper = Mage::helper('e2m/Progress');
            $progressHelper->setProgressByTag(M2E_e2M_Helper_Data::MAGENTO_IMPORT_INVENTORY, 0);

            $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'message' => 'Start task of Import Inventory to Magento...',
                'data' => array(
                    'process' => 0,
                    'items' => 0
                )
            )));

        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(500)->setBody($coreHelper->jsonEncode(array(
                'error' => true,
                'message' => $e->getMessage()
            )));
        }
    }

    //########################################
    //########################################

    public function cronAction() {

        session_write_close();

        $coreHelper = Mage::helper('core');

        try {

            $resource = Mage::getSingleton('core/resource');
            $connWrite = $resource->getConnection('core_write');
            $cronTasksInProcessing = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');
            $tasks = $connWrite->select()->from($resource->getTableName('m2e_e2m_cron_tasks_in_processing'))->query();

            $handlers = array();
            while ($task = $tasks->fetch(PDO::FETCH_ASSOC)) {
                if ($task['is_running']) {
                    continue;
                }

                $connWrite->update($cronTasksInProcessing, array(
                    'is_running' => true
                ), array('id = ?' => $task['id']));

                try {

                    /** @var M2E_e2M_Model_Cron_Task $taskModel */
                    $taskModel = Mage::getModel('e2m/' . $task['instance']);

                    $data = $taskModel->process($coreHelper->jsonDecode($task['data']));
                    $instance = lcfirst(substr($task['instance'], strrpos($task['instance'], '_') + 1));
                    $handlers[] = array(
                        'handler' => $instance . 'Handler',
                        'data' => $data
                    );

                } catch (Exception $e) {
                    throw $e;
                } finally {
                    $connWrite->update($cronTasksInProcessing, array(
                        'is_running' => false
                    ), array('id = ?' => $task['id']));
                }
            }

            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'run' => true,
                'handlers' => $handlers
            )));
        } catch (Exception $e) {

            Mage::log($e->getMessage(), Zend_Log::ERR, 'e2m.log', true);

            return $this->getResponse()->setHttpResponseCode(500)->setBody($coreHelper->jsonEncode(array(
                'run' => false,
                'handlers' => array()
            )));
        }
    }

    //########################################
}
