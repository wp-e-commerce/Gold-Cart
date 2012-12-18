<?php
if(!is_callable('get_option')) {
  // This is here to stop error messages on servers with Zend Accelerator, it includes all files before get_option is declared
  // then evidently includes them again, otherwise this code would break these modules
  return;
  exit("Something strange is happening, and \"return\" is not breaking out of a file.");
}

$nzshpcrt_gateways[$num]['name'] = __( 'Paypal - Payflow Pro', 'wpsc_gold_cart' );
$nzshpcrt_gateways[$num]['internalname'] = 'paypal_payflow';
$nzshpcrt_gateways[$num]['function'] = 'gateway_paypal_payflow';
$nzshpcrt_gateways[$num]['form'] = "form_paypal_payflow";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_paypal_payflow";
$nzshpcrt_gateways[$num]['payment_type'] = "credit_card";
   //exit(get_option('payment_gateway'));

if(in_array('paypal_payflow',(array)get_option('custom_gateway_options'))) {
   $gateway_checkout_form_fields[$nzshpcrt_gateways[$num]['internalname']] = "
         <tr><td><strong>".__( '3. Credit Card Information', 'wpsc_gold_cart' ) ."</strong></td></tr>
      <tr>
         <td>
         ".__( 'Credit Card Number* (no dashes or spaces)', 'wpsc_gold_cart' )."
         </td>
         <td>
            <input type='text' value='' name='card_number' maxlength='19'/>
         </td>
      </tr>
      <tr>
         <td>
            ".__( 'CVV*', 'wpsc_gold_cart' )."
         </td>
         <td>
            <input type='text' value='' name='card_cvv' maxlength='3' size='4'/>
         </td>
      </tr>
      <tr>
         <td>
         ".__( 'Credit Card Expiry* (MM/YY)', 'wpsc_gold_cart' )."
         </td>
         <td>
            <input type='text' size='2' value='' maxlength='2' name='expiry[month]' />/<input type='text' size='2'  maxlength='2' value='' name='expiry[year]' />
         </td>
         </tr>";

}


