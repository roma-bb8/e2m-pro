<?php

class M2E_E2M_Adminhtml_E2mController extends M2E_E2M_Controller_Adminhtml_BaseController {

    /**
     * @return Zend_Controller_Response_Abstract
     */
    public function proceedEbayImportInventoryAction() {

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        $resource = Mage::getSingleton('core/resource');

        $connWrite = $resource->getConnection('core_write');
        $connRead = $resource->getConnection('core_read');

        $cronTasksTableName = $resource->getTableName('m2e_e2m_cron_tasks');

        //----------------------------------------

        $taskId = $connRead->select()->from($cronTasksTableName, array('id'))
            ->where('instance = ?', M2E_E2M_Model_Cron_Task_Magento_ImportInventory::class)
            ->limit(1)->query()->fetchColumn();

        if (empty($taskId)) {
            return $this->ajaxResponse(array(
                'process' => $dataHelper->getConfig(M2E_E2M_Model_Cron_Task_Magento_ImportInventory::CACHE_ID)
            ));
        }

        $connWrite->update($cronTasksTableName, array(
            'pause' => false
        ), array('id = ?' => $taskId));

        $dataHelper->logReport($taskId, $dataHelper->__('Proceed task of Import Inventory from Magento...'));

        return $this->ajaxResponse(array(
            'process' => $dataHelper->getConfig(M2E_E2M_Model_Cron_Task_Magento_ImportInventory::CACHE_ID)
        ));
    }

    //----------------------------------------

    /**
     * @return Zend_Controller_Response_Abstract
     */
    public function pauseEbayImportInventoryAction() {

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        $resource = Mage::getSingleton('core/resource');

        $connWrite = $resource->getConnection('core_write');
        $connRead = $resource->getConnection('core_read');

        $cronTasksTableName = $resource->getTableName('m2e_e2m_cron_tasks');

        //----------------------------------------

        $dataHelper->setCacheValue(M2E_E2M_Model_Cron_Task_Magento_ImportInventory::CACHE_ID, 'pause');

        $taskId = $connRead->select()->from($cronTasksTableName, array('id'))
            ->where('instance = ?', M2E_E2M_Model_Cron_Task_Magento_ImportInventory::class)
            ->limit(1)->query()->fetchColumn();

        if (empty($taskId)) {
            return $this->ajaxResponse(array(
                'process' => $dataHelper->getConfig(M2E_E2M_Model_Cron_Task_Magento_ImportInventory::CACHE_ID)
            ));
        }

        $connWrite->update($cronTasksTableName, array(
            'pause' => true
        ), array('id = ?' => $taskId));

        $dataHelper->logReport($taskId, $dataHelper->__('Pause task of Import Inventory from Magento!'));

        return $this->ajaxResponse(array(
            'process' => $dataHelper->getConfig(M2E_E2M_Model_Cron_Task_Magento_ImportInventory::CACHE_ID)
        ));
    }

    //----------------------------------------

    /**
     * @return Zend_Controller_Response_Abstract
     */
    public function startEbayImportInventoryAction() {

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        $resource = Mage::getSingleton('core/resource');

        $connWrite = $resource->getConnection('core_write');
        $connRead = $resource->getConnection('core_read');

        $cronTasksTableName = $resource->getTableName('m2e_e2m_cron_tasks');

        //----------------------------------------

        $connWrite->delete($cronTasksTableName, array(
            'instance = ?' => M2E_E2M_Model_Cron_Task_Magento_ImportInventory::class
        ));

        //----------------------------------------

        $connWrite->insert($cronTasksTableName, array(
            'instance' => M2E_E2M_Model_Cron_Task_Magento_ImportInventory::class,
            'data' => Mage::helper('core')->jsonEncode(array(
                'last_import_id' => 0
            )),
            'progress' => 0
        ));

        //----------------------------------------

        $taskId = $connRead->select()->from($cronTasksTableName, 'id')
            ->where('instance = ?', M2E_E2M_Model_Cron_Task_Magento_ImportInventory::class)
            ->limit(1)->query()->fetchColumn();

        $dataHelper->logReport($taskId, $dataHelper->__('Start task of Import Inventory from Magento...'));

        return $this->ajaxResponse(array(
            'process' => 0
        ));
    }

