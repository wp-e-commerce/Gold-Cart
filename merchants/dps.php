<?php
$nzshpcrt_gateways[$num]['name'] = __( 'DPS / Payment Express(DPS Hosted) - PxAccess', 'wpsc_gold_cart' );
$nzshpcrt_gateways[$num]['internalname'] = 'dps';
$nzshpcrt_gateways[$num]['function'] = 'gateway_dps';
$nzshpcrt_gateways[$num]['form'] = "form_dps";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_dps";
$nzshpcrt_gateways[$num]['payment_type'] = 'credit_card';

function gateway_dps($seperator, $sessionid)
  {
  $PxAccess_Url  = get_option('access_url');
  $PxAccess_Userid = get_option('access_userid');
  $PxAccess_Key  = get_option('access_key');
  $Mac_Key = get_option('mac_key');
  $pxaccess = new PxAccess($PxAccess_Url, $PxAccess_Userid, $PxAccess_Key, $Mac_Key);

  $request = new PxPayRequest();

  $http_host   = getenv("HTTP_HOST");
  $request_uri = getenv("SCRIPT_NAME");
  $server_url  = get_option('siteurl');
  $script_url  = get_option('transact_url'); //Using this code after PHP version 4.3.4  ?page_id=$_GET['page_id']
  //echo $script_url . '<br />';
  //exit(get_option('checkout_url'));
  # the following variables are read from the form
  $Address1 = $_POST['address'];
  $Address2 = "";

  #Set up PxPayRequest Object
  $request->setAmountInput( nzshpcrt_overall_total_price( wpsc_get_customer_meta( 'shipping_country' ) ) );
  $request->setTxnData1(get_option('blogname'));# whatever you want to appear, original:   $request->setTxnData1("Widget order");
  $request->setTxnData2("n/a");   # whatever you want to appear
  $request->setTxnData3("n/a");   # whatever you want to appear
  $request->setTxnType("Purchase");
  if(get_option('dps_curcode') != '') {
		$request->setInputCurrency(get_option('dps_curcode'));
  } else {
		$request->setInputCurrency("USD");
	}
  $request->setMerchantReference($sessionid); # fill this with your order number
  $request->setEmailAddress(get_option('purch_log_email'));
  $request->setUrlFail($script_url);
  $request->setUrlSuccess($script_url);

  #Call makeResponse of PxAccess object to obtain the 3-DES encrypted payment request
  $request_string = $pxaccess->makeRequest($request);
  header("Location: $request_string");
  exit();
  }

function submit_dps() {
	$options = array(
		'access_url',
		'access_userid',
		'access_key',
		'mac_key',
		'dps_curcode',
	);
	foreach ( $options as $option ) {
		if ( ! empty( $_POST[$option] ) )
			update_option( $option, $_POST[$option] );
	}
	return true;
}

function decrypt_dps_response(){
  global $wpdb;
  $PxAccess_Url  = get_option('access_url');
  $PxAccess_Userid = get_option('access_userid');
  $PxAccess_Key  = get_option('access_key');
  $Mac_Key = get_option('mac_key');

  $pxaccess = new PxAccess($PxAccess_Url, $PxAccess_Userid, $PxAccess_Key, $Mac_Key);
  $curgateway = get_option('payment_gateway');
  $sessionid = $_GET['sessionid'];
  $enc_hex = $_GET["result"];
  if($enc_hex != null) {
	$rsp = $pxaccess->getResponse($enc_hex);
	$siteurl = get_option('siteurl');
	$total_weight = 0;
	if(($rsp->getResponseText() == 'APPROVED')){
    	$sessionid = $rsp->getMerchantReference();
    	$purchase_log = new WPSC_Purchase_Log( $sessionid, 'sessionid' );
		if( ! $purchase_log->is_transaction_completed() ) {
			$purchase_log->set( 'processed', WPSC_Purchase_Log::ACCEPTED_PAYMENT );
			$purchase_log->save();
		}
	}
  }
  return $sessionid;
}

