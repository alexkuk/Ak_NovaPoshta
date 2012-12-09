<?php
class Ak_NovaPoshta_Model_Import
{
    /** @var int Number of objects to save in one mysql statement when saving cities */
    protected $_bulkSizeCity = 200;

    /**
     * with higher value I got segfault error (see http://framework.zend.com/issues/browse/ZF-11249)
     * @var int Number of objects to save in one mysql statement when saving warehouses
     */
    protected $_bulkSizeWarehouse = 35;

    protected $_exisitngCities;

    protected $_exisitngWarehouses;

    protected $_dataMapCity = array(
        'id' => 'id',
        'nameRu' => 'name_ru',
        'nameUkr' => 'name_ua'
    );

    protected $_dataMapWarehouse = array(
        'wareId' => 'id',
        'city_id' => 'city_id',
        'address' => 'address_ua',
        'addressRu' => 'address_ru',
        'phone' => 'phone',
        'weekday_work_hours' => 'weekday_work_hours',
        'weekday_reseiving_hours' => 'weekday_reseiving_hours',
        'weekday_delivery_hours' => 'weekday_delivery_hours',
        'saturday_work_hours' => 'saturday_work_hours',
        'saturday_reseiving_hours' => 'saturday_reseiving_hours',
        'saturday_delivery_hours' => 'saturday_delivery_hours',
        'max_weight_allowed' => 'max_weight_allowed',
        'x' => 'longitude',
        'y' => 'latitude',
        'number' => 'number_in_city'
    );

    /**
     * @throws Exception
     * @return Ak_NovaPoshta_Model_Import
     */
    public function runWarehouseAndCityMassImport()
    {
        $apiKey = Mage::helper('novaposhta')->getStoreConfig('api_key');
        $apiUrl = Mage::helper('novaposhta')->getStoreConfig('api_url');
        if (!$apiKey || !$apiUrl) {
            Mage::helper('novaposhta')->log('No API key or API URL configured');
            throw new Exception('No API key or API URL configured');
        }

        try {
            /** @var $apiClient Ak_NovaPoshta_Model_Api_Client */
            $apiClient = Mage::getModel('novaposhta/api_client', array($apiUrl, $apiKey));

            Mage::helper('novaposhta')->log('Start city import');
            $cities = $apiClient->getCityWarehouses();
            $this->_importCities($cities);
            Mage::helper('novaposhta')->log('End city import');

            Mage::helper('novaposhta')->log('Start warehouse import');
            $warehouses = $apiClient->getWarehouses();
            $this->_importWarehouses($warehouses);
            Mage::helper('novaposhta')->log('End warehouse import');
        }
        catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('novaposhta')->log("Exception: \n" . $e->__toString());
            throw $e;
        }

