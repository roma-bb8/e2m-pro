<?php

class M2E_e2M_Adminhtml_E2MController extends Mage_Adminhtml_Controller_Action {

    const CONFIG_PATH = 'm2e/e2m/';
    const MAX_DAYS = 118;

    public function indexAction() {
        $this->loadLayout();

        $this->_addContent($this->getLayout()->createBlock('e2m/adminhtml_main'));
        $this->_setActiveMenu('e2m');
        $this->renderLayout();
    }

    public function beforeGetTokenAction() {

        try {

            /** @var M2e_e2m_Model_Api_Ebay $eBayAPI */
            $eBayAPI = Mage::getModel('e2m/Api_Ebay');

            $sessionID = $eBayAPI->getSessionID();
            $resource = Mage::getSingleton('core/resource');
            $connWrite = $resource->getConnection('core_write');
            $coreConfigDataTableName = $resource->getTableName('core_config_data');

            $select = $connWrite->select()
                ->from($coreConfigDataTableName, 'value')
                ->where('scope = ?', 'default')
                ->where('scope_id = ?', 0)
                ->where('path = ?', self::CONFIG_PATH . 'session_id');

            if ($connWrite->fetchOne($select) === false) {
                $connWrite->insert($coreConfigDataTableName, array(
                    'value' => $sessionID,
                    'scope' => 'default',
                    'scope_id' => 0,
                    'path' => self::CONFIG_PATH . 'session_id'
                ));
            } else {
                $connWrite->update($coreConfigDataTableName, array('value' => $sessionID), array(
                    'scope = ?' => 'default',
                    'scope_id = ?' => 0,
                    'path = ?' => self::CONFIG_PATH . 'session_id'
                ));
            }

            $authURL = $eBayAPI->getAuthURL($this->getUrl('*/e2m/afterGetToken'), $sessionID);
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                'error' => 0,
                'url' => $authURL
            )));
        } catch (Exception $e) {
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                'error' => 1,
                'message' => 'Error: ' . $e->getMessage()
            )));
        }
    }

    public function afterGetTokenAction() {

        $resource = Mage::getSingleton('core/resource');
        $connWrite = $resource->getConnection('core_write');
        $coreConfigDataTableName = $resource->getTableName('core_config_data');
        $select = $connWrite->select()
            ->from($coreConfigDataTableName, 'value')
            ->where('scope = ?', 'default')
            ->where('scope_id = ?', 0)
            ->where('path = ?', self::CONFIG_PATH . 'session_id');

        if (false === $sessionID = $connWrite->fetchOne($select)) {
            throw new Exception('Not SessionID in config');
        }

        /** @var M2e_e2m_Model_Api_Ebay $eBayAPI */
        $eBayAPI = Mage::getModel('e2m/Api_Ebay');

        $info = $eBayAPI->getTokenInfo($sessionID);
        $connWrite->delete($coreConfigDataTableName, array(
            'scope = ?' => 'default',
            'scope_id = ?' => 0,
            'path LIKE ?' => '%' . self::CONFIG_PATH . '%'
        ));

        $connWrite->insertMultiple($coreConfigDataTableName, array(
            array(
                'value' => $info['token'],
                'scope' => 'default',
                'scope_id' => 0,
                'path' => self::CONFIG_PATH . 'token'
            ),
            array(
                'value' => $info['expiration_time'],
                'scope' => 'default',
                'scope_id' => 0,
                'path' => self::CONFIG_PATH . 'expiration_time'
            ),
            array(
                'value' => $info['user_id'],
                'scope' => 'default',
                'scope_id' => 0,
                'path' => self::CONFIG_PATH . 'user_id'
            )
        ));

        $this->_redirect('*/e2m/index');
    }

    public function deleteTokenAction() {

        $resource = Mage::getSingleton('core/resource');
        $connWrite = $resource->getConnection('core_write');
        $coreConfigDataTableName = $resource->getTableName('core_config_data');
        $connWrite->delete($coreConfigDataTableName, array(
            'scope = ?' => 'default',
            'scope_id = ?' => 0,
            'path LIKE ?' => '%' . self::CONFIG_PATH . '%'
        ));
    }

    public function startDownloadInventoryAction() {

        session_write_close();

        $resource = Mage::getSingleton('core/resource');
        $connWrite = $resource->getConnection('core_write');
        $coreConfigDataTableName = $resource->getTableName('core_config_data');
        $connWrite->delete($coreConfigDataTableName, array(
            'scope = ?' => 'default',
            'scope_id = ?' => 0,
            'path LIKE ?' => '%' . self::CONFIG_PATH . 'progress'
        ));
        $connWrite->insert($coreConfigDataTableName, array(
            'scope' => 'default',
            'scope_id' => 0,
            'path' => self::CONFIG_PATH . 'progress',
            'value' => 0
        ));

        try {

            $select = $connWrite->select()
                ->from($coreConfigDataTableName, 'value')
                ->where('scope = ?', 'default')
                ->where('scope_id = ?', 0)
                ->where('path = ?', self::CONFIG_PATH . 'token');

            $token = $connWrite->fetchOne($select);
            if (empty($token)) {
                throw new Exception('empty $token');
            }

            /** @var M2e_e2m_Model_Api_Ebay $eBayAPI */
            $eBayAPI = Mage::getModel('e2m/Api_Ebay');

            $currentDateTime = new DateTime('now', new DateTimeZone('UTC'));
            $toDateTime = clone $currentDateTime;

            $pro100 = $currentDateTime->getTimestamp();
            $pro0 = 946684800;
            $proClean = $pro100 - $pro0;

            do {

                $fromDateTime = clone $toDateTime;
                $fromDateTime->modify('-' . self::MAX_DAYS . ' days');

                $eBayAPI->loadInventory($token, $fromDateTime, $toDateTime);
                $toDateTime = $fromDateTime;

                $ts = $fromDateTime->getTimestamp();
                $connWrite->update($coreConfigDataTableName,
                    array(
                        'value' => (int)((($ts - $pro0) / $proClean) * 100)
                    ),
                    array(
                        'scope = ?' => 'default',
                        'scope_id = ?' => 0,
                        'path LIKE ?' => '%' . self::CONFIG_PATH . 'progress'
                    )
                );

            } while ($ts > $pro0);

            $select = $connWrite->select()
                ->from($coreConfigDataTableName, 'value')
                ->where('scope = ?', 'default')
                ->where('scope_id = ?', 0)
                ->where('path = ?', self::CONFIG_PATH . 'items_count');

            $m2ee2mInventory = $resource->getTableName('m2e_e2m_inventory');
            $count = $connWrite->select()->from($m2ee2mInventory, 'COUNT(*)')->query()->fetchColumn();
            if (!$connWrite->fetchOne($select)) {
                $connWrite->insert($coreConfigDataTableName, array(
                    'value' => $count,
                    'scope' => 'default',
                    'scope_id' => 0,
                    'path' => self::CONFIG_PATH . 'items_count'
                ));
            } else {
                $connWrite->update($coreConfigDataTableName,
                    array('value' => $count),
                    array(
                        'scope = ?' => 'default',
                        'scope_id = ?' => 0,
                        'path = ?' => self::CONFIG_PATH . 'items_count'
                    )
                );
            }

            $connWrite->update($coreConfigDataTableName,
                array(
                    'value' => 100
                ),
                array(
                    'scope = ?' => 'default',
                    'scope_id = ?' => 0,
                    'path LIKE ?' => '%' . self::CONFIG_PATH . 'progress'
                )
            );

            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                'roma' => 0
            )));

        } catch (Exception $e) {
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                'error' => 1,
                'message' => $e->getMessage()
            )));
        }
    }

    public function getDownloadInventoryProgressStatusAction() {
        $resource = Mage::getSingleton('core/resource');
        $connRead = $resource->getConnection('core_read');
        $coreConfigDataTableName = $resource->getTableName('core_config_data');
        $select = $connRead->select()
            ->from($coreConfigDataTableName, 'value')
            ->where('scope = ?', 'default')
            ->where('scope_id = ?', 0)
            ->where('path = ?', self::CONFIG_PATH . 'progress');
        $value = (int)$connRead->fetchOne($select) ?: 0;
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
            'complete' => $value === 100,
            'progress' => $value
        )));
    }

    public function mappingMarketplacesStoresAction() {

        $maps = $this->getRequest()->getParam('maps');
        $resource = Mage::getSingleton('core/resource');
        $connWrite = $resource->getConnection('core_write');
        $coreConfigDataTableName = $resource->getTableName('core_config_data');
        $connWrite->delete($coreConfigDataTableName, array(
            'scope = ?' => 'default',
            'scope_id = ?' => 0,
            'path = ?' => self::CONFIG_PATH . 'marketplaces/stores/map'
        ));
        $connWrite->insert($coreConfigDataTableName, array(
            'scope' => 'default',
            'scope_id' => 0,
            'path' => self::CONFIG_PATH . 'marketplaces/stores/map',
            'value' => $maps
        ));

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
            'maps' => $maps
        )));
    }

    public function mappingMangetoToeBayAttAction() {

        $maps = $this->getRequest()->getParam('maps');
        $resource = Mage::getSingleton('core/resource');
        $connWrite = $resource->getConnection('core_write');
        $coreConfigDataTableName = $resource->getTableName('core_config_data');
        $connWrite->delete($coreConfigDataTableName, array(
            'scope = ?' => 'default',
            'scope_id = ?' => 0,
            'path = ?' => self::CONFIG_PATH . 'maps'
        ));
        $connWrite->insert($coreConfigDataTableName, array(
            'scope' => 'default',
            'scope_id' => 0,
            'path' => self::CONFIG_PATH . 'maps',
            'value' => $maps
        ));

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
            'maps' => $maps
        )));
    }

    public function mapRelationProductAction() {

        $attr = $this->getRequest()->getParam('attr');
        $resource = Mage::getSingleton('core/resource');
        $connWrite = $resource->getConnection('core_write');
        $coreConfigDataTableName = $resource->getTableName('core_config_data');
        $connWrite->delete($coreConfigDataTableName, array(
            'scope = ?' => 'default',
            'scope_id = ?' => 0,
            'path = ?' => self::CONFIG_PATH . 'attr/map'
        ));
        $connWrite->insert($coreConfigDataTableName, array(
            'scope' => 'default',
            'scope_id' => 0,
            'path' => self::CONFIG_PATH . 'attr/map',
            'value' => $attr
        ));

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
            'attr' => $attr
        )));
    }

    public function startImportInventoryAction() {
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
            'ok' => true
        )));
    }
}
