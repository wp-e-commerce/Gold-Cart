<?php
/*
Class Name: wpec auto upgrade
Description Auto Upgrade Class
Author: Instinct
Version: 1.0
Author URI: http://screamingcodemonkey.com
*/
if(!class_exists('wpec_auto_upgrade')){

class wpec_auto_upgrade {

	var $plugin_name = '';
	var $chk_file_location = '';
	var $safe_name = '';
	var $upgrade_url = 'http://example.com';
	var $current_version = '';
	var $plugin_basename = '';	
	function wpec_auto_upgrade($args){
		$this->init($args);
	}
	
	function init($args){
		$this->plugin_name = $args['plugin_name'];
		$this->safe_name = strtolower($this->plugin_name);
		$this->chk_file_location = $args['chk_file_location'];
		$this->current_version = $args['current_version'];
		$this->plugin_basename = $args['plugin_basename'];

		if(isset($_POST['upgrade_url']))
			add_action('init' , array( $this, 'save_update_urls' ) );
		
		$this->set_upgrade_url();			
		add_action('in_admin_header', array( $this, 'add_meta_boxes' ) );
		add_action( 'after_plugin_row', array( $this, 'check_version' ) );
		add_action('update-custom_wpec-premium-plugin' , array( $this, 'start_the_update' ) );			
	}
	
	function set_upgrade_url(){
		$update_urls = get_option('wpsc_upgrade_urls');
		if(isset($update_urls[$this->safe_name]) && !empty( $update_urls[$this->safe_name] ) )
			$this->upgrade_url = $update_urls[$this->safe_name];		
	}
	
	function save_update_urls(){
		if(!empty($_POST['upgrade_url'])){
			$upgrade_urls = array();
			foreach( (array)$_POST['upgrade_url'] as $key=>$upgrade_url){
				$upgrade_urls[$key] = wp_filter_nohtml_kses($upgrade_url);
			}
			update_option('wpsc_upgrade_urls', $upgrade_urls);	
		}
	}
	function add_meta_boxes(){
		add_meta_box( 'wpec_'. $this->plugin_name.'upgrade_metabox', $this->plugin_name . ' Update Url', array( $this, 'upgrade_metabox' ), 'wpsc_upgrade_page', 'top' );
	}
	
	function upgrade_metabox(){ 
		$this->set_upgrade_url();
		?>
		<p>
			<label for='<?php echo $this->safe_name; ?>_upgrade_url'><?php _e( 'Update URL:', 'wpsc' ); ?></label>
			<input class='text' type='text' size='40' value='<?php echo $this->upgrade_url; ?>' name='upgrade_url[<?php echo $this->safe_name; ?>]' id='<?php echo $this->safe_name; ?>_upgrade_url' />
		</p>
		<p>
			<input type='submit' class='button-primary' value='<?php _e( 'Update', 'wpsc' ); ?>' name='submit_values' />
		</p>
	<?php
	}
	
	function check_version( $plugin ) {
		remove_action( "after_plugin_row_$plugin", 'wp_plugin_update_row', 10, 2 );
		//remove_action( 'after_plugin_row', array( $this, 'check_version' ) );
		// We make sure we are at the bottom row of the correct Plugin
		if( strpos( $this->plugin_basename , $plugin ) !== false ) {
			// This is a file you host on your own server more on this later.
			$checkfile = $this->chk_file_location;
			//we open the file using wp function wp_remote_fopen
			$vcheck = wp_remote_fopen($checkfile);
			//If opening the file was successfull

			if( $vcheck ) {
				//get the current plugin version (cause you always 
				//have a constant set with the current version dont you)
				$version = $this->current_version;
				//Split the response from the checkfile by alias
				$status = explode('@', $vcheck);
				//Default checked version
				$theVersion = 0;
				//Default checked message
				$theMessage = '';
				//Default auto update
				$auto_update = '';
				
				//We know in location [1] is our new version number
				if(isset($status[1]))
				$theVersion = $status[1];
				//We know in location [3] is the link to the information on the new release
				if(isset($status[3]))
				$theMessage = $status[3];
				//Compare the checked version with the current version if checked version is larger display notification

				if( (version_compare(strval($theVersion), strval($version), '>') == 1) ) {					
					//Get the ransients stored by WordPress about Plugin Updates
					$current = get_site_transient( 'update_plugins' );
					//Get the Update URL we had the User set.
					$this->set_upgrade_url();

					//If the update url is not empty, and transient of our plugins current version is less than the checked version
					if( (!empty( $this->upgrade_url) && 'http://example.com' !=  $this->upgrade_url ) && ($version < $theVersion ) ){
						//Update the current response, slug
						$current->response[$plugin]->slug = $this->safe_name;
						//Update the current response, version
						$current->response[$plugin]->new_version = $theVersion;
						//Update the current response, package url
						$current->response[$plugin]->package = $this->upgrade_url;
						$current->response[$plugin]->url = $theMessage;
						//Set the transient
						set_site_transient( 'update_plugins' , $current );
						//Set the update URL
						$action = admin_url().'update.php?action=wpec-premium-plugin&amp;plugin='.$plugin;
						//Set the auto update link
						$auto_update = ' <strong>Or</strong> <a href="'.$action.'">Update Automatically</a>';
					}
					//should add nonce here
					//Display the notification row
					echo '
					<td colspan="5" class="plugin-update" style="line-height:1.2em; font-size:11px; padding:1px;">
					<div style="color:#000; font-weight:bold; margin:4px; padding:6px 5px; background-color:#fffbe4; border-color:#dfdfdf; border-width:1px; border-style:solid; -moz-border-radius:5px; -khtml-border-radius:5px; -webkit-border-radius:5px; border-radius:5px;">'.sprintf(__("There is a new version of %s available.", "wpsc") , $this->plugin_name).' <a href="'.$theMessage.'" target="_blank">View version '.$theVersion.' details</a>'.$auto_update.'</div>
					</td>';
				}
			}
		}
	}
	
	function start_the_update(){
		$plugin = isset($_REQUEST['plugin']) ? trim($_REQUEST['plugin']) : '';
		if ( ! current_user_can('update_plugins') )
			wp_die(__('You do not have sufficient permissions to update plugins for this site.'));
		$title = __('Update Plugin');
		$parent_file = 'plugins.php';
		$submenu_file = 'plugins.php';
		require_once(ABSPATH . 'wp-admin/admin-header.php');
		$nonce = 'upgrade-plugin_' . $plugin;
		$url = 'update.php?action=upgrade-plugin&plugin=' . $plugin;
		$upgrader = new Plugin_Upgrader( new Plugin_Upgrader_Skin( compact('title', 'nonce', 'url', 'plugin') ) );
		$upgrader->upgrade($plugin);
		include(ABSPATH . 'wp-admin/admin-footer.php');
	}

}
}
?>