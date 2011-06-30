jQuery(document).ready(function($) {
	/* Field item details box toggle */
	$( '.item-edit' ).click( function( e ){
		$( e.target ).closest( 'li' ).children( '.menu-item-settings' ).slideToggle( 'fast' );
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
	
	/* Hide the left arrow on load */
	$( '.nav-tabs-arrow-left' ).hide();
	
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
	
	/* Hide the spinner on load */
	$('img.waiting').hide();
	
	/* Show/hide the spinner image when creating a field */
	$( '#submit-create-field' ).click( function(e){
		$.ajax({
			url: ajaxurl,
			async: false,
			success: function( response ) {
				$( 'img.waiting' ).show();
				return;
			}
		});
	});
	
	/* Validate the sender details section */
	$( '#visual-form-builder-update' ).validate({
		rules: {
			form_email_subject: {
				required: true
			},
			form_email_from_name: {
				required: true
			},
			form_email_to: {
				required: true,
				multiemail: true
			},
			form_email_from: {
				required: true,
				email: true
			}
		},
		errorPlacement: function( error, element ) {
			error.insertAfter( element.parent() );
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