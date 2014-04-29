<?php
if(!is_callable('get_option')) {
  // This is here to stop error messages on servers with Zend Accelerator, it includes all files before get_option is declared
  // then evidently includes them again, otherwise this code would break these modules
  return;
  exit("Something strange is happening, and \"return\" is not breaking out of a file.");
}
global $gateway_checkout_form_fields;

$nzshpcrt_gateways[$num] = array(
	'name' => __( 'eWay', 'wpsc_gold_cart' ),
	'api_version' => 2.0,
	'class_name' => 'wpsc_merchant_eway',
	'has_recurring_billing' => false,
	'wp_admin_cannot_cancel' => false,
	'requirements' => array(
		 /// so that you can restrict merchant modules to PHP 5, if you use PHP 5 features
		'php_version' => 5.0,
		 /// for modules that may not be present, like curl
		'extra_modules' => array('soap', 'curl')
	),
	
	// this may be legacy, not yet decided
	'internalname' => 'wpsc_merchant_eway',

	// All array members below here are legacy, and use the code in paypal_multiple.php
	'form' => "form_eway",
	'submit_function' => "submit_eway",
	'payment_type' => "credit_card",
	'supported_currencies' => array(
	'currency_list' => array('USD')
		//,'option_name' => 'paypal_curcode'
	)
);

class wpsc_merchant_eway extends wpsc_merchant {
	var $name = 'eWay';
	var $credit_card_details = array(
		'card_number' => null,
		'expiry_month' => null,
		'expiry_year' => null,
		'card_code' => null
  );
  
	function submit() {

		//Send card data
		$this->credit_card_details = array(
			'card_number' => $_POST['card_number'],
			'expiry_month' => $_POST['expiry_month'],
			'expiry_year' => $_POST['expiry_year'],
			'card_code' => $_POST['card_code']
		);
		
		//Create RapidAPI Service
		$service = new RapidAPI();
		$request = new CreateAccessCodeRequest();
		
		//Send vars to eWay
		$request->Customer->FirstName = $this->cart_data['billing_address']['first_name'];
		$request->Customer->LastName = $this->cart_data['billing_address']['last_name'];
		$request->Customer->Reference = $this->cart_data['session_id'];
		$request->Customer->City = $this->cart_data['billing_address']['city'];
		$request->Customer->State = $this->cart_data['billing_address']['state'];
		$request->Customer->PostalCode = $this->cart_data['billing_address']['post_code'];
		$request->Customer->Email = $this->cart_data['email_address'];
		//Populate values for LineItems
		$i = 0;
		foreach($this->cart_items as $cart_row) {
			$item[$i] = new LineItem();
			$item[$i]->Description = $cart_row['name'];
			$item[$i]->Quantity = $cart_row['quantity'];
			$item[$i]->UnitCost = $cart_row['price'] * 100;
			$item[$i]->Total = ($cart_row['price'] * 100) * $cart_row['quantity'];
			$request->Items->LineItem[$i] = $item[$i];
			$i++;
		}
		//Options
		$opt = new Option();
		$opt->Value = $this->cart_data['session_id'];
		$request->Options->Option[0]= $opt;
		
		//Populate values for Payment Object
		$request->Payment->TotalAmount = number_format($this->cart_data['total_price'],2,'.','') * 100;
		$request->Payment->CurrencyCode = $this->cart_data['store_currency'];
		$request->Payment->InvoiceReference = $this->cart_data['session_id'];
		
		//Misc data
		$request->RedirectUrl = $this->cart_data['transaction_results_url'];
		$request->Method = 'ProcessPayment';
		$result = $service->CreateAccessCode($request);
		if (isset($result->Errors)) {
			//Get Error Messages from Error Code. Error Code Mappings are in the Config.ini file
			$ErrorArray = explode(",", trim($result->Errors));
			$lblError = "";
			foreach ( $ErrorArray as $error ) {
					$lblError .= $error;
			}
		}
		if (isset($lblError)) {
		   $error_messages = wpsc_get_customer_meta( 'checkout_misc_error_messages' );
			if ( ! is_array( $error_messages ) )
				$error_messages = array();
			$error_messages[] = '<strong style="color:red">' . parse_error_message_eway($lblError) . ' </strong>';
			wpsc_update_customer_meta( 'checkout_misc_error_messages', $error_messages );
			return false;
		}
		$accesscode = $result->AccessCode;
		$redirurl = $result->FormActionURL;
		
		$form = '
			<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
			<html lang="en">
			<head>
			<title></title>
			</head>
			<body>
			<form id="eway_form" action="' .$redirurl . '" method="POST">
            <input type="hidden" name="EWAY_ACCESSCODE" value="'.$accesscode.'">
			<input type="hidden" name="EWAY_CARDNAME" value="'.$this->cart_data['billing_address']['first_name'] . ' ' . $this->cart_data['billing_address']['last_name'].'">
			<input type="hidden" name="EWAY_CARDNUMBER" value="'.$this->credit_card_details['card_number'].'">
			<input type="hidden" name="EWAY_CARDEXPIRYMONTH" value="'.$this->credit_card_details['expiry_month'].'">
			<input type="hidden" name="EWAY_CARDEXPIRYYEAR" value="'.$this->credit_card_details['expiry_year'].'">
			<input type="hidden" name="EWAY_CARDCVN" value="'.$this->credit_card_details['card_code'].'">
			</form>
			<script type="text/javascript">document.getElementById("eway_form").submit();</script>
			</body>
			</html>';
		echo $form;
		}
}

