<?php

class M2E_E2M_Adminhtml_E2mController extends Mage_Adminhtml_Controller_Action {

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Exception
     */
    public function getM2InventoryExportCSVAction() {

        $prefixPath = Mage::helper('e2m')->getFullPath();
        $defaultValue = '__EMPTY__VALUE__';

        $readConn = Mage::getSingleton('core/resource')->getConnection('core_read');
        $attributeSetName = Mage::helper('e2m')->getValue(Mage::helper('e2m/Magento')->getAttributeSetNameById(
            Mage::helper('e2m/Config')->get(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET)
        ), $defaultValue);

        if (!file_exists(Mage::helper('e2m')->getFullPath('ebay_attributes_export.csv'))) {
            $this->getAttributesExportCSVAction();
        }
        $exportAttributes = Mage::helper('e2m/Ebay')->getExportAttributes(true);

        if (!file_exists(Mage::helper('e2m')->getFullPath('ebay_attributes_matching.csv'))) {
            $this->getAttributesMatchingCSVAction();
        }
        $exportSpecifics = Mage::helper('e2m/Ebay')->getExportSpecifics();

        //----------------------------------------

        $csvHeader = array(
            // required
            'attribute_set_code',
            'product_type',
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

            'configurable_variations',
            'configurable_variation_labels',

            'store_view_code',
            'base_image',
            'small_image',
            'thumbnail_image',
            'additional_images'
        );

        $csvHeader = array_unique(array_merge(
            $csvHeader,
            array_keys($exportAttributes),
            array_values($exportSpecifics)
        ));
        $csvHeader = array_filter($csvHeader);
        sort($csvHeader);

        $productSkeleton = array_combine(
            array_values($csvHeader),
            array_fill(0, count($csvHeader), $defaultValue)
        );

        //----------------------------------------

        $getItemsSQL = <<<SQL
SELECT `type`,
       `id`,
       `item_variation_id`,
       `item_variation_hash` AS `ebay_item_variation_hash`,
       `site` AS `ebay_site`,
       `ebay_item_id`,
       `sku` AS `ebay_sku`,
       `upc` AS `ebay_upc`,
       `ean` AS `ebay_ean`,
       `isbn` AS `ebay_isbn`,
       `ePID` AS `ebay_epid`,
       `mpn` AS `ebay_mpn`,
       `brand` AS `ebay_brand`,
       `title` AS `ebay_title`,
       `subtitle` AS `ebay_subtitle`,
       `description` AS `ebay_description`,
       `currency` AS `ebay_currency`,
       `start_price` AS `ebay_start_price`,
       `current_price` AS `ebay_current_price`,
       `buy_it_now` AS `ebay_buy_it_now`,
       `quantity` AS `ebay_quantity`,
       `condition_id` AS `ebay_condition_id`,
       `condition_name` AS `ebay_condition_name`,
       `condition_description` AS `ebay_condition_description`,
       `primary_category_id` AS `ebay_primary_category_id`,
       `primary_category_name` AS `ebay_primary_category_name`,
       `secondary_category_id` AS `ebay_secondary_category_id`,
       `secondary_category_name` AS `ebay_secondary_category_name`,
       `store_category_id` AS `ebay_store_category_id`,
       `store_category_name` AS `ebay_store_category_name`,
       `store_category2_id` AS `ebay_store_category2_id`,
       `store_category2_name` AS `ebay_store_category2_name`,
       `weight` AS `ebay_weight`,
       `dispatch_time_max` AS `ebay_dispatch_time_max`,
       `dimensions_depth` AS `ebay_dimensions_depth`,
       `dimensions_length` AS `ebay_dimensions_length`,
       `dimensions_weight` AS `ebay_dimensions_weight`
FROM (
         SELECT (SELECT 'simple') AS `type`,
                `m2e_e2m_ebay_items`.`id`,
                (SELECT NULL)     AS `item_variation_id`,
                (SELECT NULL)     AS `item_variation_hash`,
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
         WHERE `m2e_e2m_ebay_item_variations`.`id` IS NULL

         UNION

         SELECT (SELECT 'simple')                     AS `type`,
                `m2e_e2m_ebay_items`.`id`             AS `id`,
                `m2e_e2m_ebay_item_variations`.`id`   AS `item_variation_id`,
                `m2e_e2m_ebay_item_variations`.`hash` AS `item_variation_hash`,
                `site`,
                `ebay_item_id`,
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

         UNION

         SELECT (SELECT 'configurable') AS `type`,
                `m2e_e2m_ebay_items`.`id`,
                (SELECT NULL)           AS `item_variation_id`,
                (SELECT NULL)           AS `item_variation_hash`,
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
         WHERE `m2e_e2m_ebay_item_variations`.`id` IS NOT NULL
         GROUP BY `m2e_e2m_ebay_items`.`id`

     ) as `items`
ORDER BY `type` DESC, `ebay_sku`, `ebay_site` DESC;
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

        $variationData = array();

        $items = $readConn->query($getItemsSQL);
        while ($item = $items->fetch(PDO::FETCH_ASSOC)) {
            if (Mage::helper('e2m/Ebay_Config')->isMarketplaceSkip($item['ebay_site'])) {
                continue;
            }

            $item = Mage::helper('e2m/Ebay_Config')->applySettings($item);

            $parentAndChildIds = array($item['id'], $item['item_variation_id']);

            $product = $productSkeleton;
            $product['sku'] = $item[Mage::helper('e2m/Ebay_Config')->getProductIdentifier()];
            $product['store_view_code'] = Mage::helper('e2m/Magento')->getStoreCodeById(
                Mage::helper('e2m/Ebay_Config')->getStoreIdByMarketplaceCode($item['ebay_site'])
            );

            $specifics = $readConn->query($getSpecificsForItemSQL, $parentAndChildIds);
            while ($specific = $specifics->fetch(PDO::FETCH_ASSOC)) {
                if (empty($specific['name'])) {
                    continue;
                }

                $item[$specific['name']] = $specific['value'];
                if ('variation' !== $specific['type']) {
                    continue;
                }

                $name = $specific['name'];
                $code = Mage::helper('e2m')->getCode($name);

                $variationData[$item['id']]['configurable_variations'][$product['sku']] = 'sku=' . $product['sku'];
                $variationData[$item['id']]['configurable_variation_labels'][$code] = "{$code}={$name}";
            }

            foreach ($exportSpecifics as $specificName => $magentoAttribute) {
                if (!isset($item[$specificName])) {
                    continue;
                }

                $product[$magentoAttribute] = Mage::helper('e2m')->getValue($item[$specificName], $defaultValue);
            }

            foreach ($exportAttributes as $magentoAttribute => $eBayField) {
                if (!isset($item[$eBayField])) {
                    continue;
                }

                if ('ebay_quantity' === $eBayField) {
                    $product['is_in_stock'] = (int)(0 < (int)$item[$eBayField]);
                }

                $product[$magentoAttribute] = Mage::helper('e2m')->getValue($item[$eBayField], $defaultValue);
            }

            $media = $readConn->query($getImagesForItemSQL, $parentAndChildIds)->fetchColumn();
            if (!empty($media)) {
                $product['additional_images'] = Mage::helper('e2m')->getValue($media, $defaultValue);

                $image = array_shift(explode(',', $media));
                $product['base_image'] = $image;
                $product['small_image'] = $image;
                $product['thumbnail_image'] = $image;
            }

            if ('configurable' === $item['type']) {

                $product['configurable_variation_labels'] = Mage::helper('e2m')->getValue(implode(',',
                    $variationData[$item['id']]['configurable_variation_labels']
                ), $defaultValue);

                $product['configurable_variations'] = Mage::helper('e2m')->getValue(implode('|',
                    $variationData[$item['id']]['configurable_variations']
                ), $defaultValue);
            }

            $product['attribute_set_code'] = Mage::helper('e2m')->getValue($attributeSetName, $defaultValue);
            $product['product_type'] = $item['type'];
            $product['status'] = 1;
            $product['visibility'] = Mage::helper('e2m')->getValue('Not Visible Individually', $defaultValue);
            $product['tax_class_id'] = Mage::helper('e2m')->getValue('None', $defaultValue);

            Mage::helper('e2m')->writeCSVFile($prefixPath, implode(',', $product), $csvHeader, 'm2');
        }

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('data' => array(
            'completed' => true
        ))));
    }

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Exception
     */
    public function getM2AttributesSQLAction() {

        $file = Mage::helper('e2m')->getFullPath('ebay_attributes_m2.sql');
        $transactionSQL = '';

        $readConn = Mage::getSingleton('core/resource')->getConnection('core_read');
        $attributeSetName = $readConn->quote(Mage::helper('e2m/Magento')->getAttributeSetNameById(
            Mage::helper('e2m/Config')->get(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET)
        ));

        if (!file_exists(Mage::helper('e2m')->getFullPath('ebay_attributes_export.csv'))) {
            $this->getAttributesExportCSVAction();
        }
        $attributesExport = Mage::helper('e2m/Ebay')->getExportAttributes();

        if (!file_exists(Mage::helper('e2m')->getFullPath('ebay_attributes_matching.csv'))) {
            $this->getAttributesMatchingCSVAction();
        }
        $attributesMatching = Mage::helper('e2m/Ebay')->getMatchingAttributes();

        //----------------------------------------

        foreach ($attributesExport as $ebay => &$magento) {
            if (!isset($attributesMatching[$ebay])) {
                continue;
            }

            if (isset($attributesMatching[$magento])) {
                continue;
            }

            $attributesMatching[$magento] = $attributesMatching[$ebay];
            unset($attributesMatching[$ebay]);
            unset($attributesExport[$ebay]);
        }

        //----------------------------------------

        $adminStore = Mage::helper('e2m/Ebay_Config')->getMarketplaceCodeUseAdminStore();
        foreach ($attributesMatching as $attributeCode => $attributeData) {
            if (empty($attributeCode)) {
                continue;
            }

            $code = $readConn->quote($attributeCode);
            $type = $attributeData['type'];
            $isSelect = (int)(M2E_E2M_Helper_Magento::TYPE_SELECT === $type);
            $sourceModel = $isSelect ? $readConn->quote('Magento\\Eav\\Model\\Entity\\Attribute\\Source\\Table') : 'NULL';
            $group = $isSelect ? 'main_group_id' : 'specific_group_id';

            $name = reset($attributeData['name']);
            if ($adminStore !== false && isset($attributeData['name'][$adminStore])) {
                $name = $attributeData['name'][$adminStore];
            }
            $name = $readConn->quote($name);

            $transactionSQL .= <<<SQL

    -- {$code}

    SET @attribute_code = {$code};
    SET @frontend_input = {$name};

    SET @attribute_id = (SELECT `attribute_id` FROM `eav_attribute` WHERE `attribute_code` = @attribute_code LIMIT 1);
    IF @attribute_id IS NULL THEN
        INSERT INTO `eav_attribute` (`entity_type_id`, `attribute_code`, `backend_type`, `frontend_input`, `frontend_label`, `source_model`, `is_required`, `is_user_defined`, `default_value`, `is_unique`)
        VALUES (@entity_type_id, @attribute_code, 'varchar', '{$type}', @frontend_input, {$sourceModel}, 0, 1, NULL, 0);
        SET @attribute_id = LAST_INSERT_ID();

        INSERT INTO `catalog_eav_attribute` (`attribute_id`, `is_global`, `is_visible`, `is_searchable`, `is_filterable`, `is_comparable`, `is_visible_on_front`, `is_html_allowed_on_front`, `is_used_for_price_rules`, `is_filterable_in_search`, `used_in_product_listing`, `used_for_sort_by`, `apply_to`, `is_visible_in_advanced_search`, `position`, `is_wysiwyg_enabled`, `is_used_for_promo_rules`)
        VALUES (@attribute_id, {$isSelect}, 1, 0, 0, 1, 0, 1, 0, 0, 0, 0, 'simple,configurable', 1, 0, 0, 0);
    END IF;

    IF NOT EXISTS(SELECT `attribute_id` FROM `eav_entity_attribute` WHERE `entity_type_id` = @entity_type_id AND `attribute_set_id` = @attribute_set_id AND `attribute_id` = @attribute_id) THEN
        INSERT INTO `eav_entity_attribute` (`entity_type_id`, `attribute_set_id`, `attribute_group_id`, `attribute_id`)
        VALUES (@entity_type_id, @attribute_set_id, @{$group}, @attribute_id);
    END IF;
SQL;

            foreach ($attributeData['name'] as $site => $value) {
                $storeId = Mage::helper('e2m/Ebay_Config')->getStoreIdByMarketplaceCode($site);
                if (M2E_E2M_Helper_Magento::STORE_SKIP === $storeId) {
                    continue;
                }

                $name = $readConn->quote($value);

                $transactionSQL .= <<<SQL


    IF NOT EXISTS(SELECT `attribute_id` FROM `eav_attribute_label` WHERE `attribute_id` = @attribute_id AND `store_id` = {$storeId}) THEN
        INSERT INTO `eav_attribute_label` (`attribute_id`, `store_id`, `value`) VALUES (@attribute_id, {$storeId}, {$name});
    END IF;
SQL;
            }

            if (!isset($attributeData['name'][$adminStore])) {
                $storeId = M2E_E2M_Helper_Magento::STORE_ADMIN;

                $transactionSQL .= <<<SQL


    IF NOT EXISTS(SELECT `attribute_id` FROM `eav_attribute_label` WHERE `attribute_id` = @attribute_id AND `store_id` = {$storeId}) THEN
        INSERT INTO `eav_attribute_label` (`attribute_id`, `store_id`, `value`) VALUES (@attribute_id, {$storeId}, @frontend_input);
    END IF;
SQL;
            }

            if (!$isSelect) {
                $transactionSQL .= "\n\n\t-- end {$code}\n";

                continue;
            }

            $transactionSQL .= <<<SQL


    IF EXISTS(SELECT `backend_type` FROM `eav_attribute` WHERE `attribute_id` = @attribute_id AND `backend_type` = '{$type}') THEN
SQL;

            foreach ($attributeData['value'] as $valueCode => $attributeValueData) {

                $value = false;
                if ($adminStore !== false && isset($attributeValueData[strtolower($adminStore)])) {
                    $value = $readConn->quote($attributeValueData[strtolower($adminStore)]);
                } else {
                    foreach ($attributeValueData as $site => $datum) {
                        $value = $readConn->quote($datum);
                        break;
                    }
                }

                $transactionSQL .= <<<SQL


        SET @option_id = (SELECT `eav_attribute_option`.`option_id` FROM `eav_attribute_option` LEFT JOIN `eav_attribute_option_value` ON `eav_attribute_option`.`option_id` = `eav_attribute_option_value`.`option_id` WHERE `attribute_id` = @attribute_id AND `value` = {$value} LIMIT 1);
        IF @option_id IS NULL THEN
            INSERT INTO `eav_attribute_option` (`attribute_id`) VALUES (@attribute_id);
            SET @option_id = LAST_INSERT_ID();
        END IF;
SQL;
                $addingAdminValue = false;
                $adminValue = false;
                foreach ($attributeValueData as $site => $value) {
                    $storeId = Mage::helper('e2m/Ebay_Config')->getStoreIdByMarketplaceCode($site);
                    if (M2E_E2M_Helper_Magento::STORE_SKIP === $storeId) {
                        continue;
                    }

                    $adminValue = $value = $readConn->quote($value);
                    if (M2E_E2M_Helper_Magento::STORE_ADMIN === $storeId) {
                        $addingAdminValue = true;
                    }

                    $transactionSQL .= <<<SQL


        IF NOT EXISTS(SELECT * FROM `eav_attribute_option_value` WHERE `option_id` = @option_id AND `store_id` = {$storeId}) THEN
            INSERT INTO `eav_attribute_option_value` (`option_id`, `store_id`, `value`) VALUES (@option_id, {$storeId}, {$value});
        END IF;
SQL;
                }

                if (!$addingAdminValue) {
                    $transactionSQL .= <<<SQL


        IF NOT EXISTS(SELECT * FROM `eav_attribute_option_value` WHERE `option_id` = @option_id AND `store_id` = 0) THEN
            INSERT INTO `eav_attribute_option_value` (`option_id`, `store_id`, `value`) VALUES (@option_id, 0, {$adminValue});
        END IF;
SQL;
                }
            }

            $transactionSQL .= <<<SQL


    END IF;
SQL;
            $transactionSQL .= "\n\n\t-- end {$code}\n";
        }

        $stores = array_unique(array_merge(
            array(M2E_E2M_Helper_Magento::STORE_ADMIN),
            array_values(Mage::helper('e2m/Config')->get(
                M2E_E2M_Helper_Ebay_Config::XML_PATH_STORE_MAP, array()
            ))
        ));

        unset($attributesExport['ebay_sku'], $attributesExport['ebay_quantity']);
        foreach ($attributesExport as $ebay => $magento) {
            if (!empty($magento)) {
                continue;
            }

            $title = str_replace('_', ' ', $ebay);
            $title = str_replace('Ebay', 'eBay', ucwords($title));
            $name = $readConn->quote($title);
            $code = $readConn->quote($ebay);

            $transactionSQL .= <<<SQL

    -- {$code}

    SET @attribute_code = {$code};
    SET @frontend_input = {$name};

    SET @attribute_id = (SELECT `attribute_id` FROM `eav_attribute` WHERE `attribute_code` = @attribute_code LIMIT 1);
    IF @attribute_id IS NULL THEN
        INSERT INTO `eav_attribute` (`entity_type_id`, `attribute_code`, `backend_type`, `frontend_input`, `frontend_label`, `source_model`, `is_required`, `is_user_defined`, `default_value`, `is_unique`)
        VALUES (@entity_type_id, @attribute_code, 'varchar', 'text', @frontend_input, NULL, 0, 1, NULL, 0);
        SET @attribute_id = LAST_INSERT_ID();

        INSERT INTO `catalog_eav_attribute` (`attribute_id`, `is_global`, `is_visible`, `is_searchable`, `is_filterable`, `is_comparable`, `is_visible_on_front`, `is_html_allowed_on_front`, `is_used_for_price_rules`, `is_filterable_in_search`, `used_in_product_listing`, `used_for_sort_by`, `is_configurable`, `apply_to`, `is_visible_in_advanced_search`, `position`, `is_wysiwyg_enabled`, `is_used_for_promo_rules`)
        VALUES (@attribute_id, 1, 1, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 'simple,configurable', 1, 0, 0, 0);
    END IF;

    IF NOT EXISTS(SELECT `attribute_id` FROM `eav_entity_attribute` WHERE `entity_type_id` = @entity_type_id AND `attribute_set_id` = @attribute_set_id AND `attribute_id` = @attribute_id) THEN
        INSERT INTO `eav_entity_attribute` (`entity_type_id`, `attribute_set_id`, `attribute_group_id`, `attribute_id`)
        VALUES (@entity_type_id, @attribute_set_id, @main_group_id, @attribute_id);
    END IF;
SQL;

            foreach ($stores as $storeId) {
                if (M2E_E2M_Helper_Magento::STORE_SKIP === $storeId) {
                    continue;
                }

                $transactionSQL .= <<<SQL


    IF NOT EXISTS(SELECT `attribute_id` FROM `eav_attribute_label` WHERE `attribute_id` = @attribute_id AND `store_id` = {$storeId}) THEN
        INSERT INTO `eav_attribute_label` (`attribute_id`, `store_id`, `value`) VALUES (@attribute_id, {$storeId}, {$name});
    END IF;
SQL;
            }

            $transactionSQL .= "\n\n\t-- end {$code}\n";
        }

        $sql = <<<SQL
DROP PROCEDURE IF EXISTS createM2Attributes;
DELIMITER ;;
CREATE PROCEDURE createM2Attributes()
BEGIN
    DECLARE error_code INT;
    DECLARE error_message TEXT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN
        GET DIAGNOSTICS CONDITION 1 error_code = MYSQL_ERRNO, error_message = MESSAGE_TEXT;
        SELECT error_code as `Code`, error_message as `Message`;

        ROLLBACK;
    END;

    DECLARE EXIT HANDLER FOR SQLWARNING BEGIN
        GET DIAGNOSTICS CONDITION 1 error_code = MYSQL_ERRNO, error_message = MESSAGE_TEXT;
        SELECT error_code as `Code`, error_message as `Message`;

        ROLLBACK;
    END;

    SET @entity_type_id = (SELECT `entity_type_id` FROM `eav_entity_type` WHERE `entity_type_code` = 'catalog_product' LIMIT 1);
    SET @attribute_set_id = (SELECT `attribute_set_id` FROM `eav_attribute_set` WHERE `attribute_set_name` = {$attributeSetName} AND `entity_type_id` = @entity_type_id LIMIT 1);

    SET @main_group_id = (SELECT `attribute_group_id` FROM `eav_attribute_group` WHERE `attribute_group_name` = 'eBay' AND `attribute_set_id` = @attribute_set_id LIMIT 1);
    IF @main_group_id IS NULL THEN
        INSERT INTO `eav_attribute_group` (`attribute_set_id`, `attribute_group_name`, `sort_order`, `default_id`, `attribute_group_code`)
        VALUES (@attribute_set_id, 'eBay', 20, 0, 'ebay');
        SET @main_group_id = LAST_INSERT_ID();
    END IF;

    SET @specific_group_id = (SELECT `attribute_group_id` FROM `eav_attribute_group` WHERE `attribute_group_name` = 'eBay Specifics' AND `attribute_set_id` = @attribute_set_id LIMIT 1);
    IF @specific_group_id IS NULL THEN
        INSERT INTO `eav_attribute_group` (`attribute_set_id`, `attribute_group_name`, `sort_order`, `default_id`, `attribute_group_code`)
        VALUES (@attribute_set_id, 'eBay Specifics', 30, 0, 'ebay-specifics');
        SET @specific_group_id = LAST_INSERT_ID();
    END IF;

    START TRANSACTION;
    {$transactionSQL}
    COMMIT;
END;
;;
DELIMITER ;
CALL createM2Attributes();
SQL;

        file_put_contents($file, $sql, LOCK_EX);

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('data' => array(
            'completed' => true
        ))));
    }

    //########################################

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Exception
     */
    public function getMagmiInventoryExportCSVAction() {

        $prefixPath = Mage::helper('e2m')->getFullPath();
        $defaultValue = '';

        $readConn = Mage::getSingleton('core/resource')->getConnection('core_read');
        $attributeSetName = Mage::helper('e2m')->getValue(Mage::helper('e2m/Magento')->getAttributeSetNameById(
            Mage::helper('e2m/Config')->get(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET)
        ), $defaultValue);

        if (!file_exists(Mage::helper('e2m')->getFullPath('ebay_attributes_export.csv'))) {
            $this->getAttributesExportCSVAction();
        }
        $exportAttributes = Mage::helper('e2m/Ebay')->getExportAttributes(true);

        if (!file_exists(Mage::helper('e2m')->getFullPath('ebay_attributes_matching.csv'))) {
            $this->getAttributesMatchingCSVAction();
        }
        $exportSpecifics = Mage::helper('e2m/Ebay')->getExportSpecifics();

        //----------------------------------------

        $csvHeader = array(
            // required
            'attribute_set',
            'type',
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

            'simples_skus',
            'configurable_attributes',

            'store',
            'image',
            'small_image',
            'thumbnail',
            'media_gallery'
        );

        $csvHeader = array_unique(array_merge(
            $csvHeader,
            array_keys($exportAttributes),
            array_values($exportSpecifics)
        ));
        $csvHeader = array_filter($csvHeader);
        sort($csvHeader);

        $productSkeleton = array_combine(
            array_values($csvHeader),
            array_fill(0, count($csvHeader), $defaultValue)
        );

        //----------------------------------------

        $getItemsSQL = <<<SQL
SELECT `type`,
       `id`,
       `item_variation_id`,
       `item_variation_hash` AS `ebay_item_variation_hash`,
       `site` AS `ebay_site`,
       `ebay_item_id`,
       `sku` AS `ebay_sku`,
       `upc` AS `ebay_upc`,
       `ean` AS `ebay_ean`,
       `isbn` AS `ebay_isbn`,
       `ePID` AS `ebay_epid`,
       `mpn` AS `ebay_mpn`,
       `brand` AS `ebay_brand`,
       `title` AS `ebay_title`,
       `subtitle` AS `ebay_subtitle`,
       `description` AS `ebay_description`,
       `currency` AS `ebay_currency`,
       `start_price` AS `ebay_start_price`,
       `current_price` AS `ebay_current_price`,
       `buy_it_now` AS `ebay_buy_it_now`,
       `quantity` AS `ebay_quantity`,
       `condition_id` AS `ebay_condition_id`,
       `condition_name` AS `ebay_condition_name`,
       `condition_description` AS `ebay_condition_description`,
       `primary_category_id` AS `ebay_primary_category_id`,
       `primary_category_name` AS `ebay_primary_category_name`,
       `secondary_category_id` AS `ebay_secondary_category_id`,
       `secondary_category_name` AS `ebay_secondary_category_name`,
       `store_category_id` AS `ebay_store_category_id`,
       `store_category_name` AS `ebay_store_category_name`,
       `store_category2_id` AS `ebay_store_category2_id`,
       `store_category2_name` AS `ebay_store_category2_name`,
       `weight` AS `ebay_weight`,
       `dispatch_time_max` AS `ebay_dispatch_time_max`,
       `dimensions_depth` AS `ebay_dimensions_depth`,
       `dimensions_length` AS `ebay_dimensions_length`,
       `dimensions_weight` AS `ebay_dimensions_weight`
FROM (
         SELECT (SELECT 'simple') AS `type`,
                `m2e_e2m_ebay_items`.`id`,
                (SELECT NULL)     AS `item_variation_id`,
                (SELECT NULL)     AS `item_variation_hash`,
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
         WHERE `m2e_e2m_ebay_item_variations`.`id` IS NULL

         UNION

         SELECT (SELECT 'simple')                     AS `type`,
                `m2e_e2m_ebay_items`.`id`             AS `id`,
                `m2e_e2m_ebay_item_variations`.`id`   AS `item_variation_id`,
                `m2e_e2m_ebay_item_variations`.`hash` AS `item_variation_hash`,
                `site`,
                `ebay_item_id`,
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

         UNION

         SELECT (SELECT 'configurable') AS `type`,
                `m2e_e2m_ebay_items`.`id`,
                (SELECT NULL)           AS `item_variation_id`,
                (SELECT NULL)           AS `item_variation_hash`,
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
         WHERE `m2e_e2m_ebay_item_variations`.`id` IS NOT NULL
         GROUP BY `m2e_e2m_ebay_items`.`id`

     ) as `items`
ORDER BY `type` DESC, `ebay_sku`, `ebay_site` DESC;
SQL;

        $getImagesForItemSQL = <<<SQL
SELECT GROUP_CONCAT(DISTINCT `path` SEPARATOR ';') AS `images`
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

        $variationData = array();

        $items = $readConn->query($getItemsSQL);
        while ($item = $items->fetch(PDO::FETCH_ASSOC)) {
            if (Mage::helper('e2m/Ebay_Config')->isMarketplaceSkip($item['ebay_site'])) {
                continue;
            }

            $item = Mage::helper('e2m/Ebay_Config')->applySettings($item);

            $parentAndChildIds = array($item['id'], $item['item_variation_id']);

            $product = $productSkeleton;
            $product['sku'] = $item[Mage::helper('e2m/Ebay_Config')->getProductIdentifier()];
            $product['store'] = Mage::helper('e2m/Magento')->getStoreCodeById(
                Mage::helper('e2m/Ebay_Config')->getStoreIdByMarketplaceCode($item['ebay_site'])
            );

            $specifics = $readConn->query($getSpecificsForItemSQL, $parentAndChildIds);
            while ($specific = $specifics->fetch(PDO::FETCH_ASSOC)) {
                if (empty($specific['name'])) {
                    continue;
                }

                $item[$specific['name']] = $specific['value'];
                if ('variation' !== $specific['type']) {
                    continue;
                }

                $code = Mage::helper('e2m')->getCode($specific['name']);
                $variationData[$item['id']]['simples_skus'][$product['sku']] = $product['sku'];
                $variationData[$item['id']]['configurable_attributes'][$code] = $code;
            }

            foreach ($exportSpecifics as $specificName => $magentoAttribute) {
                if (!isset($item[$specificName])) {
                    continue;
                }

                $product[$magentoAttribute] = Mage::helper('e2m')->getValue($item[$specificName], $defaultValue);
            }

            foreach ($exportAttributes as $magentoAttribute => $eBayField) {
                if (!isset($item[$eBayField])) {
                    continue;
                }

                if ('ebay_quantity' === $eBayField) {
                    $product['is_in_stock'] = (int)(0 < (int)$item[$eBayField]);
                }

                $product[$magentoAttribute] = Mage::helper('e2m')->getValue($item[$eBayField], $defaultValue);
            }

            $media = $readConn->query($getImagesForItemSQL, $parentAndChildIds)->fetchColumn();
            if (!empty($media)) {
                $product['media_gallery'] = $media;

                $image = array_shift(explode(';', $media));
                $product['image'] = $image;
                $product['small_image'] = $image;
                $product['thumbnail'] = $image;
            }

            if ('configurable' === $item['type']) {

                $product['simples_skus'] = Mage::helper('e2m')->getValue(implode(',',
                    $variationData[$item['id']]['simples_skus']
                ), $defaultValue);

                $product['configurable_attributes'] = Mage::helper('e2m')->getValue(implode(',',
                    $variationData[$item['id']]['configurable_attributes']
                ), $defaultValue);
            }

            $product['attribute_set'] = $attributeSetName;
            $product['type'] = $item['type'];
            $product['status'] = 'Enabled';
            $product['visibility'] = 1;
            $product['tax_class_id'] = 0;

            Mage::helper('e2m')->writeCSVFile($prefixPath, implode(',', $product), $csvHeader, 'magmi');
        }

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('data' => array(
            'completed' => true
        ))));
    }

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Exception
     */
    public function getNativeInventoryExportCSVAction() {

        $prefixPath = Mage::helper('e2m')->getFullPath();
        $defaultValue = '';

        $readConn = Mage::getSingleton('core/resource')->getConnection('core_read');
        $mediaAttributeId = Mage::helper('e2m/Magento')->getMediaAttributeId();
        $attributeSetName = Mage::helper('e2m')->getValue(Mage::helper('e2m/Magento')->getAttributeSetNameById(
            Mage::helper('e2m/Config')->get(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET)
        ), $defaultValue);

        if (!file_exists(Mage::helper('e2m')->getFullPath('ebay_attributes_export.csv'))) {
            $this->getAttributesExportCSVAction();
        }
        $exportAttributes = Mage::helper('e2m/Ebay')->getExportAttributes(true);

        if (!file_exists(Mage::helper('e2m')->getFullPath('ebay_attributes_matching.csv'))) {
            $this->getAttributesMatchingCSVAction();
        }
        $exportSpecifics = Mage::helper('e2m/Ebay')->getExportSpecifics();

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

            '_store',
            'image',
            'small_image',
            'thumbnail',
            '_media_attribute_id',
            '_media_image'
        );

        $csvHeader = array_unique(array_merge(
            $csvHeader,
            array_keys($exportAttributes),
            array_values($exportSpecifics)
        ));
        $csvHeader = array_filter($csvHeader);
        sort($csvHeader);

        $productSkeleton = array_combine(
            array_values($csvHeader),
            array_fill(0, count($csvHeader), $defaultValue)
        );

        //----------------------------------------

        $getItemsSQL = <<<SQL
