<?php

class M2E_e2M_Adminhtml_E2MController extends Mage_Adminhtml_Controller_Action {

    public function indexAction() {

        $this->loadLayout();

        $this->_setActiveMenu('e2m');

        $this->getLayout()->getBlock('head')->addJs('e2M/cron.js');
        $this->getLayout()->getBlock('head')->addJs('e2M/cron/task/eBay/downloadInventoryHandler.js');

        $this->_addContent($this->getLayout()->createBlock('e2m/adminhtml_main'));

        $this->renderLayout();
    }

    //########################################
    //########################################

    public function beforeEbayGetTokenAction() {

        $coreHelper = Mage::helper('core');

        try {

            /** @var M2e_e2m_Model_Api_Ebay $eBayAPI */
            $eBayAPI = Mage::getModel('e2m/Api_Ebay');

            $sessionID = $eBayAPI->getSessionID();
            $resource = Mage::getSingleton('core/resource');
            $connWrite = $resource->getConnection('core_write');
            $coreConfigDataTableName = $resource->getTableName('core_config_data');

            $connWrite->delete($coreConfigDataTableName, array(
                'path LIKE ?' => M2E_e2M_Helper_Data::CONFIG_PATH . 'info/%'
            ));

            $connWrite->insert($coreConfigDataTableName, array(
                'path' => M2E_e2M_Helper_Data::CONFIG_PATH . 'info/session_id/',
                'value' => $sessionID
            ));

            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'auth_url' => $eBayAPI->getAuthURL($this->getUrl('*/e2m/afterEbayGetToken'), $sessionID)
            )));

        } catch (Exception $e) {
            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'error' => true,
                'message' => $e->getMessage()
            )));
        }
    }

    public function afterEbayGetTokenAction() {

        try {

            $resource = Mage::getSingleton('core/resource');
            $connWrite = $resource->getConnection('core_write');
            $coreConfigDataTableName = $resource->getTableName('core_config_data');
            $sessionID = $connWrite->select()
                ->from($coreConfigDataTableName, 'value')
                ->where('path = ?', M2E_e2M_Helper_Data::CONFIG_PATH . 'info/session_id/')
                ->query()
                ->fetchColumn('value');

            if (empty($sessionID)) {
                throw new Exception('Not SessionID in config');
            }

            /** @var M2e_e2m_Model_Api_Ebay $eBayAPI */
            $eBayAPI = Mage::getModel('e2m/Api_Ebay');

            $info = $eBayAPI->getInfo($sessionID);

            $connWrite->delete($coreConfigDataTableName, array(
                'path LIKE ?' => M2E_e2M_Helper_Data::CONFIG_PATH . 'info/%'
            ));

            $connWrite->insertMultiple($coreConfigDataTableName, array(
                array(
                    'path' => M2E_e2M_Helper_Data::CONFIG_PATH . 'info/token/',
                    'value' => $info['token']
                ),
                array(
                    'path' => M2E_e2M_Helper_Data::CONFIG_PATH . 'info/expiration_time/',
                    'value' => $info['expiration_time']
                ),
                array(
                    'path' => M2E_e2M_Helper_Data::CONFIG_PATH . 'info/user_id/',
                    'value' => $info['user_id']
                )
            ));

        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }

        return $this->_redirect('*/e2m/index');
    }

    //########################################

    public function deleteEbayTokenAction() {

        $coreHelper = Mage::helper('core');

        try {

            $resource = Mage::getSingleton('core/resource');
            $connWrite = $resource->getConnection('core_write');
            $coreConfigDataTableName = $resource->getTableName('core_config_data');

            $connWrite->delete($coreConfigDataTableName, array(
                'path LIKE ?' => M2E_e2M_Helper_Data::CONFIG_PATH . '%'
            ));

            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'delete' => true
            )));

        } catch (Exception $e) {
            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'error' => true,
                'message' => $e->getMessage()
            )));
        }
    }

    //########################################
    //########################################

    public function unmapEbayPropertyForMagentoAttributeAction() {

        $coreHelper = Mage::helper('core');

        /** @var M2E_e2M_Helper_Data $e2MHelper */
        $e2MHelper = Mage::helper('e2M');

        try {

            $eBayProperty = $this->getRequest()->getParam('ebay_property');
            if (!$e2MHelper->eBayPropertyValidator($eBayProperty)) {
                throw new Exception('Not valid eBay Property');
            }

            $resource = Mage::getSingleton('core/resource');
            $connWrite = $resource->getConnection('core_write');
            $coreConfigDataTableName = $resource->getTableName('core_config_data');

            $maps = $connWrite->select()
                ->from($coreConfigDataTableName, 'value')
                ->where('path = ?', M2E_e2M_Helper_Data::CONFIG_PATH . 'eBay/properties/for/magento/attributes/maps/')
                ->query()
                ->fetchColumn('value');

            $maps = $coreHelper->jsonDecode(empty($maps) ? '{}' : $maps);

            unset($maps[$eBayProperty]);

            if (count($maps) <= 0) {
                $connWrite->delete($coreConfigDataTableName, array(
                    'path = ?', M2E_e2M_Helper_Data::CONFIG_PATH . 'eBay/properties/for/magento/attributes/maps/'
                ));
            } else {
                $connWrite->update($coreConfigDataTableName, array(
                    'value' => $coreHelper->jsonEncode($maps)
                ), array(
                    'path = ?', M2E_e2M_Helper_Data::CONFIG_PATH . 'eBay/properties/for/magento/attributes/maps/'
                ));
            }

            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'ebay_property' => $eBayProperty
            )));

        } catch (Exception $e) {
            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'error' => true,
                'message' => $e->getMessage()
            )));
        }
    }

    //########################################

    public function mapEbayPropertyForMagentoAttributeAction() {

        $coreHelper = Mage::helper('core');

        /** @var M2E_e2M_Helper_Data $e2MHelper */
        $e2MHelper = Mage::helper('e2M');

        try {

            $eBayProperty = $this->getRequest()->getParam('ebay_property');
            if (!$e2MHelper->eBayPropertyValidator($eBayProperty)) {
                throw new Exception('Not valid eBay Property');
            }

            $magentoAttribute = $this->getRequest()->getParam('magento_attribute');
            if (!$e2MHelper->magentoAttributeValidator($magentoAttribute)) {
                throw new Exception('Not valid Magento Attribute');
            }

            $resource = Mage::getSingleton('core/resource');
            $connWrite = $resource->getConnection('core_write');
            $coreConfigDataTableName = $resource->getTableName('core_config_data');

            $maps = $connWrite->select()
                ->from($coreConfigDataTableName, 'value')
                ->where('path = ?', M2E_e2M_Helper_Data::CONFIG_PATH . 'eBay/properties/for/magento/attributes/maps/')
                ->query()
                ->fetchColumn('value');

            $maps = $coreHelper->jsonDecode(empty($maps) ? '{}' : $maps);

            $maps[$eBayProperty] = $magentoAttribute;

            if (count($maps) <= 1) {
                $connWrite->insert($coreConfigDataTableName, array(
                    'path' => M2E_e2M_Helper_Data::CONFIG_PATH . 'eBay/properties/for/magento/attributes/maps/',
                    'value' => $coreHelper->jsonEncode($maps)
                ));
            } else {
                $connWrite->update($coreConfigDataTableName, array(
                    'value' => $coreHelper->jsonEncode($maps)
                ), array(
                    'path = ?', M2E_e2M_Helper_Data::CONFIG_PATH . 'eBay/properties/for/magento/attributes/maps/'
                ));
            }

            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'ebay_property' => $eBayProperty,
                'magento_attribute' => $magentoAttribute
            )));

        } catch (Exception $e) {
            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'error' => true,
                'message' => $e->getMessage()
            )));
        }
    }

    //########################################

    public function mapMarketplacesToStoresAction() {

        $coreHelper = Mage::helper('core');

        /** @var M2E_e2M_Helper_Data $e2MHelper */
        $e2MHelper = Mage::helper('e2M');

        try {

            $maps = $coreHelper->jsonDecode($this->getRequest()->getParam('maps', '{}'));

            if (!$e2MHelper->marketplacesValidator(array_keys($maps))) {
                throw new Exception('Not valid marketplaces');
            }

            if (!$e2MHelper->storesValidator(array_values($maps))) {
                throw new Exception('Not valid stores');
            }

            $resource = Mage::getSingleton('core/resource');
            $connWrite = $resource->getConnection('core_write');
            $coreConfigDataTableName = $resource->getTableName('core_config_data');

            $connWrite->delete($coreConfigDataTableName, array(
                'path = ?' => M2E_e2M_Helper_Data::CONFIG_PATH . 'marketplaces/stores/maps/'
            ));
            $connWrite->insert($coreConfigDataTableName, array(
                'path' => M2E_e2M_Helper_Data::CONFIG_PATH . 'marketplaces/stores/maps/',
                'value' => $coreHelper->jsonEncode($maps)
            ));

            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'maps' => $maps
            )));

        } catch (Exception $e) {
            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'error' => true,
                'message' => $e->getMessage()
            )));
        }
    }

    //########################################

    public function mapIdentifierProductOnDifferentMarketplacesAction() {

        $coreHelper = Mage::helper('core');

        /** @var M2E_e2M_Helper_Data $e2MHelper */
        $e2MHelper = Mage::helper('e2M');

        try {

            $identifier = $this->getRequest()->getParam('identifier');
            if (!$e2MHelper->identifierValidator($identifier)) {
                throw new Exception('Not valid identifier');
            }

            $resource = Mage::getSingleton('core/resource');
            $connWrite = $resource->getConnection('core_write');
            $coreConfigDataTableName = $resource->getTableName('core_config_data');

            $connWrite->delete($coreConfigDataTableName, array(
                'path = ?' => M2E_e2M_Helper_Data::CONFIG_PATH . 'product/identifier/'
            ));
            $connWrite->insert($coreConfigDataTableName, array(
                'path' => M2E_e2M_Helper_Data::CONFIG_PATH . 'product/identifier/',
                'value' => $identifier
            ));

            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'identifier' => $identifier
            )));

        } catch (Exception $e) {
            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'error' => true,
                'message' => $e->getMessage()
            )));
        }
    }

    //########################################
    //########################################

    public function startTaskDownloadInventoryAction() {

        $coreHelper = Mage::helper('core');

        try {

            $dateTimeZone = new DateTimeZone('UTC');
            $fromDateTime = new DateTime('now', $dateTimeZone);
            $toDateTime = new DateTime(946684800, $dateTimeZone);

            $resource = Mage::getSingleton('core/resource');
            $connWrite = $resource->getConnection('core_write');
            $connWrite->insert($resource->getTableName('m2e_e2m_cron_tasks_in_processing'), array(
                'instance' => 'Cron_Task_eBay_DownloadInventory',
                'data' => $coreHelper->jsonEncode(array(
                    'from' => $fromDateTime->getTimestamp(),
                    'to' => $toDateTime->getTimestamp()
                ))
            ));

            $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'message' => 'Start task of Downloading Inventory from eBay...',
                'data' => array(
                    'process' => 0,
                    'items' => 0
                )
            )));

        } catch (Exception $e) {
            $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'error' => true,
                'message' => $e->getMessage()
            )));
        }
    }

    //########################################

    public function startTaskImportInventoryAction() {

        $coreHelper = Mage::helper('core');

        try {

            $resource = Mage::getSingleton('core/resource');
            $connWrite = $resource->getConnection('core_write');
            $connWrite->insert($resource->getTableName('m2e_e2m_cron_tasks_in_processing'), array(
                'instance' => 'Cron_Task_Magento_ImportInventory',
                'data' => $coreHelper->jsonEncode(array(
                    'last_import_product_id' => 0
                ))
            ));

            $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'message' => 'Start task of Import Inventory to Magento...',
                'data' => array(
                    'process' => 0,
                    'items' => 0
                )
            )));

        } catch (Exception $e) {
            $this->getResponse()->setBody($coreHelper->jsonEncode(array(
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
            $connRead = $resource->getConnection('core_read');
            $tasks = $connRead
                ->select()
                ->from($resource->getTableName('m2e_e2m_cron_tasks_in_processing'))
                ->query();

            $handlers = array();
            while ($task = $tasks->fetch()) {

                /** @var M2E_e2M_Model_Cron_Task $taskModel */
                $taskModel = Mage::getModel('e2M/' . $task['instance']);
                $data = $taskModel->process($task['data']);

                $instance = lcfirst(substr($task['instance'], strrpos($task['instance'], '_')));
                $handlers[$instance . 'Handler'] = $data;
            }

            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'run' => true,
                'handlers' => $handlers
            )));
        } catch (Exception $e) {
            return $this->getResponse()->setBody($coreHelper->jsonEncode(array(
                'run' => false,
                'handlers' => array()
            )));
        }
    }

    //########################################
}