if ( isset( $_GET['AccessCode'] ) ) {
	add_action('init', 'wpec_eway_return');
}

function wpec_eway_return() {
	if(get_option('permalink_structure') != '')
		$separator ="?";
	else
		$separator ="&";

	$transact_url = get_option('transact_url');
	//Get transaction results
	$service = new RapidAPI();
	$request = new GetAccessCodeResultRequest();
	
	$request->AccessCode = $_GET['AccessCode'];
	$result = $service->GetAccessCodeResult($request);
	

	
	if(isset($result->TransactionStatus) && $result->TransactionStatus && (is_bool($result->TransactionStatus) || $result->TransactionStatus != "false")) {
		$sessionid = $result->InvoiceReference;
		$purchase_log = new WPSC_Purchase_Log( $sessionid, 'sessionid' );
		$purchase_log->set( array(
		'processed' => WPSC_Purchase_Log::ACCEPTED_PAYMENT,
		'transactid' => $result->TransactionID,
		'notes' => 'eWay Auth Code : "' . $result->AuthorisationCode . '"',
		) );
		$purchase_log->save();
		// set this global, wonder if this is ok
		header("Location: ".$transact_url.$separator."sessionid=".$sessionid);
		exit();
	} else {
	   $error_messages = wpsc_get_customer_meta( 'checkout_misc_error_messages' );
		if ( ! is_array( $error_messages ) )
			$error_messages = array();
		$error_messages[] = '<strong style="color:red">' . parse_error_message_eway($result->ResponseMessage) . ' </strong>';
		wpsc_update_customer_meta( 'checkout_misc_error_messages', $error_messages );	
		$checkout_page_url = get_option( 'shopping_cart_url' );
		if ( $checkout_page_url ) {
		  header( 'Location: '.$checkout_page_url );
		  exit();
		}
	}
}

