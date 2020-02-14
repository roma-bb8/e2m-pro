<?php

class M2E_e2M_Block_Adminhtml_Main extends Mage_Adminhtml_Block_Widget_Form {

    /**
     * @return M2E_e2M_Helper_Data
     */
    public function getDataHelper() {
        return Mage::helper('e2m');
    }

    /**
     * @return Mage_Core_Helper_Data
     */
    public function getCoreHelper() {
        return Mage::helper('core');
    }

    /**
     * @return M2E_e2M_Helper_eBay_Config
     */
    public function getConfigHelper() {
        return Mage::helper('e2m/eBay_Config');
    }

    /**
     * @return M2E_e2M_Helper_eBay_Inventory
     */
    public function getInventoryHelper() {
        return Mage::helper('e2m/eBay_Inventory');
    }

    /**
     * @return M2E_e2M_Helper_eBay_Account
     */
    public function getAccountHelper() {
        return Mage::helper('e2m/eBay_Account');
    }

    /**
     * @return M2E_e2M_Helper_Progress
     */
    public function getProgressHelper() {
        return Mage::helper('e2m/Progress');
    }

    protected function _beforeToHtml() {

        /** @var Mage_Adminhtml_Block_Widget_Button $button */
        /** @var Mage_Adminhtml_Block_Widget_Button $widgetButton */
        $widgetButton = $this->getLayout()->createBlock('adminhtml/widget_button');

        //----------------------------------------

        if (empty($this->getAccountHelper()->getToken())) {

            $button = (clone $widgetButton)->setData(array(
                'label' => $this->getDataHelper()->__('Get Token'),
                'onclick' => 'getToken();'
            ));
            $this->setChild('get_token_button', $button);

            return;
        }

        //----------------------------------------

        $button = (clone $widgetButton)->setData(array(
            'label' => $this->getDataHelper()->__('Logout'),
            'onclick' => 'unsetToken();'
        ));
        $this->setChild('logout_button', $button);

        //----------------------------------------

        $resource = Mage::getSingleton('core/resource');
        $id = $resource->getConnection('core_read')->select()
            ->from($resource->getTableName('m2e_e2m_cron_tasks_in_processing'), 'id')
            ->where('instance = ?', 'Cron_Task_eBay_DownloadInventory')->query()->fetchColumn();

        $label = empty($id) ? 'Start download inventory' : 'Download inventory (in progress...)';
        $disabled = !empty($id);
        if ($this->getProgressHelper()->isCompletedProgressByTag(M2E_e2M_Helper_Data::EBAY_DOWNLOAD_INVENTORY)) {
            $label = 'Reload inventory (completed)';
            $disabled = false;
        }

        $button = (clone $widgetButton)->setData(array(
            'label' => $this->getDataHelper()->__($label),
            'onclick' => 'startDownloadInventory(this);',
            'disabled' => $disabled
        ));
        $this->setChild('start_download_inventory_button', $button);

        //----------------------------------------

        if ($this->getProgressHelper()->isCompletedProgressByTag(M2E_e2M_Helper_Data::EBAY_DOWNLOAD_INVENTORY)) {
            $button = (clone $widgetButton)->setData(array(
                'label' => Mage::helper('e2m')->__('Save config'),
                'class' => 'save',
                'onclick' => 'sendSettings();'
            ));
            $this->setChild('send_settings_button', $button);
        }

        //----------------------------------------

        $resource = Mage::getSingleton('core/resource');
        $id = $resource->getConnection('core_read')->select()
            ->from($resource->getTableName('m2e_e2m_cron_tasks_in_processing'), 'id')
            ->where('instance = ?', 'Cron_Task_Magento_ImportInventory')->query()->fetchColumn();

        switch (true) {

            case !$this->getConfigHelper()->isFull():
                $label = 'Import inventory (look for settings)';
                $disabled = true;
                break;

            case !empty($id):
                $label = 'Import inventory (in progress...)';
                $disabled = true;
                break;

            case $this->getProgressHelper()->isCompletedProgressByTag(M2E_e2M_Helper_Data::MAGENTO_IMPORT_INVENTORY):
                $label = 'Reimport inventory (completed)';
                $disabled = false;
                break;

            case $this->getConfigHelper()->isFull():
                $label = 'Start import inventory';
                $disabled = false;
                break;
        }

        $button = (clone $widgetButton)->setData(array(
            'label' => Mage::helper('e2m')->__($label),
            'onclick' => 'startImportInventory();',
            'disabled' => $disabled
        ));
        $this->setChild('start_import_inventory_button', $button);
    }

    //########################################

    public function __construct() {
        parent::__construct();

        $this->setTemplate('e2m/main.phtml');
    }
}
