<?php

function incsub_sbe_get_model() {
	return Incsub_Subscribe_By_Email_Model::get_instance();
}
/**
 * Get the plugin settings
 * 
 * @return Array of settings
 */
function incsub_sbe_get_settings() {
	$settings_handler = Incsub_Subscribe_By_Email_Settings_Handler::get_instance();
	$default = incsub_sbe_get_default_settings();
	return wp_parse_args( $settings_handler->get_settings(), $default );
}

function incsub_sbe_get_settings_handler() {
	return Incsub_Subscribe_By_Email_Settings_Handler::get_instance();
}

/**
 * Get the plugin default settings
 * 
 * @return Array of settings
 */
function incsub_sbe_get_default_settings() {
	$settings_handler = Incsub_Subscribe_By_Email_Settings_Handler::get_instance();
	return $settings_handler->get_default_settings();
}

/**
 * Update the plugin settings
 */
function incsub_sbe_update_settings( $settings ) {
	$settings_handler = Incsub_Subscribe_By_Email_Settings_Handler::get_instance();
	$settings_handler->update_settings( $settings );
}

/**
 * Get the settings slug
 * 
 * @return String
 */
function incsub_sbe_get_settings_slug() {
	$settings_handler = Incsub_Subscribe_By_Email_Settings_Handler::get_instance();
	return $settings_handler->get_settings_slug();
}

/**
 * Get the allowed frequency for the digests
 * 
 * @return Array
 */
function incsub_sbe_get_digest_frequency() {
	$settings_handler = Incsub_Subscribe_By_Email_Settings_Handler::get_instance();
	return $settings_handler->get_frequency();
}

/**
 * Get the allowed frequency times for the digests
 * 
 * @return Array
 */
function incsub_sbe_get_digest_times() {
	$settings_handler = Incsub_Subscribe_By_Email_Settings_Handler::get_instance();
	return $settings_handler->get_time();
}

/**
 * Get the allowed days of week for the digests
 * 
 * @return Array
 */
function incsub_sbe_get_digest_days_of_week() {
	$settings_handler = Incsub_Subscribe_By_Email_Settings_Handler::get_instance();
	return $settings_handler->get_day_of_week();
}

/**
 * Get the Captions for the confirmation Flags
 * 
 * @return Array
 */
function incsub_sbe_get_confirmation_flag_captions() {
	$settings_handler = Incsub_Subscribe_By_Email_Settings_Handler::get_instance();
	return $settings_handler->get_confirmation_flag();
}

function incsub_sbe_get_follow_button_schemas() {
	$settings_handler = incsub_sbe_get_settings_handler();
	return $settings_handler->get_follow_button_schemas();
}

/**
 * Code took and modified from wp-admin/template.php
 */
