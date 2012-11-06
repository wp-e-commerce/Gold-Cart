<?php
$nzshpcrt_gateways[$num]['name'] = __( 'Paystation', 'wpsc_gold_cart' );
$nzshpcrt_gateways[$num]['internalname'] = 'paystation';
$nzshpcrt_gateways[$num]['function'] = 'gateway_paystation';
$nzshpcrt_gateways[$num]['form'] = "form_paystation";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_paystation";
$nzshpcrt_gateways[$num]['payment_type'] = "credit_card";

function gateway_paystation($seperator, $sessionid){

	$price =  number_format( nzshpcrt_overall_total_price( wpsc_get_customer_meta( 'billing_country' ) ), 2, '', ',' );
	$url = "https://www.paystation.co.nz/dart/darthttp.dll?paystation&pi=".get_option('paystation_id')."&ms=".	$sessionid."&am=".$price."";
	header("Location: $url");
	exit();
}


function submit_paystation(){
	if ( ! empty( $_POST['paystation_id'] ) )
		update_option('paystation_id', $_POST['paystation_id']);
	return true;
}

function form_paystation(){
  return "<tr>
      <td>
      ".__( 'Paystation ID', 'wpsc_gold_cart' )."
      </td>
      <td>
      <input type='text' size='40' value='".get_option('paystation_id')."' name='paystation_id' />
      </td>
    </tr>";
}
?>