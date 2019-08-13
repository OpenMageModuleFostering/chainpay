<?php
class ChainPay_Payments_Block_Info extends Mage_Payment_Block_Info
{
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('chainpay/info/info.phtml');
    }
}
