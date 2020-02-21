<?php

/**
 * Class M2E_e2M_Model_Log
 */
class M2E_e2M_Model_Log extends Mage_Core_Model_Abstract {

    /**
     * @inheritDoc
     */
    public function _construct() {
        parent::_construct();
        $this->_init('e2m/log');
    }
}
