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

// Defines filter types used for a parameter in the cleanInput() function.
define( 'WPSC_SAGEPAY_CLEAN_INPUT_FILTER_ALPHABETIC', 'clean_input_filter_alphabetic' );
define( 'WPSC_SAGEPAY_CLEAN_INPUT_FILTER_ALPHABETIC_AND_ACCENTED', 'clean_input_filter_alphabetic_and_accented' );
define( 'WPSC_SAGEPAY_CLEAN_INPUT_FILTER_ALPHANUMERIC', 'clean_input_filter_alphanumeric' );
define( 'WPSC_SAGEPAY_CLEAN_INPUT_FILTER_ALPHANUMERIC_AND_ACCENTED', 'clean_input_filter_alphanumeric_and_accented' );
define( 'WPSC_SAGEPAY_CLEAN_INPUT_FILTER_NUMERIC', 'clean_input_filter_numeric' );
define( 'WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT', 'clean_input_filter_text' );
define( 'WPSC_SAGEPAY_CLEAN_INPUT_FILTER_WIDEST_ALLOWABLE_CHARACTER_RANGE', 'clean_input_filter_text' );

function wpec_sagepay_admin_form(){
    // construct the default email message, this message is
    //included toward the top of the customer confirmation e-mails.
    $emailmsg = sprintf ( __( 'Thanks for purchasing at %s', 'wpsc_gold_cart' ), get_bloginfo( 'name' ) );
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
			<td>' . esc_html__( 'SagePay Vendor name', 'wpsc_gold_cart' ) . ':</td>
			<td>
				<input type="text" size="40" value="'. $option['name'] .'" name="wpec_sagepay_name" />
			</td>
		</tr>
		<tr>
			<td>' . esc_html__( 'SagePay Encryption Key', 'wpsc_gold_cart' ) . ':</td>
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
			<td>' . esc_html__( 'Transaction Type', 'wpsc_gold_cart' ) . '</td>
			<td>
				<select name="wpec_sagepay_payment_type">
					<option value="PAYMENT"' . selected( $option['payment_type'], 'PAYMENT', false ) . '>' . esc_html__( 'PAYMENT', 'wpsc_gold_cart' ) . '</option>
					<option value="AUTHENTICATE"' . selected( $option['payment_type'], 'AUTHENTICATE', false ) . ' >' . esc_html__( 'AUTHENTICATE', 'wpsc_gold_cart' ) . '</option>
				</select>
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

	/**
     * Some servers may not have the PHP Mcrypt module enabled by default.
     * Show a message in the SagePay settings if this is the case.
     */
	if ( ! function_exists( 'mcrypt_encrypt' ) ) {
		$adminFormHTML .= '
			<tr>
				<td colspan="2" style="color: red;">
					' . sprintf( __( 'The <a %s>mcrypt_encrypt()</a> function which is required to send encrypted data to SagePay does not seem to be available on your server. Please <a %s>install the Mcrypt PHP module</a> or ask your web host to activate this for you.', 'wpsc_gold_cart' ), 'href="http://php.net/manual/en/function.mcrypt-encrypt.php" target="php" style="color: red; text-decoration: underline;"', 'href="http://be2.php.net/manual/en/mcrypt.installation.php" target="php" style="color: red; text-decoration: underline;"' ) . '
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
	if ( isset( $_POST['wpec_sagepay_payment_type'] ) ) {
		$sagepay_options['payment_type'] = wpec_sagepay_validate_payment_type( $_POST['wpec_sagepay_payment_type'] );
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
        $this->strPost = Sagepay_merchant::encryptAes($this->strPost, $this->sagepay_options['encrypt_key']);

    }
    private function addContrustinfo($strPost){

        // helper vars to populate the following temporary vars
        $billInfo = $this->cart_data['billing_address'];
        $shipInfo = $this->cart_data['shipping_address'];
		
		
		$strCustomerEMail      = $this->cleanInput( $this->cart_data['email_address'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT);
		
        // temporary vars that will be added to the $strPost string in url format
        $strBillingFirstnames  = $this->cleanInput( $billInfo['first_name'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT);
        $strBillingSurname     = $this->cleanInput( $billInfo['last_name'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT);
        $strBillingAddress1    = $this->cleanInput( $billInfo['address'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT);
        $strBillingCity        = $this->cleanInput( $billInfo['city'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT);
        $strBillingPostCode    = $this->cleanInput( $billInfo['post_code'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT);
        $strBillingCountry     = $this->cleanInput( $billInfo['country'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT);
        if($strBillingCountry == 'UK') $strBillingCountry= 'GB';
        $strBillingState       = $this->cleanInput( $billInfo['state'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT);
        // no state required if not in the US
        if($strBillingCountry != 'US') $strBillingState = '';		
		if ( isset ( $billInfo['phone'] ) && $billInfo['phone'] != '' ) {
			$strBillingPhone = $this->cleanInput( $billInfo['phone'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT);
		}

        //Shipping info
        $strDeliveryFirstnames = isset( $shipInfo['first_name'] )	? $this->cleanInput( $shipInfo['first_name'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT) : $strBillingFirstnames;
        $strDeliverySurname    = isset( $shipInfo['last_name'] ) 	? $this->cleanInput( $shipInfo['last_name'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT) : $strBillingSurname;
        $strDeliveryAddress1   = isset( $shipInfo['address'] ) 		? $this->cleanInput( $shipInfo['address'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT) : $strBillingAddress1;
        $strDeliveryCity       = isset( $shipInfo['city'] ) 		? $this->cleanInput( $shipInfo['city'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT) : $strBillingCity;
        $strDeliveryState      = isset( $shipInfo['state'] ) 		? $this->cleanInput( $shipInfo['state'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT) : $strBillingState;
        $strDeliveryCountry    = isset( $shipInfo['country'] ) 		? $this->cleanInput( $shipInfo['country'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT) : $strBillingCountry;
        if($strDeliveryCountry == 'UK') $strDeliveryCountry= 'GB';
        // no state required if not in the US
        if($strDeliveryCountry != 'US') $strDeliveryState = '';
		
		$strDeliveryPostCode   = isset( $shipInfo['post_code'] )		? $this->cleanInput( $shipInfo['post_code'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT) : $strBillingPostCode;

		if ( isset ( $shipInfo['phone'] ) && $shipInfo['phone'] != '' ) {
			$strDeliveryPhone = $this->cleanInput( $shipInfo['phone'], WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT);
		}

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
        $strPost .= '&SuccessURL=' . $this->cart_data['transaction_results_url'];
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
        if ( isset( $strBillingPhone ) && strlen($strBillingPhone) > 0) $strPost .= "&BillingPhone=" . $strBillingPhone;


		
		// Shipping Details:
		$strPost .= "&DeliveryFirstnames=" .  $strDeliveryFirstnames;
		$strPost .= "&DeliverySurname=" . $strDeliverySurname;
		$strPost .= "&DeliveryAddress1=" . $strDeliveryAddress1;
		$strPost .= "&DeliveryCity=" . $strDeliveryCity;
		$strPost .= "&DeliveryPostCode=" . $strDeliveryPostCode;
		$strPost .= "&DeliveryCountry=" . $strDeliveryCountry;
		
       if (strlen($strDeliveryState) > 0 || strlen($strBillingState) > 0){
           if(strlen($strDeliveryState) > 0){
               $strPost .=  "&DeliveryState=" . $strDeliveryState;
           } else if(strlen($strBillingState) > 0 && $strDeliveryCountry == 'US'){
               $strPost .=  "&DeliveryState=" .$strBillingState;
           }
       }

       /*if( ( isset( $strBillingPhone ) || isset( $strBillingPhone ) ) && ( strlen($strDeliveryPhone) > 0 || strlen($strBillingPhone) > 0 ) ){
           if(strlen($strDeliveryPhone) > 0){
               $strPost .=  "&DeliveryPhone=" . $strDeliveryPhone;
           } else if(strlen($strBillingPhone) > 0){
               $strPost .=  "&DeliveryPhone=" .$strBillingPhone;
           }
       }*/

	   return $strPost;
    }

    private function addBasketInfo($strPost){


        $basket_rows = (count($this->cart_items) + 1);
        // TODO test discount row as this is not metioned in the pdf
        if($this->cart_data['has_discounts']){
            // another row for the discount row
            $basket_rows += 1;
        }
        //The first value “The number of lines of detail in the basket” is
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
        //$this->set_purchase_processed_by_purchid(2);

        $output =
        '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"><html lang="en"><head><title></title></head><body>
        	<form id="sagepay_form" name="sagepay_form" method="post" action="' .$url . '">
       			<input type="hidden"    name="VPSProtocol"  value ="3.00" ></input>
        		<input type="hidden" name="TxType" value ="' . wpec_sagepay_validate_payment_type( $this->sagepay_options['payment_type'] ) . '"  ></input>
        		<input type="hidden"    name="Vendor"       value ="'. $this->sagepay_options['name'] . '"  ></input>
        		<input type="hidden"    name="Crypt"        value ="'. $this->strPost . '"  ></input>
        	</form>
        <script language="javascript" type="text/javascript">document.getElementById(\'sagepay_form\').submit();</script>
        </body></html>';

        echo $output;
		exit();
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

        if ($filterType == WPSC_SAGEPAY_CLEAN_INPUT_FILTER_TEXT){

            $strAllowableChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,'/\\{}@():?-_&£$=%~*+\"\n\r";
            $strCleaned = $this->cleanInput2($strRawText, $strAllowableChars, TRUE);
        }
        elseif ($filterType == WPSC_SAGEPAY_CLEAN_INPUT_FILTER_NUMERIC){

            $strAllowableChars = "0123456789 .,";
            $strCleaned = $this->cleanInput2($strRawText, $strAllowableChars, FALSE);
        }
        elseif ($filterType == WPSC_SAGEPAY_CLEAN_INPUT_FILTER_ALPHABETIC || $filterType == WPSC_SAGEPAY_CLEAN_INPUT_FILTER_ALPHABETIC_AND_ACCENTED){

            $strAllowableChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ abcdefghijklmnopqrstuvwxyz";
            if ($filterType == WPSC_SAGEPAY_CLEAN_INPUT_FILTER_ALPHABETIC_AND_ACCENTED) $blnAllowAccentedChars = TRUE;
                $strCleaned = $this->cleanInput2($strRawText, $strAllowableChars, $blnAllowAccentedChars);
        }
        elseif ($filterType == WPSC_SAGEPAY_CLEAN_INPUT_FILTER_ALPHANUMERIC || $filterType == WPSC_SAGEPAY_CLEAN_INPUT_FILTER_ALPHANUMERIC_AND_ACCENTED){

            $strAllowableChars = "0123456789 ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
            if ($filterType == WPSC_SAGEPAY_CLEAN_INPUT_FILTER_ALPHANUMERIC_AND_ACCENTED) $blnAllowAccentedChars = TRUE;
            $strCleaned = $this->cleanInput2($strRawText, $strAllowableChars, $blnAllowAccentedChars);
        }
        else{ // Widest Allowable Character Range

            $strAllowableChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,'/\\{}@():?-_&£$=%~*+\"\n\r";
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

    /**
     * PHP's mcrypt does not have built in PKCS5 Padding, so we use this.
     *
     * @param string $input The input string.
     *
     * @return string The string with padding.
     */
    static protected function addPKCS5Padding($input)
    {
        $blockSize = 16;
        $padd = "";

        // Pad input to an even block size boundary.
        $length = $blockSize - (strlen($input) % $blockSize);
        for ($i = 1; $i <= $length; $i++)
        {
            $padd .= chr($length);
        }

        return $input . $padd;
    }

    /**
     * Remove PKCS5 Padding from a string.
     *
     * @param string $input The decrypted string.
     *
     * @return string String without the padding.
     * @throws SagepayApiException
     */
    static protected function removePKCS5Padding($input)
    {
        $blockSize = 16;
        $padChar = ord($input[strlen($input) - 1]);

        /* Check for PadChar is less then Block size */
        if ($padChar > $blockSize)
        {
            throw new SagepayApiException('Invalid encryption string');
        }
        /* Check by padding by character mask */
        if (strspn($input, chr($padChar), strlen($input) - $padChar) != $padChar)
        {
            throw new SagepayApiException('Invalid encryption string');
        }

        $unpadded = substr($input, 0, (-1) * $padChar);
        /* Chech result for printable characters */
        if (preg_match('/[[:^print:]]/', $unpadded))
        {
            throw new SagepayApiException('Invalid encryption string');
        }
        return $unpadded;
    }

    /**
     * Encrypt a string ready to send to SagePay using encryption key.
     *
     * @param  string  $string  The unencrypyted string.
     * @param  string  $key     The encryption key.
     *
     * @return string The encrypted string.
     */
    static public function encryptAes($string, $key)
    {
        // AES encryption, CBC blocking with PKCS5 padding then HEX encoding.
        // Add PKCS5 padding to the text to be encypted.
        $string = self::addPKCS5Padding($string);

        // Perform encryption with PHP's MCRYPT module.
        $crypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_CBC, $key);

        // Perform hex encoding and return.
        return "@" . strtoupper(bin2hex($crypt));
    }

    /**
     * Decode a returned string from SagePay.
     *
     * @param string $strIn         The encrypted String.
     * @param string $password      The encyption password used to encrypt the string.
     *
     * @return string The unecrypted string.
     * @throws SagepayApiException
     */
    static public function decryptAes($strIn, $password)
    {
        // HEX decoding then AES decryption, CBC blocking with PKCS5 padding.
        // Use initialization vector (IV) set from $str_encryption_password.
        $strInitVector = $password;

        // Remove the first char which is @ to flag this is AES encrypted and HEX decoding.
        $hex = substr($strIn, 1);

        // Throw exception if string is malformed
        if (!preg_match('/^[0-9a-fA-F]+$/', $hex))
        {
            throw new SagepayApiException('Invalid encryption string');
        }
        $strIn = pack('H*', $hex);

        // Perform decryption with PHP's MCRYPT module.
        $string = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $password, $strIn, MCRYPT_MODE_CBC, $strInitVector);
        return self::removePKCS5Padding($string);
    }

    /**
     * Convert string to data array.
     *
     * @param string  $data       Query string
     * @param string  $delimeter  Delimiter used in query string
     *
     * @return array
     */
    static public function queryStringToArray($data, $delimeter = "&")
    {
        // Explode query by delimiter
        $pairs = explode($delimeter, $data);
        $queryArray = array();

        // Explode pairs by "="
        foreach ($pairs as $pair)
        {
            $keyValue = explode('=', $pair);

            // Use first value as key
            $key = array_shift($keyValue);

            // Implode others as value for $key
            $queryArray[$key] = implode('=', $keyValue);
        }
        return $queryArray;
    }
}

class SagepayApiException extends Exception
{

}

function sagepay_process_gateway_info() {
	global $sessionid;

	if( get_option ('permalink_structure') != '' ) {
		$separator ="?";
	} else {
		$separator ="&";
	}
	
    // first set up all the vars that we are going to need later
    $sagepay_options =  get_option('wpec_sagepay');
    $crypt = filter_input(INPUT_GET, 'crypt');
    $uncrypt = Sagepay_merchant::decryptAes( $crypt , $sagepay_options['encrypt_key'] );
	$decryptArr = Sagepay_merchant::queryStringToArray($uncrypt);
	if (!$uncrypt || empty($decryptArr))
	{
		return;
	}
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
			if ( isset( $sagepay_options['payment_type'] ) && 'AUTHENTICATE' == $sagepay_options['payment_type'] ) {
				$success = 'Authenticated';
			} else {
				$success = 'Pending';	
			}
            break;
        case 'REGISTERED': // Only returned if TxType is AUTHENTICATE
            $success = 'Authenticated';
            break;
        case 'OK':
            $success = 'Completed';
            break;
        default:
            break;
    }

    switch ( $success ) {
        case 'Completed':
            $purchase_log = new WPSC_Purchase_Log( $unencrypted_values['VendorTxCode'], 'sessionid' );
            $purchase_log->set( array(
                'processed'  => WPSC_Purchase_Log::ACCEPTED_PAYMENT,
                'transactid' => $unencrypted_values['VPSTxId'],
            ) );
            $purchase_log->save();

            // set this global, wonder if this is ok
            $sessionid = $unencrypted_values['VendorTxCode'];
			header("Location: ".get_option('transact_url').$separator."sessionid=".$sessionid);
			exit();
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

		case 'Authenticated': // Like "Completed" but only flag as order received
			$purchase_log = new WPSC_Purchase_Log( $unencrypted_values['VendorTxCode'], 'sessionid' );
			$purchase_log->set( array(
				'processed'  => WPSC_Purchase_Log::ORDER_RECEIVED,
				'transactid' => $unencrypted_values['VPSTxId'],
				'date'       => time(),
				'notes'      => 'SagePay Status: ' . $unencrypted_values['Status'],
			) );
			$purchase_log->save();

			// Redirect to reponse page
			$sessionid = $unencrypted_values['VendorTxCode'];
			header( "Location: " . get_option('transact_url') . $separator . "sessionid=" . $sessionid );
			exit();
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
}

if ( isset( $_GET['crypt'] ) && ( substr( $_GET['crypt'], 0, 1 ) === '@') ) {
  add_action('init', 'sagepay_process_gateway_info');
}

/**
 * Checks and returns a valid payment type.
 *
 * This will ALWAYS return a valid payment type.
 * If the requested payment type is not valid it will return a default payment type of "PAYMENT".
 *
 * @param   string  $payment_type  Payment type to validate.
 * @return  string                 Valid payment type.
 */
function wpec_sagepay_validate_payment_type( $payment_type ) {

	if ( in_array( $payment_type, array( 'PAYMENT', 'AUTHENTICATE' ) ) ) {
		return $payment_type;
	}

	return 'PAYMENT';

}

function _wpsc_action_admin_sagepay_suhosin_check() {
	if( in_array( 'sagepay', get_option( 'custom_gateway_options', array() ) ) ) {
		if( @ extension_loaded( 'suhosin' ) && @ ini_get( 'suhosin.get.max_value_length' ) < 1000 ) {
			add_action( 'admin_notices', '_wpsc_action_admin_notices_sagepay_suhosin' );
		}
	}
}
add_action( 'admin_init', '_wpsc_action_admin_sagepay_suhosin_check' );

function _wpsc_action_admin_notices_sagepay_suhosin() { ?>
	<div id="message" class="error fade">
		<p><?php echo __( "We noticed your host has enabled the Suhosin extension on your server.  Unfortunately, it has been misconfigured for compatibility with SagePay. </br> Before you can use SagePay, please contact your hosting provider and ask them to increase the 'suhosin.get.max_value_length' to a value over 1,500.") ?></p>
	</div>	
<?php }