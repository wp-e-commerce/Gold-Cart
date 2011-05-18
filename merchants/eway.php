<?php
if (!version_compare(phpversion(), "5.0.0", ">=") || !is_callable('get_option')) {  
  // This code absolutely does not work in anything less than PHP 5, therefore, if we are using less than that, we break out of the file here.
  // This is also here to stop error messages on servers with Zend Accelerator, it includes all files before get_option is declared
  // then evidently includes them again, otherwise this code would break these modules
  return;
  exit("Something strange is happening, and \"return\" is not breaking out of a file.");
}
$nzshpcrt_gateways[$num]['name'] = 'eWay';
$nzshpcrt_gateways[$num]['internalname'] = 'eway';
$nzshpcrt_gateways[$num]['function'] = 'gateway_eway';
$nzshpcrt_gateways[$num]['form'] = "form_eway";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_eway";
$nzshpcrt_gateways[$num]['payment_type'] = "credit_card";

if(in_array('eway',(array)get_option('custom_gateway_options'))) {
	$gateway_checkout_form_fields[$nzshpcrt_gateways[$num]['internalname']] = "
	<tr>
		<td> Credit Card Number * </td>
		<td>
			<input type='text' value='' name='card_number' />
		</td>
	</tr>
	<tr>
		<td> Credit Card Expiry * </td>
		<td>
		<input type='text' size='2' value='' maxlength='2' name='expiry[month]' />/<input type='text' size='2'  maxlength='2' value='' name='expiry[year]' />
		</td>
	</tr> ";
	if (get_option('eway_cvn')) {
		$gateway_checkout_form_fields[$nzshpcrt_gateways[$num]['internalname']] .= "
		<tr>
			<td> CVN </td>
			<td>
				<input type='text' size='4'  maxlength='4' value='' name='cvn' />
			</td>
		</tr>
		";
	}
}

