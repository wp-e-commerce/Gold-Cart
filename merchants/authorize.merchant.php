<?php
if(!is_callable('get_option')) {
  // This is here to stop error messages on servers with Zend Accelerator, it includes all files before get_option is declared
  // then evidently includes them again, otherwise this code would break these modules
  return;
  exit("Something strange is happening, and \"return\" is not breaking out of a file.");
}
global $gateway_checkout_form_fields;
/**
	* WP eCommerce Authorize.net Merchant File
	*
	* This is the Authorize.net gateway file
	*
	* @package wp-e-commerce
	* @since 3.7.6
	* @subpackage wpsc-merchants
*/
$nzshpcrt_gateways[$num] = array(
	'name' => 'Authorize.net 2.0',
	'api_version' => 2.0,
	'class_name' => 'wpsc_merchant_authorize',
	'has_recurring_billing' => true,
	'wp_admin_cannot_cancel' => false,
	'requirements' => array(
		 /// so that you can restrict merchant modules to PHP 5, if you use PHP 5 features
		'php_version' => 5.0,
		 /// for modules that may not be present, like curl
		'extra_modules' => array('soap')
	),
	
	// this may be legacy, not yet decided
	'internalname' => 'wpsc_merchant_authorize',

	// All array members below here are legacy, and use the code in paypal_multiple.php
	'form' => "form_authorize",
	'submit_function' => "submit_authorize",
	'payment_type' => "credit_card",
	'supported_currencies' => array(
		'currency_list' => array('USD')
		//,'option_name' => 'paypal_curcode'
	)
);


if(in_array('wpsc_merchant_authorize',(array)get_option('custom_gateway_options'))) {
	$gateway_checkout_form_fields[$nzshpcrt_gateways[$num]['internalname']] = "
	<tr>
		<td>Credit Card Number *</td>
		<td>
			<input type='text' value='' name='card_number' />
		</td>
	</tr>
	<tr>
		<td>Credit Card Expiry *</td>
		<td>
			<input type='text' size='2' value='' maxlength='2' name='expiry[month]' />/<input type='text' size='2'  maxlength='2' value='' name='expiry[year]' />
		</td>
	</tr>
	<tr>
		<td>CVV </td>
		<td><input type='text' size='4' value='' maxlength='4' name='card_code' /></td>
	</tr>
";
  }


/**
	* WP eCommerce Authorize.net Standard Merchant Class
	*
	* This is the Authorize.net merchant class, it extends the base merchant class
	*
	* @package wp-e-commerce
	* @since 3.7.6
	* @subpackage wpsc-merchants
*/
class wpsc_merchant_authorize extends wpsc_merchant {
  var $name = 'Authorize.net';

  var $aim_response_keys = array(
  '1' => 'response_code',
  '2' => 'response_sub_code',
  '3' => 'response_reason_code',
  '4' => 'response_description',
  '5' => 'authorization_code',
  '6'=> 'avs_response',
  '7' => 'transaction_id',
  '8' => 'invoice_number',
  '9' => 'description',
  '10' => 'amount',
  '11' => 'method',
  '12' => 'transaction_type',
  '13' => 'customer_id',
  '37' => 'purchase_order_number',
  '39' => 'card_code_response'
  );
  var $onsite_cc_form = true;
/*
  var $credit_card_details = array(
		'card_number' => '4111111111111111',
		'expiry_date' => array('year' => '10', 'month' => '08'),
		'card_code' => '123'
  );
  */
  var $credit_card_details = array(
		'card_number' => null,
		'expiry_date' => null,
		'card_code' => null
  );
  
  
	var $arb_requests = array();


