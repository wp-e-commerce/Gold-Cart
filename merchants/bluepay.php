<?php
if(!is_callable('get_option')) {
  // This is here to stop error messages on servers with Zend Accelerator, it includes all files before get_option is declared
  // then evidently includes them again, otherwise this code would break these modules
  return;
  exit("Something strange is happening, and \"return\" is not breaking out of a file.");
}

$nzshpcrt_gateways[$num]['name'] = 'Bluepay';
$nzshpcrt_gateways[$num]['internalname'] = 'bluepay';
$nzshpcrt_gateways[$num]['function'] = 'gateway_bluepay';
$nzshpcrt_gateways[$num]['form'] = "form_bluepay";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_bluepay";
$nzshpcrt_gateways[$num]['payment_type'] = "credit_card";

//include_once(ABSPATH.'wp-content/plugins/wp-shopping-cart/classes/bluepay_class.php');

if(in_array('bluepay',(array)get_option('custom_gateway_options'))) {
  $gateway_checkout_form_fields[$nzshpcrt_gateways[$num]['internalname']] = "
    <tr>
      <td>
      Credit Card Number *
      </td>
      <td>
      <input type='text' value='' name='card_number' />
      </td>
    </tr>
    <tr>
      <td>
      Credit Card Expiry *
      </td>
      <td>
      <input type='text' size='2' value='' maxlength='2' name='expiry[month]' />/<input type='text' size='2'  maxlength='2' value='' name='expiry[year]' />
      </td>
    </tr>
    <tr>
      <td>
      Credit Card Code *
      </td>
      <td>
      <input type='text' value='' name='card_code' />
      </td>
    </tr>
";
  }

