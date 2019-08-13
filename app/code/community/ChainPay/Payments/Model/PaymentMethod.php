<?php
 
class ChainPay_Payments_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'chainpay';
    protected $_isGateway                   = true;
    protected $_canAuthorize                = true;
    protected $_canCapture                  = false;
    protected $_canUseInternal              = false;
    protected $_isInitializeNeeded          = false;
    protected $_canFetchTransactionInfo     = false;
    protected $_canManagerRecurringProfiles = false;
    protected $_canUseCheckout              = true;
    protected $_canUseForMultishipping      = true;
    protected $_canCapturePartial           = false;
    protected $_canRefund                   = false;
    protected $_canVoid                     = false;
	protected static $_redirectUrl;
	
	public function createFormBlock($name)
    {
        $block = $this->getLayout()->createBlock('chainpay/form_chainpay', $name)
            ->setMethod('chainpay')
            ->setPayment($this->getPayment())
            ->setTemplate('chainpay/formBlock.phtml');

        return $block;
    }
 
   	public function authorize(Varien_Object $payment, $amount)
    {
		if (false === isset($payment) || false === isset($amount) || true === empty($payment) || true === empty($amount)) {
            $this->debugData('[ERROR] In ChainPay_Payments_Model_PaymentMethod::authorize(): missing payment or amount parameters.');
            throw new \Exception('In ChainPay_Payments_Model_PaymentMethod::authorize(): missing payment or amount parameters.');
        }
		
		$order = $payment->getOrder();
		
		$invoice = $this->CreateInvoice($order, $amount);
		
		self::$_redirectUrl = $this->GetPaymentUri($invoice->Id);
		
		return $this;
	}
	
	public function getOrderPlaceRedirectUrl()
    {
        return self::$_redirectUrl;
    }
	
	 private function CreateInvoice($order, $amount)
	 {
		$forwardOnPaidUri = \Mage::getUrl(\Mage::getStoreConfig('payment/chainpay/forwardonpaiduri'));
		$callbackUri = \Mage::getUrl(\Mage::getStoreConfig('payment/chainpay/callbackuri'));
    
		$params = array(
               'Reference' => $order->getIncrementId(),
               'RequestCurrency' => $order->getBaseCurrencyCode(),
               'RequestAmount' => (float) $amount,
               'ForwardOnPaidUri' => $forwardOnPaidUri,
               'ForwardOnCancelUri' => 'http://cancel',
               'CallbackUri' => $callbackUri
            );
	
	 	$invoice = $this->ChainPay_Post($params, 'invoice');
	 	
	 	if(!$invoice)
	 	{
			$this->debugData('In ChainPay_Payments_Model_PaymentMethod::CreateInvoice(): Could not create Invoice.');
			throw new \Exception('In ChainPay_Payments_Model_PaymentMethod::CreateInvoice(): Could not create Invoice.');
	 	}
	 	
	 	return $invoice;
	 }
	 
	 private function ChainPay_Post($params, $relativeUri)
     {
 			$options = array(
				'http' => array(
					'method'  => 'POST',
					'content' => json_encode( $params ),
					'header'=>  "Content-Type: application/json\r\n" .
								"Accept: application/json\r\n" .
								"Authorization: " . \Mage::getStoreConfig('payment/chainpay/apikey') . "\r\n"
				  )
			);
			 
			$absoluteUri = $this->GetApiUri() . $relativeUri . '.json';
			$context     = stream_context_create($options);
			$result      = file_get_contents($absoluteUri, false, $context);
			
            if (strpos($http_response_header[0], '200') !== FALSE) {
                $chainPayResult = $this->ChainPay_DecodeResponse($result);
                if($chainPayResult)
                {
				   $this->debugData('Successful call to ChainPay API: ' . $absoluteUri);
                   return $chainPayResult;
                }
                
                $this->debugData('Could not deserialize response: ' . $response);
            }
            else {
                if(strpos($http_response_header[0], '401') !== FALSE)                
                {
                    $this->debugData('Unauthorized: Called ChainPay with invalid API Key. Please check your API Key in the Payment Method settings.');
                }
                else
                {
                    $this->debugData('Error returned from ChainPay API. Called URI: ' . $absoluteUri
                    . '"\r\nWith data: ' . json_encode($params) 
                    . '\r\nResponse:' . $response);
                }
            }
            
            return false;
        }
		
		private function GetApiUri() {
			if(\Mage::getStoreConfig('payment/chainpay/sandbox'))
			{
				return 'https://testapi.altxe.com/';
			}
			else
			{
				return 'https://api.altxe.com/';
			}
		}
		
        private function GetPaymentUri($invoiceId) {
			if(\Mage::getStoreConfig('payment/chainpay/sandbox'))
			{
				return 'https://testpay.chainpay.com/invoice?id=' . $invoiceId;
			}
			else
			{
				return 'https://pay.chainpay.com/invoice?id=' . $invoiceId;
			}
		}
        
        private function ChainPay_DecodeResponse($data)
        {
            $response = json_decode( $data );
            // Verify response is a JSON encoded object.
            if( ! is_object( $response ) ) {
                // Could not decode response.
                return false;
            }
            
            return $response;
        }
}
?>