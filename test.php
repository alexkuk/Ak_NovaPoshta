<?php
require 'app/Mage.php';
Mage::app('default');
Mage::getModel('novaposhta/import')->runWarehouseAndCityMassImport();