function parse_error_message_eway($message){
	
	$error_codes = array('F7000' => "Undefined Fraud",'V5000' => "Undefined System",'A0000' => "Undefined Approved",'A2000' => "Transaction Approved",	'A2008' => "Honour With Identification",'A2010' => "Approved For Partial Amount",'A2011' => "Approved VIP",'A2016' => "Approved Update Track 3",'V6000' => "Undefined Validation",'V6001' => "Invalid Request CustomerIP",'V6002' => "Invalid Request DeviceID",'V6011' => "Invalid Payment Amount",'V6012' => "Invalid Payment InvoiceDescription",'V6013' => "Invalid Payment InvoiceNumber",'V6014' => "Invalid Payment InvoiceReference",'V6015' => "Invalid Payment CurrencyCode",'V6016' => "Payment Required",'V6017' => "Payment CurrencyCode Required",'V6018' => "Unknown Payment CurrencyCode",'V6021' => "Cardholder Name Required",'V6022' => "Card Number Required",'V6023' => "CVN Required",'V6031' => "Invalid Card Number",'V6032' => "Invalid CVN",'V6033' => "Invalid Expiry Date",'V6034' => "Invalid Issue Number",'V6035' => "Invalid Start Date",'V6036' => "Invalid Month",'V6037' => "Invalid Year",'V6040' => "Invaild Token Customer Id",'V6041' => "Customer Required",'V6042' => "Customer First Name Required",'V6043' => "Customer Last Name Required",'V6044' => "Customer Country Code Required",'V6045' => "Customer Title Required",'V6046' => "Token Customer ID Required",'V6047' => "RedirectURL Required",'V6051' => "Invalid Customer First Name",'V6052' => "Invalid Customer Last Name",'V6053' => "Invalid Customer Country Code",'V6054' => "Invalid Customer Email",'V6055' => "Invalid Customer Phone",'V6056' => "Invalid Customer Mobile",'V6057' => "Invalid Customer Fax",'V6058' => "Invalid Customer Title",'V6059' => "Redirect URL Invalid",'V6060' => "Redirect URL Invalid",'V6061' => "Invaild Customer Reference",'V6062' => "Invaild Customer CompanyName",'V6063' => "Invaild Customer JobDescription",'V6064' => "Invaild Customer Street1",'V6065' => "Invaild Customer Street2",'V6066' => "Invaild Customer City",'V6067' => "Invaild Customer State",'V6068' => "Invaild Customer Postalcode",'V6069' => "Invaild Customer Email",'V6070' => "Invaild Customer Phone",'V6071' => "Invaild Customer Mobile",'V6072' => "Invaild Customer Comments",'V6073' => "Invaild Customer Fax",'V6074' => "Invaild Customer Url",'V6075' => "Invaild ShippingAddress FirstName",'V6076' => "Invaild ShippingAddress LastName",'V6077' => "Invaild ShippingAddress Street1",'V6078' => "Invaild ShippingAddress Street2",'V6079' => "Invaild ShippingAddress City",'V6080' => "Invaild ShippingAddress State",'V6081' => "Invaild ShippingAddress PostalCode",'V6082' => "Invaild ShippingAddress Email",'V6083' => "Invaild ShippingAddress Phone",'V6084' => "Invaild ShippingAddress Country",'V6091' => "Unknown Country Code",'V6100' => "Invalid ProcessRequest name",'V6101' => "Invalid ProcessRequest ExpiryMonth",'V6102' => "Invalid ProcessRequest ExpiryYear",'V6103' => "Invalid ProcessRequest StartMonth",'V6104' => "Invalid ProcessRequest StartYear",'V6105' => "Invalid ProcessRequest IssueNumber",'V6106' => "Invalid ProcessRequest CVN",'V6107' => "Invalid ProcessRequest AccessCode",'V6108' => "Invalid ProcessRequest CustomerHostAddress",'V6109' => "Invalid ProcessRequest UserAgent",'V6110' => "Invalid ProcessRequest Number",'D4401' => "Refer to Issuer",'D4402' => "Refer to Issuer, special",'D4403' => "No Merchant",'D4404' => "Pick Up Card",'D4405' => "Do Not Honour",'D4406' => "Error",'D4407' => "Pick Up Card, Special",'D4409' => "Request In Progress",'D4412' => "Invalid Transaction",'D4413' => "Invalid Amount",'D4414' => "Invalid Card Number",'D4415' => "No Issuer",'D4419' => "Re-enter Last Transaction",'D4421' => "No Method Taken",'D4422' => "Suspected Malfunction",'D4423' => "Unacceptable Transaction Fee",'D4425' => "Unable to Locate Record On File",'D4430' => "Format Error",'D4431' => "Bank Not Supported By Switch",'D4433' => "Expired Card, Capture",'D4434' => "Suspected Fraud, Retain Card",'D4435' => "Card Acceptor, Contact Acquirer, Retain Card",'D4436' => "Restricted Card, Retain Card",'D4437' => "Contact Acquirer Security Department, Retain Card",'D4438' => "PIN Tries Exceeded, Capture",'D4439' => "No Credit Account",'D4440' => "Function Not Supported",'D4441' => "Lost Card",'D4442' => "No Universal Account",'D4443' => "Stolen Card",'D4444' => "No Investment Account",'D4451' => "Insufficient Funds",'D4452' => "No Cheque Account",'D4453' => "No Savings Account",'D4454' => "Expired Card",'D4455' => "Incorrect PIN",'D4456' => "No Card Record",'D4457' => "Function Not Permitted to Cardholder",'D4458' => "Function Not Permitted to Terminal",'D4460' => "Acceptor Contact Acquirer",'D4461' => "Exceeds Withdrawal Limit",'D4462' => "Restricted Card",'D4463' => "Security Violation",'D4464' => "Original Amount Incorrect",'D4466' => "Acceptor Contact Acquirer, Security",'D4467' => "Capture Card",'D4475' => "PIN Tries Exceeded",'D4482' => "CVV Validation Error",'D4490' => "Cutoff In Progress",'D4491' => "Card Issuer Unavailable",'D4492' => "Unable To Route Transaction",'D4493' => "Cannot Complete, Violation Of The Law",'D4494' => "Duplicate Transaction",'D4496' => "System Error",);
	$message = $error_codes[$message];
	return $message;		
}

