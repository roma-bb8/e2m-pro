<?php

/**
 * Class M2E_e2M_Helper_eBay_Account
 */
class M2E_e2M_Helper_eBay_Account {

    const PREFIX = M2E_e2M_Helper_Data::PREFIX . 'ebay/account/';

    const MODE_PRODUCTION = 1;
    const MODE_SANDBOX = 2;

    const SESSION_ID = 'session_id';
    const MODE = 'mode';
    const TOKEN = 'token';
    const EXPIRATION_TIME = 'expiration_time';
    const USER_ID = 'user_id';

    //########################################

    /** @var Mage_Core_Model_Resource $resource */
    private $resource;

    /** @var string $coreConfigDataTableName */
    private $coreConfigDataTableName;

    private $data = array();

    //########################################

    /**
     * @param int $mode
     *
     * @return $this
     */
    public function setMode($mode) {
        $this->data['mode'] = $mode;

        return $this;
    }

    /**
     * @param string $sessionId
     *
     * @return $this
     */
    public function setSessionId($sessionId) {
        $this->data['session_id'] = $sessionId;

        return $this;
    }

    /**
     * @param string $token
     *
     * @return $this
     */
    public function setToken($token) {
        $this->data['token'] = $token;

        return $this;
    }

    /**
     * @param string $expirationTime
     *
     * @return $this
     */
    public function setExpirationTime($expirationTime) {
        $this->data['expiration_time'] = $expirationTime;

        return $this;
    }

    /**
     * @param int $userId
     *
     * @return $this
     */
    public function setUserId($userId) {
        $this->data['user_id'] = $userId;

        return $this;
    }

    //----------------------------------------

    /**
     * @param array $data
     *
     * @return $this
     */
    public function setData($data) {

        isset($data['mode']) && $this->setMode($data['mode']);
        isset($data['session_id']) && $this->setSessionId($data['session_id']);
        isset($data['token']) && $this->setToken($data['token']);
        isset($data['expiration_time']) && $this->setExpirationTime($data['expiration_time']);
        isset($data['user_id']) && $this->setUserId($data['user_id']);

        return $this;
    }

    //########################################

    /**
     * @return int
     */
    public function getMode() {
        return $this->data['mode'];
    }

    /**
     * @return bool
     */
    public function isProductionMode() {
        return self::MODE_PRODUCTION === $this->getMode();
    }

    /**
     * @return bool
     */
    public function isSandboxMode() {
        return self::MODE_SANDBOX === $this->getMode();
    }

    /**
     * @return string|null
     */
    public function getSessionId() {
        return $this->data['session_id'];
    }

    /**
     * @return string|null
     */
    public function getToken() {
        return $this->data['token'];
    }

    /**
     * @return string|null
     */
    public function getUserId() {
        return $this->data['user_id'];
    }

    /**
     * @return string
     */
    public function getAccountUrl() {

        $domain = 'ebay.com';
        if ($this->isSandboxMode()) {
            $domain = 'sandbox.' . $domain;
        }

        return 'http://' . $domain . '/' . (string)$this->getUserId();
    }

    //########################################

    /**
     * @return $this
     */
    public function save() {

        $connWrite = $this->resource->getConnection('core_write');

        $connWrite->delete($this->coreConfigDataTableName, array('path IN (?)' => array(
            self::PREFIX . self::MODE,
            self::PREFIX . self::SESSION_ID,
            self::PREFIX . self::TOKEN,
            self::PREFIX . self::EXPIRATION_TIME,
            self::PREFIX . self::USER_ID
        )));

        $connWrite->insertMultiple($this->coreConfigDataTableName, array(
            array(
                'path' => self::PREFIX . self::MODE,
                'value' => $this->data['mode']
            ),
            array(
                'path' => self::PREFIX . self::SESSION_ID,
                'value' => $this->data['session_id']
            ),
            array(
                'path' => self::PREFIX . self::TOKEN,
                'value' => $this->data['token']
            ),
            array(
                'path' => self::PREFIX . self::EXPIRATION_TIME,
                'value' => $this->data['expiration_time']
            ),
            array(
                'path' => self::PREFIX . self::USER_ID,
                'value' => $this->data['user_id']
            )
        ));

        return $this;
    }

    //########################################

    /**
     * M2E_e2M_Helper_eBay_Account constructor.
     */
    public function __construct() {

        $this->resource = Mage::getSingleton('core/resource');
        $this->coreConfigDataTableName = $this->resource->getTableName('core_config_data');

        //----------------------------------------

        $this->data = array(
            'mode' => self::MODE_SANDBOX,
            'session_id' => null,
            'token' => null,
            'expiration_time' => null,
            'user_id' => null
        );

        //----------------------------------------

        $data = $this->resource->getConnection('core_read')->select()
            ->from($this->coreConfigDataTableName)
            ->where('path IN (?)', array(
                self::PREFIX . self::MODE,
                self::PREFIX . self::SESSION_ID,
                self::PREFIX . self::TOKEN,
                self::PREFIX . self::EXPIRATION_TIME,
                self::PREFIX . self::USER_ID
            ))
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as $row) {
            switch ($row['path']) {
                case self::PREFIX . self::MODE:
                    $this->data['mode'] = (int)$row['value'];
                    continue;
                case self::PREFIX . self::SESSION_ID:
                    $this->data['session_id'] = $row['value'];
                    continue;
                case self::PREFIX . self::TOKEN:
                    $this->data['token'] = $row['value'];
                    continue;
                case self::PREFIX . self::EXPIRATION_TIME:
                    $this->data['expiration_time'] = $row['value'];
                    continue;
                case self::PREFIX . self::USER_ID:
                    $this->data['user_id'] = $row['value'];
                    continue;
            }
        }
    }
}
