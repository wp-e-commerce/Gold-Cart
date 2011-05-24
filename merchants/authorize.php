<?php
if(!is_callable('get_option')) {
  // This is here to stop error messages on servers with Zend Accelerator, it includes all files before get_option is declared
  // then evidently includes them again, otherwise this code would break these modules
  return;
  exit("Something strange is happening, and \"return\" is not breaking out of a file.");
}

/*
$nzshpcrt_gateways[$num]['name'] = 'Authorize.net';
$nzshpcrt_gateways[$num]['internalname'] = 'authorize';
$nzshpcrt_gateways[$num]['function'] = 'gateway_authorize';
$nzshpcrt_gateways[$num]['form'] = "form_authorize";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_authorize";
$nzshpcrt_gateways[$num]['payment_type'] = "credit_card";
*/

//include_once(ABSPATH.'wp-content/plugins/wp-shopping-cart/classes/authorize_class.php');
//if(get_option('payment_gateway') == 'authorize') {
if(in_array('authorize',(array)get_option('custom_gateway_options'))) {
	$gateway_checkout_form_fields[$nzshpcrt_gateways[$num]['internalname']] = "
	<tr %s>
		<td>Credit Card Number *</td>
		<td>
			<input type='text' value='' name='card_number' />
			<p class='validation-error'>%s</p>
		</td>
	</tr>
	<tr %s>
		<td>Credit Card Expiry *</td>
		<td>
			<input type='text' size='2' value='' maxlength='2' name='expiry[month]' />/<input type='text' size='2'  maxlength='2' value='' name='expiry[year]' />
			<p class='validation-error'>%s</p>
		</td>
	</tr>
	<tr %s>
		<td>CVV </td>
		<td><input type='text' size='4' value='' maxlength='4' name='card_code' />
		<p class='validation-error'>%s</p>
		</td>
		
	</tr>
";
  }

