<?php
/**
 * Class that builds our Entries detail page
 * 
 * @since 1.4
 */
class VisualFormBuilder_Entries_Detail{
	public function __construct(){
		global $wpdb;
		
		/* Setup global database table names */
		$this->field_table_name = $wpdb->prefix . 'visual_form_builder_fields';
		$this->form_table_name = $wpdb->prefix . 'visual_form_builder_forms';
		$this->entries_table_name = $wpdb->prefix . 'visual_form_builder_entries';
		
		add_action( 'admin_init', array( &$this, 'entries_detail' ) );
	}
	
	public function entries_detail(){
		global $wpdb;
		
		$entry_id = absint( $_REQUEST['entry'] );
		
		$query = "SELECT forms.form_title, entries.* FROM $this->form_table_name AS forms INNER JOIN $this->entries_table_name AS entries ON entries.form_id = forms.form_id WHERE entries.entries_id  = $entry_id;";
		
		$entries = $wpdb->get_results( $query );
		
		echo '<p>' . sprintf( '<a href="?page=%s&view=%s" class="view-entry">&laquo; Back to Entries</a>', $_REQUEST['page'], $_REQUEST['view'] ) . '</p>';
		
		
		
		/* Loop trough the entries and setup the data to be displayed for each row */
		foreach ( $entries as $entry ) {
			$data = unserialize( $entry->data );
			
			echo '<div id="poststuff" class="metabox-holder has-right-sidebar">
				<div id="side-info-column" class="inner-sidebar">
					<div id="side-sortables">
						<div id="submitdiv" class="postbox">
							<h3><span>Details</span></h3>
							<div class="inside">
							<div id="submitbox" class="submitbox">
								<div id="minor-publishing">
									<div id="misc-publishing-actions">
										<div class="misc-pub-section">
											<span><strong>Form Title: </strong>' . stripslashes( $entry->form_title ) . '</span>
										</div>
										<div class="misc-pub-section">
											<span><strong>Date Submitted: </strong>' . $entry->date_submitted . '</span>
										</div>
										<div class="misc-pub-section">
											<span><strong>IP Address: </strong>' . $entry->ip_address . '</span>
										</div>
										<div class="misc-pub-section">
											<span><strong>Email Subject: </strong>' . stripslashes( $entry->subject ) . '</span>
										</div>
										<div class="misc-pub-section">
											<span><strong>Sender Name: </strong>' . stripslashes( $entry->sender_name ) . '</span>
										</div>
										<div class="misc-pub-section">
											<span><strong>Sender Email: </strong><a href="mailto:' . stripslashes( $entry->sender_email ) . '">' . stripslashes( $entry->sender_email ) . '</a></span>
										</div>
										<div class="misc-pub-section misc-pub-section-last">
											<span><strong>Emailed To: </strong>' . preg_replace('/\b([A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4})\b/i', '<a href="mailto:$1">$1</a>', implode( ',', unserialize( stripslashes( $entry->emails_to ) ) ) ) . '</span>
										</div>
										<div class="clear"></div>
									</div>
								</div>
								
								<div id="major-publishing-actions">
									<div id="delete-action">'
								. sprintf( '<a class="submitdelete deletion" href="?page=%s&view=%s&action=%s&entry=%s">Delete</a>', $_REQUEST['page'], $_REQUEST['view'], 'delete', $entry_id ) .
									'</div>
									<div class="clear"></div>
								</div>
							</div>
							</div>
						</div>
					</div>
				</div>';
			echo '<div>
					<div id="post-body-content">
						<div class="postbox">
							<h3><span>' . $entry->form_title . ' : Entry #' . $entry->entries_id . '</span></h3>
							<div class="inside">';

			foreach ( $data as $k => $v ) {
				echo '<h4>' . ucwords( $k ) . '</h4>';
				//echo '<pre>' . $v . '</pre>';
				echo $v;
			}
			
			echo '</div></div></div></div>';
		}
		
		echo '<br class="clear"></div>';
	}
}
?>