<?php

class M2E_E2M_Model_Ebay_Inventory {

    public function updateItem(Mage_Core_Model_Resource $resource, array $item, $id) {

        $bind = array();

        $bind['site'] = M2E_E2M_Helper_Ebay::$MARKETPLACE_CODE[$item['marketplace']];
        $bind['sku'] = $item['identifiers']['sku'];
        $bind['upc'] = $item['identifiers']['upc'];
        $bind['ean'] = $item['identifiers']['ean'];
        $bind['isbn'] = $item['identifiers']['isbn'];
        $bind['ePID'] = $item['identifiers']['epid'];
        $bind['mpn'] = $item['identifiers']['brand_mpn']['mpn'];
        $bind['brand'] = $item['identifiers']['brand_mpn']['brand'];
        $bind['title'] = $item['description']['title'];
        $bind['subtitle'] = $item['description']['subtitle'];
        $bind['description'] = $item['description']['description'];
        $bind['currency'] = $item['price']['currency'];
        $bind['start_price'] = $item['price']['start'];
        $bind['current_price'] = $item['price']['current'];
        $bind['buy_it_now'] = $item['price']['buy_it_now'];
        $bind['quantity'] = $item['qty']['total'];
        $bind['condition_id'] = $item['condition']['type'];
        $bind['condition_name'] = $item['condition']['name'];
        $bind['condition_description'] = $item['condition']['description'];
        $bind['primary_category_id'] = $item['categories']['primary']['id'];
        $bind['primary_category_name'] = $item['categories']['primary']['name'];
        $bind['secondary_category_id'] = $item['categories']['secondary']['id'];
        $bind['secondary_category_name'] = $item['categories']['secondary']['name'];
        $bind['store_category_id'] = $item['store']['categories']['primary']['id'];
        $bind['store_category_name'] = $item['store']['categories']['primary']['name'];
        $bind['store_category2_id'] = $item['store']['categories']['secondary']['id'];
        $bind['store_category2_name'] = $item['store']['categories']['secondary']['name'];
        $bind['weight'] = $item['shipping']['package']['weight']['major'];
        $bind['weight'] .= '.' . $item['shipping']['package']['weight']['minor'];
        $bind['dispatch_time_max'] = $item['shipping']['dispatch_time'];
        $bind['dimensions_depth'] = $item['shipping']['package']['dimensions']['depth'];
        $bind['dimensions_length'] = $item['shipping']['package']['dimensions']['length'];
        $bind['dimensions_weight'] = $item['shipping']['package']['dimensions']['width'];

        $resource->getConnection('core_write')->update(
            $resource->getTableName('m2e_e2m_ebay_items'),
            $bind,
            array('id = ?' => $id)
        );
    }

    public function updateItemSpecifics(Mage_Core_Model_Resource $resource, array $item, $id) {

        if (empty($item['item_specifics'])) {
            return;
        }

        $itemSpecificsTableName = $resource->getTableName('m2e_e2m_ebay_item_specifics');
        $connRead = $resource->getConnection('core_read');
        $connWrite = $resource->getConnection('core_write');

        $insertSpecifics = array();
        foreach ($item['item_specifics'] as $specific) {

            $itemSpecificId = $connRead->select()->from($itemSpecificsTableName, 'id')
                ->where('item_id = ?', $id)
                ->where('name = ?', $specific['name'])
                ->limit(1)->query()->fetchColumn();

            if ($itemSpecificId) {
                $connWrite->update(
                    $itemSpecificsTableName,
                    array('value' => $specific['value']),
                    array('id = ?' => $itemSpecificId)
                );

                continue;
            }

            $insertSpecifics[] = array(
                'item_id' => $id,
                'name' => $specific['name'],
                'value' => $specific['value']
            );
        }

        if (!empty($insertSpecifics)) {
            $connWrite->insertMultiple($itemSpecificsTableName, $insertSpecifics);
        }
    }

    public function updateItemImages(Mage_Core_Model_Resource $resource, array $item, $id) {

        if (empty($item['images']['urls'])) {
            return;
        }

        $importPath = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA) . DS . 'import';
        $itemImagesTableName = $resource->getTableName('m2e_e2m_ebay_item_images');
        $connRead = $resource->getConnection('core_read');
        $connWrite = $resource->getConnection('core_write');

        $insertImages = array();
        foreach ($item['images']['urls'] as $url) {

            //------------------------------------------
            $ext = strtolower(substr($url, (strripos($url, '.'))));
            !in_array($ext, array('.png', '.jpg', '.jpeg')) && $ext = '.jpg';
            $fileName = md5($url) . $ext;
            //------------------------------------------

            if (!is_file($importPath . DS . $fileName)) {
                try {
                    file_put_contents($importPath . DS . $fileName, file_get_contents($url));
                } catch (Exception $e) {
                    continue;
                }
            }

            $itemImageId = $connRead->select()->from($itemImagesTableName, 'id')
                ->where('item_id = ?', $id)
                ->where('path = ?', $fileName)
                ->limit(1)->query()->fetchColumn();

            if ($itemImageId) {
                continue;
            }

            $insertImages[] = array(
                'item_id' => $id,
                'path' => $fileName
            );
        }

