<?php
class Ak_NovaPoshta_Model_Carrier_NovaPoshta
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{

    protected $_code = 'novaposhta';

    /**
     * @param Mage_Shipping_Model_Rate_Request $request
     * @internal param \Mage_Shipping_Model_Rate_Request $data
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        /** @var $result Mage_Shipping_Model_Rate_Result */
        $result = Mage::getModel('shipping/rate_result');

        $shippingPrice = 0;
        $deliveryType = Ak_NovaPoshta_Model_Api_Client::DELIVERY_TYPE_WAREHOUSE_WAREHOUSE;

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $warehouseId = $quote->getShippingAddress()->getData('warehouse_id');

        if ($warehouseId) {
            $shippingCost = Mage::helper('novaposhta')->getShippingCost($warehouseId, false);
            $shippingPrice = $shippingCost['cost'];
        }

        /** @var $method Mage_Shipping_Model_Rate_Result_Method */
        $method = Mage::getModel('shipping/rate_result_method');
        $method->setCarrier($this->_code)
            ->setCarrierTitle($this->getConfigData('name'))
            ->setMethod('type_'.$deliveryType)
            ->setMethodTitle('Доставка до склада НовойПочты')
            ->setPrice($shippingPrice)
            ->setCost($shippingPrice);

        $result->append($method);

        return $result;
    }

    public function getAllowedMethods()
    {
        return array($this->_code => $this->getConfigData('name'));
    }

    /**
     * Check if carrier has shipping tracking option available
     *
     * @return boolean
     */
    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * @return array
     */
    protected function _getWeightPriceMap()
    {
        $weightPriceMap = $this->getConfigData('weight_price');
        if (empty($weightPriceMap)) {
            return array();
        }

        return unserialize($weightPriceMap);
    }

    /**
     * @param $packageWeight
     *
     * @return float
     */
    protected function _getDeliveryPriceByWeight($packageWeight)
    {
        $weightPriceMap = $this->_getWeightPriceMap();
        $resultingPrice = 0.00;
        if (empty($weightPriceMap)) {
            return $resultingPrice;
        }

        $minimumWeight = 1000000000;
        foreach ($weightPriceMap as $weightPrice) {
            if ($packageWeight <= $weightPrice['weight'] && $weightPrice['weight'] <= $minimumWeight) {
                $minimumWeight = $weightPrice['weight'];
                $resultingPrice = $weightPrice['price'];
            }
        }

        return $resultingPrice;
    }
}
