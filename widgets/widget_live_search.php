<?php

/**
 * WPSC_Live_Search Class
 */
class WPSC_Live_Search extends WP_Widget {
    /** constructor */
    function WPSC_Live_Search() {
        parent::WP_Widget(false, $name = 'wp-e-commerce Live Search');	
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {		
        extract( $args );
        
        $title = $instance['title'];
        if( empty( $title ) )
            $title = __('Live search', 'wpsc');
 
        $title = apply_filters('widget_title', $title);
        ?>
              <?php echo $before_widget; ?>
                  <?php echo $before_title; ?>
                      <?php echo $title; ?>
                  <?php echo $after_title; ?>
                  <?php wpsc_live_search(); ?>
              <?php echo $after_widget; ?>
        <?php
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
	$instance = $old_instance;
	$instance['title'] = strip_tags($new_instance['title']);
        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
        $title = isset( $instance['title'] ) ? esc_attr($instance['title']) : '';
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
        <?php 
    }

} // class WPSC_Live_Search

register_widget("WPSC_Live_Search");

function wpsc_live_search(){
	if ( (float)WPSC_VERSION < 3.8 )
		$autocomplete = 'onkeyup="autocomplete(event)"';
	else
		$autocomplete = '';
		
	//get the url to submit the search to
	$product_page_id = wpec_get_the_post_id_by_shortcode('[productspage]');
	$pp_url = get_permalink($product_page_id);
	// the js below listens for the enter keypress and redirects to the product page with a get var of the search term

?>
<div class="live_search_form">
	
		<input name="product_search" id="wpsc_search_autocomplete" <?php echo $autocomplete; ?> class="wpsc_live_search" autocomplete="off" />
	
	
	<script type='text/javascript' > /* <![CDATA[ */
	jQuery('#wpsc_search_autocomplete').keypress(function(e){
		if(e.keyCode == 13){
			var url = '<?php echo $pp_url ?>'+'?product_search='+jQuery(this).val();
			url = encodeURI(url);
			jQuery(window.location).attr('href', url);
		}
	})

	/* ]]> */</script>
	<div class="blind_down" style="display:none;"></div>
</div>

<?php	
}