function gateway_authorize($seperator, $sessionid) {
global $wpdb,$wpsc_cart;
$purchase_log_sql = "SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid`= ".$sessionid." LIMIT 1";
$purchase_log = $wpdb->get_row($purchase_log_sql,ARRAY_A);
$cart_sql = "SELECT * FROM `".WPSC_TABLE_CART_CONTENTS."` WHERE `purchaseid`='".$purchase_log['id']."'";
$cart = $wpdb->get_results($cart_sql,ARRAY_A);
$prodid=$cart[0]['prodid'];
$product_sql = "SELECT * FROM `".WPSC_TABLE_PRODUCT_LIST."` WHERE `id`='".$prodid."'";
$product_data = $wpdb->get_results($product_sql,ARRAY_A);
$status = get_product_meta($prodid,'is_membership',true);
$free_trial = get_product_meta($prodid,'free_trial',true);
if (($status[0] == 1) && function_exists('wpsc_members_init')) {
	$membership_length = get_product_meta($prodid,'membership_length',true);
	$membership_length = $membership_length[0];
	$length = $membership_length['length'];
	$unit = $membership_length['unit'];
	if ($unit == 'd') {
		$unit='days';
	} elseif ($unit == 'm') {
		$unit='months';
	}
	$amount = nzshpcrt_overall_total_price($_SESSION['selected_country']);
	$loginname = get_option('authorize_login');
	$transactionkey = get_option("authorize_password");
	$firstName = $_POST['collected_data'][get_option('authorize_form_first_name')];
	$lastName = $_POST['collected_data'][get_option('authorize_form_last_name')];
	$cardNumber = $_POST['card_number'];
	$expirationDate ="20" . $_POST['expiry']['year']."-".$_POST['expiry']['month'] ;
	$cardCode = $_POST['card_code'];
	$startDate=date('Y-m-d');
	$totalOccurrences = 99;
	$trialOccurrences =1;
	$amount = $product_data[0]['price'];
	$trialAmount = 0;

	$xml = "<?xml version='1.0' encoding='utf-8' ?>".
	"<ARBCreateSubscriptionRequest xmlns='AnetApi/xml/v1/schema/AnetApiSchema.xsd'>".
		"<merchantAuthentication>".
			"<name>" . $loginname . "</name>".
			"<transactionKey>" . $transactionkey . "</transactionKey>".
		"</merchantAuthentication>".
		"<refId>Instinct</refId>".
		"<subscription>".
			"<name>Samplesubscription</name>".
				"<paymentSchedule>".
					"<interval>".
						"<length>". $length ."</length>".
						"<unit>". $unit ."</unit>".
					"</interval>".
					"<startDate>" . $startDate . "</startDate>".
					"<totalOccurrences>". $totalOccurrences . "</totalOccurrences>".
					"<trialOccurrences>". $trialOccurrences . "</trialOccurrences>".
				"</paymentSchedule>".
			"<amount>". $amount ."</amount>".
			"<trialAmount>" . $trialAmount . "</trialAmount>".
			"<payment>".
				"<creditCard>".
					"<cardNumber>" . $cardNumber . "</cardNumber>".
					"<expirationDate>" . $expirationDate . "</expirationDate>".
					"<cardCode>" . $cardCode . "</cardCode>".
				"</creditCard>".
			"</payment>".
			"<billTo>".
				"<firstName>". $firstName . "</firstName>".
				"<lastName>" . $lastName . "</lastName>".
			"</billTo>".
		"</subscription>".
	"</ARBCreateSubscriptionRequest>";
//  	exit("<pre>".print_r($xml,1)."</pre>");

	//Send the XML via curl
	$response = send_request_via_curl($host,$path,$xml);
	//If curl is unavilable you can try using fsockopen
	/*
	$response = send_request_via_fsockopen($host,$path,$content);
	*/
	//If the connection and send worked $response holds the return from Authorize.Net
	if ($response) {
		list ($refId, $resultCode, $code, $text, $subscriptionId) =parse_return($response);
		if ($code == 'I00001') {
			$wpdb->query("UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `processed` = '2' WHERE `sessionid` = ".$sessionid." LIMIT 1");
			$results=$wpdb->get_results("select * from `".WPSC_TABLE_LOGGED_SUBSCRIPTIONS."` where cart_id=".$cart[0]['id']."",ARRAY_A);
			$sub_id=$results[0]['id'];
			wpsc_member_activate_subscriptions($sub_id);
			header("Location: ".get_option('transact_url').$seperator."sessionid=".$sessionid);
		} else {
			echo " refId: $refId<br>";
			echo " resultCode: $resultCode <br>";
			echo " code: $code<br>";
			echo " text: $text<br>";
			echo " subscriptionId: $subscriptionId <br><br>";
		}
	} else {
		echo "send failed <br>";
	}
	
	//Dump the response to the screen for debugging
	//echo "<xmp>$response</xmp>";  //Display response SOAP
	exit('');
}



if($purchase_log['shipping_country'] != null) {
	$shipping_country = $purchase_log['shipping_country'];

} 
if($purchase_log['shipping_region'] != null) {
	$shipping_region = $purchase_log['shipping_region'];

}else{
	$shipping_region = 0;
} 
if($purchase_log['billing_country'] != null) {
	$billing_country = $purchase_log['billing_country'];

} 
if($purchase_log['billing_region'] != null) {
	$billing_region = $purchase_log['billing_region'];
  $billing_region=$wpdb->get_var("SELECT code FROM `".WPSC_TABLE_REGION_TAX."` WHERE id='".$billing_region."'");


}else{
	$billing_region = 0;
} 

$authorize_data = array();
$authorize_data['x_Version'] = "3.1";
$authorize_data['x_Login'] = urlencode(get_option('authorize_login'));
$authorize_data['x_Password'] = urlencode(get_option("authorize_password"));
$authorize_data['x_Delim_Data'] = urlencode("TRUE"); 
$authorize_data['x_Delim_Char'] = urlencode(","); 
$authorize_data['x_Encap_Char'] = urlencode(""); 
$authorize_data['x_Type'] = urlencode("AUTH_CAPTURE"); 

$authorize_data['x_ADC_Relay_Response'] = urlencode("FALSE"); 
if(get_option('authorize_testmode') == 1) {
	$authorize_data['x_Test_Request'] = urlencode("TRUE");
}
$authorize_data['x_Method'] = urlencode("CC");
$authorize_data['x_Amount'] = number_format(nzshpcrt_overall_total_price($_SESSION['delivery_country'],false,false),2);
$authorize_data['x_First_Name'] = urlencode($_POST['collected_data'][get_option('authorize_form_first_name')]); 
$authorize_data['x_Last_Name'] = urlencode($_POST['collected_data'][get_option('authorize_form_last_name')]); 
$authorize_data['x_Card_Num'] = urlencode($_POST['card_number']); 
$authorize_data['x_Exp_Date'] = urlencode(($_POST['expiry']['month'] . $_POST['expiry']['year'])); 
$authorize_data['x_Card_Code'] = urlencode($_POST['card_code']);
$authorize_data['x_Address'] = urlencode($_POST['collected_data'][get_option('authorize_form_address')]); 
$authorize_data['x_City'] = urlencode($_POST['collected_data'][get_option('authorize_form_city')]); 
$authorize_data['x_Zip'] = urlencode($_POST['collected_data'][get_option('authorize_form_post_code')]); 
$authorize_data['x_State'] = urlencode($billing_region);
$authorize_data['x_Country'] = urlencode($billing_country);
$authorize_data['x_Phone'] = urlencode($_POST['collected_data'][get_option('authorize_form_phone')]);





$authorize_data['x_Email'] = urlencode($_POST['collected_data'][get_option('authorize_form_email')]); 
$authorize_data['x_Email_Customer'] = urlencode("TRUE"); 
$authorize_data['x_Merchant_Email'] = urlencode(get_option('purch_log_email'));


	// MY ADDITIONS HERE
  $authorize_data['x_Description'] = urlencode(get_option('authorize_form_description'));
  $authorize_data['x_invoice_num'] = $cart[0]['purchaseid'];
  $authorize_data['x_cust_id'] = $cart[0]['purchaseid'];
  $setstate=$_POST['collected_data'][get_option('authorize_form_country')][1];
  $setstate=$wpdb->get_var("SELECT code FROM `".WPSC_TABLE_REGION_TAX."` WHERE id='".$setstate."'");
  //  $authorize_data['x_State'] = urlencode($setstate);
  $setcountry=$_POST['collected_data'][get_option('authorize_form_country')][0];
  //  $authorize_data['x_Country'] = urlencode($setcountry);
  $authorize_data['x_ship_to_First_Name'] = urlencode($_POST['collected_data'][get_option('authorize_form_ship_first_name')]); 
  $authorize_data['x_ship_to_Last_Name'] = urlencode($_POST['collected_data'][get_option('authorize_form_ship_last_name')]); 
  $authorize_data['x_ship_to_Address'] = urlencode($_POST['collected_data'][get_option('authorize_form_ship_address')]); 
  $authorize_data['x_ship_to_City'] = urlencode($_POST['collected_data'][get_option('authorize_form_ship_city')]);
  $authorize_data['x_ship_to_Zip'] = urlencode($_POST['collected_data'][get_option('authorize_form_ship_post_code')]); 
  $setstate=$_POST['collected_data'][get_option('authorize_form_ship_state')];
  $shipping_region=$wpdb->get_var("SELECT code FROM `".WPSC_TABLE_REGION_TAX."` WHERE id='".$shipping_region."'");
  $authorize_data['x_ship_to_State'] = urlencode($shipping_region);
//  $setcountry=$_POST['collected_data'][get_option('authorize_form_ship_country')];	
  $authorize_data['x_ship_to_Country'] = urlencode($shipping_country);
  $authorize_data['x_tax'] = urlencode($wpsc_cart->total_tax);	
  if(wpsc_uses_shipping()){
  	$authorize_data['x_freight'] = urlencode($wpsc_cart->selected_shipping_method  . '<|>' . $wpsc_cart->selected_shipping_option . '<|>' . $wpsc_cart->base_shipping);
  }
	// Extra shopping cart data for credit card receipt
	if (isset($cart[0])) {
  	foreach ($cart as $k=>$v) {
  		$authorize_data['item_' . $k . '_name'] = $v['name'];
  		$authorize_data['item_' . $k . '_qty'] = $v['quantity'];
  		$authorize_data['item_' . $k . '_price'] = $v['price'];		
  	}
	}
  
if($x_Password!='') { 
	$authorize_data['x_Password']=$x_Password;
}

  #
  # Build fields string to post, nicer than the old code
  #
$num = 0;
foreach($authorize_data as $key => $value) {
	if($num > 0) { 
		$fields .= "&"; 
	}
	$fields .= $key."=".$value;
	$num++;
}
    
  # 
  # Start CURL session 
  # 
  $user_agent = "WP eCommerce plugin for Wordpress"; 
  $referrer = get_option('transact_url');
  
  $ch=curl_init(); 
  curl_setopt($ch, CURLOPT_URL, "https://secure.authorize.net/gateway/transact.dll"); 
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
  curl_setopt($ch, CURLOPT_NOPROGRESS, 1); 
  curl_setopt($ch, CURLOPT_VERBOSE, 1); 
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION,0); 
  curl_setopt($ch, CURLOPT_POST, 1); 
  curl_setopt($ch, CURLOPT_POSTFIELDS, $fields); 
  curl_setopt($ch, CURLOPT_TIMEOUT, 120); 
  curl_setopt($ch, CURLOPT_USERAGENT, $user_agent); 
  curl_setopt($ch, CURLOPT_REFERER, $referrer); 
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

  $buffer = curl_exec($ch);
  curl_close($ch);

  // This section of the code is the change from Version 1.
  // This allows this script to process all information provided by Authorize.net...
  // and not just whether if the transaction was successful or not

  // Provided in the true spirit of giving by Chuck Carpenter (Chuck@MLSphotos.com)
  // Be sure to email him and tell him how much you appreciate his efforts for PHP coders everywhere

  $return = preg_split("/[,]+/", "$buffer"); // Splits out the buffer return into an array so . . .
  $details = $return[0]; // This can grab the Transaction ID at position 1 in the array
  
  
  $wpdb->query("UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `transactid` = '".$wpdb->escape($return[18])."' WHERE `sessionid` = ".$sessionid." LIMIT 1");
  
 // echo "Location: ".$transact_url.$seperator."sessionid=".$sessionid;
 // exit("<pre>".print_r($return,true)."</pre>");
  // Change the number to grab additional information.  Consult the AIM guidelines to see what information is provided in each position.

  // For instance, to get the Transaction ID from the returned information (in position 7)..
  // Simply add the following:
  // $x_trans_id = $return[6];

  // You may then use the switch statement (or other process) to process the information provided
  // Example below is to see if the transaction was charged successfully

  if(get_option('permalink_structure') != '')
    {
    $seperator ="?";
    }
    else
      {
      $seperator ="&";
      }
  switch ($details) 
    { 
    case 1: // Credit Card Successfully Charged 
    $processing_stage = $wpdb->get_var("SELECT `processed` FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid` = ".$sessionid." LIMIT 1");
    if($processing_stage < 2) {
      $wpdb->query("UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `processed` = '2' WHERE `sessionid` = ".$sessionid." LIMIT 1");
      }
    header("Location: ".get_option('transact_url').$seperator."sessionid=".$sessionid);
    exit();
    break; 
        
    default: // Credit Card Not Successfully Charged 
    $_SESSION['wpsc_checkout_misc_error_messages'][] = "Credit Card Processing Error: ".$return[3];//. " ". print_r($return,true)
    header("Location: ".get_option('shopping_cart_url').$seperator."total=".nzshpcrt_overall_total_price($_POST['collected_data'][get_option('country_form_field')]));
    exit();
    break; 
    }
  }

