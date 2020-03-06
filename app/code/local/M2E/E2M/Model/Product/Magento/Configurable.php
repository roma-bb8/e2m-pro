<?php

class M2E_E2M_Model_Product_Magento_Configurable extends M2E_E2M_Model_Product_Magento_Product {

    const TYPE = 'configurable';

    //########################################

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $data
     *
     * @return Mage_Catalog_Model_Product
     * @throws Exception
     */
    private function updateProduct(Mage_Catalog_Model_Product $product, array $data) {

        try {

            $product->save();

            $this->addLog(sprintf(
                'Update config product: "%s" Store ID: "%s" from eBay Item ID: "%s"',
                $product->getSku(),
                $data['identifiers_item_id'],
                $product->getStoreId()
            ));

        } catch (Exception $e) {
            $this->addLog(sprintf(
                'Not update config product: "%s" from eBay Item ID: "%s" Because: %s',
                $product->getSku(),
                $data['identifiers_item_id'],
                $e->getMessage()
            ), M2E_E2M_Helper_Data::TYPE_REPORT_ERROR);

            throw $e;
        }

        if ($this->eBayConfigHelper->isImportQty()) {
            $product = $this->importQty($product, $data);
        }

        if ($this->eBayConfigHelper->isImportSpecifics()) {
            $product = $this->importSpecifics($product, 'eBay Specifics', $data['specifics']);
        }

        $this->addLog(sprintf(
            'Skip update set products from config product: "%s" from eBay Item ID: "%s"',
            $product->getSku(),
            $data['identifiers_item_id']
        ), M2E_E2M_Helper_Data::TYPE_REPORT_WARNING);

        return $product;
    }

    /**
     * @param Mage_Catalog_Model_Product $configProduct
     * @param array $attributes
     * @param array $childProducts
     * @param array $data
     *
     * @return Mage_Catalog_Model_Product
     * @throws Exception
     */
    private function createProduct(Mage_Catalog_Model_Product $configProduct,
                                   array $attributes, array $childProducts, array $data) {

        try {

            $configProduct->setData('type_id', self::TYPE);
            $configProduct->save();

            $this->addLog(sprintf(
                'Create config product: "%s" Store ID: "%s" from eBay Item ID: "%s"',
                $configProduct->getSku(),
                $data['identifiers_item_id'],
                $configProduct->getStoreId()
            ));

        } catch (Exception $e) {
            $this->addLog(sprintf(
                'Not create config product from eBay Item ID: "%s" Because: %s',
                $data['identifiers_item_id'],
                $e->getMessage()
            ), M2E_E2M_Helper_Data::TYPE_REPORT_ERROR);

            throw $e;
        }

        //----------------------------------------

        $productSet = array();
        $set = array();
        foreach ($childProducts as $childProductId => $childProductPrice) {
            foreach ($attributes as $id => $attribute) {
                $childData = array(
                    'id' => $attribute->getId(),
                    'label' => $attribute->getName(),
                    'attribute_id' => $attribute->getId(),
                    'value_index' => 0,
                    'is_percent' => 0,
                    'pricing_value' => $childProductPrice
                );

                $set[$attribute->getAttributeCode()][] = $childData;
                $productSet[$childProductId][] = $childData;
            }
        }

        //----------------------------------------

        /**
         ** Dirty hack **
         *      by realtime cache attributes
         * app/code/core/Mage/Eav/Model/Config.php:450
         * getEntityAttributeCodes:463
         *
         * if create new attribute current request
         */
        Mage::unregister('_singleton/eav/config');

        //***
        /** @var Mage_Catalog_Model_Product $configProduct */
        $product = clone $this->product;
        $storeId = $configProduct->getStoreId();
        $configProduct = $product->load($configProduct->getId());
        $configProduct->setStoreId($storeId);
        //***

        //----------------------------------------

        $configProduct->getTypeInstance()->setUsedProductAttributeIds(array_keys($attributes));
        $configurableAttributesData = $configProduct->getTypeInstance()->getConfigurableAttributesAsArray();
        foreach ($configurableAttributesData as &$configurableAttributesDatum) {
            $configurableAttributesDatum['values'] = $set[$configurableAttributesDatum['attribute_code']];
        }
        unset($configurableAttributesDatum);

        $configurableProductsData = array();
        foreach ($childProducts as $childProductId => $childProductPrice) {
            $configurableProductsData[$childProductId] = $productSet[$childProductId];
        }

        $configProduct->setData('configurable_products_data', $configurableProductsData);
        $configProduct->setData('configurable_attributes_data', $configurableAttributesData);
        $configProduct->setData('can_save_configurable_attributes', true);
        $configProduct->save();

        //----------------------------------------

        if ($this->eBayConfigHelper->isImportQty()) {
            $product = $this->importQty($product, $data);
        }

        if ($this->eBayConfigHelper->isImportSpecifics()) {
            $configProduct = $this->importSpecifics($product, 'eBay Specifics', $data['specifics']);
        }

        return $configProduct;
    }

    /**
     * @inheritDoc
     */
    public function process(array $data, $save = true) {

        /** @var M2E_E2M_Model_Product_Magento_Simple $productMagentoSimple */
        $productMagentoSimple = Mage::getModel('e2m/Product_Magento_Simple');
        $productMagentoSimple->setTaskId($this->taskId);

        $data = $this->prepareData($data);

        //----------------------------------------

        $childProducts = array();
        $attributes = array();
        foreach ($data['variations'] as $variation) {
            $dataVariation = $data;

            isset($variation['sku']) && $dataVariation['identifiers_sku'] = $variation['sku'];
            isset($variation['details']['ean']) && $dataVariation['identifiers_ean'] = $variation['details']['ean'];
            isset($variation['details']['upc']) && $dataVariation['identifiers_upc'] = $variation['details']['upc'];
            isset($variation['details']['isbn']) && $dataVariation['identifiers_isbn'] = $variation['details']['isbn'];
            isset($variation['details']['epid']) && $dataVariation['identifiers_epid'] = $variation['details']['epid'];
            isset($variation['price']) && $dataVariation['price_current'] = $variation['price'];
            isset($variation['price']) && $dataVariation['price_start'] = $variation['price'];
            isset($variation['price']) && $dataVariation['price_buy_it_now'] = $variation['price'];
            isset($variation['quantity']) && $dataVariation['qty_total'] = $variation['quantity'];
            isset($variation['images']) && $dataVariation['images_urls'] = $variation['images'];

            $childProduct = $productMagentoSimple->process($dataVariation);
            $childProduct = $this->importSpecifics(
                $childProduct,
                'eBay',
                $variation['specifics'],
                false,
                $this->eBayConfigHelper->isImportRenameAttribute()
            );
            $attributes += $this->attributes;

            $childProducts[$childProduct->getId()] = $childProduct->getPrice();
        }

        //----------------------------------------

        $configProduct = $productMagentoSimple->process($data, false);
        if ($configProduct->getId() && $this->eBayConfigHelper->isIgnoreActionFound()) {
            return $configProduct;
        }

        if ($configProduct->getId()) {
            return $this->updateProduct($configProduct, $data);
        }

        return $this->createProduct($configProduct, $attributes, $childProducts, $data);
    }
}
