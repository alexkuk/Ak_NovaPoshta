<?php
class Ak_NovaPoshta_Model_Resource_City extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init('novaposhta/city', 'id');
    }
}
