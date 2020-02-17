<?php

/**
 * Class M2E_e2M_Helper_cURL
 */
class M2E_e2M_Helper_cURL {

    /** @var string $url */
    private $url;

    /** @var array $headers */
    private $headers = array();

    /** @var array $body */
    private $body = array();

    //########################################

    /**
     * @param array $headers
     *
     * @return array
     */
    private function formatHeader(array $headers) {
        $queryParameters = array();
        foreach ($headers as $key => $value) {
            $queryParameters[] = "{$key}: {$value}";
        }
        return $queryParameters;
    }

    //########################################

    /**
     * @param string $url
     *
     * @return $this
     */
    public function setUrl($url) {
        $this->url = $url;

        return $this;
    }

    /**
     * @param array $headers
     *
     * @return $this
     */
    public function setHeaders(array $headers) {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @param string $body
     *
     * @return $this
     */
    public function setBody($body) {
        $this->body = $body;

        return $this;
    }

    //########################################

    /**
     * @return string|null
     */
    public function getResponse() {

        try {

            $cURL = curl_init();
            curl_setopt_array($cURL, array(
                CURLOPT_URL => $this->url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 15,
                CURLOPT_TIMEOUT => 600,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $this->body,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $this->formatHeader($this->headers),
            ));

            $response = curl_exec($cURL);
            list($header, $body) = explode("\r\n\r\n", $response, 2);

            curl_close($cURL);

            return $body;

        } catch (Exception $e) {
            Mage::helper('e2m')->logException($e);
        }

        return null;
    }
}
