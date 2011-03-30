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
	$options = array(
		'ideal_id',
		'ideal_currency',
		'ideal_language',
	);
	foreach ( $options as $option ) {
		if ( ! empty( $_POST[$option] ) )
			update_option( $option, $_POST[$option] );
	}
	if ( ! empty( $_POST['ideal_form'] ) ) {
		foreach((array)$_POST['ideal_form'] as $form => $value) {
			update_option(('ideal_form_'.$form), $value);
		}
	}
	return true;
}

function form_ideal() {
	$languages = array(
		'en_US' => 'English',
		'nl_NL' => 'Dutch',
		'fr_FR' => 'Français',
	);

	$currencies = array(
		'EUR',
		'USD',
		'GBP',
	);

	$selected_language = get_option( 'ideal_language' );
	$selected_currency = get_option( 'ideal_currency' );

	ob_start();
?>
<tr>
	<td>
		iDeal PSPID
	</td>
	<td>
		<input type='text' size='20' value='<?php echo esc_attr( get_option('ideal_id') ); ?>' name='ideal_id' />
	</td>
</tr>
<tr>
	<td>
		iDeal Currency
	</td>
	<td>
		<select name='ideal_currency'>
			<?php
			foreach ( $currencies as $currency ) {
				$selected = $currency == $selected_currency ? ' selected="selected"' : '';
				echo "<option{$selected} value='{$currency}'>{$currency}</option>";
			}
			?>
		</select>
	</td>
</tr>
<tr>
	<td>
		iDeal Language
	</td>
	<td>
		<select name='ideal_language'>
			<?php
			foreach ( $languages as $code => $language ) {
				$selected = $language == $selected_language ? ' selected="selected"' : '';
				echo "<option{$selected} value='{$code}'>{$language}</option>";
			}
			?>
		</select>
	</td>
</tr>
<tr>
	<td colspan="2"><h2>Forms Sent to Gateway</h2></td>
</tr>
<tr>
	<td>
	First Name Field
	</td>
	<td>
	<select name='ideal_form[first_name]'>
		<?php echo nzshpcrt_form_field_list( get_option( 'ideal_form_first_name' ) ); ?>
	</select>
	</td>
</tr>
<tr>
	<td>
	Last Name Field
	</td>
	<td>
	<select name='ideal_form[last_name]'>
		<?php echo nzshpcrt_form_field_list( get_option( 'ideal_form_last_name' ) ); ?>
	</select>
	</td>
</tr>
<tr>
	<td>
	Email Field
	</td>
	<td>
	<select name='ideal_form[email]'>
		<?php echo nzshpcrt_form_field_list( get_option( 'ideal_form_email' ) ); ?>
	</select>
	</td>
</tr>
<tr>
	<td>
	Address Field
	</td>
	<td>
	<select name='ideal_form[address]'>
		<?php echo nzshpcrt_form_field_list( get_option( 'ideal_form_address' ) ); ?>
	</select>
	</td>
</tr>
<tr>
	<td>
	City Field
	</td>
	<td>
	<select name='ideal_form[city]'>
		<?php echo nzshpcrt_form_field_list( get_option( 'ideal_form_city' ) ); ?>
	</select>
	</td>
</tr>
<tr>
	<td>
	State Field
	</td>
	<td>
	<select name='ideal_form[state]'>
		<?php echo nzshpcrt_form_field_list( get_option( 'ideal_form_state' ) ); ?>
	</select>
	</td>
</tr>
<tr>
	<td>
	Postal code/Zip code Field
	</td>
	<td>
	<select name='ideal_form[post_code]'>
		<?php echo nzshpcrt_form_field_list( get_option('ideal_form_post_code' ) ); ?>
	</select>
	</td>
</tr>
<tr>
	<td>
	Country Field
	</td>
	<td>
	<select name='ideal_form[country]'>
		<?php echo nzshpcrt_form_field_list( get_option( 'ideal_form_country' ) ); ?>
	</select>
	</td>
</tr>
<?php
	return ob_get_clean();
}
?>