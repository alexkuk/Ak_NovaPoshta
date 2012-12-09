<?php
class Ak_NovaPoshta_Block_Adminhtml_Warehouses extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'novaposhta';
        $this->_controller = 'adminhtml_warehouses';
        $this->_headerText = $this->__('Manage warehouses');

        parent::__construct();

        $this->_removeButton('add');
        $this->_addButton('synchronize', array(
            'label'     => $this->__('Synchronize with API'),
            'onclick'   => 'setLocation(\'' . $this->getUrl('*/*/synchronize') .'\')'
        ));
    }
}
