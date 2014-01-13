<?php
class Ak_NovaPoshta_Helper_Data extends Mage_Core_Helper_Data
{
    protected $_logFile = 'novaposhta.log';
    /** @var  Ak_NovaPoshta_Model_Resource_City */
    protected $_senderCity;

    /**
     * @return Ak_NovaPoshta_Model_Api_Client
     */
    public function getApi()
    {
        return Mage::getSingleton('novaposhta/api_client');
    }

    /**
     * @param $string
     *
     * @return Ak_NovaPoshta_Helper_Data
     */
    public function log($string)
    {
        if ($this->getStoreConfig('enable_log')) {
            Mage::log($string, null, $this->_logFile, true);
        }
        return $this;
    }

    /**
     * @param string $key
     * @param null $storeId
     *
     * @return mixed
     */
    public function getStoreConfig($key, $storeId = null)
    {
        return Mage::getStoreConfig("carriers/novaposhta/$key", $storeId);
    }

    /**
     * @return Ak_NovaPoshta_Model_City
     */
    public function getSenderCity()
    {
        if (is_null($this->_senderCity)) {
            $this->_senderCity = Mage::getModel('novaposhta/city');
            $this->_senderCity->load($this->getStoreConfig('sender_city'));
            if (!$this->_senderCity->getId()) {
                Mage::throwException($this->__('Store city not defined.'));
            }
        }

        return $this->_senderCity;
    }

    /**
     * @return Zend_Date
     */
    public function getDeliveryDate()
    {
        $date = Zend_Date::now();
        $date->addDay(intval($this->getStoreConfig('shipping_offset')));

        return $date;
    }

    /**
     * @return float
     */
    public function getDefaultPackageWeight()
    {
        return (float) $this->getStoreConfig('default_weight');
    }

    /**
     * @return int
     */
    public function getDefaultPackageLength()
    {
        return (int) $this->getStoreConfig('default_length');
    }

    /**
     * @return int
     */
    public function getDefaultPackageWidth()
    {
        return (int) $this->getStoreConfig('default_width');
    }

    /**
     * @return int
     */
    public function getDefaultPackageHeight()
    {
        return (int) $this->getStoreConfig('default_height');
    }

    /**
     * @param $destinationWarehouseId
     * @return array
     */
    public function getShippingCost($destinationWarehouseId)
    {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote                = Mage::getSingleton('checkout/session')->getQuote();
        $destinationWarehouse = Mage::getModel('novaposhta/warehouse')->load($destinationWarehouseId);
        $senderCity           = $this->getSenderCity();
        $destinationCity      = $destinationWarehouse->getCity();
        $deliveryDate         = $this->getDeliveryDate();

        $result = $this->getApi()->getShippingCost($deliveryDate, $senderCity, $destinationCity,
            $this->getDefaultPackageWeight(),
            $this->getDefaultPackageLength(),
            $this->getDefaultPackageWidth(),
            $this->getDefaultPackageHeight(),
            $quote->getSubtotal()
        );

        return $result;
    }
}