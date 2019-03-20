<?php

class curlRequest {

    protected $apiURL;
    protected $apiToken;

    public function __construct() {

    }

    protected function createApiUrl($endpoint) {

        $url = $this->apiURL . $endpoint;

        return $url;
     }

    protected function request($method, $endpoint, $data = [], $options = array()) {

        $defaultOptions = array(
            'headers' => array('Accept: application/json')
        );

        $options = array_merge($defaultOptions, $options);

        $url = $this->createApiUrl($endpoint, $method, $data);

        $ch = curl_init($url);

        $this->setCurlDefaultOptions($ch, $options['headers']);

        if ($method == 'post') {
            $this->setCurlPostOptions($ch, $data);
        }

        $response = curl_exec($ch);

        $error = curl_errno($ch);
        if ($error) {
            $this->throwCurlError($error);
        } else {
            return $this->extractResponse($response, $ch);
        }
    }

    private function extractResponse($response, &$curlHandle) {

        $parsed = json_decode($response, true);
        $curl_info = curl_getinfo($curlHandle);
        $status = $curl_info['http_code'];

        return array('status' => $status, 'data' => $parsed);
    }

    private function setCurlDefaultOptions(&$curlHandle, $headers = array()) {

        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $this->translateAssocArrayHeaders($headers));
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
    }

    private function setCurlPostOptions(&$curlHandle, $data) {

        curl_setopt($curlHandle, CURLOPT_POST, 1);
        if (count($data) > 0) {
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $data);
        }
    }

    private function throwCurlError($error) {

        $message = curl_strerror($error);
        throw new Exception('cURL failure #' . $error . ': ' . $message);
    }

    private function translateAssocArrayHeaders($headers) {

        $newHeaders = array();
        foreach($headers as $header=>$value) {
            $newHeaders[] = $header . ':' . $value;
        }

        return $newHeaders;
    }
}