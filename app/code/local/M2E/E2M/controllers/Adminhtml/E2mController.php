<?php
/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * Class M2E_E2M_Adminhtml_E2mController
 */
class M2E_E2M_Adminhtml_E2mController extends M2E_E2M_Controller_Adminhtml_BaseController {

    /**
     * @return Zend_Controller_Response_Abstract
     */
    public function pauseFinishTaskImportInventoryAction() {

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        $resource = Mage::getSingleton('core/resource');

        $connWrite = $resource->getConnection('core_write');
        $connRead = $resource->getConnection('core_read');

        $cronTasksInProcessingTableName = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');

        //----------------------------------------

        $taskId = $connRead->select()->from($cronTasksInProcessingTableName, array('id'))
            ->where('instance = ?', M2E_E2M_Model_Cron_Task_Magento_ImportInventory::INSTANCE)->query()->fetchColumn();

        if (empty($taskId)) {
            return $this->ajaxResponse(array(
                'process' => 'empty',
                'items' => 'empty'
            ));
        }

        $connWrite->update($cronTasksInProcessingTableName, array(
            'pause' => false
        ), array('id = ?' => $taskId));

        $dataHelper->logReport($taskId, $dataHelper->__('Proceed task of Import Inventory from Magento...'));

        return $this->ajaxResponse(array(
            'process' => 'pause',
            'items' => 'pause'
        ));
    }

    //----------------------------------------

    /**
     * @return Zend_Controller_Response_Abstract
     */
    public function pauseStartTaskImportInventoryAction() {

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        $resource = Mage::getSingleton('core/resource');

        $connWrite = $resource->getConnection('core_write');
        $connRead = $resource->getConnection('core_read');

        $cronTasksInProcessingTableName = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');

        //----------------------------------------

        $taskId = $connRead->select()->from($cronTasksInProcessingTableName, array('id'))
            ->where('instance = ?', M2E_E2M_Model_Cron_Task_Magento_ImportInventory::INSTANCE)->query()->fetchColumn();

        if (empty($taskId)) {
            return $this->ajaxResponse(array(
                'process' => 'empty',
                'items' => 'empty'
            ));
        }

        $connWrite->update($cronTasksInProcessingTableName, array(
            'pause' => true
        ), array('id = ?' => $taskId));

        $dataHelper->logReport($taskId, $dataHelper->__('Pause task of Import Inventory from Magento!'));

        return $this->ajaxResponse(array(
            'process' => 'pause',
            'items' => 'pause'
        ));
    }

    //----------------------------------------

    /**
     * @return Zend_Controller_Response_Abstract
     */
    public function startTaskImportInventoryAction() {

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        $resource = Mage::getSingleton('core/resource');

        $connWrite = $resource->getConnection('core_write');
        $connRead = $resource->getConnection('core_read');

        $cronTasksInProcessingTableName = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');

        //----------------------------------------

        $connWrite->delete($cronTasksInProcessingTableName, array(
            'instance = ?' => M2E_E2M_Model_Cron_Task_Magento_ImportInventory::INSTANCE
        ));

        //----------------------------------------

        $connWrite->insert($cronTasksInProcessingTableName, array(
            'instance' => M2E_E2M_Model_Cron_Task_Magento_ImportInventory::INSTANCE,
            'data' => Mage::helper('core')->jsonEncode(array(
                'last_import_id' => 0
            )),
            'progress' => 0
        ));

        //----------------------------------------

        $taskId = $connRead->select()->from($cronTasksInProcessingTableName, 'id')
            ->where('instance = ?', M2E_E2M_Model_Cron_Task_Magento_ImportInventory::INSTANCE)->query()->fetchColumn();

        $dataHelper->logReport($taskId, $dataHelper->__('Start task of Import Inventory from Magento...'));

        return $this->ajaxResponse(array(
            'process' => 0,
            'items' => 0
        ));
    }

