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
            ->where('instance = ?', 'Cron_Task_Magento_ImportInventory')->query()->fetchColumn();

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
            ->where('instance = ?', 'Cron_Task_Magento_ImportInventory')->query()->fetchColumn();

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

        /** @var M2E_E2M_Helper_Progress $progressHelper */
        $progressHelper = Mage::helper('e2m/Progress');

        $resource = Mage::getSingleton('core/resource');

        $connWrite = $resource->getConnection('core_write');
        $connRead = $resource->getConnection('core_read');

        $cronTasksInProcessingTableName = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');

        //----------------------------------------

        $connWrite->delete($cronTasksInProcessingTableName, array(
            'instance = ?' => 'Cron_Task_Magento_ImportInventory'
        ));

        //----------------------------------------

        $connWrite->insert($cronTasksInProcessingTableName, array(
            'instance' => 'Cron_Task_Magento_ImportInventory',
            'data' => Mage::helper('core')->jsonEncode(array(
                'last_import_id' => 0
            ))
        ));

        $progressHelper->setProgressByTag(M2E_E2M_Model_Cron_Task_Magento_ImportInventory::TAG, 0);

        //----------------------------------------

        $taskId = $connRead->select()->from($cronTasksInProcessingTableName, 'id')
            ->where('instance = ?', 'Cron_Task_Magento_ImportInventory')->query()->fetchColumn();

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

        /** @var M2E_E2M_Model_Ebay_Config $eBayConfigHelper */
        $eBayConfigHelper = Mage::getSingleton('e2m/Ebay_Config');

        //----------------------------------------

        $settings = Mage::helper('core')->jsonDecode($this->getRequest()->getParam('settings'));

        $eBayConfigHelper->set('marketplace_store', $settings['marketplace-store'], false);
        $eBayConfigHelper->set('product_identifier', $settings['product-identifier'], false);
        $eBayConfigHelper->set('action_found', $settings['action-found'], false);
        $eBayConfigHelper->set('import_qty', $settings['import-qty'], false);
        $eBayConfigHelper->set('generate_sku', $settings['generate-sku'], false);
        $eBayConfigHelper->set('import_image', $settings['import-image'], false);
        $eBayConfigHelper->set('delete_html', $settings['delete-html'], false);
        $eBayConfigHelper->set('attribute_set', $settings['attribute-set'], false);
        $eBayConfigHelper->set('ebay_field_magento_attribute', $settings['ebay-field-magento-attribute'], false);
        $eBayConfigHelper->save();

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
        $eBayInventory = Mage::helper('e2m/Ebay_Inventory');

        /** @var M2E_E2M_Helper_Progress $progressHelper */
        $progressHelper = Mage::helper('e2m/Progress');

        $resource = Mage::getSingleton('core/resource');

        $connWrite = $resource->getConnection('core_write');
        $connRead = $resource->getConnection('core_read');

        $cronTasksInProcessingTableName = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');

        //----------------------------------------

        $connWrite->delete($cronTasksInProcessingTableName, array(
            'instance = ?' => 'Cron_Task_eBay_DownloadInventory'
        ));

        //----------------------------------------

        $toDateTime = new DateTime('now', new DateTimeZone('UTC'));

        $fromDatetime = clone $toDateTime;
        $fromDatetime->setTimestamp(M2E_E2M_Model_Cron_Task_eBay_DownloadInventory::MAX_DOWNLOAD_TIME);

        $connWrite->insert($cronTasksInProcessingTableName, array(
            'instance' => 'Cron_Task_eBay_DownloadInventory',
            'data' => Mage::helper('core')->jsonEncode(array(
                'from' => $fromDatetime->getTimestamp(),
                'to' => $toDateTime->getTimestamp()
            ))
        ));

        $progressHelper->setProgressByTag(M2E_E2M_Model_Cron_Task_eBay_DownloadInventory::TAG, 0);

        //----------------------------------------

        $taskId = $connRead->select()->from($cronTasksInProcessingTableName, 'id')
            ->where('instance = ?', 'Cron_Task_eBay_DownloadInventory')->query()->fetchColumn();

        $dataHelper->logReport($taskId, $dataHelper->__('Start task of Downloading Inventory from eBay...'));

        return $this->ajaxResponse(array(
            'process' => 0,
            'total' => $eBayInventory->get('items/count/total'),
            'variation' => $eBayInventory->get('items/count/variation'),
            'simple' => $eBayInventory->get('items/count/simple')
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

        /** @var M2E_E2M_Helper_Progress $progressHelper */
        $progressHelper = Mage::helper('e2m/Progress');

        $resource = Mage::getSingleton('core/resource');

        //----------------------------------------

        $progressHelper->setProgressByTag(M2E_E2M_Model_Cron_Task_eBay_DownloadInventory::TAG, 0);
        $progressHelper->setProgressByTag(M2E_E2M_Model_Cron_Task_Magento_ImportInventory::TAG, 0);

        //----------------------------------------

        $connWrite = $resource->getConnection('core_write');
        $connWrite->truncateTable($resource->getTableName('m2e_e2m_log'));
        $connWrite->truncateTable($resource->getTableName('m2e_e2m_inventory_ebay'));
        $connWrite->delete($resource->getTableName('m2e_e2m_cron_tasks_in_processing'), array(
            'instance IN (?)' => array('Cron_Task_eBay_DownloadInventory', 'Cron_Task_Magento_ImportInventory')
        ));

        $eBayAccount->set('mode', 0, false);
        $eBayAccount->set('token', false, false);
        $eBayAccount->set('expiration_time', false, false);
        $eBayAccount->set('user_id', false, false);
        $eBayAccount->set('session_id', false, false);
        $eBayAccount->save();

        $eBayConfig->set('marketplace_store', array(), false);
        $eBayConfig->set('product_identifier', M2E_E2M_Model_Ebay_Config::VALUE_SKU_PRODUCT_IDENTIFIER, false);
        $eBayConfig->set('action_found', M2E_E2M_Model_Ebay_Config::VALUE_IGNORE_ACTION_FOUND, false);
        $eBayConfig->set('import_qty', false, false);
        $eBayConfig->set('generate_sku', false, false);
        $eBayConfig->set('import_image', false, false);
        $eBayConfig->set('delete_html', false, false);
        $eBayConfig->set('attribute_set', null, false);
        $eBayConfig->set('ebay_field_magento_attribute', array(), false);
        $eBayConfig->save();

        $eBayInventory->set('items/count/total', 0, false);
        $eBayInventory->set('items/count/variation', 0, false);
        $eBayInventory->set('items/count/simple', 0, false);
        $eBayInventory->set('marketplaces', array(), false);
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

        $info = $eBayAPI->getInfo((int)$eBayAccount->get('mode'), $eBayAccount->get('session_id'));

        $eBayAccount->set('session_id', false, false);
        $eBayAccount->set('token', $info['token'], false);
        $eBayAccount->set('expiration_time', $info['expiration_time'], false);
        $eBayAccount->set('user_id', $info['user_id'], false);
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

        $mode = (int)Mage::helper('core')->jsonDecode($this->getRequest()->getParam('mode'));
        $sessionID = $eBayAPI->getSessionID($mode);

        $eBayAccount->set('mode', $mode, false);
        $eBayAccount->set('session_id', $sessionID, false);
        $eBayAccount->save();

        return $this->ajaxResponse(array(
            'url' => $eBayAPI->getAuthURL($eBayAccount->getMode(),
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

        $resource = Mage::getSingleton('core/resource');

        $cronTasksInProcessing = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');

        $connWrite = $resource->getConnection('core_write');
        $connRead = $resource->getConnection('core_read');

        $tasks = $connRead->select()->from($cronTasksInProcessing)->query();

        $handlers = array();
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

                $data = $taskModel->process($task['id'], Mage::helper('core')->jsonDecode($task['data']));
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

        $this->getLayout()->getBlock('head')->addCss('e2m/css/tooltip.css');

        //----------------------------------------

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
        $this->getLayout()->getBlock('head')->addJs('e2m/observer/initialize-local-storage.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/observer/setting/ebay/attribute-set.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/observer/setting/ebay/config-input.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/observer/setting/ebay/ebay-field-magento-attribute.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/observer/hide-block.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/observer/note-block.js');
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
