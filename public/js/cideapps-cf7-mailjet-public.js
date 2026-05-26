(function( $ ) {
	'use strict';

	var SUBMIT_SELECTOR = '.wpcf7-form input[type="submit"], .wpcf7-form button[type="submit"], .wpcf7-form .wpcf7-submit';
	var LOADING_TIMEOUT_MS = 60000;
	var loadingTimeouts = new WeakMap();

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
	 * Clear safety timeout for a form.
	 *
	 * @param {HTMLElement} form CF7 form element.
	 */
	function clearLoadingTimeout( form ) {
		var timeoutId = loadingTimeouts.get( form );
		if ( timeoutId ) {
			window.clearTimeout( timeoutId );
			loadingTimeouts.delete( form );
		}
	}

	/**
	 * Start safety timeout to recover UI if CF7 never completes.
	 *
	 * @param {HTMLElement} form CF7 form element.
	 */
	function armLoadingTimeout( form ) {
		clearLoadingTimeout( form );
		loadingTimeouts.set(
			form,
			window.setTimeout( function() {
				setFormLoadingState( form, false );
			}, LOADING_TIMEOUT_MS )
		);
	}

	/**
	 * Update submit UI and loader for a CF7 form.
	 *
	 * @param {HTMLElement} form       CF7 form element.
	 * @param {boolean}     isLoading  Whether submission is in progress.
	 * @param {Object}      options    Options.
	 * @param {boolean}     options.disableButton Whether to set the disabled attribute.
	 */
	function setFormLoadingState( form, isLoading, options ) {
		if ( ! form ) {
			return;
		}

		options = options || {};
		var disableButton = options.disableButton !== false;

		var $form = $( form );
		var $submitButton = $form.find( 'input[type="submit"], button[type="submit"], .wpcf7-submit' );
		var $loader = $form.find( '.cideapps-cf7-loader' );

		if ( isLoading ) {
			$submitButton.addClass( 'cideapps-cf7-submitting' );
			$form.addClass( 'cideapps-cf7-form-submitting' );

			if ( disableButton ) {
				$submitButton.prop( 'disabled', true );
			}

			if ( $loader.length === 0 ) {
				$loader = $( '<span class="cideapps-cf7-loader" aria-hidden="true"></span>' );
				$submitButton.first().after( $loader );
			}

			$loader.removeClass( 'hidden' ).show();
			armLoadingTimeout( form );
			return;
		}

		clearLoadingTimeout( form );
		$submitButton.prop( 'disabled', false ).removeClass( 'cideapps-cf7-submitting' );
		$form.removeClass( 'cideapps-cf7-form-submitting' );
		$loader.hide().addClass( 'hidden' );
	}

	/**
	 * Bind CF7 front-end UX (loader + disabled submit).
	 */
	function initCf7MailjetPublicUi() {
		// Immediate visual feedback only — do NOT disable the button here or CF7/reCAPTCHA cannot submit.
		$( document ).on( 'click', SUBMIT_SELECTOR, function() {
			var form = this.closest( 'form.wpcf7-form' );
			if ( form && ! form.classList.contains( 'submitting' ) ) {
				setFormLoadingState( form, true, { disableButton: false } );
			}
		} );

		// CF7 5.x: real submission started — now safe to disable the button.
		document.addEventListener( 'wpcf7submitting', function( event ) {
			setFormLoadingState( getFormFromEvent( event ), true, { disableButton: true } );
		} );

		var endEvents = [
			'wpcf7sent',
			'wpcf7failed',
			'wpcf7invalid',
			'wpcf7spam',
			'wpcf7unaccepted',
			'wpcf7aborted',
			'wpcf7reset',
			'wpcf7mailsent',
			'wpcf7mailfailed'
		];

		endEvents.forEach( function( eventName ) {
			document.addEventListener( eventName, function( event ) {
				setFormLoadingState( getFormFromEvent( event ), false );
			} );
		} );

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