    //########################################

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Exception
     */
    public function setSettingsAction() {

        /** @var M2E_E2M_Model_Ebay_Config $eBayConfig */
        $eBayConfig = Mage::getSingleton('e2m/Ebay_Config');

        //----------------------------------------

        $settings = Mage::helper('core')->jsonDecode($this->getRequest()->getParam('settings'));

        $eBayConfig->set(
            M2E_E2M_Model_Ebay_Config::PATH_MARKETPLACE_TO_STORE_MAP,
            $settings['marketplace-store'],
            false
        );
        $eBayConfig->set(
            M2E_E2M_Model_Ebay_Config::PATH_INVENTORY_PRODUCT_IDENTIFIER,
            $settings['product-identifier'],
            false
        );
        $eBayConfig->set(
            M2E_E2M_Model_Ebay_Config::PATH_INVENTORY_ACTION_FOUND,
            $settings['action-found'],
            false
        );
        $eBayConfig->set(
            M2E_E2M_Model_Ebay_Config::PATH_PRODUCT_IMPORT_QTY,
            $settings['import-qty'],
            false
        );
        $eBayConfig->set(
            M2E_E2M_Model_Ebay_Config::PATH_PRODUCT_GENERATE_SKU,
            $settings['generate-sku'],
            false
        );
        $eBayConfig->set(
            M2E_E2M_Model_Ebay_Config::PATH_PRODUCT_IMPORT_IMAGE,
            $settings['import-image'],
            false
        );
        $eBayConfig->set(
            M2E_E2M_Model_Ebay_Config::PATH_PRODUCT_DELETE_HTML,
            $settings['delete-html'],
            false
        );
        $eBayConfig->set(
            M2E_E2M_Model_Ebay_Config::PATH_PRODUCT_ATTRIBUTE_SET,
            $settings['attribute-set'],
            false
        );
        $eBayConfig->set(
            M2E_E2M_Model_Ebay_Config::PATH_PRODUCT_FIELDS_ATTRIBUTES_MAP,
            $settings['ebay-field-magento-attribute'],
            false
        );
        $eBayConfig->save();

        //----------------------------------------

        $this->_getSession()->addSuccess(Mage::helper('e2m')->__('Save settings'));

        //----------------------------------------

        return $this->ajaxResponse(array(
            'settings' => $settings
        ));
    }

    //----------------------------------------

    /**
     * @return Zend_Controller_Response_Abstract
     */
    public function getAttributesBySetIdAction() {

        $setId = (int)Mage::helper('core')->jsonDecode($this->getRequest()->getParam('set_id'));
        return $this->ajaxResponse(array(
            'attributes' => Mage::helper('e2m')->getMagentoAttributes($setId)
        ));
    }

    //########################################

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Exception
     */
    public function startTaskDownloadInventoryAction() {

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        /** @var M2E_E2M_Model_Ebay_Inventory $eBayInventory */
        $eBayInventory = Mage::getSingleton('e2m/Ebay_Inventory');

        $resource = Mage::getSingleton('core/resource');

        $connWrite = $resource->getConnection('core_write');
        $connRead = $resource->getConnection('core_read');

        $cronTasksInProcessingTableName = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');

        //----------------------------------------

        $connWrite->delete($cronTasksInProcessingTableName, array(
            'instance = ?' => M2E_E2M_Model_Cron_Task_Ebay_DownloadInventory::INSTANCE
        ));

        //----------------------------------------

        $toDateTime = new DateTime('now', new DateTimeZone('UTC'));

        $fromDatetime = clone $toDateTime;
        $fromDatetime->setTimestamp(M2E_E2M_Model_Cron_Task_eBay_DownloadInventory::MAX_DOWNLOAD_TIME);

        $connWrite->insert($cronTasksInProcessingTableName, array(
            'instance' => M2E_E2M_Model_Cron_Task_Ebay_DownloadInventory::INSTANCE,
            'data' => Mage::helper('core')->jsonEncode(array(
                'from' => $fromDatetime->getTimestamp(),
                'to' => $toDateTime->getTimestamp()
            )),
            'progress' => 0
        ));

        //----------------------------------------

        $taskId = $connRead->select()->from($cronTasksInProcessingTableName, 'id')
            ->where('instance = ?', M2E_E2M_Model_Cron_Task_Ebay_DownloadInventory::INSTANCE)->query()->fetchColumn();

        $dataHelper->logReport($taskId, $dataHelper->__('Start task of Downloading Inventory from eBay...'));

        return $this->ajaxResponse(array(
            'process' => 0,
            'total' => $eBayInventory->get(M2E_E2M_Model_Ebay_Inventory::PATH_ITEMS_COUNT_TOTAL),
            'variation' => $eBayInventory->get(M2E_E2M_Model_Ebay_Inventory::PATH_ITEMS_COUNT_VARIATION),
            'simple' => $eBayInventory->get(M2E_E2M_Model_Ebay_Inventory::PATH_ITEMS_COUNT_SIMPLE)
        ));
    }