function gateway_paypal_payflow($seperator, $sessionid) {

   global $wpdb, $wpsc_cart;
   $purchase_log_sql = "SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid`= ".$sessionid." LIMIT 1";
   $purchase_log = $wpdb->get_results($purchase_log_sql,ARRAY_A) ;
   $fraud = 'NO';
   $env = (get_option('paypal_payflow_test')) ? 'Test' : 'Live';
   $user=get_option('paypal_payflow_user');
   $password=get_option('paypal_payflow_pass');
   $partner=get_option('paypal_payflow_partner');
   $vendor=get_option('paypal_payflow_vendor');
   $currency=get_option('paypal_payflow_curcode');
   if($env=='Live') {
      $submiturl = 'https://payflowpro.paypal.com';
      $PayPalURL = 'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=';
   } else {
      $submiturl = 'https://pilot-payflowpro.paypal.com';
      $PayPalURL = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=';
   }
   $cart_sql = "SELECT * FROM `".WPSC_TABLE_CART_CONTENTS."` WHERE `purchaseid`='".$purchase_log[0]['id']."'";
   $cart = $wpdb->get_results($cart_sql,ARRAY_A) ;

   $member_subtype = get_product_meta($cart[0]['prodid'],'is_permenant',true);
   $member_shiptype = get_product_meta($cart[0]['prodid'],'membership_length',true);
   $member_shiptype = $member_shiptype[0];
   $status = get_product_meta($cart[0]['prodid'],'is_membership',true);
   $is_member = $status;
   $is_perm = $member_subtype;
   $length=$member_shiptype['length'];
   $custom = $purchase_log[0]['id'];
   if($_POST['collected_data'][get_option('paypal_form_first_name')] != '') {
      $data['first_name'] = urlencode($_POST['collected_data'][get_option('paypal_form_first_name')]);
   }

   if($_POST['collected_data'][get_option('paypal_form_last_name')] != '') {
      $data['last_name'] = urlencode($_POST['collected_data'][get_option('paypal_form_last_name')]);
   }

   if($_POST['collected_data'][get_option('paypal_form_address')] != '') {
      $address_rows = explode("\n\r",$_POST['collected_data'][get_option('paypal_form_address')]);
      $data['address1'] = urlencode(str_replace(array("\n", "\r"), '', $address_rows[0]));
      unset($address_rows[0]);
      if($address_rows != null) {
         $data['address2'] = implode(", ",$address_rows);
      } else {
         $data['address2'] = '';
      }
   }

   if($_POST['collected_data'][get_option('paypal_form_city')] != '') {
      $data['city'] = urlencode($_POST['collected_data'][get_option('paypal_form_city')]);
   }

   if($_POST['collected_data'][get_option('paypal_form_state')] != '') {
      $data['state'] = $wpdb->get_var("SELECT code FROM `".WPSC_TABLE_REGION_TAX."` WHERE id='" . wpsc_get_customer_meta( 'billing_region' ) ."'");
   }

   $customer_billing_country = wpsc_get_customer_meta( 'billing_country' );

   if( preg_match("/^[a-zA-Z]{2}$/", $customer_billing_country ) ) {
      $data['country'] = $customer_billing_country;
   }

   if(is_numeric($_POST['collected_data'][get_option('paypal_form_post_code')])) {
      $data['zip'] =  urlencode($_POST['collected_data'][get_option('paypal_form_post_code')]);
   }
     $email_data = $wpdb->get_results("SELECT `id`,`type` FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `type` IN ('email') AND `active` = '1'",ARRAY_A);
   foreach((array)$email_data as $email) {
      $data['email'] = $_POST['collected_data'][$email['id']];
   }

   if(($_POST['collected_data'][get_option('email_form_field')] != null) && ($data['email'] == null)) {
      $data['email'] = $_POST['collected_data'][get_option('email_form_field')];
   }
   $card_num=$_POST['card_number'];
   $cvv2=$_POST['card_cvv'];
   $expiry=$_POST['expiry']['month'].$_POST['expiry']['year'];
   $unique_id = generateGUID();
   $fname=$data['first_name'];
   $lname=$data['last_name'];
   $addr1=$data['address1'].$data['address2'];
   $addr2=$data['city'];
   $addr3=$data['state'];
   $addr4=$data['zip'];
   $country=$data['country'];
   $email = $data['email'];
   $amount= wpsc_cart_total(false);
   $amount= number_format($amount, 2, '.', '');
   if($is_member[0]) {
      switch($member_shiptype['unit']) {
         case 'w':
            $member_ship_unit = 'WEEK';
            break;

         case 'm':
            $member_ship_unit = 'MONT';
            break;

         case 'y':
            $member_ship_unit = 'YEAR';
            break;
      }

      $paypal_query_array = array(
         'USER'       => $user,
         'PROFILENAME' => $fname.$lname.$purchase_log[0]['id'],
         'VENDOR'     => $vendor,
         'PARTNER'    => $partner,
         'PWD'        => $password,
         'TENDER'     =>'C',  // C - Direct Payment using credit card
         'TRXTYPE'    => 'R',  // A - Authorization, S - Sale R-Recurring billing
         'ACTION' => 'A',
         'START'   => date('m').(date('d')+1).date('Y'),
         'ACCT'       => $card_num,
         'CVV2'       => $cvv2,
         'EXPDATE'    => $expiry,
         'ACCTTYPE'   => $card,
         'AMT'        => $amount,
         'CURRENCY'   => $currency,
         'FIRSTNAME'  => $fname,
         'LASTNAME'   => $lname,
         'STREET'     => $addr1,
         'CITY'       => $addr2,
         'STATE'      => $addr3,
         'ZIP'        => $addr4,
         'COUNTRY'    => $country,
         'EMAIL'      => $email,
         'OPTIONALTRX'=>'A',
         'OPTIONALTRXAMT'=>'0.00',
         'CLIENTIP'     => $cust_ip,
         'COMMENT1'   => $custom,
         'ORDERDESC'  => $fname.$lname.$purchase_log[0]['id'],
         'PAYPERIOD' => $member_ship_unit,
      );

      foreach ($paypal_query_array as $key => $value) {
         if ($key == 'USER') {
            $paypal_query .= $key.'['.strlen($value).']='.$value;
         }else{
            $paypal_query .= '&'.$key.'['.strlen($value).']='.$value;
         }
      }
      $response=fetch_data($unique_id,$submiturl,$paypal_query);
      response_handler($response,'NO',$sessionid,$colected_data,1);
   }


   if (get_option('paypal_payflow_method')=='0')
      $tender='P';
   else
      $tender='C';
   $fname=$data['first_name'];
   $lname=$data['last_name'];
   $addr1=$data['address1'].$data['address2'];
   $addr2=$data['city'];
   $addr3=$data['state'];
   $addr4=$data['zip'];
   $country=$data['country'];
   $email = $data['email'];
   $paypal_query_array = array(
      'USER'       => $user,
      'VENDOR'     => $vendor,
      'PARTNER'    => $partner,
      'PWD'        => $password,
      'TENDER'     =>$tender,  // C - Direct Payment using credit card
      'TRXTYPE'    => 'S',  // A - Authorization, S - Sale
      'ACCT'       => $card_num,
      'CVV2'       => $cvv2,
      'EXPDATE'    => $expiry,
      'ACCTTYPE'   => $card,
      'AMT'        => $amount,
      'CURRENCY'   => $currency,
      'FIRSTNAME'  => $fname,
      'LASTNAME'   => $lname,
      'STREET'     => $addr1,
      'CITY'       => $addr2,
      'STATE'      => $addr3,
      'ZIP'        => $addr4,
      'COUNTRY'    => $country,
      'EMAIL'      => $email,
      'CLIENTIP'     => $cust_ip,
      'COMMENT1'   => $custom,
      'COMMENT2'   => '',
      'INVNUM'     => $order_num,
      'ORDERDESC'  => $desc,
      'VERBOSITY'  => 'MEDIUM',
      'CARDSTART'  => $card_start,
      'CARDISSUE'  => $card_issue,
   );
   foreach ($paypal_query_array as $key => $value) {
      if ($key == 'USER') {
         $paypal_query .= $key.'['.strlen($value).']='.$value;
      }else{
         $paypal_query .= '&'.$key.'['.strlen($value).']='.$value;
      }
   }
   //exit("<pre>".print_r($paypal_query_array,true)."</pre>");
   $response=fetch_data($unique_id,$submiturl,$paypal_query);
   response_handler($response,'NO',$sessionid,$colected_data);
  exit();
}