if(in_array('wpsc_merchant_eway',(array)get_option('custom_gateway_options'))) {
	$gateway_checkout_form_fields[$nzshpcrt_gateways[$num]['internalname']] = "
	<tr>
		<td>".__( 'Credit Card Number *', 'wpsc_gold_cart' )."</td>
		<td>
			<input type='text' value='' name='card_number' />
		</td>
	</tr>
	<tr>
		<td>".__( 'Credit Card Expiry *', 'wpsc_gold_cart' )."</td>
		<td>
			<input type='text' size='2' value='' maxlength='2' name='expiry_month' />/<input type='text' size='2'  maxlength='2' value='' name='expiry_year' />
		</td>
	</tr>
	<tr>
		<td>".__( 'CVV', 'wpsc_gold_cart' )."</td>
		<td><input type='text' size='4' value='' maxlength='4' name='card_code' /></td>
	</tr>
";
}

function submit_eway() {
	$options = array(
		'eway_apikey',
		'eway_apipassword',
		'eway_testmode',
	);
	foreach ( $options as $option ) {
		update_option( $option, $_POST[$option] );
	}
	return true;
}

function form_eway() {
	return "
		<tr>
			<td>
				".__( 'Api Key', 'wpsc_gold_cart' )."
			</td>
			<td>
				<input type='text' size='40' value='". get_option('eway_apikey')."' name='eway_apikey' />
			</td>
		</tr>
		<tr>
			<td>
				".__( 'Api Password', 'wpsc_gold_cart' )."
			</td>
			<td>
				<input type='text' size='40' value='". get_option('eway_apipassword')."' name='eway_apipassword' />
			</td>
		</tr>
		<tr>
			<td>
				". __( 'Transaction Mode:', 'wpsc_gold_cart' ) . "
			</td>
			<td>
				<select lass='widefat' name='eway_testmode'>
					<option  value='live' ".selected(get_option('eway_testmode') , 'live',false) . " > ". __( 'Live Mode', 'wpsc_gold_cart' ) . "</option>
					<option  value='test' ".selected(get_option('eway_testmode') , 'test',false) . " >  ". __( 'Test Mode', 'wpsc_gold_cart' ) . "</option>
				</select>
			</td>
		</tr>";
}

class RapidAPI {

    var $APIConfig;

    function __construct() {
        //Load the configuration
        $this->APIConfig['Payment.Username'] = get_option('eway_apikey');
		$this->APIConfig['Payment.Password'] = get_option('eway_apipassword');
		$this->APIConfig['PaymentService.Soap'] = get_option('eway_testmode') == 'test' ? 'https://api.sandbox.ewaypayments.com/Soap.asmx?WSDL' : 'https://api.ewaypayments.com/Soap.asmx?WSDL';
		$this->APIConfig['PaymentService.POST.CreateAccessCode'] = get_option('eway_testmode') == 'test' ? 'https://api.sandbox.ewaypayments.com/CreateAccessCode.json' : 'https://api.ewaypayments.com/CreateAccessCode.json';
		$this->APIConfig['PaymentService.POST.GetAccessCodeResult'] = get_option('eway_testmode') == 'test' ? 'https://api.sandbox.ewaypayments.com/GetAccessCodeResult.json' : 'https://api.ewaypayments.com/GetAccessCodeResult.json';
		$this->APIConfig['PaymentService.REST'] = get_option('eway_testmode') == 'test' ? 'https://api.sandbox.ewaypayments.com/AccessCode' : 'https://api.ewaypayments.com/AccessCode';
		$this->APIConfig['PaymentService.RPC'] = get_option('eway_testmode') == 'test' ? 'https://api.sandbox.ewaypayments.com/json-rpc' : 'https://api.ewaypayments.com/json-rpc';
		$this->APIConfig['PaymentService.JSONPScript'] = get_option('eway_testmode') == 'test' ? 'https://api.sandbox.ewaypayments.com/JSONP/v1/js' : 'https://api.ewaypayments.com/JSONP/v1/js';
		$this->APIConfig['Request:Method'] = 'SOAP';
		$this->APIConfig['Request:Format'] = 'JSON';
		$this->APIConfig['ShowDebugInfo'] = 0;	
    }

