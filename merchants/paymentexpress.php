<?php

//Gateway Details
$nzshpcrt_gateways[$num]['name'] 			        = 'Payment Express - PX Fusion';
$nzshpcrt_gateways[$num]['class_name']              = 'Paymentexpress_PXFusion_Merchant';
$nzshpcrt_gateways[$num]['internalname']	        = 'wpsc_merchant_paymentexpress';
$nzshpcrt_gateways[$num]['api_version']             = 2.0;
$nzshpcrt_gateways[$num]['form']                    = 'paymentexpress_admin_form';
$nzshpcrt_gateways[$num]['submit_function']         = 'wpec_paymentexpress_admin_submit';
$nzshpcrt_gateways[$num]['has_recurring_billing']   = false;
$nzshpcrt_gateways[$num]['wp_admin_cannot_cancel']  = false;
$nzshpcrt_gateways[$num]['payment_type']            = 'credit card';
$nzshpcrt_gateways[$num]['display_name']            = 'Pay with Payment Express - PX Fusion';

$nzshpcrt_gateways[$num]['requirements']            = array('php_version' => 5.0,
		 												'extra_modules' => array());
 
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


function paymentexpress_admin_form(){
    global $wpdb;
    $currency_code  = $wpdb->get_var( "SELECT `code` FROM `" . WPSC_TABLE_CURRENCY_LIST .
           									"` WHERE `id`='" . get_option( 'currency_type' ) . "' LIMIT 1" );
    
    $wpec_pxfusion_supported_currencies = array('CAD','CHF','DKK','EUR','FRF','GBP','HKD','JPY','NZD','SGD','THB',
                                                 'USD','ZAR','AUD','WST','VUV','TOP','SBD','PGK','MYR','KWD','FJD');
    
    $args = array(		'username'  => 'username',
                        'password'  => '',
                        );
    add_option('wpec_pxfusion',$args);
    
    $options = get_option('wpec_pxfusion');
    
    $curr_supported = false;
    global $wpdb;
    $currency_code  = $wpdb->get_var( "SELECT `code` FROM `" . WPSC_TABLE_CURRENCY_LIST .
       									"` WHERE `id`='" . get_option( 'currency_type' ) . "' LIMIT 1" );
    
    $curr_supported = (in_array($currency_code, $wpec_pxfusion_supported_currencies)) ? true : false;
    
   
    $output = '
    <tr>
    	<td>
    		Username : 
    	</td>
    	<td>
    		<input type="text" value="'.$options['username'].'" name="wpec_pxfusion_username"  />
    	</td>
    </tr>
    <tr>
    	<td>
    		Password : 
    	</td>
    	<td>
    		<input type="text" value="'.$options['password'].'"  name="wpec_pxfusion_password"  />
    	</td>
    </tr>
   	<tr>
   		<td colspan="2"> You can login to Payment Manager <a href="https://www.paymentexpress.com/pxmi/logon">https://www.paymentexpress.com/pxmi/logon</a> to see your transactions. </td>
   	</tr>';
    if(!$curr_supported)
    {    
        $last_one = count($wpec_pxfusion_supported_currencies) - 1;
        
        $curString = '';
        for($i = 0 ; $i< count($wpec_pxfusion_supported_currencies);$i++){
           if($last_one == $i)
                $curString .= "'". stripcslashes($wpec_pxfusion_supported_currencies[$i]) ."'";
            else
                $curString .= "'". stripcslashes($wpec_pxfusion_supported_currencies[$i]) . "',";
            
        }
        $query = "SELECT `country` FROM `" . WPSC_TABLE_CURRENCY_LIST .
            	"` WHERE `code` IN(".$curString.") ORDER BY `country` ASC"; 
        
        $output .='
           <tr>
            	<td>
            		<strong style="color:red;"> Your Selected Currency is not supported by Sageapy, 
            		to use Sagepay, go the the stores general settings and under &quot;Currency Type&quot; select one 
            		of the currencies listed on the right. </strong>
             	</td>
            	<td>
           			<ul>';
        $country_list  = $wpdb->get_results($query,'ARRAY_A');
        
        foreach($country_list as $country){
            $output .= '<li>'. $country['country'].'</li>';
        }
        $output .= '</ul>
            	</td>
            </tr>';
    } else {
        $output .='
            <tr>
            	<td colspan="2">
            	<strong style="color:green;"> Your Selected Currency will work with PX Fusion </strong>
            	</td>
            </tr>
            ';
    }
    if(!@extension_loaded('soap')){
        $output .='
        <tr>
        	<td colspan="2">
        	<strong style="color:red;"> THIS GATEWAY WILL NOT WORK ON THIS SERVER, ASK YOUR HOST TO INSTALL THE PHP SOAP EXTENSION </strong>
        	</td>
        </tr>
        ';
    }
	
	return $output;
}
function wpec_paymentexpress_admin_submit(){
    
    
    $options = get_option('wpec_pxfusion');
    
    if(isset($_POST['wpec_pxfusion_username'])){
        $options['username'] = rtrim($_POST['wpec_pxfusion_username']);
       
    }
    if(isset($_POST['wpec_pxfusion_password'])){
        $options['password'] = rtrim($_POST['wpec_pxfusion_password']);
         
    }
    update_option('wpec_pxfusion', $options);
        
    
}
class Paymentexpress_PXFusion_Merchant extends wpsc_merchant{
    
