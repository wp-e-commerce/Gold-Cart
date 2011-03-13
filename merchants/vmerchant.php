<?php
if(!is_callable('get_option')) {
  // This is here to stop error messages on servers with Zend Accelerator, it includes all files before get_option is declared
  // then evidently includes them again, otherwise this code would break these modules
  return;
  exit("Something strange is happening, and \"return\" is not breaking out of a file.");
}
global $gateway_checkout_form_fields;
$nzshpcrt_gateways[$num]['name'] = 'Virtual Merchant';
$nzshpcrt_gateways[$num]['admin_name'] = 'Virtual Merchant';
$nzshpcrt_gateways[$num]['internalname'] = 'vmerchant';
$nzshpcrt_gateways[$num]['function'] = 'gateway_vmerchant';
$nzshpcrt_gateways[$num]['form'] = "form_vmerchant";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_vmerchant";
$nzshpcrt_gateways[$num]['payment_type'] = "vmerchant";

if(in_array('vmerchant',(array)get_option('custom_gateway_options'))) {
	$gateway_checkout_form_fields[$nzshpcrt_gateways[$num]['internalname']] = "
	<tr>
		<td>Credit Card Number *</td>
		<td>
			<input type='text' value='' name='ssl_card_number' />
			<p class='validation-error'></p>
		</td>
	</tr>
	<tr>
		<td>Credit Card Expiry (MMYY) *</td>
		<td>
			<input type='text' name='ssl_exp_date' size='4'>
			<p class='validation-error'></p>
		</td>
	</tr>
	<tr>
		<td>CVV *</td>
		<td><input type='text' size='4' value='' maxlength='4' name='ssl_cvv2cvc2' />
		<p class='validation-error'></p>
		</td>
	</tr>
";
}
  

function gateway_vmerchant($seperator, $sessionid) {
  global $wpsc_cart;
  $transact_url = get_option('transact_url');
  $_SESSION['wpsc_sessionid'] = $sessionid;
  //exit('<pre>'.print_r($_POST,true).'</pre>');
   $form_action = 'https://www.myvirtualmerchant.com/VirtualMerchant/process.do';
  $output = '<form action="'.$form_action.'" id="vmerchant_form" method="post" /> 
  <input type="hidden" name="ssl_merchant_id" value="'.get_option('ssl_merchant_id').'" /> 
  <input type="hidden" name="ssl_pin" value="'.get_option('ssl_pin').'" /> 
  <input type="hidden" name="ssl_user_id" value="'.get_option('ssl_user_id').'" /> 
  <input type="hidden" name="ssl_salestax" value="'.number_format(sprintf("%01.2f", $wpsc_cart->calculate_total_tax()),2,'.','').'" /> 
  <input type="hidden" name="ssl_amount" value="'.number_format(sprintf("%01.2f", $wpsc_cart->calculate_total_price()),2,'.','').'" /> 
  <input type="hidden" name="ssl_transaction_type" value="ccsale" /> 
  <input type="hidden" name="ssl_cvv2cvc2_indicator" value="1"> 
  <input type="hidden" name="ssl_cvv2cvc2" value="'.$_POST['ssl_cvv2cvc2'].'"> 
  
  
  <input type="hidden" name="ssl_first_name" value="'.$_POST['collected_data'][2].'" />
  <input type="hidden" name="ssl_last_name" value="'.$_POST['collected_data'][3].'" />
  <input type="hidden" name="ssl_avs_address" value="'.$_POST['collected_data'][4].'" />
  <input type="hidden" name="ssl_city" value="'.$_POST['collected_data'][5].'" />
  <input type="hidden" name="ssl_state" value="'.$_POST['collected_data'][6][1].'" />

  <input type="hidden" name="ssl_avs_zip" value="'.$_POST['collected_data'][7].'" />
  <input type="hidden" name="ssl_email" value="'.$_POST['collected_data'][8].'" />
  <input type="hidden" name="ssl_avs_response" value="something" />
     
  <input type="hidden" name="ssl_show_form" value="false" /> 
  <input type="hidden" name="ssl_card_number" value="'.$_POST['ssl_card_number'].'" /> <br /> 
  <input type="hidden" name="ssl_exp_date" value="'.$_POST['ssl_exp_date'].'" size="4" /> <br /> 
  <br/>   
  
  <input type="hidden" name="ssl_result_format" value="ASCII"> 
  <input type="hidden" name="ssl_receipt_decl_method" value="REDG"> 
  <input type="hidden" name="ssl_receipt_decl_get_url" value="'.$transact_url.'"> 
  <input type="hidden" name="ssl_receipt_apprvl_method" value="REDG"> 
  <input type="hidden" name="ssl_receipt_apprvl_get_url" value="'.$transact_url.'"> 
</form> ';
  $output .='<script type="text/javascript">document.getElementById("vmerchant_form").submit();</script>';	
  echo $output;
  exit();
}

function submit_vmerchant() {
	if($_POST['ssl_merchant_id'] != ''){
	 update_option('ssl_merchant_id', $_POST['ssl_merchant_id']);
	}
	if($_POST['ssl_pin'] != ''){
	 update_option('ssl_pin', $_POST['ssl_pin']);
	}
	if($_POST['ssl_user_id'] != ''){
	 update_option('ssl_user_id', $_POST['ssl_user_id']);
	}
	
	
	return true;
}

function form_vmerchant() {  
	if(get_option('ssl_merchant_id')!=''){
		$ssl_merchant_id = get_option('ssl_merchant_id');
	}else{
		$ssl_merchant_id = '';
	}
	
	if(get_option('ssl_user_id')!=''){
		$ssl_user_id = get_option('ssl_user_id');
	}else{
		$ssl_merchant_id = '';
	}
	
	if(get_option('ssl_pin')!=''){
		$ssl_pin = get_option('ssl_pin');
	}else{
		$ssl_pin = '';
	}
	
	$output = "<tr>\n\r";
	
	$output .= "<td>\n\r<label for='ssl_merchant_id'>".__('Merchant ID','wpsc')."</label></td>";
	$output .= "<td><input type='text' id='ssl_merchant_id' value='".$ssl_merchant_id."' name='ssl_merchant_id' /></td>";
	$output .= "</tr><tr>";
	$output .= "<td>\n\r<label for='ssl_user_id'>".__('User ID','wpsc')."</label></td>";
	$output .= "<td><input type='text' id='ssl_user_id' value='".$ssl_user_id."' name='ssl_user_id' /></td>";

	$output .= "</tr><tr><td>";
	$output .= "<label for='ssl_pin'>".__('Merchant Pin','wpsc')."</label></td>";
	$output .= "<td><input type='text' id='ssl_pin' value='".$ssl_pin."' name='ssl_pin' />";
	
/*
	$output .= "<label for='ssl_merchant_id'>".__('','wpsc')."</label>";
	$output .= "<input type='text' id='' value='' name='' />";
*/
	
	$output .= "	</td>\n\r";
	$output .= "</tr>\n\r";
  return $output;
}
?>