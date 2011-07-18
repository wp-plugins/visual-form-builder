<?php

/* Include the wp_list_table class if running <WP 3.1 */
if( !class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class that builds our Entries table
 * 
 * @since 1.2
 */
class VisualFormBuilder_Entries_List extends WP_List_Table {
		
	function __construct(){
		global $status, $page, $wpdb;
		
		/* Setup global database table names */
		$this->field_table_name = $wpdb->prefix . 'visual_form_builder_fields';
		$this->form_table_name = $wpdb->prefix . 'visual_form_builder_forms';
		$this->entries_table_name = $wpdb->prefix . 'visual_form_builder_entries';
		
		/* Set parent defaults */
		parent::__construct( array(
			'singular'  => 'entry',
			'plural'    => 'entries',
			'ajax'      => false
		) );
	}
	
	/**
	 * Display column names. We'll handle the Form column separately.
	 * 
	 * @since 1.2
	 * @returns $item string Column name
	 */
	function column_default($item, $column_name){
		switch ( $column_name ) {
			case 'subject':
			case 'sender_name':
			case 'sender_email':
			case 'emails_to':
			case 'date':
			case 'ip_address':
				return $item[ $column_name ];
		}
	}
	
	/**
	 * Builds the on:hover links for the Form column
	 * 
	 * @since 1.2
	 */
	function column_form($item){
		 
		/* Build row actions */
		$actions = array(
			'view' => sprintf( '<a href="#" id="%4$s" class="view-entry">View</a>', $_REQUEST['page'], $_REQUEST['view'], 'view', $item['entry_id'] ),
			'delete' => sprintf( '<a href="?page=%s&view=%s&action=%s&entry=%s">Delete</a>', $_REQUEST['page'], $_REQUEST['view'], 'delete', $item['entry_id'] ),
		);
		
		/* Build the form's data for display only */
		$data = '<fieldset class="visual-form-builder-inline-edit"><div class="visual-form-builder-inline-edit-col">';
		foreach ( $item['data'] as $k => $v ) {
			$data .= '<label><span class="title">' . ucwords( $k ) . '</span><span class="input-text-wrap"><input class="ptitle" type="text" value="' . $v . '" readonly="readonly" /></span></label>'; 
		}
		$data .= '</div></fieldset><p class="submit"><a id="' . $item['entry_id'] . '" class="button-secondary alignleft visual-form-builder-inline-edit-cancel">Cancel</a></p>';
		
		/* Hide the data intially */
		$hidden_div = '<div id="entry-' . $item['entry_id'] . '" class="hidden">' . $data . '</div>';
	
		return sprintf( '%1$s %2$s %3$s', $item['form'], $this->row_actions( $actions ), $hidden_div );
	}
	
	/**
	 * Used for checkboxes and bulk editing
	 * 
	 * @since 1.2
	 */
	function column_cb($item){
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item['entry_id'] );
	}
	
	/**
	 * Builds the actual columns
	 * 
	 * @since 1.2
	 */
	function get_columns(){		
		$columns = array(
			'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
			'form' => 'Form',
			'subject' => 'Email Subject',
			'sender_name' => 'Sender Name',
			'sender_email' => 'Sender Email',
			'emails_to' => 'Emailed To',
			'ip_address' => 'IP Address',
			'date' => 'Date Submitted'
		);
		
		return $columns;
	}
	
	/**
	 * A custom function to get the entries and sort them
	 * 
	 * @since 1.2
	 * @returns array() $cols SQL results
	 */
	function get_entries( $orderby = 'date', $order = 'DESC' ){
		global $wpdb;
		
		switch ( $orderby ) {
			case 'date':
				$order_col = 'date_submitted';
			break;
			case 'form':
				$order_col = 'form_title';
			break;
			case 'subject':
			case 'ip_address':
			case 'sender_name':
			case 'sender_email':
				$order_col = $orderby;
			break;
		}
		
		/* If the form filter dropdown is used */
		if ( $this->current_filter_action() )
			$where = 'WHERE forms.form_id = ' . $this->current_filter_action();
		
			
		$sql_order = sanitize_sql_orderby( "$order_col $order" );
		$query = "SELECT forms.form_title, entries.* FROM $this->form_table_name AS forms INNER JOIN $this->entries_table_name AS entries ON entries.form_id = forms.form_id $where ORDER BY $sql_order";
		
		$cols = $wpdb->get_results( $query );
		
		return $cols;
	}
	
	/**
	 * Setup which columns are sortable. Default is by Date.
	 * 
	 * @since 1.2
	 * @returns array() $sortable_columns Sortable columns
	 */
	function get_sortable_columns() {		
		$sortable_columns = array(
			'form' => array( 'form', false ),
			'subject' => array( 'subject', false ),
			'sender_name' => array( 'sender_name', false ),
			'sender_email' => array( 'sender_email', false ),
			'date' => array( 'date', true )
		);
		
		return $sortable_columns;
	}
	
	/**
	 * Define our bulk actions
	 * 
	 * @since 1.2
	 * @returns array() $actions Bulk actions
	 */
	function get_bulk_actions() {
		$actions = array(
			'delete' => 'Delete'
		);
		
		return $actions;
	}
	
	/**
	 * Process our bulk actions
	 * 
	 * @since 1.2
	 */
	function process_bulk_action() {		
		$entry_id = $_REQUEST['entry'];
		
		if ( 'delete' === $this->current_action() ) {
			global $wpdb;
			
			foreach ( $entry_id as $id ) {
				$id = absint( $id );
				$wpdb->query( "DELETE FROM $this->entries_table_name WHERE entries_id = $id" );
			}
		}
	}
	
	/**
	 * Adds our forms filter dropdown
	 * 
	 * @since 1.2
	 */
	function extra_tablenav( $which ) {
		global $wpdb;
		$query = "SELECT DISTINCT forms.form_title, forms.form_id FROM $this->form_table_name AS forms ORDER BY forms.form_title ASC";
		
		$cols = $wpdb->get_results( $query );
		
		/* Only display the dropdown on the top of the table */
		if ( 'top' == $which ) {
			echo '<div class="alignleft actions">
				<select id="form-filter" name="form-filter">
				<option value="-1"' . selected( $this->current_filter_action(), -1 ) . '>View all forms</option>';
			
			foreach ( $cols as $form ) {
				echo '<option value="' . $form->form_id . '"' . selected( $this->current_filter_action(), $form->form_id ) . '>' . $form->form_title . '</option>';
			}
			
			echo '</select>
				<input type="submit" value="Filter" class="button-secondary" />
				</div>';
		}
	}
	
	/**
	 * Set our forms filter action
	 * 
	 * @since 1.2
	 * @returns int Form ID
	 */
	function current_filter_action() {
		if ( isset( $_REQUEST['form-filter'] ) && -1 != $_REQUEST['form-filter'] )
			return $_REQUEST['form-filter'];
	
		return false;
	}
	
	/**
	 * Prepares our data for display
	 * 
	 * @since 1.2
	 */
	function prepare_items() {
		global $wpdb;
		
		/* Get screen options from the wp_options table */
		$options = get_option( 'visual-form-builder-screen-options' );
		
		/* How many to show per page */
		$per_page = $options['per_page'];
		
		/* Get column headers */
		$columns = $this->get_columns();
		$hidden = array();
		
		/* Get sortable columns */
		$sortable = $this->get_sortable_columns();
		
		/* Build the column headers */
		$this->_column_headers = array($columns, $hidden, $sortable);

		/* Handle our bulk actions */
		$this->process_bulk_action();
						
		/* Set our ORDER BY and ASC/DESC to sort the entries */
		$orderby = ( !empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'date';
		$order = ( !empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'desc';
		
		/* Get the sorted entries */
		$entries = $this->get_entries( $orderby, $order );
		
		/* Loop trough the entries and setup the data to be displayed for each row */
		foreach ( $entries as $entry ) {
			$data[] = 
				array(
					'entry_id' => $entry->entries_id,
					'form' => $entry->form_title,
					'subject' => $entry->subject,
					'sender_name' => $entry->sender_name,
					'sender_email' => $entry->sender_email,
					'emails_to' => implode( ',', unserialize( $entry->emails_to ) ),
					'date' => $entry->date_submitted,
					'ip_address' => $entry->ip_address,
					'data' => unserialize( $entry->data )
			);
		}
	
		/* What page are we looking at? */
		$current_page = $this->get_pagenum();

		/* How many entries do we have? */
		$total_items = count( $entries );
		
		/* Calculate pagination */
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

		/* Add sorted data to the items property */
		$this->items = $data;

		/* Register our pagination */
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		) );
	}
}
?>