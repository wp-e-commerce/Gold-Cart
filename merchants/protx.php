<?php
//Gateway Details
$nzshpcrt_gateways[$num]['name'] 			= 'Sagepay';
$nzshpcrt_gateways[$num]['internalname']	= 'sagepay';
$nzshpcrt_gateways[$num]['function'] 		= 'gateway_sagepay';
$nzshpcrt_gateways[$num]['form'] 			= "form_sagepay";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_sagepay";
$nzshpcrt_gateways[$num]['payment_type'] 	= "credit_card";

// Sagepay Gateway 
if ( !function_exists('gateway_sagepay') ) {
	function gateway_sagepay($seperator, $sessionid) {
		global $wpdb;
		
		// Get Purchase Log
		$purchase_log_sql = "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `sessionid`= " . $sessionid . " LIMIT 1";
		$purchase_log = $wpdb->get_results($purchase_log_sql, ARRAY_A) ;

		// Get Cart Contents		
		$cart_sql = "SELECT * FROM `" . WPSC_TABLE_CART_CONTENTS . "` WHERE `purchaseid`='" . $purchase_log[0]['id'] . "'";
		$cart = $wpdb->get_results($cart_sql, ARRAY_A) ;
		
		//Set Post Data
		$data['VendorTxCode'] = $sessionid;
		$data['Amount'] = number_format($purchase_log[0]['totalprice'], 2, '.', '');
		$data['Currency'] = get_option('protx_cur');
		$data['Description'] = get_bloginfo( 'name' ) ." wpEcommerce";
		$transact_url = get_option('transact_url');
		$site_url = get_option('shopping_cart_url');
		$data['SuccessURL'] = $transact_url . $seperator . "protx=success";
		$data['FailureURL'] = $site_url;
		
		$uniquenames = get_option( 'wpsc_checkout_unique_names' );
		$field_mapping = array(
			'Surname' => 'lastname',
			'Firstnames' => 'firstname',
			'PostCode' => 'postcode',
			'Address1' => 'address',
			'City' => 'city',
			'Country' => 'country',
			'State' => 'state',
		);
		
		$field_types = array(
			'Billing' => 'billing',
			'Delivery' => 'shipping',
		);
		
		$fields_data = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `active` = '1'" );
		$fields = array();
		
		foreach ( $fields_data as $field ) {
			$fields[$field->unique_name] = $field->id;
		}
		
		foreach ( array_keys( $field_types ) as $sp_type ) {
			foreach ( $field_mapping as $sp_field => $mapped_field) {
				$field_type = $field_types[$sp_type];
				$field_name = $field_type . $mapped_field;
				if ( ! isset( $fields[$field_name] ) )
					continue;
				$field_id = $fields[$field_name];
				$field_value = isset( $_POST['collected_data'][$field_id] ) ? $_POST['collected_data'][$field_id] : '';
				
				switch ( $field_name ) {
					case 'billingcountry':
						$field_value = $field_value[0];
						break;
						
					case 'shippingcountry':
						$field_value = $_SESSION['wpsc_delivery_country'];
						break;
						
					case 'billingstate':
						$country = $_POST['collected_data'][$fields['billingcountry']][0];
						if ( $country != 'US' )
							$field_value = '';
						elseif ( empty( $field_value ) )
							$field_value = $_POST['collected_data'][$fields['billingcountry']][1];
						break;
						
					case 'shippingstate':
						if ( empty( $field_Value ) )
							$field_value = $_SESSION['wpsc_delivery_region'];
						break;
				}
				
				if ( $mapped_field == 'country' && $field_value == 'UK' )
					$field_value = 'GB';
				elseif ( $mapped_field == 'state' && is_numeric( $field_value ) )
					$field_value = wpsc_get_state_by_id( $field_value,'code');

				$data["{$sp_type}{$sp_field}"] = urlencode( $field_value );
			}
		}
		
		$basket_productprice_total = 0;
		$basket_rows = (count($cart) + 1);
		if ( !empty($purchase_log[0]['discount_value']) ) {
			$basket_rows += 1;
		}
		
		$data['Basket'] = $basket_rows . ':';
		
		foreach ( (array)$cart as $item ) {
			$product_data = get_post( $item['prodid'] );
			$basket_productprice_total += ($item['price'] * $item['quantity']);
			$data['Basket'] .= preg_replace('/[^a-z0-9]/i', '_', $product_data->post_title) . ":" . $item['quantity'] . ":" . $item['price'] . ":---:" . ($item['price'] * $item['quantity']) . ":" . ($item['price'] * $item['quantity']) . ":";
		}
		
		$basket_delivery = $data['Amount'] - $basket_productprice_total;
		if ( !empty($purchase_log[0]['discount_value']) ) {
			$basket_delivery += $purchase_log[0]['discount_value'];
		}
		$data['Basket'] .= "Delivery:---:---:---:---:" . $basket_delivery;
		
		if ( !empty($purchase_log[0]['discount_value']) ) {
			$data['Basket'] .= ":Discount (" . $purchase_log[0]['discount_data'] . "):---:---:---:---:-" . $purchase_log[0]['discount_value'];
		}
		
		// End Create Basket Data
		$postdata = "";
		$i = 0;
		// exit("<pre>" . print_r($data, true) . "</pre>");
		foreach ( $data as $key => $da ) {
			if ( $i == 0 ) {
				$postdata .= "$key=$da";
			} else {
				$postdata .= "&$key=$da";
			}
			$i++;
		}
		$servertype = get_option('protx_server_type');
		if ( $servertype == 'test' ) {
			$url = 'https://test.sagepay.com/gateway/service/vspform-register.vsp';
		} elseif ( $servertype == 'sim' ) {
			$url = 'https://test.sagepay.com/Simulator/VSPFormGateway.asp';
		} elseif ( $servertype == 'live' ) {
			$url = 'https://live.sagepay.com/gateway/service/vspform-register.vsp';
		}
		$crypt = base64_encode(SimpleXor($postdata, get_option('protx_enc_key')));
		$postdata1['VPSProtocol'] = get_option("protx_protocol");
		$postdata1['TxType'] = "PAYMENT";
		$postdata1['Vendor'] = get_option("protx_name");
		// $postdata1['VendorTxCode'] = $sessionid;
		$postdata1['Crypt'] = $crypt;
		$j = 0;
		$postdata2 = "";
		foreach ( $postdata1 as $key=>$dat ) {
			if ( $j == 0 ) {
				$postdata2 .= "$key=$dat";
			} else {
				$postdata2 .= "&$key=$dat";
			}
			$j++;
		}
        $output = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"><html lang="en"><head><title></title></head><body>'; 
		$output .= "<form id=\"sagepay_form\" name=\"sagepay_form\" method=\"post\" action=\"$url\">\n";
		$output .= "<input type='hidden' value ='2.23' name='VPSProtocol' />";
		$output .= "<input type='hidden' value ='PAYMENT' name='TxType' />";
		$output .= "<input type='hidden' value ='" . get_option("protx_name") . "' name='Vendor' />";
		$output .= "<input type='hidden' value ='" . $crypt . "' name='Crypt' />";
		$output .= "</form>";
		$output .= "<script language=\"javascript\" type=\"text/javascript\">document.getElementById('sagepay_form').submit();</script>";
		$output .= '</body></html>'; 
		echo $output;
		exit();
	}
	
	function submit_sagepay() {
		$options = array(
			'name',
			'protocol',
			'enc_key',
			'cur',
			'server_type',
		);
		foreach ( $options as $option ) {
			$field = "protx_{$option}";
			if ( ! empty( $_POST[$field] ) )
				update_option( $field, $_POST[$field] );
		}
		
		if ( ! empty( $_POST['protx_form'] ) ) {
			foreach( (array)$_POST['protx_form'] as $form => $value ) {
				update_option(('protx_form_'.$form), $value);
			}
		}
		return true;
	}
	
	function form_sagepay() {
		global $wpdb;
		$servertype = get_option('protx_server_type');
		$servertype1 = "";
		$servertype2 = "";
		$servertype3 = "";
		
		if ( $servertype == 'test' ){
			$servertype1 = 'selected="selected"';
		} elseif ( $servertype == 'sim' ) {
			$servertype2 = 'selected="selected"';				
		} elseif ( $servertype == 'live' ) {
			$servertype3 = 'selected="selected"';			
		}
		$query = "SELECT DISTINCT code FROM `" . WPSC_TABLE_CURRENCY_LIST . "` ORDER BY code";
		$result = $wpdb->get_results($query, ARRAY_A);
		$output = "
			<tr>
				<td>
					Protx Vendor name:
				</td>
				<td>
					<input type='text' size='40' value='".get_option('protx_name')."' name='protx_name' />
				</td>
			</tr>
			<tr>
				<td>
					Protx VPS Protocol:
				</td>
				<td>
				<input type='text' size='20' value='".get_option('protx_protocol')."' name='protx_protocol' /> e.g. 2.22
				</td>
			</tr>
			<tr>
				<td>
				Protx Encryption Key:
				</td>
				<td>
					<input type='text' size='20' value='".get_option('protx_enc_key')."' name='protx_enc_key' />
				</td>
			</tr>
			<tr>
				<td>
					Server Type:
				</td>
				<td>
					<select name='protx_server_type'>
						<option $servertype1 value='test'>Test Server</option>
						<option $servertype2 value='sim'>Simulator Server</option>
						<option $servertype3 value='live'>Live Server</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					Select your currency
				</td>
				<td>
					<select name='protx_cur'>";
						$current_currency = get_option('protx_cur');
						//exit($current_currency);
						foreach ( (array)$result as $currency ) {
							if ( $currency['code'] == $current_currency ) {
								$selected = "selected = 'true'";
							} else {
								$selected = "";
							}
							$output.= "<option $selected value='" . $currency['code'] . "'>" . $currency['code'] . "</option>";
						}
						$output .= "</select>
				</td>
			</tr>";

		return $output;
	}
	
	function simpleXor($InString, $Key) {
		// Initialise key array
		$KeyList = array();
		// Initialise out variable
		$output = "";
		
		// Convert $Key into array of ASCII values
		for ( $i = 0; $i < strlen($Key); $i++ ) {
			$KeyList[$i] = ord(substr($Key, $i, 1));
		}
		
		// Step through string a character at a time
		for ( $i = 0; $i < strlen($InString); $i++ ) {
			// Get ASCII code from string, get ASCII code from key (loop through with MOD), XOR the two, get the character from the result
			// % is MOD (modulus), ^ is XOR
			$output .= chr(ord(substr($InString, $i, 1)) ^ ($KeyList[$i % strlen($Key)]));
		}
		
		// Return the result
		return $output;
	}
	
}

