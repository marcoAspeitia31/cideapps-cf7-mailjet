(function( $ ) {
	'use strict';

	var SUBMIT_SELECTOR = '.wpcf7-form input[type="submit"], .wpcf7-form button[type="submit"], .wpcf7-form .wpcf7-submit';

	/**
	 * Find the CF7 form element from a DOM event.
	 *
	 * @param {Event} event DOM event.
	 * @return {HTMLElement|null}
	 */
	function getFormFromEvent( event ) {
		if ( event.target && event.target.closest ) {
			var fromTarget = event.target.closest( 'form.wpcf7-form' );
			if ( fromTarget ) {
				return fromTarget;
			}
		}

		if ( event.detail && event.detail.unitTag ) {
			var byUnit = document.querySelector( '#' + event.detail.unitTag + ' form.wpcf7-form' );
			if ( byUnit ) {
				return byUnit;
			}
		}

		return null;
	}

	/**
	 * Enable/disable submit UI and loader for a CF7 form.
	 *
	 * @param {HTMLElement} form CF7 form element.
	 * @param {boolean}     isLoading Whether submission is in progress.
	 */
	function setFormLoadingState( form, isLoading ) {
		if ( ! form ) {
			return;
		}

		var $form = $( form );
		var $submitButton = $form.find( 'input[type="submit"], button[type="submit"], .wpcf7-submit' );
		var $loader = $form.find( '.cideapps-cf7-loader' );

		if ( isLoading ) {
			$submitButton.prop( 'disabled', true ).addClass( 'cideapps-cf7-submitting' );

			if ( $loader.length === 0 ) {
				$loader = $( '<span class="cideapps-cf7-loader" aria-hidden="true"></span>' );
				$submitButton.first().after( $loader );
			}

			$loader.removeClass( 'hidden' ).show();
			return;
		}

		$submitButton.prop( 'disabled', false ).removeClass( 'cideapps-cf7-submitting' );
		$loader.hide().addClass( 'hidden' );
	}

	/**
	 * Bind CF7 front-end UX (loader + disabled submit).
	 */
	function initCf7MailjetPublicUi() {
		// Immediate feedback on click (before AJAX / reCAPTCHA).
		$( document ).on( 'click', SUBMIT_SELECTOR, function() {
			var form = this.closest( 'form.wpcf7-form' );
			if ( form && ! form.classList.contains( 'submitting' ) ) {
				setFormLoadingState( form, true );
			}
		} );

		// CF7 5.x: fires when submission starts.
		document.addEventListener( 'wpcf7submitting', function( event ) {
			setFormLoadingState( getFormFromEvent( event ), true );
		} );

		// Legacy + CF7 5.x terminal statuses.
		var endEvents = [
			'wpcf7sent',
			'wpcf7failed',
			'wpcf7invalid',
			'wpcf7spam',
			'wpcf7unaccepted',
			'wpcf7aborted',
			'wpcf7mailsent',
			'wpcf7mailfailed'
		];

		endEvents.forEach( function( eventName ) {
			document.addEventListener( eventName, function( event ) {
				setFormLoadingState( getFormFromEvent( event ), false );
			} );
		} );

		// CF7 fires wpcf7submit when the request completes (do not use it to start loading).
		document.addEventListener( 'wpcf7submit', function( event ) {
			var form = getFormFromEvent( event );
			if ( ! form || ! event.detail ) {
				return;
			}

			var status = event.detail.status || '';
			if ( status !== 'submitting' && status !== 'validating' ) {
				setFormLoadingState( form, false );
			}
		} );
	}

	$( document ).ready( initCf7MailjetPublicUi );

})( jQuery );
