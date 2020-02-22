<?php
/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * Class M2E_E2M_Model_Resource_Log
 */
class M2E_E2M_Model_Resource_Log extends Mage_Core_Model_Resource_Db_Abstract {

    /**
     * @inheritDoc
     */
    public function _construct() {
        $this->_init('e2m/log', 'id');
    }
}
