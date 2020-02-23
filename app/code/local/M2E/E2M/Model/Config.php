<?php
/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * Class M2E_E2M_Model_Config
 */
class M2E_E2M_Model_Config {

    const PREFIX = '/m2e/e2m';

    //########################################

    /** @var Mage_Core_Model_Resource $resource */
    protected $resource;

    /** @var string $coreConfigDataTableName */
    protected $coreConfigDataTableName;

    /** @var Varien_Db_Adapter_Interface $connWrite */
    protected $connWrite;

    /** @var Varien_Db_Adapter_Interface $connRead */
    protected $connRead;

    /** @var Mage_Core_Helper_Data $coreHelper */
    protected $coreHelper;

    //########################################

    /** @var array $data */
    protected $data = array();

    //########################################

    /**
     * @param $key
     *
     * @return mixed
     */
    private function prepareKey($key) {

        $lastSlash = substr($key, -1);
        $lastSlash === '/' && $key = substr($key, 0, -1);

        //----------------------------------------

        $firstSlash = substr($key, 1);
        $firstSlash === '/' && $key = substr($key, 1);

        //----------------------------------------

        return self::PREFIX . '/' . $key . '/';
    }

    //########################################

    /**
     * @param string $key
     * @param mixed $value
     * @param bool $autoSave
     *
     * @return $this
     */
    public function set($key, $value, $autoSave = true) {

        $key = $this->prepareKey($key);

        //----------------------------------------

        $oldValue = null;
        isset($this->data[$key]) && $oldValue = $this->data[$key];
        if ($oldValue === $value) {
            return $this;
        }

        //----------------------------------------

        $this->data[$key] = $value;
        if (!$autoSave) {
            return $this;
        }

        //----------------------------------------

        $this->connWrite->update($this->coreConfigDataTableName, array(
            'value' => $this->coreHelper->jsonEncode($this->data[$key])
        ), array('path = ?' => $key));

        //----------------------------------------

        return $this;
    }

    //----------------------------------------

    /**
     * @param string $key
     * @param bool $reload
     *
     * @return mixed
     */
    public function get($key, $reload = false) {

        $key = $this->prepareKey($key);

        //----------------------------------------

        if (isset($this->data[$key]) && !$reload) {
            return $this->data[$key];
        }

        //----------------------------------------

        $value = $this->connRead->select()->from($this->coreConfigDataTableName, 'value')
            ->where('path = ?', $key)->limit(1)->query()->fetchColumn();

        return $this->data[$key] = $this->coreHelper->jsonDecode($value);
    }

    //########################################

    /**
     * @throws Exception
     */
    public function save() {
        foreach ($this->data as $key => $value) {
            $this->connWrite->update($this->coreConfigDataTableName, array(
                'value' => $this->coreHelper->jsonEncode($value)
            ), array('path = ?' => $key));
        }
    }

    //########################################

    /**
     * M2E_E2M_Model_Config constructor.
     */
    public function __construct() {

        $this->resource = Mage::getSingleton('core/resource');
        $this->coreHelper = Mage::helper('core');

        //----------------------------------------

        $this->coreConfigDataTableName = $this->resource->getTableName('core_config_data');

        //----------------------------------------

        $this->connWrite = $this->resource->getConnection('core_write');
        $this->connRead = $this->resource->getConnection('core_read');
    }
}
