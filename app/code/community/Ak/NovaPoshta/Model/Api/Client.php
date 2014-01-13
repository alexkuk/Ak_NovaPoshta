<?php
class Ak_NovaPoshta_Model_Api_Client
{
    protected $_httpClient;

    const DELIVERY_TYPE_APARTMENT_APARTMENT = 1;
    const DELIVERY_TYPE_APARTMENT_WAREHOUSE = 2;
    const DELIVERY_TYPE_WAREHOUSE_APARTMENT = 3;
    const DELIVERY_TYPE_WAREHOUSE_WAREHOUSE = 4;

    const LOAD_TYPE_STANDARD   = 1;
    const LOAD_TYPE_SECURITIES = 4;

    /**
     * @return string
     */
    protected function _getApiUri()
    {
        return Mage::helper('novaposhta')->getStoreConfig('api_url');
    }

    /**
     * @return string
     */
    protected function _getApiKey()
    {
        return Mage::helper('novaposhta')->getStoreConfig('api_key');
    }

    /**
     * @return Zend_Http_Client
     */
    protected function _getHttpClient()
    {
        if (!$this->_httpClient) {
            $this->_httpClient = new Zend_Http_Client($this->_getApiUri());
        }

        return $this->_httpClient;
    }

    /**
     * @param array $array
     * @param SimpleXMLElement $element
     * @return SimpleXMLElement
     */
    protected function _buildXml(array $array, SimpleXMLElement $element = null)
    {
        if (is_null($element)) {
            $element = new SimpleXMLElement('<file/>');
            $element->addChild('auth', $this->_getApiKey());
        }

        foreach ($array as $key => $value) {
            if (!is_numeric($key)) {
                if (is_array($value)) {
                    $this->_buildXml($value, $element->addChild($key));
                } else {
                    $element->addChild($key, $value);
                }
            }
        }

        return $element;
    }

    /**
     * @param array $data
     * @return SimpleXMLElement
     */
    protected function _makeRequest(array $data)
    {
        /** @var Ak_NovaPoshta_Helper_Data $helper */
        $helper    = Mage::helper('novaposhta');
        $xmlString = $this->_buildXml($data)->asXML();

        $helper->log('Request XML:' . $xmlString);

        /** @var Zend_Http_Response $response */
        $response = $this->_getHttpClient()
            ->resetParameters(true)
            ->setRawData($xmlString)
            ->request(Zend_Http_Client::POST);

        $helper->log('Response status code:' . $response->getStatus());
        $helper->log('Response body:' . $response->getBody());

        $helper->log(print_r((array) new SimpleXMLElement($response->getBody()), true));

        if (200 != $response->getStatus()) {
            Mage::throwException('Server error, response status:' . $response->getStatus());
        }

        return new SimpleXMLElement($response->getBody());
    }

    /**
     * @return SimpleXMLElement
     */
    public function getCityWarehouses()
    {
        $responseXml = $this->_makeRequest(array(
            'citywarehouses' => null,
        ));

        return $responseXml->xpath('result/cities/city');
    }

    /**
     * @return SimpleXMLElement
     */
    public function getWarehouses()
    {
        $responseXml = $this->_makeRequest(array(
            'warenhouse' => null,
        ));

        return $responseXml->xpath('result/whs/warenhouse');
    }

    public function getShippingCost(
        Zend_Date $deliveryDate,
        Ak_NovaPoshta_Model_City $senderCity, Ak_NovaPoshta_Model_City $recipientCity,
        $packageWeight, $packageLength, $packageWidth, $packageHeight, $publicPrice,
        $deliveryType = self::DELIVERY_TYPE_WAREHOUSE_WAREHOUSE,
        $loadType = self::LOAD_TYPE_STANDARD,
        $floor = 0)
    {
        $response = $this->_makeRequest(array(
            'countPrice' => array(
                'date' => $deliveryDate->toString(Zend_Date::DATE_MEDIUM),
                'senderCity' => $senderCity->getData('name_ru'),
                'recipientCity' => $recipientCity->getData('name_ru'),
                'mass' => $packageWeight,
                'depth' => $packageLength,
                'widht' => $packageWidth,
                'height' => $packageHeight,
                'publicPrice' => $publicPrice,
                'deliveryType_id' => $deliveryType,
                'loadType_id' => $loadType,
                'floor_count' => $floor,
            )
        ));

        if (1 == (int) $response->error) {
            Mage::throwException('Novaposhta Api error');
        }

        return array (
            'delivery_date' => (string) $response->date,
            'cost' => (float) $response->cost,
        );
    }
}
