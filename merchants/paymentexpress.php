<?php
/**
	* WP eCommerce Test Merchant Gateway
	* This is the file for the test merchant gateway
	*
	* @package wp-e-comemrce
	* @since 3.7.6
	* @subpackage wpsc-merchants
*/
$nzshpcrt_gateways[$num] = array(
	'name' => 'Payment Express - PX Fusion',
	'api_version' => 2.0,
	'class_name' => 'wpsc_merchant_paymentexpress',
	'has_recurring_billing' => true,
	'wp_admin_cannot_cancel' => false,
	'requirements' => array(
		 /// so that you can restrict merchant modules to PHP 5, if you use PHP 5 features
		///'php_version' => 5.0,
	),
	
	'form' => 'paymentexpress_form',
	'payment_type' => 'credit_card',
	// this may be legacy, not yet decided
	'internalname' => 'wpsc_merchant_paymentexpress',
);

function paymentexpress_form(){
	$output = "<tr>\n\r";
	$output .= "	<td>\n\r";
	$output .= "		Username:";
	$output .= "	</td>\n\r";
	$output .= "	<td>\n\r";
	$output .= "		<input type='text' name='wpsc_options[paymentexpress_username]' value='" . stripslashes(get_option('paymentexpress_username')) . "' />";
	$output .= "	</td>\n\r";
	$output .= "</tr>\n\r";
	$output .= "<tr>\n\r";
	$output .= "	<td>\n\r";
	$output .= "		Password:";
	$output .= "	</td>\n\r";
	$output .= "	<td>\n\r";
	$output .= "		<input type='text' name='wpsc_options[paymentexpress_password]' value='" . stripslashes(get_option('paymentexpress_password')) . "' />";
	$output .= "	</td>\n\r";
	$output .= "</tr>\n\r";
	$output .= "<tr><td colspan='2'>" . __('You can login to Payment Manager (<a href="https://www.paymentexpress.com/pxmi/logon">https://www.paymentexpress.com/pxmi/logon</a>) to see your transactions.') . "</td></tr>";
	
	return $output;
}

class wpsc_merchant_paymentexpress extends wpsc_merchant {
	var $name = 'Payment Express';
  