    /**
     * Description: Create Access Code
     * @param CreateAccessCodeRequest $request
     * @return StdClass An PHP Ojbect 
     */
    public function CreateAccessCode($request) {

        //Convert An Object to Target Formats
        if ($this->APIConfig['Request:Method'] != "SOAP")
            if ($this->APIConfig['Request:Format'] == "XML")
                if ($this->APIConfig['Request:Method'] != "RPC")
                    $request = WPSC_GC_Parser::Obj2XML($request);
                else
                    $request = WPSC_GC_Parser::Obj2RPCXML("CreateAccessCode", $request);
            else {
                $i = 0;
                $tempClass = new stdClass;
                foreach ($request->Options->Option as $Option) {
                    $tempClass->Options[$i] = $Option;
                    $i++;
                }
                $request->Options = $tempClass->Options;
                $i = 0;
                $tempClass = new stdClass;
                foreach ($request->Items->LineItem as $LineItem) {
                    $tempClass->Items[$i] = $LineItem;
                    $i++;
                }
                $request->Items = $tempClass->Items;
                if ($this->APIConfig['Request:Method'] != "RPC")
                    $request = WPSC_GC_Parser::Obj2JSON($request);
                else
                    $request = WPSC_GC_Parser::Obj2JSONRPC("CreateAccessCode", $request);
            }
        else
            $request = WPSC_GC_Parser::Obj2ARRAY($request);

        $method = 'CreateAccessCode' . $this->APIConfig['Request:Method'];

        $response = $this->$method($request);

        //Convert Response Back TO An Object
        if ($this->APIConfig['Request:Method'] != "SOAP")
            if ($this->APIConfig['Request:Format'] == "XML")
                if ($this->APIConfig['Request:Method'] != "RPC")
                    $result = WPSC_GC_Parser::XML2Obj($response);
                else
                    $result = WPSC_GC_Parser::RPCXML2Obj($response);
            else
            if ($this->APIConfig['Request:Method'] != "RPC")
                $result = WPSC_GC_Parser::JSON2Obj($response);
            else
                $result = WPSC_GC_Parser::JSONRPC2Obj($response);
        else
            $result = $response;

        return $result;
    }