	var $soap_client = null;
	/**
	* construct value array method, converts the data gathered by the base class code to something acceptable to the gateway
	* @access public
	*/
	function construct_value_array() {
		$this->credit_card_details = array(
		'card_number' => $_POST['card_number'],
		'expiry_date' => array('year' => $_POST['expiry']['year'], 'month' => $_POST['expiry']['month']),
		'card_code' => $_POST['card_code']
		);
	
		$gateway_parameters = array();


		$gateway_parameters += array(
		/// Basic Authorize Settings
		'x_version' => 3.1,
		// 'x_test_request' => (int)(bool)get_option('authorize_testmode'),
		'x_type' => 'AUTH_CAPTURE',
		'x_method' => 'CC',
		'x_recurring_billing' => (int)$this->cart_data['is_subscription'],
		'x_duplicate_window' => '10', // Minimum time between duplicate transactions
		'x_delim_data' => 1,
		//'x_silent_post' => 'https://sandbox.boiling-pukeko.geek.nz',

		/// Authorize access credentials
		'x_login' => get_option('authorize_login'),
		'x_tran_key' => get_option("authorize_password"),
		
		/// Credit cart details start here
		'x_card_num' => $this->credit_card_details['card_number'],
		'x_exp_date' => $this->credit_card_details['expiry_date']['month']."-".$this->credit_card_details['expiry_date']['year'],
		'x_card_code' => $this->credit_card_details['card_code'],


		/// Transaction Details
		'x_amount' => number_format($this->cart_data['total_price'],2,'.',''),
		'x_trans_id' => $this->cart_data['session_id'],
		'x_invoice_num' => $this->cart_data['session_id'],
		//'x_description' => '',

		/// Items in the cart go here, is currently unimplemented
		//'x_line_item' => '',

		/// Customer details start here
		'x_email' => $this->cart_data['email_address'],
		//'x_phone' => '',
		//'x_cust_id' => '',
		'x_customer_ip' => $_SERVER['REMOTE_ADDR'],

		/// Customer billing details
		'x_first_name' => $this->cart_data['billing_address']['first_name'],
		'x_last_name' => $this->cart_data['billing_address']['last_name'],
		'x_address' => $this->cart_data['billing_address']['address'],
		'x_city' => $this->cart_data['billing_address']['city'],
		//'x_state' => $this->cart_data['billing_address'][''],
		'x_zip' => $this->cart_data['billing_address']['post_code'],
		'x_country' => $this->cart_data['billing_address']['country'],
		
		/// Customer shipping details
		'x_ship_to_first_name' => $this->cart_data['shipping_address']['first_name'],
		'x_ship_to_last_name' => $this->cart_data['shipping_address']['last_name'],
		'x_ship_to_address' => $this->cart_data['shipping_address']['address'],
		'x_ship_to_city' => $this->cart_data['shipping_address']['city'],
		//'x_ship_to_state' => $this->cart_data['shipping_address'][''],
		'x_ship_to_zip' => $this->cart_data['shipping_address']['post_code'],
		'x_ship_to_country' => $this->cart_data['shipping_address']['country'],
		
		//'x_po_num' => '',
		);

		foreach($this->cart_items as $cart_row) {
				if($cart_row['is_recurring'] == true) {
					$this->arb_requests[$cart_row['cart_item_id']] = $this->construct_arb_array($cart_row);
				}
		}

		
		$this->collected_gateway_data = $gateway_parameters;
	}
	
