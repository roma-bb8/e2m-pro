<?php

class M2E_E2M_Adminhtml_E2mController extends Mage_Adminhtml_Controller_Action {

    const HTTP_INTERNAL_ERROR = 500;

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

        /** @var Mage_Catalog_Model_Product_Media_Config $productMediaConfig */
        $productMediaConfig = Mage::getSingleton('catalog/product_media_config');
        $tmpMediaPath = $productMediaConfig->getBaseTmpMediaPath();

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

        //----------------------------------------

        $inventory = $connRead->select()
            ->from(array('items' => 'm2e_e2m_ebay_items'))
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
            if (Mage::helper('e2m/Ebay_Config')->isDeleteHtml()) {
                $item['title'] = strip_tags($item['title']);
                $item['subtitle'] = strip_tags($item['subtitle']);
                $item['description'] = strip_tags($item['description']);
            }

            if (empty($item['sku']) && Mage::helper('e2m/Ebay_Config')->isGenerateSku()) {
                $item['sku'] = 'SKU_' . md5($item['ebay_item_id']);
            }

            $productID = Mage::helper('e2m/Ebay_Config')->getProductIdentifier();
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

            $images = $connRead->select()
                ->from('m2e_e2m_ebay_item_images')->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('GROUP_CONCAT(`path` SEPARATOR ",") as images'))
                ->where('item_id = ?', $item['id'])->group('item_id')->query()->fetchColumn();
            if (!empty($images)) {
                $images = explode(',', $images);
                foreach ($images as &$image) {
                    $image = $tmpMediaPath . $image;
                }
                unset($image);

                $product['additional_images'] = $dataHelper->getValue(implode(',', $images));
            }

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
            $images = $connRead->select()
                ->from('m2e_e2m_ebay_item_images')->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('GROUP_CONCAT(`path` SEPARATOR ",") as images'))
                ->where('item_id = ?', $item['id'])->group('item_id')->query()->fetchColumn();
            if (!empty($images)) {
                $images = explode(',', $images);
                foreach ($images as &$image) {
                    $image = $tmpMediaPath . $image;
                }
                unset($image);

                $product['additional_images'] = $dataHelper->getValue(implode(',', $images));
            }

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

        /** @var Mage_Catalog_Model_Product_Media_Config $productMediaConfig */
        $productMediaConfig = Mage::getSingleton('catalog/product_media_config');
        $tmpMediaPath = $productMediaConfig->getBaseTmpMediaPath();

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

        //----------------------------------------

        $inventory = $connRead->select()
            ->from(array('items' => 'm2e_e2m_ebay_items'))
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
            if (Mage::helper('e2m/Ebay_Config')->isDeleteHtml()) {
                $item['title'] = strip_tags($item['title']);
                $item['subtitle'] = strip_tags($item['subtitle']);
                $item['description'] = strip_tags($item['description']);
            }

            if (empty($item['sku']) && Mage::helper('e2m/Ebay_Config')->isGenerateSku()) {
                $item['sku'] = 'SKU_' . md5($item['ebay_item_id']);
            }

            $productID = Mage::helper('e2m/Ebay_Config')->getProductIdentifier();
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

            $images = $connRead->select()
                ->from('m2e_e2m_ebay_item_images')->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('GROUP_CONCAT(`path` SEPARATOR ",") as images'))
                ->where('item_id = ?', $item['id'])->group('item_id')->query()->fetchColumn();
            if (!empty($images)) {
                $images = explode(',', $images);
                foreach ($images as &$image) {
                    $image = $tmpMediaPath . DS . $image;
                }
                unset($image);

                $product['additional_images'] = $dataHelper->getValue(implode(',', $images));
            }

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

            $images = $connRead->select()
                ->from('m2e_e2m_ebay_item_images')->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('GROUP_CONCAT(`path` SEPARATOR ",") as images'))
                ->where('item_id = ?', $item['id'])->group('item_id')->query()->fetchColumn();
            if (!empty($images)) {
                $images = explode(',', $images);
                foreach ($images as &$image) {
                    $image = $tmpMediaPath . DS . $image;
                }
                unset($image);

                $product['additional_images'] = $dataHelper->getValue(implode(',', $images));
            }

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

    public function collectAttributesM2Action() {

        $pathPrefix = BP . DS . 'var' . DS . 'e2m' . DS;
        $file = 'ebay_m2_attributes.sql';

        $attributeSetId = (int)Mage::helper('e2m')->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET);
        $sql = <<<SQL
