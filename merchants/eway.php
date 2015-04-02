<?php
if(!is_callable('get_option')) {
  // This is here to stop error messages on servers with Zend Accelerator, it includes all files before get_option is declared
  // then evidently includes them again, otherwise this code would break these modules
  return;
  exit("Something strange is happening, and \"return\" is not breaking out of a file.");
}

if (  ! version_compare( phpversion(), '5.3', '>=' ) ) { return; }

global $gateway_checkout_form_fields;

$nzshpcrt_gateways[$num] = array(
	'name' => __( 'eWay', 'wpsc_gold_cart' ),
	'api_version' => 2.0,
	'class_name' => 'wpsc_merchant_eway',
	'has_recurring_billing' => false,
	'wp_admin_cannot_cancel' => false,
	'requirements' => array(
		 /// so that you can restrict merchant modules to PHP 5, if you use PHP 5 features
		'php_version' => 5.0,
		 /// for modules that may not be present, like curl
		'extra_modules' => array('soap', 'curl')
	),
	
	// this may be legacy, not yet decided
	'internalname' => 'wpsc_merchant_eway',

	// All array members below here are legacy, and use the code in paypal_multiple.php
	'form' => "form_eway",
	'submit_function' => "submit_eway",
	'payment_type' => "credit_card",
	'supported_currencies' => array(
	'currency_list' => array('USD')
		//,'option_name' => 'paypal_curcode'
	)
);

class wpsc_merchant_eway extends wpsc_merchant {
	var $name = 'eWay';
	var $credit_card_details = array(
		'card_number' => null,
		'expiry_month' => null,
		'expiry_year' => null,
		'card_code' => null
  );
  
	function submit() {
		require_once('eWay/lib.php');

		//Send card data
		$this->credit_card_details = array(
			'card_number' => $_POST['eway_card_number'],
			'expiry_month' => $_POST['eway_expiry_month'],
			'expiry_year' => $_POST['eway_expiry_year'],
			'card_code' => $_POST['eway_card_code']
		);
		
		$request = new eWAY\CreateDirectPaymentRequest();
		
		//Send vars to eWay
		$request->Customer->FirstName = $this->cart_data['billing_address']['first_name'];
		$request->Customer->LastName = $this->cart_data['billing_address']['last_name'];
		$request->Customer->Reference = $this->cart_data['session_id'];
		$request->Customer->City = $this->cart_data['billing_address']['city'];
		$request->Customer->State = $this->cart_data['billing_address']['state'];
		$request->Customer->PostalCode = $this->cart_data['billing_address']['post_code'];
		$request->Customer->Email = $this->cart_data['email_address'];
		
		//Card info
		$request->Customer->CardDetails->Name = $request->Customer->FirstName . ' ' . $request->Customer->LastName;
		$request->Customer->CardDetails->Number = $this->credit_card_details['card_number'];
		$request->Customer->CardDetails->ExpiryMonth = $this->credit_card_details['expiry_month'];
		$request->Customer->CardDetails->ExpiryYear = $this->credit_card_details['expiry_year'];
		$request->Customer->CardDetails->CVN = $this->credit_card_details['card_code'];
		
		//Populate values for LineItems
		$i = 0;
		foreach($this->cart_items as $cart_row) {
			$item[$i] = new eWAY\LineItem();
			$item[$i]->Description = $cart_row['name'];
			$item[$i]->Quantity = $cart_row['quantity'];
			$item[$i]->UnitCost = $cart_row['price'] * 100;
			$item[$i]->Total = ($cart_row['price'] * 100) * $cart_row['quantity'];
			$request->Items->LineItem[$i] = $item[$i];
			$i++;
		}
		//Options
		$opt = new eWAY\Option();
		$opt->Value = $this->cart_data['session_id'];
		$request->Options->Option[0]= $opt;
		
		//Populate values for Payment Object
		$request->Payment->TotalAmount = number_format($this->cart_data['total_price'],2,'.','') * 100;
		$request->Payment->CurrencyCode = $this->cart_data['store_currency'];
		$request->Payment->InvoiceReference = $this->cart_data['session_id'];
		
		//Misc data
		$request->Method = 'ProcessPayment';
		$request->TransactionType = 'Purchase';

		$eway_params = array();
		if ( get_option('eway_testmode') == 'test' ) {
			$eway_params['sandbox'] = true;
		}
		$service = new eWAY\RapidAPI(get_option('eway_apikey'), get_option('eway_apipassword'), $eway_params);
		$result = $service->DirectPayment($request);		

		if(isset($result->Errors)) {
			// Get Error Messages from Error Code. Error Code Mappings are in the Config.ini file
			$ErrorArray = explode(",", $result->Errors);
			$lblError = "";
			foreach ( $ErrorArray as $error ) {
				$error = $service->getMessage($error);
				$lblError .= $error . "<br />\n";;
			}

		}
		if (isset($lblError)) {
		   $error_messages = wpsc_get_customer_meta( 'checkout_misc_error_messages' );
			if ( ! is_array( $error_messages ) )
				$error_messages = array();
			$error_messages[] = '<strong style="color:red">' . $lblError . ' </strong>';
			wpsc_update_customer_meta( 'checkout_misc_error_messages', $error_messages );
			$this->return_to_checkout();
			exit();
		}

		if (isset($result->TransactionStatus) && $result->TransactionStatus && (is_bool($result->TransactionStatus) || $result->TransactionStatus != "false")) {
			$sessionid = $result->Payment->InvoiceReference;
			$purchase_log = new WPSC_Purchase_Log( $sessionid, 'sessionid' );
			$purchase_log->set( array(
			'processed' => WPSC_Purchase_Log::ACCEPTED_PAYMENT,
			'transactid' => $result->TransactionID,
			'notes' => 'eWay Auth Code : "' . $result->AuthorisationCode . '"',
			) );
			$purchase_log->save();
			$this->go_to_transaction_results( $this->cart_data['session_id'] );
			exit();		
		} else {
		   $error_messages = wpsc_get_customer_meta( 'checkout_misc_error_messages' );
			if ( ! is_array( $error_messages ) )
				$error_messages = array();
			$error_messages[] = '<strong style="color:red">' . parse_error_message_eway($result->ResponseMessage) . ' </strong>';
			wpsc_update_customer_meta( 'checkout_misc_error_messages', $error_messages );	
			$checkout_page_url = get_option( 'shopping_cart_url' );
			if ( $checkout_page_url ) {
			  header( 'Location: '.$checkout_page_url );
			  exit();
			}
		}
	}
}

