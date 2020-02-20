<?php

/**
 * Class M2E_e2M_Model_Product_Magento_Configurable
 */
class M2E_e2M_Model_Product_Magento_Configurable extends M2E_e2M_Model_Product_Magento_Product {

    const TYPE = 'configurable';

    //########################################

    /**
     * @inheritDoc
     */
    public function process($data, $save = true) {

        /** @var M2E_e2M_Model_Product_Magento_Simple $productMagentoSimple */
        $productMagentoSimple = Mage::getModel('e2m/Product_Magento_Simple');
        $productMagentoSimple->setTaskId($this->taskId);

        $configProduct = $productMagentoSimple->process($data, false);
        if ($configProduct->getEntityId() && $this->eBayConfig->isActionFoundIgnore()) {
            $this->addLog('Skip update sku: ' . $configProduct->getSku(), M2E_e2M_Helper_Data::TYPE_REPORT_WARNING);

            if ($this->eBayConfig->isImportQty()) {
                $configProduct = $this->importQty($configProduct, $data);
            }

            return $configProduct;
        }

        $isNew = !$configProduct->getEntityId();
        if ($isNew) {
            $configProduct->setData('type_id', self::TYPE);
            $configProduct->save();
        } elseif (self::TYPE !== $configProduct->getData('type_id')) {
            $this->addLog('Skip update sku: ' . $configProduct->getSku() . ' because type product not configurable', M2E_e2M_Helper_Data::TYPE_REPORT_ERROR);

            return $configProduct;
        }

        //----------------------------------------

        /** @var Mage_Catalog_Model_Product_Action $updater */
        $updater = Mage::getSingleton('catalog/product_action');

        $attributes = array();
        $childProducts = array();
        foreach ($data['variations'] as $variation) {
            $dataVariation = $data;

            $dataVariation['identifiers_sku'] = $variation['sku'];
            $dataVariation['identifiers_ean'] = $variation['details']['ean'];
            $dataVariation['identifiers_upc'] = $variation['details']['upc'];
            $dataVariation['identifiers_isbn'] = $variation['details']['isbn'];
            $dataVariation['identifiers_epid'] = $variation['details']['epid'];
            $dataVariation['price_current'] = $variation['price'];
            $dataVariation['price_start'] = $variation['price'];
            $dataVariation['price_buy_it_now'] = $variation['price'];
            $dataVariation['qty_total'] = $variation['quantity'];
            $dataVariation['images_urls'] = $variation['images'];

            $childProduct = $productMagentoSimple->process($dataVariation);

            $storeId = $this->eBayConfig->getStoreForMarketplace($dataVariation['marketplace_id']);
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
                    $attribute = $this->createAttribute($attributeCode, $title, $storeId);
                }
                $attribute->setData('store_id', $storeId);
                $this->checkAssignedAttributes($attribute);

                $optionId = $this->addAttributeValue($attribute, $specific, $storeId);
                if ($optionId === null) {
                    //TODO
                }

                $updater->updateAttributes(array($childProduct->getId()), array(
                    $attributeCode => $optionId
                ), $childProduct->getStoreId());

                $attributes[$attribute->getId()] = $attributeCode;
            }

            $childProducts[$childProduct->getId()] = $childProduct->getPrice();
        }

        //----------------------------------------

        $set = array();
        $productSet = array();
        foreach ($childProducts as $childProductId => $childProductPrice) {
            foreach ($attributes as $id => $code) {
                $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $code);
                $childData = array(
                    'id' => $attribute->getId(),
                    'label' => $attribute->getName(),
                    'attribute_id' => $attribute->getId(),
                    'value_index' => $id,
                    'is_percent' => 0,
                    'pricing_value' => $childProductPrice
                );

                $set[$code][] = $childData;
                $productSet[$childProductId][] = $childData;
            }
        }

        //----------------------------------------

        /** @var Mage_Catalog_Model_Product $configProduct */
        $configProduct = (clone $this->product)
            ->setData('attribute_set_id', $this->eBayConfig->getAttributeSet())
            ->setData('store_id', $configProduct->getStoreId())
            ->load($configProduct->getId());

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

        if ($this->eBayConfig->isImportQty()) {
            $configProduct = $this->importQty($configProduct, $data);
        }

        return $configProduct;
    }
}
