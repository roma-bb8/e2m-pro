# e2MPro







<?php if ($this->getEbayInventory()->isDownloadInventory()): ?>
    <div class="entry-edit">
        <div class="entry-edit-head">
            <h4><?php echo $this->getDataHelper()->__('Marketplace settings'); ?></h4>
        </div>
        <div class="fieldset">
            <table class="form-list" cellspacing="0" cellpadding="0">
                <?php foreach ($this->getEbayInventory()->get(M2E_E2M_Model_Ebay_Inventory::PATH_MARKETPLACES) as $marketplaceId): ?>
                    <tr>
                        <td class="label">
                            <label for="<?php echo $marketplaceId; ?>"><?php echo $this->getDataHelper()->__($this->getEbayInventory()->getMarketplaceTitle($marketplaceId)); ?></label>
                        </td>
                        <td class="value" style="width: auto">
                            <select id="<?php echo $marketplaceId; ?>" class="marketplace-store config-input">
                                <option value="">-- Please Select --</option>
                                <option value="<?php echo M2E_E2M_Model_Ebay_Config::SKIP ?>" <?php if ($this->getEbayConfig()->isSkipStore($marketplaceId)) echo ' selected="selected"'; ?>>
                                    Skip
                                </option>
                                <?php foreach ($this->getDataHelper()->getMagentoStores() as $id => $store): ?>
                                    <option value="<?php echo $id; ?>" <?php if ($this->getEbayConfig()->getStoreForMarketplace($marketplaceId) === $id) echo ' selected="selected"'; ?>><?php echo $this->getDataHelper()->__($store); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($this->getEbayInventory()->isDownloadInventory()): ?>
    <div class="entry-edit">
        <div class="entry-edit-head">
            <h4><?php echo $this->getDataHelper()->__('Inventory settings'); ?></h4>
        </div>
        <div class="fieldset">
            <table class="form-list" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="label">
                        <label for="inventory-settings-product-identifier"><?php echo $this->getDataHelper()->__('Product ID'); ?>
                            :</label>
                    </td>
                    <td class="value" style="width: auto">
                        <select id="inventory-settings-product-identifier" class="config-input">
                            <option value="<?php echo M2E_E2M_Model_Ebay_Config::VALUE_SKU_PRODUCT_IDENTIFIER; ?>" <?php if ($this->getEbayConfig()->isSKUProductIdentifier()) echo ' selected="selected"'; ?>><?php echo $this->getDataHelper()->__('SKU'); ?></option>
                            <option value="<?php echo M2E_E2M_Model_Ebay_Config::VALUE_MPN_PRODUCT_IDENTIFIER; ?>" <?php if ($this->getEbayConfig()->isMPNProductIdentifier()) echo ' selected="selected"'; ?>><?php echo $this->getDataHelper()->__('MPN'); ?></option>
                            <option value="<?php echo M2E_E2M_Model_Ebay_Config::VALUE_UPC_PRODUCT_IDENTIFIER; ?>" <?php if ($this->getEbayConfig()->isUPCProductIdentifier()) echo ' selected="selected"'; ?>><?php echo $this->getDataHelper()->__('UPC'); ?></option>
                            <option value="<?php echo M2E_E2M_Model_Ebay_Config::VALUE_EAN_PRODUCT_IDENTIFIER; ?>" <?php if ($this->getEbayConfig()->isEANProductIdentifier()) echo ' selected="selected"'; ?>><?php echo $this->getDataHelper()->__('EAN'); ?></option>
                            <option value="<?php echo M2E_E2M_Model_Ebay_Config::VALUE_GTIN_PRODUCT_IDENTIFIER; ?>" <?php if ($this->getEbayConfig()->isGTINProductIdentifier()) echo ' selected="selected"'; ?>><?php echo $this->getDataHelper()->__('GTIN'); ?></option>
                        </select>
                        <p class="note">
                            <span><?php echo Mage::helper('e2m')->__('Product identifier on different marketplaces'); ?></span>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td class="label">
                        <label for="inventory-settings-action-found"><?php echo $this->getDataHelper()->__('Found a product'); ?>
                            :</label>
                    </td>
                    <td class="value" style="width: auto">
                        <select id="inventory-settings-action-found" class="config-input">
                            <option value="<?php echo M2E_E2M_Model_Ebay_Config::VALUE_IGNORE_ACTION_FOUND; ?>" <?php if ($this->getEbayConfig()->isIgnoreActionFound()) echo ' selected="selected"'; ?>><?php echo $this->getDataHelper()->__('Ignore'); ?></option>
                            <option value="<?php echo M2E_E2M_Model_Ebay_Config::VALUE_UPDATE_ACTION_FOUND; ?>" <?php if ($this->getEbayConfig()->isUpdateActionFound()) echo ' selected="selected"'; ?>><?php echo $this->getDataHelper()->__('Update'); ?></option>
                        </select>
                        <p class="note">
                            <span><?php echo Mage::helper('e2m')->__('Action found a product in Magento inventory'); ?></span>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td class="label">
                        <label for="import-qty"><?php echo $this->getDataHelper()->__('Create Stock item') ?>:</label>
                    </td>
                    <td class="value" style="width: auto">
                        <select id="import-qty" class="config-input">
                            <option value="1" <?php if ($this->getEbayConfig()->isImportQty()) echo ' selected="selected"'; ?>><?php echo $this->getDataHelper()->__('Yes'); ?></option>
                            <option value="0" <?php if (!$this->getEbayConfig()->isImportQty()) echo ' selected="selected"'; ?>><?php echo $this->getDataHelper()->__('No'); ?></option>
                        </select>
                        <p class="note">
                                <span><?php echo Mage::helper('e2m')->__('Create Stock item (Import QTY)'); ?>
                                </span>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($this->getEbayInventory()->isDownloadInventory()): ?>
    <div class="entry-edit">
        <div class="entry-edit-head">
            <h4><?php echo $this->getDataHelper()->__('Product settings'); ?></h4>
        </div>
        <div class="fieldset">
            <table class="form-list" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="label">
                        <label for="generate-sku"><?php echo $this->getDataHelper()->__('Generate SKU') ?>:</label>
                    </td>
                    <td class="value">
                        <select id="generate-sku" class="config-input">
                            <option value="1" <?php if ($this->getEbayConfig()->isGenerateSku()) echo ' selected="selected"'; ?>><?php echo $this->getDataHelper()->__('Yes'); ?></option>
                            <option value="0" <?php if (!$this->getEbayConfig()->isGenerateSku()) echo ' selected="selected"'; ?>><?php echo $this->getDataHelper()->__('No'); ?></option>
                        </select>
                        <p class="note">
                            <span><?php echo Mage::helper('e2m')->__('Generate SKU (if empty)'); ?></span>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td class="label">
                        <label for="import-image"><?php echo $this->getDataHelper()->__('Import image') ?>:</label>
                    </td>
                    <td class="value" style="width: auto">
                        <select id="import-image" class="config-input">
                            <option value="1" <?php if ($this->getEbayConfig()->isImportImage()) echo ' selected="selected"'; ?>><?php echo $this->getDataHelper()->__('Yes'); ?></option>
                            <option value="0" <?php if (!$this->getEbayConfig()->isImportImage()) echo ' selected="selected"'; ?>><?php echo $this->getDataHelper()->__('No'); ?></option>
                        </select>
                        <p class="note">
                            <span><?php echo Mage::helper('e2m')->__('Import image'); ?></span>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td class="label">
                        <label for="delete-html"><?php echo $this->getDataHelper()->__('Delete HTML') ?>:</label>
                    </td>
                    <td class="value" style="width: auto">
                        <select id="delete-html" class="config-input">
                            <option value="1" <?php if ($this->getEbayConfig()->isDeleteHtml()) echo ' selected="selected"'; ?>><?php echo $this->getDataHelper()->__('Yes'); ?></option>
                            <option value="0" <?php if (!$this->getEbayConfig()->isDeleteHtml()) echo ' selected="selected"'; ?>><?php echo $this->getDataHelper()->__('No'); ?></option>
                        </select>
                        <p class="note">
                            <span><?php echo Mage::helper('e2m')->__('Clean HTML in Description'); ?></span>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td class="label">
                        <label for="attribute-set"><?php echo $this->getDataHelper()->__('Attribute Set') ?>:</label>
                    </td>
                    <td class="value" style="width: auto">
                        <select id="attribute-set" class="attribute-set config-input">
                            <option value="">-- Please Select --</option>
                            <?php foreach ($this->getDataHelper()->getAllAttributeSet() as $id => $title): ?>
                                <option value="<?php echo $id; ?>" <?php if ($id == $this->getEbayConfig()->get(M2E_E2M_Model_Ebay_Config::PATH_PRODUCT_ATTRIBUTE_SET)) echo ' selected="selected"'; ?>><?php echo $this->getDataHelper()->__($title); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="note">
                            <span><?php echo Mage::helper('e2m')->__('Attribute Set'); ?></span>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td class="label">
                        <label for="magento-attribute"><?php echo $this->getDataHelper()->__('Magento attributes'); ?>
                            :</label>
                    </td>
                    <td class="value" style="width: auto">
                        <select id="magento-attribute" class="ebay-field-magento-attribute config-input">
                            <option value=""></option>
                            <?php foreach ($this->getDataHelper()->getMagentoAttributes($this->getEbayConfig()->get(M2E_E2M_Model_Ebay_Config::PATH_PRODUCT_ATTRIBUTE_SET)) as $code => $title): ?>
                                <option value="<?php echo $code; ?>"><?php echo $this->getDataHelper()->__($title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="label">
                        <label for="ebay-field"><?php echo $this->getDataHelper()->__('eBay fields'); ?>:</label>
                    </td>
                    <td class="value" style="width: auto">
                        <select id="ebay-field" class="ebay-field-magento-attribute config-input">
                            <option value=""></option>
                            <?php foreach ($this->getEbayInventory()->getEbayFields() as $code => $title): ?>
                                <option value="<?php echo $code; ?>"><?php echo $this->getDataHelper()->__($title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="label">
                        <label><?php echo $this->getDataHelper()->__('Import properties'); ?>:</label>
                    </td>
                    <td class="value" style="width: auto">
                        <table class="form-list" cellspacing="0" cellpadding="0">
                            <tr>
                                <td class="value" style="width: auto">
                                    <label for="ebay-field"><?php echo $this->getDataHelper()->__('eBay fields'); ?></label>
                                </td>
                                <td class="label">
                                    <label for="magento-attribute">/&nbsp;&nbsp;<?php echo $this->getDataHelper()->__('Magento attributes'); ?></label>
                                </td>
                            </tr>
                            <tr>
                                <td class="value" style="width: auto">
                                    <div id="field-attribute-list-e"></div>
                                </td>
                                <td class="label">
                                    <div id="field-attribute-list-m"></div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>
<?php endif; ?>

<div style="display: none" class="config-button">
    <?php echo $this->getChildHtml('send_settings_button'); ?>
    <br/>
    <br/>
</div>

<?php if (!empty($this->getEbayAccount()->get(M2E_E2M_Model_Ebay_Account::TOKEN))): ?>
    <div class="box-left entry-edit" collapseable="no">
        <div class="entry-edit-head">
            <h4 class="icon-head head-edit-form fieldset-legend"><?php echo $this->getDataHelper()->__('Summary inventory'); ?></h4>
        </div>
        <div class="fieldset">
            <div class="hor-scroll">
                <table class="form-list" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="label">
                            <label><?php echo $this->getDataHelper()->__('Total items downloaded'); ?>:</label>
                        </td>
                        <td class="value" style="width: auto">
                            <span id="download-inventory-total-items"><?php echo $this->escapeHtml($this->getEbayInventory()->get(M2E_E2M_Model_Ebay_Inventory::PATH_ITEMS_COUNT_TOTAL)) ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">
                            <label><?php echo $this->getDataHelper()->__('Variation items'); ?>:</label>
                        </td>
                        <td class="value" style="width: auto">
                            <span id="download-inventory-variation-items"><?php echo $this->escapeHtml($this->getEbayInventory()->get(M2E_E2M_Model_Ebay_Inventory::PATH_ITEMS_COUNT_VARIATION)); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">
                            <label><?php echo $this->getDataHelper()->__('Simple items'); ?>:</label>
                        </td>
                        <td class="value" style="width: auto">
                            <span id="download-inventory-simple-items"><?php echo $this->escapeHtml($this->getEbayInventory()->get(M2E_E2M_Model_Ebay_Inventory::PATH_ITEMS_COUNT_SIMPLE)); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">
                            <?php echo $this->getChildHtml('start_download_inventory_button'); ?>
                        </td>
                        <td class="value block-download-inventory-progress" style="width: auto">
                            <span id="download-inventory-progress"><?php echo $this->escapeHtml($this->getProgressByTaskInstance(M2E_e2M_Model_Cron_Task_eBay_DownloadInventory::INSTANCE)); ?></span>%
                        </td>
                    </tr>
                    <?php if ((bool)$this->getEbayInventory()->get(M2E_E2M_Model_Ebay_Inventory::PATH_ITEMS_COUNT_TOTAL)): ?>
                        <tr>
                            <td class="label">
                                <?php echo $this->getChildHtml('start_import_inventory_button'); ?>
                            </td>
                            <td class="value" style="width: auto">
                                <span id="import-inventory-progress"><?php echo $this->escapeHtml($this->getProgressByTaskInstance(M2E_e2M_Model_Cron_Task_Magento_ImportInventory::INSTANCE)); ?></span>%
                            </td>
                        </tr>
                        <tr>
                            <td id="pause-download-inventory-button" class="label">
                                <?php echo $this->getChildHtml('pause_download_inventory_button'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
    <div class="box-right entry-edit" collapseable="no">
        <div class="entry-edit-head">
            <h4 class="icon-head head-edit-form fieldset-legend"><?php echo $this->getDataHelper()->__('Logs'); ?></h4>
        </div>
        <div class="fieldset">
            <div class="hor-scroll">
                <?php echo $this->getChildHtml('log_grid'); ?>
            </div>
        </div>

    </div>
<?php endif; ?>