function parse_error_message_eway($message){
	
	$error_codes = array('F7000' => "Undefined Fraud",'V5000' => "Undefined System",'A0000' => "Undefined Approved",'A2000' => "Transaction Approved",	'A2008' => "Honour With Identification",'A2010' => "Approved For Partial Amount",'A2011' => "Approved VIP",'A2016' => "Approved Update Track 3",'V6000' => "Undefined Validation",'V6001' => "Invalid Request CustomerIP",'V6002' => "Invalid Request DeviceID",'V6011' => "Invalid Payment Amount",'V6012' => "Invalid Payment InvoiceDescription",'V6013' => "Invalid Payment InvoiceNumber",'V6014' => "Invalid Payment InvoiceReference",'V6015' => "Invalid Payment CurrencyCode",'V6016' => "Payment Required",'V6017' => "Payment CurrencyCode Required",'V6018' => "Unknown Payment CurrencyCode",'V6021' => "Cardholder Name Required",'V6022' => "Card Number Required",'V6023' => "CVN Required",'V6031' => "Invalid Card Number",'V6032' => "Invalid CVN",'V6033' => "Invalid Expiry Date",'V6034' => "Invalid Issue Number",'V6035' => "Invalid Start Date",'V6036' => "Invalid Month",'V6037' => "Invalid Year",'V6040' => "Invaild Token Customer Id",'V6041' => "Customer Required",'V6042' => "Customer First Name Required",'V6043' => "Customer Last Name Required",'V6044' => "Customer Country Code Required",'V6045' => "Customer Title Required",'V6046' => "Token Customer ID Required",'V6047' => "RedirectURL Required",'V6051' => "Invalid Customer First Name",'V6052' => "Invalid Customer Last Name",'V6053' => "Invalid Customer Country Code",'V6054' => "Invalid Customer Email",'V6055' => "Invalid Customer Phone",'V6056' => "Invalid Customer Mobile",'V6057' => "Invalid Customer Fax",'V6058' => "Invalid Customer Title",'V6059' => "Redirect URL Invalid",'V6060' => "Redirect URL Invalid",'V6061' => "Invaild Customer Reference",'V6062' => "Invaild Customer CompanyName",'V6063' => "Invaild Customer JobDescription",'V6064' => "Invaild Customer Street1",'V6065' => "Invaild Customer Street2",'V6066' => "Invaild Customer City",'V6067' => "Invaild Customer State",'V6068' => "Invaild Customer Postalcode",'V6069' => "Invaild Customer Email",'V6070' => "Invaild Customer Phone",'V6071' => "Invaild Customer Mobile",'V6072' => "Invaild Customer Comments",'V6073' => "Invaild Customer Fax",'V6074' => "Invaild Customer Url",'V6075' => "Invaild ShippingAddress FirstName",'V6076' => "Invaild ShippingAddress LastName",'V6077' => "Invaild ShippingAddress Street1",'V6078' => "Invaild ShippingAddress Street2",'V6079' => "Invaild ShippingAddress City",'V6080' => "Invaild ShippingAddress State",'V6081' => "Invaild ShippingAddress PostalCode",'V6082' => "Invaild ShippingAddress Email",'V6083' => "Invaild ShippingAddress Phone",'V6084' => "Invaild ShippingAddress Country",'V6091' => "Unknown Country Code",'V6100' => "Invalid ProcessRequest name",'V6101' => "Invalid ProcessRequest ExpiryMonth",'V6102' => "Invalid ProcessRequest ExpiryYear",'V6103' => "Invalid ProcessRequest StartMonth",'V6104' => "Invalid ProcessRequest StartYear",'V6105' => "Invalid ProcessRequest IssueNumber",'V6106' => "Invalid ProcessRequest CVN",'V6107' => "Invalid ProcessRequest AccessCode",'V6108' => "Invalid ProcessRequest CustomerHostAddress",'V6109' => "Invalid ProcessRequest UserAgent",'V6110' => "Invalid ProcessRequest Number",'D4401' => "Refer to Issuer",'D4402' => "Refer to Issuer, special",'D4403' => "No Merchant",'D4404' => "Pick Up Card",'D4405' => "Do Not Honour",'D4406' => "Error",'D4407' => "Pick Up Card, Special",'D4409' => "Request In Progress",'D4412' => "Invalid Transaction",'D4413' => "Invalid Amount",'D4414' => "Invalid Card Number",'D4415' => "No Issuer",'D4419' => "Re-enter Last Transaction",'D4421' => "No Method Taken",'D4422' => "Suspected Malfunction",'D4423' => "Unacceptable Transaction Fee",'D4425' => "Unable to Locate Record On File",'D4430' => "Format Error",'D4431' => "Bank Not Supported By Switch",'D4433' => "Expired Card, Capture",'D4434' => "Suspected Fraud, Retain Card",'D4435' => "Card Acceptor, Contact Acquirer, Retain Card",'D4436' => "Restricted Card, Retain Card",'D4437' => "Contact Acquirer Security Department, Retain Card",'D4438' => "PIN Tries Exceeded, Capture",'D4439' => "No Credit Account",'D4440' => "Function Not Supported",'D4441' => "Lost Card",'D4442' => "No Universal Account",'D4443' => "Stolen Card",'D4444' => "No Investment Account",'D4451' => "Insufficient Funds",'D4452' => "No Cheque Account",'D4453' => "No Savings Account",'D4454' => "Expired Card",'D4455' => "Incorrect PIN",'D4456' => "No Card Record",'D4457' => "Function Not Permitted to Cardholder",'D4458' => "Function Not Permitted to Terminal",'D4460' => "Acceptor Contact Acquirer",'D4461' => "Exceeds Withdrawal Limit",'D4462' => "Restricted Card",'D4463' => "Security Violation",'D4464' => "Original Amount Incorrect",'D4466' => "Acceptor Contact Acquirer, Security",'D4467' => "Capture Card",'D4475' => "PIN Tries Exceeded",'D4482' => "CVV Validation Error",'D4490' => "Cutoff In Progress",'D4491' => "Card Issuer Unavailable",'D4492' => "Unable To Route Transaction",'D4493' => "Cannot Complete, Violation Of The Law",'D4494' => "Duplicate Transaction",'D4496' => "System Error",);
	$message = $error_codes[$message];
	return $message;		
}

