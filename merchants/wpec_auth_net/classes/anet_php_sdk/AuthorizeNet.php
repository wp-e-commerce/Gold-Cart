<?php
/**
 * The AuthorizeNet PHP SDK. Include this file in your project.
 *
 * @package AuthorizeNet
 */
if (!class_exists ( 'AuthorizeNetRequest' ) ) {
	require dirname(__FILE__) . '/lib/shared/AuthorizeNetRequest.php';
}
if (!class_exists ( 'AuthorizeNetCustomer' ) ) {
require dirname(__FILE__) . '/lib/shared/AuthorizeNetTypes.php';
}
if (!class_exists ( 'AuthorizeNetXMLResponse' ) ) {
require dirname(__FILE__) . '/lib/shared/AuthorizeNetXMLResponse.php';
}
if (!class_exists ( 'AuthorizeNetResponse' ) ) {
require dirname(__FILE__) . '/lib/shared/AuthorizeNetResponse.php';
}
if (!class_exists ( 'AuthorizeNetAIM' ) ) {
require dirname(__FILE__) . '/lib/AuthorizeNetAIM.php';
}
if (!class_exists ( 'AuthorizeNetARB' ) ) {
require dirname(__FILE__) . '/lib/AuthorizeNetARB.php';
}
if (!class_exists ( 'AuthorizeNetCIM' ) ) {
require dirname(__FILE__) . '/lib/AuthorizeNetCIM.php';
}
if (!class_exists ( 'AuthorizeNetSIM' ) ) {
require dirname(__FILE__) . '/lib/AuthorizeNetSIM.php';
}
if (!class_exists ( 'AuthorizeNetDPM' ) ) {
require dirname(__FILE__) . '/lib/AuthorizeNetDPM.php';
}
if (!class_exists ( 'AuthorizeNetTD' ) ) {
require dirname(__FILE__) . '/lib/AuthorizeNetTD.php';
}
if (!class_exists ( 'AuthorizeNetSOAP' ) ) {
	if (class_exists("SoapClient")) {
		require dirname(__FILE__) . '/lib/AuthorizeNetSOAP.php';
	}
}
/**
 * Exception class for AuthorizeNet PHP SDK.
 *
 * @package AuthorizeNet
 */
if (!class_exists ( 'AuthorizeNetException' ) ) {
	class AuthorizeNetException extends Exception
	{
	}
}