function gateway_eway($seperator, $sessionid) {
	global $wpdb, $wpsc_cart;
	$purchase_log_sql = "SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid`= '".$sessionid."' LIMIT 1";
	$purchase_log = $wpdb->get_results($purchase_log_sql,ARRAY_A) ;
	$purchase_log=$purchase_log[0];

	$cart_sql = "SELECT * FROM `".WPSC_TABLE_CART_CONTENTS."` WHERE `purchaseid`='".$purchase_log['id']."'";
	$cart = $wpdb->get_results($cart_sql,ARRAY_A) ;
	$member_subtype = get_product_meta($cart[0]['prodid'],'is_permenant',true);
	$member_shiptype = get_product_meta($cart[0]['prodid'],'membership_length',true);
	$member_shiptype = $member_shiptype[0];
	$status = get_product_meta($cart[0]['prodid'],'is_membership',true);
	$is_member = $status;
	$is_perm = $member_subtype;
	if($_POST['collected_data'][get_option('eway_form_first_name')] != '') {
		$data['first_name'] = esc_attr($_POST['collected_data'][get_option('eway_form_first_name')]);
	}

	if($_POST['collected_data'][get_option('eway_form_last_name')] != '') {
		$data['last_name'] = esc_attr($_POST['collected_data'][get_option('eway_form_last_name')]);
	}

	if($_POST['collected_data'][get_option('eway_form_address')] != '') {
		$address_rows = explode("\n\r",$_POST['collected_data'][get_option('eway_form_address')]);
		$data['address1'] = esc_attr(str_replace(array("\n", "\r"), '', $address_rows[0]));
		unset($address_rows[0]);
		if($address_rows != null) {
			$data['address2'] = implode(", ",$address_rows);
		} else {
			$data['address2'] = '';
		}
	}
	//exit('<pre>'.print_r($wpsc_cart, true).'</pre>');
	foreach($wpsc_cart->cart_items as $item){
		$itemsName .= $item->product_name.', ';
		//exit('<pre>'.print_r($item,true).'</pre>');
	}


	if($_POST['collected_data'][get_option('eway_form_city')] != '') {
		$data['city'] = esc_attr($_POST['collected_data'][get_option('eway_form_city')]); 
	}

	if( empty( $_POST['collected_data'][get_option('eway_form_state')] ) && isset( $_POST['collected_data'][get_option('eway_form_country')][1]) && !empty( $_POST['collected_data'][get_option('eway_form_country')][1])) {
		$data['state'] = $_POST['collected_data'][get_option('eway_form_country')][1];
	}elseif(!empty( $_POST['collected_data'][get_option('eway_form_state')] ) ){
		$data['state'] = $_POST['collected_data'][get_option('eway_form_state')];
	}

	if($_POST['collected_data'][get_option('eway_form_country')]!='') {
		$data['country'] = $_POST['collected_data'][get_option('eway_form_country')][0];
	}

	if(is_numeric($_POST['collected_data'][get_option('eway_form_post_code')])) {
		$data['zip'] =  esc_attr($_POST['collected_data'][get_option('eway_form_post_code')] );
	}
	if($_POST['collected_data'][get_option('eway_form_email')]) {
		$data['email'] =  $_POST['collected_data'][get_option('eway_form_email')];
	}

	if(($_POST['collected_data'][get_option('email_form_field')] != null) && ($data['email'] == null)) {
		$data['email'] = esc_attr( $_POST['collected_data'][get_option('email_form_field')] );
	}
	// Live or Test Server?
	if (get_option('eway_test')) {
		$user = '87654321';
		$gateway = false;
	} else {
		$user = get_option('ewayCustomerID_id');
		$gateway = true;
	}

	if ($is_member[0]) {
	
		require_once(WPSC_GOLD_FILE_PATH.'/ewaylib/GatewayConnector.php');
	
		$objRebill = new RebillPayment();
		
		$objRebill->CustomerRef($purchase_log['id']);
		
		$objRebill->CustomerTitle('');
		
		$objRebill->CustomerFirstName($data['first_name']);
		
		$objRebill->CustomerLastName($data['last_name']);
		
		$objRebill->CustomerCompany('');
		
		$objRebill->CustomerJobDesc('');
		
		$objRebill->CustomerEmail($data['email']);
		
		$objRebill->CustomerAddress($data['address1']);
		
		$objRebill->CustomerSuburb('');
		
		$objRebill->CustomerState($data['state']);
		
		$objRebill->CustomerPostCode($data['zip']);
		
		$objRebill->CustomerCountry($data['country']);
		
		$objRebill->CustomerPhone1($data['phone']);
		
		$objRebill->CustomerPhone2('');
		
		$objRebill->CustomerFax('');
		
		$objRebill->CustomerURL('');
		
		$objRebill->CustomerComments('');
		
		$objRebill->RebillInvRef('');
		
		$objRebill->RebillInvDesc('');
		
		$objRebill->RebillCCname($data['first_name']." ".$data['last_name']);
		
		$objRebill->RebillCCNumber($_POST['card_number']);
		
		$objRebill->RebillInitAmt($purchase_log['totalprice']);
		
		$objRebill->RebillInitDate(date('d/m/Y'));
		
		$objRebill->RebillRecurAmt($purchase_log['totalprice']);
		
		$objRebill->RebillStartDate(date('d/m/Y'));
		
		$objRebill->RebillEndDate(date("d/m/Y", mktime(0, 0, 0, date('m'), date('d'), (int)date('Y')+1)));
		
		$objRebill->RebillCCExpMonth($_POST['expiry']['month']);
		
		$objRebill->RebillCCExpYear($_POST['expiry']['year']);
		
		$objRebill->RebillInterval($member_shiptype['length']);
		switch($member_shiptype['unit']) {
			case 'd':
				$member_ship_unit = '1';
				break;
			
			case 'w':
				$member_ship_unit = '2';
				break;
		
			case 'm':
				$member_ship_unit = '3';
				break;
		
			case 'y':
				$member_ship_unit = '4';
				break;
		}
		$objRebill->RebillIntervalType($member_ship_unit);
		
		$objRebill->eWAYCustomerID($user);
		
		$objConnector = new GatewayConnector($gateway);
		
		if ($objConnector->ProcessRequest($objRebill)) {
			$objResponse = $objConnector->Response();
			
			if ($objResponse != null) {
				$lblResult = $objResponse->Result();
				if ($lblResult=='Success') {
					wpsc_member_activate_subscriptions($purchase_log['id']);
					$_SESSION['nzshpcrt_cart'] = '';
					$_SESSION['nzshpcrt_cart'] = Array();
					header("Location:".get_option('product_list_url'));
				}
				$lblErrorDescription = $objResponse->ErrorDetails();
				$lblErrorSeverity = $objResponse->ErrorSeverity();
				
				// This is woefully inadequate!!!
				exit('An Error has occured >'.$lblResult ." ". $lblErrorDescription ." ". $lblErrorSeverity);
			}
		} else {
			exit("Rebill Gateway failed: " . $objConnector->Response() );
		}
		
	} else {

		require(WPSC_GOLD_FILE_PATH.'/merchants/ewaylib/EwayPaymentLive.php');
		//echo WPSC_GOLD_FILE_PATH.'/ewaylib/EwayPaymentLive.php';
		if (get_option('eway_cvn')) {
			$method = 'REAL_TIME_CVN';
		} else {
			$method = 'REAL_TIME';
		}

		$eway = new EwayPaymentLive($user, $method, $gateway);
		$amount = number_format($purchase_log['totalprice'], 2, '.', '')*100;
		$eway->setTransactionData("TotalAmount", $amount); //mandatory field
		$eway->setTransactionData("CustomerFirstName", $data['first_name']);
		$eway->setTransactionData("CustomerLastName", $data['last_name']);
		$eway->setTransactionData("CustomerEmail", $data['email']);
		$eway->setTransactionData("CustomerAddress", $data['address1'] . ' ' . $data['state']);
		$eway->setTransactionData("CustomerPostcode", $data['zip']);
		$eway->setTransactionData("CustomerInvoiceDescription", $itemsName);
		$eway->setTransactionData("CustomerInvoiceRef", $purchase_log['id']);
		$eway->setTransactionData("CardHoldersName", $data['first_name'].' '.$data['last_name']); //mandatory field
		$eway->setTransactionData("CardNumber", $_POST['card_number']); //mandatory field
		$eway->setTransactionData("CardExpiryMonth", $_POST['expiry']['month']); //mandatory field
		$eway->setTransactionData("CardExpiryYear", $_POST['expiry']['year']); //mandatory field
		$eway->setTransactionData("TrxnNumber", $purchase_log['id']);
		$eway->setTransactionData("Option1", "");
		$eway->setTransactionData("Option2", "");
		$eway->setTransactionData("Option3", "");
		//for REAL_TIME_CVN
		$eway->setTransactionData("CVN", $_POST['cvn']);
	
		//for GEO_IP_ANTI_FRAUD
		$eway->setTransactionData("CustomerIPAddress", $eway->getVisitorIP()); //mandatory field when using Geo-IP Anti-Fraud
		$eway->setTransactionData("CustomerBillingCountry", $data['country']); //mandatory field when using Geo-IP Anti-Fraud
		//special preferences for php Curl
		$eway->setCurlPreferences(CURLOPT_SSL_VERIFYPEER, 0);  //pass a long that is set to a zero value to stop curl from verifying the peer's certificate 
		//$eway->setCurlPreferences(CURLOPT_CAINFO, "/usr/share/ssl/certs/my.cert.crt"); //Pass a filename of a file holding one or more certificates to verify the peer with. This only makes sense when used in combination with the CURLOPT_SSL_VERIFYPEER option. 
		//$eway->setCurlPreferences(CURLOPT_CAPATH, "/usr/share/ssl/certs/my.cert.path");
		//$eway->setCurlPreferences(CURLOPT_PROXYTYPE, CURLPROXY_HTTP); //use CURL proxy, for example godaddy.com hosting requires it
		//$eway->setCurlPreferences(CURLOPT_PROXY, "http://proxy.shr.secureserver.net:3128"); //use CURL proxy, for example godaddy.com hosting requires it

		$ewayResponseFields = $eway->doPayment();
		//exit(print_r($ewayResponseFields,1));
		//print_r($ewayResponseFields);
		if($ewayResponseFields["EWAYTRXNSTATUS"]=="False"){
			$message .= "<h3>Please Check the Payment Results</h3>";
			$message .= "Your transaction was not successful."."<br><br>";
			$message .= $ewayResponseFields['EWAYTRXNERROR']."<br><br>";
			$message .= "<a href=".get_option('shopping_cart_url').">Click here to go back to checkout page.</a>";
			$_SESSION['eway_message'] = $message;
			header("Location:".get_option('transact_url').$seperator."eway=0&result=".$sessionid."&message=1");
			//exit();		
		}else if($ewayResponseFields["EWAYTRXNSTATUS"]=="True"){
			$wpdb->query("UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `processed`='3' WHERE `sessionid`='".$sessionid."' LIMIT 1");
			transaction_results($sessionid, false);
			$message .= "Your transaction was successful."."<br><br>";
			$message .= $ewayResponseFields['EWAYTRXNERROR']."<br><br>";
			$_SESSION['eway_message'] = $message;
			header("Location:".get_option('transact_url').$seperator."eway=1&result=".$sessionid."&message=1");
			//exit();
		}
		
	}
	
	//echo $_SESSION['eway_message'];
	exit();
}

