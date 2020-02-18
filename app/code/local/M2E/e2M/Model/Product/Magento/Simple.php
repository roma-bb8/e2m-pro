<?php

/**
 * Class M2E_e2M_Model_Product_Magento_Simple
 */
class M2E_e2M_Model_Product_Magento_Simple extends M2E_e2M_Model_Product_Magento_Product {

    const TYPE = 'simple';

    //########################################

    /**
     * @inheritDoc
     */
    public function process($data, $save = true) {

        if ($this->eBayConfig->isGenerateSku() || empty($data['identifiers_sku'])) {
            $data['identifiers_sku'] = 'RANDOM_' . md5($data['identifiers_item_id']);
        }

        $product = clone $this->product;
        $product = $this->loadProduct($product, $data, $data['marketplace_id']);
        if ($product->getId() && $this->eBayConfig->isActionFoundIgnore()) {
            $this->addLog('Skip update sku: ' . $product->getSku(), M2E_e2M_Helper_Data::TYPE_REPORT_WARNING);

            return $product;
        }

        if (!$product->getId()) {
            $product->setData('type_id', self::TYPE);
            $product->setData('store_id', $this->eBayConfig->getStoreForMarketplace($data['marketplace_id']));
            $product->setData('attribute_set_id', $this->eBayConfig->getAttributeSet());
            $product->setData('website_ids', array(Mage::app()->getStore(
                $this->eBayConfig->getStoreForMarketplace($data['marketplace_id']))->getWebsiteId()
            ));
            $product->setData('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
            $product->setData('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
            $product->setData('tax_class_id', 0);
        }

        if ($this->eBayConfig->isDeleteHtml()) {
            $data['description_title'] = strip_tags($data['description_title']);
            $data['description_subtitle'] = strip_tags($data['description_subtitle']);
            $data['description_description'] = strip_tags($data['description_description']);
        }

        $fieldsAttributes = $this->eBayConfig->getEbayFieldMagentoAttribute();
        foreach ($fieldsAttributes as $eBayField => $magentoAttribute) {
            $product->setData($magentoAttribute, $data[$eBayField]);
        }

        //---------------------------------------

        if ($this->eBayConfig->isImportImage()) {
            $product = $this->importImage($product, $data);
        }

        if ($save) {
            $product->save();
        }

        if ($save && $this->eBayConfig->isImportQty()) {
            $product = $this->importQty($product, $data);
        }

        return $product;
    }
}
