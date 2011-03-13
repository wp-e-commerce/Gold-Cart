<?php
$nzshpcrt_gateways[$num]['name'] = 'iDeal';
$nzshpcrt_gateways[$num]['internalname'] = 'ideal';
$nzshpcrt_gateways[$num]['function'] = 'gateway_ideal';
$nzshpcrt_gateways[$num]['form'] = "form_ideal";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_ideal";
$nzshpcrt_gateways[$num]['payment_type'] = "credit_card";


function gateway_ideal($seperator, $sessionid) {
	global $wpdb;
	$purchase_log_sql = "SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid`= ".$sessionid." LIMIT 1";
	$purchase_log = $wpdb->get_results($purchase_log_sql,ARRAY_A) ;
	$submiturl="https://internetkassa.abnamro.nl/ncol/prod/orderstandard.asp";
	$amount = nzshpcrt_overall_total_price($_SESSION['delivery_country']);
// 	$amount = round($amount,2)*100;
// 	exit($amount);

	if($_POST['collected_data'][get_option('ideal_form_post_code')] != ''){
		$postcode= $_POST['collected_data'][get_option('ideal_form_post_code')];
	}

	if($_POST['collected_data'][get_option('ideal_form_address')] != ''){
		$address = $_POST['collected_data'][get_option('ideal_form_address')];
	}
	
	if($_POST['collected_data'][get_option('ideal_form_email')] != ''){
		$email = $_POST['collected_data'][get_option('ideal_form_email')];
	}

	if($_POST['collected_data'][get_option('ideal_form_city')] != ''){
		$city = $_POST['collected_data'][get_option('ideal_form_city')]; 
	}

	if($_POST['collected_data'][get_option('ideal_form_country')] != ''){ 
		$country = $_POST['collected_data'][get_option('ideal_form_country')][0];
		$country = $wpdb->get_var("SELECT country FROM {$wpdb->prefix}currency_list WHERE isocode='{$country}'");
	}

	if($_POST['collected_data'][get_option('ideal_form_first_name')] != ''){
		$name = $_POST['collected_data'][get_option('ideal_form_first_name')]." ".$_POST['collected_data'][get_option('ideal_form_last_name')];
	}
?>
<body onload="setTimeout('submit_purchase()',50)">
<script type="text/javascript">
var Amount = <?php echo $amount; ?>;
var PSPID = "<?php echo get_option('ideal_id');?>";
var AM;
if (isNaN(Amount)) {
	alert("Amount not a number: " + Amount + " !");
	AM = "";
} else {
	AM = Math.round(parseFloat(Amount)*100);
}
</script>
<form method='post' action='<?php echo $submiturl;?>' id='ideal_form' name='ideal_form'>
<script type="text/javascript">
document.write("<input type=\"hidden\" NAME=\"PSPID\" value=\"" + PSPID + "\" />");
document.write("<input type=\"hidden\" NAME=\"amount\" value=\"" + AM + "\" />");
</script>
<input type="hidden" NAME="orderID" value="<?php echo $purchase_log[0]['id'];?>" />
<input type="hidden" name="currency" value="<?php echo get_option('ideal_currency');?>" />
<input type="hidden" name="language" value="<?php echo get_option('ideal_language');?>" />
<input type="hidden" name="accepturl" value="<?php echo get_option('product_list_url');?>">
<input type="hidden" name="cancelurl" value="<?php echo get_option('shopping_cart_url');?>">
<!--customer information starts-->
<input type="hidden" name="CN" value="<?=$name;?>">
<input type="hidden" name="EMAIL" value="<?=$email;?>">
<input type="hidden" name="ownerZIP" value="<?=$postcode;?>">
<input type="hidden" name="owneraddress" value="<?=$address;?>">
<input type="hidden" name="ownercty" value="<?=$country;?>">
<input type="hidden" name="ownertown" value="<?=$city;?>">
<input type="hidden" name="ownertelno" value="<?=$phone;?>">
<!--customer information ends-->
<input type="hidden" name="PM" value="iDEAL" />
</form>
</body>
<?php
// 	$fields = "PSPID=".get_option('ideal_id')."&orderID=".$sessionid."&amount=".$amount."&PM=iDEAL&language=".get_option('ideal_language')."&currency=".get_option('ideal_currency');
// 	exit($fields);
// 	header("Location:". $submiturl.$fields);
// 	exit($result);
}

