<?php

class M2E_E2M_Helper_Config {

    const PREFIX = M2E_E2M_Helper_Data::PREFIX . 'setting/';

    //########################################

    /**
     * @param string $path
     * @param mixed $value
     *
     * @return $this
     */
    public function set($path, $value) {

        $coreConfigData = Mage::getSingleton('core/resource')->getTableName('core_config_data');

        $id = Mage::getSingleton('core/resource')->getConnection('core_read')->select()
            ->from($coreConfigData, 'config_id')
            ->where('scope = ?', 'default')
            ->where('scope_id = ?', 0)
            ->where('path = ?', $path)->limit(1)->query()->fetchColumn();

        if ($id) {
            Mage::getSingleton('core/resource')->getConnection('core_write')->update($coreConfigData, array(
                'value' => Mage::helper('core')->jsonEncode($value)
            ), array('config_id = ?' => $id));
        } else {
            Mage::getSingleton('core/resource')->getConnection('core_write')->insert($coreConfigData, array(
                'scope' => 'default',
                'scope_id' => 0,
                'path' => $path,
                'value' => Mage::helper('core')->jsonEncode($value)
            ));
        }

        return $this;
    }

    /**
     * @param string $path
     * @param mixed|null $default
     *
     * @return mixed|null
     */
    public function get($path, $default = null) {

        try {

            $value = Mage::getSingleton('core/resource')->getConnection('core_read')->select()
                ->from(Mage::getSingleton('core/resource')->getTableName('core_config_data'), 'value')
                ->where('scope = ?', 'default')
                ->where('scope_id = ?', 0)
                ->where('path = ?', $path)->limit(1)->query()->fetchColumn();
            if (!$value) {
                return $default;
            }

        } catch (Exception $e) {
            Mage::helper('e2m')->writeExceptionLog($e);

            return $default;
        }

        return Mage::helper('core')->jsonDecode($value);
    }
}
