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
?>
<div class="live_search_form">
	<form>
		<input name="wpsc_live_search" id="wpsc_search_autocomplete" <?php echo $autocomplete; ?> class="wpsc_live_search" autocomplete="off" />
	</form>
	<div class="blind_down" style="display:none;"></div>
</div>
<?php	
}

?>