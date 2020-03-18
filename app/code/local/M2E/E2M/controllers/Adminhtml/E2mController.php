<?php

class M2E_E2M_Adminhtml_E2mController extends Mage_Adminhtml_Controller_Action {

    const HTTP_INTERNAL_ERROR = 500;
    const DOES_NOT_APPLY = 'does not apply';

    //########################################

    public function ajaxResponse(array $data) {
        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
            'data' => $data
        )));
    }

    final public function dispatch($action) {

        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error === null) {
                return;
            }

            if (strpos($error['message'], 'deprecated')) {
                return;
            }

            if (strpos($error['message'], 'Too few arguments')) {
                return;
            }

            /** @var M2E_E2M_Helper_Data $dataHelper */
            $dataHelper = Mage::helper('e2m');
            $dataHelper->logException(new Exception(
                "Error: {$error['message']}\nFile: {$error['file']}\nLine: {$error['line']}"
            ));
        });

        try {
            parent::dispatch($action);
        } catch (Exception $e) {
            if ($this->getRequest()->isAjax()) {

                $dataHelper = Mage::helper('e2m');
                $dataHelper->logException($e);

                $response = $this->getResponse();
                $response->setHttpResponseCode(self::HTTP_INTERNAL_ERROR);
                $response->setBody(Mage::helper('core')->jsonEncode(array(
                    'error' => true,
                    'message' => $e->getMessage()
                )));
            }

            $this->_getSession()->addError($e->getMessage());

            $this->_redirect('*/e2m/index');
        }
    }

    //########################################

    //########################################

    public function setSettingsAction() {

        $coreHelper = Mage::helper('core');
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
        $dataHelper->setConfig(
            M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_IMPORT_SPECIFICS,
            $settings['import-specifics'],
            false
        );
        $dataHelper->setConfig(
            M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_IMPORT_RENAME_ATTRIBUTE,
            $settings['rename-attribute-title-for-specifics'],
            false
        );

        //----------------------------------------

        Mage::dispatchEvent('m2e_e2m_change_ebay_settings');

        //----------------------------------------

        $this->_getSession()->addSuccess($dataHelper->__('Save settings'));

        //----------------------------------------

        return $this->ajaxResponse(array(
            'settings' => $settings
        ));
    }

    public function getAttributesBySetIdAction() {

        $setId = (int)Mage::helper('core')->jsonDecode($this->getRequest()->getParam('set_id'));
        return $this->ajaxResponse(array(
            'attributes' => Mage::helper('e2m')->getMagentoAttributes($setId)
        ));
    }

    //########################################

    public function startEbayDownloadInventoryAction() {

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

    public function unlinkEbayAccountAction() {

        $dataHelper = Mage::helper('e2m');

        $resource = Mage::getSingleton('core/resource');
        $connWrite = $resource->getConnection('core_write');
        //$connWrite->truncateTable($resource->getTableName('m2e_e2m_inventory_ebay'));
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

    public function linkEbayAccountAction() {

        $accountId = $this->getRequest()->getParam('account_id');
        if (empty($accountId)) {
            throw new Exception('Account invalid.');
        }

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

    public function indexAction() {

        if ($this->getRequest()->isAjax()) {
            return $this->getResponse()->setBody(
                $this->getLayout()->createBlock('e2m/adminhtml_log_grid')->toHtml()
            );
        }

        //----------------------------------------

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

    //########################################

    public function collectInventoryMagmiAction() {
        $resource = Mage::getSingleton('core/resource');
        $connRead = $resource->getConnection('core_read');

        $pathPrefix = BP . DS . 'var' . DS . 'e2m' . DS;
        $file1 = 'simple_attributes.csv';
        $file2 = 'config_attributes.csv';
        $filePostfix = '_inventory.csv';

        //----------------------------------------

        $dataHelper = Mage::helper('e2m');
        $fieldsAttributes = $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_MAP);
        $eBayItemImagesTableName = $resource->getTableName('m2e_e2m_ebay_item_images');
        $eBayItemsTableName = $resource->getTableName('m2e_e2m_ebay_items');
        $eBayItemVariationsTableName = $resource->getTableName('m2e_e2m_ebay_item_variations');
        $csvHeader = array(
            'attribute_set_code',
            'product_type',
            'visibility',
            'additional_images',
            'additional_attributes',
            'configurable_variations',      // sku=MH01-XS-Black,size=XS,color=Black|sku=MH01-XS-Gray,size=XS,color=Gray|sku=MH01-XS-Orange,size=XS,color=Orange|sku=MH01-S-Black,size=S,color=Black|sku=MH01-S-Gray,size=S,color=Gray|sku=MH01-S-Orange,size=S,color=Orange|sku=MH01-M-Black,size=M,color=Black|sku=MH01-M-Gray,size=M,color=Gray|sku=MH01-M-Orange,size=M,color=Orange|sku=MH01-L-Black,size=L,color=Black|sku=MH01-L-Gray,size=L,color=Gray|sku=MH01-L-Orange,size=L,color=Orange|sku=MH01-XL-Black,size=XL,color=Black|sku=MH01-XL-Gray,size=XL,color=Gray|sku=MH01-XL-Orange,size=XL,color=Orange
            'configurable_variation_labels' // size=Size,color=Color
        );

        //----------------------------------------

        $eBayItemSpecificsTableName = $resource->getTableName('m2e_e2m_ebay_item_specifics');
        $specifics = $connRead->select()->from($eBayItemSpecificsTableName)->reset(Zend_Db_Select::COLUMNS)->columns(array(
            'name',
            "GROUP_CONCAT(DISTINCT value SEPARATOR ',') as value"
        ))->group('name')->query();
        file_put_contents($pathPrefix . $file1, 'code,title,values' . PHP_EOL);
        foreach ($specifics->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (empty($row['name'])) {
                continue;
            }

            $name = $row['name'];
            $value = $row['value'];
            $code = $dataHelper->getCode($name);
            $csvHeader[] = $code;

            file_put_contents($pathPrefix . $file1, "\"{$code}\",\"{$name}\",\"{$value}\"" . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        $eBayItemVariationSpecificsTableName = $resource->getTableName('m2e_e2m_ebay_item_variation_specifics');
        $specifics = $connRead->select()->from($eBayItemVariationSpecificsTableName)->reset(Zend_Db_Select::COLUMNS)->columns(array(
            'name',
            "GROUP_CONCAT(DISTINCT value SEPARATOR ',') as value"
        ))->group('name')->query();
        file_put_contents($pathPrefix . $file2, 'code,title,values' . PHP_EOL);
        foreach ($specifics->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (empty($row['name'])) {
                continue;
            }

            $name = $row['name'];
            $value = $row['value'];
            $code = $dataHelper->getCode($name);
            $csvHeader[] = $code;

            file_put_contents($pathPrefix . $file2, "\"{$code}\",\"{$name}\",\"{$value}\"" . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        $csvHeader = array_merge($csvHeader, array_keys($fieldsAttributes));
        $csvHeader = array_unique($csvHeader);
        sort($csvHeader);
        $productData = array_combine(array_values($csvHeader), array_fill(0, count($csvHeader), '"__EMPTY__VALUE__"'));

        //----------------------------------------

        $sqlI = "SELECT GROUP_CONCAT(`path` SEPARATOR \",\") FROM `{$eBayItemImagesTableName}` WHERE `item_id` = ? GROUP BY `item_id`";
        $inventory = $connRead->select()
            ->from(array('items' => $eBayItemsTableName))
            ->joinLeft(array('variations' => $eBayItemVariationsTableName), '(items.id = variations.item_id)')
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(array(
                'items.*',
                'item_variation_id' => 'variations.id',
                'v_hash' => 'variations.hash',
                'v_sku' => 'variations.sku',
                'v_start_price' => 'variations.start_price',
                'v_quantity' => 'variations.quantity',
                'v_upc' => 'variations.upc',
                'v_ean' => 'variations.ean',
                'v_isbn' => 'variations.isbn',
                'v_ePID' => 'variations.ePID'
            ))
            ->limit(5)
            ->query();

        $variations = array();
        while ($item = $inventory->fetch(PDO::FETCH_ASSOC)) {
            $product = $productData;

            if (!empty($item['v_hash'])) {
                $variations[$item['sku']] = $item;

                $c = $connRead->select()->from($resource->getTableName('m2e_e2m_ebay_item_variation_specifics'))
                    ->where('item_variation_id = ?', $item['item_variation_id'])->query()->fetchAll();
                foreach ($c as $cc) {
                    $code = $dataHelper->getCode($cc['name']);
                    $product[$code] = $dataHelper->getValue($cc['value']);
                    $variations[$item['sku']]['configurable_variations_sku'][$item['v_sku']][$code] = $cc;
                }

                $item['ebay_item_id'] = $item['v_hash'];
                $item['sku'] = $item['v_sku'];
                $item['start_price'] = $item['v_start_price'];
                $item['start_price'] = $item['v_start_price'];
                $item['start_price'] = $item['v_start_price'];
                $item['quantity'] = $item['v_quantity'];
                $item['upc'] = $item['v_upc'];
                $item['ean'] = $item['v_ean'];
                $item['isbn'] = $item['v_isbn'];
                $item['ePID'] = $item['v_ePID'];
            }

            $file = $dataHelper->getFile($item['primary_category_name']);
            if (!file_exists($pathPrefix . $file . $filePostfix)) {
                file_put_contents($pathPrefix . $file . $filePostfix, implode(',', $csvHeader) . PHP_EOL);
            }
            $images = $connRead->query(str_replace('?', $connRead->quote($item['id']), $sqlI))->fetchColumn();

            $product['attribute_set_code'] = '%attribute_set_code%';
            $product['product_type'] = '"simple"';
            $product['visibility'] = '"Not Visible Individually"';
            $product['additional_images'] = $dataHelper->getValue($images);
            foreach ($fieldsAttributes as $magentoAttribute => $eBayField) {
                $product[$magentoAttribute] = $dataHelper->getValue($item[$eBayField]);
            }

            $f = $connRead->select()->from($eBayItemSpecificsTableName)->where('item_id = ?', $item['id'])->query()->fetchAll();
            foreach ($f as $ff) {
                $code = $dataHelper->getCode($ff['name']);
                $product[$code] = $dataHelper->getValue($ff['value']);
            }

            file_put_contents($pathPrefix . $file . $filePostfix, implode(',', $product) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        if (empty($variations)) {
            return $this->ajaxResponse(array('completed' => true));
        }

        foreach ($variations as $sku => $parent) {
            $product = $productData;

            $file = $dataHelper->getFile($parent['primary_category_name']);
            if (!file_exists($pathPrefix . $file . $filePostfix)) {
                file_put_contents($pathPrefix . $file . $filePostfix, implode(',', $csvHeader) . PHP_EOL);
            }
            $images = $connRead->query(str_replace('?', $connRead->quote($parent['id']), $sqlI))->fetchColumn();

            $product['attribute_set_code'] = '%attribute_set_code%';
            $product['product_type'] = '"configurable"';
            $product['visibility'] = '"Not Visible Individually"';
            $product['additional_images'] = $dataHelper->getValue($images);
            foreach ($fieldsAttributes as $magentoAttribute => $eBayField) {
                $product[$magentoAttribute] = $dataHelper->getValue($parent[$eBayField]);
            }

            $f = $connRead->select()->from($eBayItemSpecificsTableName)->where('item_id = ?', $parent['id'])->query()->fetchAll();
            foreach ($f as $ff) {
                $code = $dataHelper->getCode($ff['name']);
                $product[$code] = $dataHelper->getValue($ff['value']);
            }

            $configurableVariations = array();
            $configurableVariationLabels = array();
            foreach ($parent['configurable_variations_sku'] as $skuV => $dataV) {
                $tmp = array();
                $tmp[] = "sku=\"{$skuV}\"";
                foreach ($dataV as $code => $cc) {
                    $name = $cc['name'];
                    $value = $cc['value'];
                    $configurableVariationLabels[$code] = "{$code}=\"{$name}\"";
                    $tmp[$code] = "{$code}=\"{$value}\"";
                }
                $configurableVariations[$skuV] = implode(',', $tmp);
            }

            $product['configurable_variation_labels'] = $dataHelper->getValue(implode(',', $configurableVariationLabels));
            $product['configurable_variations'] = $dataHelper->getValue(implode('|', $configurableVariations));

            file_put_contents($pathPrefix . $file . $filePostfix, implode(',', $product) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        return $this->ajaxResponse(array('completed' => true));
    }

    public function collectInventoryBaseM2Action() {
        $resource = Mage::getSingleton('core/resource');
        $connRead = $resource->getConnection('core_read');

        $pathPrefix = BP . DS . 'var' . DS . 'e2m' . DS;
        $filePostfix = '_inventory.csv';

        //----------------------------------------

        $dataHelper = Mage::helper('e2m');
        $eBayConfigHelper = Mage::helper('e2m/Ebay_Config');
        $fieldsAttributes = $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_MAP);
        $eBayItemImagesTableName = $resource->getTableName('m2e_e2m_ebay_item_images');
        $eBayItemsTableName = $resource->getTableName('m2e_e2m_ebay_items');
        $eBayItemVariationsTableName = $resource->getTableName('m2e_e2m_ebay_item_variations');
        $csvHeader = array(
            'attribute_set_code',
            'product_type',
            'visibility',
            'additional_images',
            'additional_attributes',
            'configurable_variations',      // sku=MH01-XS-Black,size=XS,color=Black|sku=MH01-XS-Gray,size=XS,color=Gray|sku=MH01-XS-Orange,size=XS,color=Orange|sku=MH01-S-Black,size=S,color=Black|sku=MH01-S-Gray,size=S,color=Gray|sku=MH01-S-Orange,size=S,color=Orange|sku=MH01-M-Black,size=M,color=Black|sku=MH01-M-Gray,size=M,color=Gray|sku=MH01-M-Orange,size=M,color=Orange|sku=MH01-L-Black,size=L,color=Black|sku=MH01-L-Gray,size=L,color=Gray|sku=MH01-L-Orange,size=L,color=Orange|sku=MH01-XL-Black,size=XL,color=Black|sku=MH01-XL-Gray,size=XL,color=Gray|sku=MH01-XL-Orange,size=XL,color=Orange
            'configurable_variation_labels' // size=Size,color=Color
        );

        //----------------------------------------

        $eBayItemSpecificsTableName = $resource->getTableName('m2e_e2m_ebay_item_specifics');
        $specifics = $connRead->select()->from($eBayItemSpecificsTableName)->reset(Zend_Db_Select::COLUMNS)->columns(array(
            'name',
            "GROUP_CONCAT(DISTINCT value SEPARATOR ',') as value"
        ))->group('name')->query();
        foreach ($specifics->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (empty($row['name'])) {
                continue;
            }

            $name = $row['name'];
            $code = $dataHelper->getCode($name);
            $csvHeader[] = $code;
        }

        $eBayItemVariationSpecificsTableName = $resource->getTableName('m2e_e2m_ebay_item_variation_specifics');
        $specifics = $connRead->select()->from($eBayItemVariationSpecificsTableName)->reset(Zend_Db_Select::COLUMNS)->columns(array(
            'name',
            "GROUP_CONCAT(DISTINCT value SEPARATOR ',') as value"
        ))->group('name')->query();
        foreach ($specifics->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (empty($row['name'])) {
                continue;
            }

            $name = $row['name'];
            $code = $dataHelper->getCode($name);
            $csvHeader[] = $code;
        }

        $csvHeader = array_merge($csvHeader, array_keys($fieldsAttributes));
        $csvHeader = array_unique($csvHeader);
        sort($csvHeader);
        $productData = array_combine(array_values($csvHeader), array_fill(0, count($csvHeader), '"__EMPTY__VALUE__"'));

        //----------------------------------------

        $sqlI = "SELECT GROUP_CONCAT(`path` SEPARATOR \",\") FROM `{$eBayItemImagesTableName}` WHERE `item_id` = ? GROUP BY `item_id`";
        $inventory = $connRead->select()
            ->from(array('items' => $eBayItemsTableName))
            ->joinLeft(array('variations' => $eBayItemVariationsTableName), '(items.id = variations.item_id)')
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(array(
                'items.*',
                'item_variation_id' => 'variations.id',
                'v_hash' => 'variations.hash',
                'v_sku' => 'variations.sku',
                'v_start_price' => 'variations.start_price',
                'v_quantity' => 'variations.quantity',
                'v_upc' => 'variations.upc',
                'v_ean' => 'variations.ean',
                'v_isbn' => 'variations.isbn',
                'v_ePID' => 'variations.ePID'
            ))
            //->limit(5)
            ->query();

        $variations = array();
        while ($item = $inventory->fetch(PDO::FETCH_ASSOC)) {

            //---
            if ($eBayConfigHelper->isDeleteHtml()) {
                $item['title'] = strip_tags($item['title']);
                $item['subtitle'] = strip_tags($item['subtitle']);
                $item['description'] = strip_tags($item['description']);
            }

            if (empty($data['sku']) && $eBayConfigHelper->isGenerateSku()) {
                $data['sku'] = 'SKU_' . md5($data['ebay_item_id']);
            }

            $productID = $eBayConfigHelper->getProductIdentifier();
            if (empty($data[$productID])) {
                $data[$productID] = 'PID_' . md5($data['ebay_item_id']);
            }

            if (self::DOES_NOT_APPLY === strtolower($data[$productID])) {
                $data[$productID] = 'DNA_' . md5($data['ebay_item_id']);
            }

            //TODO Delete
            if ('refer to description' === strtolower($data[$productID])) {
                $data[$productID] = 'ROD_' . md5($data['ebay_item_id']);
            }
            //---

            $product = $productData;

            if (!empty($item['v_hash'])) {
                $variations[$item['sku']] = $item;

                $c = $connRead->select()->from($resource->getTableName('m2e_e2m_ebay_item_variation_specifics'))
                    ->where('item_variation_id = ?', $item['item_variation_id'])->query()->fetchAll();
                foreach ($c as $cc) {
                    $code = $dataHelper->getCode($cc['name']);
                    $product[$code] = $dataHelper->getValue($cc['value']);
                    $variations[$item['sku']]['configurable_variations_sku'][$item['v_sku']][$code] = $cc;
                }

                $item['ebay_item_id'] = $item['v_hash'];
                $item['sku'] = $item['v_sku'];
                $item['start_price'] = $item['v_start_price'];
                $item['start_price'] = $item['v_start_price'];
                $item['start_price'] = $item['v_start_price'];
                $item['quantity'] = $item['v_quantity'];
                $item['upc'] = $item['v_upc'];
                $item['ean'] = $item['v_ean'];
                $item['isbn'] = $item['v_isbn'];
                $item['ePID'] = $item['v_ePID'];
            }

            $file = $dataHelper->getFile($item['primary_category_name']);
            if (!file_exists($pathPrefix . $file . $filePostfix)) {
                file_put_contents($pathPrefix . $file . $filePostfix, implode(',', $csvHeader) . PHP_EOL);
            }
            $images = $connRead->query(str_replace('?', $connRead->quote($item['id']), $sqlI))->fetchColumn();

            $product['attribute_set_code'] = '%attribute_set_code%';
            $product['product_type'] = '"simple"';
            $product['visibility'] = '"Not Visible Individually"';
            $product['additional_images'] = $dataHelper->getValue($images);
            foreach ($fieldsAttributes as $magentoAttribute => $eBayField) {
                $product[$magentoAttribute] = $dataHelper->getValue($item[$eBayField]);
            }

            $f = $connRead->select()->from($eBayItemSpecificsTableName)->where('item_id = ?', $item['id'])->query()->fetchAll();
            foreach ($f as $ff) {
                $code = $dataHelper->getCode($ff['name']);
                $product[$code] = $dataHelper->getValue($ff['value']);
            }

            file_put_contents($pathPrefix . $file . $filePostfix, implode(',', $product) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        if (empty($variations)) {
            return $this->ajaxResponse(array('completed' => true));
        }

        foreach ($variations as $sku => $parent) {
            $product = $productData;

            $file = $dataHelper->getFile($parent['primary_category_name']);
            if (!file_exists($pathPrefix . $file . $filePostfix)) {
                file_put_contents($pathPrefix . $file . $filePostfix, implode(',', $csvHeader) . PHP_EOL);
            }
            $images = $connRead->query(str_replace('?', $connRead->quote($parent['id']), $sqlI))->fetchColumn();

            $product['attribute_set_code'] = '%attribute_set_code%';
            $product['product_type'] = '"configurable"';
            $product['visibility'] = '"Not Visible Individually"';
            $product['additional_images'] = $dataHelper->getValue($images);
            foreach ($fieldsAttributes as $magentoAttribute => $eBayField) {
                $product[$magentoAttribute] = $dataHelper->getValue($parent[$eBayField]);
            }

            $f = $connRead->select()->from($eBayItemSpecificsTableName)->where('item_id = ?', $parent['id'])->query()->fetchAll();
            foreach ($f as $ff) {
                $code = $dataHelper->getCode($ff['name']);
                $product[$code] = $dataHelper->getValue($ff['value']);
            }

            $configurableVariations = array();
            $configurableVariationLabels = array();
            foreach ($parent['configurable_variations_sku'] as $skuV => $dataV) {
                $tmp = array();
                $tmp[] = "sku=\"{$skuV}\"";
                foreach ($dataV as $code => $cc) {
                    $name = $cc['name'];
                    $value = $cc['value'];
                    $configurableVariationLabels[$code] = "{$code}=\"{$name}\"";
                    $tmp[$code] = "{$code}=\"{$value}\"";
                }
                $configurableVariations[$skuV] = implode(',', $tmp);
            }

            $product['configurable_variation_labels'] = $dataHelper->getValue(implode(',', $configurableVariationLabels));
            $product['configurable_variations'] = $dataHelper->getValue(implode('|', $configurableVariations));

            file_put_contents($pathPrefix . $file . $filePostfix, implode(',', $product) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        return $this->ajaxResponse(array('completed' => true));
    }

    public function collectInventoryBaseM1Action() {

        $resource = Mage::getSingleton('core/resource');
        $connRead = $resource->getConnection('core_read');

        $pathPrefix = BP . DS . 'var' . DS . 'e2m' . DS;
        $file1 = 'simple_attributes.csv';
        $file2 = 'config_attributes.csv';
        $filePostfix = '_inventory.csv';

        //----------------------------------------

        $dataHelper = Mage::helper('e2m');
        $fieldsAttributes = $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_MAP);
        $eBayItemImagesTableName = $resource->getTableName('m2e_e2m_ebay_item_images');
        $eBayItemsTableName = $resource->getTableName('m2e_e2m_ebay_items');
        $eBayItemVariationsTableName = $resource->getTableName('m2e_e2m_ebay_item_variations');
        $csvHeader = array(
            'attribute_set_code',
            'product_type',
            'visibility',
            'additional_images',
            'additional_attributes',
            'configurable_variations',      // sku=MH01-XS-Black,size=XS,color=Black|sku=MH01-XS-Gray,size=XS,color=Gray|sku=MH01-XS-Orange,size=XS,color=Orange|sku=MH01-S-Black,size=S,color=Black|sku=MH01-S-Gray,size=S,color=Gray|sku=MH01-S-Orange,size=S,color=Orange|sku=MH01-M-Black,size=M,color=Black|sku=MH01-M-Gray,size=M,color=Gray|sku=MH01-M-Orange,size=M,color=Orange|sku=MH01-L-Black,size=L,color=Black|sku=MH01-L-Gray,size=L,color=Gray|sku=MH01-L-Orange,size=L,color=Orange|sku=MH01-XL-Black,size=XL,color=Black|sku=MH01-XL-Gray,size=XL,color=Gray|sku=MH01-XL-Orange,size=XL,color=Orange
            'configurable_variation_labels' // size=Size,color=Color
        );

        //----------------------------------------

        $eBayItemSpecificsTableName = $resource->getTableName('m2e_e2m_ebay_item_specifics');
        $specifics = $connRead->select()->from($eBayItemSpecificsTableName)->reset(Zend_Db_Select::COLUMNS)->columns(array(
            'name',
            "GROUP_CONCAT(DISTINCT value SEPARATOR ',') as value"
        ))->group('name')->query();
        file_put_contents($pathPrefix . $file1, 'code,title,values' . PHP_EOL);
        foreach ($specifics->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (empty($row['name'])) {
                continue;
            }

            $name = $row['name'];
            $value = $row['value'];
            $code = $dataHelper->getCode($name);
            $csvHeader[] = $code;

            file_put_contents($pathPrefix . $file1, "\"{$code}\",\"{$name}\",\"{$value}\"" . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        $eBayItemVariationSpecificsTableName = $resource->getTableName('m2e_e2m_ebay_item_variation_specifics');
        $specifics = $connRead->select()->from($eBayItemVariationSpecificsTableName)->reset(Zend_Db_Select::COLUMNS)->columns(array(
            'name',
            "GROUP_CONCAT(DISTINCT value SEPARATOR ',') as value"
        ))->group('name')->query();
        file_put_contents($pathPrefix . $file2, 'code,title,values' . PHP_EOL);
        foreach ($specifics->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (empty($row['name'])) {
                continue;
            }

            $name = $row['name'];
            $value = $row['value'];
            $code = $dataHelper->getCode($name);
            $csvHeader[] = $code;

            file_put_contents($pathPrefix . $file2, "\"{$code}\",\"{$name}\",\"{$value}\"" . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        $csvHeader = array_merge($csvHeader, array_keys($fieldsAttributes));
        $csvHeader = array_unique($csvHeader);
        sort($csvHeader);
        $productData = array_combine(array_values($csvHeader), array_fill(0, count($csvHeader), '"__EMPTY__VALUE__"'));

        //----------------------------------------

        $sqlI = "SELECT GROUP_CONCAT(`path` SEPARATOR \",\") FROM `{$eBayItemImagesTableName}` WHERE `item_id` = ? GROUP BY `item_id`";
        $inventory = $connRead->select()
            ->from(array('items' => $eBayItemsTableName))
            ->joinLeft(array('variations' => $eBayItemVariationsTableName), '(items.id = variations.item_id)')
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(array(
                'items.*',
                'item_variation_id' => 'variations.id',
                'v_hash' => 'variations.hash',
                'v_sku' => 'variations.sku',
                'v_start_price' => 'variations.start_price',
                'v_quantity' => 'variations.quantity',
                'v_upc' => 'variations.upc',
                'v_ean' => 'variations.ean',
                'v_isbn' => 'variations.isbn',
                'v_ePID' => 'variations.ePID'
            ))
            ->limit(5)
            ->query();

        $variations = array();
        while ($item = $inventory->fetch(PDO::FETCH_ASSOC)) {
            $product = $productData;

            if (!empty($item['v_hash'])) {
                $variations[$item['sku']] = $item;

                $c = $connRead->select()->from($resource->getTableName('m2e_e2m_ebay_item_variation_specifics'))
                    ->where('item_variation_id = ?', $item['item_variation_id'])->query()->fetchAll();
                foreach ($c as $cc) {
                    $code = $dataHelper->getCode($cc['name']);
                    $product[$code] = $dataHelper->getValue($cc['value']);
                    $variations[$item['sku']]['configurable_variations_sku'][$item['v_sku']][$code] = $cc;
                }

                $item['ebay_item_id'] = $item['v_hash'];
                $item['sku'] = $item['v_sku'];
                $item['start_price'] = $item['v_start_price'];
                $item['start_price'] = $item['v_start_price'];
                $item['start_price'] = $item['v_start_price'];
                $item['quantity'] = $item['v_quantity'];
                $item['upc'] = $item['v_upc'];
                $item['ean'] = $item['v_ean'];
                $item['isbn'] = $item['v_isbn'];
                $item['ePID'] = $item['v_ePID'];
            }

            $file = $dataHelper->getFile($item['primary_category_name']);
            if (!file_exists($pathPrefix . $file . $filePostfix)) {
                file_put_contents($pathPrefix . $file . $filePostfix, implode(',', $csvHeader) . PHP_EOL);
            }
            $images = $connRead->query(str_replace('?', $connRead->quote($item['id']), $sqlI))->fetchColumn();

            $product['attribute_set_code'] = '%attribute_set_code%';
            $product['product_type'] = '"simple"';
            $product['visibility'] = '"Not Visible Individually"';
            $product['additional_images'] = $dataHelper->getValue($images);
            foreach ($fieldsAttributes as $magentoAttribute => $eBayField) {
                $product[$magentoAttribute] = $dataHelper->getValue($item[$eBayField]);
            }

            $f = $connRead->select()->from($eBayItemSpecificsTableName)->where('item_id = ?', $item['id'])->query()->fetchAll();
            foreach ($f as $ff) {
                $code = $dataHelper->getCode($ff['name']);
                $product[$code] = $dataHelper->getValue($ff['value']);
            }

            file_put_contents($pathPrefix . $file . $filePostfix, implode(',', $product) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        if (empty($variations)) {
            return $this->ajaxResponse(array('completed' => true));
        }

        foreach ($variations as $sku => $parent) {
            $product = $productData;

            $file = $dataHelper->getFile($parent['primary_category_name']);
            if (!file_exists($pathPrefix . $file . $filePostfix)) {
                file_put_contents($pathPrefix . $file . $filePostfix, implode(',', $csvHeader) . PHP_EOL);
            }
            $images = $connRead->query(str_replace('?', $connRead->quote($parent['id']), $sqlI))->fetchColumn();

            $product['attribute_set_code'] = '%attribute_set_code%';
            $product['product_type'] = '"configurable"';
            $product['visibility'] = '"Not Visible Individually"';
            $product['additional_images'] = $dataHelper->getValue($images);
            foreach ($fieldsAttributes as $magentoAttribute => $eBayField) {
                $product[$magentoAttribute] = $dataHelper->getValue($parent[$eBayField]);
            }

            $f = $connRead->select()->from($eBayItemSpecificsTableName)->where('item_id = ?', $parent['id'])->query()->fetchAll();
            foreach ($f as $ff) {
                $code = $dataHelper->getCode($ff['name']);
                $product[$code] = $dataHelper->getValue($ff['value']);
            }

            $configurableVariations = array();
            $configurableVariationLabels = array();
            foreach ($parent['configurable_variations_sku'] as $skuV => $dataV) {
                $tmp = array();
                $tmp[] = "sku=\"{$skuV}\"";
                foreach ($dataV as $code => $cc) {
                    $name = $cc['name'];
                    $value = $cc['value'];
                    $configurableVariationLabels[$code] = "{$code}=\"{$name}\"";
                    $tmp[$code] = "{$code}=\"{$value}\"";
                }
                $configurableVariations[$skuV] = implode(',', $tmp);
            }

            $product['configurable_variation_labels'] = $dataHelper->getValue(implode(',', $configurableVariationLabels));
            $product['configurable_variations'] = $dataHelper->getValue(implode('|', $configurableVariations));

            file_put_contents($pathPrefix . $file . $filePostfix, implode(',', $product) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        return $this->ajaxResponse(array('completed' => true));
    }

    public function collectAttributesM2Action() {

        $resource = Mage::getSingleton('core/resource');
        $connRead = $resource->getConnection('core_read');

        $pathPrefix = BP . DS . 'var' . DS . 'e2m' . DS;
        $file1 = 'simple_attributes.csv';
        $file2 = 'config_attributes.csv';
        $filePostfix = '_inventory.csv';

        //----------------------------------------

        $dataHelper = Mage::helper('e2m');
        $fieldsAttributes = $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_MAP);
        $eBayItemImagesTableName = $resource->getTableName('m2e_e2m_ebay_item_images');
        $eBayItemsTableName = $resource->getTableName('m2e_e2m_ebay_items');
        $eBayItemVariationsTableName = $resource->getTableName('m2e_e2m_ebay_item_variations');
        $csvHeader = array(
            'attribute_set_code',
            'product_type',
            'visibility',
            'additional_images',
            'additional_attributes',
            'configurable_variations',      // sku=MH01-XS-Black,size=XS,color=Black|sku=MH01-XS-Gray,size=XS,color=Gray|sku=MH01-XS-Orange,size=XS,color=Orange|sku=MH01-S-Black,size=S,color=Black|sku=MH01-S-Gray,size=S,color=Gray|sku=MH01-S-Orange,size=S,color=Orange|sku=MH01-M-Black,size=M,color=Black|sku=MH01-M-Gray,size=M,color=Gray|sku=MH01-M-Orange,size=M,color=Orange|sku=MH01-L-Black,size=L,color=Black|sku=MH01-L-Gray,size=L,color=Gray|sku=MH01-L-Orange,size=L,color=Orange|sku=MH01-XL-Black,size=XL,color=Black|sku=MH01-XL-Gray,size=XL,color=Gray|sku=MH01-XL-Orange,size=XL,color=Orange
            'configurable_variation_labels' // size=Size,color=Color
        );

        //----------------------------------------

        $eBayItemSpecificsTableName = $resource->getTableName('m2e_e2m_ebay_item_specifics');
        $specifics = $connRead->select()->from($eBayItemSpecificsTableName)->reset(Zend_Db_Select::COLUMNS)->columns(array(
            'name',
            "GROUP_CONCAT(DISTINCT value SEPARATOR ',') as value"
        ))->group('name')->query();
        file_put_contents($pathPrefix . $file1, 'code,title,values' . PHP_EOL);
        foreach ($specifics->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (empty($row['name'])) {
                continue;
            }

            $name = $row['name'];
            $value = $row['value'];
            $code = $dataHelper->getCode($name);
            $csvHeader[] = $code;

            file_put_contents($pathPrefix . $file1, "\"{$code}\",\"{$name}\",\"{$value}\"" . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        $eBayItemVariationSpecificsTableName = $resource->getTableName('m2e_e2m_ebay_item_variation_specifics');
        $specifics = $connRead->select()->from($eBayItemVariationSpecificsTableName)->reset(Zend_Db_Select::COLUMNS)->columns(array(
            'name',
            "GROUP_CONCAT(DISTINCT value SEPARATOR ',') as value"
        ))->group('name')->query();
        file_put_contents($pathPrefix . $file2, 'code,title,values' . PHP_EOL);
        foreach ($specifics->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (empty($row['name'])) {
                continue;
            }

            $name = $row['name'];
            $value = $row['value'];
            $code = $dataHelper->getCode($name);
            $csvHeader[] = $code;

            file_put_contents($pathPrefix . $file2, "\"{$code}\",\"{$name}\",\"{$value}\"" . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        $csvHeader = array_merge($csvHeader, array_keys($fieldsAttributes));
        $csvHeader = array_unique($csvHeader);
        sort($csvHeader);
        $productData = array_combine(array_values($csvHeader), array_fill(0, count($csvHeader), '"__EMPTY__VALUE__"'));

        //----------------------------------------

        $sqlI = "SELECT GROUP_CONCAT(`path` SEPARATOR \",\") FROM `{$eBayItemImagesTableName}` WHERE `item_id` = ? GROUP BY `item_id`";
        $inventory = $connRead->select()
            ->from(array('items' => $eBayItemsTableName))
            ->joinLeft(array('variations' => $eBayItemVariationsTableName), '(items.id = variations.item_id)')
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(array(
                'items.*',
                'item_variation_id' => 'variations.id',
                'v_hash' => 'variations.hash',
                'v_sku' => 'variations.sku',
                'v_start_price' => 'variations.start_price',
                'v_quantity' => 'variations.quantity',
                'v_upc' => 'variations.upc',
                'v_ean' => 'variations.ean',
                'v_isbn' => 'variations.isbn',
                'v_ePID' => 'variations.ePID'
            ))
            ->limit(5)
            ->query();

        $variations = array();
        while ($item = $inventory->fetch(PDO::FETCH_ASSOC)) {
            $product = $productData;

            if (!empty($item['v_hash'])) {
                $variations[$item['sku']] = $item;

                $c = $connRead->select()->from($resource->getTableName('m2e_e2m_ebay_item_variation_specifics'))
                    ->where('item_variation_id = ?', $item['item_variation_id'])->query()->fetchAll();
                foreach ($c as $cc) {
                    $code = $dataHelper->getCode($cc['name']);
                    $product[$code] = $dataHelper->getValue($cc['value']);
                    $variations[$item['sku']]['configurable_variations_sku'][$item['v_sku']][$code] = $cc;
                }

                $item['ebay_item_id'] = $item['v_hash'];
                $item['sku'] = $item['v_sku'];
                $item['start_price'] = $item['v_start_price'];
                $item['start_price'] = $item['v_start_price'];
                $item['start_price'] = $item['v_start_price'];
                $item['quantity'] = $item['v_quantity'];
                $item['upc'] = $item['v_upc'];
                $item['ean'] = $item['v_ean'];
                $item['isbn'] = $item['v_isbn'];
                $item['ePID'] = $item['v_ePID'];
            }

            $file = $dataHelper->getFile($item['primary_category_name']);
            if (!file_exists($pathPrefix . $file . $filePostfix)) {
                file_put_contents($pathPrefix . $file . $filePostfix, implode(',', $csvHeader) . PHP_EOL);
            }
            $images = $connRead->query(str_replace('?', $connRead->quote($item['id']), $sqlI))->fetchColumn();

            $product['attribute_set_code'] = '%attribute_set_code%';
            $product['product_type'] = '"simple"';
            $product['visibility'] = '"Not Visible Individually"';
            $product['additional_images'] = $dataHelper->getValue($images);
            foreach ($fieldsAttributes as $magentoAttribute => $eBayField) {
                $product[$magentoAttribute] = $dataHelper->getValue($item[$eBayField]);
            }

            $f = $connRead->select()->from($eBayItemSpecificsTableName)->where('item_id = ?', $item['id'])->query()->fetchAll();
            foreach ($f as $ff) {
                $code = $dataHelper->getCode($ff['name']);
                $product[$code] = $dataHelper->getValue($ff['value']);
            }

            file_put_contents($pathPrefix . $file . $filePostfix, implode(',', $product) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        if (empty($variations)) {
            return $this->ajaxResponse(array('completed' => true));
        }

        foreach ($variations as $sku => $parent) {
            $product = $productData;

            $file = $dataHelper->getFile($parent['primary_category_name']);
            if (!file_exists($pathPrefix . $file . $filePostfix)) {
                file_put_contents($pathPrefix . $file . $filePostfix, implode(',', $csvHeader) . PHP_EOL);
            }
            $images = $connRead->query(str_replace('?', $connRead->quote($parent['id']), $sqlI))->fetchColumn();

            $product['attribute_set_code'] = '%attribute_set_code%';
            $product['product_type'] = '"configurable"';
            $product['visibility'] = '"Not Visible Individually"';
            $product['additional_images'] = $dataHelper->getValue($images);
            foreach ($fieldsAttributes as $magentoAttribute => $eBayField) {
                $product[$magentoAttribute] = $dataHelper->getValue($parent[$eBayField]);
            }

            $f = $connRead->select()->from($eBayItemSpecificsTableName)->where('item_id = ?', $parent['id'])->query()->fetchAll();
            foreach ($f as $ff) {
                $code = $dataHelper->getCode($ff['name']);
                $product[$code] = $dataHelper->getValue($ff['value']);
            }

            $configurableVariations = array();
            $configurableVariationLabels = array();
            foreach ($parent['configurable_variations_sku'] as $skuV => $dataV) {
                $tmp = array();
                $tmp[] = "sku=\"{$skuV}\"";
                foreach ($dataV as $code => $cc) {
                    $name = $cc['name'];
                    $value = $cc['value'];
                    $configurableVariationLabels[$code] = "{$code}=\"{$name}\"";
                    $tmp[$code] = "{$code}=\"{$value}\"";
                }
                $configurableVariations[$skuV] = implode(',', $tmp);
            }

            $product['configurable_variation_labels'] = $dataHelper->getValue(implode(',', $configurableVariationLabels));
            $product['configurable_variations'] = $dataHelper->getValue(implode('|', $configurableVariations));

            file_put_contents($pathPrefix . $file . $filePostfix, implode(',', $product) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        return $this->ajaxResponse(array('completed' => true));
    }

    public function collectAttributesM1Action() {

        $resource = Mage::getSingleton('core/resource');
        $connRead = $resource->getConnection('core_read');

        $pathPrefix = BP . DS . 'var' . DS . 'e2m' . DS;
        $file1 = 'simple_attributes.csv';
        $file2 = 'config_attributes.csv';
        $filePostfix = '_inventory.csv';

        //----------------------------------------

        $dataHelper = Mage::helper('e2m');
        $fieldsAttributes = $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_MAP);
        $eBayItemImagesTableName = $resource->getTableName('m2e_e2m_ebay_item_images');
        $eBayItemsTableName = $resource->getTableName('m2e_e2m_ebay_items');
        $eBayItemVariationsTableName = $resource->getTableName('m2e_e2m_ebay_item_variations');
        $csvHeader = array(
            'attribute_set_code',
            'product_type',
            'visibility',
            'additional_images',
            'additional_attributes',
            'configurable_variations',      // sku=MH01-XS-Black,size=XS,color=Black|sku=MH01-XS-Gray,size=XS,color=Gray|sku=MH01-XS-Orange,size=XS,color=Orange|sku=MH01-S-Black,size=S,color=Black|sku=MH01-S-Gray,size=S,color=Gray|sku=MH01-S-Orange,size=S,color=Orange|sku=MH01-M-Black,size=M,color=Black|sku=MH01-M-Gray,size=M,color=Gray|sku=MH01-M-Orange,size=M,color=Orange|sku=MH01-L-Black,size=L,color=Black|sku=MH01-L-Gray,size=L,color=Gray|sku=MH01-L-Orange,size=L,color=Orange|sku=MH01-XL-Black,size=XL,color=Black|sku=MH01-XL-Gray,size=XL,color=Gray|sku=MH01-XL-Orange,size=XL,color=Orange
            'configurable_variation_labels' // size=Size,color=Color
        );

        //----------------------------------------

        $eBayItemSpecificsTableName = $resource->getTableName('m2e_e2m_ebay_item_specifics');
        $specifics = $connRead->select()->from($eBayItemSpecificsTableName)->reset(Zend_Db_Select::COLUMNS)->columns(array(
            'name',
            "GROUP_CONCAT(DISTINCT value SEPARATOR ',') as value"
        ))->group('name')->query();
        file_put_contents($pathPrefix . $file1, 'code,title,values' . PHP_EOL);
        foreach ($specifics->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (empty($row['name'])) {
                continue;
            }

            $name = $row['name'];
            $value = $row['value'];
            $code = $dataHelper->getCode($name);
            $csvHeader[] = $code;

            file_put_contents($pathPrefix . $file1, "\"{$code}\",\"{$name}\",\"{$value}\"" . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        $eBayItemVariationSpecificsTableName = $resource->getTableName('m2e_e2m_ebay_item_variation_specifics');
        $specifics = $connRead->select()->from($eBayItemVariationSpecificsTableName)->reset(Zend_Db_Select::COLUMNS)->columns(array(
            'name',
            "GROUP_CONCAT(DISTINCT value SEPARATOR ',') as value"
        ))->group('name')->query();
        file_put_contents($pathPrefix . $file2, 'code,title,values' . PHP_EOL);
        foreach ($specifics->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (empty($row['name'])) {
                continue;
            }

            $name = $row['name'];
            $value = $row['value'];
            $code = $dataHelper->getCode($name);
            $csvHeader[] = $code;

            file_put_contents($pathPrefix . $file2, "\"{$code}\",\"{$name}\",\"{$value}\"" . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        $csvHeader = array_merge($csvHeader, array_keys($fieldsAttributes));
        $csvHeader = array_unique($csvHeader);
        sort($csvHeader);
        $productData = array_combine(array_values($csvHeader), array_fill(0, count($csvHeader), '"__EMPTY__VALUE__"'));

        //----------------------------------------

        $sqlI = "SELECT GROUP_CONCAT(`path` SEPARATOR \",\") FROM `{$eBayItemImagesTableName}` WHERE `item_id` = ? GROUP BY `item_id`";
        $inventory = $connRead->select()
            ->from(array('items' => $eBayItemsTableName))
            ->joinLeft(array('variations' => $eBayItemVariationsTableName), '(items.id = variations.item_id)')
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(array(
                'items.*',
                'item_variation_id' => 'variations.id',
                'v_hash' => 'variations.hash',
                'v_sku' => 'variations.sku',
                'v_start_price' => 'variations.start_price',
                'v_quantity' => 'variations.quantity',
                'v_upc' => 'variations.upc',
                'v_ean' => 'variations.ean',
                'v_isbn' => 'variations.isbn',
                'v_ePID' => 'variations.ePID'
            ))
            ->limit(5)
            ->query();

        $variations = array();
        while ($item = $inventory->fetch(PDO::FETCH_ASSOC)) {
            $product = $productData;

            if (!empty($item['v_hash'])) {
                $variations[$item['sku']] = $item;

                $c = $connRead->select()->from($resource->getTableName('m2e_e2m_ebay_item_variation_specifics'))
                    ->where('item_variation_id = ?', $item['item_variation_id'])->query()->fetchAll();
                foreach ($c as $cc) {
                    $code = $dataHelper->getCode($cc['name']);
                    $product[$code] = $dataHelper->getValue($cc['value']);
                    $variations[$item['sku']]['configurable_variations_sku'][$item['v_sku']][$code] = $cc;
                }

                $item['ebay_item_id'] = $item['v_hash'];
                $item['sku'] = $item['v_sku'];
                $item['start_price'] = $item['v_start_price'];
                $item['start_price'] = $item['v_start_price'];
                $item['start_price'] = $item['v_start_price'];
                $item['quantity'] = $item['v_quantity'];
                $item['upc'] = $item['v_upc'];
                $item['ean'] = $item['v_ean'];
                $item['isbn'] = $item['v_isbn'];
                $item['ePID'] = $item['v_ePID'];
            }

            $file = $dataHelper->getFile($item['primary_category_name']);
            if (!file_exists($pathPrefix . $file . $filePostfix)) {
                file_put_contents($pathPrefix . $file . $filePostfix, implode(',', $csvHeader) . PHP_EOL);
            }
            $images = $connRead->query(str_replace('?', $connRead->quote($item['id']), $sqlI))->fetchColumn();

            $product['attribute_set_code'] = '%attribute_set_code%';
            $product['product_type'] = '"simple"';
            $product['visibility'] = '"Not Visible Individually"';
            $product['additional_images'] = $dataHelper->getValue($images);
            foreach ($fieldsAttributes as $magentoAttribute => $eBayField) {
                $product[$magentoAttribute] = $dataHelper->getValue($item[$eBayField]);
            }

            $f = $connRead->select()->from($eBayItemSpecificsTableName)->where('item_id = ?', $item['id'])->query()->fetchAll();
            foreach ($f as $ff) {
                $code = $dataHelper->getCode($ff['name']);
                $product[$code] = $dataHelper->getValue($ff['value']);
            }

            file_put_contents($pathPrefix . $file . $filePostfix, implode(',', $product) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        if (empty($variations)) {
            return $this->ajaxResponse(array('completed' => true));
        }

        foreach ($variations as $sku => $parent) {
            $product = $productData;

            $file = $dataHelper->getFile($parent['primary_category_name']);
            if (!file_exists($pathPrefix . $file . $filePostfix)) {
                file_put_contents($pathPrefix . $file . $filePostfix, implode(',', $csvHeader) . PHP_EOL);
            }
            $images = $connRead->query(str_replace('?', $connRead->quote($parent['id']), $sqlI))->fetchColumn();

            $product['attribute_set_code'] = '%attribute_set_code%';
            $product['product_type'] = '"configurable"';
            $product['visibility'] = '"Not Visible Individually"';
            $product['additional_images'] = $dataHelper->getValue($images);
            foreach ($fieldsAttributes as $magentoAttribute => $eBayField) {
                $product[$magentoAttribute] = $dataHelper->getValue($parent[$eBayField]);
            }

            $f = $connRead->select()->from($eBayItemSpecificsTableName)->where('item_id = ?', $parent['id'])->query()->fetchAll();
            foreach ($f as $ff) {
                $code = $dataHelper->getCode($ff['name']);
                $product[$code] = $dataHelper->getValue($ff['value']);
            }

            $configurableVariations = array();
            $configurableVariationLabels = array();
            foreach ($parent['configurable_variations_sku'] as $skuV => $dataV) {
                $tmp = array();
                $tmp[] = "sku=\"{$skuV}\"";
                foreach ($dataV as $code => $cc) {
                    $name = $cc['name'];
                    $value = $cc['value'];
                    $configurableVariationLabels[$code] = "{$code}=\"{$name}\"";
                    $tmp[$code] = "{$code}=\"{$value}\"";
                }
                $configurableVariations[$skuV] = implode(',', $tmp);
            }

            $product['configurable_variation_labels'] = $dataHelper->getValue(implode(',', $configurableVariationLabels));
            $product['configurable_variations'] = $dataHelper->getValue(implode('|', $configurableVariations));

            file_put_contents($pathPrefix . $file . $filePostfix, implode(',', $product) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        return $this->ajaxResponse(array('completed' => true));
    }

    public function collectAttributesCSVAction() {

        $resource = Mage::getSingleton('core/resource');
        $connRead = $resource->getConnection('core_read');

        $pathPrefix = BP . DS . 'var' . DS . 'e2m' . DS;
        $file1 = 'simple_attributes.csv';
        $file2 = 'config_attributes.csv';
        $filePostfix = '_inventory.csv';

        //----------------------------------------

        $dataHelper = Mage::helper('e2m');
        $fieldsAttributes = $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_MAP);
        $eBayItemImagesTableName = $resource->getTableName('m2e_e2m_ebay_item_images');
        $eBayItemsTableName = $resource->getTableName('m2e_e2m_ebay_items');
        $eBayItemVariationsTableName = $resource->getTableName('m2e_e2m_ebay_item_variations');
        $csvHeader = array(
            'attribute_set_code',
            'product_type',
            'visibility',
            'additional_images',
            'additional_attributes',
            'configurable_variations',      // sku=MH01-XS-Black,size=XS,color=Black|sku=MH01-XS-Gray,size=XS,color=Gray|sku=MH01-XS-Orange,size=XS,color=Orange|sku=MH01-S-Black,size=S,color=Black|sku=MH01-S-Gray,size=S,color=Gray|sku=MH01-S-Orange,size=S,color=Orange|sku=MH01-M-Black,size=M,color=Black|sku=MH01-M-Gray,size=M,color=Gray|sku=MH01-M-Orange,size=M,color=Orange|sku=MH01-L-Black,size=L,color=Black|sku=MH01-L-Gray,size=L,color=Gray|sku=MH01-L-Orange,size=L,color=Orange|sku=MH01-XL-Black,size=XL,color=Black|sku=MH01-XL-Gray,size=XL,color=Gray|sku=MH01-XL-Orange,size=XL,color=Orange
            'configurable_variation_labels' // size=Size,color=Color
        );

        //----------------------------------------

        $eBayItemSpecificsTableName = $resource->getTableName('m2e_e2m_ebay_item_specifics');
        $specifics = $connRead->select()->from($eBayItemSpecificsTableName)->reset(Zend_Db_Select::COLUMNS)->columns(array(
            'name',
            "GROUP_CONCAT(DISTINCT value SEPARATOR ',') as value"
        ))->group('name')->query();
        file_put_contents($pathPrefix . $file1, 'code,title,values' . PHP_EOL);
        foreach ($specifics->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (empty($row['name'])) {
                continue;
            }

            $name = $row['name'];
            $value = $row['value'];
            $code = $dataHelper->getCode($name);
            $csvHeader[] = $code;

            file_put_contents($pathPrefix . $file1, "\"{$code}\",\"{$name}\",\"{$value}\"" . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        $eBayItemVariationSpecificsTableName = $resource->getTableName('m2e_e2m_ebay_item_variation_specifics');
        $specifics = $connRead->select()->from($eBayItemVariationSpecificsTableName)->reset(Zend_Db_Select::COLUMNS)->columns(array(
            'name',
            "GROUP_CONCAT(DISTINCT value SEPARATOR ',') as value"
        ))->group('name')->query();
        file_put_contents($pathPrefix . $file2, 'code,title,values' . PHP_EOL);
        foreach ($specifics->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (empty($row['name'])) {
                continue;
            }

            $name = $row['name'];
            $value = $row['value'];
            $code = $dataHelper->getCode($name);
            $csvHeader[] = $code;

            file_put_contents($pathPrefix . $file2, "\"{$code}\",\"{$name}\",\"{$value}\"" . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        return $this->ajaxResponse(array('completed' => true));
    }
}
