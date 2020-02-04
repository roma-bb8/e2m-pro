<?php

class M2E_e2M_Model_Api_Ebay {

    private $sandbox = 'https://signin.sandbox.ebay.com/ws/eBayISAPI.dll?SignIn&runame=%s&SessID=%s&ruparams=VarA%%3D%s';
    private $url = 'https://api.sandbox.ebay.com/ws/api.dll';
    private $ruName = 'xxx';
    private $headers = array(
        'Content-Type' => 'text/xml',
        'Expect' => ' ',
        'X-EBAY-API-COMPATIBILITY-LEVEL' => 1073,
        'X-EBAY-API-SITEID' => 0,
        'X-EBAY-API-DEV-NAME' => 'xxx',
        'X-EBAY-API-APP-NAME' => 'xxx',
        'X-EBAY-API-CERT-NAME' => 'xxx',
        'X-EBAY-API-CALL-NAME' => ''
    );

    /**
     * @return string
     * @throws Exception
     */
    public function getSessionID() {

        $this->headers['X-EBAY-API-CALL-NAME'] = 'GetSessionID';
        $body = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<GetSessionIDRequest xmlns="urn:ebay:apis:eBLBaseComponents">
    <RuName>{$this->ruName}</RuName>
</GetSessionIDRequest>
XML;

        $cURL = $this->getCURL();

        $cURL->setUrl($this->url);
        $cURL->setHeader($this->headers);
        $cURL->setBody($body);

        $response = $cURL->getResponse();
        $dom = new DOMDocument();
        $dom->loadXML($response);

        if (!$dom->getElementsByTagName('SessionID')->count()) {
            throw new Exception('Not SessionID');
        }

        return $dom->getElementsByTagName('SessionID')->item(0)->nodeValue;
    }

    /**
     * @param $backURL
     * @param $sessionID
     *
     * @return string
     */
    public function getAuthURL($backURL, $sessionID) {
        return sprintf($this->sandbox, $this->ruName, $sessionID, $backURL);
    }

    /**
     * @param $sessionID
     *
     * @return array
     * @throws Exception
     */
    public function getTokenInfo($sessionID) {

        $this->headers['X-EBAY-API-CALL-NAME'] = 'FetchToken';
        $body = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<FetchTokenRequest xmlns="urn:ebay:apis:eBLBaseComponents">
    <SessionID>{$sessionID}</SessionID>
    <WarningLevel>High</WarningLevel>
</FetchTokenRequest>
XML;

        $cURL = $this->getCURL();

        $cURL->setUrl($this->url);
        $cURL->setHeader($this->headers);
        $cURL->setBody($body);

        $response = $cURL->getResponse();
        $dom = new DOMDocument();
        $dom->loadXML($response);

        if (!$dom->getElementsByTagName('eBayAuthToken')->count()) {
            throw new Exception('Not eBayAuthToken');
        }

        $token = $dom->getElementsByTagName('eBayAuthToken')->item(0)->nodeValue;

        return array(
            'token' => $token,
            'expiration_time' => $this->ebayTimeToString($dom->getElementsByTagName('HardExpirationTime')->item(0)->nodeValue),
            'user_id' => $this->getGetUser($token)
        );
    }

    public function ebayTimeToString($time) {
        return (string)$this->getEbayDateTimeObject($time)->format('Y-m-d H:i:s');
    }

    private function getEbayDateTimeObject($time) {
        $dateTime = null;

        if ($time instanceof DateTime) {
            $dateTime = clone $time;
            $dateTime->setTimezone(new DateTimeZone('UTC'));
        } else {
            is_int($time) && $time = '@' . $time;
            $dateTime = new DateTime($time, new DateTimeZone('UTC'));
        }

        if (is_null($dateTime)) {
            throw new Exception('eBay DateTime object is null');
        }

        return $dateTime;
    }