function nzshpcrt_sagepay_decryption() {
global $wpdb;
	
	if ( get_option( 'permalink_structure' ) != '' ) {
		$seperator = "?";
	} else {
		$seperator = "&";
	}
	$crypt = str_replace( " ", "+", $_GET['crypt'] );
	$uncrypt = SimpleXor( base64_decode( $crypt ), get_option( 'protx_enc_key' ) );
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
	
	switch ( $success ) {
		case 'Completed':
			$wpdb->query( "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed` = '3', `transactid` = '" . $unencrypted_values['VPSTxId'] . "', `notes` = 'SagePay Status: " . $unencrypted_values['Status'] . "' WHERE `sessionid` = " . $unencrypted_values['VendorTxCode'] . " LIMIT 1" );
			break;
		case 'Failed': // if it fails...
			switch ( $unencrypted_values['Status'] ) {
				case 'NOTAUTHED':
				case 'REJECTED':
				case 'MALFORMED':
				case 'INVALID':
				case 'ERROR':
					$wpdb->query( "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed` = '1', `notes` = 'SagePay Status: " . $unencrypted_values['Status'] . "' WHERE `sessionid` = " . $unencrypted_values['VendorTxCode'] . " LIMIT 1" );
					break;
			}
			break;
		case 'Pending': // need to wait for "Completed" before processing
			$sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed` = '1', `transactid` = '" . $unencrypted_values['VPSTxId'] . "', `date` = '" . time() . "', `notes` = 'SagePay Status: " . $unencrypted_values['Status'] . "'  WHERE `sessionid` = " . $unencrypted_values['VendorTxCode'] . " LIMIT 1";
			$wpdb->query( $sql );
			break;
	}
	
	$transact_url = get_option( 'transact_url' ) . $seperator . "sessionid=" . $unencrypted_values['VendorTxCode'];
	header( "Location: $transact_url" );
	exit();
}

if ( isset($_GET['protx']) && $_GET['protx'] == 'success' && ($_GET['crypt'] != '') ) {
	add_action('init', 'nzshpcrt_sagepay_decryption');
}



?>