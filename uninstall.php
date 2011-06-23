<?php
	if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
		exit();
		
	global $wpdb;
	
	$form_table = $wpdb->prefix . 'visual_form_builder_fields';
	$fields_table = $wpdb->prefix . 'visual_form_builder_forms';
	
	$wpdb->query( "DROP TABLE IF EXISTS $form_table" );
	$wpdb->query( "DROP TABLE IF EXISTS $fields_table" );
?>