function form_dps() {
  $access_url = get_option('access_url');
  if($access_url == '') {
		update_option('access_url', "https://sec.paymentexpress.com/pxpay/pxpay.aspx"); //Correct PxAccess URL.
  }
  $output = " \n\r";
	//   $output = "
	$output .= "  <tr>\n\r";
	$output .= "      <td>\n\r";
	$output .= __( 'Access URL', 'wpsc_gold_cart' )."\n\r";
	$output .= "      </td>\n\r";
	$output .= "      <td>\n\r";
	$output .= "      <input type='text' size='40' value='". get_option('access_url')."' name='access_url' />\n\r";
	$output .= "      </td>\n\r";
	$output .= "  </tr>\n\r";
	$output .= "  <tr>\n\r";
	$output .= "      <td>\n\r";
	$output .= __( 'Access User Id', 'wpsc_gold_cart' )."\n\r";
	$output .= "      </td>\n\r";
	$output .= "      <td>\n\r";
	$output .= "      <input type='text' size='40' value='". get_option('access_userid')."' name='access_userid' />\n\r";
	$output .= "      </td>\n\r";
	$output .= "  </tr>\n\r";
	$output .= "  <tr>\n\r";
	$output .= "      <td>\n\r";
	$output .= __( 'Access Key', 'wpsc_gold_cart' )."\n\r";
	$output .= "      </td>\n\r";
	$output .= "      <td>\n\r";
	$output .= "      <input type='text' size='40' value='". get_option('access_key')."' name='access_key' />\n\r";
	$output .= "      </td>\n\r";
	$output .= "  </tr>\n\r";
	$output .= "  <tr>\n\r";
	$output .= "      <td>\n\r";
	$output .= __( 'Mac Key', 'wpsc_gold_cart' )."\n\r";
	$output .= "      </td>\n\r";
	$output .= "      <td>\n\r";
	$output .= "      <input type='text' size='40' value='". get_option('mac_key')."' name='mac_key' />\n\r";
	$output .= "      </td>\n\r";
	$output .= "  </tr>\n\r";

	$output .= "  <tr>\n\r";
	$output .= "      <td colspan='2'>\n\r";
	$output .= __( 'Note: DPS will give you a 64 character key / password. The first 48 characters of this go into the "Access Key" field and the last 16 characters go into the "Mac Key" field.', 'wpsc_gold_cart' )."\n\r";
	$output .= "      </td>\n\r";
	$output .= "  </tr>\n\r";


	$currencies = array(
		'USD' => __( 'United States Dollar', 'wpsc_gold_cart' ),
		'CAD' => __( 'Canadian Dollar', 'wpsc_gold_cart' ),
		'CHF' => __( 'Swiss Franc', 'wpsc_gold_cart' ),
		'EUR' => __( 'Euro', 'wpsc_gold_cart' ),
		'FRF' => __( 'French Franc', 'wpsc_gold_cart' ),
		'GBP' => __( 'United Kingdom Pound', 'wpsc_gold_cart' ),
		'HKD' => __( 'Hong Kong Dollar', 'wpsc_gold_cart' ),
		'JPY' => __( 'Japanese Yen', 'wpsc_gold_cart' ),
		'NZD' => __( 'New Zealand Dollar', 'wpsc_gold_cart' ),
		'SGD' => __( 'Singapore Dollar', 'wpsc_gold_cart' ),
		'ZAR' => __( 'Rand', 'wpsc_gold_cart' ),
		'AUD' => __( 'Australian Dollar', 'wpsc_gold_cart' ),
		'WST' => __( 'Samoan Tala', 'wpsc_gold_cart' ),
		'VUV' => __( 'Vanuatu Vatu', 'wpsc_gold_cart' ),
		'TOP' => __( "Tongan Pa'anga", 'wpsc_gold_cart' ),
		'SBD' => __( 'Solomon Islands Dollar', 'wpsc_gold_cart' ),
		'PGK' => __( 'Papua New Guinea Kina', 'wpsc_gold_cart' ),
		'MYR' => __( 'Malaysian Ringgit', 'wpsc_gold_cart' ),
		'KWD' => __( 'Kuwaiti Dinar', 'wpsc_gold_cart' ),
		'FJD' => __( 'Fiji Dollar', 'wpsc_gold_cart' ),
	);
	$chosen_currency = get_option( 'dps_curcode' );

	$output .= "  <tr>\n\r";
	$output .= "      <td>\n\r";
	$output .= __( 'Currency sent to DPS', 'wpsc_gold_cart' )."\n\r";
	$output .= "      </td>\n\r";
	$output .= "      <td>\n\r";
	$output .= "        <select name='dps_curcode'>\n\r";
	foreach ( $currencies as $currency => $title ) {
		$selected = $currency == $chosen_currency ? ' selected="selected"' : '';
		$output .= "<option{$selected} value='{$currency}'>" . esc_html( $title ) . "</option>\n";
	}
	$output .= "				</select>\n\r";
	$output .= "      </td>\n\r";
	$output .= "  </tr>\n\r";

	$output .= "  <tr>\n\r";
	$output .= "			<td colspan='2'>\n\r";
	$output .= __( 'Note: Because DPS does not support questionmarks in the URL you must use permalinks - currently only date and name based permalinks have been fully tested with DPS.', 'wpsc_gold_cart' )."\n\r";
	$output .= "			</td>\n\r";
	$output .= "  </tr>\n\r";
  return $output;
}