function submit_paypal_payflow()
{
	$fields = array(
		'user',
		'pass',
		'curcode',
		'vendor',
		'partner',
		'method',
		'test',
	);
	foreach ( $fields as $field ) {
		$key = "paypal_payflow_{$field}";
		if ( isset( $_POST[$key] ) && $_POST[$key] !== '' ) {
			update_option( $key, $_POST[$key] );
		}

		elseif ( $field == 'test' )
			update_option( $key, 0 );
	}
	if ( ! empty( $_POST['paypal_form'] ) ) {
		foreach( (array) $_POST['paypal_form'] as $form => $value ) {
			update_option( 'paypal_form_' . $form , $value);
		}
	}
	return true;
}

function form_paypal_payflow()
{
	$currencies = array(
		'USD' => __( 'U.S. Dollar', 'wpsc_gold_cart' ),
		'CAD' => __( 'Canadian Dollar', 'wpsc_gold_cart' ),
		'AUD' => __( 'Australian Dollar', 'wpsc_gold_cart' ),
		'EUR' => __( 'Euro', 'wpsc_gold_cart' ),
		'GBP' => __( 'Pound Sterling', 'wpsc_gold_cart' ),
		'JPY' => __( 'Japanese Yen', 'wpsc_gold_cart' ),
	);
	$chosen_currency = get_option( 'paypal_payflow_curcode' );

	$methods = array(
		__( 'Payment Express', 'wpsc_gold_cart' ),
		__( 'Credit Card Direct Payment', 'wpsc_gold_cart' ),
	);
	$chosen_method = get_option( 'paypal_payflow_method' );
	$payflow_test = get_option( 'paypal_payflow_test' );

	ob_start();
?>
<tr>
	<td>
		<?php _e( 'PayPal Payflow Pro User', 'wpsc_gold_cart' ); ?>
	</td>
	<td>
		<input type='text' value='<?php echo esc_attr( get_option('paypal_payflow_user') ); ?>' name='paypal_payflow_user' />
	</td>
</tr>
<tr>
	<td>
		<?php _e( 'PayPal Payflow Pro Password', 'wpsc_gold_cart' ); ?>
	</td>
	<td>
		<input type='text' value='<?php echo esc_attr( get_option('paypal_payflow_pass') ); ?>' name='paypal_payflow_pass' />
	</td>
</tr>
<tr>
	<td>
		<?php _e( 'PayPal Payflow Pro Vendor', 'wpsc_gold_cart' ); ?>
	</td>
	<td>
		<input type='text' value='<?php echo esc_attr( get_option('paypal_payflow_vendor') ); ?>' name='paypal_payflow_vendor' />
	</td>
<tr>
	<td>
		<?php _e( 'PayPal Testing Environment', 'wpsc_gold_cart' ); ?>
	</td>
	<td>
		<label><input type='checkbox'<?php echo $payflow_test == 1 ? ' checked="checked"' : ''; ?> value='1' name='paypal_payflow_test' /><?php _e( 'Enable', 'wpsc_gold_cart' ); ?></label>
	</td>
	</tr>
</tr>

<tr>
	<td>
		<?php _e( 'Payment Method', 'wpsc_gold_cart' ); ?>
	</td>
	<td>
		<?php foreach ( $methods as $method => $title ) {
			$selected = $chosen_method == $method ? ' checked="checked"' : '';
			echo "<label><input type='radio'{$selected} value='{$method}' name='paypal_payflow_method' />{$title}</label><br />";
		} ?>
	</td>
</tr>
<tr>
	<td>
		<?php _e( 'PayPal Payflow Pro Partner', 'wpsc_gold_cart' ); ?>
	</td>
	<td>
		<input type='text' value='<?php echo esc_attr( get_option('paypal_payflow_partner') ); ?>' name='paypal_payflow_partner' />
	</td>
</tr>
<tr>
	<td>
		<?php _e( 'PayPal Accepted Currency (e.g. USD, AUD)', 'wpsc_gold_cart' ); ?>
	</td>
	<td>
	<select name='paypal_payflow_curcode'>
		<?php
		foreach ( $currencies as $currency => $title ) {
			$selected = $chosen_currency == $currency ? ' selected="selected"' : '';
			echo "<option{$selected} value='{$currency}'>" . esc_html( $title ) . "</option>";
		}
		?>
	  </select>
	</td>
</tr>

<tr>
	<td colspan="2"><h2><?php _e( 'Forms Sent to Gateway', 'wpsc_gold_cart' ); ?></h2></td>
</tr>
<tr>
	<td>
		<?php _e( 'First Name Field', 'wpsc_gold_cart' ); ?>
	</td>
	<td>
		<select name='paypal_form[first_name]'>
			<?php echo nzshpcrt_form_field_list( get_option( 'paypal_form_first_name' ) ); ?>
		</select>
	</td>
</tr>
<tr>
	<td>
		<?php _e( 'Last Name Field', 'wpsc_gold_cart' ); ?>
	</td>
	<td>
	<select name='paypal_form[last_name]'>
		<?php echo nzshpcrt_form_field_list( get_option( 'paypal_form_last_name' ) ); ?>
	</select>
	</td>
</tr>
<tr>
	<td>
		<?php _e( 'Address Field', 'wpsc_gold_cart' ); ?>
	</td>
	<td>
	<select name='paypal_form[address]'>
		<?php echo nzshpcrt_form_field_list( get_option( 'paypal_form_address' ) ); ?>
	</select>
	</td>
</tr>
<tr>
	<td>
		<?php _e( 'City Field', 'wpsc_gold_cart' ); ?>
	</td>
	<td>
	<select name='paypal_form[city]'>
		<?php echo nzshpcrt_form_field_list( get_option( 'paypal_form_city' ) ); ?>
	</select>
	</td>
</tr>
<tr>
	<td>
		<?php _e( 'State Field', 'wpsc_gold_cart' ); ?>
	</td>
	<td>
	<select name='paypal_form[state]'>
		<?php echo nzshpcrt_form_field_list( get_option( 'paypal_form_state' ) ); ?>
	</select>
	</td>
</tr>
<tr>
	<td>
		<?php _e( 'Postal code/Zip code Field', 'wpsc_gold_cart' ); ?>
	</td>
	<td>
	<select name='paypal_form[post_code]'>
		<?php echo nzshpcrt_form_field_list( get_option( 'paypal_form_post_code' ) ); ?>
	</select>
	</td>
</tr>
<tr>
	<td>
		<?php _e( 'Country Field', 'wpsc_gold_cart' ); ?>
	</td>
	<td>
	<select name='paypal_form[country]'>
		<?php echo nzshpcrt_form_field_list( get_option( 'paypal_form_country' ) ); ?>
	</select>
	</td>
</tr>
<?php
	return ob_get_clean();
}