SET @attribute_set_id = {$attributeSetId};
SET @entity_type_id = (SELECT `entity_type_id` FROM `eav_entity_type` WHERE `entity_type_code` = 'catalog_product' LIMIT 1);
SET @attribute_group_id = (SELECT `attribute_group_id` FROM `eav_attribute_group` WHERE `attribute_group_name` = 'eBay' AND `attribute_set_id` = @attribute_set_id LIMIT 1);
SET @specific_attribute_group_id = (SELECT `attribute_group_id` FROM `eav_attribute_group` WHERE `attribute_group_name` = 'eBay Specifics' AND `attribute_set_id` = @attribute_set_id LIMIT 1);
SQL;
        $sql .= PHP_EOL;

        $resource = Mage::getSingleton('core/resource');
        $connRead = $resource->getConnection('core_read');

        //----------------------------------------

        $stores = Mage::helper('e2m')->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_STORE_MAP);
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
            $code = Mage::helper('e2m')->getCode($name);
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
            $code = Mage::helper('e2m')->getCode($name);
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
                if (M2E_E2M_Helper_Ebay_Config::STORE_SKIP === (int)$storeId) {
                    continue;
                }

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


    public function collectInventoryBaseM1Action() {

        $prefixPath = Mage::getBaseDir('var') . DS . 'e2m' . DS;
        $defaultValue = '""';

        list($site, $store) = Mage::helper('e2m/Ebay_Config')->getSiteAndStore();
        //$storeCode = Mage::helper('e2m')->getStoreCodeById($store);

        $mediaAttributeId = Mage::helper('e2m')->getMediaAttributeId();
        $attributeSetName = Mage::helper('e2m')->getAttributeSetNameById(
            Mage::helper('e2m')->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET)
        );

        Mage::getSingleton('core/resource')->getConnection('core_read')->query(
            'SET SESSION GROUP_CONCAT_MAX_LEN = 4294967295;'
        );
        $getSpecifics = <<<SQL
SELECT MAX(`type`) as `type`, `name`, GROUP_CONCAT(DISTINCT `values`) as `values`
FROM (
         SELECT (SELECT 0) AS `type`, `name`, GROUP_CONCAT(DISTINCT `value` SEPARATOR ',') AS `values`
         FROM `m2e_e2m_ebay_item_specifics`
                  LEFT JOIN `m2e_e2m_ebay_items` ON `m2e_e2m_ebay_item_specifics`.`item_id` = `m2e_e2m_ebay_items`.`id`
         WHERE `m2e_e2m_ebay_items`.`site` = ?
         GROUP BY `name`

         UNION

         SELECT (SELECT 1) AS `type`, `name`, GROUP_CONCAT(DISTINCT `value` SEPARATOR ',') AS `values`
         FROM `m2e_e2m_ebay_item_variation_specifics`
                  LEFT JOIN `m2e_e2m_ebay_item_variations` ON `m2e_e2m_ebay_item_variation_specifics`.`item_variation_id` = `m2e_e2m_ebay_item_variations`.`id`
                  LEFT JOIN `m2e_e2m_ebay_items` ON `m2e_e2m_ebay_item_variations`.`item_id` = `m2e_e2m_ebay_items`.`id`
         WHERE `m2e_e2m_ebay_items`.`site` = ?
         GROUP BY `name`
     ) as `specifics`
GROUP BY `name`;
SQL;

        //----------------------------------------

        $csvHeader = array(
            // required
            '_attribute_set',
            '_type',
            'name',
            'description',
            'short_description',
            'sku',
            'weight',
            'status',
            'visibility',
            'price',
            'tax_class_id',
            'qty',
            'is_in_stock',
            // required end

            '_super_products_sku',
            '_super_attribute_code',
            '_super_attribute_option',

            //'_store',
            'image',
            'small_image',
            'thumbnail',
            '_media_attribute_id',
            '_media_image'
        );

        $specifics = Mage::getSingleton('core/resource')->getConnection('core_read')->query(
            $getSpecifics,
            array($site, $site)
        );
        while ($specific = $specifics->fetch(PDO::FETCH_ASSOC)) {
            $csvHeader[] = Mage::helper('e2m')->getCode($specific['name']);
        }

        $fieldsAttributes = Mage::helper('e2m')->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_MAP);
        $csvHeader = array_unique(array_merge($csvHeader, array_keys($fieldsAttributes)));
        sort($csvHeader);

        $productSkeleton = array_combine(array_values($csvHeader), array_fill(0, count($csvHeader), $defaultValue));

        //----------------------------------------

        $getItemsSQL = <<<SQL