#******************************************************************************
#* Name          : PxAccess.inc.php
#* Description   : The objects for PX Payment page  
#* Copyright (c) : 2009 Direct Payment solutions
#* Date          : 2003-12-24
#* Modifications : 2003-12-24 MifMessage class
#*				 : 2004-09-01 PxAccess, PxPayRequest, PxPayResponse classes
#*							  which encapsulate 3-DES to handle payment requests and
#*							  response.
#*				   2004-10-14 Implements complete transactions
#*				   2005-03-14 change unpack("H*", $enc); to unpack("H$enclen", $enc); 
#*							  due to the version 4.3.10 Php unpack function bugs
#*				   2008-02-28 Added missing properties CardNumber, DateExpiry, CardHolderName.
#*				   2009-11-16 TT Supports V5 (Base-64) encoding of result and request query strings
#*                Also added support for php version 4 and 5.
#*Version		 : 3.01
#******************************************************************************

# MifMessage.
# Use this class to parse a DPS PX MifMessage in XML form,
# and access the content.
class MifMessage
{
  var $xml_;
  var $xml_index_;
  var $xml_value_;

  # Constructor:
  # Create a MifMessage with the specified XML text.
  # The constructor returns a null object if there is a parsing error.
  function MifMessage($xml)
  {
    $p = xml_parser_create();
    xml_parser_set_option($p,XML_OPTION_CASE_FOLDING,0);
    $ok = xml_parse_into_struct($p, $xml, $value, $index);
    xml_parser_free($p);
    if ($ok)
    {
      $this->xml_ = $xml;
      $this->xml_value_ = $value;
      $this->xml_index_ = $index;
    }
    #print_r($this->xml_value_); # JH_DEBUG
  }

  # Return the value of the specified top-level attribute.
  # This method can only return attributes of the root element.
  # If the attribute is not found, return "".
  function get_attribute($attribute)
  {
    #$attribute = strtoupper($attribute);
    $attributes = $this->xml_value_[0]["attributes"];
    return $attributes[$attribute];
  }

  # Return the text of the specified element.
  # The element is given as a simplified XPath-like name.
  # For example, "Link/ServerOk" refers to the ServerOk element
  # nested in the Link element (nested in the root element).
  # If the element is not found, return "".
  function get_element_text($element)
  {
    #print_r($this->xml_value_); # JH_DEBUG
    $index = $this->get_element_index($element, 0);
    if ($index == 0)
    {
      return "";
    }
    else
    {
	## TW2004-09-24: Fixed bug when elemnt existent but empty
    #
    $elementObj = $this->xml_value_[$index];
    if (! array_key_exists("value", $elementObj))
      return "";
   
    return $this->xml_value_[$index]["value"];
    }
  }

