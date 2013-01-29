<?php
global $wpdb;

// Extract shortcode attributes, set defaults
extract( shortcode_atts( array(
	'id' => ''
	), $atts ) 
);

// Add JavaScript files to the front-end, only once
if ( !$this->add_scripts )
	$this->scripts();

// Get form id.  Allows use of [vfb id=1] or [vfb 1]
$form_id = ( isset( $id ) && !empty( $id ) ) ? $id : key( $atts );

$open_fieldset = $open_section = false;

// Default the submit value
$submit = 'Submit';

// If form is submitted, show success message, otherwise the form
if ( isset( $_REQUEST['visual-form-builder-submit'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'visual-form-builder-nonce' ) && isset( $_REQUEST['form_id'] ) && $_REQUEST['form_id'] == $form_id ) {
	$output = $this->confirmation();
	return;
}

// Get forms
$order = sanitize_sql_orderby( 'form_id DESC' );			
$forms = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->form_table_name WHERE form_id = %d ORDER BY $order", $form_id ) );

// Get fields
$order_fields = sanitize_sql_orderby( 'field_sequence ASC' );
$fields = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->field_table_name WHERE form_id = %d ORDER BY $order_fields", $form_id ) );

// Setup count for fieldset and ul/section class names
$count = 1;

$verification = '';

foreach ( $forms as $form ) :
	$label_alignment = ( $form->form_label_alignment !== '' ) ? " $form->form_label_alignment" : '';
	$output = '<div class="visual-form-builder-container"><form id="' . $form->form_key . '" class="visual-form-builder' . $label_alignment . '" method="post" enctype="multipart/form-data">
				<input type="hidden" name="form_id" value="' . $form->form_id . '" />';
	$output .= wp_nonce_field( 'visual-form-builder-nonce', '_wpnonce', false, false );

	foreach ( $fields as $field ) {
		// If field is required, build the span and add setup the 'required' class
		$required_span 	= ( !empty( $field->field_required ) && $field->field_required === 'yes' ) ? ' <span>*</span>' : '';
		$required 		= ( !empty( $field->field_required ) && $field->field_required === 'yes' ) ? ' required' : '';
		$validation 	= ( !empty( $field->field_validation ) ) ? " $field->field_validation" : '';
		$css 			= ( !empty( $field->field_css ) ) ? " $field->field_css" : '';
		$id_attr 		= 'vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id;
		$size			= ( !empty( $field->field_size ) ) ? " vfb-$field->field_size" : '';
		$layout 		= ( !empty( $field->field_layout ) ) ? " vfb-$field->field_layout" : '';
		$default 		= ( !empty( $field->field_default ) ) ? html_entity_decode( stripslashes( $field->field_default ) ) : '';
		
		// Close each section
		if ( $open_section == true ) {
			// If this field's parent does NOT equal our section ID
			if ( $sec_id && $sec_id !== $field->field_parent ) {
				$output .= '</div><div class="vfb-clear"></div>';
				$open_section = false;
			}
		}
		
		// Force an initial fieldset and display an error message to strongly encourage user to add one
		if ( $count === 1 && $field->field_type !== 'fieldset' ) {
			$output .= '<fieldset class="fieldset"><div class="legend" style="background-color:#FFEBE8;border:1px solid #CC0000;"><h3>Oops! Missing Fieldset</h3><p style="color:black;">If you are seeing this message, it means you need to <strong>add a Fieldset to the beginning of your form</strong>. Your form may not function or display properly without one.</p></div><ul class="section section-' . $count . '">';
			
			$count++;
		}
		
		if ( $field->field_type == 'fieldset' ) {
			// Close each fieldset
			if ( $open_fieldset == true )
				$output .= '</ul><br /></fieldset>';
			
			$output .= '<fieldset class="vfb-fieldset vfb-fieldset-' . $count . ' ' . $field->field_key . $css . '" id="' . $id_attr . '"><div class="vfb-legend"><h3>' . stripslashes( $field->field_name ) . '</h3></div><ul class="vfb-section vfb-section-' . $count . '">';
			$open_fieldset = true;
			$count++;
		}
		elseif ( $field->field_type == 'section' ) {
			$output .= '<div class="vfb-section-div vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id . ' ' . $css . '"><h4>' . stripslashes( $field->field_name ) . '</h4>';
			
			// Save section ID for future comparison
			$sec_id = $field->field_id;
			$open_section = true;
		}
		elseif ( !in_array( $field->field_type, array( 'verification', 'secret', 'submit' ) ) ) {
			
			$columns_choice = ( !empty( $field->field_size ) && in_array( $field->field_type, array( 'radio', 'checkbox' ) ) ) ? " vfb-$field->field_size" : '';
			
			if ( $field->field_type !== 'hidden' ) {
				$id_attr = 'vfb-' . esc_html( $field->field_key ) . '-' . $field->field_id;
				$output .= '<li class="vfb-item vfb-item-' . $field->field_type . $columns_choice . $layout . '" id="item-' . $id_attr . '"><label for="' . $id_attr . '" class="vfb-desc">'. stripslashes( $field->field_name ) . $required_span . '</label>';
			}
		}
		elseif ( in_array( $field->field_type, array( 'verification', 'secret' ) ) ) {
			
			if ( $field->field_type == 'verification' )
				$verification .= '<fieldset class="vfb-fieldset vfb-fieldset-' . $count . ' ' . $field->field_key . $css . '" id="' . $id_attr . '"><div class="vfb-legend"><h3>' . stripslashes( $field->field_name ) . '</h3></div><ul class="vfb-section vfb-section-' . $count . '">';
			
			if ( $field->field_type == 'secret' ) {
				// Default logged in values
				$logged_in_display = '';
				$logged_in_value = '';

				// If the user is logged in, fill the field in for them
				if ( is_user_logged_in() ) {
					// Hide the secret field if logged in
					$logged_in_display = ' style="display:none;"';
					$logged_in_value = 14;
					
					// Get logged in user details
					$user = wp_get_current_user();
					$user_identity = ! empty( $user->ID ) ? $user->display_name : '';
					
					// Display a message for logged in users
					$verification .= '<li class="vfb-item" id="' . $id_attr . '">' . sprintf( __( 'Logged in as <a href="%1$s">%2$s</a>. Verification not required.', 'visual-form-builder' ), admin_url( 'profile.php' ), $user_identity ) . '</li>';
				}
				
				$validation = ' {digits:true,maxlength:2,minlength:2}';
				$verification .= '<li class="vfb-item vfb-item-' . $field->field_type . '"' . $logged_in_display . '><label for="' . $id_attr . '" class="vfb-desc">'. stripslashes( $field->field_name ) . $required_span . '</label>';
				
				// Set variable for testing if required is Yes/No
				if ( $required == '' )
					$verification .= '<input type="hidden" name="_vfb-required-secret" value="0" />';
				
				$verification .= '<input type="hidden" name="_vfb-secret" value="vfb-' . $field->field_id . '" />';
				
				if ( !empty( $field->field_description ) )
					$verification .= '<span><input type="text" name="vfb-' . $field->field_id . '" id="' . $id_attr . '" value="' . $logged_in_value . '" class="vfb-text ' . $size . $required . $validation . $css . '" /><label>' . html_entity_decode( stripslashes( $field->field_description ) ) . '</label></span>';
				else
					$verification .= '<input type="text" name="vfb-' . $field->field_id . '" id="' . $id_attr . '" value="' . $logged_in_value . '" class="vfb-text ' . $size . $required . $validation . $css . '" />';
			}
		}
		
		switch ( $field->field_type ) {
			case 'text' :
			case 'email' :
			case 'url' :
			case 'currency' :
			case 'number' :
			case 'phone' :
				
				$form_item = sprintf(
					'<input type="text" name="vfb-%1$d" id="%2$s" value="%3$s" class="vfb-text %4$s %5$s %6$s %7$s" />',
					absint( $field->field_id ),
					$id_attr,
					$default,
					$size,
					$required,
					$validation,
					$css
				);
				
				$output .= ( !empty( $field->field_description ) ) ? sprintf( '<span>%1$s<label>%2$s</label></span>', $form_item, html_entity_decode( stripslashes( $field->field_description ) ) ) : $form_item;
									
			break;
			
			case 'textarea' :
				
				$form_item = sprintf(
					'<textarea name="vfb-%1$d" id="%2$s" class="vfb-textarea %4$s %5$s %6$s">%3$s</textarea>',
					absint( $field->field_id ),
					$id_attr,
					$default,
					$size,
					$required,
					$css
				);
				
				$output .= ( !empty( $field->field_description ) ) ? sprintf( '<span><label>%2$s</label></span>%1$s', $form_item, html_entity_decode( stripslashes( $field->field_description ) ) ) : $form_item;
					
			break;
			
			case 'select' :
				
				$field_options = maybe_unserialize( $field->field_options );
				
				$options = '';
				
				// Loop through each option and output
				foreach ( $field_options as $option => $value ) {
					$options .= sprintf( '<option value="%1$s"%2$s>%1$s</option>', trim( stripslashes( $value ) ), selected( $default, ++$option, 0 ) );
				}
				
				$form_item = sprintf(
					'<select name="vfb-%1$d" id="%2$s" class="vfb-select %3$s %4$s %5$s">%6$s</select>',
					absint( $field->field_id ),
					$id_attr,
					$size,
					$required,
					$css,
					$options
				);
				
				$output .= ( !empty( $field->field_description ) ) ? sprintf( '<span><label>%2$s</label></span>%1$s', $form_item, html_entity_decode( stripslashes( $field->field_description ) ) ) : $form_item;
				
			break;
			
			case 'radio' :
				
				$field_options = maybe_unserialize( $field->field_options );
				
				$options = '';
				
				// Loop through each option and output
				foreach ( $field_options as $option => $value ) {
					$options .= sprintf(
						'<span><input type="radio" name="vfb-%1$d" id="%2$s-%3$d" value="%6$s" class="vfb-radio %4$s %5$s"%7$s /><label for="%2$s-%3$d" class="vfb-choice">%6$s</label></span>',
						absint( $field->field_id ),
						$id_attr,
						$option,
						$required,
						$css,
						trim( stripslashes( $value ) ),
						checked( $default, ++$option, 0 )
					);
				}
				
				$form_item = $options;
				
				$output .= '<div>';
				
				$output .= ( !empty( $field->field_description ) ) ? sprintf( '<span><label>%2$s</label></span>%1$s', $form_item, html_entity_decode( stripslashes( $field->field_description ) ) ) : $form_item;
				
				$output .= '<div style="clear:both"></div></div>';
				
			break;
			
			case 'checkbox' :
				
				$field_options = maybe_unserialize( $field->field_options );
				
				$options = '';
				
				// Loop through each option and output
				foreach ( $field_options as $option => $value ) {
					$options .= sprintf(
						'<span><input type="checkbox" name="vfb-%1$d[]" id="%2$s-%3$d" value="%6$s" class="vfb-checkbox %4$s %5$s"%7$s /><label for="%2$s-%3$d" class="vfb-choice">%6$s</label></span>',
						absint( $field->field_id ),
						$id_attr,
						$option,
						$required,
						$css,
						trim( stripslashes( $value ) ),
						checked( $default, ++$option, 0 )
					);
				}
				
				$form_item = $options;
				
				$output .= '<div>';
				
				$output .= ( !empty( $field->field_description ) ) ? sprintf( '<span><label>%2$s</label></span>%1$s', $form_item, html_entity_decode( stripslashes( $field->field_description ) ) ) : $form_item;
				
				$output .= '<div style="clear:both"></div></div>';
			
			break;
			
			case 'address' :
				
				$address = '';
				
				$address_parts = array(
				    'address'    => array(
				    	'label'    => __( 'Address', 'visual-form-builder-pro' ),
				    	'layout'   => 'full'
				    ),
				    'address-2'  => array(
				    	'label'    => __( 'Address Line 2', 'visual-form-builder-pro' ),
				    	'layout'   => 'full'
				    ),
				    'city'       => array(
				    	'label'    => __( 'City', 'visual-form-builder-pro' ),
				    	'layout'   => 'left'
				    ),
				    'state'      => array(
				    	'label'    => __( 'State / Province / Region', 'visual-form-builder-pro' ),
				    	'layout'   => 'right'
				    ),
				    'zip'        => array(
				    	'label'    => __( 'Postal / Zip Code', 'visual-form-builder-pro' ),
				    	'layout'   => 'left'
				    ),
				    'country'    => array(
				    	'label'    => __( 'Country', 'visual-form-builder-pro' ),
				    	'layout'   => 'right'
				    )
				);
				
				$address_parts = apply_filters( 'vfb_address_labels', $address_parts, $form_id );
				
				foreach ( $address_parts as $parts => $part ) :
					
					if ( 'country' == $parts ) :
						
						$options = '';
					
						foreach ( $this->countries as $country ) {
							$options .= sprintf( '<option value="%1$s"%2$s>%1$s</option>', $country, selected( $default, $country, 0 ) );
						}
											
						$address .= sprintf(
							'<span class="vfb-%3$s"><select name="vfb-%1$d[%4$s]" class="vfb-select %7$s %8$s" id="%2$s-%4$s">%6$s</select><label for="%2$s-%4$s">%5$s</label></span>',
							absint( $field->field_id ),
							$id_attr,
							$part['layout'],
							$parts,
							$part['label'],
							$options,
							$required,
							$css
						);
						
					else : 
						
						$address .= sprintf(
							'<span class="vfb-%3$s"><input type="text" name="vfb-%1$d[%4$s]" id="%2$s-%4$s" maxlength="150" class="vfb-text vfb-medium %7$s %8$s" /><label for="%2$s-%4$s">%5$s</label></span>',
							absint( $field->field_id ),
							$id_attr,
							$part['layout'],
							$parts,
							$part['label'],
							$size,
							$required,
							$css
						);
					
					endif;
					
				endforeach;
				
				$output .= "<div>$address</div>";
				
			break;
			
			case 'date' :
				
				if ( !empty( $field->field_description ) )
					$output .= '<span><input type="text" name="vfb-' . $field->field_id . '" id="' . $id_attr . '" value="' . $default . '" class="vfb-text vfb-date-picker ' . $size . $required . $css . '" /><label>' . html_entity_decode( stripslashes( $field->field_description ) ) . '</label></span>';
				else
					$output .= '<input type="text" name="vfb-' . $field->field_id . '" id="' . $id_attr . '" value="' . $default . '" class="vfb-text vfb-date-picker ' . $size . $required . $css . '" />';
				
			break;
			
			case 'time' :
				if ( !empty( $field->field_description ) )
					$output .= '<span><label>' . html_entity_decode( stripslashes( $field->field_description ) ) . '</label></span>';

				// Get the time format (12 or 24)
				$time_format = str_replace( 'time-', '', $validation );
				
				$time_format = apply_filters( 'vfb_time_format', $time_format, $form_id );
				
				// Set whether we start with 0 or 1 and how many total hours
				$hour_start = ( $time_format == '12' ) ? 1 : 0;
				$hour_total = ( $time_format == '12' ) ? 12 : 23;
				
				// Hour
				$output .= '<span class="vfb-time"><select name="vfb-' . $field->field_id . '[hour]" id="' . $id_attr . '-hour" class="vfb-select' . $required . $css . '">';
				for ( $i = $hour_start; $i <= $hour_total; $i++ ) {
					// Add the leading zero
					$hour = ( $i < 10 ) ? "0$i" : $i;
					$output .= "<option value='$hour'>$hour</option>";
				}
				$output .= '</select><label for="' . $id_attr . '-hour">HH</label></span>';
				
				// Minute
				$output .= '<span class="vfb-time"><select name="vfb-' . $field->field_id . '[min]" id="' . $id_attr . '-min" class="vfb-select' . $required . $css . '">';
				
				$total_mins 	= apply_filters( 'vfb_time_min_total', 55, $form_id );
				$min_interval 	= apply_filters( 'vfb_time_min_interval', 5, $form_id );
				
				for ( $i = 0; $i <= $total_mins; $i += $min_interval ) {
					// Add the leading zero
					$min = ( $i < 10 ) ? "0$i" : $i;
					$output .= "<option value='$min'>$min</option>";
				}
				$output .= '</select><label for="' . $id_attr . '-min">MM</label></span>';
				
				// AM/PM
				if ( $time_format == '12' )
					$output .= '<span class="vfb-time"><select name="vfb-' . $field->field_id . '[ampm]" id="' . $id_attr . '-ampm" class="vfb-select' . $required . $css . '"><option value="AM">AM</option><option value="PM">PM</option></select><label for="' . $id_attr . '-ampm">AM/PM</label></span>';
				$output .= '<div class="clear"></div>';		
			break;
			
			case 'html' :
				
				if ( !empty( $field->field_description ) )
					$output .= '<span><label>' . html_entity_decode( stripslashes( $field->field_description ) ) . '</label></span>';

				$output .= '<script type="text/javascript">edToolbar("' . $id_attr . '");</script>';
				$output .= '<textarea name="vfb-' . $field->field_id . '" id="' . $id_attr . '" class="vfb-textarea vfbEditor ' . $size . $required . $css . '">' . $default . '</textarea>';
					
			break;
			
			case 'file-upload' :
				
				$options = ( is_array( unserialize( $field->field_options ) ) ) ? unserialize( $field->field_options ) : unserialize( $field->field_options );
				$accept = ( !empty( $options[0] ) ) ? " {accept:'$options[0]'}" : '';

				if ( !empty( $field->field_description ) )
					$output .= '<span><input type="file" name="vfb-' . $field->field_id . '" id="' . $id_attr . '" value="' . $default . '" class="vfb-text ' . $size . $required . $validation . $accept . $css . '" /><label>' . html_entity_decode( stripslashes( $field->field_description ) ) . '</label></span>';
				else
					$output .= '<input type="file" name="vfb-' . $field->field_id . '" id="' . $id_attr . '" value="' . $default . '" class="vfb-text ' . $size . $required . $validation . $accept . $css . '" />';
			
						
			break;
			
			case 'instructions' :
				
				$output .= html_entity_decode( stripslashes( $field->field_description ) );
			
			break;
			
			case 'submit' :							
				
				$submit = '<li class="vfb-item vfb-item-submit" id="' . $id_attr . '"><input type="submit" name="visual-form-builder-submit" value="' . stripslashes( $field->field_name ) . '" class="vfb-submit' . $css . '" id="sendmail" /></li>';
				
			break;
			
			default:
				echo '';
		}

		// Closing </li>
		$output .= ( !in_array( $field->field_type , array( 'verification', 'secret', 'submit', 'fieldset', 'section' ) ) ) ? '</li>' : '';
	}
	
	
	// Close user-added fields
	$output .= '</ul><br /></fieldset>';
	
	// Make sure the verification displays even if they have not updated their form
	if ( $verification == '' ) {
		$verification = '<fieldset class="vfb-fieldset vfb-verification">
				<div class="vfb-legend">
					<h3>' . __( 'Verification' , 'visual-form-builder') . '</h3>
				</div>
				<ul class="vfb-section vfb-section-' . $count . '">
					<li class="vfb-item vfb-item-text">
						<label for="vfb-secret" class="vfb-desc">' . __( 'Please enter any two digits with <strong>no</strong> spaces (Example: 12)' , 'visual-form-builder') . '<span>*</span></label>
						<div>
							<input type="text" name="vfb-secret" id="vfb-secret" class="vfb-text vfb-medium" />
						</div>
					</li>';
	}
	
	// Output our security test
	$output .= $verification . '<li style="display:none;">
						<label for="vfb-spam">' . __( 'This box is for spam protection - <strong>please leave it blank</strong>' , 'visual-form-builder') . ':</label>
						<div>
							<input name="vfb-spam" id="vfb-spam" />
						</div>
					</li>

					' . $submit . '
				</ul>
			</fieldset></form></div>';
	
endforeach;
?>