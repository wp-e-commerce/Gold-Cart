<?php
//Gateway Details
$nzshpcrt_gateways[$num]['name'] 			        = __( 'Sagepay', 'wpsc_gold_cart' );
$nzshpcrt_gateways[$num]['class_name']              = 'Sagepay_merchant';
$nzshpcrt_gateways[$num]['internalname']	        = 'sagepay';
$nzshpcrt_gateways[$num]['api_version']             = 2.0;
$nzshpcrt_gateways[$num]['form']                    = 'wpec_sagepay_admin_form';
$nzshpcrt_gateways[$num]['submit_function']         = 'wpec_sagepay_submit_form';
$nzshpcrt_gateways[$num]['has_recurring_billing']   = false;
$nzshpcrt_gateways[$num]['wp_admin_cannot_cancel']  = false;
$nzshpcrt_gateways[$num]['payment_type']            = __( 'credit card', 'wpsc_gold_cart' );
$nzshpcrt_gateways[$num]['display_name']            = __( 'pay with Sagepay', 'wpsc_gold_cart' );
$nzshpcrt_gateways[$num]['requirements']            = array('php_version' => 5.0, 'extra_modules' => array() );

function wpec_sagepay_admin_form(){
    // construct the default email message, this message is
    //included toward the top of the customer confirmation e-mails.
    $emailmsg = sprintf ( __( 'Thanks for purchasing at %s', 'wspc' ), get_bloginfo( 'name' ) );
    if ( get_bloginfo('admin_email') ) {
        $shopEmail = get_bloginfo('admin_email');
    } else {
        $shopEmail ='';
    }

    // Add the options, this will be igonerod if this option already exists
    $args = array(		'name'         => 'name',
                        'encrypt_key'  => 'key',
                        'shop_email'   => $shopEmail,
                        'email'		   =>  1,
                        'email_msg'    => $emailmsg,
                        'server_type'  => 'live',
                        'transact_url' => '',
                        'seperator'    => ''); // the last two will be set in the mercahnt class
   add_option('wpec_sagepay',$args);

   // get the sapay options for display in the form
   (array) $option = get_option('wpec_sagepay');

   // make sure the stores currency is supported by sagepay
   $curr_supported = false;
   global $wpdb;
   $currency_code  = $wpdb->get_var( "SELECT `code` FROM `" . WPSC_TABLE_CURRENCY_LIST .
   									"` WHERE `id`='" . get_option( 'currency_type' ) . "' LIMIT 1" );
   switch ($currency_code){
       case 'GBP':
           $curr_supported = true;
           break;
       case 'EUR':
           $curr_supported = true;
           break;
       case 'USD':
       		$curr_supported = true;
           break;
   }

   $adminFormHTML = '
		<tr>
			<td>
				Protx Vendor name:
			</td>
			<td>
				<input type="text" size="40" value="'. $option['name'] .'" name="wpec_sagepay_name" />
			</td>
		</tr>
		<tr>
			<td>
				Protx Encryption Key:
			</td>
			<td>
				<input type="text" size="20" value="'. $option['encrypt_key'] .'" name="wpec_sagepay_encrypt_key" />
			</td>
		</tr>
		<tr>
			<td>
            ' . __(' Shop Email, an e-mail will be sent to this address when each transaction completes (successfully or otherwise). If this field is blank then no email address will be provided', 'wpsc_gold_cart' ) .'
			</td>
			<td>
				<input type="text" size="20" value="'. $option['shop_email'] .'" name="wpec_sagepay_shop_email" />
			</td>
		</tr>
		<tr>
			<td>
				' . __( 'Email Message. If set then this message is included toward the top of the customer confirmation e-mails.', 'wpsc_gold_cart' ) . '
			</td>
			<td>
				<textarea name="wpec_sagepay_email_msg" rows="10" >'. $option['email_msg'] .'</textarea>
			</td>
		</tr>
		<tr>
			<td>
				'.__( 'Sagepay Email options', 'wpsc_gold_cart' ) . '
			</td>
			<td>
				<select class="widefat" name="wpec_sagepay_email">
					<option value="0" '.selected($option['email'] , 0,false) .'>'  . __( 'Do not send either customer or vendor e- mails', 'wpsc_gold_cart' ) .'</option>
					<option value="1" '.selected($option['email'] , 1,false) .' >' . __( 'Send customer and vendor e-mails', 'wpsc_gold_cart' ) . '</option>
					<option value="2" '.selected($option['email'] , 2,false) .' >' . __( 'Send vendor e-mail but NOT the customer e-mail', 'wpsc_gold_cart' ). '</option>
    			</select>
			</td>
		</tr>
		<tr>
			<td>
				' . __( 'Server Type:', 'wpsc_gold_cart' ) . '
			</td>
			<td>
				<select lass="widefat" name="wpec_sagepay_server_type">
					<option  value="test"'.selected($option['server_type'] , 'test',false) .' >' . __( 'Test Server', 'wpsc_gold_cart' ) . '</option>
					<option  value="sim" '.selected($option['server_type'] , 'sim',false) .' >'  . __( 'Simulator Server', 'wpsc_gold_cart' ) . '</option>
					<option  value="live"'.selected($option['server_type'] , 'live',false) .' >' . __( 'Live Server', 'wpsc_gold_cart' ) . '</option>
				</select>
			</td>
		</tr>';
    if(!$curr_supported)
    {
       $adminFormHTML .='
       <tr>
        	<td>
        		<strong style="color:red;">'. __( 'Your Selected Currency is not supported by Sageapy,
        		to use Sagepay, go the the stores general settings and under &quot;Currency Type&quot; select one
        		of the currencies listed on the right.', 'wpsc_gold_cart' ) .' </strong>
         	</td>
        	<td>
       			<ul>';
        $country_list  = $wpdb->get_results( "SELECT `country` FROM `" . WPSC_TABLE_CURRENCY_LIST .
        									 "` WHERE `code` IN( 'USD','GBP','EUR') ORDER BY `country` ASC" ,'ARRAY_A');
        foreach($country_list as $country){
            $adminFormHTML .= '<li>'. $country['country'].'</li>';
        }
        $adminFormHTML .= '</ul>
        	</td>
        </tr>';
    } else {
        $adminFormHTML .='
        <tr>
        	<td colspan="2">
        	<strong style="color:green;"> '.__( 'Your Selected Currency will work with Sagepay', 'wpsc_gold_cart' ). ' </strong>
        	</td>
        </tr>
        ';
    }


    return $adminFormHTML;
}
function wpec_sagepay_submit_form(){
    // a flag to run the update_option function
    $flag = false;
    $sagepay_options = get_option('wpec_sagepay');

    if( isset($_POST['wpec_sagepay_encrypt_key'])){
        $sagepay_options['encrypt_key'] = rtrim($_POST['wpec_sagepay_encrypt_key']);
        $flag = true;
    }
    if(isset($_POST['wpec_sagepay_name'])){
        $sagepay_options['name'] =  rtrim($_POST['wpec_sagepay_name']);
        $flag = true;
    }
    if(isset($_POST['wpec_sagepay_shop_email'])){
        $sagepay_options['shop_email'] = $_POST['wpec_sagepay_shop_email'];
        $flag = true;
    }
    if(isset($_POST['wpec_sagepay_email'])){
        $sagepay_options['email'] = $_POST['wpec_sagepay_email'];
        $flag = true;
    }
    if(isset($_POST['wpec_sagepay_server_type'])){
        $sagepay_options['server_type'] = $_POST['wpec_sagepay_server_type'];
        $flag = true;
    }
    if(isset($_POST['wpec_sagepay_email_msg'])){
        // TODO validate html
        $valid_msg = $_POST['wpec_sagepay_email_msg'];
        $sagepay_options['email_msg'] = $valid_msg;
        $flag = true;
    }

    if($flag)
        update_option('wpec_sagepay', $sagepay_options);
}

