<?php
/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * Class M2E_E2M_Block_Adminhtml_Main
 */
class M2E_E2M_Block_Adminhtml_Main extends Mage_Adminhtml_Block_Widget_Form {

    /** @var array $progress */
    private $progress;

    //########################################

    /**
     * @inheritDoc
     * @throws Zend_Db_Statement_Exception
     */
    protected function _beforeToHtml() {

        /** @var Mage_Adminhtml_Block_Widget_Button $widgetButton */
        /** @var Mage_Adminhtml_Block_Widget_Button $button */

        //----------------------------------------

        if (empty($this->getEbayAccount()->get(M2E_E2M_Model_Ebay_Account::TOKEN))) {

            $widgetButton = $this->getLayout()->createBlock('adminhtml/widget_button');
            $button = $widgetButton->setData(array(
                'label' => $this->getDataHelper()->__('Get Token'),
                'onclick' => 'getToken();'
            ));
            $this->setChild('get_token_button', $button);
            return;
        }

        //----------------------------------------

        $this->setChild('log_grid', $this->getLayout()->createBlock('e2m/adminhtml_log_grid'));

        //----------------------------------------

        $widgetButton = $this->getLayout()->createBlock('adminhtml/widget_button');
        $button = $widgetButton->setData(array(
            'label' => $this->getDataHelper()->__('Logout'),
            'onclick' => 'unsetToken();'
        ));
        $this->setChild('logout_button', $button);

        //----------------------------------------

        $widgetButton = $this->getLayout()->createBlock('adminhtml/widget_button');
        $button = $widgetButton->setData(array(
            'label' => $this->getDataHelper()->__('Save config'),
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
            case !empty($id):
                $label = 'Download inventory (in progress...)';
                $disabled = true;
                break;

            case $this->getEbayInventory()->get(M2E_E2M_Model_Ebay_Inventory::PATH_DOWNLOAD_INVENTORY):
                $label = 'Reload inventory (completed)';
                $disabled = false;
                break;

            default:
                $label = 'Start download inventory';
                $disabled = false;
                break;
        }

        $widgetButton = $this->getLayout()->createBlock('adminhtml/widget_button');
        $button = $widgetButton->setData(array(
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
            case !(bool)$this->getEbayConfig()->get(M2E_E2M_Model_Ebay_Config::PATH_FULL_SETTINGS):
                $label = 'Import inventory (look for settings)';
                $disabled = true;
                break;

            case !empty($task['id']):
                $label = 'Import inventory (in progress...)';
                $disabled = true;
                $disabledPause = false;
                break;

            case $this->getEbayInventory()->get(M2E_E2M_Model_Ebay_Inventory::PATH_IMPORT_INVENTORY):
                $label = 'Reimport inventory (completed)';
                $disabled = false;
                break;

            case (bool)$this->getEbayConfig()->get(M2E_E2M_Model_Ebay_Config::PATH_FULL_SETTINGS):
                $label = 'Start import inventory';
                $disabled = false;
                break;
        }

        $widgetButton = $this->getLayout()->createBlock('adminhtml/widget_button');
        $button = $widgetButton->setData(array(
            'label' => $this->getDataHelper()->__($label),
            'onclick' => 'startImportInventory(this);',
            'disabled' => $disabled
        ));
        $this->setChild('start_import_inventory_button', $button);

        $label = 'Proceed Import inventory';
        $onclick = 'pauseFinishImportInventory(this);';
        if (!$task['pause']) {
            $label = 'Pause Import inventory';
            $onclick = 'pauseStartImportInventory(this);';
        }

        $widgetButton = $this->getLayout()->createBlock('adminhtml/widget_button');
        $button = $widgetButton->setData(array(
            'label' => $this->getDataHelper()->__($label),
            'onclick' => $onclick,
            'disabled' => $disabledPause
        ));
        $this->setChild('pause_download_inventory_button', $button);
    }

    //########################################

    /**
     * @param $instance
     *
     * @return string $instance
     */
    public function getProgressByTaskInstance($instance) {

        if (isset($this->progress[$instance])) {
            return $this->progress[$instance];
        }

        //----------------------------------------

        $resource = Mage::getSingleton('core/resource');

        $connRead = $resource->getConnection('core_read');

        $cronTasksInProcessingTableName = $resource->getTableName('m2e_e2m_cron_tasks_in_processing');
        $progress = (int)$connRead->select()->from($cronTasksInProcessingTableName, array('progress'))
            ->where('instance = ?', $instance)->query()->fetchColumn();

        return $this->progress[$instance] = $progress;
    }

    /**
     * @return M2E_E2M_Model_Ebay_Inventory
     */
    public function getEbayInventory() {

        /** @var M2E_E2M_Model_Ebay_Inventory $eBayInventory */
        $eBayInventory = Mage::getSingleton('e2m/Ebay_Inventory');

        return $eBayInventory;
    }

    /**
     * @return M2E_E2M_Model_Ebay_Config
     */
    public function getEbayConfig() {

        /** @var M2E_E2M_Model_Ebay_Config $eBayConfig */
        $eBayConfig = Mage::getSingleton('e2m/Ebay_Config');

        return $eBayConfig;
    }

    /**
     * @return M2E_E2M_Model_Ebay_Account
     */
    public function getEbayAccount() {

        /** @var M2E_E2M_Model_Ebay_Account $eBayAccount */
        $eBayAccount = Mage::getSingleton('e2m/Ebay_Account');

        return $eBayAccount;
    }

    /**
     * @return M2E_E2M_Helper_Data
     */
    public function getDataHelper() {

        /** @var M2E_E2M_Helper_Data $dataHelper */
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

    //########################################

    /**
     * @inheritDoc
     */
    public function __construct() {
        parent::__construct();

        $this->setTemplate('e2m/main.phtml');
    }
}
