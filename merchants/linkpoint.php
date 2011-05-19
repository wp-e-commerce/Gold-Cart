<?php
if(!is_callable('get_option')) {
  // This is here to stop error messages on servers with Zend Accelerator, it includes all files before get_option is declared
  // then evidently includes them again, otherwise this code would break these modules
  return;
  exit("Something strange is happening, and \"return\" is not breaking out of a file.");
}
global $gateway_checkout_form_fields;
$nzshpcrt_gateways[$num]['name'] = 'LinkPoint';
$nzshpcrt_gateways[$num]['internalname'] = 'linkpoint';
$nzshpcrt_gateways[$num]['function'] = 'gateway_linkpoint';
$nzshpcrt_gateways[$num]['form'] = "form_linkpoint";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_linkpoint";
$nzshpcrt_gateways[$num]['payment_type'] = "credit_card";

if(in_array('linkpoint',(array)get_option('custom_gateway_options'))) {

	$gateway_checkout_form_fields[$nzshpcrt_gateways[$num]['internalname']] = "
<tr>
<td> Credit Card Number * </td>
<td>
<input type='text' size='4' value='' maxlength='4' name='card_number1' /> - <input type='text' size='4' value='' maxlength='4' name='card_number2' /> - <input type='text' size='4' value='' maxlength='4' name='card_number3' /> - <input type='text' size='4' value='' maxlength='4' name='card_number4' />
</td>
</tr>
<tr>
<td> Credit Card Expiration * </td>
<td>
<input type='text' size='2' value='' maxlength='2' name='expiry[month]' />/<input type='text' size='2'  maxlength='2' value='' name='expiry[year]' />
</td>
</tr> 
<td> CVV Code * </td>
<td>
<input type='text' size='4' value='' maxlength='4' name='cvmvalue' /></td>
</tr> 
";
}
function gateway_linkpoint($seperator, $sessionid) {
	global $wpdb;
	$transact_url = get_option('transact_url');
	$purchase_log_sql = "SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid`= '".$sessionid."' LIMIT 1";
	$purchase_log = $wpdb->get_results($purchase_log_sql,ARRAY_A) ;
	$purchase_log=$purchase_log[0];
		//Get provided user info
		
	//Here starts most of the changes implemented into linkpoint for passing userinfo
	$usersql = "SELECT 
		`".WPSC_TABLE_SUBMITED_FORM_DATA."`.value, 
		`".WPSC_TABLE_CHECKOUT_FORMS."`.`name`, 
		`".WPSC_TABLE_CHECKOUT_FORMS."`.`unique_name` FROM 
		`".WPSC_TABLE_CHECKOUT_FORMS."` LEFT JOIN 
		`".WPSC_TABLE_SUBMITED_FORM_DATA."` ON 
		`".WPSC_TABLE_CHECKOUT_FORMS."`.id = 
		`".WPSC_TABLE_SUBMITED_FORM_DATA."`.`form_id` WHERE  
		`".WPSC_TABLE_SUBMITED_FORM_DATA."`.`log_id`=".$purchase_log['id']." ORDER BY `".WPSC_TABLE_CHECKOUT_FORMS."`.`checkout_order`";
		
	$userinfo = $wpdb->get_results($usersql, ARRAY_A);
	

	foreach((array)$userinfo as $key => $value){
		if(($value['unique_name']=='billingfirstname') && $value['value'] != ''){
			$myorder1['FIRSTNAME']	= $value['value'];
		}
		if(($value['unique_name']=='billinglastname') && $value['value'] != ''){
			$myorder1['LASTNAME']	= $value['value'];
		}
		if(($value['unique_name']=='billingemail') && $value['value'] != ''){
			$myorder1['EMAIL']	= $value['value'];
		}
		if(($value['unique_name']=='billingphone') && $value['value'] != ''){
			$myorder1['PHONENUM']	= $value['value'];
		}
		if(($value['unique_name']=='billingaddress') && $value['value'] != ''){
			$myorder1['STREET']	= $value['value'];
		}
		if(($value['unique_name']=='billingcity') && $value['value'] != ''){
			$myorder1['CITY']	= $value['value'];
		}
		if(($value['unique_name']=='billingstate') && $value['value'] != ''){
			$sql = "SELECT `code` FROM `".WPSC_TABLE_REGION_TAX."` WHERE `id` ='".$value['value']."' LIMIT 1";
			$myorder1['STATE'] = $wpdb->get_var($sql);
		}else{
			
		//	$data['STATE']='CA';
		}
		if(($value['unique_name']=='billingcountry') && $value['value'] != ''){
			$value['value'] = maybe_unserialize($value['value']);
			if($value['value'][0] == 'UK'){
				$myorder1['COUNTRYCODE'] = 'GB';
			}else{
				$myorder1['COUNTRYCODE']	= $value['value'][0];
			}
			if(is_numeric($value['value'][1])){
				$sql = "SELECT `code` FROM `".WPSC_TABLE_REGION_TAX."` WHERE `id` ='".$value['value'][1]."' LIMIT 1";
				$myorder1['STATE'] = $wpdb->get_var($sql);
			}
		}		
		if(($value['unique_name']=='billingpostcode') && $value['value'] != ''){
			$myorder1['ZIP']	= $value['value'];
		}
		if((($value['unique_name']=='shippingfirstname') && $value['value'] != '')){
			$myorder1['SHIPTONAME1']	= $value['value'];
		}else{
		//	$myorder1['SHIPTONAME1']	= '';
		}
		if((($value['unique_name']=='shippinglastname') && $value['value'] != '')){
			$myorder1['SHIPTONAME2']	= $value['value'];
		}else{
		//	$myorder1['SHIPTONAME2']	= '';
		}
		if(($value['unique_name']=='shippingaddress') && $value['value'] != ''){
			$myorder1['SHIPTOSTREET']	= $value['value'];
		}	
		if(($value['unique_name']=='shippingcity') && $value['value'] != ''){
			$myorder1['SHIPTOCITY']	= $value['value'];
		}	
			//$data['SHIPTOCITY'] = 'CA';
		if(($value['unique_name']=='shippingstate') && $value['value'] != ''){
		//	$data['SHIPTOSTATE'] = $value['value'];
			$sql = "SELECT `code` FROM `".WPSC_TABLE_REGION_TAX."` WHERE `id` ='".$value['value']."' LIMIT 1";
			$myorder1['SHIPTOSTATE'] = $wpdb->get_var($sql);
		}else{
		}	
		if(($value['unique_name']=='shippingcountry') && $value['value'] != ''){
			$value['value'] = maybe_unserialize($value['value']);
			if(is_array($value['value'])){
			if($value['value'][0] == 'UK'){
				$myorder1['SHIPTOCOUNTRY'] = 'GB';
			}else{
				$myorder1['SHIPTOCOUNTRY']	= $value['value'][0];
			}
			if(is_numeric($value['value'][1])){
				$sql = "SELECT `code` FROM `".WPSC_TABLE_REGION_TAX."` WHERE `id` ='".$value['value'][1]."' LIMIT 1";
				$myorder1['SHIPTOSTATE'] = $wpdb->get_var($sql);
			}
			}else{
				$myorder1['SHIPTOCOUNTRY']	= $value['value'];
			}
			
		}	
		if(($value['unique_name']=='shippingpostcode') && $value['value'] != ''){
			$myorder1['SHIPTOZIP']	= $value['value'];
		}	
	}

//Here ends most of the changes implemented into linkpoint
	$store = get_option('linkpoint_store_number');
	$linkpoint = new lphp();
	$myorder["host"] = "secure.linkpt.net";
	$myorder["port"] = "1129";
	$myorder["keyfile"] = WPSC_GOLD_FILE_PATH."/merchants/linkpointpem/".$store.".pem";
	$myorder["configfile"] = $store;
	
	//	# CREDIT CARD INFO
	//if (get_option('linkpoint_test')=='0') {
		$myorder["ordertype"] = "SALE";
		$myorder["cardnumber"] = $_POST['card_number1']."-".$_POST['card_number2']."-".$_POST['card_number3']."-".$_POST['card_number4'];
/* see note below ( submit_linkpoint() )on using the linkpoint test account this way
	} else {
		$myorder["result"] = "GOOD";
		$myorder["cardnumber"] = "4111-1111-1111-1111";
		$myorder["cardexpmonth"] = "01";
		$myorder["cardexpyear"] = "11";
		$myorder["cvmvalue"] = "111";
	}
*/
	$myorder["cardexpmonth"] = $_POST['expiry']['month'];
	$myorder["cardexpyear"] = $_POST['expiry']['year'];
	$myorder["cvmvalue"] = $_POST['cvmvalue'];	
	
//	# BILLING INFO
	$myorder["name"]     = $myorder1['FIRSTNAME'].' '.$myorder1['LASTNAME'];
	//	$myorder["billingcompany"]  = $_POST["company"];
	$myorder["address"] = $myorder1['STREET'];
//	$myorder["address2"] = ' ';//$_POST["address2"];
	$myorder["city"]     = $myorder1['CITY'];
	$myorder["state"]    = $myorder1['STATE'];
	$myorder["country"]  = $myorder1['COUNTRYCODE'];
	$myorder["phone"]    = $myorder1['PHONENUM'];
	$myorder["email"]    = $myorder1['EMAIL'];
	//	$myorder["addrnum"]  = $_POST["addrnum"];
	$myorder["zip"] = $myorder1['ZIP'];

//	# SHIPPING INFO

	$myorder["sname"]     = $myorder1['SHIPTONAME1'].' '.$myorder1['SHIPTONAME2'];
	$myorder["saddress1"] = $myorder1['SHIPTOSTREET'];
	$myorder["saddress2"] = ' ';//$_POST["saddress2"];
	$myorder["scity"]     = $myorder1['SHIPTOCITY'];
	$myorder["sstate"]    = $myorder1['SHIPTOSTATE'] ;
	$myorder["szip"]      = $myorder1['SHIPTOZIP'];
	$myorder["scountry"]  = $myorder1['SHIPTOCOUNTRY'];

	
//	# ORDER INFO
	$myorder["chargetotal"] = $purchase_log['totalprice'];
//	exit('<pre>'.print_r($myorder,true).'</pre>');
	$responce = $linkpoint->curl_process($myorder);
//	exit('<pre>'.print_r($responce,true).'</pre>');
	if($responce["r_approved"]!="APPROVED"){
		$message .= "<h3>Please Check the Payment Results</h3>";
		$message .= "Your transaction was not successful."."<br /><br />";
		//$message .= "<a href=".get_option('shopping_cart_url').">Click here to go back to checkout page.</a>";
		$_SESSION['wpsc_checkout_misc_error_messages'][] = $message;
		//header("Location:".get_option('transact_url').$seperator."eway=1&result=".$sessionid."&message=1");
	}else{
		$wpdb->query("UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `processed`='3' WHERE `sessionid`='".$sessionid."' LIMIT 1");
		header("Location: ".$transact_url.$seperator."sessionid=".$sessionid);
		//transaction_results($sessionid, true);
	}
	
	exit();
}