	/**
	* submit method, sends the received data to the payment gateway
	* @access public
	*/
	function submit() {
		$name_value_pairs = array();
		foreach($this->collected_gateway_data as $key=>$value) {
			//$output .= $key.'='.urlencode($value).$amp;
			$name_value_pairs[]= $key.'='.urlencode($value);
		}
		$gateway_values =  implode('&', $name_value_pairs);

		
		if(defined('WPSC_ADD_DEBUG_PAGE') and (WPSC_ADD_DEBUG_PAGE == true) ) {
// 			echo "<a href='".get_option('paypal_multiple_url')."?".$gateway_values."'>Test the URL here</a>";
// 	  	echo "<pre>".print_r($gateway_values,true)."</pre>";
// 	   	echo "<pre>".print_r($this,true)."</pre>";
// 	  	exit();
		}

		
		$options = array(
			'timeout' => 10,
			'body' => $this->collected_gateway_data,
			'user-agent' => $this->cart_data['software_name'] ." " . get_bloginfo( 'url' ),
			'sslverify' => false
		);
		$options['body']['x_relay_response'] = "FALSE";
		$options['body']['x_delim_data'] = "TRUE";

		$wdsl_url = "https://api.authorize.net/soap/v1/Service.asmx?WSDL";
		if((bool)get_option('authorize_testmode') == true) {
			$authorize_url = "https://test.authorize.net/gateway/transact.dll";
			$service_url = "https://apitest.authorize.net/soap/v1/Service.asmx";
		} else {
			$authorize_url = "https://secure.authorize.net/gateway/transact.dll";
			$service_url = "https://api.authorize.net/soap/v1/Service.asmx";
		}
		
		$response = wp_remote_post($authorize_url, $options);
		if( is_wp_error( $response ) ) {
			// echo "teh broken";
		} else {
			$split_response = explode(",",$response['body']); // Splits out the buffer return into an array so . . .
			$parsed_response = $this->parse_aim_response($split_response);
		}
		//echo "<pre>";
		//print_r($parsed_response);
		//echo "</pre>";
		//exit();
		//$parsed_response['response_code'] = 1;
		switch($parsed_response['response_code']) {
			case 1: /// case 1 is order accepted,
			case 4: /// case 4 is order held for review
			if(count($this->arb_requests) > 0) {

				foreach($this->arb_requests as $cart_item_id => $arb_request) {
					$subscription_results = $this->do_soap_request('ARBCreateSubscription', $arb_request);
					
					if($subscription_id = $subscription_results['ARBCreateSubscriptionResult']['resultCode'] == "Ok") {
						$subscription_id = $subscription_results['ARBCreateSubscriptionResult']['subscriptionId'];
						do_action('wpsc_activate_subscription', $cart_item_id, $subscription_id);
					} else {
						$subscription_error['code'] = $subscription_results['ARBCreateSubscriptionResult']['messages']['MessagesTypeMessage']['code'];
						$subscription_error['description'] = $subscription_results['ARBCreateSubscriptionResult']['messages']['MessagesTypeMessage']['text'];
						wpsc_update_cartmeta($cart_item_id, 'subscription_error', $subscription_error);
						wpsc_update_cartmeta($cart_item_id, 'is_subscribed', 0);
						
					}
				wpsc_update_cartmeta($cart_item_id, 'subscription_report', $subscription_results);
				}

				
/*					echo "<pre>";
					//print_r($arb_client);
					print_r($subscription_results);
					//print_r($arb_request);
					echo "</pre>";
					exit()*/;
			}
			$status = 1;
			if($parsed_response['response_code'] ==  1) {
				$status = 2;
			}
			$this->set_transaction_details($parsed_response['transaction_id'], $status);
			transaction_results($this->cart_data['session_id'],false);
			$this->go_to_transaction_results($this->cart_data['session_id']);
			break;

			case 2: /// case 2 is order denied
			case 3: /// case 3 is error state
			default: /// default is http or unknown error state
			if($parsed_response['response_description'] == '') { // If there is no error message it means there was some sort of HTTP connection failure, use the following error message
			  $parsed_response['response_description'] = __("There was an error contacting the payment gateway, please try again later.", 'wpsc');
			}
			$this->set_error_message($parsed_response['response_description']);
			$this->return_to_checkout();
			break;
		}
	}

	
	/**
	* parse AIM response, translate numeric keys into meaningful names.
	* @access public
	*/
	function parse_aim_response($split_response) {
		$parsed_response = array();
		foreach($split_response as $key => $response_item) {
			if(isset($this->aim_response_keys[($key+1)])) {
				$parsed_response[$this->aim_response_keys[($key+1)]] = $response_item;
			}
		}
		return 	$parsed_response;
	}

