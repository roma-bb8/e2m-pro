<?php

class M2E_e2M_Helper_eBay_Account {

    const MODE_SANDBOX = 0;
    const MODE_PRODUCTION = 1;

    const PREFIX = M2E_e2M_Helper_Data::PREFIX . 'ebay/account/';

    const MODE = 'mode';
    const TOKEN = 'token';
    const EXPIRATION_TIME = 'expiration_time';
    const USER_ID = 'user_id';
    const SESSION_ID = 'session_id';

    //########################################

    /** @var Mage_Core_Model_Resource $resource */
    private $resource;

    /** @var string $coreConfigDataTableName */
    private $coreConfigDataTableName;

    private $data = array();

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
    public function isProduction() {
        return self::MODE_PRODUCTION === $this->getMode();
    }

    public function getToken() {
        return $this->data['token'];
    }

    public function getExpirationTime() {
        return $this->data['expiration_time'];
    }

    public function getUserId() {
        return $this->data['user_id'];
    }

    public function getSessionId() {
        return $this->data['session_id'];
    }

    public function setMode($mode) {
        $this->data['mode'] = $mode;
    }

    public function setToken($token) {
        $this->data['token'] = $token;
    }

    public function setExpirationTime($expirationTime) {
        $this->data['expiration_time'] = $expirationTime;
    }

    public function setUserId($userId) {
        $this->data['user_id'] = $userId;
    }

    public function setSessionId($sessionId) {
        $this->data['session_id'] = $sessionId;
    }

    public function setData($data) {

        isset($data['mode']) && $this->setMode($data['mode']);
        isset($data['token']) && $this->setToken($data['token']);
        isset($data['expiration_time']) && $this->setExpirationTime($data['expiration_time']);
        isset($data['user_id']) && $this->setUserId($data['user_id']);
        isset($data['session_id']) && $this->setSessionId($data['session_id']);
    }

    public function save() {

        $connWrite = $this->resource->getConnection('core_write');

        $connWrite->delete($this->coreConfigDataTableName, array('path IN (?)' => array(
            self::PREFIX . self::MODE,
            self::PREFIX . self::TOKEN,
            self::PREFIX . self::EXPIRATION_TIME,
            self::PREFIX . self::USER_ID,
            self::PREFIX . self::SESSION_ID
        )));

        $connWrite->insertMultiple($this->coreConfigDataTableName, array(
            array(
                'path' => self::PREFIX . self::MODE,
                'value' => $this->data['mode']
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
            ),
            array(
                'path' => self::PREFIX . self::SESSION_ID,
                'value' => $this->data['session_id']
            )
        ));

        return $this;
    }

    public function __construct() {

        $this->resource = Mage::getSingleton('core/resource');
        $this->coreConfigDataTableName = $this->resource->getTableName('core_config_data');

        //----------------------------------------

        $this->data = array(
            'mode' => 0,
            'token' => null,
            'expiration_time' => null,
            'user_id' => null,
            'session_id' => null
        );

        //----------------------------------------

        $data = $this->resource->getConnection('core_read')->select()
            ->from($this->coreConfigDataTableName)
            ->where('path IN (?)', array(
                self::PREFIX . self::MODE,
                self::PREFIX . self::TOKEN,
                self::PREFIX . self::EXPIRATION_TIME,
                self::PREFIX . self::USER_ID,
                self::PREFIX . self::SESSION_ID
            ))
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as $row) {
            switch ($row['path']) {
                case self::PREFIX . self::MODE:
                    $this->data['mode'] = (int)$row['value'];
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
                case self::PREFIX . self::SESSION_ID:
                    $this->data['session_id'] = $row['value'];
                    continue;
            }
        }
    }
}
