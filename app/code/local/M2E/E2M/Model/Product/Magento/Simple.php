<?php

class M2E_E2M_Model_Product_Magento_Simple extends M2E_E2M_Model_Product_Magento_Product {

    const TYPE = 'simple';

    //########################################

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $data
     * @param bool $save
     *
     * @return Mage_Catalog_Model_Product
     * @throws Exception
     */
    private function createProduct(Mage_Catalog_Model_Product $product, array $data, $save) {

        $product->setData('type_id', self::TYPE);
        $product->setData('attribute_set_id', $this->getAttributeSet());
        $product->setData('website_ids', array(Mage::app()->getStore($product->getStoreId())->getWebsiteId()));
        $product->setData('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
        $product->setData('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $product->setData('tax_class_id', Ess_M2ePro_Model_Magento_Product::TAX_CLASS_ID_NONE);

        $product = $this->importFields($product, $data);

        if ($this->eBayConfigHelper->isImportImage()) {
            $product = $this->importImage($product, $data);
        }

        if (!$save) {
            return $product;
        }

        try {

            $product->save();

            $this->addLog(sprintf(
                'Create product: "%s" Store ID: "%s" from eBay Item ID: "%s"',
                $product->getSku(),
                $product->getStoreId(),
                $data['identifiers_item_id']
            ));

        } catch (Exception $e) {
            $this->addLog(sprintf(
                'Not create product from eBay Item ID: "%s" Because: %s',
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

        return $product;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $data
     * @param bool $save
     *
     * @return Mage_Catalog_Model_Product
     * @throws Exception
     */
    private function updateProduct(Mage_Catalog_Model_Product $product, array $data, $save) {

        $product->setData('website_ids', array_unique(array_merge(
            $product->getData('website_ids') ?: array(),
            array(Mage::app()->getStore($product->getStoreId())->getWebsiteId())
        )));

        $product = $this->importFields($product, $data);

        if ($this->eBayConfigHelper->isImportImage()) {
            $product = $this->updateImage($product, $data);
        }

        if (!$save) {
            return $product;
        }

        try {

            $product->save();

            $this->addLog(sprintf(
                'Update product: "%s" Store ID: "%s" from eBay Item ID: "%s"',
                $product->getSku(),
                $product->getStoreId(),
                $data['identifiers_item_id']
            ));

        } catch (Exception $e) {
            $this->addLog(sprintf(
                'Not update product: "%s" from eBay Item ID: "%s" Because: %s',
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

        return $product;
    }

    //########################################

    /**
     * @inheritDoc
     */
    public function process(array $data, $save = true) {

        $data = $this->prepareData($data);

        $product = clone $this->product;
        $product->setStoreId($this->eBayConfigHelper->getStoreForMarketplace($data['marketplace_id']));

        //----------------------------------------

        $product = $this->loadProduct($product, $data);
        if ($product->getId() && $this->eBayConfigHelper->isIgnoreActionFound()) {
            $this->addLog(sprintf(
                'Skip update sku: "%s" Store ID: "%s"',
                $product->getSku(),
                $product->getStoreId()
            ), M2E_E2M_Helper_Data::TYPE_REPORT_WARNING);

            if ($this->eBayConfigHelper->isImportQty()) {
                $product = $this->importQty($product, $data);
            }

            return $product;
        }

        if ($product->getId()) {
            return $this->updateProduct($product, $data, $save);
        }

        return $this->createProduct($product, $data, $save);
    }
}