SELECT `type`,
       `id`,
       `item_variation_id`,
       `site`,
       `ebay_item_id`,
       `sku`,
       `upc`,
       `ean`,
       `isbn`,
       `ePID`,
       `mpn`,
       `brand`,
       `title`,
       `subtitle`,
       `description`,
       `currency`,
       `start_price`,
       `current_price`,
       `buy_it_now`,
       `quantity`,
       `condition_id`,
       `condition_name`,
       `condition_description`,
       `primary_category_id`,
       `primary_category_name`,
       `secondary_category_id`,
       `secondary_category_name`,
       `store_category_id`,
       `store_category_name`,
       `store_category2_id`,
       `store_category2_name`,
       `weight`,
       `dispatch_time_max`,
       `dimensions_depth`,
       `dimensions_length`,
       `dimensions_weight`
FROM (
         SELECT (SELECT 'simple') AS `type`,
                `m2e_e2m_ebay_items`.`id`,
                (SELECT NULL)     AS `item_variation_id`,
                `site`,
                `ebay_item_id`,
                `m2e_e2m_ebay_items`.`sku`,
                `m2e_e2m_ebay_items`.`upc`,
                `m2e_e2m_ebay_items`.`ean`,
                `m2e_e2m_ebay_items`.`isbn`,
                `m2e_e2m_ebay_items`.`ePID`,
                `mpn`,
                `brand`,
                `title`,
                `subtitle`,
                `description`,
                `currency`,
                `m2e_e2m_ebay_items`.`start_price`,
                `current_price`,
                `buy_it_now`,
                `m2e_e2m_ebay_items`.`quantity`,
                `condition_id`,
                `condition_name`,
                `condition_description`,
                `primary_category_id`,
                `primary_category_name`,
                `secondary_category_id`,
                `secondary_category_name`,
                `store_category_id`,
                `store_category_name`,
                `store_category2_id`,
                `store_category2_name`,
                `weight`,
                `dispatch_time_max`,
                `dimensions_depth`,
                `dimensions_length`,
                `dimensions_weight`
         FROM `m2e_e2m_ebay_items`
                  LEFT JOIN `m2e_e2m_ebay_item_variations`
                            ON `m2e_e2m_ebay_items`.id = `m2e_e2m_ebay_item_variations`.`item_id`
         WHERE `m2e_e2m_ebay_item_variations`.`id` IS NULL AND `m2e_e2m_ebay_items`.`site` = ?

         UNION

         SELECT (SELECT 'simple')                     AS `type`,
                `m2e_e2m_ebay_items`.`id`             as `id`,
                `m2e_e2m_ebay_item_variations`.`id`   AS `item_variation_id`,
                `site`,
                `m2e_e2m_ebay_item_variations`.`hash` AS `ebay_item_id`,
                `m2e_e2m_ebay_item_variations`.`sku`,
                `m2e_e2m_ebay_item_variations`.`upc`,
                `m2e_e2m_ebay_item_variations`.`ean`,
                `m2e_e2m_ebay_item_variations`.`isbn`,
                `m2e_e2m_ebay_item_variations`.`ePID`,
                `mpn`,
                `brand`,
                `title`,
                `subtitle`,
                `description`,
                `currency`,
                `m2e_e2m_ebay_item_variations`.`start_price`,
                `current_price`,
                `buy_it_now`,
                `m2e_e2m_ebay_item_variations`.`quantity`,
                `condition_id`,
                `condition_name`,
                `condition_description`,
                `primary_category_id`,
                `primary_category_name`,
                `secondary_category_id`,
                `secondary_category_name`,
                `store_category_id`,
                `store_category_name`,
                `store_category2_id`,
                `store_category2_name`,
                `weight`,
                `dispatch_time_max`,
                `dimensions_depth`,
                `dimensions_length`,
                `dimensions_weight`
         FROM `m2e_e2m_ebay_item_variations`
                  LEFT JOIN `m2e_e2m_ebay_items`
                            ON `m2e_e2m_ebay_item_variations`.`item_id` = `m2e_e2m_ebay_items`.`id`
         WHERE `m2e_e2m_ebay_items`.`site` = ?

         UNION

         SELECT (SELECT 'configurable') AS `type`,
                `m2e_e2m_ebay_items`.`id`,
                (SELECT NULL)           AS `item_variation_id`,
                `site`,
                `ebay_item_id`,
                `m2e_e2m_ebay_items`.`sku`,
                `m2e_e2m_ebay_items`.`upc`,
                `m2e_e2m_ebay_items`.`ean`,
                `m2e_e2m_ebay_items`.`isbn`,
                `m2e_e2m_ebay_items`.`ePID`,
                `mpn`,
                `brand`,
                `title`,
                `subtitle`,
                `description`,
                `currency`,
                `m2e_e2m_ebay_items`.`start_price`,
                `current_price`,
                `buy_it_now`,
                `m2e_e2m_ebay_items`.`quantity`,
                `condition_id`,
                `condition_name`,
                `condition_description`,
                `primary_category_id`,
                `primary_category_name`,
                `secondary_category_id`,
                `secondary_category_name`,
                `store_category_id`,
                `store_category_name`,
                `store_category2_id`,
                `store_category2_name`,
                `weight`,
                `dispatch_time_max`,
                `dimensions_depth`,
                `dimensions_length`,
                `dimensions_weight`
         FROM `m2e_e2m_ebay_items`
                  LEFT JOIN `m2e_e2m_ebay_item_variations`
                            ON `m2e_e2m_ebay_items`.id = `m2e_e2m_ebay_item_variations`.`item_id`
         WHERE `m2e_e2m_ebay_item_variations`.`id` IS NOT NULL AND `m2e_e2m_ebay_items`.`site` = ?
         GROUP BY `m2e_e2m_ebay_items`.`id`
     ) as `items`
