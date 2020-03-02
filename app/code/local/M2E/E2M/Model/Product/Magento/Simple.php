<?php

class M2E_E2M_Model_Product_Magento_Simple extends M2E_E2M_Model_Product_Magento_Product {

    const TYPE = 'simple';

    //########################################

    /**
     * @inheritDoc
     */
    public function process($data, $save = true) {

        if ($this->eBayConfigHelper->isGenerateSku() && empty($data['identifiers_sku'])) {
            $data['identifiers_sku'] = 'RANDOM_' . md5($data['identifiers_item_id']);
        }

        $storeId = $this->eBayConfigHelper->getStoreForMarketplace($data['marketplace_id']);
        $product = clone $this->product;
        $product = $this->loadProduct($product, $data, $storeId);
        if ($product->getId() && $this->eBayConfigHelper->isIgnoreActionFound()) {
            $this->addLog('Skip update sku: ' . $product->getSku()
                . ' Store ID: ' . $product->getStoreId(), M2E_E2M_Helper_Data::TYPE_REPORT_WARNING);

            if ($save && $this->eBayConfigHelper->isImportQty()) {
                $product = $this->importQty($product, $data);
            }

            return $product;
        }

        if (!$product->getId()) {
            $product->setData('type_id', self::TYPE);
            $product->setData('store_id', $storeId);
            $product->setData('attribute_set_id',
                $this->dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET)
            );
            $product->setData('website_ids', array(Mage::app()->getStore($storeId)->getWebsiteId()));
            $product->setData('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
            $product->setData('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
            $product->setData('tax_class_id', 0);
        } else {
            $product->setData('store_id', $storeId);
            $product->setData('website_ids', array_unique(array_merge(
                $product->getData('website_ids') ?: array(),
                Mage::app()->getStore($storeId)->getWebsiteId()
            )));
        }

        if ($this->eBayConfigHelper->isDeleteHtml()) {
            $data['description_title'] = strip_tags($data['description_title']);
            $data['description_subtitle'] = strip_tags($data['description_subtitle']);
            $data['description_description'] = strip_tags($data['description_description']);
        }

        $fieldsAttributes = $this->dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_MAP);
        foreach ($fieldsAttributes as $magentoAttribute => $eBayField) {
            if (empty($data[$eBayField])) {
                continue;
            }

            $product->setData($magentoAttribute, $data[$eBayField]);
        }

        if ($this->eBayConfigHelper->isGenerateSku() && empty($product->getSku())) {
            $product->setData('sku', 'RANDOM_' . md5($data['identifiers_item_id']));
        }

        //---------------------------------------

        if (!$product->getId() && $this->eBayConfigHelper->isImportImage()) {
            $product = $this->importImage($product, $data);
        } elseif ($product->getId() && $this->eBayConfigHelper->isImportImage()) {
            $product = $this->updateImage($product, $data);
        }

        if ($save) {
            $action = $product->getId() ? 'Update' : 'Create';
            $product->save();

            $this->addLog($action . ' product: "' . $product->getSku() .
                '" eBay Item Id: ' . $data['identifiers_item_id'] . ' Store ID: ' . $product->getStoreId());
        }

        if ($save && $this->eBayConfigHelper->isImportQty()) {
            $product = $this->importQty($product, $data);
        }

        return $product;
    }
}
