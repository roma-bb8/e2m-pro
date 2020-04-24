<?php

class M2E_E2M_Block_Adminhtml_Main extends Mage_Adminhtml_Block_Widget_Form {

    /**
     * @param Mage_Adminhtml_Block_Widget_Button $button
     * @param string $alias
     * @param string $label
     * @param string $onclick
     * @param bool $disabled
     */
    private function addButton($button, $alias, $label, $onclick, $disabled = false) {
        /** @var Mage_Adminhtml_Block_Widget_Button $button */
        $button = $button->setData(array(
            'label'    => Mage::helper('e2m')->__($label),
            'disabled' => $disabled,
            'onclick'  => $onclick
        ));
        $this->setChild($alias, $button);
    }

    /**
     * @param Mage_Adminhtml_Block_Widget_Button $button
     * @param string $alias
     * @param string $label
     * @param string $onclick
     */
    private function addStartDownloadInventoryButton($button, $alias, $label, $onclick) {

        $disabled = false;
        $id = Mage::getSingleton('core/resource')->getConnection('core_read')
            ->select()->from(Mage::getSingleton('core/resource')->getTableName('m2e_e2m_cron_tasks'), 'id')
            ->where('instance = ?', M2E_E2M_Model_Cron_Task_eBay_DownloadInventory::class)
            ->limit(1)->query()->fetchColumn();

        $isDownload = Mage::helper('e2m')->getConfig(M2E_E2M_Helper_Data::XML_PATH_EBAY_DOWNLOAD_INVENTORY, false);
        if (empty($id) && $isDownload) {
            $label = 'Reload inventory (completed)';
        }

        if (!empty($id)) {
            $label = 'Download inventory (in progress...)';
            $disabled = true;
        }

        $this->addButton($button, $alias, $label, $onclick, $disabled);
    }

    //########################################

    /**
     * @inheritDoc
     */
    protected function _beforeToHtml() {

        /** @var Mage_Adminhtml_Block_Widget_Button $widgetButton */
        $widgetButton = $this->getLayout()->createBlock('adminhtml/widget_button');

        //----------------------------------------

        if (empty(Mage::getSingleton('e2m/Proxy_Ebay_Account')->getUserId())) {
            $this->addButton(
                clone $widgetButton,
                'link_account_button',
                'Link',
                'linkAccount();'
            );

            return;
        }

        $this->addButton(
            clone $widgetButton,
            'unlink_account_button',
            'Logout',
            'unlinkAccount();'
        );

        $this->addStartDownloadInventoryButton(
            clone $widgetButton,
            'start_download_inventory_button',
            'Start download inventory',
            'startDownloadInventory(this);'
        );

        $this->addButton(
            clone $widgetButton,
            'send_settings_button',
            'Save config',
            'sendSettings();'
        );

        $this->addButton(
            clone $widgetButton,
            'attributes_matching_csv_button',
            'build',
            'getAttributesMatchingCSV();'
        );

        $this->addButton(
            clone $widgetButton,
            'attributes_export_csv_button',
            'build',
            'getAttributesExportCSV();'
        );

        $this->addButton(
            clone $widgetButton,
            'attributes_sql_button',
            'build',
            'getAttributesSQL();'
        );

        $this->addButton(
            clone $widgetButton,
            'native_inventory_export_csv_button',
            'native',
            'getNativeInventoryExportCSV();'
        );

        $this->addButton(
            clone $widgetButton,
            'magmi_inventory_export_csv_button',
            'magmi',
            'getMagmiInventoryExportCSV();'
        );
    }

    //########################################

    /**
     * @return Ess_M2ePro_Model_Account[]
     */
    public function getEbayAccounts() {

        $accountsCollection = Mage::helper('M2ePro/Component_Ebay')->getCollection('Account');
        return $accountsCollection->getItems();
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