SQL;

        $getImagesForItemSQL = <<<SQL
SELECT GROUP_CONCAT(DISTINCT `path` SEPARATOR ',') AS `images`
FROM (
         SELECT `path` FROM `m2e_e2m_ebay_item_images` WHERE `item_id` = ?

         UNION

         SELECT `path` FROM `m2e_e2m_ebay_item_variation_images` WHERE `item_variation_id` = ?
     ) as `images`
SQL;

        $getSpecificsForItemSQL = <<<SQL
SELECT `type`, `name`, `value`
FROM (
         SELECT (SELECT 'specific') as `type`, `name`, `value` FROM `m2e_e2m_ebay_item_specifics` WHERE `item_id` = ?

         UNION

         SELECT (SELECT 'variation') as `type`, `name`, `value` FROM `m2e_e2m_ebay_item_variation_specifics` WHERE `item_variation_id` = ?
     ) as `specifics`
SQL;

        $items = Mage::getSingleton('core/resource')->getConnection('core_read')->query(
            $getItemsSQL,
            array($site, $site, $site)
        );

        $variationData = array();
        while ($item = $items->fetch(PDO::FETCH_ASSOC)) {
            $item = Mage::helper('e2m')->applySettings($item);
            $product = $productSkeleton;

            foreach ($fieldsAttributes as $magentoAttribute => $eBayField) {
                $eBayField === 'quantity' && $product['is_in_stock'] = (int)(0 < (int)$item[$eBayField]);

                $product[$magentoAttribute] = Mage::helper('e2m')->getValue($item[$eBayField], $defaultValue);
            }

            $product['_attribute_set'] = $attributeSetName;
            //$product['_store'] = $storeCode;
            $product['_type'] = $item['type'];
            $product['sku'] = $item[Mage::helper('e2m/Ebay_Config')->getProductIdentifier()];
            $product['status'] = 1;
            $product['visibility'] = 1;
            $product['tax_class_id'] = 0;

            $specifics = Mage::getSingleton('core/resource')->getConnection('core_read')->query(
                $getSpecificsForItemSQL,
                array($item['id'], $item['item_variation_id'])
            );
            while ($specific = $specifics->fetch(PDO::FETCH_ASSOC)) {
                $value = Mage::helper('e2m')->getValue($specific['value'], $defaultValue);
                $code = Mage::helper('e2m')->getCode($specific['name']);
                $product[$code] = $value;

                if ('variation' !== $specific['type']) {
                    continue;
                }

                $variationData[$item['id']][$product['sku']][$code] = $value;
            }

            $images = array();
            $media = Mage::getSingleton('core/resource')->getConnection('core_read')->query(
                $getImagesForItemSQL,
                array($item['id'], $item['item_variation_id'])
            )->fetchColumn();
            if (!empty($media)) {

                foreach (explode(',', $media) as $img) {
                    $image = $productSkeleton;

                    $image['_media_attribute_id'] = $mediaAttributeId;
                    $image['_media_image'] = $img;

                    $images[] = $image;
                }

                $image = array_shift($images);
                $product['image'] = $image['_media_image'];
                $product['small_image'] = $image['_media_image'];
                $product['thumbnail'] = $image['_media_image'];
                $product['_media_attribute_id'] = $image['_media_attribute_id'];
                $product['_media_image'] = $image['_media_image'];

                if (!empty($images)) {
                    foreach ($images as &$image) {
                        $image = implode(',', $image);
                    }
                    unset($image);
                }
            }

            $variations = array();
            if ('configurable' === $item['type']) {

                foreach ($variationData[$item['id']] as $sku => $data) {
                    foreach ($data as $code => $value) {
                        $variation = $productSkeleton;

                        $variation['_super_products_sku'] = $sku;
                        $variation['_super_attribute_code'] = $code;
                        $variation['_super_attribute_option'] = $value;

                        $variations[] = $variation;
                    }
                }

                $variation = array_shift($variations);
                $product['_super_products_sku'] = $variation['_super_products_sku'];
                $product['_super_attribute_code'] = $variation['_super_attribute_code'];
                $product['_super_attribute_option'] = $variation['_super_attribute_option'];

                if (!empty($variations)) {
                    foreach ($variations as &$variation) {
                        $variation = implode(',', $variation);
                    }
                    unset($variation);
                }
            }

            //todo delete
            $product['short_description'] = strip_tags($product['short_description']);
            //todo delete

            $product = implode(',', $product);
            !empty($images) && $product .= PHP_EOL . implode(PHP_EOL, $images);
            !empty($variations) && $product .= PHP_EOL . implode(PHP_EOL, $variations);

            Mage::helper('e2m')->writeInventoryFile($prefixPath, $product, $csvHeader, 'm1');
        }

        return $this->ajaxResponse(array('completed' => true));
    }

    public function collectAttributesM1Action() {

        $prefixPath = Mage::getBaseDir('var') . DS . 'e2m' . DS;
        $attributesFile = 'ebay_attributes.csv';
        $file = 'ebay_m1_attributes.sql';
        $transactionSQL = '';

        if (!file_exists($prefixPath . $attributesFile)) {
            $this->collectAttributesCSVAction();
        }

        list($site, $store) = Mage::helper('e2m/Ebay_Config')->getSiteAndStore();
        $attributeSetName = Mage::getSingleton('core/resource')->getConnection('core_read')->quote(
            Mage::helper('e2m')->getAttributeSetNameById(
                Mage::helper('e2m')->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET)
            )
        );
        $stores = array_unique(array(M2E_E2M_Helper_Ebay_Config::STORE_ADMIN, $store));

        //----------------------------------------

        $attributes = fopen($prefixPath . $attributesFile, 'r');
        $header = fgetcsv($attributes, null, ',');
        while ($attribute = fgetcsv($attributes, null, ',')) {
            $attribute = array_combine(array_values($header), array_values($attribute));

            $name = Mage::getSingleton('core/resource')->getConnection('core_read')->quote($attribute['name']);
            $code = Mage::getSingleton('core/resource')->getConnection('core_read')->quote($attribute['code']);
            $isVariationSpecific = (int)($attribute['type'] === 'select');
            $group = $isVariationSpecific ? 'main_group_id' : 'specific_group_id';
            $sourceModel = $isVariationSpecific ? Mage::getSingleton('core/resource')
                ->getConnection('core_read')->quote('eav/entity_attribute_source_table') : 'NULL';
            $type = $attribute['type'];
            $transactionSQL .= <<<SQL

    -- {$code}

    SET @attribute_code = {$code};
    SET @frontend_input = {$name};

    INSERT INTO `eav_attribute` (`entity_type_id`, `attribute_code`, `backend_type`, `frontend_input`, `frontend_label`, `source_model`, `is_required`, `is_user_defined`, `default_value`, `is_unique`)
    VALUES (@entity_type_id, @attribute_code, 'varchar', '{$type}', @frontend_input, {$sourceModel}, 0, 1, NULL, 0);
    SET @attribute_id = LAST_INSERT_ID();

    INSERT INTO `eav_entity_attribute` (`entity_type_id`, `attribute_set_id`, `attribute_group_id`, `attribute_id`)
    VALUES (@entity_type_id, @attribute_set_id, @{$group}, @attribute_id);

    INSERT INTO `catalog_eav_attribute` (`attribute_id`, `is_global`, `is_visible`, `is_searchable`, `is_filterable`, `is_comparable`, `is_visible_on_front`, `is_html_allowed_on_front`, `is_used_for_price_rules`, `is_filterable_in_search`, `used_in_product_listing`, `used_for_sort_by`, `is_configurable`, `apply_to`, `is_visible_in_advanced_search`, `position`, `is_wysiwyg_enabled`, `is_used_for_promo_rules`)
    VALUES (@attribute_id, 1, 1, 0, 0, 1, 0, 1, 0, 0, 0, 0, {$isVariationSpecific}, 'simple,configurable', 1, 0, 0, 0);
SQL;

            foreach ($stores as $storeId) {
                $transactionSQL .= <<<SQL


    INSERT INTO `eav_attribute_label` (`attribute_id`, `store_id`, `value`) VALUES (@attribute_id, {$storeId}, @frontend_input);
SQL;
            }

            if (!$isVariationSpecific) {
                $transactionSQL .= "\n\n\t-- end {$code}\n";
                continue;
            }

            foreach (explode(';', $attribute['values']) as $value) {
                $value = Mage::getSingleton('core/resource')->getConnection('core_read')->quote(trim($value));
                $transactionSQL .= <<<SQL


    INSERT INTO `eav_attribute_option` (`attribute_id`) VALUES (@attribute_id);
    SET @option_id = LAST_INSERT_ID();
SQL;

                foreach ($stores as $storeId) {
                    $transactionSQL .= <<<SQL


    INSERT INTO `eav_attribute_option_value` (`option_id`, `store_id`, `value`)
    VALUES (@option_id, {$storeId}, {$value});
SQL;
                }
            }

            $transactionSQL .= "\n\n\t-- end {$code}\n";
        }

        fclose($attributes);

        $sql = <<<SQL
