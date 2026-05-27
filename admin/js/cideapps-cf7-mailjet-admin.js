(function( $ ) {
	'use strict';

	/**
	 * Initialize repeatable mapping rows (dynamic fields + attachments).
	 *
	 * @param {Object} config Configuration.
	 */
	function initRepeatableMappings( config ) {
		var $container = $( config.containerSelector );
		if ( $container.length === 0 ) {
			return;
		}

		function syncHiddenInput() {
			if ( ! config.hiddenInputSelector ) {
				return;
			}

			var lines = [];
			$container.find( '.cideapps-cf7-mappings__row' ).each( function() {
				var $row = $( this );
				var source = ( $row.find( '.cideapps-cf7-mappings__source' ).val() || '' ).toString().trim();
				var target = ( $row.find( '.cideapps-cf7-mappings__target' ).val() || '' ).toString().trim();

				if ( ! source || ! target ) {
					return;
				}

				target = target.toLowerCase().replace( /[^a-z0-9_]/g, '_' );
				lines.push( source + ':' + target );
			} );

			$( config.hiddenInputSelector ).val( lines.join( '\n' ) );
		}

		function createRow( source, target ) {
			source = source || '';
			target = target || '';

			var $row = $( '<div class="cideapps-cf7-mappings__row">' +
				'<input type="text" class="regular-text cideapps-cf7-mappings__source" />' +
				'<span class="cideapps-cf7-mappings__arrow" aria-hidden="true">→</span>' +
				'<input type="text" class="regular-text cideapps-cf7-mappings__target" />' +
				'<button type="button" class="button cideapps-cf7-mappings__remove"></button>' +
			'</div>' );

			$row.find( '.cideapps-cf7-mappings__source' )
				.attr( 'name', config.sourceInputName )
				.attr( 'placeholder', config.sourcePlaceholder )
				.val( source );

			$row.find( '.cideapps-cf7-mappings__target' )
				.attr( 'name', config.targetInputName )
				.attr( 'placeholder', config.targetPlaceholder )
				.val( target );

			$row.find( '.cideapps-cf7-mappings__remove' ).text( config.removeLabel );

			return $row;
		}

		$( config.addButtonSelector ).on( 'click', function() {
			$container.append( createRow() );
			syncHiddenInput();
		} );

		$container.on( 'click', '.cideapps-cf7-mappings__remove', function() {
			$( this ).closest( '.cideapps-cf7-mappings__row' ).remove();
			if ( $container.find( '.cideapps-cf7-mappings__row' ).length === 0 ) {
				$container.append( createRow() );
			}
			syncHiddenInput();
		} );

		$container.on( 'input', '.cideapps-cf7-mappings__source, .cideapps-cf7-mappings__target', syncHiddenInput );
		$container.closest( 'form' ).on( 'submit', syncHiddenInput );

		syncHiddenInput();
	}

	$( function() {
		initRepeatableMappings( {
			containerSelector: '#cideapps-cf7-mailjet-dynamic-mappings',
			addButtonSelector: '#cideapps-cf7-mailjet-dynamic-mappings-add',
			hiddenInputSelector: '#cideapps_cf7_mailjet_dynamic_mappings',
			sourceInputName: 'cideapps_cf7_mailjet_dynamic_mappings_source[]',
			targetInputName: 'cideapps_cf7_mailjet_dynamic_mappings_target[]',
			sourcePlaceholder: 'origen (ej: your-company o [_url])',
			targetPlaceholder: 'variable Mailjet (ej: company)',
			removeLabel: 'Remove'
		} );

		initRepeatableMappings( {
			containerSelector: '#cideapps-cf7-mailjet-attachment-mappings',
			addButtonSelector: '#cideapps-cf7-mailjet-attachment-mappings-add',
			hiddenInputSelector: '#cideapps_cf7_mailjet_attachment_mappings',
			sourceInputName: 'cideapps_cf7_mailjet_attachment_mappings_source[]',
			targetInputName: 'cideapps_cf7_mailjet_attachment_mappings_target[]',
			sourcePlaceholder: 'campo file CF7 (ej: your-cv)',
			targetPlaceholder: 'variable Mailjet (ej: cv_url)',
			removeLabel: 'Quitar'
		} );
	} );

})( jQuery );