  # (internal method)
  # Return the index of the specified element,
  # relative to some given root element index.
  #
  function get_element_index($element, $rootindex = 0)
  {
    #$element = strtoupper($element);
    $pos = strpos($element, "/");
    if ($pos !== false)
    {
      # element contains '/': find first part
      $start_path = substr($element,0,$pos);
      $remain_path = substr($element,$pos+1);
      $index = $this->get_element_index($start_path, $rootindex);
      if ($index == 0)
      {
        # couldn't find first part; give up.
        return 0;
      }
      # recursively find rest
      return $this->get_element_index($remain_path, $index);
    }
    else
    {
      # search from the parent across all its children
      # i.e. until we get the parent's close tag.
      $level = $this->xml_value_[$rootindex]["level"];
      if ($this->xml_value_[$rootindex]["type"] == "complete")
      {
        return 0;   # no children
      }
      $index = $rootindex+1;
      while ($index<count($this->xml_value_) && 
             !($this->xml_value_[$index]["level"]==$level && 
               $this->xml_value_[$index]["type"]=="close"))
      {
        # if one below parent and tag matches, bingo
        if ($this->xml_value_[$index]["level"] == $level+1 &&
#            $this->xml_value_[$index]["type"] == "complete" &&
            $this->xml_value_[$index]["tag"] == $element)
        {
          return $index;
        }
        $index++;
      }
      return 0;
    }
  }
}

class PxAccess
{
	var $Mac_Key, $Des_Key;
	var $PxAccess_Url;
	var $PxAccess_Userid;
	function PxAccess($Url, $UserId, $Des_Key, $Mac_Key){
		error_reporting(E_ERROR);
		$this->Mac_Key = pack("H*",$Mac_Key);
		$this->Des_Key = pack("H*", $Des_Key);
		$this->PxAccess_Url = $Url;
		$this->PxAccess_Userid = $UserId;
	}
	function makeRequest($request)
	{
		#Validate the REquest
		if($request->validData() == false) return "" ;
			
  		#$txnId=rand(1,100000);
		$txnId = uniqid("MI");  #You need to generate you own unqiue reference. JZ:2004-08-12
		$request->setTxnId($txnId);
		$request->setTs($this->getCurrentTS());
		$request->setSwVersion("2.01.01");
		$request->setAppletType("PHPPxAccess");
		
		
		$xml = $request->toXml();
			
	  if (strlen($xml)%8 != 0)
	  {
	    $xml = str_pad($xml, strlen($xml) + 8-strlen($xml)%8); # pad to multiple of 8
	  }
	  #add MAC code JZ2004-8-16
	  $mac = $this->makeMAC($xml,$this->Mac_Key );
	  $msg = $xml.$mac;
	  #$msg = $xml;
	  $enc = $this->encrypt_tripledes($msg, $this->Des_Key); #JZ2004-08-16: Include the MAC code
	  
	  // TT 2009-11-12 Base-64 encoding added
	  $enc_b64 = 'v5' . base64_encode($enc);
	  $enc_b64 = str_replace("/", "_", $enc_b64);
	  $enc_b64 = str_replace("+", "-", $enc_b64);
	  
	  $PxAccess_Redirect = "$this->PxAccess_Url?userid=$this->PxAccess_Userid&request=$enc_b64";

		return $PxAccess_Redirect;
		
	}
		
	#******************************************************************************
	# This function ecrypts data using 3DES via libmcrypt
	#******************************************************************************
	function encrypt_tripledes($data, $key)
	{
	# deprecated libmcrypt 2.2 encryption: use this if you have libmcrypt 2.2.x
	# $result = mcrypt_ecb(MCRYPT_DES, $key, $data, MCRYPT_ENCRYPT);
	# return $result;
	#
	# otherwise use this for libmcrypt 2.4.x and above:
	  $td = mcrypt_module_open('tripledes', '', 'ecb', '');
	  $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
	  mcrypt_generic_init($td, $key, $iv);
	  $result = mcrypt_generic($td, $data);
	  #mcrypt_generic_deinit($td); #Might cause problem in some PHP version
	  return $result;
	}
	
	
	#******************************************************************************
	# This function decrypts data using 3DES via libmcrypt
	#******************************************************************************
	function decrypt_tripledes($data, $key)
	{
	# deprecated libmcrypt 2.2 encryption: use this if you have libmcrypt 2.2.x
	# $result = mcrypt_ecb(MCRYPT_DES, $key, $data, MCRYPT_DECRYPT);
	# return $result;
	#
	# otherwise use this for libmcrypt 2.4.x and above:
	  $td = mcrypt_module_open('tripledes', '', 'ecb', '');
	  $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
	  mcrypt_generic_init($td, $key, $iv);
	  $result = mdecrypt_generic($td, $data);
	  #mcrypt_generic_deinit($td); #Might cause problem in some PHP version
	  return $result;
	}
	