DROP PROCEDURE IF EXISTS createAttributes;
DELIMITER ;;
CREATE PROCEDURE createAttributes()
BEGIN
    DECLARE error_code INT;
    DECLARE error_message TEXT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
        BEGIN
            GET CURRENT DIAGNOSTICS CONDITION 1 error_code = MYSQL_ERRNO, error_message = MESSAGE_TEXT;
            SELECT error_code as `Code`, error_message as `Message`;

            ROLLBACK;
        END;

    DECLARE EXIT HANDLER FOR SQLWARNING
        BEGIN
            GET CURRENT DIAGNOSTICS CONDITION 1 error_code = MYSQL_ERRNO, error_message = MESSAGE_TEXT;
            SELECT error_code as `Code`, error_message as `Message`;

            ROLLBACK;
        END;

    SET @entity_type_id = (SELECT `entity_type_id` FROM `eav_entity_type` WHERE `entity_type_code` = 'catalog_product' LIMIT 1);
    SET @attribute_set_id = (SELECT `attribute_set_id` FROM `eav_attribute_set` WHERE `attribute_set_name` = {$attributeSetName} AND `entity_type_id` = @entity_type_id LIMIT 1);
    SET @main_group_id = (SELECT `attribute_group_id` FROM `eav_attribute_group` WHERE `attribute_group_name` = 'eBay' AND `attribute_set_id` = @attribute_set_id LIMIT 1);
    SET @specific_group_id = (SELECT `attribute_group_id` FROM `eav_attribute_group` WHERE `attribute_group_name` = 'eBay Specifics' AND `attribute_set_id` = @attribute_set_id LIMIT 1);

    START TRANSACTION;
    {$transactionSQL}
    COMMIT;