SELECT `type`,
       `id`,
       `item_variation_id`,
       `item_variation_hash` AS `ebay_item_variation_hash`,
       `site` AS `ebay_site`,
       `ebay_item_id`,
       `sku` AS `ebay_sku`,
       `upc` AS `ebay_upc`,
       `ean` AS `ebay_ean`,
       `isbn` AS `ebay_isbn`,
       `ePID` AS `ebay_epid`,
       `mpn` AS `ebay_mpn`,
       `brand` AS `ebay_brand`,
       `title` AS `ebay_title`,
       `subtitle` AS `ebay_subtitle`,
       `description` AS `ebay_description`,
       `currency` AS `ebay_currency`,
       `start_price` AS `ebay_start_price`,
       `current_price` AS `ebay_current_price`,
       `buy_it_now` AS `ebay_buy_it_now`,
       `quantity` AS `ebay_quantity`,
       `condition_id` AS `ebay_condition_id`,
       `condition_name` AS `ebay_condition_name`,
       `condition_description` AS `ebay_condition_description`,
       `primary_category_id` AS `ebay_primary_category_id`,
       `primary_category_name` AS `ebay_primary_category_name`,
       `secondary_category_id` AS `ebay_secondary_category_id`,
       `secondary_category_name` AS `ebay_secondary_category_name`,
       `store_category_id` AS `ebay_store_category_id`,
       `store_category_name` AS `ebay_store_category_name`,
       `store_category2_id` AS `ebay_store_category2_id`,
       `store_category2_name` AS `ebay_store_category2_name`,
       `weight` AS `ebay_weight`,
       `dispatch_time_max` AS `ebay_dispatch_time_max`,
       `dimensions_depth` AS `ebay_dimensions_depth`,
       `dimensions_length` AS `ebay_dimensions_length`,
       `dimensions_weight` AS `ebay_dimensions_weight`
