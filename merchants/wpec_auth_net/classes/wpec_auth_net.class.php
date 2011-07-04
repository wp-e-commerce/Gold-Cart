<?php
class wpec_auth_net extends wpsc_merchant {

	var $name = 'Authorize.net AIM/CIM';
	var $auth;
	var $auth_cim;
	var $response;
	var $conf;
	var $customer;
	var $profile;
	var $CIM_ID;
	var $paymentProfiles;
	var $shipAddress;
	var $accountTypes = array('businessChecking'=>'Business Checking', 'savings'=>'Savings Account', 'checking'=>'Checking');
	var $payType;
	var $process_status;
	var $order;
	var $validationMode;

	function __construct($purchase_id = null, $is_receiving = false){
		//Get our config, or bail
		if(get_option('wpec_auth_net') != false){
			$this->conf = get_option('wpec_auth_net');
		}else{
			return false;
		}
		//Only Load the class when you actually need it
		require_once(WPECAUTHNET_CLASSES.'anet_php_sdk/AuthorizeNet.php'); 
		if(!defined('AUTHORIZENET_API_LOGIN_ID')) define('AUTHORIZENET_API_LOGIN_ID', $this->conf['login']);
		if(!defined('AUTHORIZENET_TRANSACTION_KEY')) define('AUTHORIZENET_TRANSACTION_KEY', $this->conf['key']);
		if(isset($this->conf['testmode']) && $this->conf['testmode'] == 'checked'){
			$this->validationMode = "testMode";
			if(!defined('AUTH_NET_TRANSID_URL')) 
			define('AUTH_NET_TRANSID_URL', 'https://sandbox.authorize.net/UI/themes/sandbox/transaction/transactiondetail.aspx?menukey=CustomerProfile&transID=');
		}else{
			$this->validationMode = "liveMode";
			define("AUTHORIZENET_SANDBOX", false);
		}
		//We have our env ready, lets get the auth handler ready for action.
		$this->auth = new AuthorizeNetAIM;
		if(AUTHORIZENET_SANDBOX === false) $this->auth->setSandbox(false);

		if(isset($_REQUEST['payType'])) $this->payType = $_REQUEST['payType'];
		//If We Are Using Authorize.net CIM, lets load up the profiles and address.  We'll store them back 
		//During the submit processes
		if(isset($this->conf['cimon']) && $this->conf['cimon'] == 'checked' ){
			$this->auth_cim = new AuthorizeNetCIM;
			$this->get_Auth_net_CIM();
			$this->profile = $this->auth_cim->getCustomerProfile($this->CIM_ID);
		}
		if ( ($purchase_id == null) && ($is_receiving == true) ) {
			$this->is_receiving = true;
			$this->parse_gateway_notification();
		}
		if ( $purchase_id > 0 ) {
			$this->purchase_id = $purchase_id;
		}
		if(is_admin()){
			add_action('wpsc_billing_details_bottom', array($this,'manage_payment_status'));
			wp_enqueue_script('jquery-ui-dialog');
			wp_enqueue_style('jquery-ui-dialog','https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css');
		}
	}

	/**
        * manage_payment_status - This adds options to the bottom of the Store Sales page,
        * If you also have the Order Management Plugin this will link directly to that plugin
	*/
        function manage_payment_status(){
                global $purchlogitem, $purchlogs, $show_auth_net_links;

		//Only Show links if we are payment gateway
		 if((!isset($show_auth_net_links) || $show_auth_net_links == false) && $purchlogitem->extrainfo->gateway == 'wpec_auth_net'){
			$show_auth_net_links = true;
			 $capture_payment_params  = array('wpsc_admin_action' => 'wpec_auth_net_capture_preauth');
			 $auth_net_details_params = AUTH_NET_TRANSID_URL.$purchlogitem->extrainfo->transactid;
			 $payment_details_params  = AUTH_NET_TRANSID_URL.$purchlogitem->extrainfo->transactid;

			if(!$this->beenCaptured($purchlogitem->purchlogid)){
				//Give Link to capture payment
				echo "<a href='".add_query_arg($capture_payment_params)."'>".__('Accept payment').'</a> | ';
			}
			if( !isset($purchlogitem->extrainfo->transactid) ){
				//Give Link to capture payment
				echo __('Missing Transaction ID in Order',WPECAUTHNET_PLUGIN_NAME)." |";
			}
			if($purchlogitem->extrainfo->gateway == 'wpec_auth_net' ){
				//Give Link to capture payment
				echo $this->displayPurchaseDetails($purchlogitem->purchlogid).' | ';
			}
/*
* until i findout the url for prod env, gonna disable this
			if(isset($purchlogitem->extrainfo->transactid) && $purchlogitem->extrainfo->transactid > 1){
				//Give Link to capture payment
				echo "<a href='$auth_net_details_params'>".__('Auth.net Order Details').'</a> | ';
			}
*/
		}

        }

	/**
	* beenCaptured - We only care about situation were we did a auth_only and haven't captured the details yet
	*
	* @param int $purchaselog_id
	* @return boolean
	*/
	function beenCaptured($purchaselog_id=false){
		$meta = $this->getOrderMeta($purchaselog_id);
		if(isset($meta['status']) && ($meta['status'] == 'AuthOnly' || $meta['status'] == 'FailedCapture')){
			return false;
		}else{
			return true;
		}
		
	}

