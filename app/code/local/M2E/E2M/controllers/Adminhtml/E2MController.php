<?php

/**
 * Class M2E_E2M_Adminhtml_E2MController
 */
class M2E_E2M_Adminhtml_E2MController extends Mage_Adminhtml_Controller_Action {

    /**
     * M2E_E2M_Adminhtml_E2MController constructor.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param Zend_Controller_Response_Abstract $response
     * @param array $invokeArgs
     */
    public function __construct(
        Zend_Controller_Request_Abstract $request,
        Zend_Controller_Response_Abstract $response,
        array $invokeArgs = array()) {

        register_shutdown_function(function () {
            $error = error_get_last();
            if (strpos($error['message'], 'deprecated')) {
                return;
            }

            if (strpos($error['message'], 'Too few arguments')) {
                return;
            }

            /** @var M2E_E2M_Helper_Data $dataHelper */
            $dataHelper = Mage::helper('e2m');
            $dataHelper->logException(new Exception(
                "Error: {$error['message']}\nFile: {$error['file']}\nLine: {$error['line']}"
            ));
        });

        parent::__construct($request, $response, $invokeArgs);
    }

    public function indexAction() {

        $this->loadLayout();

        $this->_setActiveMenu('e2m');

        $this->getLayout()->getBlock('head')->setTitle(Mage::helper('e2m')->__('eBay Data Import / eM2Pro'));

        $this->getLayout()->getBlock('head')->addCss('e2m/css/tooltip.css');

        $this->getLayout()->getBlock('head')->addJs('e2m/callback/ebay/get-token.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/callback/ebay/send-settings.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/callback/ebay/start-download-inventory.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/callback/ebay/start-import-inventory.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/callback/ebay/unset-token.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/callback/ebay/pause-finish-import-inventory.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/callback/ebay/pause-start-import-inventory.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/callback/magento/hide-block.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/callback/magento/show-block.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/cron/task/ebay/download-inventory-handler.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/cron/task/ebay/import-inventory-handler.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/ebay/paint-import-properties.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/change-tooltip-position.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/cron.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/delete-hashed-storage.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/get-hashed-storage.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/md5.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/on-tooltip-icon-mouse-leave.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/on-tooltip-mouse-enter.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/on-tooltip-mouse-leave.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/remove-local-storage.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/set-hashed-storage.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/show-tooltip.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/utf8-encode.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/observer/init-cron.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/observer/initialize-local-storage.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/observer/setting/ebay/attribute-set.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/observer/setting/ebay/config-input.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/observer/setting/ebay/ebay-field-magento-attribute.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/observer/hide-block.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/observer/note-block.js');

        $this->_addContent($this->getLayout()->createBlock('e2m/adminhtml_main'));

        $this->renderLayout();
    }

