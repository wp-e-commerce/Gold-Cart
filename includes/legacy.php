<?php
if( !function_exists( 'gold_shpcrt_display_gallery' ) ) {
	function gold_shpcrt_display_gallery( $product_id = 0, $invisible = false ) {

		_wpsc_deprecated_function( __FUNCTION__, '3.8', 'wpsc_gc_shpcrt_display_gallery' );
		return wpsc_gc_shpcrt_display_gallery( $product_id, $invisible );

	}
}

if( !function_exists( 'product_display_list' ) ) {
	function product_display_list( $product_list, $group_type, $group_sql = '', $search_sql = '' ) {

		_wpsc_deprecated_function( __FUNCTION__, '3.8', 'wpsc_gc_product_display_list' );
		return wpsc_gc_product_display_list( $product_list, $group_type, $group_sql = '', $search_sql = '' );

	}
}

if( !function_exists( 'product_display_grid' ) ) {
	function product_display_grid( $product_list, $group_type, $group_sql = '', $search_sql = '' ) {

		_wpsc_deprecated_function( __FUNCTION__, '3.8', 'wpsc_gc_product_display_grid' );
		return wpsc_gc_product_display_grid( $product_list, $group_type, $group_sql = '', $search_sql = '' );

	}
}
?>