<?php
$nzshpcrt_gateways[$num]['name'] = 'Paystation';
$nzshpcrt_gateways[$num]['internalname'] = 'paystation';
$nzshpcrt_gateways[$num]['function'] = 'gateway_paystation';
$nzshpcrt_gateways[$num]['form'] = "form_paystation";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_paystation";
$nzshpcrt_gateways[$num]['payment_type'] = "credit_card";

function gateway_paystation($seperator, $sessionid){
  
	$price =  number_format(nzshpcrt_overall_total_price($_SESSION['delivery_country']), 2, '', ',');
	$url = "https://www.paystation.co.nz/dart/darthttp.dll?paystation&pi=".get_option('paystation_id')."&ms=".	$sessionid."&am=".$price."";
	$_SESSION['checkoutdata'] = '';
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
      Paystation ID
      </td>
      <td>
      <input type='text' size='40' value='".get_option('paystation_id')."' name='paystation_id' />
      </td>
    </tr>";
}
?>