class Sagepay_merchant extends wpsc_merchant {

    private $strPost = '';
    private $sagepay_options = array();
    private $seperator = '?';

    public function __construct($purchase_id =null,$is_receiving = false ){


        if(get_option('permalink_structure') != '')
            $this->separator ="?";
        else
           $this->separator ="&";

        $this->sagepay_options =  get_option('wpec_sagepay');
        
        $this->sagepay_options['seperator'] = $this->separator;

        wpsc_merchant::__construct($purchase_id , $is_receiving);
    }


    public function construct_value_array(){

		$this->sagepay_options['transact_url'] = $this->cart_data['transaction_results_url'];
        // get the options from the form

        //1 construct $strPost string,
        $this->strPost = $this->addContrustinfo($this->strPost);
        $this->strPost = $this->addBasketInfo($this->strPost);
        $this->strPost = base64_encode(Sagepay_merchant::simpleXor($this->strPost, $this->sagepay_options['encrypt_key']));

    }
    private function addContrustinfo($strPost){

        // helper vars to populate the following temporary vars
        $billInfo = $this->cart_data['billing_address'];
        $shipInfo = $this->cart_data['shipping_address'];
        // temporary vars that will be added to the $strPost string in url format
        $strBillingFirstnames  = $this->cleanInput( $billInfo['first_name'], CLEAN_INPUT_FILTER_TEXT);
        $strBillingSurname     = $this->cleanInput( $billInfo['last_name'], CLEAN_INPUT_FILTER_TEXT);
        $strBillingAddress1    = $this->cleanInput( $billInfo['address'], CLEAN_INPUT_FILTER_TEXT);
        $strBillingCity        = $this->cleanInput( $billInfo['city'], CLEAN_INPUT_FILTER_TEXT);
        $strBillingPostCode    = $this->cleanInput( $billInfo['post_code'], CLEAN_INPUT_FILTER_TEXT);
        $strBillingCountry     = $this->cleanInput( $billInfo['country'], CLEAN_INPUT_FILTER_TEXT);
        if($strBillingCountry == 'UK') $strBillingCountry= 'GB';
        $strBillingState       = $this->cleanInput( $billInfo['state'], CLEAN_INPUT_FILTER_TEXT);
        // no state required if not in the US
        if($strBillingCountry != 'US') $strBillingState = '';
        $strCustomerEMail      = $this->cleanInput( $this->cart_data['email_address'], CLEAN_INPUT_FILTER_TEXT);
        $strDeliveryFirstnames = $this->cleanInput( $shipInfo['first_name'], CLEAN_INPUT_FILTER_TEXT);
        $strDeliverySurname    = $this->cleanInput( $shipInfo['last_name'], CLEAN_INPUT_FILTER_TEXT);
        $strDeliveryAddress1   = $this->cleanInput( $shipInfo['address'], CLEAN_INPUT_FILTER_TEXT);
        $strDeliveryCity       = $this->cleanInput( $shipInfo['city'], CLEAN_INPUT_FILTER_TEXT);
        $strDeliveryState      = $this->cleanInput( $shipInfo['state'], CLEAN_INPUT_FILTER_TEXT);
        $strDeliveryCountry    = $this->cleanInput( $shipInfo['country'], CLEAN_INPUT_FILTER_TEXT);
        if($strDeliveryCountry == 'UK') $strDeliveryCountry= 'GB';
        // no state required if not in the US
        if($strDeliveryCountry != 'US') $strDeliveryState = '';
			$strDeliveryPostCode   = $this->cleanInput( $shipInfo['post_code'], CLEAN_INPUT_FILTER_TEXT);



        // begin to populate the $strPost, witch will be sent
        // First we need to generate a unique VendorTxCode for this transaction **
        // Begin of constructing the $strPost url string  For more details see the Form Protocol 2.23

        $strPost .= 'VendorTxCode=' . $this->cart_data['session_id'];
        // amount
        $strPost .= '&Amount=' .number_format($this->cart_data['total_price'],2) ;
        // currentcy
        $strPost .= '&Currency=' . $this->cart_data['store_currency'] ;
        // discription HTML
        //TODO check where this is ouput and if it looks ok
        $description = '';
        $comma_count = 0;
        foreach($this->cart_items as $cartItem){
        	if($comma_count > 0)
            	$description .= ' ,';

            $description .= $cartItem['name'] ;

            $comma_count++;
        }
        if( strlen($description) >= 100){
            $description = substr($description , 0 , 94) . '...';

        }
        $strPost .= '&Description=' . $description;
        $strPost .= '&SuccessURL=' . $this->cart_data['transaction_results_url'] . $this->seperator;
        $strPost .= '&FailureURL=' . $this->cart_data['transaction_results_url'] . $this->seperator;
        $strPost .= '&CustomerName=' . $strBillingFirstnames . ' ' . $strBillingSurname;
        $strPost .= '&CustomerEMail=' . $strCustomerEMail;

        if(strlen($this->sagepay_options['shop_email']) > 0)
            $strPost .= '&VendorEMail=' . $this->sagepay_options['shop_email'];

        $strPost .= '&SendEMail=' . $this->sagepay_options['email'];
        // if send Email is selected and the email discription is not empty
        if($this->sagepay_options['email'] == '1' && strlen($this->sagepay_options['email_msg']) > 0)
            $strPost .= '&eMailMessage=' . $this->sagepay_options['email_msg'];

        // Billing Details:
        $strPost .= "&BillingFirstnames=" . $strBillingFirstnames;
        $strPost .= "&BillingSurname=" . $strBillingSurname;
        $strPost .= "&BillingAddress1=" . $strBillingAddress1;
        $strPost .= "&BillingCity=" . $strBillingCity;
        $strPost .= "&BillingPostCode=" . $strBillingPostCode;
        $strPost .= "&BillingCountry=" . $strBillingCountry;
        if (strlen($strBillingState) > 0) $strPost .= "&BillingState=" . $strBillingState;
        if (strlen($strBillingPhone) > 0) $strPost .= "&BillingPhone=" . $strBillingPhone;


		// Shipping Details:
        // if the shipping info isnt present then assign the billing info
        (strlen($strDeliveryFirstnames ) > 0)  ? $strPost .= "&DeliveryFirstnames=" .  $strDeliveryFirstnames : $strPost .= "&DeliveryFirstnames=" .  $strBillingFirstnames;
        (strlen($strDeliverySurname ) > 0)     ? $strPost .= "&DeliverySurname=" . $strDeliverySurname        : $strPost .= "&DeliverySurname=" . $strBillingSurname;
        (strlen($strDeliveryAddress1) > 0)     ? $strPost .= "&DeliveryAddress1=" . $strDeliveryAddress1      : $strPost .= "&DeliveryAddress1=" . $strBillingAddress1;
        (strlen($strDeliveryCity) > 0)         ? $strPost .= "&DeliveryCity=" . $strDeliveryCity              : $strPost .= "&DeliveryCity=" . $strBillingCity;
        (strlen($strDeliveryPostCode) > 0)     ? $strPost .= "&DeliveryPostCode=" . $strDeliveryPostCode      : $strPost .= "&DeliveryPostCode=" . $strBillingPostCode;
        (strlen($strDeliveryCountry) > 0)      ? $strPost .= "&DeliveryCountry=" . $strDeliveryCountry        : $strPost .= "&DeliveryCountry=" . $strBillingCountry;

       if (strlen($strDeliveryState) > 0 || strlen($strBillingState) > 0){
           if(strlen($strDeliveryState) > 0){
               $strPost .=  "&DeliveryState=" . $strDeliveryState;
           } else if(strlen($strBillingState) > 0 && $strDeliveryCountry == 'US'){
               $strPost .=  "&DeliveryState=" .$strBillingState;
           }
       }

       if(strlen($strDeliveryPhone) > 0 || strlen($strBillingPhone) > 0){
           if(strlen($strDeliveryPhone) > 0){
               $strPost .=  "&DeliveryPhone=" . $strDeliveryPhone;
           } else if(strlen($strBillingPhone) > 0){
               $strPost .=  "&DeliveryPhone=" .$strBillingPhone;
           }
       }

      return $strPost;
    }

