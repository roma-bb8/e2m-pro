<?php

/**
 * Class M2E_e2M_Block_Adminhtml_Main
 */
class M2E_e2M_Block_Adminhtml_Main extends Mage_Adminhtml_Block_Widget_Form {

    /**
     * @inheritDoc
     */
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

        $button = (clone $widgetButton)->setData(array(
            'label' => Mage::helper('e2m')->__('Save config'),
            'class' => 'save',
            'onclick' => 'sendSettings();'
        ));
        $this->setChild('send_settings_button', $button);

        //----------------------------------------

        $resource = Mage::getSingleton('core/resource');

        //----------------------------------------

        $id = $resource->getConnection('core_read')->select()
            ->from($resource->getTableName('m2e_e2m_cron_tasks_in_processing'), 'id')
            ->where('instance = ?', 'Cron_Task_eBay_DownloadInventory')->query()->fetchColumn();

        switch (true) {
            case $this->getProgressHelper()->isCompletedProgressByTag(
                M2E_e2M_Model_Cron_Task_eBay_DownloadInventory::TAG
            ):
                $label = 'Reload inventory (completed)';
                $disabled = false;
                break;

            case !empty($id):
                $label = 'Download inventory (in progress...)';
                $disabled = true;
                break;

            default:
                $label = 'Start download inventory';
                $disabled = false;
                break;
        }

        $button = (clone $widgetButton)->setData(array(
            'label' => $this->getDataHelper()->__($label),
            'onclick' => 'startDownloadInventory(this);',
            'disabled' => $disabled
        ));
        $this->setChild('start_download_inventory_button', $button);

        //----------------------------------------

        $task = $resource->getConnection('core_read')->select()
            ->from($resource->getTableName('m2e_e2m_cron_tasks_in_processing'), array('id', 'pause'))
            ->where('instance = ?', 'Cron_Task_Magento_ImportInventory')->limit(1)
            ->query()->fetch(PDO::FETCH_ASSOC);

        $disabledPause = true;
        switch (true) {
            case !$this->getConfigHelper()->isFull():
                $label = 'Import inventory (look for settings)';
                $disabled = true;
                break;

            case !empty($task['id']):
                $label = 'Import inventory (in progress...)';
                $disabled = true;
                $disabledPause = false;
                break;

            case $this->getProgressHelper()->isCompletedProgressByTag(
                M2E_e2M_Model_Cron_Task_Magento_ImportInventory::TAG
            ):
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
            'onclick' => 'startImportInventory(this);',
            'disabled' => $disabled
        ));
        $this->setChild('start_import_inventory_button', $button);

        if (!$task['pause']) {
            $label = 'Pause Import inventory';
            $onclick = 'pauseStartImportInventory(this);';
        } else {
            $label = 'Proceed Import inventory';
            $onclick = 'pauseFinishImportInventory(this);';
        }

        $button = (clone $widgetButton)->setData(array(
            'label' => $this->getDataHelper()->__($label),
            'onclick' => $onclick,
            'disabled' => $disabledPause
        ));
        $this->setChild('pause_download_inventory_button', $button);

        $this->setChild('log_grid', $this->getLayout()->createBlock('e2m/adminhtml_log_grid'));
    }

    //########################################

    /**
     * @return M2E_e2M_Helper_Data
     */
    public function getDataHelper() {

        /** @var M2E_e2M_Helper_Data $dataHelper */
        $dataHelper = Mage::helper('e2m');

        return $dataHelper;
    }

    /**
     * @return Mage_Core_Helper_Data
     */
    public function getCoreHelper() {

        /** @var Mage_Core_Helper_Data $coreHelper */
        $coreHelper = Mage::helper('core');

        return $coreHelper;
    }

    /**
     * @return M2E_e2M_Helper_eBay_Config
     */
    public function getConfigHelper() {

        /** @var M2E_e2M_Helper_eBay_Config $eBayConfigHelper */
        $eBayConfigHelper = Mage::helper('e2m/eBay_Config');

        return $eBayConfigHelper;
    }

    /**
     * @return M2E_e2M_Helper_eBay_Inventory
     */
    public function getInventoryHelper() {

        /** @var M2E_e2M_Helper_eBay_Inventory $eBayInventoryHelper */
        $eBayInventoryHelper = Mage::helper('e2m/eBay_Inventory');

        return $eBayInventoryHelper;
    }

    /**
     * @return M2E_e2M_Helper_eBay_Account
     */
    public function getAccountHelper() {

        /** @var M2E_e2M_Helper_eBay_Account $eBayAccountHelper */
        $eBayAccountHelper = Mage::helper('e2m/eBay_Account');

        return $eBayAccountHelper;
    }

    /**
     * @return M2E_e2M_Helper_Progress
     */
    public function getProgressHelper() {

        /** @var M2E_e2M_Helper_Progress $progressHelper */
        $progressHelper = Mage::helper('e2m/Progress');

        return $progressHelper;
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