function sbe_terms_checklist( $post_id = 0, $args = array() ) {
 	$defaults = array(
		'descendants_and_self' => 0,
		'selected_cats' => false,
		'popular_cats' => false,
		'walker' => null,
		'taxonomy' => 'category',
		'checked_ontop' => false,
		'disabled' => false,
		'taxonomy_slug' => '',
		'post_type_slug' => '',
		'base_name' => 'tax_input',
		'indent' => true,
		'tax_in' => 'all'
	);
	$args = apply_filters( 'wp_terms_checklist_args', $args, $post_id );


	extract( wp_parse_args($args, $defaults), EXTR_SKIP );
	
	if ( empty($walker) || !is_a($walker, 'Walker') )
		$walker = new Walker_Category_Checklist;

	$descendants_and_self = (int) $descendants_and_self;

	$args = array('taxonomy' => $taxonomy);

	$tax = get_taxonomy($taxonomy);
	$args['disabled'] = $disabled;

	$args['taxonomy_slug'] = $taxonomy_slug;
	$args['post_type_slug'] = $post_type_slug;
	$args['base_name'] = $base_name;
	$args['indent'] = $indent;
	$args['tax_in'] = $tax_in;

	if ( 'select-all' === $selected_cats || is_array( $selected_cats ) )
		$args['selected_cats'] = $selected_cats;
	elseif ( $post_id )
		$args['selected_cats'] = wp_get_object_terms($post_id, $taxonomy, array_merge($args, array('fields' => 'ids')));
	else
		$args['selected_cats'] = array();

	if ( is_array( $popular_cats ) )
		$args['popular_cats'] = $popular_cats;
	else
		$args['popular_cats'] = get_terms( $taxonomy, array( 'fields' => 'ids', 'orderby' => 'count', 'order' => 'DESC', 'number' => 10, 'hierarchical' => false ) );

	if ( $descendants_and_self ) {
		$categories = (array) get_terms($taxonomy, array( 'child_of' => $descendants_and_self, 'hierarchical' => 0, 'hide_empty' => 0 ) );
		$self = get_term( $descendants_and_self, $taxonomy );
		array_unshift( $categories, $self );
	} else {
		$categories = (array) get_terms($taxonomy, array('get' => 'all'));
	}

	
	if ( $checked_ontop ) {
		// Post process $categories rather than adding an exclude to the get_terms() query to keep the query the same across all posts (for any query cache)
		$checked_categories = array();
		$keys = array_keys( $categories );

		foreach( $keys as $k ) {
			if ( in_array( $categories[$k]->term_id, $args['selected_cats'] ) ) {
				$checked_categories[] = $categories[$k];
				unset( $categories[$k] );
			}
		}

		// Put checked cats on top
		echo call_user_func_array(array(&$walker, 'walk'), array($checked_categories, 0, $args));
	}
	// Then the rest of them
	echo call_user_func_array(array(&$walker, 'walk'), array($categories, 0, $args));
}

function incsub_sbe_download_csv( $sep, $sample = false ) {
    header( 'Content-Type: text/csv' );
    header( 'Content-Disposition: attachment;filename=' . date( 'YmdHi' ) . '.csv' );
    
    $extra_fields_slugs = incsub_sbe_get_extra_fields_slugs();

    sort($extra_fields_slugs);

    echo '"email"' . $sep . '"type"' . $sep . '"note"';
    if ( ! empty( $extra_fields_slugs ) )
    	echo $sep . '"' . implode( '"' . $sep . '"', $extra_fields_slugs ) . '"';
    echo "\n";
    
    $args = array(
        'per_page' => -1
    );

    if ( $sample ) {
    	$subscriptions = array();
    	$_subscription = new stdClass();
    	$_subscription->subscription_email = 'sample_email_1@email.com';
    	$_subscription->subscription_type = __( 'Instant', INCSUB_SBE_PLUGIN_DIR );
    	$_subscription->subscription_note = __( 'Manual Subscription', INCSUB_SBE_PLUGIN_DIR );
    	$subscriptions[0] = new Subscribe_By_Email_Subscriber( $_subscription );

    	$_subscription->subscription_email = 'sample_email_2@email.com';
    	$subscriptions[1] = new Subscribe_By_Email_Subscriber( $_subscription );
    }
    else {
    	$subscriptions = incsub_sbe_get_subscribers( $args );
    }

    foreach ( $subscriptions as $subscription ) {

    	if ( ! $sample ) {
    		$meta = $subscription->get_metas( $extra_fields_slugs );
    	}
    	else {
    		$meta = array();
    		foreach ( $extra_fields_slugs as $extra_field_slug )
    			$meta[] = 'Sample';
    	}
    		

        echo '"' . $subscription->get_subscription_email() . '"'
        	. $sep . '"' . $subscription->get_subscription_type() . '"' 
        	. $sep . '"' . $subscription->get_subscription_note() . '"';

        echo $sep . '"' . implode( '"' . $sep . '"', stripslashes_deep( $meta ) ) . '"';
        echo "\n";
    }
    
    exit();     
}



function incsub_sbe_log( $message ) {        
    // full path to log file
    $file = INCSUB_SBE_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'sbe_log.log';

    /* backtrace */
    $bTrace = debug_backtrace(); // assoc array

    /* Build the string containing the complete log line. */
    $line = PHP_EOL.sprintf('[%s, <%s>, (%d)]==> %s', 
                            date("Y/m/d h:i:s", mktime()),
                            basename($bTrace[0]['file']), 
                            $bTrace[0]['line'], 
                            $message );
    
    // log to file
    file_put_contents( $file, $line, FILE_APPEND );
    
    return true;
}

