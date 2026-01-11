(function( $ ) {
	'use strict';

	/**
	 * Handle CF7 form submission - disable button and show loader
	 */
	$( document ).ready( function() {
		// Listen for all CF7 form submission events
		$( document ).on( 'wpcf7submit', 'form.wpcf7-form', function( event ) {
			var $form = $( this );
			var $submitButton = $form.find( 'input[type="submit"], button[type="submit"]' );
			var $loader = $form.find( '.cideapps-cf7-loader' );

			// Disable submit button
			$submitButton.prop( 'disabled', true ).addClass( 'cideapps-cf7-submitting' );

			// Show loader if it exists, otherwise create it
			if ( $loader.length === 0 ) {
				$loader = $( '<span class="cideapps-cf7-loader"></span>' );
				$submitButton.after( $loader );
			}
			$loader.show();
		} );

		// Re-enable button and hide loader on successful submission
		$( document ).on( 'wpcf7mailsent', 'form.wpcf7-form', function( event ) {
			var $form = $( this );
			var $submitButton = $form.find( 'input[type="submit"], button[type="submit"]' );
			var $loader = $form.find( '.cideapps-cf7-loader' );

			$submitButton.prop( 'disabled', false ).removeClass( 'cideapps-cf7-submitting' );
			$loader.hide();
		} );

		// Re-enable button and hide loader on validation error
		$( document ).on( 'wpcf7invalid', 'form.wpcf7-form', function( event ) {
			var $form = $( this );
			var $submitButton = $form.find( 'input[type="submit"], button[type="submit"]' );
			var $loader = $form.find( '.cideapps-cf7-loader' );

			$submitButton.prop( 'disabled', false ).removeClass( 'cideapps-cf7-submitting' );
			$loader.hide();
		} );

		// Re-enable button and hide loader on spam detection
		$( document ).on( 'wpcf7spam', 'form.wpcf7-form', function( event ) {
			var $form = $( this );
			var $submitButton = $form.find( 'input[type="submit"], button[type="submit"]' );
			var $loader = $form.find( '.cideapps-cf7-loader' );

			$submitButton.prop( 'disabled', false ).removeClass( 'cideapps-cf7-submitting' );
			$loader.hide();
		} );

		// Re-enable button and hide loader on mail failed
		$( document ).on( 'wpcf7mailfailed', 'form.wpcf7-form', function( event ) {
			var $form = $( this );
			var $submitButton = $form.find( 'input[type="submit"], button[type="submit"]' );
			var $loader = $form.find( '.cideapps-cf7-loader' );

			$submitButton.prop( 'disabled', false ).removeClass( 'cideapps-cf7-submitting' );
			$loader.hide();
		} );
	} );

})( jQuery );
