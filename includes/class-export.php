<?php
/**
 * Class that builds our Entries table
 * 
 * @since 1.2
 */
class VisualFormBuilder_Export {
	
	public function __construct(){
		global $wpdb;
		
		// CSV delimiter
		$this->delimiter = apply_filters( 'vfb_csv_delimiter', ',' );
		
		// Setup our default columns
		$this->default_cols = array(
			'entries_id' 		=> __( 'Entries ID' , 'vfb_pro_display_entries'),
			'date_submitted' 	=> __( 'Date Submitted' , 'vfb_pro_display_entries'),
			'ip_address' 		=> __( 'IP Address' , 'vfb_pro_display_entries'),
			'subject' 			=> __( 'Subject' , 'vfb_pro_display_entries'),
			'sender_name' 		=> __( 'Sender Name' , 'vfb_pro_display_entries'),
			'sender_email' 		=> __( 'Sender Email' , 'vfb_pro_display_entries'),
			'emails_to' 		=> __( 'Emailed To' , 'vfb_pro_display_entries'),
		);
		
		// Setup global database table names
		$this->field_table_name 	= $wpdb->prefix . 'visual_form_builder_fields';
		$this->form_table_name 		= $wpdb->prefix . 'visual_form_builder_forms';
		$this->entries_table_name 	= $wpdb->prefix . 'visual_form_builder_entries';
		
		add_action( 'admin_init', array( &$this, 'display' ) );
		
		$this->process_export_action();
	}
	