function submit_linkpoint() {

/* Doing a test account with link point this way is silly because we send linkpoint the test card number that is always used for accepted payment, and there is no way to test a faild payment. Link point have two card numbers which you can use with your live account to test transactions one card number will generate a faild response and one an accepted one. Also some people were having issues where this option was not re updated to change the option to use test account back to no. The foreach is redundent but this is getting rewritten to use new api so for now it will stay like this */
	
	$options = array(
		'store_number',
		//'test',
	);
	foreach ( $options as $option ) {
		$field = "linkpoint_{$option}";
		if ( ! empty( $_POST[$field] ) )
			update_option( $field, $_POST[$field] );
	}
	return true;
}

function form_linkpoint() {
	
	return "
		<tr>
			<td>
				Store Number
			</td>
			<td>
				<input type='text' size='40' value='". get_option('linkpoint_store_number')."' name='linkpoint_store_number' />
			</td>
		</tr>
		<tr>
			<td>
				Test Environment
			</td>
			<td>
				<input type='radio' $linkpoint_test1 value='1' name='linkpoint_test' /> Yes
				<input type='radio' $linkpoint_test2 value='0' name='linkpoint_test' /> No
			</td>
		</tr>";
}


		/* lphp.php  LINKPOINT PHP MODULE */
	
		/* A php interlocutor CLASS for
		LinkPoint: LINKPOINT LSGS API using
		libcurl, liblphp.so and liblpssl.so
		v3.0.005  20 Aug. 2003  smoffet */
		
		
