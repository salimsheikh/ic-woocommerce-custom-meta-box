<?php
/*
Plugin Name: WooCommerce Custom Meta Box
Description: Adds a custom meta box to the WooCommerce order edit page for adding and saving custom order meta data.
Version: 1.0
Author: Salim Shaikh
*/

// Exit if accessed directly to prevent direct access to the script
if (!defined('ABSPATH')) {
    exit;
}

// Add meta box to WooCommerce order edit page
function custom_add_order_meta_box() {
	
	// Determine the screen ID to decide where to display the meta box
	$screen =  get_current_screen()->id == 'woocommerce_page_wc-orders' ? 'woocommerce_page_wc-orders' : 'shop_order';
	
	// Add meta box to the 'shop_order' screen or 'woocommerce_page_wc-orders'
	if($screen == "shop_order" || $screen == "woocommerce_page_wc-orders"){
		add_meta_box(
			'custom_order_meta_box',        // Unique ID for the meta box
			'Custom Order Meta',            // Title of the meta box
			'custom_order_meta_box_content',// Callback function to display content
			$screen,                   		// The screen (post type) on which to show the box
			'side',                         // Context: 'normal', 'side', 'advanced'
			'default'                       // Priority: 'high', 'core', 'default', 'low'
		);
	}
    
}
// Hook the custom_add_order_meta_box function to the add_meta_boxes action
add_action( 'add_meta_boxes', 'custom_add_order_meta_box' );

// Callback function to display the content of the custom meta box
function custom_order_meta_box_content( $object  ) {
	
	$order_id = 0;
	// Check if the $object is a WP_Post object
	if(is_a( $object, 'WP_Post' )){
		$order_id = $object->ID;
		// Alternative approach to retrieve existing meta value
    	//$custom_meta_value = get_post_meta( $post->ID, '_custom_meta_key', true );
	} else {
		$order = $object;
		$order_id = $order->get_id();
	}
	
	// Get the WooCommerce order object
	$order = is_a( $object, 'WP_Post' ) ? wc_get_order( $object->ID ) : $object;
	
	// If no order is found, exit the function
	if(!$order){
		return false;
	}
	
    // Retrieve existing value from the order meta if available
    $custom_meta_value = $order->get_meta('_custom_meta_key', true );

    // Display the form field for inputting custom meta value
    echo '<label for="custom_meta_field">Custom Meta:</label>';
    echo ' <input type="text" id="custom_meta_field" name="custom_meta_field" value="' . esc_attr( $custom_meta_value ) . '" />';
}

// Function to save custom meta box data when the order is saved
function custom_save_order_meta_data( $post_id ) {
    // Check if this is an autosave to prevent overriding data
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Verify that the current user has permission to edit the post
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Ensure the post type is 'shop_order' before proceeding
    if ( 'shop_order' != get_post_type( $post_id ) ) {
        return;
    }
	
	// Temporarily remove the save_post action to prevent recursion
	remove_action( 'save_post', 'custom_save_order_meta_data' );

    // Check if the custom meta field is set and save the data
    if ( isset( $_POST['custom_meta_field'] ) ) {
        $custom_meta_value = sanitize_text_field( $_POST['custom_meta_field'] );
        $order = wc_get_order( $post_id );
        if ( $order ) {
            $order->update_meta_data( '_custom_meta_key', $custom_meta_value );
            $order->save();
        }
    }
}
// Hook the custom_save_order_meta_data function to the save_post action
add_action( 'save_post', 'custom_save_order_meta_data' );