function fetch_data($unique_id, $submiturl, $data) {
   $user_agent = $_SERVER['HTTP_USER_AGENT'];
   $headers[] = "Content-Type: text/namevalue";
   $headers[] = "Content-Length : " . strlen ($data);
   $headers[] = "X-VPS-Timeout: 45";
   $headers[] = "X-VPS-Request-ID:" . $unique_id;

    // Optional Headers.  If used adjust as necessary.
    //$headers[] = "X-VPS-VIT-OS-Name: Linux";                  // Name of your OS
    //$headers[] = "X-VPS-VIT-OS-Version: RHEL 4";          // OS Version
    //$headers[] = "X-VPS-VIT-Client-Type: PHP/cURL";          // What you are using
    //$headers[] = "X-VPS-VIT-Client-Version: 0.01";          // For your info
    //$headers[] = "X-VPS-VIT-Client-Architecture: x86";          // For your info
    //$headers[] = "X-VPS-VIT-Integration-Product: PHPv4::cURL";  // For your info, would populate with application name
    //$headers[] = "X-VPS-VIT-Integration-Version: 0.01";         // Application version
   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL, $submiturl);
   curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
   curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
   curl_setopt($ch, CURLOPT_HEADER, 1);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   curl_setopt($ch, CURLOPT_TIMEOUT, 90);
   curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
   curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
   curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
   curl_setopt($ch, CURLOPT_POST, 1);

    $i=1;
    while ($i++ <= 3) {
       $result = curl_exec($ch);
       $headers = curl_getinfo($ch);
   if ($headers['http_code'] != 200) {
      sleep(5);
   }
   else if ($headers['http_code'] == 200) {
             break;
   }
    }
          if ($headers['http_code'] != 200) {
       echo '<h2>'.__( 'General Error!', 'wpsc_gold_cart' ).'</h2>';
       echo '<h3>'.__( 'Unable to receive response from PayPal server.', 'wpsc_gold_cart' ).'</h3><p>';
       echo '<h4>'.sprintf( __( 'Verify host URL of %s and check for firewall/proxy issues.', 'wpsc_gold_cart'), $submiturl ).'</h4>';
       curl_close($ch);
       exit;
          }
          curl_close($ch);
          $result = strstr($result, "RESULT");
          $proArray = array();
      while(strlen($result)){
         $keypos= strpos($result,'=');
   $keyval = substr($result,0,$keypos);
         $valuepos = strpos($result,'&') ? strpos($result,'&'): strlen($result);
   $valval = substr($result,$keypos+1,$valuepos-$keypos-1);
         $proArray[$keyval] = $valval;
   $result = substr($result,$valuepos+1,strlen($result));
    }
    return $proArray;
}