	#JZ2004-08-16
	
	#******************************************************************************
	# Generate and return a message authentication code (MAC) for a string.
	# (Uses ANSI X9.9 procedure.)
	#******************************************************************************
	function makeMAC($msg,$Mackey){
		
	 if (strlen($msg)%8 != 0)
	  {
	  	$extra = 8 - strlen($msg)%8;
	    $msg .= str_repeat(" ", $extra); # pad to multiple of 8
	  }
	  $mac = pack("C*", 0, 0, 0, 0, 0, 0, 0, 0); # start with all zeros
	  #$mac_result = unpack("C*", $mac);
	  
	   for ( $i=0; $i<strlen($msg)/8; $i++)
	  {
	    $msg8 = substr($msg, 8*$i, 8);
	    
		$mac ^= $msg8;
	    $mac = $this->encrypt_des($mac,$Mackey);
	    
	  }
		#$mac = pack("C*", $mac);
	    #$mac_result= encrypt_des($mac, $Mackey);
		
		$mac_result	= unpack("H8", $mac);
		#$mac_result	= $mac_result[""]; #use this function if PHP version before 4.3.4
		#$mac_result	= $mac_result[1]; #use this function if PHP version after 4.3.4
		$mac_result = (version_compare(PHP_VERSION, "4.3.4", ">=")) ? $mac_result[1]: $mac_result[""];

		return $mac_result;
	  
	   
	}
	 
	#******************************************************************************
	# This function ecrypts data using DES via libmcrypt
	# JZ2004-08-16
	#******************************************************************************
	function encrypt_des($data, $key)
	{
	# deprecated libmcrypt 2.2 encryption: use this if you have libmcrypt 2.2.x
	#  $result = mcrypt_ecb(MCRYPT_3DES, $key, $data, MCRYPT_ENCRYPT);
	#  return $result;
	#
	# otherwise use this for libmcrypt 2.4.x and above:
	  $td = mcrypt_module_open('des', '', 'ecb', '');
	  $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
	  mcrypt_generic_init($td, $key, $iv);
	  $result = mcrypt_generic($td, $data);
	  #mcrypt_generic_deinit($td); #Might cause problem in some PHP version
	  mcrypt_module_close($td);
	
	  return $result;
	}


	#JZ2004-08-16
	function getResponse($resp_enc){
		#global $Mac_Key;
	$resp_enc = substr($resp_enc, 2);
	$resp_enc = str_replace("_", "/", $resp_enc);
	$resp_enc = str_replace("-", "+", $resp_enc);
	$enc = base64_decode($resp_enc);
	
		$resp = trim($this->decrypt_tripledes($enc, $this->Des_Key));
		$xml = substr($resp, 0, strlen($resp)-8);
		
	    $mac = substr($resp, -8);
		$checkmac = $this->makeMac($xml, $this->Mac_Key);
		if($mac != $checkmac){
			$xml = "<success>0</success><ResponseText>Response MAC Invalid</ResponseText>";
		}
		
		$pxresp = new PxPayResponse($xml);
		return $pxresp;
	
	}

	
	
	#******************************************************************************
	# Return the current time (GMT/UTC).The return time formatted YYYYMMDDHHMMSS.
	#JZ2004-08-30
	#******************************************************************************
	function getCurrentTS()
	{
	  
	  return gmstrftime("%Y%m%d%H%M%S", time());
	}
	
	

}

#******************************************************************************
# Class for PxPay request messages.
#******************************************************************************
class PxPayRequest extends PxPayMessage
{
	var $s,$q,$r;
	var $c, $e, $d;
	var $v;
	var $C;
	

	#Constructor
 	function PxPayRequest(){
		$this->PxPayMessage();
		
	}
	
	function setAppletType($AppletType){
		$this->e = $AppletType;
	}
	
	function getAppletType(){
		return $this->e;
	}
	
	
	