    //########################################

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Exception
     */
    public function unsetEbayTokenAction() {

        /** @var M2E_E2M_Model_Ebay_Account $eBayAccount */
        $eBayAccount = Mage::getSingleton('e2m/Ebay_Account');

        /** @var M2E_E2M_Model_Ebay_Config $eBayConfig */
        $eBayConfig = Mage::getSingleton('e2m/Ebay_Config');

        /** @var M2E_E2M_Model_Ebay_Inventory $eBayInventory */
        $eBayInventory = Mage::getSingleton('e2m/Ebay_Inventory');

        $resource = Mage::getSingleton('core/resource');

        //----------------------------------------

        $connWrite = $resource->getConnection('core_write');
        $connWrite->truncateTable($resource->getTableName('m2e_e2m_log'));
        $connWrite->truncateTable($resource->getTableName('m2e_e2m_inventory_ebay'));
        $connWrite->delete($resource->getTableName('m2e_e2m_cron_tasks_in_processing'), array(
            'instance IN (?)' => array(
                M2E_E2M_Model_Cron_Task_Ebay_DownloadInventory::INSTANCE,
                M2E_E2M_Model_Cron_Task_Magento_ImportInventory::INSTANCE
            )
        ));

        $eBayAccount->set(M2E_E2M_Model_Ebay_Account::MODE, 0, false);
        $eBayAccount->set(M2E_E2M_Model_Ebay_Account::TOKEN, false, false);
        $eBayAccount->set(M2E_E2M_Model_Ebay_Account::EXPIRATION_TIME, false, false);
        $eBayAccount->set(M2E_E2M_Model_Ebay_Account::USER_ID, false, false);
        $eBayAccount->set(M2E_E2M_Model_Ebay_Account::SESSION_ID, false, false);
        $eBayAccount->save();

        $eBayConfig->set(M2E_E2M_Model_Ebay_Config::PATH_MARKETPLACE_TO_STORE_MAP, array(), false);
        $eBayConfig->set(
            M2E_E2M_Model_Ebay_Config::PATH_INVENTORY_PRODUCT_IDENTIFIER,
            M2E_E2M_Model_Ebay_Config::VALUE_SKU_PRODUCT_IDENTIFIER,
            false
        );
        $eBayConfig->set(
            M2E_E2M_Model_Ebay_Config::PATH_INVENTORY_ACTION_FOUND,
            M2E_E2M_Model_Ebay_Config::VALUE_IGNORE_ACTION_FOUND,
            false
        );
        $eBayConfig->set(M2E_E2M_Model_Ebay_Config::PATH_PRODUCT_IMPORT_QTY, false, false);
        $eBayConfig->set(M2E_E2M_Model_Ebay_Config::PATH_PRODUCT_GENERATE_SKU, false, false);
        $eBayConfig->set(M2E_E2M_Model_Ebay_Config::PATH_PRODUCT_IMPORT_IMAGE, false, false);
        $eBayConfig->set(M2E_E2M_Model_Ebay_Config::PATH_PRODUCT_DELETE_HTML, false, false);
        $eBayConfig->set(M2E_E2M_Model_Ebay_Config::PATH_PRODUCT_ATTRIBUTE_SET, null, false);
        $eBayConfig->set(M2E_E2M_Model_Ebay_Config::PATH_PRODUCT_FIELDS_ATTRIBUTES_MAP, array(), false);
        $eBayConfig->save();

        $eBayInventory->set(M2E_E2M_Model_Ebay_Inventory::PATH_ITEMS_COUNT_TOTAL, 0, false);
        $eBayInventory->set(M2E_E2M_Model_Ebay_Inventory::PATH_ITEMS_COUNT_VARIATION, 0, false);
        $eBayInventory->set(M2E_E2M_Model_Ebay_Inventory::PATH_ITEMS_COUNT_SIMPLE, 0, false);
        $eBayInventory->set(M2E_E2M_Model_Ebay_Inventory::PATH_MARKETPLACES, array(), false);
        $eBayInventory->save();

        //----------------------------------------

        $this->_getSession()->addSuccess(Mage::helper('e2m')->__('Unset token'));

        //----------------------------------------

        return $this->ajaxResponse(array(
            'delete' => true
        ));
    }

    //----------------------------------------