        if (!empty($insertImages)) {
            $connWrite->insertMultiple($itemImagesTableName, $insertImages);
        }
    }

    public function updateItemVariations(Mage_Core_Model_Resource $resource, array $item, $id) {

        if (empty($item['variations'])) {
            return;
        }

        $itemVariationsTableName = $resource->getTableName('m2e_e2m_ebay_item_variations');
        $connRead = $resource->getConnection('core_read');
        $connWrite = $resource->getConnection('core_write');

        $insertVariations = array();
        foreach ($item['variations'] as $variation) {

            $bind = array();

            $bind['hash'] = md5(implode('|', $variation['specifics'])) . '-' . $item['identifiers']['item_id'];
            $bind['item_id'] = $id;
            $bind['sku'] = $variation['sku'];
            $bind['start_price'] = $variation['price'];
            $bind['quantity'] = $variation['quantity'];
            $bind['ean'] = $variation['details']['ean'];
            $bind['upc'] = $variation['details']['upc'];
            $bind['isbn'] = $variation['details']['isbn'];
            $bind['ePID'] = $variation['details']['epid'];

            $itemVariationId = $connRead->select()->from($itemVariationsTableName, 'id')
                ->where('hash = ?', $bind['hash'])
                ->limit(1)->query()->fetchColumn();

            if ($itemVariationId) {
                $connWrite->update(
                    $itemVariationsTableName,
                    $bind,
                    array('id = ?' => $itemVariationId)
                );

                continue;
            }

            $insertVariations[] = $bind;
        }

        if (!empty($insertVariations)) {
            $connWrite->insertMultiple($itemVariationsTableName, $insertVariations);
        }
    }

    public function updateItemVariationSpecifics(Mage_Core_Model_Resource $resource, array $item) {

        if (empty($item['variations'])) {
            return;
        }

        $itemVariationSpecificsTableName = $resource->getTableName('m2e_e2m_ebay_item_variation_specifics');
        $connRead = $resource->getConnection('core_read');
        $connWrite = $resource->getConnection('core_write');

        $insertSpecifics = array();
        foreach ($item['variations'] as $variation) {
            $hash = md5(implode('|', $variation['specifics'])) . '-' . $item['identifiers']['item_id'];
            $itemVariationId = $connRead->select()->from($resource->getTableName('m2e_e2m_ebay_item_variations'), 'id')
                ->where('hash = ?', $hash)
                ->limit(1)->query()->fetchColumn();

            if (!$itemVariationId) {
                continue;
            }

            foreach ($variation['specifics'] as $name => $value) {

                $itemSpecificId = $connRead->select()->from($itemVariationSpecificsTableName, 'id')
                    ->where('item_variation_id = ?', $itemVariationId)
                    ->where('name = ?', $name)
                    ->limit(1)->query()->fetchColumn();

                if (!$itemSpecificId) {
                    $insertSpecifics[] = array(
                        'item_variation_id' => $itemVariationId,
                        'name' => $name,
                        'value' => $value
                    );

                    continue;
                }

                $connWrite->update(
                    $itemVariationSpecificsTableName,
                    array('value' => $value),
                    array('id = ?' => $itemSpecificId)
                );
            }
        }

        if (!empty($insertSpecifics)) {
            $connWrite->insertMultiple($itemVariationSpecificsTableName, $insertSpecifics);
        }
    }

    public function updateItemVariationImages(Mage_Core_Model_Resource $resource, array $item) {

        if (empty($item['variations'])) {
            return;
        }

        $importPath = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA) . DS . 'import';
        $itemVariationTableName = $resource->getTableName('m2e_e2m_ebay_item_variations');
        $itemImagesTableName = $resource->getTableName('m2e_e2m_ebay_item_variation_images');
        $connRead = $resource->getConnection('core_read');
        $connWrite = $resource->getConnection('core_write');
        $insertImages = array();

        foreach ($item['variations'] as $variation) {
            if (empty($variation['images'])) {
                continue;
            }

            $hash = md5(implode('|', $variation['specifics'])) . '-' . $item['identifiers']['item_id'];
            $itemVariationId = $connRead->select()->from($itemVariationTableName, 'id')
                ->where('hash = ?', $hash)
                ->limit(1)->query()->fetchColumn();

            if (!$itemVariationId) {
                continue;
            }

            foreach ($variation['images'] as $url) {

                //------------------------------------------
                $ext = strtolower(substr($url, (strripos($url, '.'))));
                !in_array($ext, array('.png', '.jpg', '.jpeg')) && $ext = '.jpg';
                $fileName = md5($url) . $ext;
                //------------------------------------------

                if (!is_file($importPath . DS . $fileName)) {
                    try {
                        file_put_contents($importPath . DS . $fileName, file_get_contents($url));
                    } catch (Exception $e) {
                        continue;
                    }
                }

                $itemImageId = $connRead->select()->from($itemImagesTableName, 'id')
                    ->where('item_variation_id = ?', $itemVariationId)
                    ->where('path = ?', $fileName)
                    ->limit(1)->query()->fetchColumn();

                if ($itemImageId) {
                    continue;
                }

                $insertImages[] = array(
                    'item_variation_id' => $itemVariationId,
                    'path' => $fileName
                );
            }
        }

        if (!empty($insertImages)) {
            $connWrite->insertMultiple($itemImagesTableName, $insertImages);
        }
    }

    public function updateItems(Mage_Core_Model_Resource $resource, array $items) {

        $eBayItemsTableName = $resource->getTableName('m2e_e2m_ebay_items');
        $connRead = $resource->getConnection('core_read');

        $rows = $connRead->select()->from($eBayItemsTableName, array('id', 'ebay_item_id'))
            ->where('ebay_item_id IN (?)', array_keys($items))->query()->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {

            $item = $items[$row['ebay_item_id']];

            $this->updateItem($resource, $item, $row['id']);
            $this->updateItemSpecifics($resource, $item, $row['id']);
            $this->updateItemImages($resource, $item, $row['id']);
            $this->updateItemVariations($resource, $item, $row['id']);
            $this->updateItemVariationSpecifics($resource, $item);
            $this->updateItemVariationImages($resource, $item);

            unset($items[$row['ebay_item_id']]);
        }

        return $items;
    }

    //########################################

    public function createItem(Mage_Core_Model_Resource $resource, array $item) {

        $bind = array();

        $bind['site'] = M2E_E2M_Helper_Ebay::$MARKETPLACE_CODE[$item['marketplace']];
        $bind['ebay_item_id'] = $item['identifiers']['item_id'];
        $bind['sku'] = $item['identifiers']['sku'];
        $bind['upc'] = $item['identifiers']['upc'];
        $bind['ean'] = $item['identifiers']['ean'];
        $bind['isbn'] = $item['identifiers']['isbn'];
        $bind['ePID'] = $item['identifiers']['epid'];
        $bind['mpn'] = $item['identifiers']['brand_mpn']['mpn'];
        $bind['brand'] = $item['identifiers']['brand_mpn']['brand'];
        $bind['title'] = $item['description']['title'];
        $bind['subtitle'] = $item['description']['subtitle'];
        $bind['description'] = $item['description']['description'];
        $bind['currency'] = $item['price']['currency'];
        $bind['start_price'] = $item['price']['start'];
        $bind['current_price'] = $item['price']['current'];
        $bind['buy_it_now'] = $item['price']['buy_it_now'];
        $bind['quantity'] = $item['qty']['total'];
        $bind['condition_id'] = $item['condition']['type'];
        $bind['condition_name'] = $item['condition']['name'];
        $bind['condition_description'] = $item['condition']['description'];
        $bind['primary_category_id'] = $item['categories']['primary']['id'];
        $bind['primary_category_name'] = $item['categories']['primary']['name'];
        $bind['secondary_category_id'] = $item['categories']['secondary']['id'];
        $bind['secondary_category_name'] = $item['categories']['secondary']['name'];
        $bind['store_category_id'] = $item['store']['categories']['primary']['id'];
        $bind['store_category_name'] = $item['store']['categories']['primary']['name'];
        $bind['store_category2_id'] = $item['store']['categories']['secondary']['id'];
        $bind['store_category2_name'] = $item['store']['categories']['secondary']['name'];
        $bind['weight'] = $item['shipping']['package']['weight']['major'];
        $bind['weight'] .= '.' . $item['shipping']['package']['weight']['minor'];
        $bind['dispatch_time_max'] = $item['shipping']['dispatch_time'];
        $bind['dimensions_depth'] = $item['shipping']['package']['dimensions']['depth'];
        $bind['dimensions_length'] = $item['shipping']['package']['dimensions']['length'];
        $bind['dimensions_weight'] = $item['shipping']['package']['dimensions']['width'];

        $resource->getConnection('core_write')->insert($resource->getTableName('m2e_e2m_ebay_items'), $bind);
        return $resource->getConnection('core_read')->select()
            ->from($resource->getTableName('m2e_e2m_ebay_items'), array('id'))
            ->where('ebay_item_id = ?', $item['identifiers']['item_id'])->query()->fetchColumn();
    }

    public function createItems(Mage_Core_Model_Resource $resource, array $items) {
        foreach ($items as $item) {

            $id = $this->createItem($resource, $item);
            $this->updateItemSpecifics($resource, $item, $id);
            $this->updateItemImages($resource, $item, $id);
            $this->updateItemVariations($resource, $item, $id);
            $this->updateItemVariationSpecifics($resource, $item);
            $this->updateItemVariationImages($resource, $item);
        }
    }

    //########################################

    /**
     * @param array $items
     */
    public function process(array $items) {
        $items = $this->updateItems(Mage::getSingleton('core/resource'), $items);
        $this->createItems(Mage::getSingleton('core/resource'), $items);
    }
}