	/**
	 * Display the export form
	 *
	 * @since 1.7
	 *
	 */
	public function display(){
		global $wpdb;
		
		// Query to get all forms
		$order = sanitize_sql_orderby( 'form_id ASC' );
		$where = apply_filters( 'vfb_pre_get_forms_export', '' );
		$forms = $wpdb->get_results( "SELECT * FROM $this->form_table_name WHERE 1=1 $where ORDER BY $order" );
		
		if ( !$forms ) {
			echo '<div class="vfb-form-alpha-list"><h3 id="vfb-no-forms">You currently do not have any forms.  Click on the <a href="' . esc_url( admin_url( 'admin.php?page=vfb-add-new' ) ) . '">New Form</a> button to get started.</h3></div>';
			//return;
		}
		
		// Safe to get entries now
		$entries = $wpdb->get_results( $wpdb->prepare( "SELECT form_id, data FROM $this->entries_table_name WHERE 1=1 AND form_id = %d", $forms[0]->form_id ), ARRAY_A );
		
		// Return nothing if no entries found
		if ( !$entries )
			$no_entries = __( 'No entries to pull field names from.', 'vfb_pro_display_entries' );
		else {
			// Get columns
			$columns = $this->get_cols( $entries );
			
			// Get JSON data
			$data = json_decode( $columns, true );
		}
		
		?>
        <form method="post" id="vfb-export">
        	<p><?php _e( 'Backup and save some or all of your Visual Form Builder data.', 'visual-form-builder' ); ?></p>
        	<p><?php _e( 'Once you have saved the file, you will be able to import Visual Form Builder Pro data from this site into another site.', 'visual-form-builder' ); ?></p>
        	<h3><?php _e( 'Choose what to export', 'visual-form-builder' ); ?></h3>
        	
        	<p><label><input type="radio" name="content" value="all" disabled="disabled" /> <?php _e( 'All data', 'visual-form-builder' ); ?></label></p>
        	<p class="description"><?php _e( 'This will contain all of your forms, fields, entries, and email design settings.', 'visual-form-builder' ); ?><br><strong>*<?php _e( 'Only available in Visual Form Builder Pro', 'visual-form-builder' ); ?>*</strong></p>
        	
        	<p><label><input type="radio" name="content" value="forms" disabled="disabled" /> <?php _e( 'Forms', 'visual-form-builder' ); ?></label></p>
        	<p class="description"><?php _e( 'This will contain all of your forms, fields, and email design settings', 'visual-form-builder' ); ?>.<br><strong>*<?php _e( 'Only available in Visual Form Builder Pro', 'visual-form-builder' ); ?>*</strong></p>
        	
        	<p><label><input type="radio" name="content" value="entries" checked="checked" /> <?php _e( 'Entries', 'visual-form-builder' ); ?></label></p>
        	
        	<ul id="entries-filters" class="vfb-export-filters">
        		<li><p class="description"><?php _e( 'This will export entries in either a .csv, .txt, or .xls and cannot be used with the Import.  If you need to import entries on another site, please use the All data option above.', 'visual-form-builder' ); ?></p></li>
        		<li>
        			<label for="format"><?php _e( 'Format', 'visual-form-builder' ); ?>:</label>
        			<select name="format">
        				<option value="csv" selected="selected"><?php _e( 'Comma Separated (.csv)', 'visual-form-builder' ); ?></option>
        				<option value="txt" disabled="disabled"><?php _e( 'Tab Delimited (.txt) - Pro only', 'visual-form-builder' ); ?></option>
        				<option value="xls" disabled="disabled"><?php _e( 'Excel (.xls) - Pro only', 'visual-form-builder' ); ?></option>
        			</select>
        		</li>
        		<li>
		        	<label for="form_id"><?php _e( 'Form', 'visual-form-builder' ); ?>:</label> 
		            <select name="form_id">
					<?php
						foreach ( $forms as $form ) {
							echo '<option value="' . $form->form_id . '" id="' . $form->form_key . '">' . stripslashes( $form->form_title ) . '</option>';
						}
					?>
					</select>
        		</li>
        		<li>
        			<label><?php _e( 'Date Range', 'visual-form-builder' ); ?>:</label>
        			<select name="entries_start_date">
        				<option value="0">Start Date</option>
        				<?php $this->months_dropdown(); ?>
        			</select>
        			<select name="entries_end_date">
        				<option value="0">End Date</option>
        				<?php $this->months_dropdown(); ?>
        			</select>
        		</li>
        		<li id="vfb-export-entries-fields">
        			<label><?php _e( 'Fields', 'visual-form-builder' ); ?>:</label>
        			<?php
								if ( isset( $no_entries ) ) :
									echo $no_entries;
								else :
									$array = array();
									foreach ( $data as $row ) :
										$array = array_merge( $row, $array );
									endforeach;
									
									$array = array_keys( $array );
									$array = array_values( array_merge( $this->default_cols, $array ) );
									
									foreach ( $array as $k => $v ) :
										$selected = ( in_array( $v, $this->default_cols ) ) ? ' checked="checked"' : '';
										
										echo sprintf( '<label for="vfb-display-entries-val-%1$d"><input name="entries_columns[]" class="vfb-display-entries-vals" id="vfb-display-entries-val-%1$d" type="checkbox" value="%2$s" %3$s> %4$s</label><br>', $k, $v, $selected, $v );
									endforeach;
							?>
							
							
							
							<?php endif; ?>
        		</li>
        	</ul>
        	
         <?php submit_button( __( 'Download Export File', 'visual-form-builder' ) ); ?>
        </form>
<?php
	}
	
	
	/**
	 * Build the entries export array
	 *
	 * @since 1.7
	 *
	 * @param array $args Filters defining what should be included in the export
	 */
	public function export_entries( $args = array() ) {
		global $wpdb;
		
		// Set inital fields as a string
		$initial_fields = implode( ',', $this->default_cols );
		
		$defaults = array( 
			'content' 		=> 'entries',
			'format' 		=> 'csv',
			'form_id' 		=> 0,
			'start_date' 	=> false, 
			'end_date' 		=> false,
			'fields'		=> $initial_fields
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		$where = '';
		
		if ( 'entries' == $args['content'] ) {
			if ( 0 !== $args['form_id'] )
				$where .= $wpdb->prepare( " AND form_id = %d", $args['form_id'] );
				
			if ( $args['start_date'] )
				$where .= $wpdb->prepare( " AND date_submitted >= %s", date( 'Y-m-d', strtotime( $args['start_date'] ) ) );
				
			if ( $args['end_date'] )
				$where .= $wpdb->prepare( " AND date_submitted < %s", date( 'Y-m-d', strtotime('+1 month', strtotime( $args['end_date'] ) ) ) );
		}
		
		$entries = $wpdb->get_results( "SELECT * FROM $this->entries_table_name WHERE 1=1 $where" );
		$form_key = $wpdb->get_var( $wpdb->prepare( "SELECT form_key, form_title FROM $this->form_table_name WHERE form_id = %d", $args['form_id'] ) );
		$form_title = $wpdb->get_var( null, 1 );
		
		$sitename = sanitize_key( get_bloginfo( 'name' ) );
		if ( ! empty($sitename) ) $sitename .= '.';
		$filename = $sitename . 'vfb.' . "$form_key." . date( 'Y-m-d' ) . ".{$args['format']}";
		
		$content_type = 'text/csv';
		
		// Return nothing if no entries found
		if ( !$entries )
			return;
		
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( "Content-Type: $content_type; charset=" . get_option( 'blog_charset' ), true );
		header( 'Expires: 0' );
		header( 'Pragma: public' );
		
		$fh = @fopen( 'php://output', 'w' );
		
		// Get columns
		$columns = $this->get_cols( $entries );
		
		// Get JSON data
		$data = json_decode( $columns, true );
		
		// Build array of fields to display
		$fields = !is_array( $args['fields'] ) ? array_map( 'trim', explode( ',', $args['fields'] ) ) : $args['fields'];
		
		// Build headers
		fputcsv( $fh, $fields );
				
		$test = array();
		
		// Build table rows and cells		
		foreach ( $data as $row ) :
			
			foreach ( $fields as $label ) {
				if ( isset( $row[ $label ] ) && in_array( $label, $fields ) )
					$test[ $label ] = $row[ $label ];
			}
			
			fputcsv( $fh, $test );
			
		endforeach;
		
		// Close the file
		fclose( $fh );
		
		exit();
	}

	public function get_cols( $entries ) {
		
		
		// Initialize row index at 0
		$row = 0;
		$output = array();
		
		// Loop through all entries
		foreach ( $entries as $entry ) :
		
			foreach ( $entry as $key => $value ) :
				
				switch ( $key ) {
					case 'entries_id':
					case 'date_submitted':
					case 'ip_address':
					case 'subject':
					case 'sender_name':
					case 'sender_email':
						$output[ $row ][ $this->default_cols[ $key ] ] = $value;
					break;
					
					case 'emails_to':
						$output[ $row ][ $this->default_cols[ $key ] ] = implode( ',', maybe_unserialize( $value ) );
					break;
					
					case 'data':
						// Unserialize value only if it was serialized
						$fields = maybe_unserialize( $value );
						
						// Loop through our submitted data
						foreach ( $fields as $field_key => $field_value ) :
							// Cast each array as an object
							$obj = (object) $field_value;
							
							switch ( $obj->type ) {
								case 'fieldset' :
								case 'section' :
								case 'instructions' :
								case 'page-break' :
								case 'verification' :
								case 'secret' :
								case 'submit' :
								break;
								
								default :
									$output[ $row ][ $obj->name ] = $obj->value;
								break;
							} //end $obj switch
						endforeach; // end $fields loop
					break;
				} //end $key switch
			endforeach; // end $entry loop
			$row++;
		endforeach; //end $entries loop
		
		return json_encode( $output );	
	}
	
	/**
	 * Return the entries data formatted for CSV
	 *
	 * @since 1.7
	 *
	 * @param array $cols The multidimensional array of entries data
	 * @param int $row The row index
	 */
	public function csv( $cols, $row ) {
		// Setup our CSV vars
		$csv_headers = NULL;
		$csv_rows = array();
		
		// Loop through each column
		foreach ( $cols as $data ) {
			// End our header row, if needed
			if ( $csv_headers )
				$csv_headers .= $this->delimiter;
			
			// Build our headers
			$csv_headers .= stripslashes( htmlentities( $data['header'] ) );
			
			// Loop through each row of data and add to our CSV
			for ( $i = 0; $i < $row; $i++ ) {
				// End our row of data, if needed
				if ( array_key_exists( $i, $csv_rows ) && !empty( $csv_rows[ $i ] ) )
					$csv_rows[ $i ] .= $this->delimiter;
				elseif ( !array_key_exists( $i, $csv_rows ) )
					$csv_rows[ $i ] = '';
				
				// Add a starting quote for this row's data
				$csv_rows[ $i ] .= '"';
				
				// If there's data at this point, add it to the row
				if ( array_key_exists( $i, $data[ 'data' ] ) )
					$csv_rows[ $i ] .=  $data[ 'data' ][ $i ];
				
				// Add a closing quote for this row's data
				$csv_rows[ $i ] .= '"';				
			}			
		}
		
		// Print headers for the CSV
		echo "$csv_headers\n";
		
		// Print each row of data for the CSV
		foreach ( $csv_rows as $row ) {
			echo "$row\n";
		}
	}
		
	/**
	 * Return the selected export type
	 *
	 * @since 1.7
	 *
	 * @return string|bool The type of export
	 */
	public function export_action() {
		if ( isset( $_REQUEST['content'] ) )
			return $_REQUEST['content'];
	
		return false;
	}
	
	/**
	 * Determine which export process to run
	 *
	 * @since 1.7
	 *
	 */
	public function process_export_action() {
		
		$args = array();
		
		if ( !isset( $_REQUEST['content'] ) || 'entries' == $_REQUEST['content'] ) {
			$args['content'] = 'entries';
			
			$args['format'] = 'csv';
				
			if ( isset( $_REQUEST['form_id'] ) )
				$args['form_id'] = (int) $_REQUEST['form_id'];
			
			if ( isset( $_REQUEST['entries_start_date'] ) || isset( $_REQUEST['entries_end_date'] ) ) {
				$args['start_date'] = $_REQUEST['entries_start_date'];
				$args['end_date'] = $_REQUEST['entries_end_date'];
			}
			
			if ( isset( $_REQUEST['entries_columns'] ) )
				$args['fields'] = array_map( 'esc_html',  $_REQUEST['entries_columns'] );
		}
		
		switch( $this->export_action() ) {
			case 'entries' :
				$this->export_entries( $args );
				die(1);
			break;
		}
	}
	
	/**
	 * Wrap given string in XML CDATA tag.
	 *
	 * @since 1.7
	 *
	 * @param string $str String to wrap in XML CDATA tag.
	 * @return string
	 */
	function cdata( $str ) {
		if ( seems_utf8( $str ) == false )
			$str = utf8_encode( $str );

		$str = '<![CDATA[' . str_replace( ']]>', ']]]]><![CDATA[>', $str ) . ']]>';

		return $str;
	}
	
	/**
	 * Display Year/Month filter
	 * 
	 * @since 1.7
	 */
	public function months_dropdown() {
		global $wpdb, $wp_locale;
		
		$where = apply_filters( 'vfb_pre_get_entries', '' );
		
	    $months = $wpdb->get_results( "
			SELECT DISTINCT YEAR( forms.date_submitted ) AS year, MONTH( forms.date_submitted ) AS month
			FROM $this->entries_table_name AS forms
			WHERE 1=1 $where
			ORDER BY forms.date_submitted DESC
		" );

		$month_count = count( $months );

		if ( !$month_count || ( 1 == $month_count && 0 == $months[0]->month ) )
			return;
		
		$m = isset( $_REQUEST['m'] ) ? (int) $_REQUEST['m'] : 0;
?>
<?php
		foreach ( $months as $arc_row ) {
			if ( 0 == $arc_row->year )
				continue;
			
			$month = zeroise( $arc_row->month, 2 );
			$year = $arc_row->year;

			printf( "<option value='%s'>%s</option>\n",
				esc_attr( $arc_row->year . '-' . $month ),
				sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year )
			);
		}
?>
<?php
	}
}
?>