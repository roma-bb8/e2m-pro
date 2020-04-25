<?php

class M2E_E2M_Helper_Config {

    const PREFIX = M2E_E2M_Helper_Data::PREFIX . 'setting/';

    //########################################

    /**
     * @param string $path
     * @param mixed $value
     * @param bool $cleanCache
     *
     * @return $this
     */
    public function set($path, $value, $cleanCache = false) {

        Mage::getModel('core/config')->saveConfig(
            $path,
            Mage::helper('core')->jsonEncode($value)
        );

        $cleanCache && Mage::getModel('core/config')->cleanCache();

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

            $value = Mage::app()->getStore()->getConfig($path);
            if (!$value) {
                return $default;
            }

        } catch (Mage_Core_Model_Store_Exception $e) {
            Mage::helper('e2m')->writeExceptionLog($e);

            return $default;
        }

        return Mage::helper('core')->jsonDecode($value);
    }
}