if(in_array('wpsc_merchant_eway',(array)get_option('custom_gateway_options'))) {
	$gateway_checkout_form_fields[$nzshpcrt_gateways[$num]['internalname']] = "
	<tr>
		<td>".__( 'Credit Card Number *', 'wpsc_gold_cart' )."</td>
		<td>
			<input type='text' data-eway-encrypt-name='eway_card_number' />
		</td>
	</tr>
	<tr>
		<td>".__( 'Credit Card Expiry *', 'wpsc_gold_cart' )."</td>
		<td>
			<input type='text' size='2' value='' maxlength='2' name='eway_expiry_month' />/<input type='text' size='2'  maxlength='2' value='' name='eway_expiry_year' />
		</td>
	</tr>
	<tr>
		<td>".__( 'CVV', 'wpsc_gold_cart' )."</td>
		<td><input type='text' size='4' id='eway_card_cvv' data-eway-encrypt-name='eway_card_code' /></td>
	</tr>
";
}

function eway_enqueue_js() {
	echo "
		<script type='text/javascript'>
			(function($){
				var eWayScript = function() {
						if ( $( 'input[name=\"custom_gateway\"]' ).val() === 'wpsc_merchant_eway' ) {
							$('form.wpsc_checkout_forms').attr('data-eway-encrypt-key', '".get_option('eway_encryption_key')."');
							$.getScript( 'https://secure.ewaypayments.com/scripts/eCrypt.js', function() {
							});
						}
				};

			$( window ).load( eWayScript );
			$( 'input:radio[name=\"custom_gateway\"]' ).change( eWayScript );

<<<<<<< HEAD
			$( window ).load( function() {
				var check_btn = $('.make_purchase.wpsc_buy_button');
				check_btn.click( function(event) {
					$(this).hide();
					// do other stuff here
					return(true);
				});
			});
			
=======
>>>>>>> fd6f5d8dc41c8518a6afe5fda2bfa9a1610a8c5a
			})(jQuery);
		</script>";
}
add_action('wpsc_bottom_of_shopping_cart' , 'eway_enqueue_js');

