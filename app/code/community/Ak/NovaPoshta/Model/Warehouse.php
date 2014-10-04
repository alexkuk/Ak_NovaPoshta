<?php
class Ak_NovaPoshta_Model_Warehouse extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        $this->_init('novaposhta/warehouse');
    }

    /**
     * @return Ak_NovaPoshta_Model_City
     */
    public function getCity()
    {
        return Mage::getModel('novaposhta/city')->load($this->getData('city_id'));
    }
}