	function setTs($Ts){
		$this->C = $Ts;
	}
	function setEnableAddBillCard($EnableBillAddCard){
	 $this->v = $EnableBillAddCard;
	}
	
	function getEnableAddBillCard(){
		return $this->v;
	}
	function setInputCurrency($InputCurrency){
		$this->d = $InputCurrency;
	}
	function getInputCurrency(){
		return $this->d;
	}
	function setTxnId($TxnId)
	{
		$this->s = $TxnId;
	}
	function getTxnId(){
		return $this->s;
	}
	
	function setUrlFail($UrlFail){
		$this->q = $UrlFail;
	}
	function getUrlFail(){
		return $this->q;
	}
	function setUrlSuccess($UrlSuccess){
		$this->r = $UrlSuccess;
	}
	function setAmountInput($AmountInput){
		$this->c = sprintf("%9.2f",$AmountInput); 
	}
	
	function getAmountInput(){
		return $this->c;
	}
	function setSwVersion($SwVersion){
		$this->AppletVersion = $SwVersion;
	}
	
	function getSwVersion(){
		return $this->AppletVersion;
	}
	#******************************************************************
	#Data validation 
	#******************************************************************
	function validData(){
		$msg = "";
		if($this->a != "Purchase")
			if($this->a != "Auth")
				if($this->a != "GetCurrRate")
					if($this->a != "Refund")
						if($this->a != "Complete")
							if($this->a != "Order1")
								$msg = "Invalid a[$this->a]<br>";
		
		if(strlen($this->j) > 64)
			$msg = "Invalid MerchantReference [$this->j]<br>";
		
		if(strlen($this->s) > 16)
			$msg = "Invalid TxnId [$this->s]<br>";
		if(strlen($this->k) > 255)
			$msg = "Invalid TxnData1 [$this->k]<br>";
		if(strlen($this->l) > 255)
			$msg = "Invalid TxnData2 [$this->l]<br>";
		if(strlen($this->m) > 255)
			$msg = "Invalid TxnData3 [$this->m]<br>";
			
		if(strlen($this->o) > 255)
			$msg = "Invalid EmailAddress [$this->o]<br>";
			
		if(strlen($this->q) > 255)
			$msg = "Invalid UrlFail [$this->q]<br>";
		if(strlen($this->r) > 255)
			$msg = "Invalid UrlSuccess [$this->r]<br>";
		if(strlen($this->t) > 32)
			$msg = "Invalid BillingId [$this->t]<br>";
		if(strlen($this->u) > 16)
			$msg = "Invalid DpsBillingId [$this->u]<br>";
			
		if ($msg != "") {
		    trigger_error($msg,E_USER_ERROR);
			return false;
		}
		return true;
	}

}

#******************************************************************************
# Abstract base class for PxPay messages.
# These are messages with certain defined elements,  which can be serialized to XML.

#******************************************************************************
class PxPayMessage {
    var $a;
  	var $k;
  	var $l;
  	var $m;
  	var $j;
  	var $o;
  	var $t;
  	var $u;
    var $x;
    var $y;
	
	function PxPayMessage(){
	
	}
	function setDpsTxnRef($DpsTxnRef){
		$this->x = $DpsTxnRef;
	}
	
	function getDpsTxnRef(){
		return $this->x;
	}
	
	function setDpsBillingId($DpsBillingId){
		$this->u = $DpsBillingId;
	}
	
	function getDpsBillingId(){
		return $this->u;
	}
	function setBillingId($BillingId){
		$this->t = $BillingId;
	}
	
	function getBillingId(){
		return $this->t;
	}
	function setTxnType($TxnType){
		$this->a = $TxnType;
	}
	function getTxnType(){
		return $this->a;
	}
	function setMerchantReference($MerchantReference){
		$this->j = $MerchantReference;
	}
	
	function getMerchantReference(){
		return $this->j;
	}
	function setEmailAddress($EmailAddress){
		$this->o = $EmailAddress;
		
	}
	
	function getEmailAddress(){
		return $this->o;
	}
	
	function setTxnData1($TxnData1){
		$this->k = $TxnData1;
		
	}
	function getTxnData1(){
		return $this->k;
	}
	function setTxnData2($TxnData2){
		$this->l = $TxnData2;
		
	}
	function getTxnData2(){
		return $this->l;
	}
	