	/**
	* construct ARB Array, constructs the array for the ARB SOAP requests
	* @access public
	*/
	function construct_arb_array(&$cart_item) {
	  //print_r($cart_item);

	  /// Authorize.net ARB accepts days or months, nothing else
	  switch($cart_item['recurring_data']['rebill_interval']['unit']) {
	  	case "w":
	  	$arb_length = (int)$cart_item['recurring_data']['rebill_interval']['length'] * 7;
	  	$arb_unit = 'days';
	  	break;
	  	
	  	case "y":
	  	$arb_length = (int)$cart_item['recurring_data']['rebill_interval']['length'] / 12;
	  	$arb_unit = 'months';
	  	break;

	  	
	  	case "m":
	  	default:
	  	$arb_length = $cart_item['recurring_data']['rebill_interval']['length'];
	  	$arb_unit = 'months';
	  	break;
	  }
		if($cart_item['recurring_data']['charge_to_expiry'] !== true) {
			$arb_times_to_rebill = $cart_item['recurring_data']['times_to_rebill'];
	  } else {
			/// If subscription is permanent, rebill over 9000 times
	  	$arb_times_to_rebill = 9999;
	  }
	  if($arb_times_to_rebill > 1) {
			$arb_times_to_rebill--;
	  }

	  
		$arb_body = array(
			/// Authentication Details go here
			'merchantAuthentication'=>array(
				'name'=>get_option('authorize_login'),
				'transactionKey'=>get_option("authorize_password")
			)	,
			'subscription' => array(
				/// Name goes here
				'name' =>$cart_item['name'],
				/// Amount goes here
				'amount' => number_format($cart_item['price'],2,'.',''),
				'trialAmount' => number_format(0,2,'.',''),

				/// Payment Schedule goes here
				'paymentSchedule' => array(
					'interval' => array(
						'length' => $arb_length,
						'unit' => $arb_unit
					),
					'startDate' => gmdate("Y-m-d"),
					'totalOccurrences' => $arb_times_to_rebill,
					'trialOccurrences' => '1'
				),
				/// Payment Details go here
				'payment' => array(
					'creditCard' => array(
						'cardNumber' => $this->credit_card_details['card_number'],
						'expirationDate' => $this->credit_card_details['expiry_date']['month']."-".$this->credit_card_details['expiry_date']['year'],
						'cardCode' => $this->credit_card_details['card_code']
					)
				),
				/// Customer Details go Here
				'order' => array(
					//'invoiceNumber' => $this->cart_data['session_id']."123",
					'description' => ''
				),
				/// Customer Details go Here
				'customer' => array(
					//'id' => 1,
					'email' => $this->cart_data['email_address']
				),
				/// Billing Address Details go here
				'billTo' => array(
					'firstName' => $this->cart_data['billing_address']['first_name'],
					'lastName' => $this->cart_data['billing_address']['last_name'],
					'address' => $this->cart_data['billing_address']['address'],
					'city' => $this->cart_data['billing_address']['city'],
					//'state' => '',
					'zip' => $this->cart_data['billing_address']['post_code'],
					'country' => $this->cart_data['billing_address']['country']
				)
			)
		);
		return $arb_body;
	}

	/**
	* cancel_subscription, cancels a subscription.
	* @access public
	*/
	function cancel_subscription($cart_id, $subscription_id) {
		$arb_body = array(
			/// Authentication Details go here
			'merchantAuthentication'=>array(
				'name'=>get_option('authorize_login'),
				'transactionKey'=>get_option("authorize_password")
			)	,
			'subscriptionId' => $subscription_id
		);
		
		$subscription_results = $this->do_soap_request('ARBCancelSubscription', $arb_body);

		if($subscription_results['ARBCancelSubscriptionResult']['resultCode'] == "Ok") {
			wpsc_update_cartmeta($cart_id, 'is_subscribed', 0);
		}
	}

	/**
	* Do SOAP request wrapper function
	* can use either the built in PHP library, or nusoap
	*/
	function do_soap_request($function, $arguments) {
		$wdsl_url = "https://api.authorize.net/soap/v1/Service.asmx?WSDL";
		if((bool)get_option('authorize_testmode') == true) {
			$service_url = "https://apitest.authorize.net/soap/v1/Service.asmx";
		} else {
			$service_url = "https://api.authorize.net/soap/v1/Service.asmx";
		}
		
		$function = (string)$function;

		if(@extension_loaded('soap')) { // Check to see if PHP-SOAP is loaded, if so, use that
		  if(($this->soap_client == null) || !is_a($this->soap_client, 'SoapClient')) {
				$this->soap_client = @ new SoapClient($wdsl_url, array('soap_version' => SOAP_1_2, 'trace' => 1));
			}
			$this->soap_client->__setLocation($service_url);
			$returned_data = $this->soap_client->__soapCall($function, array($function => $arguments));
		} else { // otherwise include and use nusoap
		  if(($this->soap_client == null) || !is_a($this->soap_client, 'soapclient')) {
				include_once(WPSC_FILE_PATH.'/wpsc-includes/nusoap/nusoap.php');
				$this->soap_client = new soapclient($wdsl_url, true);
			}
			$this->soap_client->setEndpoint($service_url);
			$subscription_results = $this->soap_client->call($function, $arguments);
		}

		$returned_data = wpsc_object_to_array($returned_data);
		return $returned_data;
	}
}
?>