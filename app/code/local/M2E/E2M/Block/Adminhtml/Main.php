<?php

class M2E_E2M_Block_Adminhtml_Main extends Mage_Adminhtml_Block_Widget_Form {

    private function addMagmiInventoryExportCSVButton(Mage_Adminhtml_Block_Widget_Button $button) {

        /** @var Mage_Adminhtml_Block_Widget_Button $button */
        $button = clone $button;
        $button = $button->setData(array(
            'label' => $this->getDataHelper()->__('magmi'),
            'onclick' => 'getMagmiInventoryExportCSV();'
        ));
        $this->setChild('magmi_inventory_export_csv_button', $button);
    }

    private function addNativeInventoryExportCSVButton(Mage_Adminhtml_Block_Widget_Button $button) {

        /** @var Mage_Adminhtml_Block_Widget_Button $button */
        $button = clone $button;
        $button = $button->setData(array(
            'label' => $this->getDataHelper()->__('native'),
            'onclick' => 'getNativeInventoryExportCSV();'
        ));
        $this->setChild('native_inventory_export_csv_button', $button);
    }

    private function addAttributesSQLButton(Mage_Adminhtml_Block_Widget_Button $button) {

        /** @var Mage_Adminhtml_Block_Widget_Button $button */
        $button = clone $button;
        $button = $button->setData(array(
            'label' => $this->getDataHelper()->__('build'),
            'onclick' => 'getAttributesSQL();'
        ));
        $this->setChild('attributes_sql_button', $button);
    }

    private function addAttributesExportCSVButton(Mage_Adminhtml_Block_Widget_Button $button) {

        /** @var Mage_Adminhtml_Block_Widget_Button $button */
        $button = clone $button;
        $button = $button->setData(array(
            'label' => $this->getDataHelper()->__('build'),
            'onclick' => 'getAttributesExportCSV();'
        ));
        $this->setChild('attributes_export_csv_button', $button);
    }

    private function addAttributesMatchingCSVButton(Mage_Adminhtml_Block_Widget_Button $button) {

        /** @var Mage_Adminhtml_Block_Widget_Button $button */
        $button = clone $button;
        $button = $button->setData(array(
            'label' => $this->getDataHelper()->__('build'),
            'onclick' => 'getAttributesMatchingCSV();'
        ));
        $this->setChild('attributes_matching_csv_button', $button);
    }

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

    private function addUnlinkAccountButton(Mage_Adminhtml_Block_Widget_Button $button) {

        /** @var Mage_Adminhtml_Block_Widget_Button $button */
        $button = $button->setData(array(
            'label' => $this->getDataHelper()->__('Logout'),
            'onclick' => 'unlinkAccount();'
        ));
        $this->setChild('unlink_account_button', $button);
    }

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
        $this->addSettingsButton(clone $widgetButton);
        $this->addAttributesMatchingCSVButton(clone $widgetButton);
        $this->addAttributesExportCSVButton(clone $widgetButton);
        $this->addAttributesSQLButton(clone $widgetButton);
        $this->addNativeInventoryExportCSVButton(clone $widgetButton);
        $this->addMagmiInventoryExportCSVButton(clone $widgetButton);
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

    //########################################

    /**
     * @inheritDoc
     */
    public function __construct() {
        parent::__construct();

        $this->setTemplate('e2m/main.phtml');
    }
}
