<?php
class Ak_NovaPoshta_CheckoutController
    extends Mage_Core_Controller_Front_Action
{
    /**
     * Render form for choose city and warehouse
     */
    public function formAction()
    {
        $this->loadLayout();
        if ($cityId = $this->getRequest()->getParam('city')) {
            $this->getLayout()->getBlock('root')->setData('city_id', $cityId);
        }

        $this->renderLayout();
    }

    /**
     * Calculate shipping cost for destination
     */
    public function calculateAction()
    {
        $helper         = Mage::helper('novaposhta');
        $warehouseId    = (int) $this->getRequest()->getParam('warehouse');
        $result         = $helper->getShippingCost($warehouseId);
        $result['cost'] = $helper->currency( (float) $result['cost'], true, false);

        $this->getResponse()->setBody($helper->jsonEncode($result));
    }
}