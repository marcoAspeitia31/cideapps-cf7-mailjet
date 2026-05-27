(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	function syncDynamicMappingsHiddenInput( $container ) {
		var lines = [];
		$container.find( '.cideapps-cf7-mappings__row' ).each( function() {
			var $row = $( this );
			var source = ( $row.find( '.cideapps-cf7-mappings__source' ).val() || '' ).toString().trim();
			var target = ( $row.find( '.cideapps-cf7-mappings__target' ).val() || '' ).toString().trim();

			if ( ! source || ! target ) {
				return;
			}

			// Keep target compatible with sanitize_key on save.
			target = target.toLowerCase().replace( /[^a-z0-9_]/g, '_' );
			lines.push( source + ':' + target );
		} );

		$( '#cideapps_cf7_mailjet_dynamic_mappings' ).val( lines.join( "\n" ) );
	}

	function createDynamicMappingRow( source, target ) {
		source = source || '';
		target = target || '';

		return $( '<div class="cideapps-cf7-mappings__row">' +
			'<input type="text" name="cideapps_cf7_mailjet_dynamic_mappings_source[]" class="regular-text cideapps-cf7-mappings__source" placeholder="origen (ej: your-company o [_url])" />' +
			'<span class="cideapps-cf7-mappings__arrow" aria-hidden="true">→</span>' +
			'<input type="text" name="cideapps_cf7_mailjet_dynamic_mappings_target[]" class="regular-text cideapps-cf7-mappings__target" placeholder="variable Mailjet (ej: company)" />' +
			'<button type="button" class="button cideapps-cf7-mappings__remove">Remove</button>' +
		'</div>' )
			.find( '.cideapps-cf7-mappings__source' ).val( source ).end()
			.find( '.cideapps-cf7-mappings__target' ).val( target ).end();
	}

	$( function() {
		var $container = $( '#cideapps-cf7-mailjet-dynamic-mappings' );
		if ( $container.length === 0 ) {
			return;
		}

		// Add row.
		$( '#cideapps-cf7-mailjet-dynamic-mappings-add' ).on( 'click', function() {
			$container.append( createDynamicMappingRow() );
			syncDynamicMappingsHiddenInput( $container );
		} );

		// Remove row.
		$container.on( 'click', '.cideapps-cf7-mappings__remove', function() {
			$( this ).closest( '.cideapps-cf7-mappings__row' ).remove();
			if ( $container.find( '.cideapps-cf7-mappings__row' ).length === 0 ) {
				$container.append( createDynamicMappingRow() );
			}
			syncDynamicMappingsHiddenInput( $container );
		} );

		// Keep hidden value in sync (backward compat / debugging).
		$container.on( 'input', '.cideapps-cf7-mappings__source, .cideapps-cf7-mappings__target', function() {
			syncDynamicMappingsHiddenInput( $container );
		} );

		// Ensure hidden is updated on save.
		$container.closest( 'form' ).on( 'submit', function() {
			syncDynamicMappingsHiddenInput( $container );
		} );

		// Initial sync.
		syncDynamicMappingsHiddenInput( $container );
	} );

})( jQuery );