	function getTxnData3(){
		return $this->m;
	}
	function setTxnData3($TxnData3){
		$this->m = $TxnData3;
		
	}
  function getCardNumber(){
		return $this->z;
	}
	function setCardNumber($CardNumber){
		$this->z = $CardNumber;		
	}
  function getDateExpiry(){
		return $this->A;
	}
	function setDateExpiry($DateExpiry){
		$this->A = $DateExpiry;		
	}
  function getCardHolderName(){
		return $this->y;
	}
	function setCardHolderName($CardHolderName){
		$this->y = $CardHolderName;		
	}

	function toXml(){
		$arr = get_object_vars($this);
		$root = strtolower(get_class($this));
		if($root == "pxpaypequest")
			$root = "Request";
		elseif ($root == "pxpaypesponse")
			$root = "Response";
		else
			$root ="Request";
			
		$xml  = "<$root>";
    	while (list($prop, $val) = each($arr))
        	$xml .= "<$prop>$val</$prop>" ;

		$xml .= "</$root>";
		return $xml;
	}
	
	
}

#******************************************************************************
# Class for PxPay response messages.
#******************************************************************************

class PxPayResponse extends PxPayMessage
{
	var $h;
  var $E;
  var $F;
  var $J;
  var $f;
  var $b;
  var $n;
  var $d;
  var $UserId;
  var $i;  
  var $p;
  var $z;
  var $A;
  var $y;
  var $C;
  var $s;  
  
	function PxPayResponse($xml){

		$msg = new MifMessage($xml);
		$this->PxPayMessage();
			
		$TS = $msg->get_element_text("C");
		$expiryTS = $this->getExpiredTS();
		if(strcmp($TS, $expiryTS) < 0 ){
			$this->Success = "0";
			$this->i = "Response TS out of range";
			return;
		}
		
	
		$this->setBillingId($msg->get_element_text("t"));
		$this->setDpsBillingId($msg->get_element_text("u"));
		$this->setEmailAddress($msg->get_element_text("o"));
		$this->setMerchantReference($msg->get_element_text("j"));
		$this->setTxnData1($msg->get_element_text("k"));
		$this->setTxnData2($msg->get_element_text("l"));
		$this->setTxnData3($msg->get_element_text("m"));
		$this->setTxnType($msg->get_element_text("a"));
		
		$this->h = $msg->get_element_text("h");
		$this->E = $msg->get_element_text("E");
		$this->F = $msg->get_element_text("F");
		$this->J = $msg->get_element_text("J");
		$this->f = $msg->get_element_text("f");
		$this->b = $msg->get_element_text("b");
		$this->n = $msg->get_element_text("n");
		$this->b = $msg->get_element_text("b");
		$this->UserId = $msg->get_element_text("UserId");
		$this->i = $msg->get_element_text("i");
		$this->x = $msg->get_element_text("x");
		$this->s = $msg->get_element_text("s");
    
    $this->z = $msg->get_element_text("z");
    $this->A = $msg->get_element_text("A");
    $this->y = $msg->get_element_text("y");
    
    
		$this->TS = $msg->get_element_text("C");
	}
	function getTS(){
		return $this->C;
	}
	function getMerchantTxnId(){
		return $this->s;
	}
	
	function getResponseText(){
		return $this->i;
	}
	function getUserId(){
		return $this->UserId;
	}
	function getCurrencyInput(){
		return $this->d;
	}
	function getCardName(){
		return $this->n;
	}
	function getCurrencySettlement(){
		$this->b;
	}
	function getAmountSettlement(){
		return $this->f;
	}
	function getSuccess(){
		return $this->h;
	}
	function getStatusRequired(){
		return $this->F;
	}
	function getRetry(){
		return $this->E;
	}
	function getAuthCode(){
		return $this->J;
	}
	#******************************************************************************
	# Return the expired time, i.e. 2 days ago (GMT/UTC).
	#JZ2004-08-30
	#******************************************************************************
	function  getExpiredTS()
	{
	  
	  return gmstrftime("%Y%m%d%H%M%S", time()- 2 * 24 * 60 * 60);
	}
	
}
?>