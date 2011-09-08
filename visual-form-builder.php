<?php
/*
Plugin Name: Visual Form Builder
Description: Dynamically build forms using a simple interface. Forms include jQuery validation, a basic logic-based verification system, and entry tracking.
Author: Matthew Muro
Version: 1.5.1
*/

/*
This program is free software; you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by 
the Free Software Foundation; version 2 of the License.

This program is distributed in the hope that it will be useful, 
but WITHOUT ANY WARRANTY; without even the implied warranty of 
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
GNU General Public License for more details. 

You should have received a copy of the GNU General Public License 
along with this program; if not, write to the Free Software 
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA 
*/

/* Instantiate new class */
$visual_form_builder = new Visual_Form_Builder();

/* Restrict Categories class */
class Visual_Form_Builder{
	
	public $vfb_db_version = '1.5.1';
	
	public function __construct(){
		global $wpdb;
		
		/* Setup global database table names */
		$this->field_table_name = $wpdb->prefix . 'visual_form_builder_fields';
		$this->form_table_name = $wpdb->prefix . 'visual_form_builder_forms';
		$this->entries_table_name = $wpdb->prefix . 'visual_form_builder_entries';
		
		/* Make sure we are in the admin before proceeding. */
		if ( is_admin() ) {
			/* Build options and settings pages. */
			add_action( 'admin_menu', array( &$this, 'add_admin' ) );
			add_action( 'admin_menu', array( &$this, 'save' ) );
			add_action( 'wp_ajax_visual_form_builder_process_sort', array( &$this, 'visual_form_builder_process_sort_callback' ) );
			add_action( 'admin_init', array( &$this, 'add_visual_form_builder_contextual_help' ) );
			add_action( 'admin_init', array( &$this, 'export_entries' ) );

			/* Load the includes files */
			add_action( 'plugins_loaded', array( &$this, 'includes' ) );
			
			/* Adds a Screen Options tab to the Entries screen */
			add_action( 'admin_init', array( &$this, 'save_screen_options' ) );
			add_filter( 'screen_settings', array( &$this, 'add_visual_form_builder_screen_options' ) );
			
			/* Adds a Settings link to the Plugins page */
			add_filter( 'plugin_action_links', array( &$this, 'visual_form_builder_plugin_action_links' ), 10, 2 );
			
			/* Add a database version to help with upgrades and run SQL install */
			if ( !get_option( 'vfb_db_version' ) ) {
				update_option( 'vfb_db_version', $this->vfb_db_version );
				$this->install_db();
			}
			
			/* If database version doesn't match, update and run SQL install */
			if ( get_option( 'vfb_db_version' ) != $this->vfb_db_version ) {
				update_option( 'vfb_db_version', $this->vfb_db_version );
				$this->install_db();
			}
			
			/* Load the jQuery and CSS we need if we're on our plugin page */
			add_action( 'load-settings_page_visual-form-builder', array( &$this, 'form_admin_scripts' ) );
			add_action( 'load-settings_page_visual-form-builder', array( &$this, 'form_admin_css' ) );
		}
		
		add_shortcode( 'vfb', array( &$this, 'form_code' ) );
		add_action( 'init', array( &$this, 'email' ), 10 );
		add_action( 'init', array( &$this, 'confirmation' ), 12 );
		
		/* Add jQuery and CSS to the front-end */
		add_action( 'wp_head', array( &$this, 'form_css' ) );
		add_action( 'template_redirect', array( &$this, 'form_validation' ) );
	}
	
	/**
	 * Adds extra include files
	 * 
	 * @since 1.2
	 */
	public function includes(){
		/* Load the Entries List class */
		require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'class-entries-list.php' );
		
