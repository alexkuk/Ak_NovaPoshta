<?php
class Ak_NovaPoshta_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_logFile = 'novaposhta.log';

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
}