	function displayPurchaseDetails($purchaselog_id){
		$meta = $this->getOrderMeta($purchaselog_id);
		if(!isset($meta['response'])) return false;
		$dump = "<!-- meta dump:".print_r($meta,1)."-->\n";
		if(isset($meta['capturePreAuth'])){
			$header = __('Capture Details',WPECAUTHNET_PLUGIN_NAME);
			$cap = $meta['capturePreAuth'];
			$post = <<<EOF
			<hr />
			<table>
			  <tr><th colspan='4'>{$header}</th></tr>
			  <tr><th>Approved:</th><td>{$cap['approved']}</td><th>Declined:</th><td>{$cap['declined']}</td></tr>
			  <tr><th>Auth Code:</th><td>{$cap['authorization_code']}</td><th>Transaction ID:</th><td>{$cap['transaction_id']}</td></tr>
			  <tr><th>Type:</th><td>{$cap['method']} {$cap['transaction_type']}</td><th>Ammount:</th><td>{$cap['amount']}</td></tr>
			  <tr><th>Response:</th><td colspan='3'>{$cap['response_reason_text']}</td></tr>
			  <tr><th>MD5 Hash:</th><td colspan='3'>{$cap['md5_hash']}</td></tr>
			</table>
EOF;
		}else{ $post = ''; }
		$meta = (array)$meta['response'];
		$title = __('Purchase Log Details From Authorize.Net',WPECAUTHNET_PLUGIN_NAME);
		
		$popupDisplay = <<<EOF
		  <script>
		  function showAuthNetDetails(){
		    jQuery("#purchaseDetails").dialog({ minWidth: 600, modal: true });
		  }
		  </script>
		
		  <style>
			#purchaseDetails{ 
				display: none; 
				text-align: left;
			}
			#purchaseDetails table th{
				padding-left: 40px;
			}
		</style>
		{$dump}
		<a href="javascript:showAuthNetDetails();">Purchase Details</a>
		<div id='purchaseDetails' title='{$title}' style='display: none; text-align: left;'>
		<table>
		   <tr><th>Approved:</th><td>{$meta['approved']}</td><th>Declined:</th><td>{$meta['declined']}</td></tr>
		   <tr><th>Auth Code:</th><td>{$meta['authorization_code']}</td><th>Transaction ID:</th><td>{$meta['transaction_id']}</td></tr>
		   <tr><th>Type:</th><td>{$meta['method']} {$meta['transaction_type']}</td><th>Ammount:</th><td>{$meta['amount']}</td></tr>
		   <tr><th>Response:</th><td colspan='3'>{$meta['response_reason_text']}</td></tr>
		   <tr><th>MD5 Hash:</th><td colspan='3'>{$meta['md5_hash']}</td></tr>
		</table>
		{$post}
		</div>
		