# Copyright 2003 LinkPoint International, Inc. All Rights Reserved.
# 
# This software is the proprietary information of LinkPoint International, Inc.
# Use is subject to license terms.


	### YOU REALLY DO NOT NEED TO EDIT THIS FILE! ###


class lphp {
	var $debugging;
	###########################################
	#
	#	F U N C T I O N    p r o c e s s ( ) 
	#
	#	process a hash table or XML string 
	#	using LIBLPHP.SO and LIBLPSSL.SO
	#
	###########################################
	function process($data) {
		$using_xml = 0;
		$webspace = 1;

		if (isset($data["webspace"])) {
			if ($data["webspace"] == "false") // if explicitly set to false, don't use html output
				$webspace = 0;
		}

		if ( isset($data["debugging"]) || isset($data["debug"]) ) {
			if ($data["debugging"] == "true" || $data["debug"] == "true"  ) {
				$this->debugging = 1;
				
				# print out incoming hash
				if ($webspace) {
					echo "at process, incoming data: <br>";
					while (list($key, $value) = each($data))
						 echo htmlspecialchars($key) . " = " . htmlspecialchars($value) . "<BR>\n";
				} else {     // don't use html output
					echo "at process, incoming data: \n";
					while (list($key, $value) = each($data))
						echo "$key = $value\n"; 
				}
				reset($data); 
			}
		}
		if (isset($data["xml"])){
			$using_xml = 1;
			$xml = $data["xml"];
		} else {
			//  otherwise convert incoming hash to XML string
			$xml = $this->buildXML($data);
		}

		// then set up transaction variables
		$key	= $data["keyfile"];
		$host	= $data["host"];
		$port	= $data["port"];


		# FOR PERFORMANCE, Use the 'extensions' statement in your php.ini to load
		# this library at PHP startup, then comment out the next seven lines 

		// load library
		if (!extension_loaded('liblphp')) {
			if (!dl('liblphp.so')) {
				exit("cannot load liblphp.so, bye\n");
			}
		}

		if ($this->debugging) {
			if ($webspace)
				echo "<br>sending xml string:<br>" . htmlspecialchars($xml) . "<br><br>";    
			else
				echo "\nsending xml string:\n$xml\n\n";
		}

		// send transaction to LSGS
		$retstg = send_stg($xml, $key, $host, $port);


		if (strlen($retstg) < 4)
			exit ("cannot connect to lsgs, exiting");
		
		if ($this->debugging) {
			if ($this->webspace)	// we're web space
				echo "<br>server responds:<br>" . htmlspecialchars($retstg) . "<br><br>";
			else						// not html output
				echo "\nserver responds:\n $retstg\n\n";
		}
	
		if ($using_xml != 1) {
			// convert xml response back to hash
			$retarr = $this->decodeXML($retstg);
			
			// and send it back to caller
			return ($retarr);
		} else {
			// send server response back
			return $retstg;
		}
	}


