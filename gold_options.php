<?php do_action('wpsc_gold_module_activation'); ?>
<div class='wrap'>
	<div class='metabox-holder wpsc_gold_side'>
		<?php
		/* ADDITIONAL GOLD CART MODULES SECTION
		 * ADDED 18-06-09
		 */
		?>
		<strong><?php _e( 'WP e-Commerce Modules', 'wpsc_gold_cart' ); ?></strong><br />
		<span><?php _e( 'Add more functionality to your e-Commerce site', 'wpsc_gold_cart' ); ?><input type='button' class='button-primary' onclick='window.open ("http://getshopped.org/extend/premium-upgrades/","mywindow"); ' value='<?php _e( 'Go to Shop', 'wpsc_gold_cart' ); ?>' id='visitInstinct' name='visitInstinct' /></span>
		<br />
		<div class='wpsc_gold_module'>
			<br />
			<strong><?php _e( 'Pure Gold', 'wpsc_gold_cart' ); ?></strong>
			<p class='wpsc_gold_text'><?php _e( 'Add Products search &amp; additional payment gateways to your e-Commerce install' ); ?></p>
			<span class='wpsc_gold_info'>$25</span>
		</div>
		<div class='wpsc_gold_module'>
			<br />
			<strong><?php _e( 'DropShop', 'wpsc_gold_cart' ); ?></strong>
			<p class='wpsc_gold_text'><?php _e( 'Impress your customers with a sliding DropShop', 'wpsc_gold_cart' ); ?></p>
			<span class='wpsc_gold_info'>$75</span>
		</div>
		<div class='wpsc_gold_module'>
			<br />
			<strong><?php _e( 'Grid View', 'wpsc_gold_cart' ); ?> </strong>
			<p class='wpsc_gold_text'><?php _e( 'Change the layout of your shop with this 960 inspired grid view.', 'wpsc_gold_cart' ); ?></p>
			<span class='wpsc_gold_info'>$15</span>
		</div>
		<div class='wpsc_gold_module'>
			<br />
			<strong><?php _e( 'MP3 Player', 'wpsc_gold_cart' ); ?></strong>
			<p class='wpsc_gold_text'><?php _e( 'Selling music? Then this is the module for you!', 'wpsc_gold_cart' ); ?></p>
			<span class='wpsc_gold_info'>$10</span>
		</div>
		<div class='wpsc_gold_module'>
			<br />
			<strong><?php _e( 'Members Only Module', 'wpsc_gold_cart' ); ?> </strong>
			<p class='wpsc_gold_text'><?php _e( 'Private Articles and Images are your business? Sell them with ease using this module.', 'wpsc_gold_cart' ); ?></p>
			<span class='wpsc_gold_info'>$10</span>
		</div>
		<div class='wpsc_gold_module'>
			<br />
			<strong><?php _e( 'Product Slider', 'wpsc_gold_cart' ); ?> </strong>
			<p class='wpsc_gold_text'><?php _e( 'Display your products in a new and fancy way using the "Product Slider" module.', 'wpsc_gold_cart' ); ?></p>
			<span class='wpsc_gold_info'>$25</span>
		</div>
		<div class='wpsc_gold_module'>
			<br />
			<strong><?php _e( 'NextGen Gallery Buy Now Buttons', 'wpsc_gold_cart' ); ?> </strong>
			<p class='wpsc_gold_text'><?php _e( 'Make your Online photo gallery into an e-Commerce solution.', 'wpsc_gold_cart' ); ?></p>
			<span class='wpsc_gold_info'>$10</span>
		</div>
	</div>
	<div class='wpsc_gold_float'>
		<div class='metabox-holder'>
		  <h2><?php echo TXT_WPSC_GOLD_OPTIONS;?></h2>
		  <form method='post' id='gold_cart_form' action=''>
		     <div class='postbox'>
		     	<h3 class='hndle'><?php echo TXT_WPSC_ACTIVATE_SETTINGS;?></h3>
				  <?php if ( get_option('activation_state') == "true" ) { ?>
			  	<p>
			  		<img align='middle' src='../wp-content/plugins/<?php echo WPSC_DIR_NAME; ?>/images/tick.png' alt='' title='' />
			  		&nbsp;<?php _e( 'The gold cart is currently activated.', 'wpsc_gold_cart' ); ?>
			  	</p>
				  <?php } else{ ?>
			    <p>
			    	<img align='middle' src='../wp-content/plugins/<?php echo WPSC_DIR_NAME; ?>/images/cross.png' alt='' title=''/>
			    	&nbsp;<?php _e( 'The gold cart is currently deactivated.', 'wpsc_gold_cart' ); ?>
			  	</p>
				  <?php } ?>
					<p>
			      <label for='activation_name'><?php echo TXT_WPSC_NAME;?>:</label>
			      <input class='text' type='text' size='40' value='<?php echo get_option( 'activation_name' ); ?>' name='activation_name' id='activation_name' />
					</p>
					<p>
						<label for='activation_key'><?php echo TXT_WPSC_ACTIVATION_KEY;?>:</label>
						<input class='text' type='text' size='40' value='<?php echo get_option( 'activation_key' ); ?>' name='activation_key' id='activation_key' />
					</p>
					<p>
			      <input type='submit' class='button-primary' value='<?php echo TXT_WPSC_SUBMIT;?>' name='submit_values' />
			      <input type='submit' class='button' value='<?php echo TXT_WPSC_RESET_API;?>' name='reset_values' onclick='document.getElementById("activation_key").value=""' />
					</p>
		    </div>
				<?php do_action('wpsc_gold_module_activation_forms'); ?>
			</form>
		</div> 
	</div>
</div>