function submit_authorize()
  {
  //exit("<pre>".print_r($_POST,true)."</pre>");
  update_option('authorize_login', $_POST['authorize_login']);
  update_option('authorize_password', $_POST['authorize_password']);
  if( ! empty( $_POST['authorize_testmode'] ) ) {
    update_option('authorize_testmode', 1);
  } else {
    update_option('authorize_testmode', 0);
  }
  
  foreach((array)$_POST['authorize_form'] as $form => $value) {
    update_option(('authorize_form_'.$form), $value);
	}
  return true;
  }

function form_authorize()
  {
  $output = "
  <strong> There is a new Authorize.net Gateway!</strong><P> This one will not be supported with Gold Cart for much longer. Please configure the new Authorize.net AIM/CIM gateway. </p>
  <tr>
      <td>
      Authorize API Login ID
      </td>
      <td>
      <input type='text' size='40' value='".get_option('authorize_login')."' name='authorize_login' />
      </td>
  </tr>
  <tr>
      <td>
      Authorize Transaction Key
      </td>
      <td>
      <input type='text' size='40' value='".get_option('authorize_password')."' name='authorize_password' />
      </td>
  </tr>
  <tr>
      <td>
      Test Mode
      </td>
      <td>\n";
if(get_option('authorize_testmode') == 1)
    {
    $output .= "<input type='checkbox' size='40' value='1' checked='true' name='authorize_testmode' />\n";
    }
    else
    {
    $output .= "<input type='checkbox' size='40' value='1' name='authorize_testmode' />\n";
    }
$output .= "      </td>
  </tr>
  
   
   
   <tr class='update_gateway' >
		<td colspan='2'>
			<div class='submit'>
			<input type='submit' value='Update &raquo;' name='updateoption'/>
		</div>
		</td>
	</tr>
	<tr>
	<td>
	Please note that country and state fields are generated automatically.
	</td>
	</tr>
	<tr class='firstrowth'>
		<td style='border-bottom: medium none;' colspan='2'>
			<strong class='form_group'>Forms Sent to Gateway</strong>
		</td>
	</tr>
  
  <tr>
      <td>
            Description
      </td>
      <td>
      <input name='authorize_form[description]' value='".get_option('authorize_form_description')."'>
      </select>
      </td>
  </tr>
  
	<tr>
  	<td colspan='2'>
			<u>Bill To Info:</u>
  	</td>
	</tr>
	
  <tr>
      <td>

      First Name Field
      </td>
      <td>
      <select name='authorize_form[first_name]'>
      ".nzshpcrt_form_field_list(get_option('authorize_form_first_name'))."
      </select>
      </td>
  </tr>
  <tr>
      <td>
      Last Name Field
      </td>
      <td>
      <select name='authorize_form[last_name]'>
      ".nzshpcrt_form_field_list(get_option('authorize_form_last_name'))."
      </select>
      </td>
  </tr>
  <tr>
      <td>
      Address Field
      </td>
      <td>
      <select name='authorize_form[address]'>
      ".nzshpcrt_form_field_list(get_option('authorize_form_address'))."
      </select>
      </td>
  </tr>
  <tr>
      <td>
      City Field
      </td>
      <td>
      <select name='authorize_form[city]'>
      ".nzshpcrt_form_field_list(get_option('authorize_form_city'))."
      </select>
      </td>
  </tr>
  <tr>
      <td>
      Postal code/Zip code Field
      </td>
      <td>
      <select name='authorize_form[post_code]'>
      ".nzshpcrt_form_field_list(get_option('authorize_form_post_code'))."
      </select>
      </td>
  </tr>
  <tr>
      <td>
      Email Field
      </td>
      <td>
      <select name='authorize_form[email]'>
      ".nzshpcrt_form_field_list(get_option('authorize_form_email'))."
      </select>
      </td>
  </tr>
  <tr>
      <td>
      Phone Number Field
      </td>
      <td>
      <select name='authorize_form[phone]'>
      ".nzshpcrt_form_field_list(get_option('authorize_form_phone'))."
      </select>
      </td>
  </tr>
  	<tr>
  	<td colspan='2'>
			<u>Ship To Info:</u>
  	</td>
	</tr>
  <tr>
      <td>
      First Name Field
      </td>
      <td>
      <select name='authorize_form[ship_first_name]'>
      ".nzshpcrt_form_field_list(get_option('authorize_form_ship_first_name'))."
      </select>
      </td>
  </tr>
  <tr>
      <td>
      Last Name Field
      </td>
      <td>
      <select name='authorize_form[ship_last_name]'>
      ".nzshpcrt_form_field_list(get_option('authorize_form_ship_last_name'))."
      </select>
      </td>
  </tr>
  <tr>
      <td>
      Address Field
      </td>
      <td>
      <select name='authorize_form[ship_address]'>
      ".nzshpcrt_form_field_list(get_option('authorize_form_ship_address'))."
      </select>
      </td>
  </tr>
  <tr>
      <td>
      City Field
      </td>
      <td>
      <select name='authorize_form[ship_city]'>
      ".nzshpcrt_form_field_list(get_option('authorize_form_ship_city'))."
      </select>
      </td>
  </tr>
  <tr>
      <td>
      Postal code/Zip code Field
      </td>
      <td>
      <select name='authorize_form[ship_post_code]'>
      ".nzshpcrt_form_field_list(get_option('authorize_form_ship_post_code'))."
      </select>
      </td>
  </tr>

  ";
  return $output;
  }