        return $this;
    }

    /**
     * @param SimpleXMLElement $cities
     *
     * @throws Exception
     * @return bool
     */
    protected function _importCities(SimpleXMLElement $cities)
    {
        if (empty($cities)) {
            Mage::helper('novaposhta')->log('No city with warehouses received');
            throw new Exception('No city with warehouses received');
        }

        $cities = $this->_applyMap($cities, $this->_dataMapCity);

        if (count($cities) > 0) {
            $cities = array_chunk($cities, $this->_bulkSizeCity);
            foreach ($cities as $chunk) {
                $sql = 'INSERT INTO `novaposhta_city` (' . implode(', ', array_keys($chunk[0])) . ') VALUES ';
                foreach ($chunk as $cityToInsert) {
                    $sql .= '("' . implode('", "', $cityToInsert) . '"), ';
                }
                $sql = trim($sql, ', ');
                $sql .= ' ON DUPLICATE KEY UPDATE ';
                foreach (array_keys($chunk[0]) as $field) {
                    $sql .= "$field = VALUES($field), ";
                }
                $sql = trim($sql, ', ');
                $this->_getConnection()->query($sql);
            }
        }

        return true;
    }

    /**
     * @return array
     */
    protected function &_getExistingCities()
    {
        if (!$this->_exisitngCities) {
            $existingCitiesTemp = Mage::getResourceModel('novaposhta/city_collection')->getSelect();
            $existingCitiesTemp = $this->_getConnection()->query($existingCitiesTemp)->fetchAll();
            if (empty($existingCitiesTemp)) {
                $existingCitiesTemp = array();
            }

            $this->_exisitngCities = array();
            foreach ($existingCitiesTemp as $existingCity) {
                $this->_exisitngCities[$existingCity['id']] = $existingCity;
            }

            unset($existingCitiesTemp);
        }
        return $this->_exisitngCities;
    }

    /**
     * @param array $existingCity
     * @param array $city
     *
     * @return bool
     */
    protected function _isCityChanged(array $existingCity, array $city)
    {
        foreach ($existingCity as $key => $value) {
            if (isset($city[$key]) && $city[$key] != $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param SimpleXMLElement $apiObjects
     * @param                  $map
     *
     * @return array
     */
    protected function _applyMap(SimpleXMLElement $apiObjects, $map)
    {
        $resultingArray = array();
        $idKey = array_search('id', $map);
        foreach ($apiObjects as $apiObject) {
            $id = (string)$apiObject->$idKey;
            $resultingArray[$id] = array();
            foreach ($apiObject as $apiKey => $value) {
                if (!isset($map[$apiKey])) {
                    continue;
                }
                $resultingArray[$id][$map[$apiKey]] = addcslashes((string)$value, "\000\n\r\\'\"\032");
            }
        }

        return $resultingArray;
    }

    /**
     * @return Varien_Db_Adapter_Interface
     */
    protected function _getConnection()
    {
        return Mage::getSingleton('core/resource')->getConnection('core_write');
    }

    /**
     * @param SimpleXMLElement $warehouses
     *
     * @throws Exception
     * @return bool
     */
    protected function _importWarehouses(SimpleXMLElement $warehouses)
    {
        if (empty($warehouses)) {
            Mage::helper('novaposhta')->log('No warehouses received');
            throw new Exception('No warehouses received');
        }

        $warehouses = $this->_applyMap($warehouses, $this->_dataMapWarehouse);
        $existingWarehouses = $this->_getExistingWarehouses();
        $warehousesToDelete = array_diff(array_keys($existingWarehouses), array_keys($warehouses));

        if (count($warehousesToDelete) > 0) {
            $warehousesToDelete = implode(', ', $warehousesToDelete);
            $sql = "DELETE FROM `novaposhta_warehouse` WHERE `id` IN ($warehousesToDelete)";
            $this->_getConnection()->query($sql);
            Mage::helper('novaposhta')->log("Warehouses deleted: $warehousesToDelete");
        }

        if (count($warehouses) > 0) {
            $warehouses = array_chunk($warehouses, $this->_bulkSizeWarehouse);
            foreach ($warehouses as &$chunk) {
                $sql = 'INSERT INTO `novaposhta_warehouse` (' . implode(', ', array_keys($chunk[0])) . ') VALUES ';
                foreach ($chunk as $warehouseToInsert) {
                    $sql .= '("' . implode('", "', $warehouseToInsert) . '"), ';
                }
                $sql = trim($sql, ', ');
                $sql .= ' ON DUPLICATE KEY UPDATE ';
                foreach (array_keys($chunk[0]) as $field) {
                    $sql .= "$field = VALUES($field), ";
                }
                $sql = trim($sql, ', ');
                Mage::helper('novaposhta')->log($sql);
                $this->_getConnection()->query($sql);
            }
        }

        return true;
    }

    /**
     * @return array
     */
    protected function &_getExistingWarehouses()
    {
        if (!$this->_exisitngWarehouses) {
            $existingWarehousesTemp = Mage::getResourceModel('novaposhta/warehouse_collection')->getSelect();
            $existingWarehousesTemp = $this->_getConnection()->query($existingWarehousesTemp)->fetchAll();
            if (empty($existingWarehousesTemp)) {
                $existingWarehousesTemp = array();
            }

            $this->_exisitngWarehouses = array();
            foreach ($existingWarehousesTemp as $existingWarehouse) {
                $this->_exisitngWarehouses[$existingWarehouse['id']] = $existingWarehouse;
            }

            unset($existingWarehousesTemp);
        }
        return $this->_exisitngWarehouses;
    }
}