    /**
     * Description: Get Result with Access Code
     * @param GetAccessCodeResultRequest $request
     * @return StdClass An PHP Ojbect 
     */
    public function GetAccessCodeResult($request) {
        
        if ($this->APIConfig['ShowDebugInfo']) {
            echo "GetAccessCodeResult Request Object";
            var_dump($request);
        }

        //Convert An Object to Target Formats
        if ($this->APIConfig['Request:Method'] != "SOAP")
            if ($this->APIConfig['Request:Format'] == "XML")
                if ($this->APIConfig['Request:Method'] != "RPC")
                    $request = WPSC_GC_Parser::Obj2XML($request);
                else
                    $request = WPSC_GC_Parser::Obj2RPCXML("GetAccessCodeResult", $request);
            else
            if ($this->APIConfig['Request:Method'] != "RPC")
                $request = WPSC_GC_Parser::Obj2JSON($request);
            else
                $request = WPSC_GC_Parser::Obj2JSONRPC("GetAccessCodeResult", $request);
        else
            $request = WPSC_GC_Parser::Obj2ARRAY($request);

        //Build method name
        $method = 'GetAccessCodeResult' . $this->APIConfig['Request:Method'];
        
        //Is Debug Mode
        if ($this->APIConfig['ShowDebugInfo']) {
            echo "GetAccessCodeResult Request String";
            var_dump($request);
        }

        //Call to the method
        $response = $this->$method($request);
        
        //Is Debug Mode
        if ($this->APIConfig['ShowDebugInfo']) {
            echo "GetAccessCodeResult Response String";
            var_dump($response);
        }

        //Convert Response Back TO An Object
        if ($this->APIConfig['Request:Method'] != "SOAP")
            if ($this->APIConfig['Request:Format'] == "XML")
                if ($this->APIConfig['Request:Method'] != "RPC")
                    $result = WPSC_GC_Parser::XML2Obj($response);
                else {
                    $result = WPSC_GC_Parser::RPCXML2Obj($response);

                    //Tweak the Options Obj to $obj->Options->Option[$i]->Value instead of $obj->Options[$i]->Value
                    if (isset($result->Options)) {
                        $i = 0;
                        $tempClass = new stdClass;
                        foreach ($result->Options as $Option) {
                            $tempClass->Option[$i]->Value = $Option->Value;
                            $i++;
                        }
                        $result->Options = $tempClass;
                    }
                } else {
                if ($this->APIConfig['Request:Method'] == "RPC")
                    $result = WPSC_GC_Parser::JSONRPC2Obj($response);
                else
                    $result = WPSC_GC_Parser::JSON2Obj($response);

                //Tweak the Options Obj to $obj->Options->Option[$i]->Value instead of $obj->Options[$i]->Value
                if (isset($result->Options)) {
                    $i = 0;
                    $tempClass = new stdClass;
                    foreach ($result->Options as $Option) {
                        $tempClass->Option[$i]->Value = $Option->Value;
                        $i++;
                    }
                    $result->Options = $tempClass;
                }
            }
        else
            $result = $response;

        //Is Debug Mode
        if ($this->APIConfig['ShowDebugInfo']) {
            echo "GetAccessCodeResult Response Object";
            var_dump($result);
        }

        return $result;
    }

    /**
     * Description: Create Access Code Via SOAP
     * @param Array $request
     * @return StdClass An PHP Ojbect 
     */
    public function CreateAccessCodeSOAP($request) {

        try {
            $client = new SoapClient($this->APIConfig["PaymentService.Soap"], array(
                        'trace' => true,
                        'exceptions' => true,
                        'login' => $this->APIConfig['Payment.Username'],
                        'password' => $this->APIConfig['Payment.Password'],
                    ));
            $result = $client->CreateAccessCode(array('request' => $request));
            //echo(htmlspecialchars($client->__getLastRequest()));
        } catch (Exception $e) {
            $lblError = $e->getMessage();
        }

        if (isset($lblError)) {
            echo "<h2>CreateAccessCode SOAP Error: $lblError</h2><pre>";
            die();
        }
        else
            return $result->CreateAccessCodeResult;
    }

    /**
     * Description: Get Result with Access Code Via SOAP
     * @param Array $request
     * @return StdClass An PHP Ojbect 
     */
    public function GetAccessCodeResultSOAP($request) {

        try {
            $client = new SoapClient($this->APIConfig["PaymentService.Soap"], array(
                        'trace' => true,
                        'exceptions' => true,
                        'login' => $this->APIConfig['Payment.Username'],
                        'password' => $this->APIConfig['Payment.Password'],
                    ));
            $result = $client->GetAccessCodeResult(array('request' => $request));
        } catch (Exception $e) {
            $lblError = $e->getMessage();
        }

        if (isset($lblError)) {
            echo "<h2>GetAccessCodeResult SOAP Error: $lblError</h2><pre>";
            die();
        }
        else
            return $result->GetAccessCodeResultResult;
    }

    /**
     * Description: Create Access Code Via REST POST
     * @param XML/JSON Format $request
     * @return XML/JSON Format Response 
     */
    public function CreateAccessCodeREST($request) {

        $response = $this->PostToRapidAPI($this->APIConfig["PaymentService.REST"] . "s", $request);

        return $response;
    }

