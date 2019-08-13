<?php
class ChainPay_Payments_CallbackController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        if (ini_get('allow_url_fopen') === false) {
            ini_set('allow_url_fopen', true);
        }

        $data = file_get_contents('php://input');
		if ($data === false) {
            $this->debugData('No data received.');
            throw new \Exception('No data received.');
        }

		$callbackId = $_SERVER['HTTP_X_ALTXE_CALLBACKID'];
		$callbackType = $_SERVER['HTTP_X_ALTXE_CALLBACKTYPE'];
		$callbackCreated = $_SERVER['HTTP_X_ALTXE_CALLBACKCREATED'];
		$callbackAttempt = $_SERVER['HTTP_X_ALTXE_CALLBACKATTEMPT'];
		$callbackSignature = $_SERVER['HTTP_X_ALTXE_SIGNATURE'];
		$callbackSalt = $_SERVER['HTTP_X_ALTXE_SALT'];
		
		$settings = \Mage::helper('chainpay')->getSettings();
		
		$signature = base64_decode($callbackSignature);
		$salt = base64_decode($callbackSalt);
		$key = base64_decode($settings['privatekey']) . $salt;

		$isValid = $this->ValidateSignature($data, $signature, $key);
		if($isValid != false)
		{
			$event = $this->DecodeResponse($data);
			$this->debugData('WebHook object received: ' . json_encode($event));
			if($event != false)
			{
				$orderId = intval($event->Reference);
				$order = \Mage::getModel('sales/order')->loadByIncrementId($orderId);
				if($order)
				{
					switch($callbackType)
					{
						case 'InvoicePaid':
							$this->debugData('WebHook - OrderId: ' . $orderId . ' is now Paid.');
												
							$payment = \Mage::getModel('sales/order_payment')->setOrder($order);
							$payment->registerCaptureNotification($order->getGrandTotal());
							$order->addPayment($payment);
							
							if (!$order->getEmailSent()) {
								$order->sendNewOrderEmail();
							}
							
							$newStatus = $settings['invoice_paid'];
							$order->addStatusToHistory($newStatus, sprintf('ChainPay notification changed status to %s', $newStatus));
							$order->save();
							break;
						case 'InvoiceCompleted':
							$this->debugData('WebHook - OrderId: ' . $orderId . ' is now Completed.');
														
							$newStatus = $settings['invoice_completed'];
							$order->addStatusToHistory($newStatus, sprintf('ChainPay notification changed status to %s', $newStatus));
							$order->save();
							break;
						case 'InvoiceExpired':
							$this->debugData('WebHook - OrderId: ' . $orderId . ' is now Expired.');
														
							$newStatus = $settings['invoice_expired'];
							$order->addStatusToHistory($newStatus, sprintf('ChainPay notification changed status to %s', $newStatus));
							$order->save();
							break;
					}
					return true;
				}
				else
				{
					$this->debugData('Could not locate Order relating to WebHook event: ' . json_encode($data));
					throw new \Exception('Could not locate Order relating to WebHook event: ' . json_encode($data));
				}
			}
		}
		
		$this->debugData('WebHook event failure: ' . json_encode($data));
		throw new \Exception('WebHook event failure: ' . json_encode($data));
    }
	
	function DecodeResponse($data)
	{
		$response = json_decode( $data );
		// Verify response is a JSON encoded object.
		if( ! is_object( $response ) ) {
			// Could not decode response.
			return false;
		}
		
		return $response;
	}
	
	function ValidateSignature($message, $signature, $key)
	{
		$hmac = hash_hmac('sha256', $message, $key );
		$hmacBytes = pack('H*', $hmac); 
		
		if($hmacBytes != $signature)
		{
			$this->debugData('Invalid message signature: ' . $message);
			return false;
		}
		return true;
	}
	
	function debugData($data)
	{
		\Mage::helper('chainpay')->debugData($data);
	}
}
