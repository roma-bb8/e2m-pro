<?php

class M2E_E2M_Helper_Magento {

    /** @var array $stores */
    private $stores = array();

    /** @var array $attributeSets */
    private $attributeSets = array();

    /** @var array $attributeSetNames */
    private $attributeSetNames = array();

    //########################################

    /**
     * @return array
     */
    public function getStores() {

        if (!empty($this->stores)) {
            return $this->stores;
        }

        foreach (Mage::app()->getStores(true) as $store) {

            /** @var Mage_Core_Model_Store $store */
            $this->stores[(int)$store->getId()] = $store->getName();
        }

        return $this->stores;
    }

    /**
     * @param int $storeId
     *
     * @return string
     */
    public function getCodeStoreById($storeId) {

        foreach (Mage::app()->getStores(true) as $store) {

            /** @var Mage_Core_Model_Store $store */
            if ($storeId === (int)$store->getId()) {
                return $store->getCode();
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function getAllAttributeSet() {

        if (!empty($this->attributeSets)) {
            return $this->attributeSets;
        }

        $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection');
        $attributeSetCollection->setEntityTypeFilter(Mage::getModel('catalog/product')->getResource()->getTypeId());
        foreach ($attributeSetCollection as $attributeSet) {
            $name = $attributeSet->getAttributeSetName();
            $attributeSetId = (int)$attributeSet->getId();

            $this->attributeSets[$attributeSetId] = $name;
        }

        return $this->attributeSets;
    }

    /**
     * @param int $attributeSetId
     *
     * @return mixed|string
     */
    public function getAttributeSetNameById($attributeSetId) {

        if (isset($this->attributeSetNames[$attributeSetId])) {
            return $this->attributeSetNames[$attributeSetId];
        }

        $resource = Mage::getSingleton('core/resource');
        $attributeSetName = $resource->getConnection('core_read')->select()
            ->from($resource->getTableName('eav_attribute_set'), 'attribute_set_name')
            ->where('attribute_set_id = ?', $attributeSetId)->query()->fetchColumn();

        return $this->attributeSetNames[$attributeSetId] = $attributeSetName;
    }

    /**
     * @return int
     */
    public function getMediaAttributeId() {

        $resource = Mage::getSingleton('core/resource');
        $attributeId = $resource->getConnection('core_read')->select()
            ->from($resource->getTableName('eav_attribute'), 'attribute_id')
            ->where('attribute_code = ?', 'media_gallery')->limit(1)->query()->fetchColumn();

        return (int)$attributeId;
    }
}