EOF;
		return $popupDisplay;
	}

	function deletePay($id){
		$tmpcim = new AuthorizeNetCIM();
		$response = $tmpcim->deleteCustomerPaymentProfile($this->CIM_ID, $id);
		if($response->isOk){
			return false;
		}else{
			return $response->getErrorMessage();
		}
	}
	function deleteShip($id){
		$tmpcim = new AuthorizeNetCIM();
		$response = $tmpcim->deleteCustomerShippingAddress($this->CIM_ID, $id);
		if($response->isOk){
			return false;
		}else{
			return $response->getErrorMessage();
		}
	}
	function getBankAccountProfiles(){
		//For What Ever Reason the ship to profiles are no good, then bail
		if(isset($this->profile->xml->messages->resultCode) && $this->profile->xml->messages->resultCode != 'Ok'){
			return false;
		}
		if(isset($this->profile->xml->profile->paymentProfiles) ){
			$output = "";
			foreach($this->profile->xml->profile->paymentProfiles as $account){
				if(isset($account->payment->bankAccount)){
					$accountType = (string)$account->payment->bankAccount->accountType;
					$output .= "<div id='".$account->customerPaymentProfileId."'>\n";
					$output .= "<span class='authNetPreSelect'><input type='checkbox' class='authNetPreSelect' name='auth_net[payment_preset]' value='".
							  $account->customerPaymentProfileId."'></span>\n";
					$output .= "<span class='bankInfo'>".$account->payment->bankAccount->bankName." - ".$account->payment->bankAccount->accountNumber.
							" ".$this->accountTypes[$accountType]."</span>\n";
					$output .= "</div>\n";
				}
			}
			return $output;
		}				
	}

	function getCreditCardProfiles(){
		//For What Ever Reason the ship to profiles are no good, then bail
		if(isset($this->profile->xml->messages->resultCode) && $this->profile->xml->messages->resultCode != 'Ok'){
			return false;
		}
		if(isset($this->profile->xml->profile->paymentProfiles) ){
			$output = "";
			foreach($this->profile->xml->profile->paymentProfiles as $account){
				if(isset($account->payment->creditCard)){
					$output .= "<div id='".$account->customerPaymentProfileId."'>\n";
					$output .= "<span class='authNetPreSelect'><input type='checkbox' class='authNetPreSelect' name='auth_net[payment_preset]' value='".$account->customerPaymentProfileId."'></span>\n";
					$output .= "<span class='ccInfo'>".__("Card Ending in ",'wpsc').$account->payment->creditCard->cardNumber."<span>\n";
					$output .= "</div>\n";
				}
			}
			return $output;
		}				
	}
	function getShippingProfiles(){
		//For What Ever Reason the ship to profiles are no good, then bail
		if(isset($this->profile->xml->messages->resultCode) && $this->profile->xml->messages->resultCode != 'Ok'){
			return false;
		}
		if(isset($this->profile->xml->profile->shipToList) && is_array($this->profile->xml->profile->shipToList)){
			$output = '';
			foreach($this->profile->xml->profile->shipToList as $address){
				$output .= "<div id='".$address->customerAddressId."'>\n";
				//javascipt will take over if you select this
				$output .= "<span class='authNetPreSelect'><input type='checkbox' class='authNetPreSelect' name='auth_net[shiptoaddress_preset]' value='".$address->customerAddressId."'></span>\n";
				$output .= "<span class='addressTo'>";
				if(isset($address->firstName)){
					$output .= $address->firstName;
				}
				$output .= ' ';
				if(isset($address->lastName)){
					$output .= $address->lastName;
				}
				$output .= "</span>\n";
				$output .= "<span class='address'>\n";
				if(isset($address->address)){
					$output .= $address->address."<br />\n";
				}
				if(isset($address->city)){
					$output .= $address->city.", ";
				}
				if(isset($address->state)){
					$output .= $address->state." ";
				}
				if(isset($address->zip)){
					$output .= $address->zip." ";
				}
				if(isset($address->country)){
					$output .= $address->country." ";
				}
				$output .= "</span>\n";
				$output .= "</div>\n";
			}
			return $output;
		}else{
			return false;
		}
		
	}
				
				
	function capturePreAuth($order){
		global $purchlogitem;
		extract($order);
		$this->metaOrder  = $this->getOrderMeta($purchlog_id);
		if(isset($this->metaOrder['status']) && ($this->metaOrder['status'] == 'AuthOnly' || $this->metaOrder['status'] == 'FailedCapture')){
			$trans_id = $purchlog_data['transactid'];
			$amount  = $purchlog_data['totalprice'];
			$this->response = $this->auth->priorAuthCapture($trans_id, $amount);
			$this->metaOrder['capturePreAuth'] = (array)$this->response;
			if ($this->response->approved) {
				$this->metaOrder['status'] = 'CapturedPayment';
				$this->setOrderMeta();
				$this->set_transaction_details($this->response->transaction_id,3);
				$this->set_authcode($this->response->authorization_code,true);
				return true;
			}else{
				$this->metaOrder['status'] = 'FailedCapture';
				$this->setOrderMeta();
				$this->set_transaction_details($this->response->transaction_id,2);
				return false;
			}
		}
	}
	function validatePayment(){
		if($this->payType == 'creditCardForms'){
			//Using Credit Card
			$creditCard = $_REQUEST['auth_net']['creditCard'];
			if(!isset($creditCard['card_number'])){
				$this->set_error_message(__('Valid Credit Card Not Given.','wpsc'));
				$this->return_to_checkout();
			}
			$this->auth->card_num = $creditCard['card_number'];
			if(!isset($creditCard['expiry'])){
				$this->set_error_message(__('Valid Credit Card Expiration Not Given.','wpsc'));
				$this->return_to_checkout();
			}
			$this->auth->exp_date = $creditCard['expiry']['month'].$creditCard['expiry']['year'];
			if(!isset($creditCard['card_code'])){
				$this->set_error_message(__('Please Enter The CVV Off The Back of The Card.','wpsc'));
				$this->return_to_checkout();
			}
			$this->auth->card_code = $creditCard['card_code'];
		}elseif($this->payType == 'checkForms'){
			$bankAccount = $_REQUEST['auth_net']['bankAccount'];
			$check = array();
			$check['echeck_type'] = 'WEB';
			$check['method'] = 'echeck';
			if(!isset($bankAccount['bank_name'])){
				$this->set_error_message(__('Please Enter The Bank Name','wpsc'));
				$this->return_to_checkout();
			}
			$check['bank_name'] = $bankAccount['bank_name'];
			if(!isset($bankAccount['account_type'])){
				$this->set_error_message(__('Please Specify The Account Type.','wpsc'));
				$this->return_to_checkout();
			}
			$check['bank_acct_type'] = $bankAccount['account_type'];
			if(!isset($bankAccount['name_on_account'])){
				$this->set_error_message(__('Please Give The Name On The Account.','wpsc'));
				$this->return_to_checkout();
			}
			$check['bank_acct_name'] = $bankAccount['name_on_account'];
			if(!isset($bankAccount['account_number'])){
				$this->set_error_message(__('No Account Number Given.','wpsc'));
				$this->return_to_checkout();
			}
			$check['bank_acct_num'] = $bankAccount['account_number'];
			if(!isset($bankAccount['routing_number'])){
				$this->set_error_message(__('No Bank Routing Number Given.','wpsc'));
				$this->return_to_checkout();
			}
			$check['bank_aba_code'] = $bankAccount['routing_number'];
			$this->auth->setFields($check);
		}
	}

	function processCIMTransaction(){
		$paymentProfile = $_REQUEST['auth_net']['payment_preset'];
		$this->auth     = new AuthorizeNetCIM;
		$cimTransaction = new AuthorizeNetTransaction;
		
		$cimTransaction->amount = number_format($this->cart_data['total_price'],2);
		$cimTransaction->tax->amount = $this->cart_data['cart_tax'];
		$cimTransaction->shipping->amount = $this->cart_data['base_shipping'];
		if(isGood($this->purchase_id)) $cimTransaction->order->invoiceNumber = $this->purchase_id;
		$cimTransaction->customerProfileId = $this->CIM_ID;
		$cimTransaction->customerPaymentProfileId = $paymentProfile;
		
		foreach($this->cart_items as $i => $Item) {
		    $taxable = isset($Item->tax) ? true : false;
		    //For Some Lame Reason the name can only be 30characters long... weak sauce.
		    $name = substr($Item['name'],0,31);
		    $description = isset($Item['description']) ? $Item['Description'] : 'Generic Goods';
		    $lineItem = new AuthorizeNetLineItem;
		    $lineItem->itemId = $Item['product_id'];
		    $lineItem->name = $name;
		    $lineItem->description = $description;
		    $lineItem->quantity    = $Item['quantity'];
		    $lineItem->unitPrice   = $Item['price'];
		    $lineItem->taxable     = $taxable;
		    $cimTransaction->lineItems[] = $lineItem;
		}

		if(isset($this->conf['verifyFirst']) && $this->conf['verifyFirst'] == 'checked'){
			//This is what we set the proccess status to if we are successful for the CIM type transactions
			$this->process_status = 2;
			$capture = 'AuthOnly';
		}else{
			//This is what we set the proccess status to if we are successful for the AIM type transactions
			$this->process_status = 3;
			$capture = 'AuthCapture';
		}

		$this->metaOrder['status'] = $capture;
		$this->metaOrder['process_status'] = $this->process_status;
		$this->response = $this->auth->createCustomerProfileTransaction($capture, $cimTransaction);
		$resultOptions = explode(',', $this->response->xml->directResponse);
		$this->metaOrder['response']['response_reason_text'] = $resultOptions[3];
		$this->metaOrder['response']['authorization_code'] = $resultOptions[4];
		$this->metaOrder['response']['avs_response'] = $resultOptions[5];
		$this->metaOrder['response']['transaction_id'] = $resultOptions[6];
		$this->metaOrder['response']['invoice_number'] = $resultOptions[7];
		$this->metaOrder['response']['amount'] = $resultOptions[9];
		$this->metaOrder['response']['method'] = $resultOptions[10];
		$this->metaOrder['response']['transaction_type'] = $resultOptions[11];
		$this->metaOrder['response']['customer_id'] = $resultOptions[12];
		$this->metaOrder['response']['first_name'] = $resultOptions[13];
		$this->metaOrder['response']['last_name'] = $resultOptions[14];
		$this->metaOrder['response']['company'] = $resultOptions[15];
		$this->metaOrder['response']['address'] = $resultOptions[16];
		$this->metaOrder['response']['city'] = $resultOptions[17];
		$this->metaOrder['response']['state'] = $resultOptions[18];
		$this->metaOrder['response']['zip_code'] = $resultOptions[19];
		$this->metaOrder['response']['country'] = $resultOptions[20];
		$this->metaOrder['response']['phone'] = $resultOptions[21];
		$this->metaOrder['response']['fax'] = $resultOptions[22];
		$this->metaOrder['response']['email_address'] = $resultOptions[23];
		$this->metaOrder['response']['ship_to_first_name'] = $resultOptions[24];
		$this->metaOrder['response']['ship_to_last_name'] = $resultOptions[25];
		$this->metaOrder['response']['ship_to_company'] = $resultOptions[26];
		$this->metaOrder['response']['ship_to_address'] = $resultOptions[27];
		$this->metaOrder['response']['ship_to_city'] = $resultOptions[28];
		$this->metaOrder['response']['ship_to_state'] = $resultOptions[29];
		$this->metaOrder['response']['ship_to_zip_code'] = $resultOptions[30];
		$this->metaOrder['response']['ship_to_country'] = $resultOptions[31];
		$this->metaOrder['response']['tax'] = $resultOptions[32];
		$this->metaOrder['response']['duty'] = $resultOptions[33];
		$this->metaOrder['response']['freight'] = $resultOptions[34];
		$this->metaOrder['response']['tax_exempt'] = $resultOptions[35];
		$this->metaOrder['response']['purchase_order_number'] = $resultOptions[36];
		$this->metaOrder['response']['md5_hash'] = $resultOptions[37];
		$this->metaOrder['response']['card_code_response'] = $resultOptions[38];
		$this->metaOrder['response']['cavv_response'] = $resultOptions[39];
		$this->metaOrder['response']['account_number'] = $resultOptions[40];
		$this->metaOrder['response']['card_type'] = $resultOptions[41];
		$this->metaOrder['response']['split_tender_ip'] = $resultOptions[42];
		$this->metaOrder['response']['requested_amount'] = $resultOptions[43];
		$this->metaOrder['response']['balance_on_card'] = $resultOptions[44];
		
		
		if($this->response->isOk()) return true;
		else return false;
	}

	/**
	* setOrderMeta - add some details about the transaction that came from authorize.net
	*/
	function setOrderMeta(){
		$meta_key = '_wpsc_auth_net_status';
		$type = 'wpsc_purchase_log';
		wpsc_update_meta($this->purchase_id,$meta_key,$this->metaOrder,$type);
	}

	/**
	* getOrderMeta - get the order details from the purchase log meta details
	*
	* @param int $purchase_id purchase number
	* @return mixed purchase log details
	*/
	function getOrderMeta($purchase_id){
		$meta_key = '_wpsc_auth_net_status';
		$type = 'wpsc_purchase_log';
		return wpsc_get_meta($purchase_id,$meta_key,$type);
	}

	function processAIMTransaction(){
		//Standard Authorize.net AIM Transaction
		// Set Invoice Number:
		//Set the amount
		$this->auth->amount = number_format($this->cart_data['total_price'],2);
		$this->auth->customer_ip = getip();
		$this->validatePayment();
		$this->setBillingAddress();
		$this->setShippingAddress();
		if(isGood($this->purchase_id)) $this->auth->invoice_num = $this->purchase_id;
		if(isGood($this->cart_data['cart_tax'])) $this->auth->tax = $this->cart_data['cart_tax'];
		if(isGood($this->cart_data['base_shipping'])) $this->auth->freight = $this->cart_data['base_shipping'];


		foreach($this->cart_items as $i => $Item) {
		    if($Item['is_recurring'] == 1) continue;
		    $taxable = ($Item['tax'] >0) ? 'Y' : 'N';
		    //For Some Lame Reason the name can only be 30characters long... weak sauce.
		    $name = substr($Item['name'],0,31);
		    $description = isset($Item['description']) ? $Item['Description'] : 'Generic Goods';
		    $this->auth->addLineItem($Item['product_id'], $name, $description, $Item['quantity'], $Item['price'], $taxable);
		}
		if(isset($this->conf['verifyFirst']) && $this->conf['verifyFirst'] == 'checked'){
			//This is what we set the proccess status to if we are successful for the CIM type transactions
			$this->process_status = 2;
			$this->metaOrder['process_status'] = $this->process_status;
			$this->response  = $this->auth->authorizeOnly();
			if($this->response->approved == 1){
				$this->metaOrder['response'] = (array)$this->response;
				$this->metaOrder['status'] = 'AuthOnly';
				return true;
			}else return false;
			
		}else{
			//This is what we set the proccess status to if we are successful for the AIM type transactions
			$this->process_status = 3;
			$this->metaOrder['process_status'] = $this->process_status;
			$this->response  = $this->auth->authorizeAndCapture();
			if($this->response->approved == 1){
				$this->metaOrder['response'] = (array)$this->response;
				$this->metaOrder['status'] = 'AuthCapture';
				return true;
			}else return false;
			
		}
	}
		
	function submit() {
		global $wpdb;
		$this->collate_data();
		$this->collate_cart();

                if(isset($this->cart_data['is_subscription']) && (int)$this->cart_data['is_subscription'] > 0){
                        //This is subscription based, use the ARB api
			if($subRes = $this->setSubscription()){
				$callAim = false;
				//Check if their is anything else in the cart that needs to be processed.
				// If so throw it at AIM to finish
				foreach($this->cart_items as $item){
					if((int)$item['is_recurring'] != 1){
						$callAim = true;
					}
				}
				if($callAim) $result = $this->processAIMTransaction();
				else{
					if($subRes == true){
						$this->set_transaction_details($this->response->transaction_id,$this->process_status);
						$this->set_authcode($this->response->authorization_code);
						$this->go_to_transaction_results($this->cart_data['session_id']);
						return true;
					}else{
						//error occured, bailing back to checkout stand
						$this->set_transaction_details($this->response->transaction_id,6);
						$this->set_error_message("Failed to create subscription");
						$this->return_to_checkout();
						return false;
					}
				}
			}else{
				$result = false;
			}

                }else if(isset($this->conf['cimon']) && $this->conf['cimon'] == 'checked' && $this->payType == 'preset'
                && isset($_REQUEST['auth_net']['payment_preset'])){
                        $result = $this->processCIMTransaction();
                }else{
                        $result = $this->processAIMTransaction();
                }
		do_action('submit_payment_response',$this);
		$this->setOrderMeta();
		if ($result == true) {
			$this->metaOrder['result'] = true;
			//Save The Credit Card if they asked us to
			if(isset($_REQUEST['auth_net']['SaveCreditCard']) && $_REQUEST['auth_net']['SaveCreditCard'] == 'Keep On File' && isset($this->conf['cimon']) 
				&& $this->conf['cimon'] == 'checked' && $this->payType == 'creditCardForms' && isset($_REQUEST['auth_net']['creditCard']) ){
				//Ok, We Should Save This Credit Card
				$creditCard = $_REQUEST['auth_net']['creditCard'];
				$this->addPaymentProfile(array('type'=>'CC', 'card_number'=>$creditCard['card_number'], 'expire'=>$creditCard['expiry']));
			}
			//Now For the Bank account
			if(isset($_REQUEST['auth_net']['SaveBankAccount']) &&$_REQUEST['auth_net']['SaveCreditCard'] == 'Keep On File' && isset($this->conf['cimon']) 
				&& $this->conf['cimon'] == 'checked' && $this->payType == 'bankForms' && isset($_REQUEST['auth_net']['bankAccount']) ){
				//Ok, We Should Save This bank account
				$bankAccount = $_REQUEST['auth_net']['bankAccount'];
				$this->addPaymentProfile(array(
				'type'=>'bank', 
				'account_type'=>$bankAccount['account_type'], 
				'routing_number'=>$bankAccount['routing_number'], 
				'account_number'=>$bankAccount['account_number'], 
				'account_name'=>$bankAccount['name_on_account'], 
				'echeck_type'=>'WEB',
				'bank_name'=>$bankAccount['bank_name']
				));
			}
			$this->set_transaction_details($this->response->transaction_id,$this->process_status);
			$this->set_authcode($this->response->authorization_code);
			$this->go_to_transaction_results($this->cart_data['session_id']);
		}else{
			//error occured, bailing back to checkout stand
			$this->set_transaction_details($this->response->transaction_id,6);
			$this->set_error_message($this->response->response_reason_text);
			$this->return_to_checkout();
		}
		exit();
	}

	function addPaymentProfile($pp){

		//Perhaps you should check if this card is already on file before you try to save it
		if(isset($pp['type']) && $pp['type'] == 'CC' && isset($pp['card_number']) && isset($pp['expire']) ){ //Looks like we are adding a credit card profile
			$newpp = new AuthorizeNetPaymentProfile;
			$newpp->customerType = "individual";
			$newpp->payment->creditCard->cardNumber = $pp['card_number'];
			$newpp->payment->creditCard->expirationDate = $pp['expire']['year']."-".$pp['expire']['month'];
			$response = $this->auth_cim->createCustomerPaymentProfile($this->CIM_ID, $newpp);
			if($response->isOk()){
				return true;
			}else{
				return false;
			}
		}elseif(isset($pp['type']) && $pp['type'] =='check' && isset($pp['routing_number']) && isset($pp['account_number']) ){ //OK now we want to store bank account info
			$newpp = new AuthorizeNetPaymentProfile;
			$newpp->customerType = "individual";
			$newpp->payment->bankAccount->accountType = $pp['account_type'];
			$newpp->payment->bankAccount->routingNumber = $pp['routing_number'];
			$newpp->payment->bankAccount->accountNumber = $pp['account_number'];
			$newpp->payment->bankAccount->nameOnAccount = $pp['account_name'];
			$newpp->payment->bankAccount->echeckType = $pp['echeck_type'];
			$newpp->payment->bankAccount->bankName = $pp['bank_name'];
			$response = $this->auth_cim->createCustomerPaymentProfile($this->CIM_ID, $newpp);
			if($response->isOk()){
				return true;
			}else{
				return false;
			}
		
		}else{ return false; }
	}


	function get_Auth_net_CIM(){
		global $user_ID;

		$this->CIM_ID = (int)get_user_meta($user_ID, 'wpec_auth_net_cim_id', true);
		if(!isset($this->CIM_ID) || !$this->CIM_ID){
			$user_info = get_userdata($user_ID);
			if($user_info == false) return false;
			$customerProfile = new AuthorizeNetCustomer;
			$customerProfile->description = "{$user_info->user_firstname} {$user_info->user_lastname} <{$user_info->user_email}>";
			$customerProfile->merchantCustomerId = $user_ID;
			$customerProfile->email = $user_info->user_email;
			$profile_create_response = $this->auth_cim->createCustomerProfile($customerProfile);
			if($profile_create_response->isOk()){
				$this->CIM_ID = $profile_create_response->getCustomerProfileId();
				update_user_meta($user_ID, 'wpec_auth_net_cim_id', $this->CIM_ID);
			}else { return false; }
		}
		return $this->CIM_ID;
	}

	function getPaymentOnFile($type){
		if(isset($this->conf['cimon']) && $this->conf['cimon'] =='checked'){
			//We're using Auth.net CIM, get any payment profiles we have
			return 'Not implemented yet';
		}
	}
	
	function CheckOrCC(){
		global $wpdb, $wpsc_cart, $user_ID;
		//Find out if their is a subscription in the cart and disable cimon
		foreach($wpsc_cart->cart_items as $item){
			if($is_recurring = (bool)get_post_meta( $item->product_id, '_wpsc_is_recurring', true )) $this->conf['cimon'] = false;
		}

		$output = <<<EOF
		<div id='checkorcc_select'>
		<script>
			var shown = 'none';
			jQuery(document).ready( function() {
			    jQuery('.paymentType').hide();
			    jQuery("select.paymentTypes").change(function() {
				jQuery('.paymentType').hide();
				shown = jQuery(this).children("option:selected").val();
				jQuery('#'+shown).show();
				jQuery('#payType').val(shown);
			    });
			    jQuery('.authNetPreSelect').change( function(){
				    if( jQuery('.authNetPreSelect').is(':checked')){
					jQuery('.authNetPaymentInput').attr('disabled',true);
					jQuery('#payType').val('preset');
				    }else{
					jQuery('.authNetPaymentInput').attr('disabled',false);
					jQuery('#payType').val(shown);
				    }
			    });
			});
		</script>
		<input type='hidden' name='payType' id='payType'>
		<select name='paymentTypes' class="paymentTypes">
			<option value='NONE'>Select Payment Type</option>
			<option value='creditCardForms'>Credit Card</option>
			<option value='checkForms'>E-Check</option>
		</select>
EOF;
		$output .= $this->showCheckForm();
		$output .= $this->showCCForm();
		$output .= '</div>';
		return $output;
	}
	


	
	function showCheckForm(){
		$output = "<div id='checkForms' class='paymentType'>";
		$output .= "<fieldset><legend>".__('E-Check','wpsc')."</legend>";
		if(isset($this->conf['cimon']) && $this->conf['cimon'] =='checked'){
			$bankProfiles = $this->getBankAccountProfiles();
			if($bankProfiles){
				$output .= "<div><span class='head2'>".__('Use Bank Account on file.','wpsc')."</span>\n";
				$output .= $bankProfiles;
				$output .= "</div>\n<span class='head2'> ".__('Or Enter a New Bank Account','wpsc')."</span>\n";
			}
			$output .= "<span id='saveBankAccount'><input type='checkbox' name='auth_net[SaveBankAccount]' value='Keep On File'>".__('Save Payment Info (You Can Save Up To 10 accounts).','wpsc')."</span>\n";
		}
		$output .= $this->showNewBankAccountForm();
		$output .= "</fieldset></div>";
		return $output;
	}





	function showCCForm(){
		$output = "<div id='creditCardForms' class='paymentType'>";
		$output .= "<fieldset><legend>".__('Credit Card','wpsc')."</legend>";
		if(isset($this->conf['cimon']) && $this->conf['cimon'] =='checked'){
			$creditcards     = $this->getCreditCardProfiles();
			if($creditcards){
				$output .= "<div><span class='head2'>".__('Use Credit Card on file.','wpsc')."</span>\n";
				$output .= $creditcards;
				$output .= "</div>\n<span class='head2'> ".__('Or Enter a New Card','wpsc')."</span>\n";
			}
			$output .= "<span id='saveCreditCard'><input type='checkbox' name='auth_net[SaveCreditCard]' value='Keep On File'>".__('Save Payment Info (You Can Save Up To 10 accounts).','wpsc')."</span>\n";
		}
		$output .= $this->showNewCCForm();
		$output .= "</fieldset></div>";
		return $output;
	}
	

	function showNewBankAccountForm(){
		if(isset($_REQUEST['auth_net']['bankAccount'])) $auth_net = $_REQUEST['auth_net']['bankAccount'];
		else $auth_net = array('account_type'=>'', 'bank_name'=>'','name_on_account'=>'','routing_number'=>'','account_number'=>'');
		if(isset($auth_net['account_type']) && array_key_exists($auth_net['account_type'], $this->accountTypes)){
			$selected = "<option value='{$auth_net['account_type']}' selected>{$this->accountTypes[$auth_net['account_type']]}</option>\n";
		}else{
			$selected = "<option value='none'>Select Account Type</option>\n";
		}
		return <<<EOF
		<div id='BankAccountNew'>
		<table border='0'>
		<tr>
			<td class='wpsc_BankAccount_details'>Bank Name</td>
			<td>
				<input type='text' class='authNetPaymentInput' value='{$auth_net['bank_name']}' name='auth_net[bankAccount][bank_name]' />
			</td> 
		</tr>
		<tr>
			<td class='wpsc_BankAccount_details'>Account Type</td>
			<td>
			<select name='auth_net[bankAccount][account_type]' class='authNetPaymentInput'>
				{$selected}
				<option value='businessChecking'>Business Checking</option>
				<option value='checking'>Checking</option>
				<option value='savings'>Savings</option>
			</select>
			</td>
		</tr>
		<tr>
			<td class='wpsc_BankAccount_details'>Name on Account</td>
			<td><input type='text'  name='auth_net[bankAccount][name_on_account]' class='authNetPaymentInput' value='{$auth_net['name_on_account']}' />
			</td>
		</tr>
		<tr>
			<td class='wpsc_BankAccount_details'>Routing Number</td>
			<td><input type='text' name='auth_net[bankAccount][routing_number]' class='authNetPaymentInput' value='{$auth_net['routing_number']}' />
			</td>
		</tr>
		<tr>
			<td class='wpsc_BankAccount_details'>Account Number</td>
			<td><input type='text' name='auth_net[bankAccount][account_number]' class='authNetPaymentInput' value='{$auth_net['account_number']}' />
			</td>
		</tr>
		</table>
		</div>
EOF;
	}

	function showNewCCForm(){
		$selected_month = $selected_year = $years = $months = '';
		$curryear = date( 'Y' );

		//generate year options
		for ( $i = 0; $i < 10; $i++ ) {
			$years .= "<option value='" . $curryear . "'>" . $curryear . "</option>\r\n";
			$curryear++;
		}
		if(isset($_REQUEST['auth_net']['creditCard'])) $auth_net = $_REQUEST['auth_net']['creditCard'];
		else $auth_net = array('name_on_card'=>'', 'card_number'=>'', 'expiry'=> array( 'month'=>'', 'year'=>''));
		if(isset($auth_net['expiry']['month']) && $auth_net['expiry']['month'] > 0){
			$selected_month = "<option value='{$auth_net['expiry']['month']}' selected>{$auth_net['expiry']['month']}</option>\n";
			$selected_year  = "<option value='{$auth_net['expiry']['year']}' selected>{$auth_net['expiry']['year']}</option>\n";
		}
	return <<<EOF
	<div id='creditCardNew'>
		<table border='0'>
		<tr>
			<td class='wpsc_CC_details'>Name as It Appears on Card *</td>
			<td>
				<input type='text' value='{$auth_net['name_on_card']}' name='auth_net[creditCard][name_on_card]' class='authNetPaymentInput' />
			</td> 
		</tr>
		<tr>
			<td class='wpsc_CC_details'>Credit Card Number *</td>
			<td>
				<input type='text' value='{$auth_net['card_number']}' name='auth_net[creditCard][card_number]' class='authNetPaymentInput' />
			</td> 
		</tr>
		<tr>
			<td class='wpsc_CC_details'>Credit Card Expiry *</td>
			<td>
				<select class='wpsc_ccBox authNetPaymentInput' name=auth_net[creditCard][expiry][month]' >
				" . $months . "
				{$selected_month}
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
				<select class='wpsc_ccBox authNetPaymentInput' name='auth_net[creditCard][expiry][year]' >
				{$selected_year}
				" . $years . "
				</select>
			</td>
		</tr>
		<tr>
			<td class='wpsc_CC_details'>CVV *</td>
			<td><input type='text' size='4' value='' maxlength='4' name='auth_net[creditCard][card_code]' class='authNetPaymentInput'/>
			</td>
		</tr>
		</table>
	</div>
EOF;
	}

	function setBillingAddress(){
		if(isGood($this->cart_data['billing_address']['address']))     $this->auth->address	        = $this->cart_data['billing_address']['address'];
		if(isGood($this->cart_data['billing_address']['city']))	       $this->auth->city 		= $this->cart_data['billing_address']['city'];
		if(isGood($this->cart_data['billing_address']['state']))       $this->auth->state 		= $this->cart_data['billing_address']['state'];
		if(isGood($this->cart_data['billing_address']['post_code']))   $this->auth->zip 		= $this->cart_data['billing_address']['post_code'];
		if(isGood($this->cart_data['billing_address']['first_name']))  $this->auth->first_name          = $this->cart_data['billing_address']['first_name'];
		if(isGood($this->cart_data['billing_address']['last_name']))   $this->auth->last_name           = $this->cart_data['billing_address']['last_name'];
		if(isGood($this->cart_data['billing_address']['country']))   $this->auth->country           = $this->cart_data['billing_address']['country'];
		if(isGood($this->cart_data['email_address']))   $this->auth->email           = $this->cart_data['email_address'];
	}
	function setShippingAddress(){
		if(isGood($this->cart_data['shipping_address']['address']))    $this->auth->ship_to_address     = $this->cart_data['shipping_address']['address'];
		if(isGood($this->cart_data['shipping_address']['city']))       $this->auth->ship_to_city        = $this->cart_data['shipping_address']['city'];
		if(isGood($this->cart_data['shipping_address']['state']))      $this->auth->ship_to_state       = $this->cart_data['shipping_address']['state'];
		if(isGood($this->cart_data['shipping_address']['post_code']))  $this->auth->ship_to_zip         = $this->cart_data['shipping_address']['post_code'];
		if(isGood($this->cart_data['shipping_address']['first_name'])) $this->auth->ship_to_first_name  = $this->cart_data['shipping_address']['first_name'];
		if(isGood($this->cart_data['shipping_address']['last_name']))  $this->auth->ship_to_last_name   = $this->cart_data['shipping_address']['last_name'];
		if(isGood($this->cart_data['shipping_address']['country']))  $this->auth->ship_to_country   = $this->cart_data['shipping_address']['country'];
	}

	function setSubscription(){
		global $user_ID;
		
		$sub = new WPSC_Subscription($user_ID);
		foreach($this->cart_items as $itemIndex => $item){
			if(isset($item['is_recurring']) && (int)$item['is_recurring']>0){
				//If you already have a subscription then pass
				$subscription = new AuthorizeNet_Subscription;

				//If there is a predefined interval set it
				if(isGood($item['recurring_data']['rebill_interval']['unit']) && isGood($item['recurring_data']['rebill_interval']['length'])){
					$unit = $item['recurring_data']['rebill_interval']['unit'];
					$length = $item['recurring_data']['rebill_interval']['length'];
					if( $unit == 'month' ) $unit = 'months';
					elseif ($unit == 'days') $unit = 'days';
					elseif ($unit == 'week'){
						$length = $length * 7;
						$unit = 'days';
					}
					elseif ($unit == 'year'){
						$length = $length * 12;
						$unit = 'months';
					}


					$subscription->intervalUnit = $unit;
					$subscription->intervalLength = $length;
				}


				//Set rebill design
				if(isset($item['recurring_data']['charge_to_expiry']) && (int)$item['recurring_data']['charge_to_expiry'] > 0){
					//we bill forever, set the totalOccurences to 9999
					$subscription->totalOccurrences = 9999;
				}elseif(isset($item['recurring_data']['times_to_rebill']) && (int)$item['recurring_data']['times_to_rebill'] > 0){
					//Only a specified ammount of times
					$subscription->totalOccurrences = (int)$item['recurring_data']['times_to_rebill'];
				}else{
					$subscription->totalOccurrences = 1;
				}
			
				//Always set the start date to today
				$subscription->startDate = date('Y-m-d');
				$subscription->amount = ($item['price'] * $item['quantity']) + $item['tax'] + $item['shipping'];
				$amount = $subscription->amount;
				if(isGood($item['name'])) $subscription->name = $item['name'];

				//Set the billing info
				if(isGood($this->cart_data['billing_address']['first_name']))
					$subscription->billToFirstName = $this->cart_data['billing_address']['first_name'];

				if(isGood($this->cart_data['billing_address']['last_name']))
					$subscription->billToLastName = $this->cart_data['billing_address']['last_name'];

				if(isGood($this->cart_data['billing_address']['address']))
					$subscription->billToAddress = $this->cart_data['billing_address']['address'];

				if(isGood($this->cart_data['billing_address']['city']))
					$subscription->billToCity = $this->cart_data['billing_address']['city'];

				if(isGood($this->cart_data['billing_address']['state']))
					$subscription->billToState = $this->cart_data['billing_address']['state'];

				if(isGood($this->cart_data['billing_address']['post_code']))
					$subscription->billToZip = $this->cart_data['billing_address']['post_code'];

				if(isGood($this->cart_data['billing_address']['country']))
					$subscription->billToCountry = $this->cart_data['billing_address']['country'];

				if(isGood($this->cart_data['email_address']))
					$subscription->customerEmail = $this->cart_data['email_address'];

				$subscription->customerId = $user_ID;

				//Trying to figure out how to set this
				//$subscription->customerPhoneNumber = $item[''];


				if(isGood($_REQUEST['payType']) && $_REQUEST['payType'] = 'creditCardForms'){
					$ccInfo = $_REQUEST['auth_net']['creditCard'];
					$subscription->creditCardCardNumber = $ccInfo['card_number'];
					$subscription->creditCardExpirationDate = $ccInfo['expiry']['year'].'-'.$ccInfo['expiry']['month'];
					$subscription->creditCardCardCode = $ccInfo['card_code'];
				}elseif(isGood($_REQUEST['payType']) && $_REQUEST['payType'] = 'checkForms'){
					$bInfo = $_REQUEST['auth_net']['bankAccount'];
					$subscription->bankAccountAccountType = $bInfo['account_type'];
					$subscription->bankAccountRoutingNumber = $bInfo['routing_number'];
					$subscription->bankAccountAccountNumber = $bInfo['account_number'];
					$subscription->bankAccountNameOnAccount = $bInfo['name_on_account'];
					//$subscription->bankAccountEcheckType = "";
					$subscription->bankAccountBankName = $bInfo['bank_name'];
				}else{
					//TODO return if payment data is bad
				}
				$request = new AuthorizeNetARB;
				//Create a unique refid so if there are multiple subscriptions everything works out ok
				$refid = $this->cart_data['session_id'].'-'.$item['cart_item_id'];
				$request->setRefId($refid);
				$this->response = $request->createSubscription($subscription);
				$this->response->transaction_id = $this->cart_data['session_id'];
				if($this->response->isOk()){
					//This created subscription;
					$this->response->authorization_code = $this->response->getSubscriptionId();
					$subscriptionId = $this->response->getSubscriptionId();
					$end  = strtotime($length.' '.$unit);
					$sub->saveSubscriptionMeta(array(
						'purchase_id'=>$this->purchase_id, 
						'producti_d'=>$item['product_id'],
						'ref_id'=>$refid, 
						'subscription_id'=>$this->response->getSubscriptionId(),
						'startTime'=>time(),'endTime'=>$end)
					);
					//If we setup the transaction and it worked, remove it from the cart
					//if their are more subscriptions the foreach will catch the reset of them
					//If there are other items non-subscription based this loop finishes and returns true
					//THe submit function will then continue to see if it needs to do anything else.
					unset($this->cart_items[$itemIndex]);
					//Remove this ammount from cart_data['total_price'] & tax
					$this->cart_data['total_price'] = $this->cart_data['total_price'] - $amount;
					$this->cart_data['cart_tax'] = $this->cart_data['cart_tax'] - $item['tax'];
				}else{
					//error occured, bailing back to checkout stand
					$this->set_transaction_details($this->cart_data['session_id'],6);
					$this->set_error_message(__('Failed to Authorize the Subscription','wpsc'));
					$this->return_to_checkout();
					return false;
				}
			}
		}	
		return true;
	}

       /**
        * cancelSubscription($purchaseLog, $subscription) find the subscription and cancel it
        * TODO rewrite to be used as API
        * @param int $purchlogID
        */
        function cancelSubscription($purchaseLog = false, $subscription = false){
                if($purchaseLog != false && $subscription != false){
                        $ref_id = $subscription['ref_id'];
                        $subscription_id = $subscription['subscription_id'];

                        $cancellation = new AuthorizeNetARB;
                        $cancellation->setRefId($ref_id);
                        $cancel_response = $cancellation->cancelSubscription($subscription_id);
                        if($cancel_response->isOk()){
                                return true;
                        }else{  
                                return false;
                        }
                }else{
			return false;
		}
        }

}