    private $options;
    private $separator;
    
    public function __construct( $purchase_id = null, $is_receiving = false ) {
        wpsc_merchant::__construct($purchase_id , $is_receiving);
        
        $this->options = get_option('wpec_pxfusion');
        
        if(get_option('permalink_structure') != '')
            $this->separator ="?";
        else
            $this->separator ="&";
        
    }
        
    
    
    
    public function submit(){
        error_log('$this:' . var_export($this, TRUE));
        
        $pxf = new PxFusion($this->options['username'],$this->options['password']);
        
        // Work out the probable location of return.php since this sample
        // code could be anywhere on a development server.
        //$returnUrl = add_query_arg( 'sessionid', $this->cart_data['session_id'], get_option( 'transact_url' ) );
        $returnUrl =  $this->cart_data['transaction_results_url'];
        // Set some transaction details
        $pxf->set_txn_detail('txnType', 'Purchase');	# required
        $pxf->set_txn_detail('currency', $this->cart_data['store_currency']);		# required
        $pxf->set_txn_detail('returnUrl', $returnUrl);	# required
        $pxf->set_txn_detail('amount', number_format($this->cart_data['total_price'],2));		# required
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
        
        // You should store these values in a database
        // ... they are needed to query the transaction's outcome
        // tran and seeion id seem to be the same
        $result = $response->GetTransactionIdResult;
        $transaction_id = $result->transactionId;
        $PXsession_id = trim($result->sessionId);
       
        $errorMsg = "";
        //get the credit card info from POST, will like to do better verafication in a future version
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
        if(isset($_POST['CardHolderName']) && strlen($_POST['CardHolderName']) > 0)
            $CardHolderName = $_POST['CardHolderName'];
       else
           $errorMsg .= "Credit Card Name    Required    <br/>";
       
       error_log('$errorMsg:' . var_export($errorMsg, TRUE));
        
        if(strlen($errorMsg) > 0){
            $this->set_error_message($errorMsg);
            header('Location: '.$this->cart_data['shopping_cart_url']);
            exit();
        }
        
        $this->set_purchase_processed_by_purchid(2);
        $this->set_transaction_details($transaction_id,2);
        // ok Im going to save the PX fusion session id in the Auth Code field, then check for this in the 
        // wpsc_transaction_theme() function
        
        global $wpdb;
        $wpdb->query( "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `authcode` = '" . $PXsession_id . "' WHERE `id` = " . absint( $this->purchase_id ) . " LIMIT 1" );
        
        
        
        
        $html = '
        <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"><html lang="en"><head><title></title></head><body>
        <div STYLE="display:none;">
        	<form id="px_form" enctype="multipart/form-data" action="https://sec.paymentexpress.com/pxmi3/pxfusionauth" method="post">
				<input type="hidden" name="SessionId" value="'. $PXsession_id . '" />
				<input type="hidden" name="Action" value="Add" />
				<input type="hidden" name="Object" value="DpsPxPay" />
				<input name="CardNumber" value="'.$CardNumber.'"  />
				<input name="ExpiryMonth" value="'. $ExpiryMonth .'"  /> 
				<input  name="ExpiryYear" value="'. $ExpiryYear .'"  />
				<input  name="Cvc2" value="' . $Cvc2 .'"  />
				<input  name="CardHolderName" value="' . $CardHolderName . '" />
				<script language="javascript" type="text/javascript">document.getElementById(\'px_form\').submit();</script>
			</form>
			</div>
			
		</body></html>
        ';
        echo $html;
   }
}

//$_SESSION['pxfusion'] = 'in_active';
if( isset($_GET['sessionid']) && strlen($_GET['sessionid']) > 20) {

    
    add_action('init', 'wpec_pxfusion_return');
    
}
function wpec_pxfusion_return(){
    
    if(isset($_GET['sessionid'])){
        $PXsessionid = $_GET['sessionid'];
        
    } else {
        wp_die('Session id error');
    }
    
    global $wpdb; 
    $query = "SELECT `transactid`,`sessionid` FROM  `" .WPSC_TABLE_PURCHASE_LOGS. "` WHERE  `authcode` ='" . $PXsessionid . "'";
    $results = $wpdb->get_results($query,'ARRAY_A');
    
    $pxTransactionid = $results[0]['transactid'];
    $sessionid       = $results[0]['sessionid'];
    
    
    $options = get_option('wpec_pxfusion');
    $pxf = new PxFusion($options['username'],$options['password']);
    
    $response = $pxf->get_transaction($pxTransactionid);
    $transaction_details = get_object_vars($response->GetTransactionResult);
    //debug info
    //error_log('$transaction_details:' . var_export($transaction_details, TRUE));
    
    switch($transaction_details['status']){
        case 0:
            //'approved';
            $success = 'Completed';
            break;
        case 1:
            //declined
            $success = 'Failed';
            break;
        case 2:
            //transient error, retry
            $success = 'Failed';
            break;
        case 3:
            //'invalid data';
            $success = 'Failed';
            break;
        case 4:
            //'result cannot be determined at this time, retry';
            $success = 'Failed';
            break;
        case 5:
            //failed due timeout or canceled
            $success = 'Failed';
            break;
        case 6:
            //transaction not found'
            $success = 'Failed';
            break;
    }
      
        switch ( $success ) {
        case 'Completed':
            $wpdb->query( 	"UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "`
                				SET `processed` = '3',  
                				`notes` = 'PX Fusion Status: " . $transaction_details['responseText']. "' 
                				WHERE `sessionid` = '" . $sessionid . "' LIMIT 1" );
    		transaction_results($sessionid,true);
            break;
        case 'Failed': // if it fails...
             
            $wpdb->query(   "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed` = '6',
                				`notes` = 'PX Fusion Status: " . $transaction_details['responseText']. "'  
                				WHERE `sessionid` = '" . $sessionid . "' LIMIT 1" );
            // redirect to checkout page with an error
            $checkout_page_url = get_option('shopping_cart_url');
            if($checkout_page_url){
            	$_SESSION['wpsc_checkout_misc_error_messages'][] = '<strong>' . $transaction_details['responseText'] . '</strong>';
            	header('Location: '.$checkout_page_url);
            	exit();
            }
            break;
            
        
    }
    
    
}
class PxFusion
{
    // DPS Px Fusion Details
    protected $fusion_username;
    protected $fusion_password;
    protected $wsdl = 'https://sec.paymentexpress.com/pxf/pxf.svc?wsdl';

    // Variables/Objects that are used to hold data for transactions
    public $tranDetail;
    protected $soap_client;

    public function __construct($username,$password)
    {
        $this->fusion_username = $username;
        $this->fusion_password = $password;
        
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
			'username' => $this->fusion_username,
			'password' => $this->fusion_password,
			'tranDetail' => get_object_vars($this->tranDetail) # extracts all properties of object into associative array
        );

        $response = $this->soap_client->GetTransactionId($array_for_soap);
        return $response;
    }

    public function get_transaction($transaction_id)
    {
        $this->soap_client = new SoapClient($this->wsdl, array('soap_version' => SOAP_1_1));
        $array_for_soap = array(
			'username' => $this->fusion_username,
			'password' => $this->fusion_password,
			'transactionId' => $transaction_id
        );

        $response = $this->soap_client->GetTransaction($array_for_soap);
        return $response;
    }
}
?>
