<?php
global $gateway_checkout_form_fields;
$nzshpcrt_gateways[$num]['name']         = 'Virtual Merchant';
$nzshpcrt_gateways[$num]['internalname'] = 'vmerchant';
$nzshpcrt_gateways[$num]['class_name']   = 'Virtual_Merchant';
$nzshpcrt_gateways[$num]['api_version']  = 2.0;
$nzshpcrt_gateways[$num]['form'] = "form_vmerchant";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_vmerchant";
$nzshpcrt_gateways[$num]['payment_type'] = "credit card";

if(in_array('vmerchant',(array)get_option('custom_gateway_options'))) {
    
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


function form_vmerchant() {  
    $args = array(
    	'user_id'      => '',
        'merchant_id'  => '',
        'pin'		   => '',
        'avs'		   => 'no',
        'mode'         => 'live'
    );
    add_option('wpsc_vmerchnat',$args);
    $options = get_option('wpsc_vmerchnat');
    $output = '
    <tr>
        <td>
        	'. __('Account ID','wpsc') .'
        </td>
        <td>
        	<input type="text" value="'.$options['merchant_id'].'" name="wpsc_vmerchnat[merchant_id]"  />
        </td>
    </tr>
    <tr>
        <td>
        	' . __('User ID','wpsc') .'
        </td>
        <td>
        	<input type="text" value="'.$options['user_id'].'"  name="wpsc_vmerchnat[user_id]"  />
        </td>
    </tr>
    <tr>
        <td>
        	' . __('Merchant Pin','wpsc') .'
        </td>
        <td>
        	<input type="text" value="'.$options['pin'].'"  name="wpsc_vmerchnat[pin]"  />
        </td>
    </tr>
     <tr>
        <td>
        	' . __('AVS Sercuity','wpsc') .'
        </td>
        <td>
			<input type="radio" value="yes" name="wpsc_vmerchnat[avs]"  ' . checked('yes',$options['avs'],false) .'  /><label>' .  TXT_WPSC_YES . ' </label> 
			<input type="radio" value="no"  name="wpsc_vmerchnat[avs]"  ' . checked('no', $options['avs'],false) .'  /><label>' .  TXT_WPSC_NO . '</label>
		</td>
    </tr>
    <tr>
        <td>
        	' . __('Mode','wpsc') .'
        </td>
        <td>
			<input type="radio" value="live" name="wpsc_vmerchnat[mode]"  ' .  checked('live', $options['mode'],false) .'  /><label>' .  __('Live Mode','wpsc') . ' </label> 
			<input type="radio" value="test"  name="wpsc_vmerchnat[mode]"  ' . checked('test', $options['mode'],false) .'  /><label>' . __('Test Mode','wpsc') . '</label>
		</td>
    </tr>
    ';
   $struc = get_option('permalink_structure');
   if($struc == ''){
       $output .= '
           <tr>
        <td colspan="2">
        	<strong style="color:red;">' . __('This Gateway will only work if you change your permalink structure do anything except the default setting. In Settings->Permalinks','wpsc') .'</strong>
        </td>
      
    </tr>
       ';
   }
   error_log('$struc:' . var_export($struc, TRUE));
    
	return $output;
}
function submit_vmerchant() {
    $options = get_option('wpsc_vmerchnat');
    foreach($_POST['wpsc_vmerchnat'] as $name => $value ){
        $options[$name] = rtrim($value);
    }
    update_option('wpsc_vmerchnat', $options);
    return true;
}
class Virtual_Merchant extends wpsc_merchant {
    // r47hg5fre3b is test password
    // 549114 is pin
    public function submit(){
        // basic credit card verification
        $errorMsg = "";
        if(isset($_POST['CardNumber']) && strlen($_POST['CardNumber']) > 0)
            $CardNumber = $_POST['CardNumber'];
        else
            $errorMsg .= "Credit Card Number Required  <br/>";
        if(isset($_POST['ExpiryMonth']) && strlen($_POST['ExpiryMonth']) > 0)
            $ExpiryMonth = $_POST['ExpiryMonth'];
        else
            $errorMsg .= "Credit Card Expiry Month Required  <br/>";
        if(isset($_POST['ExpiryYear']) && strlen($_POST['ExpiryYear']) > 0)
            $ExpiryYear = $_POST['ExpiryYear'];
        else
            $errorMsg .= "Credit Card Expiry Year Required  <br/>";
        if(isset($_POST['Cvc2']) && strlen($_POST['Cvc2']) > 0)
            $Cvc2 = $_POST['Cvc2'];
        else
            $errorMsg .= "Credit Card Cvc2 code  Required    <br/>";
        
        if(strlen($errorMsg) > 0){
            $this->set_error_message($errorMsg);
            header('Location: '.$this->cart_data['shopping_cart_url']);
            exit();
        }
      
        //
        $options = get_option('wpsc_vmerchnat');
        
        error_log('$this->cart_data:' . var_export($this->cart_data, TRUE));
        $options  = get_option('wpsc_vmerchnat');
        // temp vars to make things easier
        if(get_option('permalink_structure') != '')
            $separator ="?";
        else
            $separator ="&";
        
        if($options['mode'] == 'test'){
            // test url goes here
            $url                  = 'https://demo.myvirtualmerchant.com/VirtualMerchantDemo/process.do';
        }else{
            //live url goes here
            $url                  = 'https://www.myvirtualmerchant.com/VirtualMerchant/process.do';
        }
        $amount                   =  number_format($this->cart_data['total_price'],2) ;
        $sales_tax                = $this->cart_data['cart_tax'];
        $invoice_number           = $this->cart_data['session_id'];
        $email                    = $this->cart_data['email_address'];
        $transaction_results_page = $this->cart_data['transaction_results_url'];
        $credit_card_date         = $ExpiryMonth . '' . $ExpiryYear;
        // optional vars
        $first_name               = $this->cleanInput($this->cart_data['billing_address']['first_name']);
        $last_name                = $this->cleanInput($this->cart_data['billing_address']['last_name']);
        $address2                 = $this->cleanInput($this->cart_data['billing_address']['address']);
        $city                     = $this->cleanInput($this->cart_data['billing_address']['city']);
        $state                    = $this->cleanInput($this->cart_data['billing_address']['state']);
        $country                  = $this->cart_data['billing_address']['country'];
        // avs vars
        if($options['avs'] == 'yes' ){
            $avs_zip                  = $this->cart_data['billing_address']['post_code'];
            $avs_address              = $this->cleanInput($this->cart_data['billing_address']['address']);
        }
        
        
        $form = '
        <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"><html lang="en"><head><title></title></head><body>
        <form id="vmerchant_form" action="' .$url . '" method="POST">
        <input type="hidden" name="ssl_transaction_type"      value="ccsale"> 
        <input type="hidden" name="ssl_show_form"             value="false">
        <input type="hidden" name="ssl_merchant_id"           value="'. $options['merchant_id'] .'">
        <input type="hidden" name="ssl_user_id"               value="'. $options['user_id'] .'">
        <input type="hidden" name="ssl_pin"                   value="'. $options['pin'] .'">
       	<input type="hidden" name="ssl_amount"                value="'. $amount .'"> 
       	<input type="hidden" name="ssl_salestax"              value="'. $sales_tax .'"> 
        <input type="hidden" name="ssl_invoice_number"        value="'. $invoice_number . '"> 
        <input type="hidden" name="ssl_email"                 value="'. $email . '">  
        <input type="hidden" name="ssl_card_number"           value="'. $CardNumber . '">  
        <input type="hidden" name="ssl_exp_date"              value="'. $credit_card_date . '">  
        <input type="hidden" name="ssl_cvv2cvc2_indicator"    value="1">
        <input type="hidden" name="ssl_cvv2cvc2"              value="'. $Cvc2 . '"> 
        <input type="hidden" name="ssl_receipt_decl_get_url"  value="'. $transaction_results_page . '">  
		<input type="hidden" name="ssl_receipt_apprvl_get_url"value="'. $transaction_results_page . '' .$separator .'"> 
        <input type="hidden" name="ssl_result_format"         value="HTML">
        <input type="hidden" name="ssl_receipt_decl_method"   value="REDG">
      	<input type="hidden" name="ssl_receipt_apprvl_method" value="REDG">
        <input type="hidden" name="ssl_customer_code"         value="1111">';
        if(strlen($first_name) > 0){
            $form .= '
            <input type="hidden" name="ssl_first_name" 	  value="' . $first_name . '">';
        }
        if(strlen($last_name) > 0){
            $form .= '
            <input type="hidden" name="ssl_last_name"     value="' . $last_name . '">';
        }
        if(strlen($address2) > 0){
            $form .= '
            <input type="hidden" name="ssl_address2" 	  value="' . $address2 . '">';
        }
        if(strlen($city) > 0){
            $form .= '
             <input type="hidden" name="ssl_city" 		  value="' . $city . '">';
        }
        if(strlen($state) > 0){
            $form .= '
            <input type="hidden" name="ssl_state" 		  value="' . $state . '">';
        }
        if(strlen($country) > 0){
            $form .= '
            <input type="hidden" name="ssl_country" 	  value="' . $country. '">';
        }
        
        if($options['mode'] == 'test'){
            $form .= '
            <input type="hidden" name="ssl_test_mode" value="true">';
        } else {
            $form .= '
            <input type="hidden" name="ssl_test_mode" value="false">';
        }
        if($options['avs'] == 'yes'){
            $form .= '
            <input type="hidden" name="ssl_avs_address" value="' . $avs_address . '">
            <input type="hidden" name="ssl_avs_zip"     value="' . $avs_zip . '">';
        }
        $form .= '   
        </form>
        <script type="text/javascript">document.getElementById("vmerchant_form").submit();</script></body></html>';
        //error_log('SENT FORM:' . $form);
        echo $form;
        
    }
    
    
    private function cleanInput($strRawText){
        $iCharPos = 0;
        $chrThisChar = "";
        $strCleanedText = "";
        $strAllowableChars     = "0123456789 ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-_/\(),.:|";
        $blnAllowAccentedChars = TRUE;
        //Compare each character based on list of acceptable characters
        while ($iCharPos < strlen($strRawText))
        {
            // Only include valid characters **
            $chrThisChar = substr($strRawText, $iCharPos, 1);
            if (strpos($strAllowableChars, $chrThisChar) !== FALSE)
            {
                $strCleanedText = $strCleanedText . $chrThisChar;
            }
            elseIf ($blnAllowAccentedChars == TRUE)
            {
                // Allow accented characters and most high order bit chars which are harmless **
                if (ord($chrThisChar) >= 191)
                {
                    $strCleanedText = $strCleanedText . $chrThisChar;
                }
            }
    
            $iCharPos = $iCharPos + 1;
        }
    
        return $strCleanedText;
    }
    
}

if( isset($_GET['ssl_card_number']) && 
    isset($_GET['ssl_exp_date']) && 
    isset($_GET['ssl_amount']) && 
    isset($_GET['ssl_invoice_number']) && 
    isset($_GET['ssl_email']) && 
    isset($_GET['ssl_result_message']) && 
    isset($_GET['ssl_txn_id']) && 
    isset($_GET['ssl_approval_code']) && 
    isset($_GET['ssl_cvv2_response']) && 
    isset($_GET['ssl_txn_time']) ){
    // just to make sure that this is a vmerchnat responce
    add_action('init', 'wpec_vmerchant_return');
}
function wpec_vmerchant_return(){
    // error_log('responce:' . var_export($_GET, TRUE));
    global $sessionid, $wpdb;
    
    $sessionid = $_GET['ssl_invoice_number'];
    
    if($_GET['ssl_result_message'] == 'APPROVED' || $_GET['ssl_result_message'] == 'APPROVAL'){
        // success
        $wpdb->query( 	"UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "`
                    	SET `processed` = '3', `transactid` = '" . $_GET['ssl_txn_id'] . "', 
                    	`notes` = 'Virtual Merchant time : " .     $_GET['ssl_txn_time'] . "' 
                    	WHERE `sessionid` = " . $sessionid . " LIMIT 1" );
        
        // set this global, wonder if this is ok
        transaction_results($sessionid,true);
    } else {
        $wpdb->query(   "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` 
        				SET `processed` = '1',`transactid` = '" . $_GET['ssl_txn_id'] . "', 
                    	`notes` = 'Virtual Merchant time : " .     $_GET['ssl_txn_time'] . "' 
                    	WHERE `sessionid` = " . $sessionid . " LIMIT 1" );
        
        $_SESSION['wpsc_checkout_misc_error_messages'][] = '<strong style="color:red">' . urldecode($_GET['ssl_result_message']) . ' </strong>';
        $checkout_page_url = get_option('shopping_cart_url');
        if($checkout_page_url){
            header('Location: '.$checkout_page_url);
            exit();
        }
    }
    
}?>