    /**
     * @return M2E_E2M_Adminhtml_E2MController|Mage_Core_Controller_Varien_Action
     * @throws Exception
     */
    public function getAfterEbayTokenAction() {

        /** @var M2E_E2M_Model_Ebay_Account $eBayAccount */
        $eBayAccount = Mage::getSingleton('e2m/Ebay_Account');

        /** @var M2e_e2m_Model_Api_Ebay $eBayAPI */
        $eBayAPI = Mage::getSingleton('e2m/Api_Ebay');

        //----------------------------------------

        $info = $eBayAPI->getInfo(
            $eBayAccount->get(M2E_E2M_Model_Ebay_Account::MODE),
            $eBayAccount->get(M2E_E2M_Model_Ebay_Account::SESSION_ID)
        );

        $eBayAccount->set(M2E_E2M_Model_Ebay_Account::SESSION_ID, false, false);
        $eBayAccount->set(M2E_E2M_Model_Ebay_Account::TOKEN, $info['token'], false);
        $eBayAccount->set(M2E_E2M_Model_Ebay_Account::EXPIRATION_TIME, $info['expiration_time'], false);
        $eBayAccount->set(M2E_E2M_Model_Ebay_Account::USER_ID, $info['user_id'], false);
        $eBayAccount->save();

        //----------------------------------------

        $this->_getSession()->addSuccess(Mage::helper('e2m')->__('Save eBay token'));

        //----------------------------------------

        return $this->_redirect('*/e2m/index');
    }

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Exception
     */
    public function getBeforeEbayTokenAction() {

        /** @var M2E_E2M_Model_eBay_Account $eBayAccount */
        $eBayAccount = Mage::getSingleton('e2m/Ebay_Account');

        /** @var M2e_e2m_Model_Api_Ebay $eBayAPI */
        $eBayAPI = Mage::getSingleton('e2m/Api_Ebay');

        //----------------------------------------

        $mode = (int)Mage::helper('core')->jsonDecode(
            $this->getRequest()->getParam(M2E_E2M_Model_Ebay_Account::MODE)
        );
        $sessionID = $eBayAPI->getSessionID($mode);

        $eBayAccount->set(M2E_E2M_Model_Ebay_Account::MODE, $mode, false);
        $eBayAccount->set(M2E_E2M_Model_Ebay_Account::SESSION_ID, $sessionID, false);
        $eBayAccount->save();

        return $this->ajaxResponse(array(
            'url' => $eBayAPI->getAuthURL($eBayAccount->get(M2E_E2M_Model_Ebay_Account::MODE),
                $this->getUrl('*/e2m/getAfterEbayToken'), $sessionID)
        ));
    }

    //########################################

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Zend_Db_Statement_Exception
     */
    public function cronAction() {

        session_write_close();

        $coreHelper = Mage::helper('core');

        $resource = Mage::getSingleton('core/resource');

        $connWrite = $resource->getConnection('core_write');
        $connRead = $resource->getConnection('core_read');

        $cronTasksInProcessing = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');

        //----------------------------------------

        $tasks = $connRead->select()->from($cronTasksInProcessing)->query();

        $handlers = array();
        while ($task = $tasks->fetch(PDO::FETCH_ASSOC)) {
            if ($task['is_running'] || $task['pause'] ||
                $task['progress'] === M2E_E2M_Model_Cron_Task_Completed::COMPLETED) {
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

            } finally {
                $connWrite->update($cronTasksInProcessing, array(
                    'is_running' => false
                ), array('id = ?' => $task['id']));
            }
        }

        return $this->ajaxResponse($handlers);
    }

    //########################################

    /**
     * @return Mage_Core_Controller_Varien_Action
     */
    public function indexAction() {

        $this->loadLayout();

        //----------------------------------------

        $this->_setActiveMenu('e2m');

        //----------------------------------------

        $this->getLayout()->getBlock('head')->setTitle(Mage::helper('e2m')->__('eBay Data Import / eM2Pro'));

        //----------------------------------------

        $this->getLayout()->getBlock('head')->addCss('e2m/css/main.css');

        //----------------------------------------

        $this->getLayout()->getBlock('head')->addJs('e2m/main.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/magento.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/ebay.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/callback/magento.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/callback/ebay.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/cron/task/ebay.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/observer.js');

        //----------------------------------------

        $this->_addContent($this->getLayout()->createBlock('e2m/adminhtml_main'));

        //----------------------------------------

        return $this->renderLayout();
    }

    //########################################

    /**
     * @inheritDoc
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
}