function form_auth_net(){
	if(get_option('wpec_auth_net') != false){
		$auth_net = get_option('wpec_auth_net');
	}else{
		$auth_net = array('login'=>'', 'key'=>'', 'testmode'=>'checked', 'cimon'=>'checked');
	}
	$output =<<<EOF
	<tr>
	  <td>
		API Login ID
	  </td>
	  <td>
		<input type='text' name='wpec_auth_net[login]' value='{$auth_net['login']}'>
	  </td>
		</tr>
		<tr>
		  <td>
			Transaction Key
		  </td>
		  <td>
			<input type='text' name='wpec_auth_net[key]' value='{$auth_net['key']}'>
		  </td>
		</tr>
		<tr>
		  <td>
			Test Mode
		  </td>
		  <td>
			<input type='checkbox' name='wpec_auth_net[testmode]' value='true' {$auth_net['testmode']}>
		  </td>
		</tr>
		<tr>
		  <td>
			Enable <a href="http://www.authorize.net/solutions/merchantsolutions/merchantservices/cim/">CIM</a>
		  </td>
		  <td>
			<input type='checkbox' name='wpec_auth_net[cimon]' value='true' {$auth_net['cimon']}>
		  </td>
		</tr>
		<tr>
		  <td>
			Verify First, Capture Later
		  </td>
		  <td>
			<input type='checkbox' name='wpec_auth_net[verifyFirst]' value='true' {$auth_net['verifyFirst']}>
		  </td>
		</tr>
EOF;
	return $output;
}

