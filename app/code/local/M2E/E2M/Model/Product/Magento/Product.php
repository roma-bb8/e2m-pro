<?php

abstract class M2E_E2M_Model_Product_Magento_Product extends Mage_Core_Model_Abstract {

    const DOES_NOT_APPLY = 'does not apply';

    //########################################

    /** @var string $tmpMediaPath */
    private $tmpMediaPath;

    /** @var Mage_Core_Helper_Data $coreHelper */
    private $coreHelper;

    /** @var M2E_E2M_Helper_Data $dataHelper */
    private $dataHelper;

    /** @var int $taskId */
    protected $taskId;

    /** @var M2E_E2M_Helper_Ebay_Config $eBayConfigHelper */
    protected $eBayConfigHelper;

    /** @var M2E_E2M_Helper_Magento_Attribute $magentoAttributeHelper */
    protected $magentoAttributeHelper;

    /** @var Mage_Catalog_Model_Product $product */
    protected $product;

    /** @var array $attributes */
    protected $attributes;

    //########################################

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $data
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function importQty(Mage_Catalog_Model_Product $product, array $data) {

        $qty = (int)$data['qty_total'];

        try {

            /** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
            $stockItem = Mage::getModel('cataloginventory/stock_item');
            $stockItem->assignProduct($product);
            $stockItem->addData(array(
                'qty' => $qty,
                'stock_id' => Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID,
                'is_in_stock' => $qty >= 1,
                'is_qty_decimal' => 0
            ));

            $stockItem->save();

        } catch (Exception $e) {
            $this->addLog(sprintf(
                "Quantity not import for product: %s Because: %s",
                $product->getSku(),
                $e->getMessage()
            ), M2E_E2M_Helper_Data::TYPE_REPORT_ERROR);
        }

        return $product;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $data
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function importImage(Mage_Catalog_Model_Product $product, array $data) {

        if (empty($data['images_urls'])) {
            return $product;
        }

        $gallery = array();
        foreach ($data['images_urls'] as $index => $url) {

            //------------------------------------------
            $ext = strtolower(substr($url, (strripos($url, '.'))));
            !in_array($ext, array('.png', '.jpg', '.jpeg')) && $ext = '.jpg';
            $fileName = md5($url) . $ext;
            //------------------------------------------

            if (!is_file($this->tmpMediaPath . DS . $fileName)) {
                try {
                    file_put_contents($this->tmpMediaPath . DS . $fileName, file_get_contents($url));
                } catch (Exception $e) {
                    $this->addLog("Image '{$url}' not import Because: " . $e->getMessage(), M2E_E2M_Helper_Data::TYPE_REPORT_WARNING);
                    continue;
                }
            }

            $gallery[] = array(
                'file' => $fileName,
                'label' => '',
                'position' => ($index + 1),
                'disabled' => 0,
                'removed' => 0
            );
        }

        if (empty($gallery)) {
            return $product;
        }

        foreach ($gallery as $index => $image) {
            try {
                $product->addImageToMediaGallery(
                    $this->tmpMediaPath . DS . $image['file'],
                    $index === 0 ? array('image', 'small_image', 'thumbnail') : null,
                    true,
                    false
                );
            } catch (Exception $e) {
                $this->addLog(sprintf(
                    'Image "%s" not adding to product Because: %s',
                    $this->tmpMediaPath . DS . $image['file'],
                    $e->getMessage()
                ), M2E_E2M_Helper_Data::TYPE_REPORT_WARNING);
                continue;
            }
        }

        return $product;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $data
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function updateImage(Mage_Catalog_Model_Product $product, array $data) {

        $galleryImages = $product->getData('media_gallery');
        if (!isset($galleryImages['images']) || !is_array($galleryImages['images'])) {
            return $this->importImage($product, $data);
        }

        $imagesURLs = array();
        foreach ($data['images_urls'] as $index => $url) {
            $ext = strtolower(substr($url, (strripos($url, '.'))));
            !in_array($ext, array('.png', '.jpg', '.jpeg')) && $ext = '.jpg';
            $imagesURLs[$index] = md5($url) . $ext;
        }

        $importURLs = array();
        foreach ($galleryImages['images'] as $galleryImage) {
            if (!isset($galleryImage['file'])) {
                continue;
            }

            if (!$i = array_search($galleryImage['file'], $imagesURLs)) {
                continue;
            }

            $importURLs[] = $data['images_urls'][$i];
        }

        if (!empty($importURLs)) {
            return $this->importImage($product, array('images_urls' => $importURLs));
        }

        return $product;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param string $groupName
     * @param array $specifics
     * @param bool $text
     * @param bool $rename
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function importSpecifics($product, $groupName, $specifics, $text = true, $rename = false) {
        $this->attributes = array();

        $this->magentoAttributeHelper->setStoreId($product->getStoreId());
        $this->magentoAttributeHelper->setAttributeSetId($this->getAttributeSet());
        $this->magentoAttributeHelper->setProduct($product);
        $this->magentoAttributeHelper->setGroupName($groupName);
        $this->magentoAttributeHelper->setText($text);
        $this->magentoAttributeHelper->setRename($rename);

        foreach ($specifics as $name => $value) {
            $this->magentoAttributeHelper->setTitle($name);
            $this->magentoAttributeHelper->setValue($value);

            try {

                $attribute = $this->magentoAttributeHelper->save();

                $this->attributes[$attribute->getId()] = $attribute;

            } catch (Exception $e) {
                $this->addLog(sprintf(
                    'Import data attribute not success Because: %s',
                    $e->getMessage()
                ), M2E_E2M_Helper_Data::TYPE_REPORT_ERROR);

                continue;
            }
        }

        return $product;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $data
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function importFields(Mage_Catalog_Model_Product $product, array $data) {

        $fieldsAttributes = $this->dataHelper->getConfig(M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_MAP);
        foreach ($fieldsAttributes as $magentoAttribute => $eBayField) {
            if (empty($data[$eBayField])) {
                continue;
            }

            $product->setData($magentoAttribute, $data[$eBayField]);
        }

        $product->setData('sku', $data[$this->eBayConfigHelper->getProductIdentifier()]);

        return $product;
    }

    //########################################

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $data
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function loadProduct(Mage_Catalog_Model_Product $product, array $data) {
        $product->load($product->getIdBySku($data[$this->eBayConfigHelper->getProductIdentifier()]));

        return $product;
    }

    //########################################

    /**
     * @return int|null
     */
    protected function getAttributeSet() {
        return $this->dataHelper->getConfig(
            M2E_E2M_Helper_Ebay_Config::XML_PATH_PRODUCT_ATTRIBUTE_SET
        );
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function prepareData(array $data) {

        if ($this->eBayConfigHelper->isDeleteHtml()) {
            $data['description_title'] = strip_tags($data['description_title']);
            $data['description_subtitle'] = strip_tags($data['description_subtitle']);
            $data['description_description'] = strip_tags($data['description_description']);
        }

        if (empty($data['identifiers_sku']) && $this->eBayConfigHelper->isGenerateSku()) {
            $data['identifiers_sku'] = 'SKU_' . md5($data['identifiers_item_id']);
        }

        $productID = $this->eBayConfigHelper->getProductIdentifier();
        if (empty($data[$productID])) {
            $data[$productID] = 'PID_' . md5($data['identifiers_item_id']);
        }

        if (self::DOES_NOT_APPLY === strtolower($data[$productID])) {
            $data[$productID] = 'DNA_' . md5($data['identifiers_item_id']);
        }

        //TODO Delete
        if ('refer to description' === strtolower($data[$productID])) {
            $data[$productID] = 'ROD_' . md5($data['identifiers_item_id']);
        }

        return $data;
    }

    //########################################

    /**
     * @param string $description
     * @param int $type
     */
    protected function addLog($description, $type = M2E_E2M_Helper_Data::TYPE_REPORT_SUCCESS) {
        $this->dataHelper->logReport($this->taskId, $description, $type);
    }

    //########################################

    /**
     * @param int $taskId
     *
     * @return $this
     */
    public function setTaskId($taskId) {
        $this->taskId = $taskId;
        $this->magentoAttributeHelper->setTaskId($taskId);

        return $this;
    }

    //########################################

    /**
     * @inheritDoc
     */
    public function __construct() {
        parent::__construct();

        $this->coreHelper = Mage::helper('core');

        $this->dataHelper = Mage::helper('e2m');

        $this->eBayConfigHelper = Mage::helper('e2m/Ebay_Config');
        $this->magentoAttributeHelper = Mage::helper('e2m/Magento_Attribute');

        $this->product = Mage::getModel('catalog/product');

        /** @var Mage_Catalog_Model_Product_Media_Config $productMediaConfig */
        $productMediaConfig = Mage::getSingleton('catalog/product_media_config');
        $this->tmpMediaPath = $productMediaConfig->getBaseTmpMediaPath();
    }

    //########################################

    /**
     * @param array $data
     * @param bool $save
     *
     * @return Mage_Catalog_Model_Product
     * @throws Exception
     */
    abstract public function process(array $data, $save = true);
}
