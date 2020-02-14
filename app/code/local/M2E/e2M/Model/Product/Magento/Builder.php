<?php

class M2E_e2M_Model_Product_Magento_Builder extends Mage_Core_Model_Abstract {

    const TYPE_SIMPLE = 'simple';
    const TYPE_CONFIGURABLE = 'configurable';

    //########################################

    /** @var M2E_e2M_Helper_eBay_Config $eBayConfig */
    private $eBayConfig;

    /** @var Mage_Catalog_Model_Product $product */
    private $product;

    /** @var Mage_Core_Helper_Data $coreHelper */
    private $coreHelper;

    /** @var int $groupId */
    private $groupId;

    //########################################

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $data
     * @param int $marketplaceId
     *
     * @return Mage_Catalog_Model_Product
     */
    private function loadProduct($product, $data, $marketplaceId) {
        switch (true) {
            case $this->eBayConfig->isProductIdentifierSKU():
                $product->setData('store_id', $this->eBayConfig->getStoreForMarketplace($marketplaceId));
                $product->load($product->getIdBySku($data['sku']));
                break;
        }

        return $product;
    }

    //########################################

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $images
     *
     * @return Mage_Catalog_Model_Product
     */
    private function importImage($product, $images) {

        if (empty($images)) {
            return $product;
        }

        $tempMediaPath = Mage::getSingleton('catalog/product_media_config')->getBaseTmpMediaPath();
        $files = array();
        foreach ($images['urls'] as $url) {
            $ext = strtolower(substr($url, (strripos($url, '.'))));
            !in_array($ext, array('.png', '.jpg', '.jpeg')) && $ext = '.jpg';
            $fileName = md5($url) . $ext;
            file_put_contents($tempMediaPath . DS . $fileName, file_get_contents($url));

            $files[] = $fileName;
        }

        $gallery = array();

        $imagePosition = 1;
        foreach ($files as $file) {
            if (!is_file($tempMediaPath . DS . $file)) {
                continue;
            }

            $gallery[] = array(
                'file' => $file,
                'label' => '',
                'position' => $imagePosition++,
                'disabled' => 0,
                'removed' => 0
            );
        }

        if (empty($gallery)) {
            return $product;
        }

        $firstImage = reset($gallery);
        $firstImage = $firstImage['file'];

        $product->setData('image', $firstImage);
        $product->setData('thumbnail', $firstImage);
        $product->setData('small_image', $firstImage);
        $product->setData('media_gallery', array(
            'images' => $this->coreHelper->jsonEncode($gallery),
            'values' => $this->coreHelper->jsonEncode(array(
                'main' => $firstImage,
                'image' => $firstImage,
                'small_image' => $firstImage,
                'thumbnail' => $firstImage
            ))
        ));

        return $product;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $data
     *
     * @return Mage_Catalog_Model_Product
     * @throws Exception
     */
    public function importQty($product, $data) {

        $qty = (int)$data['total'];

        /** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
        $stockItem = Mage::getModel('cataloginventory/stock_item');

        /**
         * Multi Stock is not supported by core Magento functionality.
         * app/code/core/Mage/CatalogInventory/Model/Stock/Item.php::getStockId()
         * But by changing this method the M2e Pro can be made compatible with a custom solution
         */
        $stockItem->assignProduct($product);
        $stockItem->addData(array(
            'qty' => $qty,
            'stock_id' => Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID,
            'is_in_stock' => $qty >= 1,
            'use_config_min_qty' => 1,
            'use_config_min_sale_qty' => 1,
            'use_config_max_sale_qty' => 1,
            'is_qty_decimal' => 0
        ));

        $stockItem->save();

        return $product;
    }

    //########################################

    /**
     * @param $data
     *
     * @return Mage_Catalog_Model_Product
     * @throws Exception
     */
    public function buildProduct($data) {

        $product = clone $this->product;
        $product = $this->loadProduct($product, $data['identifiers'], $data['marketplace_id']);
        if ($product->getId() && $this->eBayConfig->isActionFoundIgnore()) {
            return $product;
        }

        if (!$product->getId()) {
            $product->setData('type_id', self::TYPE_SIMPLE);
            $product->setData('attribute_set_id', $this->eBayConfig->getAttributeSet());
            $product->setData('website_ids', array(Mage::app()->getStore(
                $this->eBayConfig->getStoreForMarketplace($data['marketplace_id']))->getWebsiteId()
            ));
        }

        $fieldsAttributes = $this->eBayConfig->getEbayFieldMagentoAttribute();
        foreach ($fieldsAttributes as $eBayField => $magentoAttribute) {
            $eBayFieldPath = explode('][', $eBayField);
            switch (count($eBayFieldPath)) {
                case 1:
                    $product->setData($magentoAttribute, $data[$eBayFieldPath[0]]);
                    continue;
                case 2:
                    $product->setData($magentoAttribute, $data[$eBayFieldPath[0]][$eBayFieldPath[1]]);
                    continue;
                case 3:
                    $product->setData($magentoAttribute, $data[$eBayFieldPath[0]][$eBayFieldPath[1]][$eBayFieldPath[2]]);
                    continue;
                case 4:
                    $product->setData($magentoAttribute, $data[$eBayFieldPath[0]][$eBayFieldPath[1]][$eBayFieldPath[2]][$eBayFieldPath[3]]);
                    continue;
            }
        }

        $product->setData('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
        $product->setData('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $product->setData('tax_class_id', 0);

        //---------------------------------------

        if ($this->eBayConfig->isImportImage()) {
            $product = $this->importImage($product, $data['images']);
        }

        //---------------------------------------

        return $product;
    }

    private function loadEbayGroup() {

        if (!empty($this->groupId)) {
            return $this->groupId;
        }

        $groups = Mage::getModel('eav/entity_attribute_group')->getResourceCollection()
            ->addFilter('attribute_group_name', 'eBay')
            ->addFilter('attribute_set_id', $this->eBayConfig->getAttributeSet());

        $group = array_shift($groups);
        if ($group) {
            return $this->groupId = $group->getId();
        }

        $group = Mage::getModel('eav/entity_attribute_group');
        $group->setAttributeGroupName('eBay')
            ->setAttributeSetId($this->eBayConfig->getAttributeSet());
        $group->save();

        return $this->groupId = $group->getId();
    }

    /**
     * @param $code
     * @param $title
     *
     * @return Mage_Eav_Model_Entity_Attribute_Abstract
     * @throws Exception
     */
    private function createAttribute($code, $title) {

        $attribute = Mage::getModel('catalog/resource_eav_attribute');
        $attribute->addData(array(
            'attribute_code' => $code,
            'is_global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
            'frontend_input' => 'select',
            'default_value_text' => '',
            'default_value_yesno' => '0',
            'default_value_date' => '',
            'default_value_textarea' => '',
            'is_unique' => '0',
            'is_required' => '0',
            'apply_to' => array('simple', 'configurable'),
            'is_configurable' => '1',
            'is_searchable' => '0',
            'is_visible_in_advanced_search' => '1',
            'is_comparable' => '1',
            'is_used_for_price_rules' => '0',
            'is_wysiwyg_enabled' => '0',
            'is_html_allowed_on_front' => '1',
            'is_visible_on_front' => '0',
            'used_in_product_listing' => '0',
            'used_for_sort_by' => '0',
            'frontend_label' => array(Mage_Core_Model_App::ADMIN_STORE_ID => $title),
            'type' => 'varchar',
            'backend_type' => 'varchar',
            'backend' => 'eav/entity_attribute_backend_array'
        ));
        $attribute->setAttributeSetId($this->eBayConfig->getAttributeSet());
        $attribute->setAttributeGroupId($this->loadEbayGroup());
        $attribute->setEntityTypeId(Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId());
        $attribute->setIsUserDefined(1);
        $attribute->save();

        return Mage::getModel('eav/config')->getAttribute('catalog_product', $code);
    }

    /**
     * @param $attribute
     * @param string $value
     * @param int $storeId
     *
     * @return int
     */
    private function addAttributeValue($attribute, $value, $storeId) {

        $optionId = Mage::getModel('eav/entity_attribute_source_table')->setAttribute($attribute)->getOptionId($value);
        if ($optionId) {
            return $optionId;
        }

        $attribute->setData('option', array('value' => array('option' =>
            array($storeId => $value)
        )));
        $attribute->save();

        return Mage::getModel('eav/entity_attribute_source_table')
            ->setAttribute($attribute)
            ->getOptionId($value);
    }

    /**
     * @param $data
     *
     * @return Mage_Catalog_Model_Product
     * @throws Exception
     */
    public function buildConfigurableProduct($data) {

        $configProduct = $this->buildProduct($data);
        $configProduct->setData('type_id', self::TYPE_CONFIGURABLE);
        $configProduct->save();

        $updater = Mage::getSingleton('catalog/product_action');

        $childProducts = array();
        $attributes = array();
        foreach ($data['variations'] as $variation) {
            $dataVariation = $data;

            $dataVariation['identifiers']['sku'] = $variation['sku'];
            $dataVariation['identifiers']['ean'] = $variation['details']['ean'];
            $dataVariation['identifiers']['upc'] = $variation['details']['upc'];
            $dataVariation['identifiers']['isbn'] = $variation['details']['isbn'];
            $dataVariation['identifiers']['epid'] = $variation['details']['epid'];
            $dataVariation['price']['current'] = $variation['price'];
            $dataVariation['price']['start'] = $variation['price'];
            $dataVariation['price']['buy_it_now'] = $variation['price'];
            $dataVariation['qty']['total'] = $variation['quantity'];
            $dataVariation['images']['urls'] = $variation['images'];

            $childProduct = $this->buildProduct($dataVariation);
            $update = empty($childProduct->getId());
            $childProduct->getResource()->save($childProduct);
            if ($this->isImportQty() && ($this->isActionFoundIgnore() && $update)) {
                $this->importQty($childProduct, $dataVariation['qty']);
            }

            foreach ($variation['specifics'] as $title => $specific) {
                $attributeCode = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title);
                $attributeCode = preg_replace('/[^0-9a-z]/i', '_', $attributeCode);
                $attributeCode = preg_replace('/_+/', '_', $attributeCode);
                $abc = 'abcdefghijklmnopqrstuvwxyz';
                if (preg_match('/^\d/', $attributeCode, $matches)) {
                    $index = $matches[0];
                    $attributeCode = $abc[$index] . '_' . $attributeCode;
                }
                $attributeCode = strtolower($attributeCode);

                $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $attributeCode);
                if ($attribute && !$attribute->getId()) {
                    $attribute = $this->createAttribute($attributeCode, $title);
                }

                $optionId = $this->addAttributeValue(
                    $attribute,
                    $specific,
                    $this->eBayConfig->getStoreForMarketplace($dataVariation['marketplace_id'])
                );
                $updater->updateAttributes(array($childProduct->getId()), array(
                    $attributeCode => $optionId
                ), $childProduct->getStoreId());

                $attributes[$attribute->getId()] = $attributeCode;
            }

            $childProduct->getResource()->save($childProduct);
            $childProducts[] = $childProduct;
        }

        $sets = array();
        $setsP = array();
        foreach ($childProducts as $childProduct) {
            foreach ($attributes as $id => $code) {
                $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $code);
                $d = array(
                    'label' => $attribute->getName(),
                    'attribute_id' => $attribute->getId(),
                    'value_index' => $id,
                    'is_percent' => 0,
                    'pricing_value' => $childProduct->getPrice(),
                );

                $sets[$code][] = $d;
                $setsP[$childProduct->getId()][] = $d;
            }
        }

        /** assigning associated product to configurable */
        $configProduct = (clone $this->product)->load($configProduct->getId());
        $configProduct->getTypeInstance()->setUsedProductAttributeIds(array_keys($attributes));
        $configurableAttributesData = $configProduct->getTypeInstance()->getConfigurableAttributesAsArray();
        foreach ($configurableAttributesData as &$configurableAttributesDatum) {
            $configurableAttributesDatum['values'] = array_merge(
                $configurableAttributesDatum['values'],
                $sets[$configurableAttributesDatum['attribute_code']]
            );
        }

        $configurableProductsData = array();
        foreach ($childProducts as $childProduct) {
            $configurableProductsData[$childProduct->getId()] = $setsP[$childProduct->getId()];
        }

        $configProduct->setData('configurable_products_data', $configurableProductsData);
        $configProduct->setData('configurable_attributes_data', $configurableAttributesData);
        $configProduct->setData('can_save_configurable_attributes', true);
        $configProduct->save();

        return $configProduct;
    }

    //########################################

    /**
     * @return bool
     */
    public function isImportQty() {
        return $this->eBayConfig->isImportQty();
    }

    public function isActionFoundIgnore() {
        return $this->eBayConfig->isActionFoundIgnore();
    }



    //########################################

    /**
     * @inheritDoc
     */
    public function __construct() {
        parent::__construct();

        $this->eBayConfig = Mage::helper('e2m/eBay_Config');
        $this->coreHelper = Mage::helper('core');
        $this->product = Mage::getModel('catalog/product');
    }
}