	function submit() {
		if(@extension_loaded('soap')){
			$pxf = new PxFusion(); # handles most of the Px Fusion magic
	
			// Work out the probable location of return.php since this sample
			// code could be anywhere on a development server.
			$returnUrl = add_query_arg( 'sessionid', $this->cart_data['session_id'], get_option( 'transact_url' ) );
		
			// Set some transaction details
			$pxf->set_txn_detail('txnType', 'Purchase');	# required
			$pxf->set_txn_detail('currency', 'NZD');		# required
			$pxf->set_txn_detail('returnUrl', $returnUrl);	# required
			$pxf->set_txn_detail('amount', number_format ( wpsc_cart_total(false) , $decimals = 2 , $dec_point = '.' , $thousands_sep = '' ));		# required
			$pxf->set_txn_detail('merchantReference', get_bloginfo('name'));
		
			// Some of the many optional settings that could be specified:
			$pxf->set_txn_detail('enableAddBillCard', 0);
			$pxf->set_txn_detail('txnRef', substr(uniqid() . rand(1000,9999), 0, 16)); # random 16 digit reference);
			
			// Make the request for a transaction id
			$response = $pxf->get_transaction_id();
			
			if ( ! $response->GetTransactionIdResult->success)
			{
				wp_die('Error! There was a problem getting a transaction id from DPS, please contact the server administrator.');
			}
			else
			{
				// You should store these values in a database
				// ... they are needed to query the transaction's outcome
				$result = $response->GetTransactionIdResult;
				$transaction_id = $result->transactionId;
				$session_id = $result->sessionId;
			}
			
			$curlPost = array(
				'SessionId' => $session_id,
				'Add' => 'Add',
				'CardHolderName' =>  $_POST['CardHolderName'],
				'CardNumber' =>  $_POST['CardNumber'],
				'Cvc2' =>  $_POST['Cvc2'],
				'ExpiryMonth' =>  $_POST['ExpiryMonth'],
				'ExpiryYear' =>  $_POST['ExpiryYear'],
			);
			
			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_URL, 'https://sec.paymentexpress.com/pxmi3/pxfusionauth');
			curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
			curl_setopt($ch, CURLOPT_HEADER, 1); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($ch, CURLOPT_POST, 1); 
			curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_CAINFO, WPSC_GOLD_FILE_PATH ."/merchants/paymentexpress/ThawteServerCA");
			$data = curl_exec($ch); 
			curl_close($ch);
		} else {
			
			$data = '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://paymentexpress.com">
						  <SOAP-ENV:Body>
						    <ns1:GetTransactionId>
						      <ns1:username>InstinctFusion</ns1:username>
						      <ns1:password>inst1234</ns1:password>
						      <ns1:tranDetail>
						        <ns1:amount>1.00</ns1:amount>
						        <ns1:currency>NZD</ns1:currency>
						        <ns1:enableAddBillCard>false</ns1:enableAddBillCard>
						        <ns1:merchantReference>Px Fusion -PHP</ns1:merchantReference>
						        <ns1:returnUrl>http://www.myReturnURL/return.php</ns1:returnUrl>
						        <ns1:txnRef>4cf703e6c79ff738</ns1:txnRef>
						        <ns1:txnType>Purchase</ns1:txnType>
						      </ns1:tranDetail>
						    </ns1:GetTransactionId>
						  </SOAP-ENV:Body>
						</SOAP-ENV:Envelope>';
			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_URL, "https://sec.paymentexpress.com/pxf/pxf.svc?wsdl"); 
			curl_setopt($ch, CURLOPT_VERBOSE, 0); 
			curl_setopt($ch, CURLOPT_HEADER, 0); 
			curl_setopt($ch, CURLOPT_POST, 1);
			
			// SSL security
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_CAINFO, WPSC_GOLD_FILE_PATH ."/merchants/paymentexpress/ThawteServerCA");
			//
			
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: text/xml;charset=\"utf-8\"", 
													        "Accept: text/xml", 
													        "Cache-Control: no-cache", 
													        "Pragma: no-cache", 
													        "SOAPAction: \"http://paymentexpress.com/IPxFusion/GetTransactionId\"", 
													        "Content-length: ".strlen($data))
        	); 
        	
			$response = curl_exec($ch); 
			
			if(curl_errno($ch)){ 
			  wp_die( 'Curl error: ' . curl_error($ch) . '. Please contact server administrator.' ); 
			} 
			
			curl_close($ch);  
			
			$xml_parser = xml_parser_create(); 
			if(!(xml_parse_into_struct($xml_parser, $response, $vals, $index))){ 
			    wp_die("Error while parsing response from PX Fusion. Line " . xml_get_current_line_number($xml_parser) . '. Please contact server administrator.' ); 
			}
			xml_parser_free($xml_parser);
			
			$parsed_xml=array();
			foreach($vals as $val)
				$parsed_xml[$val['tag']]=$val['value'];
			
			if ( ! $parsed_xml['A:SUCCESS'] )
			{
				wp_die('Error! There was a problem getting a transaction id from DPS, please contact the server administrator.');
			}
			else
			{
				// You should store these values in a database
				// ... they are needed to query the transaction's outcome
				$transaction_id = $parsed_xml["A:TRANSACTIONID"];
				$session_id = $parsed_xml["A:SESSIONID"];
			}
			
			$curlPost = array(
				'SessionId' => $session_id,
				'Add' => 'Add',
				'CardHolderName' =>  $_POST['CardHolderName'],
				'CardNumber' =>  $_POST['CardNumber'],
				'Cvc2' =>  $_POST['Cvc2'],
				'ExpiryMonth' =>  $_POST['ExpiryMonth'],
				'ExpiryYear' =>  $_POST['ExpiryYear'],
			);
			
			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_URL, 'https://sec.paymentexpress.com/pxmi3/pxfusionauth');
			curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
			curl_setopt($ch, CURLOPT_HEADER, 1); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($ch, CURLOPT_POST, 1); 
			curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_CAINFO, WPSC_GOLD_FILE_PATH ."/merchants/paymentexpress/ThawteServerCA");
			$data = curl_exec($ch); 
			curl_close($ch);
		}
		
		$this->px_process_transaction($transaction_id);
	}
	
	function px_process_transaction($transaction_id, $try = 0){
		global $purchase_log;
		if( @extension_loaded('soap')){
			$pxf = new PxFusion(); # handles most of the Px Fusion magic
		
			$response = $pxf->get_transaction($transaction_id);
			$transaction_details = get_object_vars($response->GetTransactionResult);
			unset($pxf);
		} else {
			$data = '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://paymentexpress.com">
						  <SOAP-ENV:Body>
						    <ns1:GetTransaction>
						      <ns1:username>InstinctFusion</ns1:username>
						      <ns1:password>inst1234</ns1:password>
						        <ns1:transactionId>' . $transaction_id . '</ns1:transactionId>
						    </ns1:GetTransaction>
						  </SOAP-ENV:Body>
						</SOAP-ENV:Envelope>';
			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_URL, "https://sec2.paymentexpress.com/pxf/pxf.svc?wsdl"); 
			curl_setopt($ch, CURLOPT_VERBOSE, 0); 
			curl_setopt($ch, CURLOPT_HEADER, 0); 
			curl_setopt($ch, CURLOPT_POST, 1);
			
			// SSL security
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_CAINFO, WPSC_GOLD_FILE_PATH ."/merchants/paymentexpress/ThawteServerCA");
			//
			
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: text/xml;charset=\"utf-8\"", 
													        "Accept: text/xml", 
													        "Cache-Control: no-cache", 
													        "Pragma: no-cache", 
													        "SOAPAction: \"http://paymentexpress.com/IPxFusion/GetTransaction\"", 
													        "Content-length: ".strlen($data))
        	); 
        	
			$response = curl_exec($ch); 
			
			if(curl_errno($ch)){ 
			  wp_die( 'Curl error: ' . curl_error($ch) . '. Please contact server administrator.' ); 
			} 
			
			curl_close($ch); 
			
			$xml_parser = xml_parser_create(); 
			if(!(xml_parse_into_struct($xml_parser, $response, $vals, $index))){ 
			    wp_die("Error while parsing response from PX Fusion. Line " . xml_get_current_line_number($xml_parser) . '. Please contact server administrator.' ); 
			}
			xml_parser_free($xml_parser);
			
			$parsed_xml=array();
			foreach($vals as $val)
				$parsed_xml[$val['tag']]=$val['value'];
			
			if ( ! isset($parsed_xml["STATUS"]) )
			{
				wp_die('Error! There was a problem getting response from DPS, please contact the server administrator.');
			}
			else
			{
				$transaction_details['status'] = $parsed_xml["STATUS"];
				$transaction_details['transactionId'] = $parsed_xml["TRANSACTIONID"];
			}
		
		}
		switch($transaction_details['status']){
			case 0:
				//'approved';
				$this->set_transaction_details( $transaction_details['transactionId'], 3 );
				$purchase_log['processed']=3;
				$this->go_to_transaction_results($this->cart_data['session_id']);
				break;
			case 1:
				//declined
				$this->set_transaction_details( $transaction_details['transactionId'], 1 );
				$this->set_error_message(__('Your transaction was declined. Please check your credit card details and try again.', 'wpsc'));
				do_action('wpsc_payment_failed');
				$this->return_to_checkout();
				break;
			case 2:
				//transient error, retry
				if( $try<10 ) {
					//retry
					$this->px_process_transaction($transaction_id, $try+1);
				} else {
					$this->set_transaction_details( $transaction_details['transactionId'], 2 );
					$purchase_log['processed']=2;
					$this->go_to_transaction_results($this->cart_data['session_id']);
				}
				break;
			case 3:
				//'invalid data';
				if( $try<5 ) {
					//retry
					$this->px_process_transaction($transaction_id, $try+1);
				} else {
					$this->set_transaction_details( $transaction_details['transactionId'], 1 );
					$purchase_log['processed']=1;
					$this->go_to_transaction_results($this->cart_data['session_id']);
				}
				break;
			case 4:
				//'result cannot be determined at this time, retry';
				if( $try<10 ) {
					//retry
					$this->px_process_transaction($transaction_id, $try+1);
				} else {
					$this->set_transaction_details( $transaction_details['transactionId'], 2 );
					$purchase_log['processed']=2;
					$this->go_to_transaction_results($this->cart_data['session_id']);
				}
				break;
			case 5:
				//failed due timeout or canceled
				$this->set_transaction_details( $transaction_details['transactionId'], 1 );
				$purchase_log['processed']=1;
				$this->go_to_transaction_results($this->cart_data['session_id']);
				break;
			case 6:
				//transaction not found'
				$this->set_transaction_details( $transaction_details['transactionId'], 1 );
				$purchase_log['processed']=1;
				$this->go_to_transaction_results($this->cart_data['session_id']);
				break;
		}
	}
}

