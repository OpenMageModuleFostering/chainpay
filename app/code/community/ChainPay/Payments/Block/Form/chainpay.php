<?php
class ChainPay_Payments_Block_Form_ChainPay extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        $payment_template = 'chainpay/form/chainpay.phtml';

        parent::_construct();
        
        $this->setTemplate($payment_template);
    }
}
