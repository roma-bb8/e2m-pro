<?php

class M2E_E2M_Model_Lock_Item {

    const MAX_UPDATE = 1800000; // 30 minutes

    //########################################

    /** @var string $lockItemName */
    private $lockItemName;

    /** @var resource $lockItemFile */
    private $lockItemFile;

    //########################################

    /**
     * @return bool
     */
    public function lock() {
        return flock($this->lockItemFile, LOCK_EX);
    }

    /**
     * @return bool
     */
    public function unlock() {
        return flock($this->lockItemFile, LOCK_UN);
    }

    //########################################

    /**
     * @return bool
     */
    public function isLocked() {

        if (flock($this->lockItemFile, LOCK_EX | LOCK_NB)) {
            flock($this->lockItemFile, LOCK_UN);

            return false;
        }

        clearstatcache();
        $diff = (time() - filemtime($this->lockItemName));
        if (self::MAX_UPDATE < $diff) {
            flock($this->lockItemFile, LOCK_UN);

            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function activate() {

        rewind($this->lockItemFile);
        if (!fwrite($this->lockItemFile, date('r'))) {
            throw new Exception('Lock item is delete.');
        }

        return true;
    }

    /**
     * @return bool
     */
    public function lockAndActivate() {

        if (!$this->lock()) {
            return false;
        }

        return $this->activate();
    }

    //########################################

    public function __destruct() {
        fclose($this->lockItemFile);
    }

    /**
     * M2E_E2M_Model_Cron_Lock_Item constructor.
     *
     * @param array $args
     *
     * @throws Exception
     */
    public function __construct($args) {

        $locksPath = Mage::getConfig()->getVarDir('locks');
        $this->lockItemName = $locksPath . DS . strtolower($args[0]) . '.lock';
        $this->lockItemFile = fopen($this->lockItemName, is_file($this->lockItemName) ? 'w' : 'x');
        if (!$this->lockItemFile) {
            throw new Exception('Not create lock file.');
        }
    }
}