function submit_eway() {
	$options = array(
		'eway_apikey',
		'eway_apipassword',
		'eway_encryption_key',
		'eway_testmode',
	);
	foreach ( $options as $option ) {
		update_option( $option, $_POST[$option] );
	}
	return true;
}

function form_eway() {
	return "
		<tr>
			<td>
				".__( 'Api Key', 'wpsc_gold_cart' )."
			</td>
			<td>
				<input type='text' size='40' value='". get_option('eway_apikey')."' name='eway_apikey' />
			</td>
		</tr>
		<tr>
			<td>
				".__( 'Api Password', 'wpsc_gold_cart' )."
			</td>
			<td>
				<input type='text' size='40' value='". get_option('eway_apipassword')."' name='eway_apipassword' />
			</td>
		</tr>
		<tr>
			<td>
				".__( 'Encryption Key', 'wpsc_gold_cart' )."
			</td>
			<td>
				<input type='text' size='40' value='". get_option('eway_encryption_key')."' name='eway_encryption_key' />
			</td>
		</tr>
		<tr>
			<td>
				". __( 'Transaction Mode:', 'wpsc_gold_cart' ) . "
			</td>
			<td>
				<select lass='widefat' name='eway_testmode'>
					<option  value='live' ".selected(get_option('eway_testmode') , 'live',false) . " > ". __( 'Live Mode', 'wpsc_gold_cart' ) . "</option>
					<option  value='test' ".selected(get_option('eway_testmode') , 'test',false) . " >  ". __( 'Test Mode', 'wpsc_gold_cart' ) . "</option>
				</select>
			</td>
		</tr>";
}
?>