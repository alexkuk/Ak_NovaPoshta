<?php
require_once '../abstract.php';

/**
 * Shell script for import warehouses and cities
 *
 * Class Mage_Shell_Novaposhta_Import
 */
class Mage_Shell_Novaposhta_Import
    extends Mage_Shell_Abstract
{
    /**
     * Import warehouses and cities
     *
     * @return $this
     */
    public function run()
    {
        Mage::getModel('novaposhta/import')->runWarehouseAndCityMassImport();

        return $this;
    }
}

$shell = new Mage_Shell_Novaposhta_Import();
$shell->run();
