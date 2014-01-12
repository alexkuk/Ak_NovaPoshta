<?php
class Ak_NovaPoshta_Model_Observer
{
    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function loadAddressCollectionData(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Resource_Quote_Address_Collection $addressCollection */
        $addressCollection = $observer->getData('quote_address_collection');

        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
        $select = $connection->select();
        $select->from($resource->getTableName('novaposhta_quote_address'));
        $select->where('address_id IN (?)', $addressCollection->getAllIds());

        foreach ($connection->fetchAll($select) as $row) {
            $addressCollection->getItemById($row['address_id'])->setData('warehouse_id', $row['warehouse_id']);
        }

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function loadAddressData(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Quote_Address $address */
        $address = $observer->getData('quote_address');
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
        $select = $connection->select();
        $select->from($resource->getTableName('novaposhta_quote_address'), 'warehouse_id');
        $select->where('address_id = ?', $address->getId());

        $address->setData('warehouse_id', $connection->fetchOne($select));

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function saveAddressData(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Quote_Address $address */
        $address = $observer->getData('quote_address');
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
        $connection->insertOnDuplicate($resource->getTableName('novaposhta_quote_address'), array(
            'address_id' => $address->getId(),
            'warehouse_id' => $address->getData('warehouse_id'),
        ));

        return $this;
    }
}