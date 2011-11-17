<?php
if ( is_user_logged_in() ) {
	global $separator, $wpec_auth_net_user_profile_url;
	add_action('wpsc_additional_user_profile_links', 'wpec_auth_net_user_profile_display_links');
	$wpec_auth_net_user_profile_url = get_option( 'user_account_url' ) . $separator . "wpec_auth_net_user_profile=true";
	function wpec_auth_net_user_profile_display_links($div){
		global $separator, $wpec_auth_net_user_profile_url;
		echo "{$div} <a href='{$wpec_auth_net_user_profile_url}'>".__('Saved Credit Card, Bank or Shipping Information','wpsc')."</a> ";
	}

	//We sneak right infront of user profile page filter
	add_filter( 'the_content', 'wpec_auth_net_user_profile_hook', 11 );
	function wpec_auth_net_user_profile_hook( $content ){
	
		if ( preg_match( "/\[userlog\]/", $content ) &&  isset($_REQUEST['wpec_auth_net_user_profile'])) {
			define( 'DONOTCACHEPAGE', true );

		if(isGood($_REQUEST['submit']) && isGood($_REQUEST['type']) && isGood($_REQUEST['auth_net']['payment_preset']) ){
			wpec_auth_net_user_profile_remove($_REQUEST['type'], $_REQUEST['auth_net']['payment_preset']);
		}
                ob_start();
		wpec_auth_net_user_profile_display();
                $content = ob_get_contents();
                ob_end_clean();
		return $content;

		} else {
			return $content;
		}


	}

	function wpec_auth_net_user_profile_remove( $type, $id ){
		if(is_array($id)){
			foreach($id as $single){
				wpec_auth_delete($type,$single);
			}
		}else{
			wpec_auth_delete($type,$id);
		}
	}

	function wpec_auth_delete($type,$id){

		$myGateway = new wpec_auth_net();
		if($type!='shippingadress'){
			$result = $myGateway->deletePay($id);
			if($result === false){
				$_SESSION['wpec_message'] = $result;
			}else{
				$_SESSION['wpec_message'] = __('Saved Payment Details Have Been Deleted.','wpsc');
			}
		}else{
			$result = $myGateway->deleteShip($id);
			if($result === false){
				$_SESSION['wpec_message'] = $result;
			}else{
                                $_SESSION['wpec_message'] = __('Saved Shipping Details Have Been Deleted.','wpsc');
                        }
		}
	}

	function wpec_auth_net_user_profile_display(){
		$myGateway = new wpec_auth_net();

		$creditcards = $myGateway->getCreditCardProfiles();
		$bankaccounts = $myGateway->getBankAccountProfiles();
		$shipaddress = $myGateway->getShippingProfiles();
		if ( class_exists('WPSC_Subscription') )
			$subs = new WPSC_Subscription();
		?>
		<div id='wpec_auth_net_user_profile_manager'>
		<h2><?php _e('Saved Credit Card, Bank and Shipping Information','wpsc');?></h2>
		<?php if(isGood($_SESSION['wpec_message'])){ ?>
			<div class='notice'><?php echo $_SESSION['wpec_message'];?></div>
		<?php 
			unset($_SESSION['wpec_message']);
		} 
		if($bankaccounts){ ?>
		<form action="<?php echo $wpec_auth_net_user_profile_url;?>" method="post">
			<div id='bankaccounts'class='sectionBox'>
			<span class="sectionHeader"><?php _e("Bank Accounts You've Saved For Easy Checkout.",'wpsc'); ?></span>
			<div class="displayList"><?php echo $bankaccounts; ?> </div>
			<input type='hidden' name='type' value='bankaccounts'>
			<input type='submit' name='submit' class='btn' value='Delete'>
			</div>
		</form>
		<?php } ?>
		<?php if($creditcards){ ?>
		<form action="<?php echo $wpec_auth_net_user_profile_url;?>" method="post">
			<div id='creditcards'class='sectionBox'>
			<span class="sectionHeader"><?php _e("Credit Cards You've Saved For Easy Checkout.",'wpsc'); ?></span>
			<div class="displayList"><?php echo $creditcards; ?> </div>
			<input type='hidden' name='type' value='creditcards'>
			<input type='submit' name='submit' class='btn' value='Delete'>
			</div>
		</form>
		<?php } ?>
		<?php if($shipaddress){ ?>
		<form action="<?php echo $wpec_auth_net_user_profile_url;?>" method="post">
			<div id='shipaddress'class='sectionBox'>
			<span class="sectionHeader"><?php _e("Shipping Addresses You've Saved For Easy Checkout.",'wpsc'); ?></span>
			<div class="displayList"><?php echo $shipaddress; ?> </div>
			<input type='hidden' name='type' value='shippingaddress'>
			<input type='submit' class='btn' name='submit' value='Delete'>
			</div>
		</form>
		<?php }
		 ?>
		</div>
		<?php
	}
}
?>
