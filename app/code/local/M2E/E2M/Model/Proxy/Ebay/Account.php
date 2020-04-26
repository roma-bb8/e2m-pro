<?php

class M2E_E2M_Model_Proxy_Ebay_Account {

    const XML_PATH_EBAY_ACCOUNT_ID = M2E_E2M_Helper_Data::PREFIX . 'ebay/account/id';

    //########################################

    /** @var Ess_M2ePro_Model_Ebay_Account $eBayAccount */
    private $eBayAccount;

    //########################################

    public function getToken() {
        return $this->eBayAccount->getServerHash();
    }

    public function isSandboxMode() {
        return $this->eBayAccount->isModeSandbox();
    }

    public function getUserId() {
        return $this->eBayAccount->getUserId();
    }

    public function getAccountUrl() {

        $domain = 'ebay.com';
        $this->isSandboxMode() && $domain = 'sandbox.' . $domain;

        return sprintf('https://%s/%s', $domain, $this->getUserId());
    }

    //########################################

    public function __construct() {

        $this->eBayAccount = Mage::getModel('M2ePro/Ebay_Account');
        $id = Mage::helper('e2m/Config')->get(self::XML_PATH_EBAY_ACCOUNT_ID);
        if (!empty($id)) {
            $this->eBayAccount->load($id);
        }
    }
}