END;
;;
DELIMITER ;
CALL createAttributes();
SQL;

        file_put_contents($prefixPath . $file, $sql, LOCK_EX);

        return $this->ajaxResponse(array('completed' => true));
    }

    public function collectAttributesCSVAction() {

        list($site, $store) = Mage::helper('e2m/Ebay_Config')->getSiteAndStore();
        $prefixPath = Mage::getBaseDir('var') . DS . 'e2m' . DS;
        $file = 'ebay_attributes.csv';

        Mage::getSingleton('core/resource')->getConnection('core_read')->query(
            'SET SESSION GROUP_CONCAT_MAX_LEN = 4294967295;'
        );

        //----------------------------------------

        $specificsSQL = <<<SQL
SELECT MAX(`type`) as `type`, `name`, GROUP_CONCAT(DISTINCT `values`) as `values`
FROM (
         SELECT (SELECT 0) AS `type`, `name`, GROUP_CONCAT(DISTINCT `value` SEPARATOR ';') AS `values`
         FROM `m2e_e2m_ebay_item_specifics`
                  LEFT JOIN `m2e_e2m_ebay_items` ON `m2e_e2m_ebay_item_specifics`.`item_id` = `m2e_e2m_ebay_items`.`id`
         WHERE `m2e_e2m_ebay_items`.`site` = ?
         GROUP BY `name`

         UNION

         SELECT (SELECT 1) AS `type`, `name`, GROUP_CONCAT(DISTINCT `value` SEPARATOR ';') AS `values`
         FROM `m2e_e2m_ebay_item_variation_specifics`
                  LEFT JOIN `m2e_e2m_ebay_item_variations` ON `m2e_e2m_ebay_item_variation_specifics`.`item_variation_id` = `m2e_e2m_ebay_item_variations`.`id`
                  LEFT JOIN `m2e_e2m_ebay_items` ON `m2e_e2m_ebay_item_variations`.`item_id` = `m2e_e2m_ebay_items`.`id`
         WHERE `m2e_e2m_ebay_items`.`site` = ?
         GROUP BY `name`
     ) as `specifics`
GROUP BY `name`;
SQL;
        $specifics = Mage::getSingleton('core/resource')->getConnection('core_read')->query(
            $specificsSQL,
            array($site, $site)
        );

        file_put_contents($prefixPath . $file, 'type,code,name,values' . PHP_EOL);
        $fp = fopen($prefixPath . $file, 'a');
        while ($specific = $specifics->fetch(PDO::FETCH_ASSOC)) {
            if (empty($specific['name'])) {
                continue;
            }

            $name = $specific['name'];
            fputcsv($fp, array(
                /** type   => */ (bool)$specific['type'] ? 'select' : 'text',
                /** code   => */ Mage::helper('e2m')->getCode($name),
                /** name   => */ $name,
                /** values => */ $specific['values']
            ));
        }

        fclose($fp);

        return $this->ajaxResponse(array('completed' => true));
    }

    //########################################

    public function setSettingsAction() {

        $coreHelper = Mage::helper('core');
        $settings = $coreHelper->jsonDecode($this->getRequest()->getParam('settings'));

        //----------------------------------------

        Mage::helper('e2m')->setConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET, $settings['attribute-set']);
        Mage::helper('e2m')->setConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_STORE_MAP, $settings['marketplace-store']);
        Mage::helper('e2m')->setConfig(
            M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_MAP,
            $settings['ebay-field-magento-attribute']
        );
        Mage::helper('e2m')->setConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_GENERATE_SKU, $settings['generate-sku']);
        Mage::helper('e2m')->setConfig(
            M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_IDENTIFIER,
            $settings['product-identifier']
        );
        Mage::helper('e2m')->setConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_DELETE_HTML, $settings['delete-html']);
        Mage::helper('e2m')->setConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_FOUND, $settings['action-found']);
        Mage::helper('e2m')->setConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_IMPORT_IMAGE, $settings['import-image']);
        Mage::helper('e2m')->setConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_IMPORT_QTY, $settings['import-qty'], true);
        Mage::helper('e2m')->setConfig(
            M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_IMPORT_SPECIFICS,
            $settings['import-specifics'],
            false
        );
        Mage::helper('e2m')->setConfig(
            M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_IMPORT_RENAME_ATTRIBUTE,
            $settings['rename-attribute-title-for-specifics'],
            false
        );

        //----------------------------------------

        Mage::dispatchEvent('m2e_e2m_change_ebay_settings');

        //----------------------------------------

        $this->_getSession()->addSuccess(Mage::helper('e2m')->__('Save settings'));

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