function response_handler($nvpArray, $fraud,$sessionid,$data=null,$recurring=null) {
   global $wpdb;
   $result_code = $nvpArray['RESULT'];

   //$RespMsg = 'General Error.  Please contact Customer Support.';
//    echo ($result_code);
   if ($result_code == 1 || $result_code == 26) {
      wpsc_update_customer_meta( 'payflow_message', __( 'Account configuration issue.  Please verify your login credentials.', 'wpsc_gold_cart' ) );
   } else if ($result_code== '0') {
      $purchase_log = new WPSC_Purchase_Log( $sessionid, 'sessionid' );
      $purchase_log->set( 'processed', WPSC_Purchase_Log::ACCEPTED_PAYMENT );
      $purchase_log->save();
      $log_id=$purchase_log->get( 'id' );
      if (isset($nvpArray['CVV2MATCH'])) {
         if ($nvpArray['CVV2MATCH'] != "Y") {
            $RespMsg = __( 'Your billing (cvv2) information does not match. Please re-enter.', 'wpsc_gold_cart' );
         }
      }
   } else if ($result_code == 12) {
      $log_id = $wpdb->get_var("SELECT `id` FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid`='$sessionid' LIMIT 1");
      $delete_log_form_sql = "SELECT * FROM `".$wpdb->prefix."cart_contents` WHERE `purchaseid`='$log_id'";
      $cart_content = $wpdb->get_results($delete_log_form_sql,ARRAY_A);
      /*
foreach((array)$cart_content as $cart_item) {
         $cart_item_variations = $wpdb->query("DELETE FROM `".$wpdb->prefix."cart_item_variations` WHERE `cart_id` = '".$cart_item['id']."'", ARRAY_A);
      }
*/
      $wpdb->query("DELETE FROM `".WPSC_TABLE_CART_CONTENTS."` WHERE `purchaseid`='$log_id'");
      $wpdb->query("DELETE FROM `".WPSC_TABLE_SUBMITED_FORM_DATA."` WHERE `log_id` IN ('$log_id')");
      $wpdb->query("DELETE FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `id`='$log_id' LIMIT 1");
      wpsc_update_customer_meta( 'payflow_message', __( 'Your credit card has been declined.  You may press the back button in your browser and check that you\'ve entered your card information correctly, otherwise please contact your credit card issuer.', 'wpsc_gold_cart' ) );
      header("Location:".get_option('transact_url').$seperator."payflow=1&message=1");
   } else if ($result_code == 13) {
      $log_id = $wpdb->get_var("SELECT `id` FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid`='$sessionid' LIMIT 1");
      $delete_log_form_sql = "SELECT * FROM `".WPSC_TABLE_CART_CONTENTS."` WHERE `purchaseid`='$log_id'";
      $cart_content = $wpdb->get_results($delete_log_form_sql,ARRAY_A);
      /*
      foreach((array)$cart_content as $cart_item) {
         $cart_item_variations = $wpdb->query("DELETE FROM `".WPSC_TABLE_CART_ITEM_VARIATIONS."` WHERE `cart_id` = '".$cart_item['id']."'", ARRAY_A);
      }
      */
      $RespMsg = __( 'Invalid credit card information. Please use the back button in your browser and re-enter if you feel that you have received this message in error', 'wpsc_gold_cart' );
      wp_die($RespMsg);//die before deleting cart information
      $wpdb->query("DELETE FROM `".WPSC_TABLE_CART_CONTENTS."` WHERE `purchaseid`='$log_id'");
      $wpdb->query("DELETE FROM `".WPSC_TABLE_SUBMITED_FORM_DATA."` WHERE `log_id` IN ('$log_id')");
      $wpdb->query("DELETE FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `id`='$log_id' LIMIT 1");
   } else if ($result_code == 23 || $result_code == 24) {
      $log_id = $wpdb->get_var("SELECT `id` FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid`='$sessionid' LIMIT 1");
      $delete_log_form_sql = "SELECT * FROM `".WPSC_TABLE_CART_CONTENTS."` WHERE `purchaseid`='$log_id'";
      $cart_content = $wpdb->get_results($delete_log_form_sql,ARRAY_A);
/*
      foreach((array)$cart_content as $cart_item) {
         $cart_item_variations = $wpdb->query("DELETE FROM `".$wpdb->prefix."cart_item_variations` WHERE `cart_id` = '".$cart_item['id']."'", ARRAY_A);
      }
*/
      $RespMsg = __( 'Invalid credit card information. Please use the back button in your browser and re-enter if you feel that you have received this message in error', 'wpsc_gold_cart' );
      wp_die($RespMsg);//die before deleting cart information
      $wpdb->query("DELETE FROM `".WPSC_TABLE_CART_CONTENTS."` WHERE `purchaseid`='$log_id'");
      $wpdb->query("DELETE FROM `".WPSC_TABLE_SUBMITED_FORM_DATA."` WHERE `log_id` IN ('$log_id')");
      $wpdb->query("DELETE FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `id`='$log_id' LIMIT 1");
      $RespMsg = __( 'Invalid credit card information. Please use the back button in your browser and re-enter. If you feel that you received this message in error.', 'wpsc_gold_cart' );
   } else {
      $log_id = $wpdb->get_var("SELECT `id` FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid`='$sessionid' LIMIT 1");
      $delete_log_form_sql = "SELECT * FROM `".WPSC_TABLE_CART_CONTENTS."` WHERE `purchaseid`='$log_id'";
      $cart_content = $wpdb->get_results($delete_log_form_sql,ARRAY_A);
/*
      foreach((array)$cart_content as $cart_item) {
         $cart_item_variations = $wpdb->query("DELETE FROM `".$wpdb->prefix."cart_item_variations` WHERE `cart_id` = '".$cart_item['id']."'", ARRAY_A);
      }
*/
      $wpdb->query("DELETE FROM `".WPSC_TABLE_CART_CONTENTS."` WHERE `purchaseid`='$log_id'");
      $wpdb->query("DELETE FROM `".WPSC_TABLE_SUBMITED_FORM_DATA."` WHERE `log_id` IN ('$log_id')");
      $wpdb->query("DELETE FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `id`='$log_id' LIMIT 1");
      $RespMsg = __( 'Invalid credit card information. Please use the back button in your browser and re-enter. If you feel that you received this message in error.', 'wpsc_gold_cart' );
   }

   if ($fraud == 'YES') {
      if ($result_code == 125) {
         $log_id = $wpdb->get_var("SELECT `id` FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid`='$sessionid' LIMIT 1");
         $delete_log_form_sql = "SELECT * FROM `".WPSC_TABLE_CART_CONTENTS."` WHERE `purchaseid`='$log_id'";
         $cart_content = $wpdb->get_results($delete_log_form_sql,ARRAY_A);
         /*
foreach((array)$cart_content as $cart_item) {
            $cart_item_variations = $wpdb->query("DELETE FROM `".$wpdb->prefix."cart_item_variations` WHERE `cart_id` = '".$cart_item['id']."'", ARRAY_A);
         }
*/

         $wpdb->query("DELETE FROM `".WPSC_TABLE_CART_CONTENTS."` WHERE `purchaseid`='$log_id'");
         $wpdb->query("DELETE FROM `".WPSC_TABLE_SUBMITED_FORM_DATA."` WHERE `log_id` IN ('$log_id')");
         $wpdb->query("DELETE FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `id`='$log_id' LIMIT 1");
      } else if ($result_code == 126) {
          $RespMsg = __( 'Your Transaction is Under Review. We will notify you via e-mail if accepted.', 'wpsc_gold_cart' );
      } else if ($result_code == 127) {
          $RespMsg = __( 'Your Transaction is Under Review. We will notify you via e-mail if accepted.', 'wpsc_gold_cart' );
      }
   }
   //$message=transaction_results($sessionid,false,null,$data,$result_code);
   if (get_option('permalink_structure')!='') {
      $seperator='?';
   } else {
      $seperator='&';
   }

   if ($result_code!=0) {
      wpsc_update_customer_meta( 'payflow_message', $RespMsg );
      header("Location:".get_option('transact_url').$seperator."payflow=1&&sessionid=".$sessionid."result=".$result_code."&message=1");
   }

   header("Location:".get_option('transact_url').$seperator."payflow=1&sessionid=".$sessionid."&result=".$result_code."&message=1");
}