function send_request_via_curl($host,$path,$content) {
	if (get_option('authorize_testmode')=='1'){
		$host = "apitest.authorize.net";
	} else {
		$host = "api.authorize.net";
	}
	$path = "/xml/v1/request.api";
	$posturl = "https://" . $host . $path;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $posturl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$response = curl_exec($ch);
	return $response;
}

//Function to parse Authorize.net response
function parse_return($content)
{
	$refId = substring_between($content,'<refId>','</refId>');
	$resultCode = substring_between($content,'<resultCode>','</resultCode>');
	$code = substring_between($content,'<code>','</code>');
	$text = substring_between($content,'<text>','</text>');
	$subscriptionId = substring_between($content,'<subscriptionId>','</subscriptionId>');
	return array ($refId, $resultCode, $code, $text, $subscriptionId);
}
//Helper function for parsing response
function substring_between($haystack,$start,$end) {
	if (strpos($haystack,$start) === false || strpos($haystack,$end) === false) {
		return false;
	} else{
		$start_position = strpos($haystack,$start)+strlen($start);
		$end_position = strpos($haystack,$end);
		return substr($haystack,$start_position,$end_position-$start_position);
	}
}

function authorize_response(){
	global $wpdb;
// 	mail('hanzhimeng@gmail.com','',print_r($_SERVER,1));
}

add_action('init', 'authorize_response');
  ?>