function submit_auth_net(){
	if(isset($_REQUEST['wpec_auth_net']) && is_array($_REQUEST['wpec_auth_net']) ){
		if(isset($_REQUEST['wpec_auth_net']['cimon'])  && $_REQUEST['wpec_auth_net']['cimon'] == 'true'){
			$_REQUEST['wpec_auth_net']['cimon'] = 'checked';
		}
		if(isset($_REQUEST['wpec_auth_net']['testmode']) && $_REQUEST['wpec_auth_net']['testmode'] == 'true'){
			$_REQUEST['wpec_auth_net']['testmode'] = 'checked';
		}
		if(isset($_REQUEST['wpec_auth_net']['verifyFirst']) && $_REQUEST['wpec_auth_net']['verifyFirst'] == 'true'){
			$_REQUEST['wpec_auth_net']['verifyFirst'] = 'checked';
		}
		if(get_option('wpec_auth_net') === false){
			add_option('wpec_auth_net',$_REQUEST['wpec_auth_net'], '', 'no');
		}else{
			update_option('wpec_auth_net',$_REQUEST['wpec_auth_net']);
		}
	}
	return true;
}

function validip($ip) {
        if (isGood($ip) && ip2long($ip)!=-1) {
                $reserved_ips = array (
                        array('0.0.0.0','2.255.255.255'),
                        array('10.0.0.0','10.255.255.255'),
                        array('127.0.0.0','127.255.255.255'),
                        array('169.254.0.0','169.254.255.255'),
                        array('172.16.0.0','172.31.255.255'),
                        array('192.0.2.0','192.0.2.255'),
                        array('192.168.0.0','192.168.255.255'),
                        array('255.255.255.0','255.255.255.255')
                );
                foreach ($reserved_ips as $r) {
                        $min = ip2long($r[0]);
                        $max = ip2long($r[1]);
                        if ((ip2long($ip) >= $min) && (ip2long($ip) <= $max)) return false;
                }
                return true;
        } else {
                return false;
        }
}

