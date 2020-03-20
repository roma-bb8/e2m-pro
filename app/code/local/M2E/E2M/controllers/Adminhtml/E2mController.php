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

    public function collectInventoryMagmiAction() {

        $eBayConfigHelper = Mage::helper('e2m/Ebay_Config');
        $dataHelper = Mage::helper('e2m');
        $fieldsAttributes = $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_MAP);

        $pathPrefix = BP . DS . 'var' . DS . 'e2m' . DS;

        $resource = Mage::getSingleton('core/resource');
        $connRead = $resource->getConnection('core_read');

        //----------------------------------------

        $csvHeader = array(
            'attribute_set_code',
            'product_type',
            'tax_class_id',
            'is_in_stock',
            'status',
            'visibility',
            'additional_images',
            'additional_attributes',
            'configurable_attributes',
            'simples_skus'
        );

        $eBayItemVariationSpecificsTableName = $resource->getTableName('m2e_e2m_ebay_item_variation_specifics');
        $eBayItemSpecificsTableName = $resource->getTableName('m2e_e2m_ebay_item_specifics');
        $specifics = $connRead->select()->union(array(
            $connRead->select()->from($eBayItemSpecificsTableName)
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('name'))
                ->group('name'),
            $connRead->select()->from($eBayItemVariationSpecificsTableName)
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('name'))
                ->group('name'),
        ))->query();

        while ($specific = $specifics->fetch(PDO::FETCH_ASSOC)) {
            if (empty($specific['name'])) {
                continue;
            }

            $csvHeader[] = $dataHelper->getCode($specific['name']);
        }

        $csvHeader = array_merge($csvHeader, array_keys($fieldsAttributes));
        $csvHeader = array_unique($csvHeader);
        sort($csvHeader);
        $productData = array_combine(
            array_values($csvHeader),
            array_fill(0, count($csvHeader), '"__EMPTY__VALUE__"')
        );

        //----------------------------------------

        $eBayItemVariationsTableName = $resource->getTableName('m2e_e2m_ebay_item_variations');
        $eBayItemImagesTableName = $resource->getTableName('m2e_e2m_ebay_item_images');
        $eBayItemsTableName = $resource->getTableName('m2e_e2m_ebay_items');

        //----------------------------------------

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
            ))->query();

        $variations = array();
        while ($item = $inventory->fetch(PDO::FETCH_ASSOC)) {

            //---
            if ($eBayConfigHelper->isDeleteHtml()) {
                $item['title'] = strip_tags($item['title']);
                $item['subtitle'] = strip_tags($item['subtitle']);
                $item['description'] = strip_tags($item['description']);
            }

            if (empty($item['sku']) && $eBayConfigHelper->isGenerateSku()) {
                $item['sku'] = 'SKU_' . md5($item['ebay_item_id']);
            }

            $productID = $eBayConfigHelper->getProductIdentifier();
            if (empty($item[$productID])) {
                $item[$productID] = 'PID_' . md5($item['ebay_item_id']);
            }

            if (self::DOES_NOT_APPLY === strtolower($item[$productID])) {
                $item[$productID] = 'DNA_' . md5($item['ebay_item_id']);
            }

            //TODO Delete
            if ('refer to description' === strtolower($item[$productID])) {
                $item[$productID] = 'ROD_' . md5($item['ebay_item_id']);
            }
            //---

            $product = $productData;

            $attributeSetName = $dataHelper->getAttributeSetNameById(
                $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET)
            );
            $product['attribute_set_code'] = "\"{$attributeSetName}\"";
            $product['status'] = 1;
            $product['tax_class_id'] = '"None"';
            $product['product_type'] = '"simple"';
            $product['visibility'] = '"Not Visible Individually"';
            $product['additional_images'] = $dataHelper->getValue($connRead->select()
                ->from($eBayItemImagesTableName)->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('GROUP_CONCAT(`path` SEPARATOR ",") as images'))
                ->where('item_id = ?', $item['id'])->group('item_id')->query()->fetchColumn());

            if (!empty($item['v_hash'])) {
                $variations[$item['sku']] = $item;

                $variations[$item['sku']]['simples_skus'][] = $item['v_sku'];
                foreach ($connRead->select()->from($eBayItemVariationSpecificsTableName)
                             ->where('item_variation_id = ?', $item['item_variation_id'])
                             ->query()->fetchAll() as $specific) {
                    $code = $dataHelper->getCode($specific['name']);
                    $product[$code] = $dataHelper->getValue($specific['value']);
                    $variations[$item['sku']]['configurable_attributes'][] = $code;
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

            foreach ($fieldsAttributes as $magentoAttribute => $eBayField) {
                if ($eBayField === 'quantity') {
                    $product['is_in_stock'] = (int)(0 < (int)$item[$eBayField]);
                }

                $product[$magentoAttribute] = $dataHelper->getValue($item[$eBayField]);
            }

            foreach ($connRead->select()->from($eBayItemSpecificsTableName)
                         ->where('item_id = ?', $item['id'])->query()->fetchAll() as $specific) {
                $code = $dataHelper->getCode($specific['name']);
                $product[$code] = $dataHelper->getValue($specific['value']);
            }

            $dataHelper->writeInventoryFile($pathPrefix, implode(',', $product), $csvHeader, 'magmi');
        }

        if (empty($variations)) {
            return $this->ajaxResponse(array('completed' => true));
        }

        foreach ($variations as $parent) {
            $product = $productData;

            $attributeSetName = $dataHelper->getAttributeSetNameById(
                $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET)
            );
            $product['attribute_set_code'] = "\"{$attributeSetName}\"";
            $product['status'] = 1;
            $product['tax_class_id'] = '"None"';
            $product['product_type'] = '"configurable"';
            $product['visibility'] = '"Not Visible Individually"';
            $product['additional_images'] = $dataHelper->getValue($connRead->select()
                ->from($eBayItemImagesTableName)->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('GROUP_CONCAT(`path` SEPARATOR ",")'))
                ->where('item_id = ?', $item['id'])->group('item_id')->query()->fetchColumn());

            foreach ($fieldsAttributes as $magentoAttribute => $eBayField) {
                $product[$magentoAttribute] = $dataHelper->getValue($parent[$eBayField]);
            }

            foreach ($connRead->select()->from($eBayItemSpecificsTableName)
                         ->where('item_id = ?', $parent['id'])->query()->fetchAll() as $specific) {
                $code = $dataHelper->getCode($specific['name']);
                $product[$code] = $dataHelper->getValue($specific['value']);
            }

            $product['configurable_attributes'] = implode(',', array_unique($parent['configurable_attributes']));
            $product['simples_skus'] = implode(',', array_unique($parent['simples_skus']));

            $dataHelper->writeInventoryFile($pathPrefix, implode(',', $product), $csvHeader, 'magmi');
        }

        return $this->ajaxResponse(array('completed' => true));
    }

    public function collectInventoryBaseM2Action() {

        $eBayConfigHelper = Mage::helper('e2m/Ebay_Config');
        $dataHelper = Mage::helper('e2m');
        $fieldsAttributes = $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_MAP);

        $pathPrefix = BP . DS . 'var' . DS . 'e2m' . DS;

        $resource = Mage::getSingleton('core/resource');
        $connRead = $resource->getConnection('core_read');

        //----------------------------------------

        $csvHeader = array(
            'attribute_set_code',
            'product_type',
            'tax_class_id',
            'is_in_stock',
            'status',
            'visibility',
            'additional_images',
            'additional_attributes',
            'configurable_variations',
            'configurable_variation_labels'
        );

        $eBayItemVariationSpecificsTableName = $resource->getTableName('m2e_e2m_ebay_item_variation_specifics');
        $eBayItemSpecificsTableName = $resource->getTableName('m2e_e2m_ebay_item_specifics');
        $specifics = $connRead->select()->union(array(
            $connRead->select()->from($eBayItemSpecificsTableName)
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('name'))
                ->group('name'),
            $connRead->select()->from($eBayItemVariationSpecificsTableName)
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('name'))
                ->group('name'),
        ))->query();

        while ($specific = $specifics->fetch(PDO::FETCH_ASSOC)) {
            if (empty($specific['name'])) {
                continue;
            }

            $csvHeader[] = $dataHelper->getCode($specific['name']);
        }

        $csvHeader = array_merge($csvHeader, array_keys($fieldsAttributes));
        $csvHeader = array_unique($csvHeader);
        sort($csvHeader);
        $productData = array_combine(
            array_values($csvHeader),
            array_fill(0, count($csvHeader), '"__EMPTY__VALUE__"')
        );

        //----------------------------------------

        $eBayItemVariationsTableName = $resource->getTableName('m2e_e2m_ebay_item_variations');
        $eBayItemImagesTableName = $resource->getTableName('m2e_e2m_ebay_item_images');
        $eBayItemsTableName = $resource->getTableName('m2e_e2m_ebay_items');

        //----------------------------------------

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
            ))->query();

        $variations = array();
        while ($item = $inventory->fetch(PDO::FETCH_ASSOC)) {

            //---
            if ($eBayConfigHelper->isDeleteHtml()) {
                $item['title'] = strip_tags($item['title']);
                $item['subtitle'] = strip_tags($item['subtitle']);
                $item['description'] = strip_tags($item['description']);
            }

            if (empty($item['sku']) && $eBayConfigHelper->isGenerateSku()) {
                $item['sku'] = 'SKU_' . md5($item['ebay_item_id']);
            }

            $productID = $eBayConfigHelper->getProductIdentifier();
            if (empty($item[$productID])) {
                $item[$productID] = 'PID_' . md5($item['ebay_item_id']);
            }

            if (self::DOES_NOT_APPLY === strtolower($item[$productID])) {
                $item[$productID] = 'DNA_' . md5($item['ebay_item_id']);
            }

            //TODO Delete
            if ('refer to description' === strtolower($item[$productID])) {
                $item[$productID] = 'ROD_' . md5($item['ebay_item_id']);
            }
            //---

            $product = $productData;

            $attributeSetName = $dataHelper->getAttributeSetNameById(
                $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET)
            );
            $product['attribute_set_code'] = "\"{$attributeSetName}\"";
            $product['status'] = 1;
            $product['tax_class_id'] = '"None"';
            $product['product_type'] = '"simple"';
            $product['visibility'] = '"Not Visible Individually"';
            $product['additional_images'] = $dataHelper->getValue($connRead->select()
                ->from($eBayItemImagesTableName)->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('GROUP_CONCAT(`path` SEPARATOR ",") as images'))
                ->where('item_id = ?', $item['id'])->group('item_id')->query()->fetchColumn());

            if (!empty($item['v_hash'])) {
                $variations[$item['sku']] = $item;

                foreach ($connRead->select()->from($eBayItemVariationSpecificsTableName)
                             ->where('item_variation_id = ?', $item['item_variation_id'])
                             ->query()->fetchAll() as $specific) {
                    $code = $dataHelper->getCode($specific['name']);
                    $product[$code] = $dataHelper->getValue($specific['value']);
                    $variations[$item['sku']]['configurable_variations_sku'][$item['v_sku']][$code] = $specific;
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

            foreach ($fieldsAttributes as $magentoAttribute => $eBayField) {
                if ($eBayField === 'quantity') {
                    $product['is_in_stock'] = (int)(0 < (int)$item[$eBayField]);
                }

                $product[$magentoAttribute] = $dataHelper->getValue($item[$eBayField]);
            }

            foreach ($connRead->select()->from($eBayItemSpecificsTableName)
                         ->where('item_id = ?', $item['id'])->query()->fetchAll() as $specific) {
                $code = $dataHelper->getCode($specific['name']);
                $product[$code] = $dataHelper->getValue($specific['value']);
            }

            $dataHelper->writeInventoryFile($pathPrefix, implode(',', $product), $csvHeader, 'm2');
        }

        if (empty($variations)) {
            return $this->ajaxResponse(array('completed' => true));
        }

        foreach ($variations as $parent) {
            $product = $productData;

            $attributeSetName = $dataHelper->getAttributeSetNameById(
                $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET)
            );
            $product['attribute_set_code'] = "\"{$attributeSetName}\"";
            $product['status'] = 1;
            $product['tax_class_id'] = '"None"';
            $product['product_type'] = '"configurable"';
            $product['visibility'] = '"Not Visible Individually"';
            $product['additional_images'] = $dataHelper->getValue($connRead->select()
                ->from($eBayItemImagesTableName)->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('GROUP_CONCAT(`path` SEPARATOR ",")'))
                ->where('item_id = ?', $item['id'])->group('item_id')->query()->fetchColumn());

            foreach ($fieldsAttributes as $magentoAttribute => $eBayField) {
                $product[$magentoAttribute] = $dataHelper->getValue($parent[$eBayField]);
            }

            foreach ($connRead->select()->from($eBayItemSpecificsTableName)
                         ->where('item_id = ?', $parent['id'])->query()->fetchAll() as $specific) {
                $code = $dataHelper->getCode($specific['name']);
                $product[$code] = $dataHelper->getValue($specific['value']);
            }

            $variationLabels = array();
            $variations = array();
            foreach ($parent['configurable_variations_sku'] as $sku => $data) {
                $variation = array();
                $variation[] = "sku={$sku}";
                foreach ($data as $code => $specific) {
                    $name = $specific['name'];
                    $value = $specific['value'];
                    $variationLabels[$code] = "{$code}={$name}";
                    $tmp[$code] = "{$code}={$value}";
                }
                $variations[$sku] = implode(',', $variation);
            }
            $product['configurable_variation_labels'] = $dataHelper->getValue(implode(',', $variationLabels));
            $product['configurable_variations'] = $dataHelper->getValue(implode('|', $variations));

            $dataHelper->writeInventoryFile($pathPrefix, implode(',', $product), $csvHeader, 'm2');
        }

        return $this->ajaxResponse(array('completed' => true));
    }

    public function collectInventoryBaseM1Action() {

        $eBayConfigHelper = Mage::helper('e2m/Ebay_Config');
        $dataHelper = Mage::helper('e2m');
        $fieldsAttributes = $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_MAP);

        $pathPrefix = BP . DS . 'var' . DS . 'e2m' . DS;

        $resource = Mage::getSingleton('core/resource');
        $connRead = $resource->getConnection('core_read');

        //----------------------------------------

        $csvHeader = array(
            '_attribute_set',
            '_type',
            'tax_class_id',
            'is_in_stock',
            'status',
            'visibility',
            'additional_images',
            'image',
            '_super_products_sku',
            '_super_attribute_code',
            '_super_attribute_option'
        );

        $eBayItemVariationSpecificsTableName = $resource->getTableName('m2e_e2m_ebay_item_variation_specifics');
        $eBayItemSpecificsTableName = $resource->getTableName('m2e_e2m_ebay_item_specifics');
        $specifics = $connRead->select()->union(array(
            $connRead->select()->from($eBayItemSpecificsTableName)
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('name'))
                ->group('name'),
            $connRead->select()->from($eBayItemVariationSpecificsTableName)
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('name'))
                ->group('name'),
        ))->query();

        while ($specific = $specifics->fetch(PDO::FETCH_ASSOC)) {
            if (empty($specific['name'])) {
                continue;
            }

            $csvHeader[] = $dataHelper->getCode($specific['name']);
        }

        $csvHeader = array_merge($csvHeader, array_keys($fieldsAttributes));
        $csvHeader = array_unique($csvHeader);
        sort($csvHeader);
        $productData = array_combine(
            array_values($csvHeader),
            array_fill(0, count($csvHeader), '"__EMPTY__VALUE__"')
        );

        //----------------------------------------

        $eBayItemVariationsTableName = $resource->getTableName('m2e_e2m_ebay_item_variations');
        $eBayItemImagesTableName = $resource->getTableName('m2e_e2m_ebay_item_images');
        $eBayItemsTableName = $resource->getTableName('m2e_e2m_ebay_items');

        //----------------------------------------

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
            ))->query();

        $variations = array();
        while ($item = $inventory->fetch(PDO::FETCH_ASSOC)) {

            //---
            if ($eBayConfigHelper->isDeleteHtml()) {
                $item['title'] = strip_tags($item['title']);
                $item['subtitle'] = strip_tags($item['subtitle']);
                $item['description'] = strip_tags($item['description']);
            }

            if (empty($item['sku']) && $eBayConfigHelper->isGenerateSku()) {
                $item['sku'] = 'SKU_' . md5($item['ebay_item_id']);
            }

            $productID = $eBayConfigHelper->getProductIdentifier();
            if (empty($item[$productID])) {
                $item[$productID] = 'PID_' . md5($item['ebay_item_id']);
            }

            if (self::DOES_NOT_APPLY === strtolower($item[$productID])) {
                $item[$productID] = 'DNA_' . md5($item['ebay_item_id']);
            }

            //TODO Delete
            if ('refer to description' === strtolower($item[$productID])) {
                $item[$productID] = 'ROD_' . md5($item['ebay_item_id']);
            }
            //---

            $product = $productData;

            $attributeSetName = $dataHelper->getAttributeSetNameById(
                $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET)
            );
            $product['_attribute_set'] = "\"{$attributeSetName}\"";
            $product['status'] = 1;
            $product['tax_class_id'] = '"None"';
            $product['_type'] = '"simple"';
            $product['visibility'] = '"Not Visible Individually"';
            $product['image'] = $dataHelper->getValue($connRead->select()
                ->from($eBayItemImagesTableName)->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('GROUP_CONCAT(`path` SEPARATOR ",") as images'))
                ->where('item_id = ?', $item['id'])->group('item_id')->query()->fetchColumn());

            if (!empty($item['v_hash'])) {
                $variations[$item['sku']] = $item;

                foreach ($connRead->select()->from($eBayItemVariationSpecificsTableName)
                             ->where('item_variation_id = ?', $item['item_variation_id'])
                             ->query()->fetchAll() as $specific) {
                    $code = $dataHelper->getCode($specific['name']);
                    $value = $dataHelper->getValue($specific['value']);
                    $product[$code] = $value;
                    $variations[$item['sku']]['configurable_variations_sku'][$item['v_sku']][$code] = $value;
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

            foreach ($fieldsAttributes as $magentoAttribute => $eBayField) {
                if ($eBayField === 'quantity') {
                    $product['is_in_stock'] = (int)(0 < (int)$item[$eBayField]);
                }

                $product[$magentoAttribute] = $dataHelper->getValue($item[$eBayField]);
            }

            foreach ($connRead->select()->from($eBayItemSpecificsTableName)
                         ->where('item_id = ?', $item['id'])->query()->fetchAll() as $specific) {
                $code = $dataHelper->getCode($specific['name']);
                $product[$code] = $dataHelper->getValue($specific['value']);
            }

            $dataHelper->writeInventoryFile($pathPrefix, implode(',', $product), $csvHeader, 'm1');
        }

        if (empty($variations)) {
            return $this->ajaxResponse(array('completed' => true));
        }

        foreach ($variations as $parent) {
            $product = $productData;

            $attributeSetName = $dataHelper->getAttributeSetNameById(
                $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET)
            );
            $product['_attribute_set'] = "\"{$attributeSetName}\"";
            $product['status'] = 1;
            $product['tax_class_id'] = '"None"';
            $product['_type'] = '"configurable"';
            $product['visibility'] = '"Not Visible Individually"';
            $product['image'] = $dataHelper->getValue($connRead->select()
                ->from($eBayItemImagesTableName)->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('GROUP_CONCAT(`path` SEPARATOR ",")'))
                ->where('item_id = ?', $item['id'])->group('item_id')->query()->fetchColumn());

            foreach ($fieldsAttributes as $magentoAttribute => $eBayField) {
                $product[$magentoAttribute] = $dataHelper->getValue($parent[$eBayField]);
            }

            foreach ($connRead->select()->from($eBayItemSpecificsTableName)
                         ->where('item_id = ?', $parent['id'])->query()->fetchAll() as $specific) {
                $code = $dataHelper->getCode($specific['name']);
                $product[$code] = $dataHelper->getValue($specific['value']);
            }

            $change = array();
            foreach ($parent['configurable_variations_sku'] as $sku => $data) {
                foreach ($data as $code => $value) {
                    $variation = $productData;

                    $variation['_super_products_sku'] = $sku;
                    $variation['_super_attribute_code'] = $code;
                    $variation['_super_attribute_option'] = $value;

                    $change[] = $variation;
                }
            }

            $variation = array_shift($change);
            $product['_super_products_sku'] = $variation['_super_products_sku'];
            $product['_super_attribute_code'] = $variation['_super_attribute_code'];
            $product['_super_attribute_option'] = $variation['_super_attribute_option'];

            $product = implode(',', $product);
            if (!empty($change)) {
                $children = array();
                foreach ($change as $variation) {
                    $children[] = implode(',', $variation);
                }
                $product .= PHP_EOL . implode(PHP_EOL, $children);
            }

            $dataHelper->writeInventoryFile($pathPrefix, $product, $csvHeader, 'm1');
        }

        return $this->ajaxResponse(array('completed' => true));
    }

    public function collectAttributesCSVAction() {

        $dataHelper = Mage::helper('e2m');

        $pathPrefix = BP . DS . 'var' . DS . 'e2m' . DS;
        $file = 'ebay_attributes.csv';

        $resource = Mage::getSingleton('core/resource');
        $connRead = $resource->getConnection('core_read');

        file_put_contents($pathPrefix . $file, 'code,title,values' . PHP_EOL);

        //----------------------------------------

        $specifics = $connRead->select()->union(array(
            $connRead->select()->from($resource->getTableName('m2e_e2m_ebay_item_specifics'))
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('name', "GROUP_CONCAT(DISTINCT value SEPARATOR ',') as value"))
                ->group('name'),
            $connRead->select()->from($resource->getTableName('m2e_e2m_ebay_item_variation_specifics'))
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('name', "GROUP_CONCAT(DISTINCT value SEPARATOR ',') as value"))
                ->group('name'),
        ))->query();

        while ($specific = $specifics->fetch(PDO::FETCH_ASSOC)) {
            if (empty($specific['name'])) {
                continue;
            }

            $name = $specific['name'];
            $value = $specific['value'];
            $code = $dataHelper->getCode($name);

            file_put_contents($pathPrefix . $file, "\"{$code}\",\"{$name}\",\"{$value}\"" . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        return $this->ajaxResponse(array('completed' => true));
    }

    public function collectAttributesM2Action() {

        $dataHelper = Mage::helper('e2m');

        $pathPrefix = BP . DS . 'var' . DS . 'e2m' . DS;
        $file = 'ebay_m2_attributes.sql';

        $attributeSetId = (int)$dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET);
        $sql = <<<SQL
SET @entity_type_id = (SELECT `entity_type_id` FROM `eav_entity_type` WHERE `entity_type_code` = 'catalog_product' LIMIT 1);
SET @attribute_group_id = (SELECT `attribute_group_id` FROM `eav_attribute_group` WHERE `attribute_group_name` = 'eBay' LIMIT 1);
SET @specific_attribute_group_id = (SELECT `attribute_group_id` FROM `eav_attribute_group` WHERE `attribute_group_name` = 'eBay Specifics' LIMIT 1);
SET @attribute_set_id = {$attributeSetId};
SQL;
        $sql .= PHP_EOL;

        $resource = Mage::getSingleton('core/resource');
        $connRead = $resource->getConnection('core_read');

        //----------------------------------------

        $stores = $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_STORE_MAP);
        $specifics = $connRead->select()
            ->from($resource->getTableName('m2e_e2m_ebay_item_variation_specifics'))
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(array('name', "GROUP_CONCAT(DISTINCT value SEPARATOR ',') as value"))
            ->group('name')->query();

        while ($specific = $specifics->fetch(PDO::FETCH_ASSOC)) {
            if (empty($specific['name'])) {
                continue;
            }

            $name = str_replace("'", '"', $specific['name']);
            $code = $dataHelper->getCode($name);
            $sql .= PHP_EOL;
            $sql .= <<<SQL
-- {$code}

SET @attribute_code = '{$code}';
SET @frontend_input = '{$name}';

INSERT INTO `eav_attribute` (`attribute_id`, `entity_type_id`, `attribute_code`, `backend_type`, `frontend_input`, `frontend_label`, `source_model`, `is_required`, `is_user_defined`, `default_value`, `is_unique`)
VALUES (NULL, @entity_type_id, @attribute_code, 'varchar', 'select', @frontend_input, 'Magento\\Eav\\Model\\Entity\\Attribute\\Source\\Table', 0, 1, '', 0);

SET @attribute_id = LAST_INSERT_ID();

INSERT INTO `eav_entity_attribute` (`entity_attribute_id`, `entity_type_id`, `attribute_set_id`, `attribute_group_id`, `attribute_id`)
VALUES (NULL, @entity_type_id, @attribute_set_id, @attribute_group_id, @attribute_id);

INSERT INTO `catalog_eav_attribute` (`attribute_id`, `is_global`, `is_visible`, `is_searchable`, `is_filterable`, `is_comparable`, `is_visible_on_front`, `is_html_allowed_on_front`, `is_used_for_price_rules`, `is_filterable_in_search`, `used_in_product_listing`, `used_for_sort_by`, `is_visible_in_advanced_search`, `position`, `is_wysiwyg_enabled`, `is_used_for_promo_rules`)
VALUES (@attribute_id, 1, 1, 0, 0, 1, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0);

INSERT INTO `eav_attribute_option` (`option_id`, `attribute_id`) VALUES (NULL, @attribute_id);

SET @option_id = LAST_INSERT_ID();
SQL;
            $sql .= PHP_EOL . PHP_EOL;
            foreach ($stores as $marketplaceId => $storeId) {
                $sql .= <<<SQL
INSERT INTO `eav_attribute_label` (`attribute_label_id`, `attribute_id`, `store_id`, `value`) VALUES (NULL, @attribute_id, {$storeId}, @frontend_input);
SQL;
                $sql .= PHP_EOL;
                $values = explode(',', $specific['value']);
                foreach ($values as $value) {
                    $value = str_replace("'", '"', $value);
                    $sql .= <<<SQL
INSERT INTO `eav_attribute_option_value` (`value_id`, `option_id`, `store_id`, `value`) VALUES (NULL, @option_id, {$storeId}, '{$value}');
SQL;
                    $sql .= PHP_EOL;
                }
            }

            $sql .= PHP_EOL . "-- end {$code}" . PHP_EOL;
        }

        file_put_contents($pathPrefix . $file, $sql, LOCK_EX);

        $specifics = $connRead->select()
            ->from($resource->getTableName('m2e_e2m_ebay_item_specifics'))
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(array('name', "GROUP_CONCAT(DISTINCT value SEPARATOR ',') as value"))
            ->group('name')->query();

        while ($specific = $specifics->fetch(PDO::FETCH_ASSOC)) {
            if (empty($specific['name'])) {
                continue;
            }

            $name = str_replace("'", '"', $specific['name']);
            $code = $dataHelper->getCode($name);
            $sql .= PHP_EOL;
            $sql .= <<<SQL
-- {$code}

SET @attribute_code = '{$code}';
SET @frontend_input = '{$name}';

INSERT INTO `eav_attribute` (`attribute_id`, `entity_type_id`, `attribute_code`, `backend_type`, `frontend_input`, `frontend_label`, `source_model`, `is_required`, `is_user_defined`, `default_value`, `is_unique`)
VALUES (NULL, @entity_type_id, @attribute_code, 'varchar', 'select', @frontend_input, 'Magento\\Eav\\Model\\Entity\\Attribute\\Source\\Table', 0, 1, '', 0);

SET @attribute_id = LAST_INSERT_ID();

INSERT INTO `eav_entity_attribute` (`entity_attribute_id`, `entity_type_id`, `attribute_set_id`, `attribute_group_id`, `attribute_id`)
VALUES (NULL, @entity_type_id, @attribute_set_id, @specific_attribute_group_id, @attribute_id);

INSERT INTO `catalog_eav_attribute` (`attribute_id`, `is_global`, `is_visible`, `is_searchable`, `is_filterable`, `is_comparable`, `is_visible_on_front`, `is_html_allowed_on_front`, `is_used_for_price_rules`, `is_filterable_in_search`, `used_in_product_listing`, `used_for_sort_by`, `is_visible_in_advanced_search`, `position`, `is_wysiwyg_enabled`, `is_used_for_promo_rules`)
VALUES (@attribute_id, 1, 1, 0, 0, 1, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0);
SQL;
            $sql .= PHP_EOL . PHP_EOL;
            foreach ($stores as $marketplaceId => $storeId) {
                $sql .= <<<SQL
INSERT INTO `eav_attribute_label` (`attribute_label_id`, `attribute_id`, `store_id`, `value`) VALUES (NULL, @attribute_id, {$storeId}, @frontend_input);
SQL;
                $sql .= PHP_EOL;
            }

            $sql .= PHP_EOL . "-- end {$code}" . PHP_EOL;
        }

        file_put_contents($pathPrefix . $file, $sql, LOCK_EX);

        return $this->ajaxResponse(array('completed' => true));
    }

    public function collectAttributesM1Action() {

        $dataHelper = Mage::helper('e2m');

        $pathPrefix = BP . DS . 'var' . DS . 'e2m' . DS;
        $file = 'ebay_m1_attributes.sql';

        $attributeSetId = (int)$dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET);
        $sql = <<<SQL