    //########################################

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Exception
     */
    public function setSettingsAction() {

        /** @var Mage_Core_Helper_Data $coreHelper */
        $coreHelper = Mage::helper('core');

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        $settings = $coreHelper->jsonDecode($this->getRequest()->getParam('settings'));

        //----------------------------------------

        $dataHelper->setConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET, $settings['attribute-set']);
        $dataHelper->setConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_STORE_MAP, $settings['marketplace-store']);
        $dataHelper->setConfig(
            M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_MAP,
            $settings['ebay-field-magento-attribute']
        );
        $dataHelper->setConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_GENERATE_SKU, $settings['generate-sku']);
        $dataHelper->setConfig(
            M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_IDENTIFIER,
            $settings['product-identifier']
        );
        $dataHelper->setConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_DELETE_HTML, $settings['delete-html']);
        $dataHelper->setConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_FOUND, $settings['action-found']);
        $dataHelper->setConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_IMPORT_IMAGE, $settings['import-image']);
        $dataHelper->setConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_IMPORT_QTY, $settings['import-qty'], true);

        //----------------------------------------

        Mage::dispatchEvent('m2e_e2m_change_ebay_settings');

        //----------------------------------------

        $this->_getSession()->addSuccess($dataHelper->__('Save settings'));

        //----------------------------------------

        return $this->ajaxResponse(array(
            'settings' => $settings
        ));
    }

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
    public function startEbayDownloadInventoryAction() {

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        $resource = Mage::getSingleton('core/resource');

        $connWrite = $resource->getConnection('core_write');
        $connRead = $resource->getConnection('core_read');

        $cronTasksTableName = $resource->getTableName('m2e_e2m_cron_tasks');

        //----------------------------------------

        $connWrite->delete($cronTasksTableName, array(
            'instance = ?' => M2E_E2M_Model_Cron_Task_Ebay_DownloadInventory::class
        ));

        //----------------------------------------

        $toDateTime = new DateTime('now', new DateTimeZone('UTC'));

        $fromDatetime = clone $toDateTime;
        $fromDatetime->setTimestamp(M2E_E2M_Model_Cron_Task_Ebay_DownloadInventory::MAX_DOWNLOAD_TIME);

        $connWrite->insert($cronTasksTableName, array(
            'instance' => M2E_E2M_Model_Cron_Task_Ebay_DownloadInventory::class,
            'data' => Mage::helper('core')->jsonEncode(array(
                'from' => $fromDatetime->getTimestamp(),
                'to' => $toDateTime->getTimestamp()
            )),
            'progress' => 0
        ));

        //----------------------------------------

        $taskId = $connRead->select()->from($cronTasksTableName, 'id')
            ->where('instance = ?', M2E_E2M_Model_Cron_Task_Ebay_DownloadInventory::class)
            ->limit(1)->query()->fetchColumn();

        $dataHelper->logReport($taskId, $dataHelper->__('Start task of Downloading Inventory from eBay...'));

        return $this->ajaxResponse(array(
            'process' => 0,
            'total' => $dataHelper->getCacheValue(M2E_E2M_Helper_Data::CACHE_ID_EBAY_INVENTORY_TOTAL_COUNT, 0),
            'variation' => $dataHelper->getCacheValue(M2E_E2M_Helper_Data::CACHE_ID_EBAY_INVENTORY_VARIATION_COUNT, 0),
            'simple' => $dataHelper->getCacheValue(M2E_E2M_Helper_Data::CACHE_ID_EBAY_INVENTORY_SIMPLE_COUNT, 0)
        ));
    }

    //########################################

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Exception
     */
    public function unlinkEbayAccountAction() {

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        $resource = Mage::getSingleton('core/resource');
        $connWrite = $resource->getConnection('core_write');
        $connWrite->truncateTable($resource->getTableName('m2e_e2m_log'));
        $connWrite->truncateTable($resource->getTableName('m2e_e2m_inventory_ebay'));
        $connWrite->delete($resource->getTableName('m2e_e2m_cron_tasks'), array(
            'instance <> ?' => M2E_E2M_Model_Cron_Task_Completed::class
        ));

        $dataHelper->setConfig(M2E_E2M_Helper_Data::XML_PATH_EBAY_AVAILABLE_MARKETPLACES, array());
        $dataHelper->setConfig(M2E_E2M_Helper_Data::XML_PATH_EBAY_DOWNLOAD_INVENTORY, false);
        $dataHelper->setConfig(M2E_E2M_Helper_Data::XML_PATH_EBAY_IMPORT_INVENTORY, false);

        $dataHelper->setConfig(M2E_E2M_Model_Proxy_Ebay_Account::XML_PATH_EBAY_ACCOUNT_ID, false, true);

        $dataHelper->setCacheValue(M2E_E2M_Helper_Data::CACHE_ID_EBAY_INVENTORY_VARIATION_COUNT, false);
        $dataHelper->setCacheValue(M2E_E2M_Helper_Data::CACHE_ID_EBAY_INVENTORY_SIMPLE_COUNT, false);
        $dataHelper->setCacheValue(M2E_E2M_Helper_Data::CACHE_ID_EBAY_INVENTORY_TOTAL_COUNT, false);

        $dataHelper->setCacheValue(M2E_E2M_Model_Cron_Task_Magento_ImportInventory::CACHE_ID, 0);
        $dataHelper->setCacheValue(M2E_E2M_Model_Cron_Task_Ebay_DownloadInventory::CACHE_ID, 0);

        //----------------------------------------

        $this->_getSession()->addSuccess(Mage::helper('e2m')->__('Account unlink.'));

        //----------------------------------------

        return $this->ajaxResponse(array(
            'redirect' => true
        ));
    }

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Exception
     */
    public function linkEbayAccountAction() {

        $accountId = $this->getRequest()->getParam('account_id');
        if (empty($accountId)) {
            throw new Exception('Account invalid.');
        }

        /** @var M2E_E2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        $dataHelper->setConfig(M2E_E2M_Model_Proxy_Ebay_Account::XML_PATH_EBAY_ACCOUNT_ID, $accountId, true);

        //----------------------------------------

        $this->_getSession()->addSuccess(Mage::helper('e2m')->__('Account link.'));

        //----------------------------------------

        return $this->ajaxResponse(array(
            'redirect' => true
        ));
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


    public function cronAction() {

        session_write_close();

        $coreHelper = Mage::helper('core');

        $resource = Mage::getSingleton('core/resource');

        $connWrite = $resource->getConnection('core_write');
        $connRead = $resource->getConnection('core_read');

        $cronTasksTableName = $resource->getTableName('m2e_e2m_cron_tasks');

        //----------------------------------------

        $tasks = $connRead->select()->from($cronTasksTableName)->order('id DESC')->query();
        $handlers = array();
        while ($task = $tasks->fetch(PDO::FETCH_ASSOC)) {
            if ($task['is_running'] || $task['pause'] ||
                $task['progress'] === M2E_E2M_Model_Cron_Task_Completed::COMPLETED) {
                continue;
            }

            $connWrite->update($cronTasksTableName, array(
                'is_running' => true
            ), array('id = ?' => $task['id']));

            try {

                /** @var M2E_E2M_Model_Cron_Task $taskModel */
                $taskModel = Mage::getModel('e2m/' . str_replace('M2E_E2M_Model_', '', $task['instance']));

                $data = $taskModel->process($task['id'], $coreHelper->jsonDecode($task['data']));
                $instance = lcfirst(substr($task['instance'], strrpos($task['instance'], '_') + 1));
                $handlers[] = array(
                    'handler' => $instance . 'Handler',
                    'data' => $data
                );

            } finally {
                $connWrite->update($cronTasksTableName, array(
                    'is_running' => false
                ), array('id = ?' => $task['id']));
            }
        }

        return $this->ajaxResponse($handlers);
    }
}
