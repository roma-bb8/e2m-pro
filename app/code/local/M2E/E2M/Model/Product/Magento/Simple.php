<?php
/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * Class M2E_E2M_Model_Product_Magento_Simple
 */
class M2E_E2M_Model_Product_Magento_Simple extends M2E_E2M_Model_Product_Magento_Product {

    const TYPE = 'simple';

    //########################################

    /**
     * @inheritDoc
     */
    public function process($data, $save = true) {

        if ($this->eBayConfig->isGenerateSku() && empty($data['identifiers_sku'])) {
            $data['identifiers_sku'] = 'RANDOM_' . md5($data['identifiers_item_id']);
        }

        $storeId = $this->eBayConfig->getStoreForMarketplace($data['marketplace_id']);
        $product = clone $this->product;
        $product = $this->loadProduct($product, $data, $storeId);
        if ($product->getEntityId() && $this->eBayConfig->isIgnoreActionFound()) {
            $this->addLog('Skip update sku: ' . $product->getSku(), M2E_E2M_Helper_Data::TYPE_REPORT_WARNING);

            if ($save && (bool)$this->eBayConfig->get(M2E_E2M_Model_Ebay_Config::PATH_PRODUCT_IMPORT_QTY)) {
                $product = $this->importQty($product, $data);
            }

            return $product;
        }

        if (!$product->getId()) {
            $product->setData('type_id', self::TYPE);
            $product->setData('store_id', $storeId);
            $product->setData('attribute_set_id',
                $this->eBayConfig->get(M2E_E2M_Model_Ebay_Config::PATH_PRODUCT_ATTRIBUTE_SET)
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

        if ($this->eBayConfig->isDeleteHtml()) {
            $data['description_title'] = strip_tags($data['description_title']);
            $data['description_subtitle'] = strip_tags($data['description_subtitle']);
            $data['description_description'] = strip_tags($data['description_description']);
        }

        $fieldsAttributes = $this->eBayConfig->get(M2E_E2M_Model_Ebay_Config::PATH_PRODUCT_FIELDS_ATTRIBUTES_MAP);
        foreach ($fieldsAttributes as $magentoAttribute => $eBayField) {
            if (empty($data[$eBayField])) {
                continue;
            }

            $product->setData($magentoAttribute, $data[$eBayField]);
        }

        //---------------------------------------

        if (!$product->getId() && $this->eBayConfig->isImportImage()) {
            $product = $this->importImage($product, $data);
        } elseif ($product->getId() && $this->eBayConfig->isImportImage()) {
            $product = $this->updateImage($product, $data);
        }

        if ($save) {
            $action = $product->getId() ? 'Update' : 'Create';
            $product->save();

            $this->addLog($action . ' product: "' . $product->getSku() .
                '" eBay Item Id: ' . $data['identifiers_item_id']);
        }

        if ($save && $this->eBayConfig->isImportQty()) {
            $product = $this->importQty($product, $data);
        }

        return $product;
    }
}