class PxFusion
{
	// DPS Px Fusion Details
	protected function fusion_username(){
		return stripslashes(get_option('paymentexpress_username'));
	}
	protected function fusion_password(){
		return stripslashes(get_option('paymentexpress_password'));
	}
	protected $wsdl = 'https://sec.paymentexpress.com/pxf/pxf.svc?wsdl';

	// Variables/Objects that are used to hold data for transactions
	public $tranDetail;
	protected $soap_client;

	public function __construct()
	{
		if ( ! is_object($this->tranDetail))
		{
			$this->tranDetail = new stdClass();
		}
	}

	public function set_txn_detail($property, $value)
	{
		$this->tranDetail->$property = $value;
	}

	public function get_transaction_id()
	{
		$this->soap_client = new SoapClient($this->wsdl, array('soap_version' => SOAP_1_1));

		// SoapClient does some magic conversion from array into the required soap+xml format
		$array_for_soap = array(
			'username' => $this->fusion_username(),
			'password' => $this->fusion_password(),
			'tranDetail' => get_object_vars($this->tranDetail) # extracts all properties of object into associative array
		);

		$response = $this->soap_client->GetTransactionId($array_for_soap);
		return $response;
	}

	public function get_transaction($transaction_id)
	{
		$this->soap_client = new SoapClient($this->wsdl, array('soap_version' => SOAP_1_1));
		$array_for_soap = array(
			'username' => $this->fusion_username(),
			'password' => $this->fusion_password(),
			'transactionId' => $transaction_id
		);

		$response = $this->soap_client->GetTransaction($array_for_soap);
		return $response;
	}
}