SET @entity_type_id = (SELECT `entity_type_id` FROM `eav_entity_type` WHERE `entity_type_code` = 'catalog_product' LIMIT 1);
SET @attribute_group_id = (SELECT `attribute_group_id` FROM `eav_attribute_group` WHERE `attribute_group_name` = 'eBay' LIMIT 1);
SET @specific_attribute_group_id = (SELECT `attribute_group_id` FROM `eav_attribute_group` WHERE `attribute_group_name` = 'eBay Specifics' LIMIT 1);
SET @attribute_set_id = {$attributeSetId};
SQL;
        $sql .= PHP_EOL;

        $resource = Mage::getSingleton('core/resource');
        $connRead = $resource->getConnection('core_read');

        //----------------------------------------

        $stores = $dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_STORE_MAP);
        $specifics = $connRead->select()
            ->from($resource->getTableName('m2e_e2m_ebay_item_variation_specifics'))
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(array('name', "GROUP_CONCAT(DISTINCT value SEPARATOR ',') as value"))
            ->group('name')->query();

        while ($specific = $specifics->fetch(PDO::FETCH_ASSOC)) {
            if (empty($specific['name'])) {
                continue;
            }

            $name = str_replace("'", '"', $specific['name']);
            $code = $dataHelper->getCode($name);
            $sql .= PHP_EOL;
            $sql .= <<<SQL
-- {$code}

SET @attribute_code = '{$code}';
SET @frontend_input = '{$name}';

INSERT INTO `eav_attribute` (`attribute_id`, `entity_type_id`, `attribute_code`, `backend_type`, `frontend_input`, `frontend_label`, `source_model`, `is_required`, `is_user_defined`, `default_value`, `is_unique`)
VALUES (NULL, @entity_type_id, @attribute_code, 'varchar', 'select', @frontend_input, 'eav/entity_attribute_source_table', 0, 1, '', 0);

SET @attribute_id = LAST_INSERT_ID();

INSERT INTO `eav_entity_attribute` (`entity_attribute_id`, `entity_type_id`, `attribute_set_id`, `attribute_group_id`, `attribute_id`)
VALUES (NULL, @entity_type_id, @attribute_set_id, @attribute_group_id, @attribute_id);

INSERT INTO `catalog_eav_attribute` (`attribute_id`, `is_global`, `is_visible`, `is_searchable`, `is_filterable`, `is_comparable`, `is_visible_on_front`, `is_html_allowed_on_front`, `is_used_for_price_rules`, `is_filterable_in_search`, `used_in_product_listing`, `used_for_sort_by`, `is_configurable`, `apply_to`, `is_visible_in_advanced_search`, `position`, `is_wysiwyg_enabled`, `is_used_for_promo_rules`)
VALUES (@attribute_id, 1, 1, 0, 0, 1, 0, 1, 0, 0, 0, 0, 1, 'simple,configurable', 1, 0, 0, 0);

INSERT INTO `eav_attribute_option` (`option_id`, `attribute_id`) VALUES (NULL, @attribute_id);

SET @option_id = LAST_INSERT_ID();
SQL;
            $sql .= PHP_EOL . PHP_EOL;
            foreach ($stores as $marketplaceId => $storeId) {
                $sql .= <<<SQL
INSERT INTO `eav_attribute_label` (`attribute_label_id`, `attribute_id`, `store_id`, `value`) VALUES (NULL, @attribute_id, {$storeId}, @frontend_input);
SQL;
                $sql .= PHP_EOL;
                $values = explode(',', $specific['value']);
                foreach ($values as $value) {
                    $value = str_replace("'", '"', $value);
                    $sql .= <<<SQL
INSERT INTO `eav_attribute_option_value` (`value_id`, `option_id`, `store_id`, `value`) VALUES (NULL, @option_id, {$storeId}, '{$value}');
SQL;
                    $sql .= PHP_EOL;
                }
            }

            $sql .= PHP_EOL . "-- end {$code}" . PHP_EOL;
        }

        file_put_contents($pathPrefix . $file, $sql, LOCK_EX);

        $specifics = $connRead->select()
            ->from($resource->getTableName('m2e_e2m_ebay_item_specifics'))
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(array('name', "GROUP_CONCAT(DISTINCT value SEPARATOR ',') as value"))
            ->group('name')->query();

        while ($specific = $specifics->fetch(PDO::FETCH_ASSOC)) {
            if (empty($specific['name'])) {
                continue;
            }

            $name = str_replace("'", '"', $specific['name']);
            $code = $dataHelper->getCode($name);
            $sql .= PHP_EOL;
            $sql .= <<<SQL
-- {$code}

SET @attribute_code = '{$code}';
SET @frontend_input = '{$name}';

INSERT INTO `eav_attribute` (`attribute_id`, `entity_type_id`, `attribute_code`, `backend_type`, `frontend_input`, `frontend_label`, `source_model`, `is_required`, `is_user_defined`, `default_value`, `is_unique`)
VALUES (NULL, @entity_type_id, @attribute_code, 'varchar', 'select', @frontend_input, 'eav/entity_attribute_source_table', 0, 1, '', 0);

SET @attribute_id = LAST_INSERT_ID();

INSERT INTO `eav_entity_attribute` (`entity_attribute_id`, `entity_type_id`, `attribute_set_id`, `attribute_group_id`, `attribute_id`)
VALUES (NULL, @entity_type_id, @attribute_set_id, @specific_attribute_group_id, @attribute_id);

INSERT INTO `catalog_eav_attribute` (`attribute_id`, `is_global`, `is_visible`, `is_searchable`, `is_filterable`, `is_comparable`, `is_visible_on_front`, `is_html_allowed_on_front`, `is_used_for_price_rules`, `is_filterable_in_search`, `used_in_product_listing`, `used_for_sort_by`, `is_configurable`, `apply_to`, `is_visible_in_advanced_search`, `position`, `is_wysiwyg_enabled`, `is_used_for_promo_rules`)
VALUES (@attribute_id, 1, 1, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 'simple', 1, 0, 0, 0);
SQL;
            $sql .= PHP_EOL . PHP_EOL;
            foreach ($stores as $marketplaceId => $storeId) {
                $sql .= <<<SQL
INSERT INTO `eav_attribute_label` (`attribute_label_id`, `attribute_id`, `store_id`, `value`) VALUES (NULL, @attribute_id, {$storeId}, @frontend_input);
SQL;
                $sql .= PHP_EOL;
            }

            $sql .= PHP_EOL . "-- end {$code}" . PHP_EOL;
        }

        file_put_contents($pathPrefix . $file, $sql, LOCK_EX);

        return $this->ajaxResponse(array('completed' => true));
    }

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
}