	#####################################################
	#
	#	F U N C T I O N    c u r l _ p r o c e s s ( ) 
	#
	#	process hash table or xml string table using 
	#	curl, either with PHP built-in curl methods 
	#	or binary executable curl
	#
	#####################################################
	
	function curl_process($data) {
		$using_xml = 0;
		$webspace = 1;

		if (isset($data["webspace"])) {
			if ($data["webspace"] == "false") // if explicitly set to false, don't use html output
				$webspace = 0;
		}

		if (isset($data["debugging"]) || isset($data["debug"]) ) {
			if ($data["debugging"] == "true" || $data["debug"] == "true" ) {
				$this->debugging = 1;
				# print out incoming hash
				if ($webspace) {
					echo "at curl_process, incoming data: <br>";

					while (list($key, $value) = each($data))
						 echo htmlspecialchars($key) . " = " . htmlspecialchars($value) . "<BR>\n";
				} else {
					echo "at curl_process, incoming data: \n";
					
					while (list($key, $value) = each($data))
						echo "$key = $value\n";
				}
				reset($data); 
			}
		}
		if (isset($data["xml"])) {
			$using_xml = 1;
			$xml = $data["xml"];
		} else {
			// otherwise convert incoming hash to XML string
			$xml = $this->buildXML($data);
		}

		if ($this->debugging) {
			if ($webspace)
				echo "<br>sending xml string:<br>" . htmlspecialchars($xml) . "<br><br>";    
			else
				echo "\nsending xml string:\n$xml\n\n";
		}
		// set up transaction variables
		$key = $data["keyfile"];
		$port = $data["port"];
		$host = "https://".$data["host"].":".$port."/LSGSXML";
		if (isset($data["cbin"])) {
			if ($data["cbin"] == "true") {
				if (isset($data["cpath"]))
					$cpath = $data["cpath"];
						
				else {
					if (getenv("OS") == "Windows_NT")
						$cpath = "c:\\curl\\curl.exe";
					else
						$cpath = "/usr/bin/curl";
				}

				// look for $cargs variable, otherwise use default curl arguments
				if (isset($data["cargs"]))
					$args = $data["cargs"];
				else
					$args = "-m 300 -s -S";		// default curl args; 5 min. timeout
				# TRANSACT #

				if (getenv("OS") == "Windows_NT") {
					if ($this->debugging)
						$result = exec ("$cpath -v -d \"$xml\" -E $key  -k $host", $retarr, $retnum);
					else
						$result = exec ("$cpath -d \"$xml\" -E $key  -k $host", $retarr, $retnum);
				} else {	//*nix string
				
					if ($this->debugging)
						$result = exec ("'$cpath' $args -v -E '$key' -d '$xml' '$host'", $retarr, $retnum);
					else
						$result = exec ("'$cpath' $args -E '$key' -d '$xml' '$host'", $retarr, $retnum);
				}

				# EVALUATE RESPONSE #

				if (strlen($result) < 2) {
					$result = "<r_approved>FAILURE</r_approved><r_error>Could not connect.</r_error>"; 
					return $result;
				}

				if ($this->debugging) {
					if ($this->webspace)
						echo "<br>server responds:<br>" . htmlspecialchars($result) . "<br><br>";
					else						// non html output
						echo "\nserver responds:\n $result\n\n";
				}

				if ($using_xml == 1) {
					// return xml string straight from server
					return ($result);
				} else {
					// convert xml response back to hash
					$retarr = $this->decodeXML($result);
					
					// and send it back to caller. Done.
					return ($retarr);
				}
			}
		} else {	// using BUILT-IN PHP curl methods
		
			$ch = curl_init ();
			curl_setopt ($ch, CURLOPT_URL,$host);
			curl_setopt ($ch, CURLOPT_POST, 1);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $xml);
			if (is_file($key)) {
				curl_setopt ($ch, CURLOPT_SSLCERT, $key);
			}
			curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);