    /**
     * Description: Get Result with Access Code Via REST GET
     * @param XML/JSON Format $request
     * @return XML/JSON Format Response 
     */
    public function GetAccessCodeResultREST($request) {

        $response = $this->PostToRapidAPI($this->APIConfig["PaymentService.REST"] . "/" . $_GET['AccessCode'], $request, false);

        return $response;
    }

    /**
     * Description: Create Access Code Via HTTP POST
     * @param XML/JSON Format $request
     * @return XML/JSON Format Response 
     */
    public function CreateAccessCodePOST($request) {

        $response = $this->PostToRapidAPI($this->APIConfig["PaymentService.POST.CreateAccessCode"], $request);

        return $response;
    }

    /**
     * Description: Get Result with Access Code Via HTTP POST
     * @param XML/JSON Format $request
     * @return XML/JSON Format Response 
     */
    public function GetAccessCodeResultPOST($request) {

        $response = $this->PostToRapidAPI($this->APIConfig["PaymentService.POST.GetAccessCodeResult"], $request);

        return $response;
    }

    /**
     * Description: Create Access Code Via HTTP POST
     * @param XML/JSON Format $request
     * @return XML/JSON Format Response 
     */
    public function CreateAccessCodeRPC($request) {

        $response = $this->PostToRapidAPI($this->APIConfig["PaymentService.RPC"], $request);

        return $response;
    }

    /**
     * Description: Get Result with Access Code Via HTTP POST
     * @param XML/JSON Format $request
     * @return XML/JSON Format Response 
     */
    public function GetAccessCodeResultRPC($request) {
      
        $response = $this->PostToRapidAPI($this->APIConfig["PaymentService.RPC"], $request);

        return $response;
    }

    /*
     * Description A Function for doing a Curl GET/POST
     */

    private function PostToRapidAPI($url, $request, $IsPost = true) {

        $ch = curl_init($url);

        if ($this->APIConfig['Request:Format'] == "XML")
            curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
        else
            curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/json"));

        curl_setopt($ch, CURLOPT_USERPWD, $this->APIConfig['Payment.Username'] . ":" . $this->APIConfig['Payment.Password']);
        if ($IsPost)
            curl_setopt($ch, CURLOPT_POST, true);
        else
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);

        if (curl_errno($ch) != CURLE_OK) {
            echo "<h2>POST Error: " . curl_error($ch) . " URL: $url</h2><pre>";
            die();
        } else {
            curl_close($ch);
            return $response;
        }
    }

}

/**
 * Description of CreateAccessCodeRequest
 * 
 * 
 */
class CreateAccessCodeRequest {

    /**
     * @var Customer $Customer
     */
    public $Customer;

    /**
     * @var ShippingAddress $ShippingAddress
     */
    public $ShippingAddress;
    public $Items;
    public $Options;

    /**
     * @var Payment $Payment
     */
    public $Payment;
    public $RedirectUrl;
    public $Method;
    private $CustomerIP;
    private $DeviceID;

    function __construct() {

        $this->Customer = new Customer();
        $this->ShippingAddress = new ShippingAddress();
        $this->Payment = new Payment();
        $this->CustomerIP = $_SERVER["SERVER_NAME"];
    }

}

/**
 * Description of Customer
 */
class Customer {

    public $TokenCustomerID;
    public $Reference;
    public $Title;
    public $FirstName;
    public $LastName;
    public $CompanyName;
    public $JobDescription;
    public $Street1;
    public $Street2;
    public $City;
    public $State;
    public $PostalCode;
    public $Country;
    public $Email;
    public $Phone;
    public $Mobile;
    public $Comments;
    public $Fax;
    public $Url;

}

class ShippingAddress {

    public $FirstName;
    public $LastName;
    public $Street1;
    public $Street2;
    public $City;
    public $State;
    public $Country;
    public $PostalCode;
    public $Email;
    public $Phone;
    public $ShippingMethod;

}

class Items {

    public $LineItem = array();

}

class LineItem {

    public $SKU;
    public $Description;

}

class eWay_Options {

    public $Option = array();

}

class Option {

    public $Value;

}

class Payment {

    public $TotalAmount;
    /// <summary>The merchant's invoice number</summary>
    public $InvoiceNumber;
    /// <summary>merchants invoice description</summary>
    public $InvoiceDescription;
    /// <summary>The merchant's invoice reference</summary>
    public $InvoiceReference;
    /// <summary>The merchant's currency</summary>
    public $CurrencyCode;

}

