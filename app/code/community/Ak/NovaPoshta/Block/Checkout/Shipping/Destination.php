<?php
class Ak_NovaPoshta_Block_Checkout_Shipping_Destination
    extends Mage_Core_Block_Template
{
    /**
     * @return Ak_NovaPoshta_Model_Warehouse|bool
     */
    public function getWarehouse()
    {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $warehouseId = $quote->getShippingAddress()->getData('warehouse_id');
        if ($warehouseId) {
            $warehouse = Mage::getModel('novaposhta/warehouse')->load($warehouseId);
            if ($warehouse->getId()) {
                return $warehouse;
            }
        }

        return false;
    }

    /**
     * @return bool|int
     */
    public function getCityId()
    {
        $cityId = (int) $this->getData('city_id');
        if (!$cityId) {
            $warehouse = $this->getWarehouse();
            if ($warehouse) {
                $cityId =  $warehouse->getCity()->getId();;
                $this->setData('city_id', $cityId);
            }
        }

        if ($cityId) {
            return $cityId;
        }

        return false;
    }

    /**
     * @return Ak_NovaPoshta_Model_Resource_City_Collection
     */
    public function getCities()
    {
        $collection = Mage::getResourceModel('novaposhta/city_collection');
        $collection->setOrder('name_ru');

        return $collection;
    }

    /**
     * @return Ak_NovaPoshta_Model_Resource_Warehouse_Collection|bool
     */
    public function getWarehouses()
    {
        if ($cityId = $this->getCityId()) {
            /** @var Ak_NovaPoshta_Model_Resource_Warehouse_Collection $collection */
            $collection = Mage::getResourceModel('novaposhta/warehouse_collection');
            $collection->addFieldToFilter('city_id', $cityId);
            $collection->setOrder('address_ru');

            return $collection;
        }

        return false;
    }
}