    private function addBasketInfo($strPost){


        $basket_rows = (count($this->cart_items) + 1);
        // TODO test discount row as this is not metioned in the pdf
        if($this->cart_data['has_discounts']){
            // another row for the discount row
            $basket_rows += 1;
        }
        //The first value �The number of lines of detail in the basket� is
        //NOT the total number of items ordered, but the total number of rows
        //of basket information
        $cartString = $basket_rows ;

        foreach ( $this->cart_items as $item ) {
            global $wpsc_cart;
            // tax percent
            $tax = '0.00';
            $itemWithTax = $item['price'];
            // first check if the individual product has tax applied
            if($item['tax'] != '0.00'){
                $tax = $item['tax'];
            } elseif (!empty($wpsc_cart->tax_percentage)){
                //if not check for a global tax rate
                //TODO see if this is correct
                $onePecent = $item['price'] / 100;
                $tax = number_format($onePecent * $wpsc_cart->tax_percentage, 2, '.', '');
                $itemWithTax = $tax + $item['price'];

            }
            // Description, remove (): if product has variations
			$restricted_vars = array("(", ")", ":");
			$item['name'] = str_replace($restricted_vars,'',$item['name']);
            $cartString .= ':' . $item['name'];
            // Quantity of this item
            $cartString .=  ':' . $item['quantity'];
            // base price without tax of single
            $cartString .=  ':' . $item['price'];
            // tax amount for single
            $cartString .=  ':' . $tax ; //TODO find tax percentage to create these values
            // Tax applied to price of single
            $cartString .= ':' . $itemWithTax;
            // Total cost of item * quantity
            $cartString .= ':' . number_format((float)$itemWithTax * $item['quantity'], 2, '.', '');
        }

        $cartString .= ':shipping:---:---:---:---:' .$this->cart_data['base_shipping'] ;

        if ( $this->cart_data['has_discounts'] ) {
            $cartString .= ':Discount:'. $this->cart_data['cart_discount_coupon'] . ':---:---:---:' . $this->cart_data['cart_discount_value'];
        }

        $strPost .= "&Basket=" . $cartString;
        return $strPost;
    }
    public function submit() {

        $servertype = $this->sagepay_options['server_type'];
        $url = '';
        if ( $servertype == 'test' ) {
            $url = 'https://test.sagepay.com/gateway/service/vspform-register.vsp';
        } elseif ( $servertype == 'sim' ) {
            $url = 'https://test.sagepay.com/Simulator/VSPFormGateway.asp';
        } elseif ( $servertype == 'live' ) {
            $url = 'https://live.sagepay.com/gateway/service/vspform-register.vsp';
        }
        //TODO update purchase logs to pending
        $this->set_purchase_processed_by_purchid(2);

        $output =
        '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"><html lang="en"><head><title></title></head><body>
        	<form id="sagepay_form" name="sagepay_form" method="post" action="' .$url . '">
       			<input type="hidden"    name="VPSProtocol"  value ="2.23" ></input>
        		<input type="hidden"    name="TxType"       value ="PAYMENT"  ></input>
        		<input type="hidden"    name="Vendor"       value ="'. $this->sagepay_options['name'] . '"  ></input>
        		<input type="hidden"    name="Crypt"        value ="'. $this->strPost . '"  ></input>
        	</form>
        <script language="javascript" type="text/javascript">document.getElementById(\'sagepay_form\').submit();</script>
        </body></html>';

        echo $output;
    }

    public function parse_gateway_notification() {


    }

    public function process_gateway_notification() {


    }

    private function cleanInput($strRawText, $filterType){

        $strAllowableChars = "";
        $blnAllowAccentedChars = FALSE;
        $strCleaned = "";
        $filterType = strtolower($filterType); //ensures filterType matches constant values

        if ($filterType == CLEAN_INPUT_FILTER_TEXT){

            $strAllowableChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,'/\\{}@():?-_&�$=%~*+\"\n\r";
            $strCleaned = $this->cleanInput2($strRawText, $strAllowableChars, TRUE);
        }
        elseif ($filterType == CLEAN_INPUT_FILTER_NUMERIC){

            $strAllowableChars = "0123456789 .,";
            $strCleaned = $this->cleanInput2($strRawText, $strAllowableChars, FALSE);
        }
        elseif ($filterType == CLEAN_INPUT_FILTER_ALPHABETIC || $filterType == CLEAN_INPUT_FILTER_ALPHABETIC_AND_ACCENTED){

            $strAllowableChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ abcdefghijklmnopqrstuvwxyz";
            if ($filterType == CLEAN_INPUT_FILTER_ALPHABETIC_AND_ACCENTED) $blnAllowAccentedChars = TRUE;
                $strCleaned = $this->cleanInput2($strRawText, $strAllowableChars, $blnAllowAccentedChars);
        }
        elseif ($filterType == CLEAN_INPUT_FILTER_ALPHANUMERIC || $filterType == CLEAN_INPUT_FILTER_ALPHANUMERIC_AND_ACCENTED){

            $strAllowableChars = "0123456789 ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
            if ($filterType == CLEAN_INPUT_FILTER_ALPHANUMERIC_AND_ACCENTED) $blnAllowAccentedChars = TRUE;
            $strCleaned = $this->cleanInput2($strRawText, $strAllowableChars, $blnAllowAccentedChars);
        }
        else{ // Widest Allowable Character Range

            $strAllowableChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,'/\\{}@():?-_&�$=%~*+\"\n\r";
            $strCleaned = $this->cleanInput2($strRawText, $strAllowableChars, TRUE);
        }

        return $strCleaned;
    }

    /**
    * Filters unwanted characters out of an input string based on an allowable character set.
    * Useful for tidying up FORM field inputs
    *
    *
    * @param	string	$strRawText				value to clean.
    * @param	string	$strAllowableChars	    a string of characters allowable in "strRawText" if its to be deemed valid.
    * @param	boolean	$blnAllowAccentedChars	 determines if "strRawText" can contain Accented or High-order characters
    * @return	string $strCleanedText
    */

    private function cleanInput2($strRawText, $strAllowableChars, $blnAllowAccentedChars)
    {
        $iCharPos = 0;
        $chrThisChar = "";
        $strCleanedText = "";

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

    /*  The SimpleXor encryption algorithm                                                                                **
    **  NOTE: This is a placeholder really.  Future releases of Form will use AES or TwoFish.  Proper encryption      **
    **  This simple function and the Base64 will deter script kiddies and prevent the "View Source" type tampering        **
    **  It won't stop a half decent hacker though, but the most they could do is change the amount field to something     **
    **  else, so provided the vendor checks the reports and compares amounts, there is no harm done.  It's still          **
    **  more secure than the other PSPs who don't both encrypting their forms at all                                      */

   public static function simpleXor($InString, $Key) {
        // Initialise key array
        $KeyList = array();
        // Initialise out variable
        $output = "";

        // Convert $Key into array of ASCII values
        for($i = 0; $i < strlen($Key); $i++){
            $KeyList[$i] = ord(substr($Key, $i, 1));
        }

        // Step through string a character at a time
        for($i = 0; $i < strlen($InString); $i++) {
            // Get ASCII code from string, get ASCII code from key (loop through with MOD), XOR the two, get the character from the result
            // % is MOD (modulus), ^ is XOR
            $output.= chr(ord(substr($InString, $i, 1)) ^ ($KeyList[$i % strlen($Key)]));
        }

        // Return the result
        return $output;
    }

}


add_filter('wpsc_previous_selected_gateway_sagepay', 'sagepay_process_gateway_info', 10, 1);

function sagepay_process_gateway_info($sessionid){
    // first set up all the vars that we are going to need later
    $sagepay_options =  get_option('wpec_sagepay');

    $crypt = str_replace( " ", "+", $_GET['crypt'] );
    $uncrypt = Sagepay_merchant::simpleXor( base64_decode( $crypt ), $sagepay_options['encrypt_key'] );
    parse_str( $uncrypt, $unencrypted_values );


    $success = '';
    switch ( $unencrypted_values['Status'] ) {
        case 'NOTAUTHED':
        case 'REJECTED':
            $success = 'Failed';
            break;
        case 'MALFORMED':
        case 'INVALID':
            $success = 'Failed';
            break;
        case 'ERROR':
            $success = 'Failed';
            break;
        case 'ABORT':
            $success = 'Failed';
            break;
        case 'AUTHENTICATED': // Only returned if TxType is AUTHENTICATE
            $success = 'Pending';
        case 'REGISTERED': // Only returned if TxType is AUTHENTICATE
            $success = 'Failed';
            break;
        case 'OK':
            $success = 'Completed';
            break;
        default:
            break;
    }
    global $sessionid;
    switch ( $success ) {
        case 'Completed':
            $purchase_log = new WPSC_Purchase_Log( $unencrypted_values['VendorTxCode'], 'sessionid' );
            $purchase_log->set( array(
                'processed'  => WPSC_Purchase_Log::ACCEPTED_PAYMENT,
                'transactid' => $unencrypted_values['VPSTxId'],
                'notes'      => 'SagePay Status: ' . $unencrypted_values['Status'],
            ) );
            $purchase_log->save();

            // set this global, wonder if this is ok
            $sessionid = $unencrypted_values['VendorTxCode'];
            transaction_results($sessionid,true);

            break;
        case 'Failed': // if it fails...
            switch ( $unencrypted_values['Status'] ) {
                case 'NOTAUTHED':
                case 'REJECTED':
                case 'MALFORMED':
                case 'INVALID':
				case 'ABORT':
                case 'ERROR':
                    $purchase_log = new WPSC_Purchase_Log( $unencrypted_values['VendorTxCode'], 'sessionid' );
                    $purchase_log->set( array(
                        'processed'  => WPSC_Purchase_Log::INCOMPLETE_SALE,
                        'notes'      => 'SagePay Status: ' . $unencrypted_values['Status'],
                    ) );
                    $purchase_log->save();
					// if it fails redirect to the shopping cart page with the error
					// redirect to checkout page with an error
					$error_messages = wpsc_get_customer_meta( 'checkout_misc_error_messages' );
					if ( ! is_array( $error_messages ) )
						$error_messages = array();
					$error_messages[] = '<strong style="color:red">' . $unencrypted_values['StatusDetail'] . ' </strong>';
					wpsc_update_customer_meta( 'checkout_misc_error_messages', $error_messages );
					$checkout_page_url = get_option( 'shopping_cart_url' );
					if ( $checkout_page_url ) {
					  header( 'Location: '.$checkout_page_url );
					  exit();
					}
                    break;
            }
            break;
        case 'Pending': // need to wait for "Completed" before processing
            $purchase_log = new WPSC_Purchase_Log( $unencrypted_values['VendorTxCode'], 'sessionid' );
            $purchase_log->set( array(
                'processed'  => WPSC_Purchase_Log::ORDER_RECEIVED,
                'transactid' => $unencrypted_values['VPSTxId'],
                'date'       => time(),
                'notes'      => 'SagePay Status: ' . $unencrypted_values['Status'],
            ) );
            $purchase_log->save();
			// redirect to checkout page with an error
			$error_messages = wpsc_get_customer_meta( 'checkout_misc_error_messages' );
			if ( ! is_array( $error_messages ) )
				$error_messages = array();
			$error_messages[] = '<strong style="color:red">' . $unencrypted_values['StatusDetail'] . ' </strong>';
			wpsc_update_customer_meta( 'checkout_misc_error_messages', $error_messages );
			$checkout_page_url = get_option( 'shopping_cart_url' );
			if ( $checkout_page_url ) {
			  header( 'Location: '.$checkout_page_url );
			  exit();
			}
            break;
    }

    return $unencrypted_values['VendorTxCode'];
}