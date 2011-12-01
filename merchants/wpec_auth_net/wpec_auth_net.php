<?php
/*
Plugin Name: Wpec Authorize.net plugin
Plugin URI: http://www.analogrithems.com/rant/2010/12/17/wordpress-ecommerce-authorize-net-merchant-plugin/
Description: This plugin allows you to use authorize.net as merchant gateway for wordpress ecommerce plugin
Version: 0.2
Author: Analogrithems
Author URI: http://www.analogrithems.com
*/

define('WPECAUTHNET_PLUGIN_NAME','wpec_auth_net');
define('WPECAUTHNET_ADMIN',plugin_dir_path( __FILE__ ) . 'admin/');
define('WPECAUTHNET_CLASSES', plugin_dir_path( __FILE__ ) . 'classes/');


class wpec_auth_net_setup{

	var $myGateway;

        function __construct(){
                /* The activation hook is executed when the plugin is activated. */
                register_activation_hook(__FILE__,array($this, 'activate_plugin'));

                /* The deactivation hook is executed when the plugin is deactivated */
                register_deactivation_hook(__FILE__,array($this, 'deactivate_plugin'));

                add_action('wpsc_init',array($this,'wpsc_init'));
		        }

	//Standard practice, always have a place to do work during activation and deactivation of the plugin.
	function register_activation_hook(){
	}
	function deactivate_plugin(){
	}

	
	function wpsc_init(){
		include_once(WPECAUTHNET_CLASSES.'wpec_auth_net.class.php');
		include_once('user_profile.saved_accouns.php');
		add_action('wpsc_before_shipping_of_shopping_cart', array($this, 'wpec_auth_net_load'));
		if(is_admin()){
			//Cleaning this code, the following will be removed soon
			add_action('wpsc_edit_order_status', array($this,'edit_status'));
			//Edit the order to perhaps remove something
			if ( isset($_REQUEST['wpsc_admin_action']) && $_REQUEST['wpsc_admin_action'] == 'wpec_auth_net_capture_preauth' ){
				add_action( 'admin_init', array($this,'capture_preauth'));
			}
			//Talk with the order management plugin to get credit card changes
			add_action('wpscusermanagement_commit_changes',array($this, 'oms_payment'));
			add_filter('wpscusermanagement_commit_changes_payment_gateway', array($this, 'oms_payment_form'),10,2);

		}

	}

	function oms_payment_form($payment_gateway,$order){
		global $user_ID;
		
		//Save the current user and switch it to get the user
		//we are trying to modify
		$temp_user = $user_ID;
		$user_ID = $order['orderID'];
		$oms = new wpec_auth_net();
		$form = $payment_gateway . $oms->CheckOrCC();
		$user_ID = $temp_user;
		return $form;
	}


	/**
	* oms_payment - this is a hook for the wpsc user management plugins order management feature
	* The plugin allows you to update a users order after checkout.  In order for the order to be able
	* to credit or debit the user for changes made to the order we must hook to the commitChanges via the 
	* wpscusermanagement_commit_changes action
	*
	* @param mixed $order
	* @return boolean [true | false] if the update worked.
	*/
	function oms_payment($order = false){
		return true;

	}

	//Capture funds for order that was preauthed
	function edit_status($order=false){
		extract($order);
		if($purchlog_data['gateway'] == 'wpec_auth_net'){
			$auth_net = new wpec_auth_net($purchlog_id);
			if(!$auth_net->beenCaptured($purchlog_id)){
				if($auth_net->capturePreAuth($order)){
					return true;
				}else{ 
					//Kill THis processes so this order status can't get updated
					die('Failed to capture payment');
				}
			}
		}
	}

	function wpec_auth_net_load(){
		global $nzshpcrt_gateways, $gateway_checkout_form_fields, $num;
		//Hook into the My Account so the user can manage their saved credit cards and bank accounts
		if ( is_user_logged_in() ) {
			if ( in_array( WPECAUTHNET_PLUGIN_NAME, (array)get_option( 'custom_gateway_options' ) ) ) {
				$this->myGateway = new wpec_auth_net();
				$checkOrCC = $this->myGateway->CheckOrCC();
				$gateway_checkout_form_fields[WPECAUTHNET_PLUGIN_NAME] = "
				<tr><td colspan='2'>
				{$checkOrCC}
				</td>
				</tr>
				";
			}
		}else{
			//Not Logged In, disable CIM and ARB
			if ( in_array( WPECAUTHNET_PLUGIN_NAME, (array)get_option( 'custom_gateway_options' ) ) ) {
				$this->myGateway = new wpec_auth_net();
				$this->myGateway->conf['cimon'] = false;
				$checkOrCC = $this->myGateway->CheckOrCC();
				$gateway_checkout_form_fields[WPECAUTHNET_PLUGIN_NAME] = "
				<tr><td colspan='2'>
				{$checkOrCC}
				</td>
				</tr>
				";
			}
		}
	}


	function capture_preauth(){
		$id = isset($_REQUEST['purchaselog_id']) ? $_REQUEST['purchaselog_id'] : '0';
		return wpsc_purchlog_edit_status($id,3);
	}

}

//This starts the module up
$wpec_authorize_net = new wpec_auth_net_setup();

?>
