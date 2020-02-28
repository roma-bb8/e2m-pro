<?php

class M2E_E2M_Block_Adminhtml_Main extends Mage_Adminhtml_Block_Widget_Form {

    /**
     * @param Mage_Adminhtml_Block_Widget_Button $button
     */
    private function addSettingsButton(Mage_Adminhtml_Block_Widget_Button $button) {

        /** @var Mage_Adminhtml_Block_Widget_Button $button */
        $button = clone $button;
        $button = $button->setData(array(
            'label' => $this->getDataHelper()->__('Save config'),
            'class' => 'save',
            'onclick' => 'sendSettings();'
        ));
        $this->setChild('send_settings_button', $button);
    }

    /**
     * @param Mage_Adminhtml_Block_Widget_Button $button
     *
     * @throws Zend_Db_Statement_Exception
     */
    private function addStartImportInventoryButton(Mage_Adminhtml_Block_Widget_Button $button) {

        /** @var Mage_Adminhtml_Block_Widget_Button $button */

        $resource = Mage::getSingleton('core/resource');
        $connRead = $resource->getConnection('core_read');
        $cronTasksTableName = $resource->getTableName('m2e_e2m_cron_tasks');

        //----------------------------------------

        $label = 'Import inventory (look for settings)';
        $disabledPause = true;
        $disabled = true;
        $task = $connRead->select()->from($cronTasksTableName, array('id', 'pause'))
            ->where('instance = ?', M2E_E2M_Model_Cron_Task_Magento_ImportInventory::class)
            ->limit(1)->query()->fetch(PDO::FETCH_ASSOC);

        if ($this->getDataHelper()->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_FULL_SET_SETTING, false)) {
            $label = 'Start import inventory';
            $disabled = false;
        }

        if ($this->getDataHelper()->getConfig(M2E_E2M_Helper_Data::XML_PATH_EBAY_IMPORT_INVENTORY, false)) {
            $label = 'Reimport inventory (completed)';
            $disabled = false;
        }

        if (!empty($task['id'])) {
            $label = 'Import inventory (in progress...)';
            $disabledPause = false;
            $disabled = true;
        }

        $button = $button->setData(array(
            'label' => $this->getDataHelper()->__($label),
            'onclick' => 'startImportInventory(this);',
            'disabled' => $disabled
        ));
        $this->setChild('start_import_inventory_button', $button);

        //----------------------------------------

        $button = clone $button;
        $button = $button->setData(array(
            'label' => $this->getDataHelper()->__((!$task['pause'] ? 'Pause' : 'Proceed') . ' Import inventory'),
            'onclick' => !$task['pause'] ? 'pauseStartImportInventory(this);' : 'pauseFinishImportInventory(this);',
            'disabled' => $disabledPause
        ));
        $this->setChild('pause_import_inventory_button', $button);
    }

    /**
     * @param Mage_Adminhtml_Block_Widget_Button $button
     */
    private function addStartDownloadInventoryButton(Mage_Adminhtml_Block_Widget_Button $button) {

        /** @var Mage_Adminhtml_Block_Widget_Button $button */

        $resource = Mage::getSingleton('core/resource');
        $connRead = $resource->getConnection('core_read');
        $cronTasksTableName = $resource->getTableName('m2e_e2m_cron_tasks');

        //----------------------------------------

        $label = 'Start download inventory';
        $disabled = false;
        $id = $connRead->select()->from($cronTasksTableName, 'id')
            ->where('instance = ?', M2E_E2M_Model_Cron_Task_eBay_DownloadInventory::class)
            ->limit(1)->query()->fetchColumn();

        if (empty($id) && $this->getDataHelper()
                ->getConfig(M2E_E2M_Helper_Data::XML_PATH_EBAY_DOWNLOAD_INVENTORY, false)) {
            $label = 'Reload inventory (completed)';
            $disabled = false;
        }

        if (!empty($id)) {
            $label = 'Download inventory (in progress...)';
            $disabled = true;
        }

        $button = $button->setData(array(
            'label' => $this->getDataHelper()->__($label),
            'onclick' => 'startDownloadInventory(this);',
            'disabled' => $disabled
        ));
        $this->setChild('start_download_inventory_button', $button);
    }

    /**
     * @param Mage_Adminhtml_Block_Widget_Button $button
     */
    private function addUnlinkAccountButton(Mage_Adminhtml_Block_Widget_Button $button) {

        /** @var Mage_Adminhtml_Block_Widget_Button $button */
        $button = $button->setData(array(
            'label' => $this->getDataHelper()->__('Logout'),
            'onclick' => 'unlinkAccount();'
        ));
        $this->setChild('unlink_account_button', $button);
    }

    /**
     * @param Mage_Adminhtml_Block_Widget_Button $button
     */
    private function addLinkAccountButton(Mage_Adminhtml_Block_Widget_Button $button) {

        /** @var Mage_Adminhtml_Block_Widget_Button $button */
        $button = $button->setData(array(
            'label' => $this->getDataHelper()->__('Link'),
            'onclick' => 'linkAccount();'
        ));
        $this->setChild('link_account_button', $button);
    }

    //########################################

    /**
     * @inheritDoc
     * @throws Zend_Db_Statement_Exception
     */
    protected function _beforeToHtml() {

        /** @var Mage_Adminhtml_Block_Widget_Button $widgetButton */
        $widgetButton = $this->getLayout()->createBlock('adminhtml/widget_button');

        //----------------------------------------

        if (empty($this->getEbayAccount()->getUserId())) {
            $this->addLinkAccountButton(clone $widgetButton);
            return;
        }

        $this->addUnlinkAccountButton(clone $widgetButton);
        $this->addStartDownloadInventoryButton(clone $widgetButton);
        $this->addStartImportInventoryButton(clone $widgetButton);
        $this->addSettingsButton(clone $widgetButton);

        $this->setChild('logs_block', $this->getLayout()->createBlock('e2m/adminhtml_log_grid'));
    }

    //########################################

    /**
     * @return Ess_M2ePro_Model_Account[]
     */
    public function getEbayAccounts() {

        $accountsCollection = Mage::helper('M2ePro/Component_Ebay')->getCollection('Account');
        return $accountsCollection->getItems();
    }

    /**
     * @return M2E_E2M_Model_Proxy_Ebay_Account
     */
    public function getEbayAccount() {
        return Mage::getSingleton('e2m/Proxy_Ebay_Account');
    }

    //########################################

    /**
     * @return M2E_E2M_Helper_Ebay_Config
     */
    public function getEbayConfigHelper() {
        return Mage::helper('e2m/Ebay_Config');
    }

    /**
     * @return M2E_E2M_Helper_Data
     */
    public function getDataHelper() {
        return Mage::helper('e2m');
    }

    /**
     * @return Mage_Core_Helper_Data
     */
    public function getCoreHelper() {
        return  Mage::helper('core');
    }

    //########################################

    /**
     * @inheritDoc
     */
    public function __construct() {
        parent::__construct();

        $this->setTemplate('e2m/main.phtml');
    }
}
