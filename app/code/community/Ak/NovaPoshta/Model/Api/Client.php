<?php
class Ak_NovaPoshta_Model_Api_Client
{
    protected $_httpClient;
    protected $_apiKey;

    public function __construct(array $apiSettings)
    {
        $this->_getHttpClient()->setUri($apiSettings[0]);
        $this->_apiKey = $apiSettings[1];
    }

    /**
     * @return Zend_Http_Client
     */
    protected function _getHttpClient()
    {
        if (!$this->_httpClient) {
            $this->_httpClient = new Zend_Http_Client();
            $this->_httpClient->setMethod(Zend_Http_Client::POST)
                ->setHeaders('Content-Type', 'text/xml');
        }
        return $this->_httpClient;
    }

    /**
     * @param SimpleXMLElement $xml
     *
     * @return bool|SimpleXMLElement
     */
    protected function _sendRequest(SimpleXMLElement $xml)
    {
        try {
            $xml = $xml->asXML();

            Mage::helper('novaposhta')->log('Request XML: ' . $xml);

            $this->_getHttpClient()->setRawData($xml);
            $result = $this->_getHttpClient()->request();
            $result = $result->getBody();
            if (empty($result)) {
                return false;
            }

            Mage::helper('novaposhta')->log('Response XML: ' . $result);

            return new SimpleXMLElement($result);
        }
        catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('novaposhta')->log("Exception: \n" . $e->__toString());
            return false;
        }
    }

    /**
     * @return array|SimpleXMLElement
     */
    public function getCityWarehouses()
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><file/>');
        $xml->addChild('auth', $this->_apiKey);
        $xml->addChild('citywarehouses');

        $responseXml = $this->_sendRequest($xml);
        if (!$responseXml) {
            return array();
        }

        try {
            return $responseXml->result->cities->city;
        }
        catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('novaposhta')->log("Exception: \n" . $e->__toString());
            return array();
        }
    }

    /**
     * @return array|SimpleXMLElement
     */
    public function getWarehouses()
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><file/>');
        $xml->addChild('auth', $this->_apiKey);
        $xml->addChild('warenhouse');

        $responseXml = $this->_sendRequest($xml);
        if (!$responseXml) {
            return array();
        }

        try {
            return $responseXml->result->whs->warenhouse;
        }
        catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('novaposhta')->log("Exception: \n" . $e->__toString());
            return array();
        }
    }
}
