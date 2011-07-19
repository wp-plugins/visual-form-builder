jQuery(document).ready(function($) {
	$( '.visual-form-builder' ).validate({
		rules: {
			secret:{
				maxlength:2
			}
		},
		errorPlacement: function(error, element) {
			if ( element.is( ':radio' ) || element.is( ':checkbox' ) ) {
				error.appendTo( element.parent().parent() );
			}
			else {
				error.insertAfter( element );
			}
		} 
	});
	
	/* Display jQuery UI date picker */
	$( '.vfb-date-picker' ).datepicker();
});