function submit_eway() {
	$options = array(
		'ewayCustomerID_id',
		'eway_cvn',
		'eway_test',
	);
	foreach ( $options as $option ) {
		if ( ! empty( $_POST[$option] ) )
			update_option( $option, $_POST[$option] );
	}
	if ( ! empty( $_POST['eway_form'] ) ) {
		foreach((array)$_POST['eway_form'] as $form => $value) {
			update_option(('eway_form_'.$form), $value);
		}
	}
	return true;
}
  
function form_eway() {
	$eway_cvn = get_option('eway_cvn');
	$eway_cvn1 = $eway_cvn2 = '';
	if ($eway_cvn=='1') {
		$eway_cvn1="checked='checked'";
	} else {
		$eway_cvn2="checked='checked'";
	}
	$eway_test = get_option('eway_test');
	$eway_test1 = $eway_test2 = '';
	if ($eway_test=='1') {
		$eway_test1="checked='checked'";
	} else {
		$eway_test2="checked='checked'";
	}
	$output = "
	<tr>
		<td>
			eWay Customer id
		</td>
		<td>
			<input type='text' size='10' value='".get_option('ewayCustomerID_id')."' name='ewayCustomerID_id' />
		</td>
	</tr>
	<tr>
		<td>
			Use Testing enviroment
		</td>
		<td>
			<input type='radio' value='1' name='eway_test' id='eway_test1' ".$eway_test1." /> <label for='eway_test1'>".TXT_WPSC_YES."</label> &nbsp;
			<input type='radio' value='0' name='eway_test' id='eway_test2' ".$eway_test2." /> <label for='eway_test2'>".TXT_WPSC_NO."</label>
		</td>
	</tr>
	<tr>
		<td>
			Use CVN Security
		</td>
		<td>
			<input type='radio' value='1' name='eway_cvn' id='eway_cvn1' ".$eway_cvn1." /> <label for='eway_cvn1'>".TXT_WPSC_YES."</label> &nbsp;
			<input type='radio' value='0' name='eway_cvn' id='eway_cvn2' ".$eway_cvn2." /> <label for='eway_cvn2'>".TXT_WPSC_NO."</label>
		</td>
	</tr>";
	$output .= "
		<tr>
			<td>First Name Field</td>
			<td>
				<select name='eway_form[first_name]'>
					".nzshpcrt_form_field_list(get_option('eway_form_first_name'))."
				</select>
			</td>
		</tr>
		<tr>
			<td> Last Name Field </td>
			<td>
				<select name='eway_form[last_name]'>
					".nzshpcrt_form_field_list(get_option('eway_form_last_name'))."
				</select>
			</td>
		</tr>
		<tr>
			<td> Address Field </td>
			<td>
				<select name='eway_form[address]'>
					".nzshpcrt_form_field_list(get_option('eway_form_address'))."
				</select>
			</td>
		</tr>
		<tr>
			<td> City Field </td>
			<td>
				<select name='eway_form[city]'>
					".nzshpcrt_form_field_list(get_option('eway_form_city'))."
				</select>
			</td>
		</tr>
		<tr>
			<td> State Field </td>
			<td>
				<select name='eway_form[state]'>
					".nzshpcrt_form_field_list(get_option('eway_form_state'))."
				</select>
			</td>
		</tr>
		<tr>
			<td> Postal code/Zip code Field </td>
			<td>
				<select name='eway_form[post_code]'>
					".nzshpcrt_form_field_list(get_option('eway_form_post_code'))."
				</select>
			</td>
		</tr>
		<tr>
			<td> Country Field </td>
			<td>
				<select name='eway_form[country]'>
					".nzshpcrt_form_field_list(get_option('eway_form_country'))."
				</select>
			</td>
		</tr>
				<tr>
			<td> Email Field </td>
			<td>
				<select name='eway_form[email]'>
					".nzshpcrt_form_field_list(get_option('eway_form_email'))."
				</select>
			</td>
		</tr>";
	return $output;
}
 ?>