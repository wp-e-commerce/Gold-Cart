<?php
do_action('wpsc_gold_module_activation');
		
?>
<div class='wrap'>
	<div class='metabox-holder wpsc_gold_side'>
		<?php
		/* ADDITIONAL GOLD CART MODULES SECTION
		 * ADDED 18-06-09
		 */
		?>
		<strong><?php _e('WP e-Commerce Modules'); ?></strong><br />
		<span><?php _e('Add more functionality to your e-Commerce site'); ?><input type='button' class='button-primary' onclick='window.open ("http://getshopped.org/extend/premium-upgrades/","mywindow"); ' value='Go to Shop' id='visitInstinct' name='visitInstinct' /></span>
		
		<br />
		<div class='wpsc_gold_module'>
			<br />
			<strong><?php _e('Pure Gold'); ?></strong>
			<p class='wpsc_gold_text'>Add Products search &amp; additional payment gateways to your e-Commerce install</p>
			<span class='wpsc_gold_info'>$25</span>
		</div>
		<div class='wpsc_gold_module'>
			<br />
			<strong><?php _e('DropShop'); ?></strong>
			<p class='wpsc_gold_text'>Impress your customers with a sliding DropShop </p>
			<span class='wpsc_gold_info'>$75</span>
		</div>
		<div class='wpsc_gold_module'>
			<br />
			<strong><?php _e('Grid View'); ?> </strong>
			<p class='wpsc_gold_text'>Change the layout of your shop with this 960 inspired grid view.</p>
			<span class='wpsc_gold_info'>$15</span>
		</div>
		<div class='wpsc_gold_module'>
			<br />
			<strong><?php _e('MP3 Player'); ?></strong>
			<p class='wpsc_gold_text'>Selling music? Then this is the module for you!</p>
			<span class='wpsc_gold_info'>$10</span>
		</div>
		<div class='wpsc_gold_module'>
			<br />
			<strong><?php _e('Members Only Module'); ?> </strong>
			<p class='wpsc_gold_text'>Private Articles and Images are your business? Sell them with ease using this module.</p>
			<span class='wpsc_gold_info'>$10</span>
		</div>
		<div class='wpsc_gold_module'>
			<br />
			<strong><?php _e('Product Slider'); ?> </strong>
			<p class='wpsc_gold_text'>Display your products in a new and fancy way using the "Product Slider" module.</p>
			<span class='wpsc_gold_info'>$25</span>
		</div>
		<div class='wpsc_gold_module'>
			<br />
			<strong><?php _e('NextGen Gallery Buy Now Buttons'); ?> </strong>
			<p class='wpsc_gold_text'>Make your Online photo gallery into an e-Commerce solution.</p>
			<span class='wpsc_gold_info'>$10</span>
		</div>
	</div>

<div class='wpsc_gold_float'>
<div class='metabox-holder'>
  <h2><?php echo TXT_WPSC_GOLD_OPTIONS;?></h2>
  <form method='post' id='gold_cart_form' action=''>
     <div class='postbox'>
     	<h3 class='hndle'><?php echo TXT_WPSC_ACTIVATE_SETTINGS;?></h3>
		  <?php 
			if(get_option('activation_state') == "true"){
		  ?>
		  		<p><img align='middle' src='../wp-content/plugins/<?php echo WPSC_DIR_NAME; ?>/images/tick.png' alt='' title='' />
		  		&nbsp;The gold cart is currently activated.</p>
		  <?php
		    } else{ 
		  ?>
		    	<p><img align='middle' src='../wp-content/plugins/<?php echo WPSC_DIR_NAME; ?>/images/cross.png' alt='' title=''/>
		    	&nbsp;The gold cart is currently deactivated.</p>
		  <?php
		   }
		  ?>
			<p>
			      <label for='activation_name'><?php echo TXT_WPSC_NAME;?>:</label>
			      <input class='text' type='text' size='40' value='<?php echo get_option('activation_name'); ?>' name='activation_name' id='activation_name' />
			</p>
			<p>
			      <label for='activation_key'><?php echo TXT_WPSC_ACTIVATION_KEY;?>:</label>
			
			      <input class='text' type='text' size='40' value='<?php echo get_option('activation_key'); ?>' name='activation_key' id='activation_key' />
			</p>
			<p>
			      <input type='submit' class='button-primary' value='<?php echo TXT_WPSC_SUBMIT;?>' name='submit_values' />
			      <input type='submit' class='button' value='<?php echo TXT_WPSC_RESET_API;?>' name='reset_values' onclick='document.getElementById("activation_key").value=""' />
			</p>
    </div>
<?php
do_action('wpsc_gold_module_activation_forms');
?>
</form>


</div> 
</div>
</div>