function gateway_bluepay($seperator, $sessionid)
  {
  //$transact_url = get_option('transact_url');
  //exit("<pre>".print_r($_POST,true)."</pre>");
//   if($_SESSION['cart_paid'] == true)
//     {
//     header("Location: ".get_option('transact_url').$seperator."sessionid=".$sessionid);
//     }
  $x_Login= urlencode(get_option('bluepay_login')); // Replace LOGIN with your login 
  $x_Password= urlencode(get_option("bluepay_password")); // Replace PASS with your password 
  $x_Delim_Data= urlencode("TRUE"); 
  $x_Delim_Char= urlencode(","); 
  $x_Encap_Char= urlencode(""); 
  $x_Type= urlencode("AUTH_CAPTURE"); 
  
  $x_ADC_Relay_Response = urlencode("FALSE"); 
  if(get_option('bluepay_testmode') == 1)
    {
    $x_Test_Request= urlencode("TRUE"); // Remove this line of code when you are ready to go live 
    }
  # 
  # Customer Information 
  # 
  $x_Method= urlencode("CC"); 
  $x_Amount= urlencode(nzshpcrt_overall_total_price($_SESSION['delivery_country']));
  //exit($x_Amount);
  $x_First_Name= urlencode($_POST['collected_data'][get_option('bluepay_form_first_name')]); 
  $x_Last_Name= urlencode($_POST['collected_data'][get_option('bluepay_form_last_name')]); 
  $x_Card_Num= urlencode($_POST['card_number']); 
  $ExpDate = urlencode(($_POST['expiry']['month'] . $_POST['expiry']['year'])); 
  $x_Exp_Date= $ExpDate; 
  $x_Address= urlencode($_POST['collected_data'][get_option('bluepay_form_address')]); 
  $x_City= urlencode($_POST['collected_data'][get_option('bluepay_form_city')]);
   
  $State= urlencode($_POST['collected_data'][get_option('bluepay_form_state')]); //gets the state from the input box not the usa ddl
  
  if (empty($State)){ // check if the state is there from the input box if not get it from the ddl
  	$State_id= $_POST['collected_data'][get_option('bluepay_form_country')][1];
   	$x_State = urlencode(wpsc_get_state_by_id($State_id, 'name'));
  }else{
  	$x_State = $State;
  }
  
  $x_Zip= urlencode($_POST['collected_data'][get_option('bluepay_form_post_code')]); 
  $x_Email= urlencode($_POST['collected_data'][get_option('bluepay_form_email')]); 
  $x_Email_Customer= urlencode("TRUE"); 
  $x_Merchant_Email= urlencode(get_option('purch_log_email')); //  Replace MERCHANT_EMAIL with the merchant email address 
  $x_Card_Code = urlencode($_POST['card_code']);
  # 
  # Build fields string to post 
  #
  $fields="x_Version=3.1&x_Login=$x_Login&x_Delim_Data=$x_Delim_Data&x_Delim_Char=$x_Delim_Char&x_Encap_Char=$x_Encap_Char"; 
  $fields.="&x_Type=$x_Type&x_Test_Request=$x_Test_Request&x_Method=$x_Method&x_Amount=$x_Amount&x_First_Name=$x_First_Name"; 
  $fields.="&x_Last_Name=$x_Last_Name&x_Card_Num=$x_Card_Num&x_Exp_Date=$x_Exp_Date&x_Card_Code=$x_Card_Code&x_Address=$x_Address&x_City=$x_City&x_State=$x_State&x_Zip=$x_Zip&x_Email=$x_Email&x_Email_Customer=$x_Email_Customer&x_Merchant_Email=$x_Merchant_Email&x_ADC_Relay_Response=$x_ADC_Relay_Response";

  if($x_Password!='') 
  { 
  $fields.="&x_Password=$x_Password"; 
  } 
  //exit($fields);
  # 
  # Start CURL session 
  # 
  $agent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)"; 
  $ref = get_option('transact_url'); // Replace this URL with the URL of this script 
  
  $ch=curl_init(); 
  curl_setopt($ch, CURLOPT_URL, "https://secure.bluepay.com/interfaces/a.net"); 
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
  curl_setopt($ch, CURLOPT_NOPROGRESS, 1); 
  curl_setopt($ch, CURLOPT_VERBOSE, 1); 
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION,0); 
  curl_setopt($ch, CURLOPT_POST, 1); 
  curl_setopt($ch, CURLOPT_POSTFIELDS, $fields); 
  curl_setopt($ch, CURLOPT_TIMEOUT, 120); 
  curl_setopt($ch, CURLOPT_USERAGENT, $agent); 
  curl_setopt($ch, CURLOPT_REFERER, $ref); 
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
 // echo "Location: ".$transact_url.$seperator."sessionid=".$sessionid;
 // exit("<pre>".print_r($return,true)."</pre>");
  // Change the number to grab additional information.  Consult the AIM guidelines to see what information is provided in each position.
  
  // For instance, to get the Transaction ID from the returned information (in position 7)..
  // Simply add the following:
  // $x_trans_id = $return[6];
  
  // You may then use the switch statement (or other process) to process the information provided
  // Example below is to see if the transaction was charged successfully
  if(get_option('permalink_structure') != '') {
    $seperator ="?";
	} else {
		$seperator ="&";
	}
	//exit("<pre>".print_r($return,true)."</pre>");
  switch ($details) 
    { 
    case 1: // Credit Card Successfully Charged
    global $wpdb;
    $wpdb->update( WPSC_TABLE_PURCHASE_LOGS, array('processed' => 3),array('sessionid'=>$sessionid), array('%f') );
   	//$_SESSION['cart_paid'] = true;
    header("Location: ".get_option('transact_url').$seperator."sessionid=".$sessionid);
    exit();
    break; 
    default: // Credit Card Not Successfully Charged 
    $_SESSION['wpsc_checkout_misc_error_messages'][] = "Credit Card Processing Error: ".$return[3];
    header("Location: ".get_option('transact_url').$seperator."total=".nzshpcrt_overall_total_price($_POST['collected_data'][get_option('country_form_field')]));
    exit();
    break; 
    }
  }

