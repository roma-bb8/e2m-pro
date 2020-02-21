<?php

/**
 * Class M2E_E2M_Helper_Data
 */
class M2E_E2M_Helper_Data extends Mage_Core_Helper_Abstract {

    const PREFIX = 'm2e/e2m/';

    const TYPE_REPORT_ERROR = 3;
    const TYPE_REPORT_WARNING = 2;
    const TYPE_REPORT_SUCCESS = 1;

    //########################################

    private $magentoAttributeSets = array();
    private $magentoAttributes = array();
    private $magentoStores = array();

    //########################################

    /**
     * @return array
     */
    public function getMagentoStores() {
        if (!empty($this->magentoStores)) {
            return $this->magentoStores;
        }

        foreach (Mage::app()->getStores(true) as $store) {
            /** @var Mage_Core_Model_Store $store */
            $this->magentoStores[$store->getId()] = $store->getName();
        }

        return $this->magentoStores;
    }

    //########################################

    /**
     * @return array
     */
    public function getAllAttributeSet() {
        if (!empty($this->magentoAttributeSets)) {
            return $this->magentoAttributeSets;
        }

        $entityType = Mage::getModel('catalog/product')->getResource()->getTypeId();
        $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection');
        $attributeSetCollection->setEntityTypeFilter($entityType);
        foreach ($attributeSetCollection as $attributeSet) {
            $name = $attributeSet->getAttributeSetName();
            $attributeSetId = (int)$attributeSet->getId();

            $this->magentoAttributeSets[$attributeSetId] = $name;
        }

        return $this->magentoAttributeSets;
    }

    //########################################

    /**
     * @param int $setId
     * @param bool $reload
     *
     * @return array
     */
    public function getMagentoAttributes($setId, $reload = false) {
        if (!empty($this->magentoAttributes[$setId]) && !$reload) {
            return $this->magentoAttributes[$setId];
        }

        /** @var Mage_Eav_Model_Entity_Attribute_Group[] $groups */
        $groups = Mage::getModel('eav/entity_attribute_group')
            ->getResourceCollection()
            ->setAttributeSetFilter($setId)
            ->setSortOrder()
            ->load();

        $sets = array();
        foreach ($groups as $group) {

            $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->setAttributeGroupFilter($group->getId())
                ->addVisibleFilter()
                ->checkConfigurableProducts()
                ->load();

            if ($attributes->getSize() <= 0) {
                continue;
            }

            foreach ($attributes->getItems() as $attribute) {
                $sets[$attribute['attribute_code']] = $attribute['frontend_label'];
            }
        }

        return $this->magentoAttributes[$setId] = $sets;
    }

    //########################################

    /**
     * @param int $taskId
     * @param string $description
     * @param int $type
     */
    public function logReport($taskId, $description, $type = self::TYPE_REPORT_SUCCESS) {
        $resource = Mage::getSingleton('core/resource');
        $coreConfigDataTableName = $resource->getTableName('m2e_e2m_log');
        $connWrite = $resource->getConnection('core_write');
        $connWrite->insert($coreConfigDataTableName, array(
            'task_id' => $taskId,
            'type' => $type,
            'description' => $description
        ));
    }

    //########################################

    public function logException(Exception $e) {

        $type = get_class($e);
        $exceptionInfo = <<<EXCEPTION

-------------------------------- EXCEPTION INFO ----------------------------------
Type: {$type}
File: {$e->getFile()}
Line: {$e->getLine()}
Code: {$e->getCode()}
Message: {$e->getMessage()}
-------------------------------- STACK TRACE INFO --------------------------------
{$e->getTraceAsString()}

###################################################################################
EXCEPTION;

        Mage::log($exceptionInfo, Zend_Log::ERR, 'e2m.log', true);
    }
}