		/* Load the Entries Details class */
		require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'class-entries-detail.php' );
	}
	
	/**
	 * Register contextual help. This is for the Help tab dropdown
	 * 
	 * @since 1.0
	 */
	public function add_visual_form_builder_contextual_help(){
		$text = "<p><strong>Getting Started</strong></p>
					<ul>
						<li>Click on the + tab, give your form a name and click Create Form.</li>
						<li>Select form fields from the box on the left and click a field to add it to your form.</li>
						<li>Edit the information for each form field by clicking on the down arrow.</li>
						<li>Drag and drop the elements to put them in order.</li>
						<li>Click Save Form to save your changes.</li>
					</ul>
				<p><strong>Form Item Configuration</strong></p>
					<ul>
						<li><em>Name</em> will change the display name of your form input.</li>
						<li><em>Description</em> will be displayed below the associated input.</li>
						<li><em>Validation</em> allows you to select from several of jQuery's Form Validation methods for text inputs. For more about the types of validation, read the <em>Validation</em> section below.</li>
						<li><em>Required</em> is either Yes or No. Selecting 'Yes' will make the associated input a required field and the form will not submit until the user fills this field out correctly.</li>
						<li><em>Options</em> will only be active for Radio and Checkboxes.  This field contols how many options are available for the associated input. Multiple options must be separated by commas (ex: <em>Option 1, Option 2, Option 3</em>).</li>
						<li><em>Size</em> controls the width of Text, Textarea, Select, and Date Picker input fields.  The default is set to Medium but if you need a longer text input, select Large.</li>
					</ul>
				<p><strong>Validation</strong><p>
					<ul>
						<li>Visual Form Builder uses the <a href='http://docs.jquery.com/Plugins/Validation/Validator'>jQuery Form Validation plugin</a> to perform clientside form validation.</li>
						<li><em>Email</em>: makes the element require a valid email.</li>
						<li><em>URL</em>: makes the element require a valid url.</li>
						<li><em>Date</em>: makes the element require a date. <a href='http://docs.jquery.com/Plugins/Validation/Methods/date'>Refer to documentation for various accepted formats</a>.
						<li><em>Number</em>: makes the element require a decimal number.</li>
						<li><em>Digits</em>: makes the element require digits only.</li>
						<li><em>Phone</em>: makes the element require a US or International phone number. Most formats are accepted.</li>
						<li><em>Time</em>: choose either 12- or 24-hour time format (NOTE: only available with the Time field).</li>
					</ul>
				<p><strong>Confirmation</strong><p>
					<ul>
						<li>Each form allows you to customize the confirmation by selecing either a Text Message, a WordPress Page, or to Redirect to a URL.</li>
						<li><em>Text</em> allows you to enter a custom formatted message that will be displayed on the page after your form is submitted. HTML is allowed here.</li>
						<li><em>Page</em> displays a dropdown of all WordPress Pages you have created. Select one to redirect the user to that page after your form is submitted.</li>
						<li><em>Redirect</em> will only accept URLs and can be used to send the user to a different site completely, if you choose.
					</ul>
				<p><strong>Tips</strong></p>
					<ul>
						<li>Fieldsets, a way to group form fields, are an essential piece of this plugin's HTML. As such, at least one fieldset is required and must be first in the order. Subsequent fieldsets may be placed wherever you would like to start your next grouping of fields.</li>
						<li>Security verification is automatically included on very form. It's a simple logic question and should keep out most, if not all, spam bots.</li>
						<li>There is a hidden spam field, known as a honey pot, that should also help deter potential abusers of your form.</li>
					</ul>";
		
    	add_contextual_help( 'settings_page_visual-form-builder', $text ); 
	}
	
	/**
	 * Adds the Screen Options tab to the Entries screen
	 * 
	 * @since 1.2
	 */
	public function add_visual_form_builder_screen_options($current){
		global $current_screen;
		
		$options = get_option( 'visual-form-builder-screen-options' );

		if ( $current_screen->id == 'settings_page_visual-form-builder' && isset( $_REQUEST['view'] ) && in_array( $_REQUEST['view'], array( 'entries' ) ) ){
			$current = '<h5>Show on screen</h5>
					<input type="text" value="' . $options['per_page'] . '" maxlength="3" id="visual-form-builder-per-page" name="visual-form-builder-screen-options[per_page]" class="screen-per-page"> <label for="visual-form-builder-per-page">Entries</label>
					<input type="submit" value="Apply" class="button" id="visual-form-builder-screen-options-apply" name="visual-form-builder-screen-options-apply">';
		}
		
		return $current;
	}
	
	/**
	 * Saves the Screen Options
	 * 
	 * @since 1.2
	 */
	public function save_screen_options(){
		$options = get_option( 'visual-form-builder-screen-options' );
		
		/* Default is 20 per page */
		$defaults = array(
			'per_page' => 20
		);
		
		/* If the option doesn't exist, add it with defaults */
		if ( !$options )
			update_option( 'visual-form-builder-screen-options', $defaults );
		
		/* If the user has saved the Screen Options, update */
		if ( isset( $_REQUEST['visual-form-builder-screen-options-apply'] ) && in_array( $_REQUEST['visual-form-builder-screen-options-apply'], array( 'Apply', 'apply' ) ) ) {
			$per_page = absint( $_REQUEST['visual-form-builder-screen-options']['per_page'] );
			$updated_options = array(
				'per_page' => $per_page
			);
			update_option( 'visual-form-builder-screen-options', $updated_options );
		}
	}
	
	/**
	 * Runs the export_entries function in the class-entries-list.php file
	 * 
	 * @since 1.4
	 */
	public function export_entries() {
		$entries = new VisualFormBuilder_Entries_List();
		
		/* If exporting all, don't pass the IDs */
		if ( 'export-all' === $entries->current_action() )
			$entries->export_entries();
		/* If exporting selected, pick up the ID array and pass them */
		elseif ( 'export-selected' === $entries->current_action() ) {
			$entry_id = ( is_array( $_REQUEST['entry'] ) ) ? $_REQUEST['entry'] : array( $_REQUEST['entry'] );
			$entries->export_entries( $entry_id );
		}
	}

	
	/**
	 * Install database tables
	 * 
	 * @since 1.0 
	 */
	static function install_db() {
		global $wpdb;
		
		$field_table_name = $wpdb->prefix . 'visual_form_builder_fields';
		$form_table_name = $wpdb->prefix . 'visual_form_builder_forms';
		$entries_table_name = $wpdb->prefix . 'visual_form_builder_entries';
		
		/* Explicitly set the character set and collation when creating the tables */
		$charset = ( defined( 'DB_CHARSET' && '' !== DB_CHARSET ) ) ? DB_CHARSET : 'utf8';
		$collate = ( defined( 'DB_COLLATE' && '' !== DB_COLLATE ) ) ? DB_COLLATE : 'utf8_general_ci';
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); 
				
		$field_sql = "CREATE TABLE $field_table_name (
				field_id BIGINT(20) NOT NULL AUTO_INCREMENT,
				form_id BIGINT(20) NOT NULL,
				field_key VARCHAR(255) NOT NULL,
				field_type VARCHAR(25) NOT NULL,
				field_options TEXT,
				field_description TEXT,
				field_name VARCHAR(255) NOT NULL,
				field_sequence TINYINT DEFAULT '0',
				field_validation VARCHAR(25),
				field_required VARCHAR(25),
				field_size VARCHAR(25),
				UNIQUE KEY  (field_id)
			) DEFAULT CHARACTER SET $charset COLLATE $collate;";

		$form_sql = "CREATE TABLE $form_table_name (
				form_id BIGINT(20) NOT NULL AUTO_INCREMENT,
				form_key TINYTEXT NOT NULL,
				form_title TEXT NOT NULL,
				form_email_subject TEXT,
				form_email_to VARCHAR(255),
				form_email_from VARCHAR(255),
				form_email_from_name VARCHAR(255),
				form_email_from_override VARCHAR(255),
				form_email_from_name_override VARCHAR(255),
				form_success_type VARCHAR(25) DEFAULT 'text',
				form_success_message TEXT,
				UNIQUE KEY  (form_id)
			) DEFAULT CHARACTER SET $charset COLLATE $collate;";
		
		$entries_sql = "CREATE TABLE $entries_table_name (
				entries_id BIGINT(20) NOT NULL AUTO_INCREMENT,
				form_id BIGINT(20) NOT NULL,
				data TEXT NOT NULL,
				subject TEXT,
				sender_name VARCHAR(255),
				sender_email VARCHAR(25),
				emails_to VARCHAR(255),
				date_submitted VARCHAR(25),
				ip_address VARCHAR(25),
				UNIQUE KEY  (entries_id)
			) DEFAULT CHARACTER SET $charset COLLATE $collate;";
		
		/* Create or Update database tables */
		dbDelta( $field_sql );
		dbDelta( $form_sql );
		dbDelta( $entries_sql );
		
	}

	/**
	 * Queue plugin CSS for admin styles
	 * 
	 * @since 1.0
	 */
	public function form_admin_css(){
		wp_enqueue_style( 'visual-form-builder-style', plugins_url( 'visual-form-builder' ) . '/css/visual-form-builder-admin.css' );
		wp_enqueue_style( 'visual-form-builder-main', plugins_url( 'visual-form-builder' ) . '/css/nav-menu.css' );
	}
	
	/**
	 * Queue plugin scripts for sorting form fields
	 * 
	 * @since 1.0 
	 */
	public function form_admin_scripts() {
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'jquery-form-validation', 'http://ajax.aspnetcdn.com/ajax/jquery.validate/1.8/jquery.validate.min.js', array( 'jquery' ), '', true );
		wp_enqueue_script( 'form-elements-add', plugins_url( 'visual-form-builder' ) . '/js/visual-form-builder.js' , array( 'jquery', 'jquery-form-validation' ), '', true );
	}
	
	/**
	 * Queue form validation scripts
	 * 
	 * @since 1.0 
	 */
	public function form_validation(){
		wp_enqueue_script( 'jquery-form-validation', 'http://ajax.aspnetcdn.com/ajax/jquery.validate/1.8/jquery.validate.min.js', array( 'jquery' ), '', true );
		wp_enqueue_script( 'jquery-ui-core ', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.13/jquery-ui.min.js', array( 'jquery' ), '', true );
		wp_enqueue_script( 'visual-form-builder-validation', plugins_url( 'visual-form-builder' ) . '/js/visual-form-builder-validate.js' , array( 'jquery', 'jquery-form-validation' ), '', true );
		wp_enqueue_script( 'visual-form-builder-quicktags', plugins_url( 'visual-form-builder' ) . '/js/js_quicktags.js' );
	}
	
	/**
	 * Add form CSS to wp_head
	 * 
	 * @since 1.0 
	 */
	public function form_css(){
		echo apply_filters( 'visual-form-builder-css', '<link rel="stylesheet" href="' . plugins_url( 'css/visual-form-builder.css', __FILE__ ) . '" type="text/css" />' );
		echo apply_filters( 'vfb-date-picker-css', '<link media="all" type="text/css" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/themes/base/jquery-ui.css" rel="stylesheet" />' );
	}
	
	/**
	 * Add Settings link to Plugins page
	 * 
	 * @since 1.8 
	 * @return $links array Links to add to plugin name
	 */
	public function visual_form_builder_plugin_action_links( $links, $file ) {
		if ( $file == plugin_basename( __FILE__ ) )
			$links[] = '<a href="options-general.php?page=visual-form-builder">' . __( 'Settings' ) . '</a>';
	
		return $links;
	}
	
	/**
	 * Add options page to Settings menu
	 * 
	 * 
	 * @since 1.0
	 * @uses add_options_page() Creates a menu item under the Settings menu.
	 */
	public function add_admin() {  
		add_options_page( __( 'Visual Form Builder', 'visual-form-builder' ), __( 'Visual Form Builder', 'visual-form-builder' ), 'create_users', 'visual-form-builder', array( &$this, 'admin' ) );
	}
	
	
	/**
	 * Actions to save, update, and delete forms/form fields
	 * 
	 * 
	 * @since 1.0
	 */
	public function save() {
		global $wpdb;
				
		if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'visual-form-builder' && isset( $_REQUEST['action'] ) ) {
			
			switch ( $_REQUEST['action'] ) {
				case 'create_form' :
					
					$form_id = absint( $_REQUEST['form_id'] );
					$form_key = sanitize_title( $_REQUEST['form_title'] );
					$form_title = esc_html( $_REQUEST['form_title'] );
					
					check_admin_referer( 'create_form-' . $form_id );
					
					$newdata = array(
						'form_key' => $form_key,
						'form_title' => $form_title
					);
					
					/* Set message to display */
					$this->message = '<div id="message" class="updated"><p>The <strong>' . $form_title . '</strong> form has been created.</p></div>';
					
					/* Create the form */
					$wpdb->insert( $this->form_table_name, $newdata );
					
					/* Get form ID to add our first field */
					$new_form_selected = $wpdb->insert_id;
					
					/* Setup the initial fieldset */
					$initial_fieldset = array(
						'form_id' => $wpdb->insert_id,
						'field_key' => 'fieldset',
						'field_type' => 'fieldset',
						'field_name' => 'Fieldset',
						'field_sequence' => 0
					);
					
					/* Add the first fieldset to get things started */ 
					$wpdb->insert( $this->field_table_name, $initial_fieldset );
					
					/* Redirect to keep the URL clean (use AJAX in the future?) */
					wp_redirect( 'options-general.php?page=visual-form-builder&form=' . $new_form_selected );
					exit();
					
				break;
				
				case 'update_form' :

					$form_id = absint( $_REQUEST['form_id'] );
					$form_key = sanitize_title( $_REQUEST['form_title'] );
					$form_title = esc_html( $_REQUEST['form_title'] );
					$form_subject = esc_html( $_REQUEST['form_email_subject'] );
					$form_to = serialize( esc_html( $_REQUEST['form_email_to'] ) );
					$form_from = esc_html( $_REQUEST['form_email_from'] );
					$form_from_name = esc_html( $_REQUEST['form_email_from_name'] );
					$form_from_override = esc_html( $_REQUEST['form_email_from_override'] );
					$form_from_name_override = esc_html( $_REQUEST['form_email_from_name_override'] );
					$form_success_type = esc_html( $_REQUEST['form_success_type'] );
					
					/* Add confirmation based on which type was selected */
					switch ( $form_success_type ) {
						case 'text' :
							$form_success_message = wp_richedit_pre( $_REQUEST['form_success_message_text'] );
						break;
						case 'page' :
							$form_success_message = esc_html( $_REQUEST['form_success_message_page'] );
						break;
						case 'redirect' :
							$form_success_message = esc_html( $_REQUEST['form_success_message_redirect'] );
						break;
					}
					
					check_admin_referer( 'update_form-' . $form_id );
					
					$newdata = array(
						'form_key' => $form_key,
						'form_title' => $form_title,
						'form_email_subject' => $form_subject,
						'form_email_to' => $form_to,
						'form_email_from' => $form_from,
						'form_email_from_name' => $form_from_name,
						'form_email_from_override' => $form_from_override,
						'form_email_from_name_override' => $form_from_name_override,
						'form_success_type' => $form_success_type,
						'form_success_message' => $form_success_message
					);
					
					$where = array(
						'form_id' => $form_id
					);
					
					/* Update form details */
					$wpdb->update( $this->form_table_name, $newdata, $where );
					
					/* Loop through each field and update all at once */
					if ( !empty( $_REQUEST['field_id'] ) ) {
						foreach ( $_REQUEST['field_id'] as $id ) {
							$field_name = ( isset( $_REQUEST['field_name-' . $id] ) ) ? esc_html( $_REQUEST['field_name-' . $id] ) : '';
							$field_key = sanitize_title( $field_name );
							$field_desc = ( isset( $_REQUEST['field_description-' . $id] ) ) ? esc_html( $_REQUEST['field_description-' . $id] ) : '';
							$field_options = ( isset( $_REQUEST['field_options-' . $id] ) ) ? serialize( esc_html( $_REQUEST['field_options-' . $id] ) ) : '';
							$field_validation = ( isset( $_REQUEST['field_validation-' . $id] ) ) ? esc_html( $_REQUEST['field_validation-' . $id] ) : '';
							$field_required = ( isset( $_REQUEST['field_required-' . $id] ) ) ? esc_html( $_REQUEST['field_required-' . $id] ) : '';
							$field_size = ( isset( $_REQUEST['field_size-' . $id] ) ) ? esc_html( $_REQUEST['field_size-' . $id] ) : '';
							
							$field_data = array(
								'field_key' => $field_key,
								'field_name' => $field_name,
								'field_description' => $field_desc,
								'field_options' => $field_options,
								'field_validation' => $field_validation,
								'field_required' => $field_required,
								'field_size' => $field_size
							);
							
							$where = array(
								'form_id' => $_REQUEST['form_id'],
								'field_id' => $id
							);
							
							/* Update all fields */
							$wpdb->update( $this->field_table_name, $field_data, $where );
						}
					}
					
					/* Set message to display */
					$this->message = '<div id="message" class="updated"><p>The <strong>' . $form_title . '</strong> form has been updated.</p></div>';
					
				break;
				
				case 'delete_form' :
					$id = absint( $_REQUEST['form'] );
					
					check_admin_referer( 'delete-form-' . $id );
					
					/* Delete form and all fields */
					$wpdb->query( $wpdb->prepare( "DELETE FROM $this->form_table_name WHERE form_id = %d", $id ) );
					$wpdb->query( $wpdb->prepare( "DELETE FROM $this->field_table_name WHERE form_id = %d", $id ) );
					
					/* Set message to display */
					$this->message = '<div id="message" class="updated"><p>This form has been deleted.</p></div>';
					
					/* Redirect to keep the URL clean (use AJAX in the future?) */
					wp_redirect( 'options-general.php?page=visual-form-builder' );
					exit();
					
				break;
				
				case 'delete_field' :
					$form_id = absint( $_REQUEST['form'] );
					$field_id = absint( $_REQUEST['field'] );
					
					check_admin_referer( 'delete-field-' . $form_id );
					
					/* Delete the field */
					$wpdb->query( $wpdb->prepare( "DELETE FROM $this->field_table_name WHERE field_id = %d", $field_id ) );
					
					/* Set message to display */
					$this->message = '<div id="message" class="updated"><p>The field has been deleted.</p></div>';
					
					/* Redirect to keep the URL clean (use AJAX in the future?) */
					wp_redirect( 'options-general.php?page=visual-form-builder&form=' . $form_id );
					exit();
					
				break;
				
				case 'create_field' :
					$form_id = absint( $_REQUEST['form_id'] );
					$field_key = sanitize_title( $_REQUEST['field_name'] );
					$field_name = esc_html( $_REQUEST['field_type'] );
					$field_type = strtolower( sanitize_title( $_REQUEST['field_type'] ) );
					
					/* Set defaults for validation */
					switch ( $field_type ) {
						case 'email' :
						case 'url' :
						case 'phone' :
							$field_validation = $field_type;
						break;
						case 'currency' :
							$field_validation = 'number';
						break;
						case 'number' :
							$field_validation = 'digits';
						break;
						case 'time' :
							$field_validation = 'time-12';
						break;
					}
					
					check_admin_referer( 'create-field-' . $form_id );
					
					/* Get the last row's sequence */
					$sequence_last_row = $wpdb->get_row( "SELECT field_sequence FROM $this->field_table_name WHERE form_id = $form_id ORDER BY field_sequence DESC LIMIT 1" );
					
					/* If it's not the first for this form, add 1 */
					$field_sequence = ( !empty( $sequence_last_row ) ) ? $sequence_last_row->field_sequence + 1 : 0;
					
					$newdata = array(
						'form_id' => absint( $_REQUEST['form_id'] ),
						'field_key' => $field_key,
						'field_name' => $field_name,
						'field_type' => $field_type,
						'field_sequence' => $field_sequence,
						'field_validation' => $field_validation
					);
					
					/* Create the field */
					$wpdb->insert( $this->field_table_name, $newdata );
					
				break;
			}
		}
	}	
	
	/**
	 * The jQuery field sorting callback
	 * 
	 * @since 1.0
	 */
	public function visual_form_builder_process_sort_callback() {
		global $wpdb;
		
		/* Get the order of the fields as make an array */
		$order = explode( ',', $_REQUEST['order'] );
		
		foreach ( $order as $k => $v ) {
			/* Find the digits from each field */
			preg_match( '/(\d+)/', $v, $matches );
			
			/* Update each field with it's new sequence */
			$wpdb->update( $this->field_table_name, array( 'field_sequence' => $k ), array( 'field_id' => $matches[0] ) );
		}

		die(1);
	}
	
	/**
	 * Builds the options settings page
	 * 
	 * @since 1.0
	 */
	public function admin() {
		global $wpdb;

		/* Set variables depending on which tab is selected */
		$form_nav_selected_id = ( isset( $_REQUEST['form'] ) ) ? $_REQUEST['form'] : '0';
		$action = ( isset( $_REQUEST['form'] ) && $_REQUEST['form'] !== '0' ) ? 'update_form' : 'create_form';
		$details_meta = ( isset( $_REQUEST['details'] ) ) ? $_REQUEST['details'] : 'email';
		
		/* Query to get all forms */
		$order = sanitize_sql_orderby( 'form_id DESC' );
		$query = "SELECT * FROM $this->form_table_name ORDER BY $order";
		
		/* Build our forms as an object */
		$forms = $wpdb->get_results( $query );
		
		/* Loop through each form and assign a form id, if any */
		foreach ( $forms as $form ) {
			$form_id = ( $form_nav_selected_id == $form->form_id ) ? $form->form_id : '';
			
			/* If we are on a form, set the form name for the shortcode box */
			if ( $form_nav_selected_id == $form->form_id )
				$form_name = stripslashes( $form->form_title );	
		}
		
	?>
	
		<div class="wrap">
			<?php screen_icon( 'options-general' ); ?>
			<h2><?php _e('Visual Form Builder', 'visual-form-builder'); ?></h2>            
            <ul class="subsubsub">
                <li><a<?php echo ( !isset( $_REQUEST['view'] ) ) ? ' class="current"' : ''; ?> href="<?php echo admin_url( 'options-general.php?page=visual-form-builder' ); ?>">Forms</a> |</li>
                <li><a<?php echo ( isset( $_REQUEST['view'] ) && in_array( $_REQUEST['view'], array( 'entries' ) ) ) ? ' class="current"' : ''; ?> href="<?php echo add_query_arg( 'view', 'entries', admin_url( 'options-general.php?page=visual-form-builder' ) ); ?>">Entries</a></li>
            </ul>
            
            <?php
				/* Display the Entries */
				if ( isset( $_REQUEST['view'] ) && in_array( $_REQUEST['view'], array( 'entries' ) ) ) : 
				
					$entries_list = new VisualFormBuilder_Entries_List();
					$entries_detail = new VisualFormBuilder_Entries_Detail();
					
					if ( isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], array( 'view' ) ) ) :
						$entries_detail->entries_detail();
					else :
						$entries_list->prepare_items();
			?>
                <form id="entries-filter" method="post" action="">
                    <?php $entries_list->display(); ?>
                </form>
            <?php
				endif;
				
				/* Display the Forms */
				else:	
					echo ( isset( $this->message ) ) ? $this->message : ''; ?>          
            <div id="nav-menus-frame">
                <div id="menu-settings-column" class="metabox-holder<?php echo ( empty( $form_nav_selected_id ) ) ? ' metabox-holder-disabled' : ''; ?>">
                    <div id="side-sortables" class="metabox-holder">

                    <form id="form-items" class="nav-menu-meta" method="post" action="">
                        <input name="action" type="hidden" value="create_field" />
						<input name="form_id" type="hidden" value="<?php echo $form_nav_selected_id; ?>" />
                        <?php
							/* Security nonce */
							wp_nonce_field( 'create-field-' . $form_nav_selected_id );
							
							/* Disable the left box if there's no active form selected */
                        	$disabled = ( empty( $form_nav_selected_id ) ) ? ' disabled="disabled"' : '';
						?>
                            <div class="postbox">
                                <h3 class="hndle"><span>Form Items</span></h3>
                                <div class="inside" >
                                    <div class="taxonomydiv">
                                        <p><strong>Click</strong> to Add a Field <img id="add-to-form" alt="" src="<?php echo admin_url( '/images/wpspin_light.gif' ); ?>" class="waiting" /></p>
                                        <ul>
                                            <li><input type="submit" id="form-element-fieldset" class="button-secondary" name="field_type" value="Fieldset"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-text" class="button-secondary" name="field_type" value="Text"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-textarea" class="button-secondary" name="field_type" value="Textarea"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-checkbox" class="button-secondary" name="field_type" value="Checkbox"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-radio" class="button-secondary" name="field_type" value="Radio"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-select" class="button-secondary" name="field_type" value="Select"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-address" class="button-secondary" name="field_type" value="Address"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-datepicker" class="button-secondary" name="field_type" value="Date"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-email" class="button-secondary" name="field_type" value="Email"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-url" class="button-secondary" name="field_type" value="URL"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-currency" class="button-secondary" name="field_type" value="Currency"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-digits" class="button-secondary" name="field_type" value="Number"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-time" class="button-secondary" name="field_type" value="Time"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-phone" class="button-secondary" name="field_type" value="Phone"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-html" class="button-secondary" name="field_type" value="HTML"<?php echo $disabled; ?> /></li>
                                            <li><input type="submit" id="form-element-file" class="button-secondary" name="field_type" value="File Upload"<?php echo $disabled; ?> /></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                      </form>
                            <div class="postbox">
                                <h3 class="hndle"><span>Form Output</span></h3>
                                <div class="inside">
                                    <div id="customlinkdiv" class="customlinkdiv">
                                        <p>Copy this shortcode and paste into any Post or Page. <?php echo ( $form_nav_selected_id !== '0') ? "This will display the <strong>$form_name</strong> form." : ''; ?></p>
                                        <p id="menu-item-url-wrap">
                                		<form action="">      
                                            <label class="howto">
                                                <span>Shortcode</span>
                                                <input id="form-copy-to-clipboard" type="text" class="code menu-item-textbox" value="<?php echo ( $form_nav_selected_id !== '0') ? "[vfb id=$form_nav_selected_id]" : ''; ?>"<?php echo $disabled; ?> style="width:75%;" />
                                            </label>
                               			 </form>
                                        </p>
                                    </div>
                                </div>
                            </div> 
                	</div>
            	</div>
                
                <div id="menu-management-liquid">
                    <div id="menu-management">
                       	<div class="nav-tabs-nav">
                        	<div class="nav-tabs-arrow nav-tabs-arrow-left"><a>&laquo;</a></div>
                            <div class="nav-tabs-wrapper">
                                <div class="nav-tabs">
                                    <?php
										/* Loop through each for and build the tabs */
										foreach ( $forms as $form ) {
											
											/* Control selected tab */
											if ( $form_nav_selected_id == $form->form_id ) :
												echo '<span class="nav-tab nav-tab-active">' . stripslashes( $form->form_title ) . '</span>';
												$form_id = $form->form_id;
												$form_title = stripslashes( $form->form_title );
												$form_subject = stripslashes( $form->form_email_subject );
												$form_email_from_name = stripslashes( $form->form_email_from_name );
												$form_email_from = stripslashes( $form->form_email_from);
												$form_email_from_override = stripslashes( $form->form_email_from_override);
												$form_email_from_name_override = stripslashes( $form->form_email_from_name_override);
												$form_email_to = unserialize( stripslashes( $form->form_email_to ) );
												$form_success_type = stripslashes( $form->form_success_type );
												$form_success_message = stripslashes( $form->form_success_message );
												
												/* Only show required text fields for the sender name override */
												$sender_query 	= "SELECT * FROM $this->field_table_name WHERE form_id = $form_nav_selected_id AND field_type='text' AND field_validation = '' AND field_required = 'yes'";
												$senders = $wpdb->get_results( $sender_query );
												
												/* Only show required email fields for the email override */
												$email_query = "SELECT * FROM $this->field_table_name WHERE (form_id = $form_nav_selected_id AND field_type='text' AND field_validation = 'email' AND field_required = 'yes') OR (form_id = $form_nav_selected_id AND field_type='email' AND field_validation = 'email' AND field_required = 'yes')";
												$emails = $wpdb->get_results( $email_query );
											
											else :
												echo '<a href="' . esc_url( add_query_arg( array( 'form' => $form->form_id ), admin_url( 'options-general.php?page=visual-form-builder' ) ) ) . '" class="nav-tab" id="' . $form->form_key . '">' . stripslashes( $form->form_title ) . '</a>';
											endif;
											
										}
										
										/* Displays the build new form tab */
										if ( '0' == $form_nav_selected_id ) :
									?>
                                    	<span class="nav-tab menu-add-new nav-tab-active"><?php printf( '<abbr title="%s">+</abbr>', esc_html__( 'Add form' ) ); ?></span>
									<?php else : ?>
                                    	<a href="<?php echo esc_url( add_query_arg( array( 'form' => 0 ), admin_url( 'options-general.php?page=visual-form-builder' ) ) ); ?>" class="nav-tab menu-add-new"><?php printf( '<abbr title="%s">+</abbr>', esc_html__( 'Add form' ) ); ?></a>
									<?php endif; ?>
                                </div>
                            </div>
                            <div class="nav-tabs-arrow nav-tabs-arrow-right"><a>&raquo;</a></div>
                        </div>

                        <div class="menu-edit">
                        	<form method="post" id="visual-form-builder-update" action="">
                            	<input name="action" type="hidden" value="<?php echo $action; ?>" />
								<input name="form_id" type="hidden" value="<?php echo $form_nav_selected_id; ?>" />
                                <?php wp_nonce_field( "$action-$form_nav_selected_id" ); ?>
                            	<div id="nav-menu-header">
                                	<div id="submitpost" class="submitbox">
                                    	<div class="major-publishing-actions">
                                        	<label for="form-name" class="menu-name-label howto open-label">
                                                <span class="sender-labels">Form Name</span>
                                                <input type="text" value="<?php echo ( isset( $form_title ) ) ? $form_title : ''; ?>" title="Enter form name here" class="menu-name regular-text menu-item-textbox" id="form-name" name="form_title" />
                                            </label>
                                            <?php 
												/* Display sender details and confirmation message if we're on a form, otherwise just the form name */
												if ( $form_nav_selected_id !== '0' ) : 
											?>
                                            <br class="clear" />
											
                                            <div id="form-details-nav">
                                            	<a href="<?php echo add_query_arg( array( 'form' => $form_nav_selected_id, 'details' => 'email' ), admin_url( 'options-general.php?page=visual-form-builder' ) ); ?>" class="<?php echo ( 'email' == $details_meta ) ? 'current' : ''; ?>" title="Customize Email Details">Email Details</a>
                                                <a href="<?php echo add_query_arg( array( 'form' => $form_nav_selected_id, 'details' => 'confirmation' ), admin_url( 'options-general.php?page=visual-form-builder' ) ); ?>" class="<?php echo ( 'confirmation' == $details_meta ) ? 'current' : ''; ?>" title="Customize Confirmation Message">Confirmation</a>
                                            </div>
                                            
                                            <div id="email-details" class="<?php echo ( 'email' == $details_meta ) ? 'form-details-current' : 'form-details'; ?>">
                                                <p><em>The forms you build here will send information to one or more email addresses when submitted by a user on your site.  Use the fields below to customize the details of that email.</em></p>
                                                <label for="form-email-subject" class="menu-name-label howto open-label">
                                                    <span class="sender-labels">E-mail Subject</span>
                                                    <input type="text" value="<?php echo $form_subject; ?>" class="menu-name regular-text menu-item-textbox" id="form-email-subject" name="form_email_subject" />
                                                </label>
                                                <br class="clear" />
                                                <label for="form-email-sender-name" class="menu-name-label howto open-label">
                                                    <span class="sender-labels">Sender Name</span>
                                                    <input type="text" value="<?php echo $form_email_from_name; ?>" class="menu-name regular-text menu-item-textbox" id="form-email-sender-name" name="form_email_from_name"<?php echo ( $form_email_from_name_override != '' ) ? ' readonly="readonly"' : ''; ?> />
                                                </label>
                                                <span>OR</span>
                                                <select name="form_email_from_name_override" id="form_email_from_name_override">
                                                    <option value="" <?php selected( $form_email_from_name_override, '' ); ?>>Select a required text field</option>
                                                    <?php 
													foreach( $senders as $sender ) {
                                                    	echo '<option value="' . $sender->field_id . '"' . selected( $form_email_from_name_override, $sender->field_id ) . '>' . $sender->field_name . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                                <br class="clear" />
                                                <label for="form-email-sender" class="menu-name-label howto open-label">
                                                    <span class="sender-labels">Sender E-mail</span>
                                                    <input type="text" value="<?php echo $form_email_from; ?>" class="menu-name regular-text menu-item-textbox" id="form-email-sender" name="form_email_from"<?php echo ( $form_email_from_override != '' ) ? ' readonly="readonly"' : ''; ?> />
                                                </label>
                                                <span>OR</span>
                                                <select name="form_email_from_override" id="form_email_from_override">
                                                    <option value="" <?php selected( $form_email_from_override, '' ); ?>>Select a required text field with email validation</option>
                                                    <?php 
													foreach( $emails as $email ) {
                                                    	echo '<option value="' . $email->field_id . '"' . selected( $form_email_from_override, $email->field_id ) . '>' . $email->field_name . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                                <br class="clear" />
                                                <label for="form-email-to" class="menu-name-label howto open-label">
                                                    <span class="sender-labels">E-mail(s) To</span>
                                                    <input type="text" value="<?php echo $form_email_to; ?>" class="menu-name regular-text menu-item-textbox" id="form-email-to" name="form_email_to" />
                                                </label>
                                                <span><em>(multiple emails separated by commas)</em></span>
                                            </div>
                                            
                                            <div id="confirmation-message" class="<?php echo ( 'confirmation' == $details_meta ) ? 'form-details-current' : 'form-details'; ?>">
                                            	<p><em>After someone submits a form, you can control what is displayed. By default, it's a message but you can send them to another WordPress Page or a custom URL.</em></p>
                                            	<label for="form-success-text" class="menu-name-label open-label">
                                                    <input type="radio" value="text" id="form-success-type-text" class="form-success-type" name="form_success_type" <?php checked( $form_success_type, 'text' ); ?> />
                                                    <span>Text</span>
                                                </label>
                                                <label for="form-success-page" class="menu-name-label open-label">
                                                    <input type="radio" value="page" id="form-success-type-page" class="form-success-type" name="form_success_type" <?php checked( $form_success_type, 'page' ); ?>/>
                                                    <span>Page</span>
                                                </label>
                                                <label for="form-success-redirect" class="menu-name-label open-label">
                                                    <input type="radio" value="redirect" id="form-success-type-redirect" class="form-success-type" name="form_success_type" <?php checked( $form_success_type, 'redirect' ); ?>/>
                                                    <span>Redirect</span>
                                                </label>
                                                <br class="clear" />
                                                
                                                <?php
												/* If there's no text message, make sure there is something displayed by setting a default */
												if ( $form_success_message === '' )
													$default_text = '<p id="form_success">Your form was successfully submitted. Thank you for contacting us.</p>';
												?>
                                                <textarea id="form-success-message-text" class="form-success-message<?php echo ( 'text' == $form_success_type ) ? ' active' : ''; ?>" name="form_success_message_text"><?php echo $default_text; ?><?php echo ( 'text' == $form_success_type ) ? $form_success_message : ''; ?></textarea>
                                                
												<?php
												/* Display all Pages */
												wp_dropdown_pages( array(
													'name' => 'form_success_message_page', 
													'id' => 'form-success-message-page',
													'show_option_none' => 'Select a Page',
													'selected' => $form_success_message
												));
												?>
                                                <input type="text" value="<?php echo ( 'redirect' == $form_success_type ) ? $form_success_message : ''; ?>" id="form-success-message-redirect" class="form-success-message regular-text<?php echo ( 'redirect' == $form_success_type ) ? ' active' : ''; ?>" name="form_success_message_redirect" />

                                            </div>
                                            <?php endif; ?>
                                            <br class="clear" />
                                            <div class="publishing-action">
                                                <input type="submit" value="<?php echo ( $action == 'create_form' ) ? 'Create Form' : 'Save Form'; ?>" class="button-primary menu-save" id="save_form" name="save_form" />
                                            </div>
                                            <?php if ( !empty( $form_nav_selected_id ) ) : ?>
                                            	<div class="delete-action">
                                                	<a class="submitdelete deletion menu-delete" href="<?php echo esc_url( wp_nonce_url( admin_url('options-general.php?page=visual-form-builder&amp;action=delete_form&amp;form=' . $form_nav_selected_id ), 'delete-form-' . $form_nav_selected_id ) ); ?>"><?php _e('Delete Form'); ?></a>
                                            	</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div id="post-body">
                                    <div id="post-body-content">
                                <?php if ( '0' == $form_nav_selected_id ) : ?>
                                            <div class="post-body-plain">
                                                <p>To create a custom form, give it a name above and click Create Form. Then choose form elements from the left column to add to this form.</p>
                                                <p>After you have added your items, drag and drop to put them in the order you want. You can also click each item to reveal additional configuration options.</p>
                                                <p>When you have finished building your custom form, make sure you click the Save Form button.</p>
                                                <p>For more help, click on the Help tab at the top of this page.</p>
                                            </div>
                               	<?php else : 
								
								if ( !empty( $form_nav_selected_id ) && $form_nav_selected_id !== '0' ) :
									/* Display help text for adding fields */
									echo '<div class="post-body-plain" id="menu-instructions"><p>Select form inputs from the box at left to begin building your custom form. An initial fieldset has been automatically added to get you started.</p></div>';
									
									/* Display all fields for the selected form */
									$query_fields = "SELECT * FROM $this->field_table_name WHERE form_id = $form_nav_selected_id ORDER BY field_sequence ASC";
									$fields = $wpdb->get_results( $query_fields );
									
									echo '<ul id="menu-to-edit" class="menu ui-sortable droppable">';
									
									/* Loop through each field and display */
									foreach ( $fields as $field ) :
								?>
                                        <li id="form_item_<?php echo $field->field_id; ?>" class="form-item">
                                                <dl class="menu-item-bar">
                                                    <dt class="menu-item-handle<?php echo ( $field->field_type == 'fieldset' ) ? ' fieldset' : ''; ?>">
                                                        <span class="item-title"><?php echo stripslashes( htmlspecialchars( $field->field_name ) ); ?><?php echo ( $field->field_required == 'yes' ) ? ' <span class="is-field-required">*</span>' : ''; ?></span>                                          <span class="item-controls">
                                                            <span class="item-type"><?php echo strtoupper( str_replace( '-', ' ', $field->field_type ) ); ?></span>
                                                            <a href="#" title="Edit Field Item" id="edit-<?php echo $field->field_id; ?>" class="item-edit">Edit Field Item</a>
                                                        </span>
                                                    </dt>
                                                </dl>
                                    
                                                <div id="form-item-settings-<?php echo $field->field_id; ?>" class="menu-item-settings" style="display: none;">
                                                    <?php if ( $field->field_type == 'fieldset' ) : ?>
                                                    	<p class="description description-wide">
                                                            <label for="edit-form-item-name-<?php echo $field->field_id; ?>">Legend<br />
                                                                <input type="text" value="<?php echo stripslashes( $field->field_name ); ?>" name="field_name-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-name-<?php echo $field->field_id; ?>" />
                                                            </label>
                                                    	</p>
                                                    <?php else: ?>
                                                        <p class="description description-wide">
                                                            <label for="edit-form-item-name-<?php echo $field->field_id; ?>">
                                                                Name<br />
                                                                <input type="text" value="<?php echo stripslashes( htmlspecialchars( $field->field_name ) ); ?>" name="field_name-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-name-<?php echo $field->field_id; ?>" />
                                                            </label>
                                                        </p>
                                                        <p class="description description-wide">
                                                            <label for="edit-form-item-description-<?php echo $field->field_id; ?>">
                                                                Description<br />
                                                                <input type="text" value="<?php echo stripslashes( $field->field_description ); ?>" name="field_description-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-description-<?php echo $field->field_id; ?>" />
                                                            </label>
                                                        </p>
                                                        
                                                        <?php
															/* Display the Options input only for radio, checkbox, and select fields */
															if ( in_array( $field->field_type, array( 'radio', 'checkbox', 'select' ) ) ) : ?>
                                                            <p class="description description-wide">
                                                            <?php
                                                                if ( !empty( $field->field_options ) ) {
                                                                    if ( is_serialized( $field->field_options ) ) {
                                                                        $opts_vals = unserialize( $field->field_options ); 
                                                                    }
                                                                }
																
                                                            ?>
                                                                <label for="edit-form-item-options-<?php echo $field->field_id; ?>">
                                                                    Options (separated by commas)<br />
                                                                    <input type="text" value="<?php echo stripslashes( $opts_vals ); ?>" name="field_options-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-options-<?php echo $field->field_id; ?>" />
                                                                </label>
                                                            </p>
                                                        <?php
															/* Unset the options for any following radio, checkboxes, or selects */
															unset( $opts_vals );
															endif;
														?>
                                                        
                                                        <p class="description description-thin">
                                                            <label for="edit-form-item-validation">
                                                                Validation<br />
                                                               <select name="field_validation-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-validation-<?php echo $field->field_id; ?>"<?php echo ( in_array( $field->field_type, array( 'radio', 'select', 'checkbox', 'address', 'date', 'textarea', 'html', 'file-upload' ) ) ) ? ' disabled="disabled"' : ''; ?>>
                                                                    <?php if ( $field->field_type == 'time' ) : ?>
                                                                    <option value="time-12" <?php selected( $field->field_validation, 'time-12' ); ?>>12 Hour Format</option>
                                                                    <option value="time-24" <?php selected( $field->field_validation, 'time-24' ); ?>>24 Hour Format</option>
                                                                    <?php else : ?>
                                                                    <option value="" <?php selected( $field->field_validation, '' ); ?>>None</option>
                                                                    <option value="email" <?php selected( $field->field_validation, 'email' ); ?>>Email</option>
                                                                    <option value="url" <?php selected( $field->field_validation, 'url' ); ?>>URL</option>
                                                                    <option value="date" <?php selected( $field->field_validation, 'date' ); ?>>Date</option>
                                                                    <option value="number" <?php selected( $field->field_validation, 'number' ); ?>>Number</option>
                                                                    <option value="digits" <?php selected( $field->field_validation, 'digits' ); ?>>Digits</option>
                                                                    <option value="phone" <?php selected( $field->field_validation, 'phone' ); ?>>Phone</option>
                                                                    <?php endif; ?>
                                                                </select>
                                                            </label>
                                                        </p>
                                                        <p class="field-link-target description description-thin">
                                                            <label for="edit-form-item-required">
                                                                Required<br />
                                                                <select name="field_required-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-required-<?php echo $field->field_id; ?>">
                                                                    <option value="no" <?php selected( $field->field_required, 'no' ); ?>>No</option>
                                                                    <option value="yes" <?php selected( $field->field_required, 'yes' ); ?>>Yes</option>
                                                                </select>
                                                            </label>
                                                        </p>
                                                       
                                                        <?php if ( !in_array( $field->field_type, array( 'radio', 'checkbox', 'time' ) ) ) : ?>
                                                            <p class="description description-wide">
                                                                <label for="edit-form-item-size">
                                                                    Size<br />
                                                                    <select name="field_size-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-size-<?php echo $field->field_id; ?>">
                                                                        <option value="medium" <?php selected( $field->field_size, 'medium' ); ?>>Medium</option>
                                                                        <option value="large" <?php selected( $field->field_size, 'large' ); ?>>Large</option>
                                                                    </select>
                                                                </label>
                                                            </p>
                                                        <?php endif; ?>
                                    				<?php endif; ?>
                                                    <div class="menu-item-actions description-wide submitbox">
                                                    	<a href="<?php echo esc_url( wp_nonce_url( admin_url('options-general.php?page=visual-form-builder&amp;action=delete_field&amp;form=' . $form_nav_selected_id . '&amp;field=' . $field->field_id ), 'delete-field-' . $form_nav_selected_id ) ); ?>" class="item-delete submitdelete deletion">Remove</a>
                                                    </div>
                                                <input type="hidden" name="field_id[<?php echo $field->field_id; ?>]" value="<?php echo $field->field_id; ?>" />
                                            	</div>
                                            </li>
                                <?php
									endforeach;
									echo '</ul>';
								endif;
								?>
                                    
								<?php endif; ?>
                                    </div>
                                 </div>
                                <div id="nav-menu-footer">
                                	<div class="major-publishing-actions">
                                        <div class="publishing-action">
                                            <input type="submit" value="<?php echo ( $action == 'create_form' ) ? 'Create Form' : 'Save Form'; ?>" class="button-primary menu-save" id="save_form" name="save_form" />
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
		</div>
	<?php
		endif;
	}
	
	/**
	 * Handle confirmation when form is submitted
	 * 
	 * @since 1.3
	 */
	function confirmation(){
		global $wpdb;
		
		$form_id = ( isset( $_REQUEST['form_id'] ) ) ? $_REQUEST['form_id'] : '';
		
		if ( isset( $_REQUEST['visual-form-builder-submit'] ) && in_array( $_REQUEST['visual-form-builder-submit'], array( 'Submit', 'submit' ) ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'visual-form-builder-nonce' ) ) {
			/* Get forms */
			$order = sanitize_sql_orderby( 'form_id DESC' );
			$query 	= "SELECT * FROM $this->form_table_name WHERE form_id = $form_id ORDER BY $order";
			
			$forms 	= $wpdb->get_results( $query );
			
			foreach ( $forms as $form ) {
				/* If text, return output and format the HTML for display */
				if ( 'text' == $form->form_success_type )
					return stripslashes( html_entity_decode( wp_kses_stripslashes( $form->form_success_message ) ) );
				/* If page, redirect to the permalink */
				elseif ( 'page' == $form->form_success_type ) {
					$page = get_permalink( $form->form_success_message );
					wp_redirect( $page );
					exit();
				}
				/* If redirect, redirect to the URL */
				elseif ( 'redirect' == $form->form_success_type ) {
					wp_redirect( $form->form_success_message );
					exit();
				}
			}
		}
	}
	
	/**
	 * Output form via shortcode
	 * 
	 * @since 1.0
	 */
	public function form_code( $atts ) {
		global $wpdb;
		
		/* Extract shortcode attributes, set defaults */
		extract( shortcode_atts( array(
			'id' => ''
			), $atts ) 
		);
		
		/* Get form id.  Allows use of [vfb id=1] or [vfb 1] */
		$form_id = ( isset( $id ) && !empty( $id ) ) ? $id : $atts[0];
		
		$open_fieldset = false;
		
		/* If form is submitted, show success message, otherwise the form */
		if ( isset( $_REQUEST['visual-form-builder-submit'] ) && in_array( $_REQUEST['visual-form-builder-submit'], array( 'Submit', 'submit' ) ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'visual-form-builder-nonce' ) && isset( $_REQUEST['form_id'] ) && $_REQUEST['form_id'] == $form_id ) {
			$output = $this->confirmation();
		}
		else {
			/* Get forms */
			$order = sanitize_sql_orderby( 'form_id DESC' );
			$query 	= "SELECT * FROM $this->form_table_name WHERE form_id = $form_id ORDER BY $order";
			
			$forms 	= $wpdb->get_results( $query );
			
			/* Get fields */
			$order_fields = sanitize_sql_orderby( 'field_sequence ASC' );
			$query_fields = "SELECT * FROM $this->field_table_name WHERE form_id = $form_id ORDER BY $order_fields";
			
			$fields = $wpdb->get_results( $query_fields );
			
			foreach ( $forms as $form ) :
						
				$output = '<form id="' . $form->form_key . '" class="visual-form-builder" method="post" enctype="multipart/form-data">
							<input type="hidden" name="form_id" value="' . $form->form_id . '" />';
				$output .= wp_nonce_field( 'visual-form-builder-nonce', '_wpnonce', false, false );
				
				foreach ( $fields as $field ) {
					if ( $field->field_type == 'fieldset' ) {
						/* Close each fieldset */
						if ( $open_fieldset == true )
							$output .= '</ul><br /></fieldset>';
						
						$output .= '<fieldset><div class="legend"><h3>' . stripslashes( $field->field_name ) . '</h3></div><ul>';
						$open_fieldset = true;
					}
					else {
						/* If field is required, build the span and add setup the 'required' class */
						$required_span = ( !empty( $field->field_required ) && $field->field_required === 'yes' ) ? ' <span>*</span>' : '';
						$required = ( !empty( $field->field_required ) && $field->field_required === 'yes' ) ? ' required' : '';
						$validation = ( !empty( $field->field_validation ) ) ? " $field->field_validation" : '';
						
						$output .= '<li><label for="vfb-' . $field->field_key . '" class="desc">'. stripslashes( $field->field_name ) . $required_span . '</label>';
					}
					
					switch ( $field->field_type ) {
						case 'text' :
						case 'email' :
						case 'url' :
						case 'currency' :
						case 'number' :
						case 'phone' :
							
							if ( !empty( $field->field_description ) )
								$output .= '<span><input type="text" name="vfb-' . esc_html( $field->field_key ) . '" id="vfb-' . esc_html( $field->field_key )  . '" value="" class="text ' . $field->field_size . $required . $validation . '" /><label>' . $field->field_description . '</label></span>';
							else
								$output .= '<input type="text" name="vfb-' . esc_html( $field->field_key ) . '" id="vfb-' . esc_html( $field->field_key )  . '" value="" class="text ' . $field->field_size . $required . $validation . '" />';

						break;
						
						case 'textarea' :
							
							if ( !empty( $field->field_description ) )
								$output .= '<span><label>' . stripslashes( $field->field_description ) . '</label></span>';
	
							$output .= '<textarea name="vfb-'. $field->field_key . '" id="vfb-'. $field->field_key . '" class="textarea ' . $field->field_size . $required . '"></textarea>';
								
						break;
						
						case 'select':
							if ( !empty( $field->field_description ) )
								$output .= '<span><label>' . stripslashes( $field->field_description ) . '</label></span>';
									
							$output .= '<select name="vfb-'. $field->field_key . '" id="vfb-'. $field->field_key . '" class="select ' . $field->field_size . $required . '">';
							
							$options = explode( ',', unserialize( $field->field_options ) );
							
							/* Loop through each option and output */
							foreach ( $options as $option => $value ) {
								$output .= '<option value="' . $value . '">'. $value. '</option>';
							}
							
							$output .= '</select>';
							
						break;
						
						case 'radio':
							
							if ( !empty( $field->field_description ) )
								$output .= '<span><label>' . stripslashes( $field->field_description ) . '</label></span>';
							
							$options = explode( ',', unserialize( $field->field_options ) );
							
							$output .= '<div>';
							
							/* Loop through each option and output */
							foreach ( $options as $option => $value ) {
								$output .= '<span>
												<input type="radio" name="vfb-'. $field->field_key . '" id="vfb-'. $field->field_key . '-' . $option . '" value="'. stripslashes( $value ) . '" class="radio' . $required . '" />'. 
											' <label for="vfb-' . $field->field_key . '-' . $option . '" class="choice">' . stripslashes( $value ) . '</label>' .
											'</span>';
							}
							
							$output .= '</div>';
							
						break;
						
						case 'checkbox':
							
							if ( !empty( $field->field_description ) )
								$output .= '<span><label>' . stripslashes( $field->field_description ) . '</label></span>';
							
							$options = explode( ',', unserialize( $field->field_options ) );
							
							$output .= '<div>';

							/* Loop through each option and output */
							foreach ( $options as $option => $value ) {
								
								$output .= '<span><input type="checkbox" name="vfb-'. $field->field_key . '[]" id="vfb-'. $field->field_key . '-' . $option . '" value="'. trim( stripslashes( $value ) ) . '" class="checkbox' . $required . '" />'. 
									' <label for="vfb-' . $field->field_key . '-' . $option . '" class="choice">' . trim( stripslashes( $value ) ) . '</label></span>';
							}
							
							$output .= '</div>';
						
						break;
						
						case 'address':
							
							if ( !empty( $field->field_description ) )
								$output .= '<span><label>' . stripslashes( $field->field_description ) . '</label></span>';
								
								$countries = array( "Afghanistan", "Albania", "Algeria", "Andorra", "Angola", "Antigua and Barbuda", "Argentina", "Armenia", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bhutan", "Bolivia", "Bosnia and Herzegovina", "Botswana", "Brazil", "Brunei", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Central African Republic", "Chad", "Chile", "China", "Colombi", "Comoros", "Congo (Brazzaville)", "Congo", "Costa Rica", "Cote d'Ivoire", "Croatia", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "East Timor (Timor Timur)", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Fiji", "Finland", "France", "Gabon", "Gambia, The", "Georgia", "Germany", "Ghana", "Greece", "Grenada", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Honduras", "Hungary", "Iceland", "India", "Indonesia", "Iran", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, North", "Korea, South", "Kuwait", "Kyrgyzstan", "Laos", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libya", "Liechtenstein", "Lithuania", "Luxembourg", "Macedonia", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Mauritania", "Mauritius", "Mexico", "Micronesia", "Moldova", "Monaco", "Mongolia", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepa", "Netherlands", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Poland", "Portugal", "Qatar", "Romania", "Russia", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Serbia and Montenegro", "Seychelles", "Sierra Leone", "Singapore", "Slovakia", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "Spain", "Sri Lanka", "Sudan", "Suriname", "Swaziland", "Sweden", "Switzerland", "Syria", "Taiwan", "Tajikistan", "Tanzania", "Thailand", "Togo", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States of America", "Uruguay", "Uzbekistan", "Vanuatu", "Vatican City", "Venezuela", "Vietnam", "Yemen", "Zambia", "Zimbabwe" );
							$output .= '<div>
								<span class="full">
					
									<input type="text" name="vfb-address" maxlength="150" id="vfb-address" class="text medium' . $required . '" />
									<label>Address</label>
								</span>
								<span class="full">
									<input type="text" name="vfb-address-2" maxlength="150" id="vfb-address-2" class="text medium" />
									<label>Address Line 2</label>
								</span>
								<span class="left">
					
									<input type="text" name="vfb-city" maxlength="150" id="vfb-city" class="text medium' . $required . '" />
									<label>City</label>
								</span>
								<span class="right">
									<input type="text" name="vfb-state" maxlength="150" id="vfb-state" class="text medium' . $required . '" />
									<label>State / Province / Region</label>
								</span>
								<span class="left">
					
									<input type="text" name="vfb-zip" maxlength="150" id="vfb-zip" class="text medium' . $required . '" />
									<label>Postal / Zip Code</label>
								</span>
								<span class="right">
								<select class="select' . $required . '" name="vfb-country" id="vfb-country">
								<option selected="selected" value=""></option>';
								
								foreach ( $countries as $country ) {
									$output .= "<option value='$country'>$country</option>";
								}
								
								$output .= '</select>
									<label>Country</label>
								</span>
							</div>';
	
						
						break;
						
						case 'date':
							
							if ( !empty( $field->field_description ) )
								$output .= '<span><input type="text" name="vfb-' . esc_html( $field->field_key ) . '" id="vfb-' . esc_html( $field->field_key )  . '" value="" class="text vfb-date-picker ' . $field->field_size . $required . '" /><label>' . stripslashes( $field->field_description ) . '</label></span>';
							else
								$output .= '<input type="text" name="vfb-' . esc_html( $field->field_key ) . '" id="vfb-' . esc_html( $field->field_key )  . '" value="" class="text vfb-date-picker ' . $field->field_size . $required . '" />';
							
						break;
						
						case 'time' :
							if ( !empty( $field->field_description ) )
								$output .= '<span><label>' . stripslashes( $field->field_description ) . '</label></span>';

							/* Get the time format (12 or 24) */
							$time_format = str_replace( 'time-', '', $validation );
							/* Set whether we start with 0 or 1 and how many total hours */
							$hour_start = ( $time_format == '12' ) ? 1 : 0;
							$hour_total = ( $time_format == '12' ) ? 12 : 23;
							
							/* Hour */
							$output .= '<span class="time"><select name="vfb-'. $field->field_key . '[hour]" id="vfb-'. $field->field_key . '" class="select' . $required . '">';
							for ( $i = $hour_start; $i <= $hour_total; $i++ ) {
								/* Add the leading zero */
								$hour = ( $i < 10 ) ? "0$i" : $i;
								$output .= "<option value='$hour'>$hour</option>";
							}
							$output .= '</select><label>HH</label></span>';
							
							/* Minute */
							$output .= '<span class="time"><select name="vfb-'. $field->field_key . '[min]" id="vfb-'. $field->field_key . '" class="select' . $required . '">';
							for ( $i = 0; $i <= 55; $i+=5 ) {
								/* Add the leading zero */
								$min = ( $i < 10 ) ? "0$i" : $i;
								$output .= "<option value='$min'>$min</option>";
							}
							$output .= '</select><label>MM</label></span>';
							
							/* AM/PM */
							if ( $time_format == '12' )
								$output .= '<span class="time"><select name="vfb-'. $field->field_key . '[ampm]" id="vfb-'. $field->field_key . '" class="select' . $required . '"><option value="AM">AM</option><option value="PM">PM</option></select><label>AM/PM</label></span>';							
						break;
						
						case 'html' :
							
							if ( !empty( $field->field_description ) )
								$output .= '<span><label>' . stripslashes( $field->field_description ) . '</label></span>';

							$output .= '<script type="text/javascript">edToolbar("vfb-' . $field->field_key . '");</script>';
							$output .= '<textarea name="vfb-'. $field->field_key . '" id="vfb-'. $field->field_key . '" class="textarea vfbEditor ' . $field->field_size . $required . '"></textarea>';
								
						break;
						
						case 'file-upload' :
							
							if ( !empty( $field->field_description ) )
								$output .= '<span><input type="file" size="35" name="vfb-' . esc_html( $field->field_key ) . '" id="vfb-' . esc_html( $field->field_key )  . '" value="" class="text ' . $field->field_size . $required . $validation . '" /><label>' . $field->field_description . '</label></span>';
							else
								$output .= '<input type="file" size="35" name="vfb-' . esc_html( $field->field_key ) . '" id="vfb-' . esc_html( $field->field_key )  . '" value="" class="text ' . $field->field_size . $required . $validation . '" />';
						
									
						break;

					}
				
					$output .= '</li>';
				}
				
				/* Close user-added fields */
				$output .= '</ul><br /></fieldset>';
				
				/* Output our security test */
				$output .= '<fieldset>
							<div class="legend">
								<h3>Verification</h3>
							</div>
							<ul>
								<li>
									<label class="desc">Please enter any two digits with <strong>no</strong> spaces (Example: 12) <span>*</span></label>
									<div>
										<input type="text" name="vfb-secret" id="vfb-secret" class="text medium required digits" />
									</div>
								</li>
								<div style="display:none;">
								<li>
									<label for="vfb-spam">This box is for spam protection - <strong>please leave it blank</strong>:</label>
									<div>
										<input name="vfb-spam" id="vfb-spam" />
									</div>
								</li>
								</div>
								<li>
									<input type="submit" name="visual-form-builder-submit" value="Submit" class="submit" id="sendmail" />
								</li>
							</ul>
						</fieldset></form>';
			
			endforeach;
		}
		
		return $output;
	}
	
	/**
	 * Handle emailing the content
	 * 
	 * @since 1.0
	 * @uses wp_mail() E-mails a message
	 */
	public function email() {
		global $wpdb, $post;
		
		/* Security check before moving any further */
		if ( isset( $_REQUEST['visual-form-builder-submit'] ) && $_REQUEST['visual-form-builder-submit'] == 'Submit' && $_REQUEST['vfb-spam'] == '' && is_numeric( $_REQUEST['vfb-secret'] ) && strlen( $_REQUEST['vfb-secret'] ) == 2 ) :
			$nonce = $_REQUEST['_wpnonce'];
			
			/* Security check to verify the nonce */
			if ( ! wp_verify_nonce( $nonce, 'visual-form-builder-nonce' ) )
				die(__('Security check'));
			
			/* Set submitted action to display success message */
			$this->submitted = true;
			
			/* Tells us which form to get from the database */
			$form_id = absint( $_REQUEST['form_id'] );
			
			/* Query to get all forms */
			$order = sanitize_sql_orderby( 'form_id DESC' );
			$query = "SELECT * FROM $this->form_table_name WHERE form_id = $form_id ORDER BY $order";
			
			/* Build our forms as an object */
			$forms = $wpdb->get_results( $query );
			
			/* Get sender and email details */
			foreach ( $forms as $form ) {
				$form_title = $form->form_title;
				$form_subject = $form->form_email_subject;
				$form_to = explode( ',', unserialize( $form->form_email_to ) );
				$form_from = $form->form_email_from;
				$form_from_name = $form->form_email_from_name;
			}
			
			/* Sender name override query */
			$sender_query = "SELECT fields.field_key FROM $this->form_table_name AS forms LEFT JOIN $this->field_table_name AS fields ON forms.form_email_from_name_override = fields.field_id WHERE forms.form_id = $form_id";
			$senders = $wpdb->get_results( $sender_query );

			/* Sender email override query */
			$email_query = "SELECT fields.field_key FROM $this->form_table_name AS forms LEFT JOIN $this->field_table_name AS fields ON forms.form_email_from_override = fields.field_id WHERE forms.form_id = $form_id";
			$emails = $wpdb->get_results( $email_query );
			
			/* Loop through name results and assign sender name to override, if needed */
			foreach( $senders as $sender ) {
				if ( !empty( $sender->field_key ) )
					$form_from_name = $_POST[ 'vfb-' . $sender->field_key ];
			}

			/* Loop through email results and assign sender email to override, if needed */
			foreach ( $emails as $email ) {
				if ( !empty( $email->field_key ) )
					$form_from = $_POST[ 'vfb-' . $email->field_key ];
			}
			
			/* Prepare the beginning of the content */
			$message = '<html><body><table rules="all" style="border-color: #666;" cellpadding="10">';
			
			/* Loop through each form field and build the body of the message */
			foreach ( $_POST as $key => $value ) {
				
				/* Remove prefix, dashes and lowercase */
				$key = str_replace( 'vfb-', '', $key );
				$key = strtolower( str_replace( '-', ' ', $key ) );
				
				/* If time field, build proper output */
				if ( is_array( $value ) && array_key_exists( 'hour', $value )  && array_key_exists( 'min', $value ) )
					$value = ( array_key_exists( 'ampm', $value ) ) ? substr_replace( implode( ':', $value ), ' ', 5, 1 ) : implode( ':', $value );
				/* If multiple values, build the list */
				elseif ( is_array( $value ) )
					$value = implode( ', ', $value );
				/* Lastly, handle single values */
				else
					$value = esc_html( $value );
			
				/* Hide fields that aren't necessary to the body of the message */
				if ( !in_array( $key, array( 'spam', 'secret', 'visual form builder submit', '_wpnonce', 'form_id' ) ) ) {
					$message .= '<tr><td><strong>' . ucwords( $key ) . ': </strong></td><td>' . $value . '</td></tr>';
					$fields[ $key ] = $value;
				}	
			}
			
			/* Prepare the attachments */
			if ( isset( $_FILES ) ) {
				foreach ( $_FILES as $k => $v ) {
					if ( $v['size'] > 0 ) {
						/* Options array for the wp_handle_upload function. 'test_upload' => false */
						$upload_overrides = array( 'test_form' => false ); 
						
						/* We need to include the file that runs the wp_handle_upload function */
						require_once( ABSPATH . 'wp-admin/includes/file.php' );
						
						/* Handle the upload using WP's wp_handle_upload function. Takes the posted file and an options array */
						$uploaded_file = wp_handle_upload( $v, $upload_overrides );
						
						/* If the wp_handle_upload call returned a local path for the image */
						if ( isset( $uploaded_file['file'] ) ) {
							$attachments[$k] = $uploaded_file['file'];
							
							$key = str_replace( 'vfb-', '', $k );
							$key = strtolower( str_replace( '-', ' ', $key ) );
							$fields[$key] = $uploaded_file['url'];
							
							$message .= '<tr><td><strong>' . ucwords( $key ) . ': </strong></td><td><a href="' . $uploaded_file['url'] . '">' . $uploaded_file['url'] . '</a></td></tr>';
						}
					}
				}
			}
			
			/* Setup our entries data */
			$entry = array(
				'form_id' => $form_id,
				'data' => serialize( $fields ),
				'subject' => $form_subject,
				'sender_name' => $form_from_name,
				'sender_email' => $form_from,
				'emails_to' => serialize( $form_to ),
				'date_submitted' => date_i18n( 'Y-m-d G:i:s' ),
				'ip_address' => $_SERVER['REMOTE_ADDR']
			);
			
			/* Insert this data into the entries table */
			$wpdb->insert( $this->entries_table_name, $entry );
			
			/* Close out the content */
			$message .= '</table></body></html>';
			
			/* Set headers to send an HTML email */
			$headers = "MIME-Version: 1.0\n".
						"From: " . $form_from_name . " <" . $form_from . ">\n" .
						"Content-Type: text/html; charset=\"" . get_settings( 'blog_charset' ) . "\"\n";
			
			/* Send the mail */
			foreach ( $form_to as $email ) {
				$mail_sent = wp_mail( $email, esc_html( $form_subject ), $message, $headers, $attachments );
			}
		elseif ( isset( $_REQUEST['visual-form-builder-submit'] ) ) :
			/* If any of the security checks fail, provide some user feedback */
			if ( $_REQUEST['vfb-spam'] !== '' || !is_numeric( $_REQUEST['vfb-secret'] ) || strlen( $_REQUEST['vfb-secret'] ) !== 2 )
				wp_die( 'Ooops! Looks like you have failed the security validation for this form. Please go back and try again.' );
		endif;
	}
}

/* On plugin activation, install the databases and add/update the DB version */
register_activation_hook( __FILE__, array( 'Visual_Form_Builder', 'install_db' ) );
?>