function submit_bluepay()
  {
  //exit("<pre>".print_r($_POST,true)."</pre>");
  update_option('bluepay_login', $_POST['bluepay_login']);
  update_option('bluepay_password', $_POST['bluepay_password']);
  if( ! empty( $_POST['bluepay_testmode'] ) )
    {
    update_option('bluepay_testmode', 1);
    }
    else
    {
    update_option('bluepay_testmode', 0);
    }
  
  foreach((array)$_POST['bluepay_form'] as $form => $value)
    {
    update_option(('bluepay_form_'.$form), $value);
    }
  return true;
  }

function form_bluepay()
  {
  $output = "
  <tr>
      <td>
      Account ID:
      </td>
      <td colspan='2'>
      <input type='text' size='40' value='".get_option('bluepay_login')."' name='bluepay_login' />
      </td>
  </tr>
  <tr>
      <td>
      Secrete Key:
      </td>
      <td colspan='2'>
      <input type='text' size='40' value='".get_option('bluepay_password')."' name='bluepay_password' />
      </td>
  </tr>
  <tr>
      <td>
      Test Mode
      </td>
      <td colspan='2'>\n";
if(get_option('bluepay_testmode') == 1)
    {
    $output .= "<input type='checkbox' size='40' value='1' checked='true' name='bluepay_testmode' />\n";
    }
    else
    {
    $output .= "<input type='checkbox' size='40' value='1' name='bluepay_testmode' />\n";
    }
$output .= "      </td>
  </tr>
  <tr>
    <td colspan='3'>
    &nbsp;
    </td>
  </tr>
  <tr>
  	<td colspan='8'>
  		<p>
        Select the form fields below to send your customers details to BluePay These are the values that corospond with your WP-e-Commerce checkout form, If you leave these options blank then only the purchase amount will be sent to your BluePay account.
     	</p>
     </td>
  </tr>

  <tr>
      <td>
      First Name Field
      </td>
      <td>
      <select name='bluepay_form[first_name]'>
      ".nzshpcrt_form_field_list(get_option('bluepay_form_first_name'))."
      </select>
      </td>
        </tr>
  <tr>
      <td>
      Last Name Field
      </td>
      <td>
      <select name='bluepay_form[last_name]'>
      ".nzshpcrt_form_field_list(get_option('bluepay_form_last_name'))."
      </select>
      </td>
  </tr>
  <tr>
      <td>
      Address Field
      </td>
      <td>
      <select name='bluepay_form[address]'>
      ".nzshpcrt_form_field_list(get_option('bluepay_form_address'))."
      </select>
      </td>
  </tr>
  <tr>
      <td>
      City Field
      </td>
      <td>
      <select name='bluepay_form[city]'>
      ".nzshpcrt_form_field_list(get_option('bluepay_form_city'))."
      </select>
      </td>
  </tr>
  <tr>
      <td>
      State Field
      </td>
      <td>
      <select name='bluepay_form[state]'>
      ".nzshpcrt_form_field_list(get_option('bluepay_form_state'))."
      </select>
      </td>
  </tr>
  <tr>
      <td>
      Country:
      </td>
      <td>
      <select name='bluepay_form[country]'>
      ".nzshpcrt_form_field_list(get_option('bluepay_form_country'))."
      </select>
      </td>
  </tr>

  <tr>
      <td>
      Postal / Zip code Field
      </td>
      <td>
      <select name='bluepay_form[post_code]'>
      ".nzshpcrt_form_field_list(get_option('bluepay_form_post_code'))."
      </select>
      </td>
  </tr>
  
  <tr>
      <td>
      Email Field
      </td>
      <td>
      <select name='bluepay_form[email]'>
      ".nzshpcrt_form_field_list(get_option('bluepay_form_email'))."
      </select>
      </td>
  </tr>
  ";
  return $output;
  }
  ?>