    /**
     * @param $token
     *
     * @return string
     * @throws Exception
     */
    public function getGetUser($token) {

        $this->headers['X-EBAY-API-CALL-NAME'] = 'GetUser';
        $body = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<GetUserRequest xmlns="urn:ebay:apis:eBLBaseComponents">
    <RequesterCredentials>
        <eBayAuthToken>{$token}</eBayAuthToken>
    </RequesterCredentials>
    <IncludeFeatureEligibility>true</IncludeFeatureEligibility>
</GetUserRequest>
XML;

        $cURL = $this->getCURL();

        $cURL->setUrl($this->url);
        $cURL->setHeader($this->headers);
        $cURL->setBody($body);

        $response = $cURL->getResponse();
        $dom = new DOMDocument();
        $dom->loadXML($response);

        if (!$dom->getElementsByTagName('UserID')->count()) {
            throw new Exception('Not UserID');
        }

        return $dom->getElementsByTagName('UserID')->item(0)->nodeValue;
    }

    /**
     * @param array $marketplaces
     * @param string $token
     *
     * @param DateTime $fromDateTime
     * @param DateTime $toDateTime
     *
     * @return bool
     * @throws Exception
     */
    public function loadInventory($token, $fromDateTime, $toDateTime) {
        $ii = 0;

        /** @var M2e_M2i_Helper_Full $full */
        $full = Mage::helper('m2i/Full');

        $resource = Mage::getSingleton('core/resource');
        $connWrite = $resource->getConnection('core_write');
        $inventoryTableName = $resource->getTableName('m2e_m2i_inventory');

        $i = 1;
        $this->headers['X-EBAY-API-CALL-NAME'] = 'GetSellerList';
        $next = true;

        do {
            $body = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<GetSellerListRequest xmlns="urn:ebay:apis:eBLBaseComponents">
    <RequesterCredentials>
        <eBayAuthToken>{$token}</eBayAuthToken>
    </RequesterCredentials>
    <DetailLevel>ReturnAll</DetailLevel>
    <GranularityLevel>Fine</GranularityLevel>
    <IncludeVariations>true</IncludeVariations>
    <StartTimeFrom>{$fromDateTime->format('Y-m-d H:i:s')}</StartTimeFrom>
    <StartTimeTo>{$toDateTime->format('Y-m-d H:i:s')}</StartTimeTo>
    <Pagination ComplexType="PaginationType">
        <EntriesPerPage>200</EntriesPerPage>
        <PageNumber>{$i}</PageNumber>
    </Pagination>
</GetSellerListRequest>
XML;
            $cURL = $this->getCURL();

            $cURL->setUrl($this->url);
            $cURL->setHeader($this->headers);
            $cURL->setBody($body);

            $response = $cURL->getResponse();
            $response = new SimpleXMLElement($response);
            $i++;

            if (!$response->ReturnedItemCountActual) {
                break;
            }

            if ((int)$response->ReturnedItemCountActual < 200) {
                $next = false;
            }

            if (empty($response->ItemArray)) {
                $next = false;
                break;
            }

            $items = array();
            foreach ($response->ItemArray->Item as $item) {
                $arr = $full->parseItem($item);
                $items[$arr['identifiers']['item_id']] = $arr;
            }

            $result = $connWrite->select()
                ->from($inventoryTableName, 'item_id')
                ->where('item_id IN (?)', array_keys($items))
                ->query();

            $itemIDs = array_column($result->fetchAll(), 'item_id');
            foreach ($itemIDs as $itemId) {
                $connWrite->update($inventoryTableName,
                    array('data' => json_encode($items[$itemId])),
                    array('item_id = ?' => $itemId)
                );

                unset($items[$itemId]);
            }

            foreach ($items as $item => $data) {
                $connWrite->insert($inventoryTableName, array(
                    'item_id' => $item,
                    'site' => $data['marketplace'],
                    'data' => json_encode($data)
                ));
            }

        } while ($next);

        return $ii;
    }

    //########################################

    /**
     * @return M2e_M2i_Helper_Data
     */
    public function getCURL() {
        return Mage::helper('m2i');
    }
}