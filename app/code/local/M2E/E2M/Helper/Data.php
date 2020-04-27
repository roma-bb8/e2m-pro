<?php

class M2E_E2M_Helper_Data extends Mage_Core_Helper_Abstract {

    const PREFIX = 'm2e/e2m/';

    //########################################

    /** @var int $maxUploadSize */
    private $maxUploadSize;

    //########################################

    /**
     * @param Exception $e
     */
    public function writeExceptionLog(Exception $e) {

        $type = get_class($e);
        $exceptionInfo = <<<EXCEPTION

-------------------------------- EXCEPTION INFO ----------------------------------
Type: {$type}
File: {$e->getFile()}
Line: {$e->getLine()}
Code: {$e->getCode()}
Message: {$e->getMessage()}
-------------------------------- STACK TRACE INFO --------------------------------
{$e->getTraceAsString()}

###################################################################################
EXCEPTION;

        Mage::log($exceptionInfo, Zend_Log::ERR, 'e2m.log', true);
    }

    //########################################

    /**
     * @return int
     */
    public function getMaxUploadSize() {

        if (!empty($this->maxUploadSize)) {
            return $this->maxUploadSize;
        }

        $maxUploadSize = Mage::helper('importexport')->getMaxUploadSize();
        if (empty($maxUploadSize)) {
            return $this->maxUploadSize = 2000000; // 2M
        }

        $lastMaxUploadSizeLetter = strtolower(substr($maxUploadSize, -1));
        $maxUploadSize = (int)$maxUploadSize;

        switch ($lastMaxUploadSizeLetter) {
            case 'g':
                $maxUploadSize *= 1024;
            // no break
            case 'm':
                $maxUploadSize *= 1024;
            // no break
            case 'k':
                $maxUploadSize *= 1024;
            // no break
        }

        if ($maxUploadSize <= 0) {
            return $this->maxUploadSize = 2000000; // 2M
        }

        return $this->maxUploadSize = ($maxUploadSize - 97152); // diff
    }

    //########################################

    /**
     * @param bool|int|float|string $value
     * @param bool|int|float|string $defaultValue
     *
     * @return string
     */
    public function getValue($value, $defaultValue) {

        if (empty($value)) {
            return $defaultValue;
        }

        $value = trim($value);
        $value = str_replace("\n", '', $value);

        $fp = fopen('php://memory', 'w+');
        fputcsv($fp, array($value));
        rewind($fp);
        $value = stream_get_contents($fp);
        fclose($fp);

        return trim($value, "\n");
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getCode($name) {

        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        $name = preg_replace('/[^0-9a-z]/i', '_', $name);
        $name = preg_replace('/_+/', '_', $name);
        $abc = 'abcdefghijklmnopqrstuvwxyz';
        if (preg_match('/^\d/', $name, $matches)) {
            $index = $matches[0];
            $name = $abc[$index] . '_' . $name;
        }

        return strtolower($name);
    }

    //########################################

    /**
     * @param string $file
     *
     * @return string
     * @throws Exception
     */
    public function getFullPath($file = '') {

        $prefixPath = Mage::getBaseDir('var') . DS . 'e2m' . DS;
        if (is_dir($prefixPath)) {
            return $prefixPath . $file;
        }

        if (!mkdir($prefixPath, 0755, true)) {
            throw new Exception('"e2m" folder not create.');
        }

        return $prefixPath . $file;
    }

    //########################################

    /**
     * @param string $file
     *
     * @return array
     * @throws Exception
     */
    public function getDataCSVFile($file) {

        $file = fopen($this->getFullPath($file), 'r');

        $header = fgetcsv($file, null, ',');

        $data = array();
        while ($values = fgetcsv($file, null, ',')) {
            $data[] = array_combine(array_values($header), array_values($values));
        }
        fclose($file);

        return $data;
    }

    //########################################

    /**
     * @param string $path
     * @param string $data
     * @param array $csvHeader
     * @param string $source
     */
    public function writeCSVFile($path, $data, $csvHeader, $source) {

        $i = 0;
        do {

            $i++;

            $file = "ebay_{$source}_inventory_part_{$i}.csv";
            if (!file_exists($path . $file)) {
                file_put_contents($path . $file, implode(',', $csvHeader) . PHP_EOL, LOCK_EX);

                break;
            }

            clearstatcache();

        } while (filesize($path . $file) > $this->getMaxUploadSize());

        file_put_contents($path . $file, $data . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
