<?php
class ChainPay_Payments_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function debugData($debugData)
    {
        if (true === isset($debugData) && false === empty($debugData)) {
            \Mage::getModel('chainpay/PaymentMethod')->debugData($debugData);
        }
    }
	
	public function getSettings()
	{
		$params = array(
               'apikey' => \Mage::getStoreConfig('payment/chainpay/apikey'),
			   'privatekey' => \Mage::getStoreConfig('payment/chainpay/privatekey'),
			   
			   'invoice_created' => \Mage::getStoreConfig('payment/chainpay/invoice_created'),
			   'invoice_paid' => \Mage::getStoreConfig('payment/chainpay/invoice_paid'),
			   'invoice_completed' => \Mage::getStoreConfig('payment/chainpay/invoice_completed'),
			   'invoice_expired' => \Mage::getStoreConfig('payment/chainpay/invoice_expired'),
			   'invoice_cancelled' => \Mage::getStoreConfig('payment/chainpay/invoice_cancelled'),
            );

		return $params;
	}
}
?>