    //########################################

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Zend_Controller_Response_Exception
     */
    public function getBeforeEbayTokenAction() {

        $coreHelper = Mage::helper('core');

        try {

            $mode = (int)$coreHelper->jsonDecode($this->getRequest()->getParam('mode'));

            /** @var M2e_e2m_Model_Api_Ebay $eBayAPI */
            $eBayAPI = Mage::getModel('e2m/Api_Ebay');
            $sessionID = $eBayAPI->getSessionID($mode);

            /** @var M2E_E2M_Helper_eBay_Account $eBayAccount */
            $eBayAccount = Mage::helper('e2m/eBay_Account');
            $eBayAccount->setMode($mode);
            $eBayAccount->setSessionId($sessionID);
            $eBayAccount->save();

            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'url' => $eBayAPI->getAuthURL(
                    $eBayAccount->getMode(),
                    $this->getUrl('*/e2m/getAfterEbayToken'),
                    $sessionID
                )
            )));

        } catch (Exception $e) {
            Mage::helper('e2m')->logException($e);

            return $this->getResponse()->setHttpResponseCode(500)->setBody($coreHelper->jsonEncode(array(
                'error' => true,
                'message' => $e->getMessage()
            )));
        }
    }

    /**
     * @return M2E_E2M_Adminhtml_E2MController|Mage_Core_Controller_Varien_Action
     */
    public function getAfterEbayTokenAction() {

        try {

            /** @var M2E_E2M_Helper_eBay_Account $eBayAccount */
            $eBayAccount = Mage::helper('e2m/eBay_Account');

            /** @var M2e_e2m_Model_Api_Ebay $eBayAPI */
            $eBayAPI = Mage::getModel('e2m/Api_Ebay');

            $info = $eBayAPI->getInfo($eBayAccount->getMode(), $eBayAccount->getSessionId());
            $info['session_id'] = false;

            $eBayAccount->setData($info);
            $eBayAccount->save();

            $this->_getSession()->addSuccess(Mage::helper('e2m')->__('Save eBay token'));

        } catch (Exception $e) {
            Mage::helper('e2m')->logException($e);

            $this->_getSession()->addError($e->getMessage());
        }

        return $this->_redirect('*/e2m/index');
    }

    //----------------------------------------

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Zend_Controller_Response_Exception
     */
    public function unsetEbayTokenAction() {

        $coreHelper = Mage::helper('core');

        try {

            /** @var M2E_E2M_Helper_Progress $progressHelper */
            $progressHelper = Mage::helper('e2m/Progress');
            $progressHelper->setProgressByTag(M2E_E2M_Model_Cron_Task_eBay_DownloadInventory::TAG, 0);
            $progressHelper->setProgressByTag(M2E_E2M_Model_Cron_Task_Magento_ImportInventory::TAG, 0);

            $resource = Mage::getSingleton('core/resource');
            $connWrite = $resource->getConnection('core_write');
            $connWrite->delete($resource->getTableName('m2e_e2m_cron_tasks_in_processing'), array(
                'instance IN (?)' => array('Cron_Task_eBay_DownloadInventory', 'Cron_Task_Magento_ImportInventory')
            ));
            $connWrite->truncateTable($resource->getTableName('m2e_e2m_inventory_ebay'));

            /** @var M2E_E2M_Helper_eBay_Account $eBayAccount */
            $eBayAccount = Mage::helper('e2m/eBay_Account');
            $eBayAccount->setData(array(
                'mode' => 0,
                'token' => false,
                'expiration_time' => false,
                'user_id' => false,
                'session_id' => false
            ));
            $eBayAccount->save();

            /** @var M2E_E2M_Helper_eBay_Config $eBayConfig */
            $eBayConfig = Mage::helper('e2m/eBay_Config');
            $eBayConfig->setSettings(array(
                'marketplace-store' => array(),
                'product-identifier' => M2E_E2M_Helper_eBay_Config::VALUE_SKU_PRODUCT_IDENTIFIER,
                'action-found' => M2E_E2M_Helper_eBay_Config::VALUE_IGNORE_ACTION_FOUND,
                'import-qty' => false,
                'generate-sku' => false,
                'import-image' => false,
                'delete-html' => false,
                'attribute-set' => null,
                'ebay-field-magento-attribute' => array()
            ));
            $eBayConfig->save();

            /** @var M2E_E2M_Helper_eBay_Inventory $eBayInventory */
            $eBayInventory = Mage::helper('e2m/eBay_Inventory');
            $eBayInventory->reloadData();
            $eBayInventory->save();

            $this->_getSession()->addSuccess(Mage::helper('e2m')->__('Unset token'));

            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'delete' => true
            )));

        } catch (Exception $e) {
            Mage::helper('e2m')->logException($e);

            return $this->getResponse()->setHttpResponseCode(500)->setBody($coreHelper->jsonEncode(array(
                'error' => true,
                'message' => $e->getMessage()
            )));
        }
    }

    //########################################

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Zend_Controller_Response_Exception
     */
    public function getAttributesBySetIdAction() {

        $coreHelper = Mage::helper('core');

        try {

            $setId = $coreHelper->jsonDecode($this->getRequest()->getParam('set_id'));
            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'attributes' => Mage::helper('e2m')->getMagentoAttributes($setId)
            )));

        } catch (Exception $e) {
            Mage::helper('e2m')->logException($e);

            return $this->getResponse()->setHttpResponseCode(500)->setBody($coreHelper->jsonEncode(array(
                'error' => true,
                'message' => $e->getMessage()
            )));
        }
    }

    //########################################

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Zend_Controller_Response_Exception
     */
    public function setSettingsAction() {

        $coreHelper = Mage::helper('core');

        try {

            $settings = $coreHelper->jsonDecode($this->getRequest()->getParam('settings'));

            /** @var M2E_E2M_Helper_eBay_Config $eBayConfigHelper */
            $eBayConfigHelper = Mage::helper('e2m/eBay_Config');

            $eBayConfigHelper->setSettings($settings);
            $eBayConfigHelper->save();

            $this->_getSession()->addSuccess(Mage::helper('e2m')->__('Save settings'));

            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'settings' => $settings
            )));

        } catch (Exception $e) {
            Mage::helper('e2m')->logException($e);

            return $this->getResponse()->setHttpResponseCode(500)->setBody($coreHelper->jsonEncode(array(
                'error' => true,
                'message' => $e->getMessage()
            )));
        }
    }

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

            /** @var M2E_E2M_Helper_eBay_Inventory $eBayInventory */
            $eBayInventory = Mage::helper('e2m/eBay_Inventory');
            $eBayInventory->save();

            $toDateTime = new DateTime('now', new DateTimeZone('UTC'));

            $fromDatetime = clone $toDateTime;
            $fromDatetime->setTimestamp(M2E_E2M_Model_Cron_Task_eBay_DownloadInventory::MAX_DOWNLOAD_TIME);

            $id = $connWrite->insert($cronTasksInProcessingTableName, array(
                'instance' => 'Cron_Task_eBay_DownloadInventory',
                'data' => $coreHelper->jsonEncode(array(
                    'from' => $fromDatetime->getTimestamp(),
                    'to' => $toDateTime->getTimestamp()
                ))
            ));

            Mage::helper('e2m')->logReport($id, 'Start task of Downloading Inventory from eBay...',
                M2E_E2M_Helper_Data::TYPE_REPORT_SUCCESS
            );

            /** @var M2E_E2M_Helper_Progress $progressHelper */
            $progressHelper = Mage::helper('e2m/Progress');
            $progressHelper->setProgressByTag(M2E_E2M_Model_Cron_Task_eBay_DownloadInventory::TAG, 0);

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
            Mage::helper('e2m')->logException($e);

            return $this->getResponse()->setHttpResponseCode(500)->setBody($coreHelper->jsonEncode(array(
                'error' => true,
                'message' => $e->getMessage()
            )));
        }
    }

    //########################################

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Zend_Controller_Response_Exception
     */
    public function pauseStartTaskImportInventoryAction() {

        $coreHelper = Mage::helper('core');

        try {

            $resource = Mage::getSingleton('core/resource');
            $connWrite = $resource->getConnection('core_write');
            $cronTasksInProcessingTableName = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');
            $taskId = $connWrite->select()->from($cronTasksInProcessingTableName, array('id'))
                ->where('instance = ?', 'Cron_Task_Magento_ImportInventory')->limit(1)
                ->query()->fetchColumn();

            if (empty($taskId)) {
                return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                    'message' => 'Not task of Import Inventory.',
                    'data' => array(
                        'process' => 'p',
                        'items' => 'p'
                    )
                )));
            }

            $connWrite->update($cronTasksInProcessingTableName, array(
                'pause' => true
            ), array('id = ?' => $taskId));

            Mage::helper('e2m')->logReport($taskId, 'Pause task of Import Inventory from Magento!',
                M2E_E2M_Helper_Data::TYPE_REPORT_SUCCESS
            );

            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'message' => 'Pause task of Import Inventory from ebay...',
                'data' => array(
                    'process' => 'p',
                    'items' => 'p'
                )
            )));

        } catch (Exception $e) {
            Mage::helper('e2m')->logException($e);

            return $this->getResponse()->setHttpResponseCode(500)->setBody($coreHelper->jsonEncode(array(
                'error' => true,
                'message' => $e->getMessage()
            )));
        }
    }

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Zend_Controller_Response_Exception
     */
    public function pauseFinishTaskImportInventoryAction() {

        $coreHelper = Mage::helper('core');

        try {

            $resource = Mage::getSingleton('core/resource');
            $connWrite = $resource->getConnection('core_write');
            $cronTasksInProcessingTableName = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');
            $taskId = $connWrite->select()->from($cronTasksInProcessingTableName, array('id'))
                ->where('instance = ?', 'Cron_Task_Magento_ImportInventory')->limit(1)
                ->query()->fetchColumn();

            /** @var M2E_E2M_Helper_eBay_Inventory $eBayInventory */
            $eBayInventory = Mage::helper('e2m/eBay_Inventory');

            if (empty($taskId)) {
                return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                    'message' => 'Not task of Import Inventory.',
                    'data' => array(
                        'process' => 0,
                        'total' => $eBayInventory->getItemsTotal(),
                        'variation' => $eBayInventory->getItemsVariation(),
                        'simple' => $eBayInventory->getItemsSimple()
                    )
                )));
            }

            $connWrite->update($cronTasksInProcessingTableName, array(
                'pause' => false
            ), array('id = ?' => $taskId));

            Mage::helper('e2m')->logReport($taskId, 'Proceed task of Import Inventory from Magento...',
                M2E_E2M_Helper_Data::TYPE_REPORT_SUCCESS
            );

            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'message' => 'Pause task of Import Inventory from ebay...',
                'data' => array(
                    'process' => 'p',
                    'items' => 'p'
                )
            )));

        } catch (Exception $e) {
            Mage::helper('e2m')->logException($e);

            return $this->getResponse()->setHttpResponseCode(500)->setBody($coreHelper->jsonEncode(array(
                'error' => true,
                'message' => $e->getMessage()
            )));
        }
    }

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

            $id = $connWrite->insert($cronTasksInProcessingTableName, array(
                'instance' => 'Cron_Task_Magento_ImportInventory',
                'data' => $coreHelper->jsonEncode(array(
                    'last_import_id' => 0
                ))
            ));

            Mage::helper('e2m')->logReport($id, 'Start task of Import Inventory from Magento...',
                M2E_E2M_Helper_Data::TYPE_REPORT_SUCCESS
            );

            /** @var M2E_E2M_Helper_Progress $progressHelper */
            $progressHelper = Mage::helper('e2m/Progress');
            $progressHelper->setProgressByTag(M2E_E2M_Model_Cron_Task_Magento_ImportInventory::TAG, 0);

            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'message' => 'Start task of Import Inventory to Magento...',
                'data' => array(
                    'process' => 0,
                    'items' => 0
                )
            )));

        } catch (Exception $e) {
            Mage::helper('e2m')->logException($e);

            return $this->getResponse()->setHttpResponseCode(500)->setBody($coreHelper->jsonEncode(array(
                'error' => true,
                'message' => $e->getMessage()
            )));
        }
    }

    //########################################

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Zend_Controller_Response_Exception
     */
    public function cronAction() {

        session_write_close();

        $coreHelper = Mage::helper('core');

        try {

            $resource = Mage::getSingleton('core/resource');
            $connWrite = $resource->getConnection('core_write');
            $cronTasksInProcessing = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');

            $handlers = array();
            $tasks = $connWrite->select()->from($cronTasksInProcessing)->query();
            while ($task = $tasks->fetch(PDO::FETCH_ASSOC)) {
                if ($task['is_running'] || $task['pause']) {
                    continue;
                }

                $connWrite->update($cronTasksInProcessing, array(
                    'is_running' => true
                ), array('id = ?' => $task['id']));

                try {

                    /** @var M2E_E2M_Model_Cron_Task $taskModel */
                    $taskModel = Mage::getModel('e2m/' . $task['instance']);

                    $data = $taskModel->process($task['id'], $coreHelper->jsonDecode($task['data']));
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
            Mage::helper('e2m')->logException($e);

            return $this->getResponse()->setHttpResponseCode(500)->setBody($coreHelper->jsonEncode(array(
                'run' => false,
                'handlers' => array()
            )));
        }
    }
}