class GetAccessCodeResultRequest {

    public $AccessCode;

}

/*
 * Description A Class for conversion between different formats
 */

class WPSC_GC_Parser {

    public static function Obj2JSON($obj) {

        return json_encode($obj);
    }

    public static function Obj2JSONRPC($APIAction, $obj) {

        if ($APIAction == "CreateAccessCode") {
            //Tweak the request object in order to generate a valid JSON-RPC format for RapidAPI.
            $obj->Payment->TotalAmount = (int) $obj->Payment->TotalAmount;
        }

        $tempClass = new stdClass;
        $tempClass->id = 1;
        $tempClass->method = $APIAction;
        $tempClass->params->request = $obj;

        return json_encode($tempClass);
    }

    public static function Obj2ARRAY($obj) {
        //var_dump($obj);
        return get_object_vars($obj);
    }

    public static function Obj2XML($obj) {

        $xml = new XmlWriter();
        $xml->openMemory();
        $xml->setIndent(TRUE);

        $xml->startElement(get_class($obj));
        $xml->writeAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $xml->writeAttribute("xmlns:xsd", "http://www.w3.org/2001/XMLSchema");

        self::getObject2XML($xml, $obj);

        $xml->endElement();

        $xml->endElement();

        return $xml->outputMemory(true);
    }

    public static function Obj2RPCXML($APIAction, $obj) {

        if ($APIAction == "CreateAccessCode") {
            //Tweak the request object in order to generate a valid XML-RPC format for RapidAPI.
            $obj->Payment->TotalAmount = (int) $obj->Payment->TotalAmount;

            $obj->Items = $obj->Items->LineItem;

            $obj->Options = $obj->Options->Option;

            $obj->Customer->TokenCustomerID = (float) (isset($obj->Customer->TokenCustomerID) ? $obj->Customer->TokenCustomerID : null);

            return str_replace("double>", "long>", xmlrpc_encode_request($APIAction, get_object_vars($obj)));
        }

        if ($APIAction == "GetAccessCodeResult") {
            return xmlrpc_encode_request($APIAction, get_object_vars($obj));
        }
    }

    public static function JSON2Obj($obj) {
        return json_decode($obj);
    }

    public static function JSONRPC2Obj($obj) {
        
        
        $tempClass = json_decode($obj);
        
        if (isset($tempClass->error)) {
            $tempClass->Errors = $tempClass->error->data;
            return $tempClass;
        }

        return $tempClass->result;
    }

    public static function XML2Obj($obj) {
        //Strip the empty JSON object
        return json_decode(str_replace("{}", "null", json_encode(simplexml_load_string($obj))));
    }

    public static function RPCXML2Obj($obj) {
        return json_decode(json_encode(xmlrpc_decode($obj)));
    }

    public static function HasProperties($obj) {
        if (is_object($obj)) {
            $reflect = new ReflectionClass($obj);
            $props = $reflect->getProperties();
            return !empty($props);
        }
        else
            return TRUE;
    }

    private static function getObject2XML(XMLWriter $xml, $data) {
        foreach ($data as $key => $value) {

            if ($key == "TokenCustomerID" && $value == "") {
                $xml->startElement("TokenCustomerID");
                $xml->writeAttribute("xsi:nil", "true");
                $xml->endElement();
            }

            if (is_object($value)) {
                $xml->startElement($key);
                self::getObject2XML($xml, $value);
                $xml->endElement();
                continue;
            } else if (is_array($value)) {
                self::getArray2XML($xml, $key, $value);
            }

            if (is_string($value)) {
                $xml->writeElement($key, $value);
            }
        }
    }

    private static function getArray2XML(XMLWriter $xml, $keyParent, $data) {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $xml->writeElement($keyParent, $value);
                continue;
            }

            if (is_numeric($key)) {
                $xml->startElement($keyParent);
            }

            if (is_object($value)) {
                self::getObject2XML($xml, $value);
            } else if (is_array($value)) {
                $this->getArray2XML($xml, $key, $value);
                continue;
            }

            if (is_numeric($key)) {
                $xml->endElement();
            }
        }
    }

}
?>