function displayResponse($RespMsg, $nvpArray) {

   echo '<p>'.__( 'Results returned from server:', 'wpsc_gold_cart' ).'<br><br>';
   while (list($key, $val) = each($nvpArray)) {
      echo "\n" . $key . ": " . $val . "\n<br>";
   }
   echo '</p>';
          if(isset ($nvpArray['DUPLICATE'])) {
            echo '<h2>'.__( 'Error!', 'wpsc_gold_cart' ).'</h2>';
            echo '<p>'.__( 'This is a duplicate of your previous order.', 'wpsc_gold_cart' ).'</p>';
            echo '<p>'.__( 'Notice that DUPLICATE=1 is returned and the PNREF is the same as the previous one. You can see this in Manager as the Transaction Type will be "N".', 'wpsc_gold_cart' ).'</p>';
          }
          if (isset($nvpArray['PPREF'])) {
         if (isset($nvpArray['PENDINGREASON'])) {
      if ($nvpArray['PENDINGREASON']=='completed') {
         echo '<h2>'.__( 'Transaction Completed!', 'wpsc_gold_cart' ).'</h2>';
         echo '<h3>'.$RespMsg.'</h3>';
         echo '<h4>'.__( 'Note: To simulate a duplicate transaction, refresh this page in your browser. Notice that you will see DUPLICATE=1 returned.', 'wpsc_gold_cart' ).'</h4>';
      }
      elseif($nvpArray['PENDINGREASON']=='echeck') {
         echo '<h2>'.__( 'Transaction Completed!', 'wpsc_gold_cart' ).'</h2>';
         echo '<h3>'.__( 'The payment is pending because it was made by an eCheck that has not yet cleared.', 'wpsc_gold_cart' ).'</h3>';
      }
      else {
         echo '<h2>'.__( 'Transaction Completed!', 'wpsc_gold_cart' ).'</h2>';
         echo '<h3>'.sprintf( __( 'The payment is pending due to: %s', 'wpsc_gold_cart' ), $nvpArray['PENDINGREASON'] ).'</h3>';
         echo '<h4>'.__( 'Please login to your PayPal account for more details.', 'wpsc_gold_cart' ).'</h4>';
      }
         } else {
            echo '<h2>'.__( 'Transaction Completed!', 'wpsc_gold_cart' ).'</h2>';
            echo '<h3>'.$RespMsg.'</h3>';
            if ($nvpArray['RESULT'] != "26" && $nvpArray['RESULT'] != "1") {
               echo '<h4>'.__( 'Note: To simulate a duplicate transaction, refresh this page in your browser. Notice that you will see DUPLICATE=1 returned.', 'wpsc_gold_cart' ).'</h4>';
            }
         }
          }
}

function generateCharacter () {
   $possible = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
   $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
   return $char;
}

function generateGUID () {
   $GUID = generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter()."-";
   $GUID = $GUID .generateCharacter().generateCharacter().generateCharacter().generateCharacter()."-";
   $GUID = $GUID .generateCharacter().generateCharacter().generateCharacter().generateCharacter()."-";
   $GUID = $GUID .generateCharacter().generateCharacter().generateCharacter().generateCharacter()."-";
   $GUID = $GUID .generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter();
   return $GUID;
}

?>