function submit_ideal() {
	if($_POST['ideal_id'] != null) {
		update_option('ideal_id', $_POST['ideal_id']);
	}
	if($_POST['ideal_currency'] != null) {
		update_option('ideal_currency', $_POST['ideal_currency']);
	}
	if($_POST['ideal_language'] != null) {
		update_option('ideal_language', $_POST['ideal_language']);
	}
	foreach((array)$_POST['ideal_form'] as $form => $value) {
		update_option(('ideal_form_'.$form), $value);
	}
	return true;
}

function form_ideal() {
	if (get_option('ideal_language') == 'en_US'){
		$language1="selected";
	} else if (get_option('ideal_language') == 'nl_NL') {
		$language2="selected";
	} else if (get_option('ideal_language') == 'fr_FR') {
		$language3="selected";
	}
	
	if (get_option('ideal_currency') == 'EUR'){
		$currency1="selected";
	} else if(get_option('ideal_currency') == 'USD') {
		$currency2="selected";
	} else if(get_option('ideal_currency') == 'GBP') {
		$currency3="selected";
	}
	$output = "
	<tr>
		<td>
			iDeal PSPID
		</td>
		<td>
			<input type='text' size='20' value='".get_option('ideal_id')."' name='ideal_id' />
		</td>
	</tr>
	<tr>
		<td>
			iDeal Currency
		</td>
		<td>
			<select value='".get_option('ideal_currency')."' name='ideal_currency'>
				<option $currency1 value='EUR'> EUR </option>
				<option $currency2 value='USD'> USD </option>
				<option $currency3 value='GBP'> GBP </option>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			iDeal Language
		</td>
		<td>
			<select value='".get_option('ideal_language')."' name='ideal_language'>
				<option $language1 value='en_US'> English </option>
				<option $language2 value='nl_NL'> Dutch </option>
				<option $language3 value='fr_FR'> Français </option>
			</select>
		</td>
	</tr>";
	$output.="<h2>Forms Sent to Gateway</h2>
		<table>
			<tr>
				<td>
				First Name Field
				</td>
				<td>
				<select name='ideal_form[first_name]'>
				".nzshpcrt_form_field_list(get_option('ideal_form_first_name'))."
				</select>
				</td>
		</tr>
			<tr>
				<td>
				Last Name Field
				</td>
				<td>
				<select name='ideal_form[last_name]'>
				".nzshpcrt_form_field_list(get_option('ideal_form_last_name'))."
				</select>
				</td>
		</tr>
		<tr>
				<td>
				Email Field
				</td>
				<td>
				<select name='ideal_form[email]'>
				".nzshpcrt_form_field_list(get_option('ideal_form_email'))."
				</select>
				</td>
		</tr>
			<tr>
				<td>
				Address Field
				</td>
				<td>
				<select name='ideal_form[address]'>
				".nzshpcrt_form_field_list(get_option('ideal_form_address'))."
				</select>
				</td>
		</tr>
		<tr>
				<td>
				City Field
				</td>
				<td>
				<select name='ideal_form[city]'>
				".nzshpcrt_form_field_list(get_option('ideal_form_city'))."
				</select>
				</td>
		</tr>
		<tr>
				<td>
				State Field
				</td>
				<td>
				<select name='ideal_form[state]'>
				".nzshpcrt_form_field_list(get_option('ideal_form_state'))."
				</select>
				</td>
		</tr>
		<tr>
				<td>
				Postal code/Zip code Field
				</td>
				<td>
				<select name='ideal_form[post_code]'>
				".nzshpcrt_form_field_list(get_option('ideal_form_post_code'))."
				</select>
				</td>
		</tr>
		<tr>
				<td>
				Country Field
				</td>
				<td>
				<select name='ideal_form[country]'>
				".nzshpcrt_form_field_list(get_option('ideal_form_country'))."
				</select>
				</td>
		</tr>
	</table> ";
	return $output;
}
?>