//One day this will be a standard XHTML form that we can include into any gateway 
if(in_array('wpsc_merchant_paymentexpress',(array)get_option('custom_gateway_options'))) {
	$curryear = date( 'Y' );
	$curryear_2 = date( 'y' );
	//generate year options
	$years = '';
	for ( $i = 0; $i < 10; $i++ ) {
		$years .= "<option value='" . $curryear_2 . "'>" . $curryear . "</option>\r\n";
		$curryear++;
		$curryear_2++;
	}

	$gateway_checkout_form_fields[$nzshpcrt_gateways[$num]['internalname']] = "
	<tr>
		<td colspan='2'>
			<h4>".__('Credit cards Details','wpsc')."</h4>
			<img src='".WPSC_GOLD_FILE_URL."/merchants/paymentexpress/cc.gif' alt='Visa, MasterCard, AmericanExpress, AMEX' />
		</td>
	</tr>
	<tr>
		<td>
			<label for='CardHolderName'>".__('Card holder name','wpsc')." *</label> 
		</td>
		<td>
			<input type='text' id='CardHolderName' name='CardHolderName' size='42' />
		</td>
	</tr>
	<tr>
		<td class='wpsc_CC_details'>
			<label for='CardNumber'>".__('Card Number','wpsc')." * </label></td>
		<td>
			<input type='text' value='' name='CardNumber' id='CardNumber' maxlength='16' />
		</td>
	</tr>
	<tr>
		<td class='wpsc_CC_details'>
			<label for='Cvc2'>".__('Cvc2','wpsc')." * </label></td>
		<td><input type='text' size='4' value='' maxlength='4' name='Cvc2' id='Cvc2' />
		</td>
	</tr>
	<tr>
		<td class='wpsc_CC_details'>
			<label for='ExpiryMonth'>".__('Expiry','wpsc')." * </label></td>
		<td>
			<select class='wpsc_ccBox' name='ExpiryMonth' id='ExpiryMonth'>
			<option value='01'>01</option>
			<option value='02'>02</option>
			<option value='03'>03</option>
			<option value='04'>04</option>
			<option value='05'>05</option>						
			<option value='06'>06</option>						
			<option value='07'>07</option>					
			<option value='08'>08</option>						
			<option value='09'>09</option>						
			<option value='10'>10</option>						
			<option value='11'>11</option>																			
			<option value='12'>12</option>																			
			</select>
			<select class='wpsc_ccBox' name='ExpiryYear'>
			" . $years . "
			</select>
		</td>
	</tr>
";
  }
?>
