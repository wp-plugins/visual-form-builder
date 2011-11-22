jQuery(document).ready(function($) {
	/* Dynamically add options for Select, Radio, and Checkbox */
	$( '.addOption' ).live( 'click', function( e ) {
		/* Get how many options we already have */		
		var num = $( this ).parent().parent().find( '.clonedOption').length;
		
		/* Add one to how many options */
		var newNum = num + 1;
		
		/* Get this div's ID */
		var id = $( this ).closest( 'div' ).attr( 'id' );
		
		/* Get this div's for attribute, which matches the input's ID */
		var label_for = $( this ).closest( 'div' ).children( 'label' ).attr( 'for' );
		
		/* Strip out the last number (i.e. count) from the for to make a new ID */
		var new_id = label_for.replace( new RegExp( /(\d+)$/g ), '' );
		var div_id = id.replace( new RegExp( /(\d+)$/g ), '' );
		
		/* Clone this div and change the ID */
		var newElem = $( '#' + id ).clone().attr( 'id', div_id + newNum);

		/* Change the IDs of the for and input to match */
		newElem.children( 'label' ).attr( 'for', new_id + newNum );
		newElem.find( 'input' ).attr( 'id', new_id + newNum );
		
		/* Insert our cloned option after the last one */
		$( '#' + div_id + num ).after( newElem );
		
		return false;
	});
	
	/* Dynamically delete options for Select, Radio, and Checkbox */
	$( '.deleteOption' ).live( 'click', function() {
		/* Get how many options we already have */
		var num = $( this ).parent().parent().find( '.clonedOption').length;
		
		/* If there's only one option left, don't let someone delete it */
		if ( num - 1 == 0 ) {
			alert( 'You must have at least one option.' );
		}
		else {
			$( this ).closest( 'div' ).remove();
		}
		
		return false;
	});
	
	/* Dynamically add values for the E-mail(s) To field */
	$( '.addEmail' ).live( 'click', function( e ) {
		/* Get how many options we already have */		
		var num = $( this ).parent().parent().find( '.clonedOption').length;
		
		/* Add one to how many options */
		var newNum = num + 1;
		
		/* Get this div's ID */
		var id = $( this ).closest( 'div' ).attr( 'id' );
		
		/* Get this div's for attribute, which matches the input's ID */
		var label_for = $( this ).closest( 'div' ).children( 'label' ).attr( 'for' );
		
		/* Strip out the last number (i.e. count) from the for to make a new ID */
		var new_id = label_for.replace( new RegExp( /(\d+)$/g ), '' );
		var div_id = id.replace( new RegExp( /(\d+)$/g ), '' );
		
		/* Clone this div and change the ID */
		var newElem = $( '#' + id ).clone().attr( 'id', div_id + newNum);

		/* Change the IDs of the for and input to match */
		newElem.children( 'label' ).attr( 'for', new_id + newNum );
		newElem.find( 'input' ).attr( 'id', new_id + newNum );
		
		/* Insert our cloned option after the last one */
		$( '#' + div_id + num ).after( newElem );
		
		return false;
	});
	
	/* Dynamically delete values for the E-mail(s) To field */
	$( '.deleteEmail' ).live( 'click', function() {
		/* Get how many options we already have */
		var num = $( this ).parent().parent().find( '.clonedOption').length;
		
		/* If there's only one option left, don't let someone delete it */
		if ( num - 1 == 0 ) {
			alert( 'You must have at least one option.' );
		}
		else {
			$( this ).closest( 'div' ).remove();
		}
		
		return false;
	});
	
	/* Field item details box toggle */
	$( '.item-edit' ).click( function( e ){
		$( e.target ).closest( 'li' ).children( '.menu-item-settings' ).slideToggle( 'fast' );
		
		$( this ).toggleClass( 'opened' );
		
		return false;
	});
	
	/* Highlight the shortcode to make easier copying */
	$( '#form-copy-to-clipboard' ).focus( function(){
		this.select();
	});
	
	
	/* Setup the tabs array */
	var tabsWidth = new Array();
	
	/* Set the width of each tab and add extra margins */
	$( '.nav-tab' ).each(function(i){
		if ( i == 0 )
			tabsWidth[i] = Math.abs( $(this).outerWidth() ) + 26;
		else
			tabsWidth[i] = Math.abs( $(this).outerWidth() ) + 6;
	});
	
	var count = 0;
	
	/* Move tabs to the right */
	$( '.nav-tabs-arrow-right' ).click( function(){
		
		/* First time we click on the right arrow, show the left one */
		if ( count == 0 ) 
			$( '.nav-tabs-arrow-left' ).show();
		
		/* Slide div over one tab at a time */
		$( '.nav-tabs' ).animate({ marginLeft: '-=' + tabsWidth[count] });
		
		/* Make sure we always show the + tab */
		if ( count == tabsWidth.length - 2 ) {
			$( this ).hide();
		}
		
		count += 1;
	});
	
	/* Move tabs to the left */
	$( '.nav-tabs-arrow-left' ).click( function(){
		
		count -= 1;
		
		/* If we click on the left arrow, show the right one */
		$( '.nav-tabs-arrow-right' ).show();
		
		/* If at the beginning (left side), hide the left arrow */
		if ( count == 0 )
			$( this ).hide();
		
		/* Slide div back to the left, one tab at a time */
		$( '.nav-tabs' ).animate({ marginLeft: '+=' + tabsWidth[count] });
	});
		
	/* Handle sorting the field items */
	$( '#menu-to-edit' ).sortable({
		handle: '.menu-item-handle',
		placeholder: 'sortable-placeholder',
		items: 'li:not(.ui-state-disabled)',
		stop: function( event, ui ){
			opts = {
				url: ajaxurl,
				type: 'POST',
				async: true,
				cache: false,
				dataType: 'json',
				data: {
					action: 'visual_form_builder_process_sort',
					order: $( this ).sortable( 'toArray' ).toString()
				},
                success: function( response ) {
                    $( '#loading-animation' ).hide(); // Hide the loading animation
                    return; 
                },
                error: function( xhr,textStatus,e ) {  // This can be expanded to provide more information
                    alert('There was an error saving the updates');
                    $('#loading-animation').hide(); // Hide the loading animation
                    return; 
                }
			};
			
			$.ajax(opts);
		}
	});
	
	/* Show/hide the spinner image when creating a field */
	$( '#form-items' ).submit( function(e){
		$.ajax({
			url: ajaxurl,
			async: false,
			success: function( response ) {
				$( 'img.waiting' ).show();
				return;
			}
		});
	});
	
	/* Display the selected confirmation type on load */
	var confirmation = $( '.form-success-type:checked' ).val();
	$( '#form-success-message-' + confirmation ).show();

	/* Control the Confirmation Message tabs */
	$( '.form-success-type' ).change(function(){
		var type = $( this ).val();

		if ( 'text' == type ) {
			$( '#form-success-message-text' ).show();
			$( '#form-success-message-page, #form-success-message-redirect' ).hide();
		}
		else if ( 'page' == type ) {
			$( '#form-success-message-page' ).show();
			$( '#form-success-message-text, #form-success-message-redirect' ).hide();
		}
		else if ( 'redirect' == type ) {
			$( '#form-success-message-redirect' ).show();
			$( '#form-success-message-text, #form-success-message-page' ).hide();
		}
	});
	
	/* Validate the sender details section */
	$( '#visual-form-builder-update' ).validate({
		rules: {
			form_email_subject: {
				required: true
			},
			form_email_from_name: {
				required : function( element ){
					return $( '#form_email_from_name_override option:selected' ).val() == ''
				}
			},
			'form_email_to[]': {
				required: true,
				email: true
			},
			form_email_from: {
				required: function( element ){
					return $( '#form_email_from_override option:selected' ).val() == ''
				},
				email: true
			},
			form_success_message_redirect: {
				url: true
			},
			form_notification_email_name: {
				required: function( element ){
					return $( '#form-notification-setting' ).is( ':checked' )
				}
			},
			form_notification_email_from: {
				required: function( element ){
					return $( '#form-notification-setting' ).is( ':checked' )
				},
				email: true
			},
			form_notification_email: {
				required: function( element ){
					return $( '#form-notification-setting' ).is( ':checked' )
				}
			}
		},
		errorPlacement: function( error, element ) {
			error.insertAfter( element.parent() );
		}
	});
	
	/* Make Sender Name field readonly if the override is active */
	$( '#form_email_from_name_override' ).change( function(){
		if ( $( '#form_email_from_name_override' ).val() == '' ) {
			$( '#form-email-sender-name' ).attr( 'readonly', false );
		}
		else{
			$( '#form-email-sender-name' ).attr( 'readonly', 'readonly' );
		}
	});
	
	/* Make Sender Email field readonly if the override is active */
	$( '#form_email_from_override' ).change( function(){
		if ( $( '#form_email_from_override' ).val() == '' ) {
			$( '#form-email-sender' ).attr( 'readonly', false );
		}
		else{
			$( '#form-email-sender' ).attr( 'readonly', 'readonly' );
		}
	});
	
	
	/* Show/Hide display of Notification fields  */ 
	if ( $( '#form-notification-setting' ).is( ':checked' ) ) {
		$( '#notification-email' ).show();
	}
	else {
		$( '#notification-email' ).hide();
	}
	
	/* Enable/Disable Notification fields */
	$( '#form-notification-setting' ).change( function(){
		var checked = $(this).is(':checked');
		
		if ( checked ) {
			$( '#notification-email' ).show();
			$( '#form-notification-email-name, #form-notification-email-from, #form-notification-email, #form-notification-subject, #form-notification-message, #form-notification-entry' ).attr( 'disabled', false );
		}
		else{
			$( '#notification-email' ).hide();
			$( '#form-notification-email-name, #form-notification-email-from, #form-notification-email, #form-notification-subject, #form-notification-message, #form-notification-entry' ).attr( 'disabled', 'disabled' );
		}
	});
	
	/* Custom validation method to check multiple emails */
	$.validator.addMethod( 'multiemail', function( value, element ) {
		
		/* Return true on an optional element */
		if ( this.optional( element ) )
			return true; 
		
		/* RegEx for emails - delimiters are commas or semicolons */
		var emails = value.split( new RegExp( '\\s*[,|;]\\s*', 'gi' ) );
		
		/* It's valid unless the loop below proves otherwise */
		valid = true;
		
		/* Loop through each email and validate as email */
		for ( var i in emails ) { 
			value = emails[i];
			valid = valid && jQuery.validator.methods.email.call( this, value, element );
		}
		
		return valid; 
		
		}, 'One or more email addresses are invalid'
	);

});