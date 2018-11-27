<?php

namespace BrunoGoossens\LTI;

use Symfony\Component\HttpFoundation\Request;

class LTIProvider
{
    private $key;
    private $secret;

    public function __construct($key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
    }

    public function validateRequest()
    {
        if (!$this->validMessageType()) {
            throw new \Exception("Invalid LTI message type");
        }
        if (!$this->validVersion()) {
            throw new \Exception("Invalid LTI version");
        }
        if (!$this->validNonce()) {
            throw new \Exception("Invalid oauth nonce value");
        }
        if (!$this->validTimestamp()) {
            throw new \Exception("Invalid timestamp");
        }
        if (!$this->validConsumer()) {
            throw new \Exception("Invalid OAuth consumor key or secret");
        }
        if (!$this->validSignature()) {
            throw new \Exception("Invalid signature");
        }
    }

    public function getRoles()
    {
        $roles = $_REQUEST['roles'];
        if (strpos($roles, ',') === false) {
            if (!empty($roles)) {
                return [$roles];
            } else {
                return [];
            }
        } else {
            return explode(',', $roles);
        }
    }

    private function validConsumer()
    {
        if ($_REQUEST['oauth_consumer_key'] == $this->key) {
            return true;
        }

        return false;
    }

    private function validMessageType()
    {
        if ($_REQUEST['lti_message_type'] !== 'basic-lti-launch-request') {
            return false;
        }
        return true;
    }

    private function validVersion()
    {
        if ($_REQUEST['lti_version'] !== 'LTI-1p0') {
            return false;
        }
        return true;
    }

    private function validNonce()
    {
        $nonce = $_REQUEST['oauth_nonce'];
        // @TODO: check if $nonce is unique for the past 90 minutes;
        // for now just return true
        return true;
    }

    private function validTimestamp()
    {
        $timestamp = $_REQUEST['oauth_timestamp'];
        $requestDate = new \DateTime();
        $requestDate->setTimestamp($timestamp);
        $validDate = new \DateTime();
        $validDate->modify('-10 minutes');

        if ($requestDate < $validDate) {
            return false;
        }
        return true;
    }

    private function validSignature()
    {
        $consumerKey = $_REQUEST['oauth_consumer_key'];
        // $signatureMethod = $request->request->get('oauth_signature_method');
        $requestSignature = $_REQUEST['oauth_signature'];

        // generate singature
        $params = $_REQUEST;
        unset($params['oauth_signature']);
        ksort($params);
        $sortedParamsByKeyEncodedForm = array();
        foreach ($params as $key => $value) {
            $sortedParamsByKeyEncodedForm[] = rawurlencode($key) . '=' . rawurlencode($value);
        }
        $strParams = implode('&', $sortedParamsByKeyEncodedForm);
        $method = strtoupper($_SERVER['REQUEST_METHOD']);
        $url = ($_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . strtok($_SERVER["REQUEST_URI"], '?');

        $base = $method . '&' . rawurlencode($url) . '&' . rawurlencode($strParams);
        $key = rawurlencode($this->secret) . '&';
        $signature = base64_encode(hash_hmac('SHA1', $base, $key, 1));

        if ($signature != $requestSignature) {
            return false;
        }

        return true;
    }

    private function makeSignature($url, $method, $params)
    {
        ksort($params);
        $sortedParamsByKeyEncodedForm = array();
        foreach ($params as $key => $value) {
            $sortedParamsByKeyEncodedForm[] = rawurlencode($key) . '=' . rawurlencode($value);
        }
        $strParams = implode('&', $sortedParamsByKeyEncodedForm);
        $method = strtoupper($method);

        $base = $method . '&' . rawurlencode($url) . '&' . rawurlencode($strParams);
        $key = rawurlencode($this->secret) . '&';
        return base64_encode(hash_hmac('SHA1', $base, $key, 1));
    }

    public function postScore($outcome_service_url, $sourcedid, $grade)
    {
        $xml = str_replace(
            array('SOURCEDID', 'GRADE', 'MESSAGE'),
            array($sourcedid, $grade, uniqid()),
            trim(file_get_contents(__DIR__ . '/templates/replaceResult.xml'))
        );

        $this->doXMLRequest($outcome_service_url, $xml);
    }

    public function readScore($outcome_service_url, $sourcedid)
    {
        $xml = str_replace(
            array('SOURCEDID', 'MESSAGE'),
            array($sourcedid, uniqid()),
            trim(file_get_contents(__DIR__ . '/templates/readResult.xml'))
        );

        $this->doXMLRequest($outcome_service_url, $xml);
    }

    private function doXMLRequest($outcome_service_url, $xml)
    {
        $method = 'POST';
        $oauth_params = array(
            'oauth_version' => '1.0',
            'oauth_nonce' => md5(microtime() . mt_rand()),
            'oauth_timestamp' => time(),
            'oauth_consumer_key' => $this->key,
            'oauth_body_hash' => base64_encode(sha1($xml, true)),
            'oauth_signature_method' => 'HMAC-SHA1',
        );

        $headers[0] = 'Authorization: OAuth realm=""';
        foreach ($oauth_params as $key => $param) {
            $headers[0] .= ',' . rawurlencode($key) . '="' . rawurlencode($param) . '"';
        }
        $headers[0] .= ',oauth_signature="' . rawurlencode($this->makeSignature($outcome_service_url, $method, $oauth_params)) . '"';
        $headers[1] = 'Content-Type: application/xml';
        $headers[2] = "";

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $outcome_service_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);
        curl_close($ch);

        // var_dump($response);
        // exit;
    }
}
