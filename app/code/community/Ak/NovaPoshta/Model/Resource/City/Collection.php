<?php
class Ak_NovaPoshta_Model_Resource_City_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('novaposhta/city');
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->setOrder('name_ru', Varien_Data_Collection_Db::SORT_ORDER_ASC)->_toOptionArray('id', 'name_ru');
    }
}