function getip() {
        if (isset($_SERVER["HTTP_CLIENT_IP"]) && validip($_SERVER["HTTP_CLIENT_IP"])) {
                return $_SERVER["HTTP_CLIENT_IP"];
        }
	if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
		foreach (explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]) as $ip) {
			if (validip(trim($ip))) {
				return $ip;
			}
		}
	}
        if (isset($_SERVER["HTTP_X_FORWARDED"]) && validip($_SERVER["HTTP_X_FORWARDED"])) {
                return $_SERVER["HTTP_X_FORWARDED"];
        } elseif (isset($_SERVER["HTTP_FORWARDED_FOR"]) && validip($_SERVER["HTTP_FORWARDED_FOR"])) {
                return $_SERVER["HTTP_FORWARDED_FOR"];
        } elseif (isset($_SERVER["HTTP_FORWARDED"]) && validip($_SERVER["HTTP_FORWARDED"])) {
                return $_SERVER["HTTP_FORWARDED"];
        } elseif (isset($_SERVER["HTTP_X_FORWARDED"]) && validip($_SERVER["HTTP_X_FORWARDED"])) {
                return $_SERVER["HTTP_X_FORWARDED"];
        } else {
                return $_SERVER["REMOTE_ADDR"];
        }
}

/*
* This is just a general function I carry around to save a few key strokes
*
*/
function isGood(&$var){
	if(isset($var) && !empty($var) && $var != 'NA' && $var != '<null>'){
		return true;
	}else{ return false; }
}

?>