FROM (
         SELECT (SELECT 'simple') AS `type`,
                `m2e_e2m_ebay_items`.`id`,
                (SELECT NULL)     AS `item_variation_id`,
                (SELECT NULL)     AS `item_variation_hash`,
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
         WHERE `m2e_e2m_ebay_item_variations`.`id` IS NULL

         UNION

         SELECT (SELECT 'simple')                     AS `type`,
                `m2e_e2m_ebay_items`.`id`             AS `id`,
                `m2e_e2m_ebay_item_variations`.`id`   AS `item_variation_id`,
                `m2e_e2m_ebay_item_variations`.`hash` AS `item_variation_hash`,
                `site`,
                `ebay_item_id`,
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

         UNION

         SELECT (SELECT 'configurable') AS `type`,
                `m2e_e2m_ebay_items`.`id`,
                (SELECT NULL)           AS `item_variation_id`,
                (SELECT NULL)           AS `item_variation_hash`,
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
         WHERE `m2e_e2m_ebay_item_variations`.`id` IS NOT NULL
         GROUP BY `m2e_e2m_ebay_items`.`id`

     ) as `items`
ORDER BY `type` DESC, `ebay_sku`, `ebay_site` DESC;
SQL;

        $getImagesForItemSQL = <<<SQL
SELECT GROUP_CONCAT(DISTINCT `path` SEPARATOR ';') AS `images`
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

        $beforeSku = false;
        $variationData = array();

        $items = $readConn->query($getItemsSQL);
        while ($item = $items->fetch(PDO::FETCH_ASSOC)) {
            if (Mage::helper('e2m/Ebay_Config')->isMarketplaceSkip($item['ebay_site'])) {
                continue;
            }

            $item = Mage::helper('e2m/Ebay_Config')->applySettings($item);

            $parentAndChildIds = array($item['id'], $item['item_variation_id']);

            $product = $productSkeleton;
            $product['sku'] = $item[Mage::helper('e2m/Ebay_Config')->getProductIdentifier()];
            $product['_store'] = Mage::helper('e2m/Magento')->getStoreCodeById(
                Mage::helper('e2m/Ebay_Config')->getStoreIdByMarketplaceCode($item['ebay_site'])
            );

            $specifics = $readConn->query($getSpecificsForItemSQL, $parentAndChildIds);
            while ($specific = $specifics->fetch(PDO::FETCH_ASSOC)) {
                if (empty($specific['name'])) {
                    continue;
                }

                $item[$specific['name']] = $specific['value'];
                if ('variation' !== $specific['type']) {
                    continue;
                }

                $value = Mage::helper('e2m')->getValue($specific['value'], $defaultValue);
                $code = Mage::helper('e2m')->getCode($specific['name']);
                $variationData[$item['id']][$product['sku']][$code] = $value;
            }

            foreach ($exportSpecifics as $specificName => $magentoAttribute) {
                if (!isset($item[$specificName])) {
                    continue;
                }

                $product[$magentoAttribute] = Mage::helper('e2m')->getValue($item[$specificName], $defaultValue);
            }

            foreach ($exportAttributes as $magentoAttribute => $eBayField) {
                if (!isset($item[$eBayField])) {
                    continue;
                }

                if ('ebay_quantity' === $eBayField) {
                    $product['is_in_stock'] = (int)(0 < (int)$item[$eBayField]);
                }

                $product[$magentoAttribute] = Mage::helper('e2m')->getValue($item[$eBayField], $defaultValue);
            }

            $images = array();
            $media = $readConn->query($getImagesForItemSQL, $parentAndChildIds)->fetchColumn();
            if (!empty($media)) {

                foreach (explode(';', $media) as $img) {
                    $image = $productSkeleton;

                    $image['_store'] = $product['_store'];
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

                        $variation['_store'] = $product['_store'];
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

            $product['_attribute_set'] = $attributeSetName;
            $product['_type'] = $item['type'];
            $product['status'] = 1;
            $product['visibility'] = 1;
            $product['tax_class_id'] = 0;

            if ($beforeSku === $product['sku']) {
                foreach (array('_type', '_attribute_set', 'sku',
                             'weight', 'price', 'special_price',
                             'qty', 'is_in_stock') as $globalAttribute) {
                    if (!isset($product[$globalAttribute]) || $product[$globalAttribute] === $defaultValue) {
                        continue;
                    }

                    $product[$globalAttribute] = $defaultValue;
                }
            }
            $beforeSku = $product['sku'];

            $product = implode(',', $product);
            !empty($images) && $product .= PHP_EOL . implode(PHP_EOL, $images);
            !empty($variations) && $product .= PHP_EOL . implode(PHP_EOL, $variations);

            Mage::helper('e2m')->writeCSVFile($prefixPath, $product, $csvHeader, 'm1');
        }

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('data' => array(
            'completed' => true
        ))));
    }

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Exception
     */
    public function getAttributesSQLAction() {

        $file = Mage::helper('e2m')->getFullPath('ebay_attributes.sql');
        $transactionSQL = '';

        $readConn = Mage::getSingleton('core/resource')->getConnection('core_read');
        $attributeSetName = $readConn->quote(Mage::helper('e2m/Magento')->getAttributeSetNameById(
            Mage::helper('e2m/Config')->get(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET)
        ));

        if (!file_exists(Mage::helper('e2m')->getFullPath('ebay_attributes_export.csv'))) {
            $this->getAttributesExportCSVAction();
        }
        $attributesExport = Mage::helper('e2m/Ebay')->getExportAttributes();

        if (!file_exists(Mage::helper('e2m')->getFullPath('ebay_attributes_matching.csv'))) {
            $this->getAttributesMatchingCSVAction();
        }
        $attributesMatching = Mage::helper('e2m/Ebay')->getMatchingAttributes();

        //----------------------------------------

        foreach ($attributesExport as $ebay => &$magento) {
            if (!isset($attributesMatching[$ebay])) {
                continue;
            }

            if (isset($attributesMatching[$magento])) {
                continue;
            }

            $attributesMatching[$magento] = $attributesMatching[$ebay];
            unset($attributesMatching[$ebay]);
            unset($attributesExport[$ebay]);
        }

        //----------------------------------------

        $adminStore = Mage::helper('e2m/Ebay_Config')->getMarketplaceCodeUseAdminStore();
        foreach ($attributesMatching as $attributeCode => $attributeData) {
            if (empty($attributeCode)) {
                continue;
            }

            $code = $readConn->quote($attributeCode);
            $type = $attributeData['type'];
            $isSelect = (int)(M2E_E2M_Helper_Magento::TYPE_SELECT === $type);
            $sourceModel = $isSelect ? $readConn->quote('eav/entity_attribute_source_table') : 'NULL';
            $group = $isSelect ? 'main_group_id' : 'specific_group_id';

            $name = reset($attributeData['name']);
            if ($adminStore !== false && isset($attributeData['name'][$adminStore])) {
                $name = $attributeData['name'][$adminStore];
            }
            $name = $readConn->quote($name);

            $transactionSQL .= <<<SQL

    -- {$code}

    SET @attribute_code = {$code};
    SET @frontend_input = {$name};

    SET @attribute_id = (SELECT `attribute_id` FROM `eav_attribute` WHERE `attribute_code` = @attribute_code LIMIT 1);
    IF @attribute_id IS NULL THEN
        INSERT INTO `eav_attribute` (`entity_type_id`, `attribute_code`, `backend_type`, `frontend_input`, `frontend_label`, `source_model`, `is_required`, `is_user_defined`, `default_value`, `is_unique`)
        VALUES (@entity_type_id, @attribute_code, 'varchar', '{$type}', @frontend_input, {$sourceModel}, 0, 1, NULL, 0);
        SET @attribute_id = LAST_INSERT_ID();

        INSERT INTO `catalog_eav_attribute` (`attribute_id`, `is_global`, `is_visible`, `is_searchable`, `is_filterable`, `is_comparable`, `is_visible_on_front`, `is_html_allowed_on_front`, `is_used_for_price_rules`, `is_filterable_in_search`, `used_in_product_listing`, `used_for_sort_by`, `is_configurable`, `apply_to`, `is_visible_in_advanced_search`, `position`, `is_wysiwyg_enabled`, `is_used_for_promo_rules`)
        VALUES (@attribute_id, {$isSelect}, 1, 0, 0, 1, 0, 1, 0, 0, 0, 0, {$isSelect}, 'simple,configurable', 1, 0, 0, 0);
    END IF;

    IF NOT EXISTS(SELECT `attribute_id` FROM `eav_entity_attribute` WHERE `entity_type_id` = @entity_type_id AND `attribute_set_id` = @attribute_set_id AND `attribute_id` = @attribute_id) THEN
        INSERT INTO `eav_entity_attribute` (`entity_type_id`, `attribute_set_id`, `attribute_group_id`, `attribute_id`)
        VALUES (@entity_type_id, @attribute_set_id, @{$group}, @attribute_id);
    END IF;
SQL;

            foreach ($attributeData['name'] as $site => $value) {
                $storeId = Mage::helper('e2m/Ebay_Config')->getStoreIdByMarketplaceCode($site);
                if (M2E_E2M_Helper_Magento::STORE_SKIP === $storeId) {
                    continue;
                }

                $name = $readConn->quote($value);

                $transactionSQL .= <<<SQL


    IF NOT EXISTS(SELECT `attribute_id` FROM `eav_attribute_label` WHERE `attribute_id` = @attribute_id AND `store_id` = {$storeId}) THEN
        INSERT INTO `eav_attribute_label` (`attribute_id`, `store_id`, `value`) VALUES (@attribute_id, {$storeId}, {$name});
    END IF;
SQL;
            }

            if (!isset($attributeData['name'][$adminStore])) {
                $storeId = M2E_E2M_Helper_Magento::STORE_ADMIN;

                $transactionSQL .= <<<SQL


    IF NOT EXISTS(SELECT `attribute_id` FROM `eav_attribute_label` WHERE `attribute_id` = @attribute_id AND `store_id` = {$storeId}) THEN
        INSERT INTO `eav_attribute_label` (`attribute_id`, `store_id`, `value`) VALUES (@attribute_id, {$storeId}, @frontend_input);
    END IF;
SQL;
            }

            if (!$isSelect) {
                $transactionSQL .= "\n\n\t-- end {$code}\n";

                continue;
            }

            $transactionSQL .= <<<SQL


    IF EXISTS(SELECT `backend_type` FROM `eav_attribute` WHERE `attribute_id` = @attribute_id AND `backend_type` = '{$type}') THEN
SQL;

            foreach ($attributeData['value'] as $valueCode => $attributeValueData) {

                $value = false;
                if ($adminStore !== false && isset($attributeValueData[strtolower($adminStore)])) {
                    $value = $readConn->quote($attributeValueData[strtolower($adminStore)]);
                } else {
                    foreach ($attributeValueData as $site => $datum) {
                        $value = $readConn->quote($datum);
                        break;
                    }
                }

                $transactionSQL .= <<<SQL


        SET @option_id = (SELECT `eav_attribute_option`.`option_id` FROM `eav_attribute_option` LEFT JOIN `eav_attribute_option_value` ON `eav_attribute_option`.`option_id` = `eav_attribute_option_value`.`option_id` WHERE `attribute_id` = @attribute_id AND `value` = {$value} LIMIT 1);
        IF @option_id IS NULL THEN
            INSERT INTO `eav_attribute_option` (`attribute_id`) VALUES (@attribute_id);
            SET @option_id = LAST_INSERT_ID();
        END IF;
SQL;
                $addingAdminValue = false;
                $adminValue = false;
                foreach ($attributeValueData as $site => $value) {
                    $storeId = Mage::helper('e2m/Ebay_Config')->getStoreIdByMarketplaceCode($site);
                    if (M2E_E2M_Helper_Magento::STORE_SKIP === $storeId) {
                        continue;
                    }

                    $adminValue = $value = $readConn->quote($value);
                    if (M2E_E2M_Helper_Magento::STORE_ADMIN === $storeId) {
                        $addingAdminValue = true;
                    }

                    $transactionSQL .= <<<SQL


        IF NOT EXISTS(SELECT * FROM `eav_attribute_option_value` WHERE `option_id` = @option_id AND `store_id` = {$storeId}) THEN
            INSERT INTO `eav_attribute_option_value` (`option_id`, `store_id`, `value`) VALUES (@option_id, {$storeId}, {$value});
        END IF;
SQL;
                }

                if (!$addingAdminValue) {
                    $transactionSQL .= <<<SQL


        IF NOT EXISTS(SELECT * FROM `eav_attribute_option_value` WHERE `option_id` = @option_id AND `store_id` = 0) THEN
            INSERT INTO `eav_attribute_option_value` (`option_id`, `store_id`, `value`) VALUES (@option_id, 0, {$adminValue});
        END IF;
SQL;
                }
            }

            $transactionSQL .= <<<SQL


    END IF;
SQL;
            $transactionSQL .= "\n\n\t-- end {$code}\n";
        }

        $stores = array_unique(array_merge(
            array(M2E_E2M_Helper_Magento::STORE_ADMIN),
            array_values(Mage::helper('e2m/Config')->get(
                M2E_E2M_Helper_Ebay_Config::XML_PATH_STORE_MAP, array()
            ))
        ));

        unset($attributesExport['ebay_sku'], $attributesExport['ebay_quantity']);
        foreach ($attributesExport as $ebay => $magento) {
            if (!empty($magento)) {
                continue;
            }

            $title = str_replace('_', ' ', $ebay);
            $title = str_replace('Ebay', 'eBay', ucwords($title));
            $name = $readConn->quote($title);
            $code = $readConn->quote($ebay);

            $transactionSQL .= <<<SQL

    -- {$code}

    SET @attribute_code = {$code};
    SET @frontend_input = {$name};

    SET @attribute_id = (SELECT `attribute_id` FROM `eav_attribute` WHERE `attribute_code` = @attribute_code LIMIT 1);
    IF @attribute_id IS NULL THEN
        INSERT INTO `eav_attribute` (`entity_type_id`, `attribute_code`, `backend_type`, `frontend_input`, `frontend_label`, `source_model`, `is_required`, `is_user_defined`, `default_value`, `is_unique`)
        VALUES (@entity_type_id, @attribute_code, 'varchar', 'text', @frontend_input, NULL, 0, 1, NULL, 0);
        SET @attribute_id = LAST_INSERT_ID();

        INSERT INTO `catalog_eav_attribute` (`attribute_id`, `is_global`, `is_visible`, `is_searchable`, `is_filterable`, `is_comparable`, `is_visible_on_front`, `is_html_allowed_on_front`, `is_used_for_price_rules`, `is_filterable_in_search`, `used_in_product_listing`, `used_for_sort_by`, `is_configurable`, `apply_to`, `is_visible_in_advanced_search`, `position`, `is_wysiwyg_enabled`, `is_used_for_promo_rules`)
        VALUES (@attribute_id, 1, 1, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 'simple,configurable', 1, 0, 0, 0);
    END IF;

    IF NOT EXISTS(SELECT `attribute_id` FROM `eav_entity_attribute` WHERE `entity_type_id` = @entity_type_id AND `attribute_set_id` = @attribute_set_id AND `attribute_id` = @attribute_id) THEN
        INSERT INTO `eav_entity_attribute` (`entity_type_id`, `attribute_set_id`, `attribute_group_id`, `attribute_id`)
        VALUES (@entity_type_id, @attribute_set_id, @main_group_id, @attribute_id);
    END IF;
SQL;

            foreach ($stores as $storeId) {
                if (M2E_E2M_Helper_Magento::STORE_SKIP === $storeId) {
                    continue;
                }

                $transactionSQL .= <<<SQL


    IF NOT EXISTS(SELECT `attribute_id` FROM `eav_attribute_label` WHERE `attribute_id` = @attribute_id AND `store_id` = {$storeId}) THEN
        INSERT INTO `eav_attribute_label` (`attribute_id`, `store_id`, `value`) VALUES (@attribute_id, {$storeId}, {$name});
    END IF;
SQL;
            }

            $transactionSQL .= "\n\n\t-- end {$code}\n";
        }

        $sql = <<<SQL
DROP PROCEDURE IF EXISTS createM1Attributes;
DELIMITER ;;
CREATE PROCEDURE createM1Attributes()
BEGIN
    DECLARE error_code INT;
    DECLARE error_message TEXT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN
        GET DIAGNOSTICS CONDITION 1 error_code = MYSQL_ERRNO, error_message = MESSAGE_TEXT;
        SELECT error_code as `Code`, error_message as `Message`;

        ROLLBACK;
    END;

    DECLARE EXIT HANDLER FOR SQLWARNING BEGIN
        GET DIAGNOSTICS CONDITION 1 error_code = MYSQL_ERRNO, error_message = MESSAGE_TEXT;
        SELECT error_code as `Code`, error_message as `Message`;

        ROLLBACK;
    END;

    SET @entity_type_id = (SELECT `entity_type_id` FROM `eav_entity_type` WHERE `entity_type_code` = 'catalog_product' LIMIT 1);
    SET @attribute_set_id = (SELECT `attribute_set_id` FROM `eav_attribute_set` WHERE `attribute_set_name` = {$attributeSetName} AND `entity_type_id` = @entity_type_id LIMIT 1);

    SET @main_group_id = (SELECT `attribute_group_id` FROM `eav_attribute_group` WHERE `attribute_group_name` = 'eBay' AND `attribute_set_id` = @attribute_set_id LIMIT 1);
    IF @main_group_id IS NULL THEN
        INSERT INTO `eav_attribute_group` (`attribute_set_id`, `attribute_group_name`, `sort_order`, `default_id`)
        VALUES (@attribute_set_id, 'eBay', 20, 0);
        SET @main_group_id = LAST_INSERT_ID();
    END IF;

    SET @specific_group_id = (SELECT `attribute_group_id` FROM `eav_attribute_group` WHERE `attribute_group_name` = 'eBay Specifics' AND `attribute_set_id` = @attribute_set_id LIMIT 1);
    IF @specific_group_id IS NULL THEN
        INSERT INTO `eav_attribute_group` (`attribute_set_id`, `attribute_group_name`, `sort_order`, `default_id`)
        VALUES (@attribute_set_id, 'eBay Specifics', 30, 0);
        SET @specific_group_id = LAST_INSERT_ID();
    END IF;

    START TRANSACTION;
    {$transactionSQL}
    COMMIT;
END;
;;
DELIMITER ;
CALL createM1Attributes();
SQL;

        file_put_contents($file, $sql, LOCK_EX);

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('data' => array(
            'completed' => true
        ))));
    }

    //########################################

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Exception
     */
    public function getAttributesMatchingCSVAction() {

        $file = Mage::helper('e2m')->getFullPath('ebay_attributes_matching.csv');
        $defaultValue = '';
        $attributes = array();

        $sql = <<<SQL
SELECT `name`, `value`, `site`
FROM `m2e_e2m_ebay_item_variation_specifics`
         LEFT JOIN `m2e_e2m_ebay_item_variations`
             ON `m2e_e2m_ebay_item_variation_specifics`.`item_variation_id` = `m2e_e2m_ebay_item_variations`.`id`
         LEFT JOIN `m2e_e2m_ebay_items`
             ON `m2e_e2m_ebay_item_variations`.`item_id` = `m2e_e2m_ebay_items`.`id`
ORDER BY `name`
SQL;
        $specifics = Mage::getSingleton('core/resource')->getConnection('core_read')->query($sql);
        while ($specific = $specifics->fetch(PDO::FETCH_ASSOC)) {
            if (Mage::helper('e2m/Ebay_Config')->isMarketplaceSkip($specific['site'])) {
                continue;
            }

            $code = Mage::helper('e2m')->getCode($specific['name']);

            $attributes[$code]['type'] = M2E_E2M_Helper_Magento::TYPE_SELECT;
            $attributes[$code]['name'][$specific['site']] = $specific['name'];
            $attributes[$code]['values'][$specific['site']][] = $specific['value'];
        }

        $sql = <<<SQL
SELECT `name`, `value`, `site`
FROM `m2e_e2m_ebay_item_specifics`
         LEFT JOIN `m2e_e2m_ebay_items`
             ON `m2e_e2m_ebay_item_specifics`.`item_id` = `m2e_e2m_ebay_items`.`id`
ORDER BY `name`
SQL;
        $specifics = Mage::getSingleton('core/resource')->getConnection('core_read')->query($sql);
        while ($specific = $specifics->fetch(PDO::FETCH_ASSOC)) {
            if (Mage::helper('e2m/Ebay_Config')->isMarketplaceSkip($specific['site'])) {
                continue;
            }

            $code = Mage::helper('e2m')->getCode($specific['name']);
            if (isset($attributes[$code]) && $attributes[$code]['type'] === M2E_E2M_Helper_Magento::TYPE_SELECT) {
                $attributes[$code]['name'][$specific['site']] = $specific['name'];
                $attributes[$code]['values'][$specific['site']][] = $specific['value'];

                continue;
            }

            $attributes[$code]['type'] = M2E_E2M_Helper_Magento::TYPE_TEXT;
            $attributes[$code]['name'][$specific['site']] = $specific['name'];
        }

        file_put_contents($file, 'type,site,name_code,name,value_code,value' . PHP_EOL, LOCK_EX);
        $fp = fopen($file, 'a');
        foreach ($attributes as $code => $data) {
            if (M2E_E2M_Helper_Magento::TYPE_TEXT === $data['type']) {
                foreach ($attributes[$code]['name'] as $site => $name) {
                    fputcsv($fp, array(
                        /** type       => */ $data['type'],
                        /** site       => */ strtolower($site),
                        /** name_code  => */ $code,
                        /** name       => */ $name,
                        /** value_code => */ $defaultValue,
                        /** value      => */ $defaultValue
                    ));
                }

                continue;
            }

            foreach ($attributes[$code]['values'] as $site => $values) {
                foreach (array_unique($values) as $value) {
                    fputcsv($fp, array(
                        /** type       => */ $data['type'],
                        /** site       => */ strtolower($site),
                        /** name_code  => */ $code,
                        /** name       => */ $attributes[$code]['name'][$site],
                        /** value_code => */ Mage::helper('e2m')->getCode($value),
                        /** value      => */ $value
                    ));
                }
            }
        }
        fclose($fp);

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('data' => array(
            'completed' => true
        ))));
    }

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Exception
     */
    public function getAttributesExportCSVAction() {

        $file = Mage::helper('e2m')->getFullPath('ebay_attributes_export.csv');
        $defaultValue = '';
        $attributes = array(
            'ebay_item_id',
            'ebay_item_variation_hash',
            'ebay_site',
            'ebay_sku',
            'ebay_upc',
            'ebay_ean',
            'ebay_isbn',
            'ebay_epid',
            'ebay_mpn',
            'ebay_brand',
            'ebay_title',
            'ebay_subtitle',
            'ebay_description',
            'ebay_currency',
            'ebay_start_price',
            'ebay_current_price',
            'ebay_buy_it_now',
            'ebay_quantity',
            'ebay_condition_id',
            'ebay_condition_name',
            'ebay_condition_description',
            'ebay_primary_category_id',
            'ebay_primary_category_name',
            'ebay_secondary_category_id',
            'ebay_secondary_category_name',
            'ebay_store_category_id',
            'ebay_store_category_name',
            'ebay_store_category2_id',
            'ebay_store_category2_name',
            'ebay_weight',
            'ebay_dispatch_time_max',
            'ebay_dimensions_depth',
            'ebay_dimensions_length',
            'ebay_dimensions_weight'
        );

        file_put_contents($file, 'ebay_property_code,magento_attribute_code' . PHP_EOL, LOCK_EX);
        $fp = fopen($file, 'a');
        foreach ($attributes as $ebayPropertyCode) {
            fputcsv($fp, array(
                /** ebay_property_code     => */ $ebayPropertyCode,
                /** magento_attribute_code => */ $defaultValue
            ));
        }
        fclose($fp);

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('data' => array(
            'completed' => true
        ))));
    }

    //########################################

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Exception
     */
    public function startEbayDownloadInventoryAction() {

        $toDateTime = new DateTime('now', new DateTimeZone('UTC'));

        $fromDatetime = clone $toDateTime;
        $fromDatetime->setTimestamp(M2E_E2M_Model_Cron_Job_Ebay_DownloadInventory::MAX_DOWNLOAD_TIME);

        Mage::helper('e2m/Config')->set(
            M2E_E2M_Model_Cron_Job_Ebay_DownloadInventory::XML_PATH_PROCESS_DOWNLOAD_INVENTORY,
            0
        );
        Mage::helper('e2m/Config')->set(
            M2E_E2M_Model_Cron_Job_Ebay_DownloadInventory::XML_PATH_FROM_DOWNLOAD_INVENTORY,
            $fromDatetime->getTimestamp()
        );
        Mage::helper('e2m/Config')->set(
            M2E_E2M_Model_Cron_Job_Ebay_DownloadInventory::XML_PATH_TO_DOWNLOAD_INVENTORY,
            $toDateTime->getTimestamp()
        );

        Mage::helper('e2m/Config')->set(
            M2E_E2M_Model_Cron_Job_Ebay_DownloadInventory::XML_PATH_WORK_DOWNLOAD_INVENTORY,
            true,
            true
        );

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('data' => array(
            'simple' => 0,
            'variation' => 0,
            'total' => 0,
            'process' => 0
        ))));
    }

    //########################################

    /**
     * @return Zend_Controller_Response_Abstract
     */
    public function setSettingsAction() {

        $settings = Mage::helper('core')->jsonDecode(
            $this->getRequest()->getParam('settings', array())
        );

        isset($settings['attribute-set']) && Mage::helper('e2m/Config')->set(
            M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET,
            $settings['attribute-set'], true
        );

        isset($settings['marketplace-store']) && Mage::helper('e2m/Config')->set(
            M2E_E2M_Helper_Ebay_Config::XML_PATH_STORE_MAP,
            $settings['marketplace-store'], true
        );

        isset($settings['generate-sku']) && Mage::helper('e2m/Config')->set(
            M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_SKU_GENERATE,
            $settings['generate-sku'], true
        );

        isset($settings['product-identifier']) && Mage::helper('e2m/Config')->set(
            M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_SKU,
            $settings['product-identifier'], true
        );

        isset($settings['delete-html']) && Mage::helper('e2m/Config')->set(
            M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_HTML_DELETE,
            $settings['delete-html'], true
        );

        $this->_getSession()->addSuccess(Mage::helper('e2m')->__('Save settings'));

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('data' => array(
            'settings' => $settings
        ))));
    }

    //########################################

    /**
     * @return Zend_Controller_Response_Abstract
     */
    public function unlinkEbayAccountAction() {

        Mage::helper('e2m/Config')->set(M2E_E2M_Helper_Ebay_Config::XML_PATH_STORE_MAP, array());
        Mage::helper('e2m/Config')->set(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET, false);
        Mage::helper('e2m/Config')->set(
            M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_SKU,
            M2E_E2M_Helper_Ebay_Config::PRODUCT_IDENTIFIER_SKU
        );
        Mage::helper('e2m/Config')->set(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_SKU_GENERATE, false);
        Mage::helper('e2m/Config')->set(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_HTML_DELETE, false);

        Mage::helper('e2m/Config')->set(M2E_E2M_Helper_Ebay::XML_PATH_AVAILABLE_MARKETPLACES, array());
        Mage::helper('e2m/Config')->set(M2E_E2M_Helper_Ebay::XML_PATH_INVENTORY_VARIATION_COUNT, 0);
        Mage::helper('e2m/Config')->set(M2E_E2M_Helper_Ebay::XML_PATH_INVENTORY_SIMPLE_COUNT, 0);
        Mage::helper('e2m/Config')->set(M2E_E2M_Helper_Ebay::XML_PATH_INVENTORY_TOTAL_COUNT, 0);

        Mage::helper('e2m/Config')->set(M2E_E2M_Model_Proxy_Ebay_Account::XML_PATH_EBAY_ACCOUNT_ID, 0, true);

        $sql = <<<SQL
TRUNCATE `m2e_e2m_ebay_item_variation_images`;
TRUNCATE `m2e_e2m_ebay_item_variation_specifics`;
TRUNCATE `m2e_e2m_ebay_item_variations`;
TRUNCATE `m2e_e2m_ebay_item_images`;
TRUNCATE `m2e_e2m_ebay_item_specifics`;
TRUNCATE `m2e_e2m_ebay_items`;
SQL;
        Mage::getSingleton('core/resource')->getConnection('core_read')->multiQuery($sql)->execute();

        $this->_getSession()->addSuccess(Mage::helper('e2m')->__('Account unlink.'));

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('data' => array(
            'redirect' => true
        ))));
    }

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Exception
     */
    public function linkEbayAccountAction() {

        $accountId = $this->getRequest()->getParam('account_id');
        if (empty($accountId)) {
            throw new Exception('Account invalid.');
        }

        Mage::helper('e2m/Config')->set(
            M2E_E2M_Model_Proxy_Ebay_Account::XML_PATH_EBAY_ACCOUNT_ID,
            $accountId,
            true
        );

        $this->_getSession()->addSuccess(Mage::helper('e2m')->__('Account link.'));

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array('data' => array(
            'redirect' => true
        ))));
    }

    //########################################

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
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

            Mage::helper('e2m')->writeExceptionLog(new Exception(
                "Error: {$error['message']}\nFile: {$error['file']}\nLine: {$error['line']}"
            ));
        });

        try {

            parent::dispatch($action);

        } catch (Exception $e) {

            Mage::helper('e2m')->writeExceptionLog($e);

            if (!$this->getRequest()->isAjax()) {

                $this->_getSession()->addError($e->getMessage());

                $this->_redirect('*/e2m/index');

                return;
            }

            $this->getResponse()->setHttpResponseCode(500)->setBody(Mage::helper('core')->jsonEncode(array(
                'message' => $e->getMessage(),
                'error' => true
            )));
        }
    }

    //########################################

    public function indexAction() {

        $this->loadLayout();

        $this->_setActiveMenu('e2m');

        $this->getLayout()->getBlock('head')->setTitle(Mage::helper('e2m')->__('eBay Data Import / eM2Pro'));
        $this->getLayout()->getBlock('head')->addCss('e2m/css/main.css');
        $this->getLayout()->getBlock('head')->addJs('e2m/magento.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/ebay.js');
        $this->getLayout()->getBlock('head')->addJs('e2m/main.js');

        $this->_addContent($this->getLayout()->createBlock('e2m/adminhtml_main'));

        return $this->renderLayout();
    }
}
