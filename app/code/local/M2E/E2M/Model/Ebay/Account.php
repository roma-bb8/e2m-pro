<?php
/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * Class M2E_E2M_Model_Ebay_Account
 */
class M2E_E2M_Model_Ebay_Account extends M2E_E2M_Model_Config {

    const PREFIX = parent::PREFIX . '/ebay/account';

    //########################################

    const MODE_PRODUCTION = 1;
    const MODE_SANDBOX = 2;

    const MODE = 'mode';
    const SESSION_ID = 'session_id';
    const TOKEN = 'token';
    const EXPIRATION_TIME = 'expiration_time';
    const USER_ID = 'user_id';

    //########################################

    /**
     * @return string
     */
    public function getAccountUrl() {

        $domain = 'ebay.com';
        if (self::MODE_SANDBOX === (int)$this->get(self::MODE)) {
            $domain = 'sandbox.' . $domain;
        }

        return 'https://' . $domain . '/' . (string)$this->get(self::USER_ID);
    }
}