			if ($this->debugging)
				curl_setopt ($ch, CURLOPT_VERBOSE, 1);

			#  use curl to send the xml SSL string
			$result = curl_exec ($ch);
			
			curl_close($ch);

			if (strlen($result) < 2) {
				$result = "<r_approved>FAILURE</r_approved><r_error>Could not connect.</r_error>"; 
				return $result;
			}

			if ($this->debugging) {
				if ($webspace)	// html-friendly output
					echo "<br>server responds:<br>" . htmlspecialchars($result) . "<br><br>";
				else
					echo "\nserver responds:\n $result\n\n";
			}

			if ($using_xml) {
				# send xml response back
				return $result;
			} else {
				#convert xml response to hash
				$retarr = $this->decodeXML($result);
				
				# and send it back
				return ($retarr);
			}
		}
	}
	#############################################	
	#
	#	F U N C T I O N   d e c o d e X M L ( ) 
	#
	#	converts the LSGS response xml string	
	#	to a hash of name-value pairs
	#
	#############################################

	function decodeXML($xmlstg) {
		preg_match_all ("/<(.*?)>(.*?)\</", $xmlstg, $out, PREG_SET_ORDER);
		$n = 0;
		while (isset($out[$n])) {
			$retarr[$out[$n][1]] = strip_tags($out[$n][0]);
			$n++;
		}
		return $retarr;
	}


	############################################
	#
	#	F U N C T I O N    b u i l d X M L ( ) 
	#
	#	converts a hash of name-value pairs
	#	to the correct XML format for LSGS
	#
	############################################

	function buildXML($pdata)
	{

//		while (list($key, $value) = each($pdata))
//			 echo htmlspecialchars($key) . " = " . htmlspecialchars($value) . "<br>\n";


		### ORDEROPTIONS NODE ###
		$xml = "<order><orderoptions>";

		if (isset($pdata["ordertype"]))
			$xml .= "<ordertype>" . $pdata["ordertype"] . "</ordertype>";
		if (isset($pdata["result"]))
			$xml .= "<result>" . $pdata["result"] . "</result>";
		$xml .= "</orderoptions>";
		### CREDITCARD NODE ###
		$xml .= "<creditcard>";
		if (isset($pdata["cardnumber"]))
			$xml .= "<cardnumber>" . $pdata["cardnumber"] . "</cardnumber>";

		if (isset($pdata["cardexpmonth"]))
			$xml .= "<cardexpmonth>" . $pdata["cardexpmonth"] . "</cardexpmonth>";

		if (isset($pdata["cardexpyear"]))
			$xml .= "<cardexpyear>" . $pdata["cardexpyear"] . "</cardexpyear>";

		if (isset($pdata["cvmvalue"]))
			$xml .= "<cvmvalue>" . $pdata["cvmvalue"] . "</cvmvalue>";

		if (isset($pdata["cvmindicator"]))
			$xml .= "<cvmindicator>" . $pdata["cvmindicator"] . "</cvmindicator>";

		if (isset($pdata["track"]))
			$xml .= "<track>" . $pdata["track"] . "</track>";

		$xml .= "</creditcard>";


		### BILLING NODE ###
		$xml .= "<billing>";

		if (isset($pdata["name"]))
			$xml .= "<name>" . $pdata["name"] . "</name>";

		if (isset($pdata["company"]))
			$xml .= "<company>" . $pdata["company"] . "</company>";

		if (isset($pdata["address1"]))
			$xml .= "<address1>" . $pdata["address1"] . "</address1>";
		elseif (isset($pdata["address"]))
			$xml .= "<address1>" . $pdata["address"] . "</address1>";

		if (isset($pdata["address2"]))
			$xml .= "<address2>" . $pdata["address2"] . "</address2>";

		if (isset($pdata["city"]))
			$xml .= "<city>" . $pdata["city"] . "</city>";
			
		if (isset($pdata["state"]))
			$xml .= "<state>" . $pdata["state"] . "</state>";
			
		if (isset($pdata["zip"]))
			$xml .= "<zip>" . $pdata["zip"] . "</zip>";

		if (isset($pdata["country"]))
			$xml .= "<country>" . $pdata["country"] . "</country>";

		if (isset($pdata["userid"]))
			$xml .= "<userid>" . $pdata["userid"] . "</userid>";

		if (isset($pdata["email"]))
			$xml .= "<email>" . $pdata["email"] . "</email>";

		if (isset($pdata["phone"]))
			$xml .= "<phone>" . $pdata["phone"] . "</phone>";

		if (isset($pdata["fax"]))
			$xml .= "<fax>" . $pdata["fax"] . "</fax>";

		if (isset($pdata["addrnum"]))
			$xml .= "<addrnum>" . $pdata["addrnum"] . "</addrnum>";

		$xml .= "</billing>";

		
		## SHIPPING NODE ##
		$xml .= "<shipping>";

		if (isset($pdata["sname"]))
			$xml .= "<name>" . $pdata["sname"] . "</name>";

		if (isset($pdata["saddress1"]))
			$xml .= "<address1>" . $pdata["saddress1"] . "</address1>";

		if (isset($pdata["saddress2"]))
			$xml .= "<address2>" . $pdata["saddress2"] . "</address2>";

		if (isset($pdata["scity"]))
			$xml .= "<city>" . $pdata["scity"] . "</city>";

		if (isset($pdata["sstate"]))
			$xml .= "<state>" . $pdata["sstate"] . "</state>";
		elseif (isset($pdata["state"]))
			$xml .= "<state>" . $pdata["sstate"] . "</state>";

		if (isset($pdata["szip"]))
			$xml .= "<zip>" . $pdata["szip"] . "</zip>";
		elseif (isset($pdata["sip"]))
			$xml .= "<zip>" . $pdata["zip"] . "</zip>";

		if (isset($pdata["scountry"]))
			$xml .= "<country>" . $pdata["scountry"] . "</country>";

		if (isset($pdata["scarrier"]))
			$xml .= "<carrier>" . $pdata["scarrier"] . "</carrier>";

		if (isset($pdata["sitems"]))
			$xml .= "<items>" . $pdata["sitems"] . "</items>";

		if (isset($pdata["sweight"]))
			$xml .= "<weight>" . $pdata["sweight"] . "</weight>";

		if (isset($pdata["stotal"]))
			$xml .= "<total>" . $pdata["stotal"] . "</total>";

		$xml .= "</shipping>";


		### TRANSACTIONDETAILS NODE ###
		$xml .= "<transactiondetails>";

		if (isset($pdata["oid"]))
			$xml .= "<oid>" . $pdata["oid"] . "</oid>";

		if (isset($pdata["ponumber"]))
			$xml .= "<ponumber>" . $pdata["ponumber"] . "</ponumber>";

		if (isset($pdata["recurring"]))
			$xml .= "<recurring>" . $pdata["recurring"] . "</recurring>";

		if (isset($pdata["taxexempt"]))
			$xml .= "<taxexempt>" . $pdata["taxexempt"] . "</taxexempt>";

		if (isset($pdata["terminaltype"]))
			$xml .= "<terminaltype>" . $pdata["terminaltype"] . "</terminaltype>";

		if (isset($pdata["ip"]))
			$xml .= "<ip>" . $pdata["ip"] . "</ip>";

		if (isset($pdata["reference_number"]))
			$xml .= "<reference_number>" . $pdata["reference_number"] . "</reference_number>";

		if (isset($pdata["transactionorigin"]))
			$xml .= "<transactionorigin>" . $pdata["transactionorigin"] . "</transactionorigin>";

		if (isset($pdata["tdate"]))
			$xml .= "<tdate>" . $pdata["tdate"] . "</tdate>";

		$xml .= "</transactiondetails>";


		### MERCHANTINFO NODE ###
		$xml .= "<merchantinfo>";

		if (isset($pdata["configfile"]))
			$xml .= "<configfile>" . $pdata["configfile"] . "</configfile>";

		if (isset($pdata["keyfile"]))
			$xml .= "<keyfile>" . $pdata["keyfile"] . "</keyfile>";

		if (isset($pdata["host"]))
			$xml .= "<host>" . $pdata["host"] . "</host>";

		if (isset($pdata["port"]))
			$xml .= "<port>" . $pdata["port"] . "</port>";

		if (isset($pdata["appname"]))
			$xml .= "<appname>" . $pdata["appname"] . "</appname>";

		$xml .= "</merchantinfo>";



		### PAYMENT NODE ###
		$xml .= "<payment>";

		if (isset($pdata["chargetotal"]))
			$xml .= "<chargetotal>" . $pdata["chargetotal"] . "</chargetotal>";

		if (isset($pdata["tax"]))
			$xml .= "<tax>" . $pdata["tax"] . "</tax>";

		if (isset($pdata["vattax"]))
			$xml .= "<vattax>" . $pdata["vattax"] . "</vattax>";

		if (isset($pdata["shipping"]))
			$xml .= "<shipping>" . $pdata["shipping"] . "</shipping>";

		if (isset($pdata["subtotal"]))
			$xml .= "<subtotal>" . $pdata["subtotal"] . "</subtotal>";

		$xml .= "</payment>";


		### CHECK NODE ### 


		if (isset($pdata["voidcheck"]))
		{
			$xml .= "<telecheck><void>1</void></telecheck>";
		}
		elseif (isset($pdata["routing"]))
		{
			$xml .= "<telecheck>";
			$xml .= "<routing>" . $pdata["routing"] . "</routing>";

			if (isset($pdata["account"]))
				$xml .= "<account>" . $pdata["account"] . "</account>";

			if (isset($pdata["bankname"]))
				$xml .= "<bankname>" . $pdata["bankname"] . "</bankname>";
	
			if (isset($pdata["bankstate"]))
				$xml .= "<bankstate>" . $pdata["bankstate"] . "</bankstate>";

			if (isset($pdata["ssn"]))
				$xml .= "<ssn>" . $pdata["ssn"] . "</ssn>";

			if (isset($pdata["dl"]))
				$xml .= "<dl>" . $pdata["dl"] . "</dl>";

			if (isset($pdata["dlstate"]))
				$xml .= "<dlstate>" . $pdata["dlstate"] . "</dlstate>";

			if (isset($pdata["checknumber"]))
				$xml .= "<checknumber>" . $pdata["checknumber"] . "</checknumber>";
				
			if (isset($pdata["accounttype"]))
				$xml .= "<accounttype>" . $pdata["accounttype"] . "</accounttype>";

			$xml .= "</telecheck>";
		}


		### PERIODIC NODE ###

		if (isset($pdata["startdate"]))
		{
			$xml .= "<periodic>";

			$xml .= "<startdate>" . $pdata["startdate"] . "</startdate>";

			if (isset($pdata["installments"]))
				$xml .= "<installments>" . $pdata["installments"] . "</installments>";

			if (isset($pdata["threshold"]))
						$xml .= "<threshold>" . $pdata["threshold"] . "</threshold>";

			if (isset($pdata["periodicity"]))
						$xml .= "<periodicity>" . $pdata["periodicity"] . "</periodicity>";

			if (isset($pdata["pbcomments"]))
						$xml .= "<comments>" . $pdata["pbcomments"] . "</comments>";

			if (isset($pdata["action"]))
				$xml .= "<action>" . $pdata["action"] . "</action>";

			$xml .= "</periodic>";
		}


		### NOTES NODE ###

		if (isset($pdata["comments"]) || isset($pdata["referred"]))
		{
			$xml .= "<notes>";

			if (isset($pdata["comments"]))
				$xml .= "<comments>" . $pdata["comments"] . "</comments>";

			if (isset($pdata["referred"]))
				$xml .= "<referred>" . $pdata["referred"] . "</referred>";

			$xml .= "</notes>";
		}

		### ITEMS AND OPTIONS NODES ###
	
		if ($this->debugging)	// make it easy to see
		{						// LSGS doesn't mind whitespace
			reset($pdata);

			while (list ($key, $val) = each ($pdata))
			{
				if (is_array($val))
				{
					$otag = 0;
					$ostag = 0;
					$items_array = $val;
					$xml .= "\n<items>\n";

					while(list($key1, $val1) = each ($items_array))
					{
						$xml .= "\t<item>\n";

						while (list($key2, $val2) = each ($val1))
						{
							if (!is_array($val2))
								$xml .= "\t\t<$key2>$val2</$key2>\n";

							else
							{
								if (!$ostag)
								{
									$xml .= "\t\t<options>\n";
									$ostag = 1;
								}

								$xml .= "\t\t\t<option>\n";
								$otag = 1;
								
								while (list($key3, $val3) = each ($val2))
									$xml .= "\t\t\t\t<$key3>$val3</$key3>\n";
							}

							if ($otag)
							{
								$xml .= "\t\t\t</option>\n";
								$otag = 0;
							}
						}

						if ($ostag)
						{
							$xml .= "\t\t</options>\n";
							$ostag = 0;
						}
					$xml .= "\t</item>\n";
					}
				$xml .= "</items>\n";
				}
			}
		}

		else // !debugging
		{
			while (list ($key, $val) = each ($pdata))
			{
				if (is_array($val))
				{
					$otag = 0;
					$ostag = 0;
					$items_array = $val;
					$xml .= "<items>";

					while(list($key1, $val1) = each ($items_array))
					{
						$xml .= "<item>";

						while (list($key2, $val2) = each ($val1))
						{
							if (!is_array($val2))
								$xml .= "<$key2>$val2</$key2>";
							else
							{
								if (!$ostag)
								{
									$xml .= "<options>";
									$ostag = 1;
								}

								$xml .= "<option>";
								$otag = 1;
								
								while (list($key3, $val3) = each ($val2))
									$xml .= "<$key3>$val3</$key3>";
							}

							if ($otag)
							{
								$xml .= "</option>";
								$otag = 0;
							}
						}

						if ($ostag)
						{
							$xml .= "</options>";
							$ostag = 0;
						}
					$xml .= "</item>";
					}
				$xml .= "</items>";
				}
			}
		}
		$xml .= "</order>";
		return $xml;
	}
}
?>
