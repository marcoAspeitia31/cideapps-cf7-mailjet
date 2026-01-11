<?php

/**
 * Contact Form 7 handler class
 *
 * @link       https://cideapps.com
 * @since      1.0.0
 *
 * @package    Cideapps_Cf7_Mailjet
 * @subpackage Cideapps_Cf7_Mailjet/includes
 */

/**
 * Contact Form 7 handler class.
 *
 * Handles CF7 form submissions and integrates with Mailjet.
 *
 * @package    Cideapps_Cf7_Mailjet
 * @subpackage Cideapps_Cf7_Mailjet/includes
 * @author     CIDEAPPS DIGITAL <contacto@cideapps.com>
 */
class Cideapps_Cf7_Mailjet_CF7_Handler {

	/**
	 * Mailjet API instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Cideapps_Cf7_Mailjet_API    $mailjet_api    Mailjet API instance
	 */
	private $mailjet_api;

	/**
	 * Rate limit instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Cideapps_Cf7_Mailjet_Rate_Limit    $rate_limit    Rate limit instance
	 */
	private $rate_limit;

	/**
	 * Logger instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Cideapps_Cf7_Mailjet_Logger    $logger    Logger instance
	 */
	private $logger;

	/**
	 * Initialize the class
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->mailjet_api = new Cideapps_Cf7_Mailjet_API();
		$this->rate_limit  = new Cideapps_Cf7_Mailjet_Rate_Limit();
		$this->logger      = new Cideapps_Cf7_Mailjet_Logger();
	}

	/**
	 * Resolve CF7 field label from value
	 *
	 * Given a CF7 form, field name, and submitted value, returns the human-readable label.
	 * Works with select, radio, and checkbox fields.
	 *
	 * @since    1.0.0
	 * @param    WPCF7_ContactForm    $contact_form    CF7 contact form object
	 * @param    string               $field_name      Field name (e.g., 'service')
	 * @param    string               $submitted_value Submitted value (e.g., 'apps-moviles')
	 * @return   string    Label if found, original value if not found
	 */
	private function resolve_cf7_label_from_value( $contact_form, $field_name, $submitted_value ) {
		if ( empty( $submitted_value ) ) {
			return '';
		}

		// Normalize submitted value for comparison
		$submitted = trim( (string) $submitted_value );

		// Get form tags
		$tags = $contact_form->scan_form_tags();
		if ( empty( $tags ) ) {
			return sanitize_text_field( $submitted_value );
		}

		// Find the tag with matching name
		$field_tag = null;
		foreach ( $tags as $tag ) {
			if ( isset( $tag->name ) && $tag->name === $field_name ) {
				$field_tag = $tag;
				break;
			}
		}

		if ( ! $field_tag ) {
			return sanitize_text_field( $submitted_value );
		}

		// Get values, labels, and raw_values
		$values    = isset( $field_tag->values ) && is_array( $field_tag->values ) ? $field_tag->values : array();
		$labels    = isset( $field_tag->labels ) && is_array( $field_tag->labels ) ? $field_tag->labels : array();
		$raw_values = isset( $field_tag->raw_values ) && is_array( $field_tag->raw_values ) ? $field_tag->raw_values : array();

		// Case 1: If we have labels and values arrays with matching counts
		if ( ! empty( $labels ) && count( $labels ) === count( $values ) ) {
			// Search for the value in values array using trim() comparison
			foreach ( $values as $index => $value ) {
				if ( trim( (string) $value ) === $submitted ) {
					return sanitize_text_field( $labels[ $index ] );
				}
			}
		}

		// Case 2: Fallback for "Label|value" format in raw_values
		if ( ! empty( $raw_values ) ) {
			foreach ( $raw_values as $raw ) {
				// Split by pipe if present (format: "Label|value")
				if ( strpos( $raw, '|' ) !== false ) {
					$parts = explode( '|', $raw, 2 );
					if ( count( $parts ) === 2 ) {
						$label = trim( $parts[0] );
						$val   = trim( $parts[1] );
						if ( $val === $submitted ) {
							return sanitize_text_field( $label );
						}
					}
				}
			}
		}

		// Case 3: Check if submitted matches any label directly
		if ( ! empty( $labels ) ) {
			foreach ( $labels as $label ) {
				if ( trim( (string) $label ) === $submitted ) {
					return sanitize_text_field( $label );
				}
			}
		}

		// Case 4: If not found, return original value sanitized
		return sanitize_text_field( $submitted_value );
	}

	/**
	 * Handle CF7 form submission
	 *
	 * @since    1.0.0
	 * @param    WPCF7_ContactForm    $contact_form    CF7 contact form object
	 * @return   void
	 */
	public function handle_form_submission( $contact_form ) {
		// Get form ID
		$form_id = $contact_form->id();

		// Check if this form is enabled
		$enabled_forms = get_option( 'cideapps_cf7_mailjet_enabled_form_ids', array() );
		if ( ! is_array( $enabled_forms ) ) {
			$enabled_forms = array();
		}

		if ( ! in_array( $form_id, $enabled_forms, true ) ) {
			$this->logger->info( "Form ID {$form_id} is not enabled. Skipping." );
			return;
		}

		// Get submission object
		$submission = WPCF7_Submission::get_instance();
		if ( ! $submission ) {
			$this->logger->error( "Could not get CF7 submission object for form ID {$form_id}" );
			return;
		}

		// Get posted data
		$posted_data = $submission->get_posted_data();
		if ( empty( $posted_data ) ) {
			$this->logger->error( "No posted data found for form ID {$form_id}" );
			return;
		}

		// Get field names from options
		$email_field   = get_option( 'cideapps_cf7_mailjet_email_field', 'your-email' );
		$name_field    = get_option( 'cideapps_cf7_mailjet_name_field', 'your-name' );
		$phone_field   = get_option( 'cideapps_cf7_mailjet_phone_field', 'your-phone' );
		$service_field = get_option( 'cideapps_cf7_mailjet_service_field', 'service' );

		// Extract and sanitize data
		$email = isset( $posted_data[ $email_field ] ) ? sanitize_email( $posted_data[ $email_field ] ) : '';
		$name  = isset( $posted_data[ $name_field ] ) ? sanitize_text_field( $posted_data[ $name_field ] ) : '';
		$phone = isset( $posted_data[ $phone_field ] ) ? sanitize_text_field( $posted_data[ $phone_field ] ) : '';
		
		// Handle service field (can be string or array for select/checkbox/radio fields)
		$service = '';
		if ( isset( $posted_data[ $service_field ] ) ) {
			if ( is_array( $posted_data[ $service_field ] ) ) {
				$service = sanitize_text_field( $posted_data[ $service_field ][0] ?? '' );
			} else {
				$service = sanitize_text_field( $posted_data[ $service_field ] );
			}
		}

		// Convert service value to label if option is enabled
		$service_send_label = get_option( 'cideapps_cf7_mailjet_service_send_label', false );
		$service_raw         = $service; // Keep original for logging
		if ( $service_send_label && ! empty( $service ) ) {
			$service = $this->resolve_cf7_label_from_value( $contact_form, $service_field, $service );
			if ( $service !== $service_raw ) {
				$this->logger->info( "Service field resolved: value '{$service_raw}' -> label '{$service}'" );
			}
		}

		// Validate email
		if ( empty( $email ) || ! is_email( $email ) ) {
			$this->logger->error( "Invalid or missing email for form ID {$form_id}" );
			return;
		}

		// Get client IP
		$client_ip = $this->rate_limit->get_client_ip();

		// Check rate limits
		if ( $this->rate_limit->is_email_rate_limited( $email ) ) {
			$this->logger->warning( "Email rate limit exceeded for: {$email}" );
			return;
		}

		if ( $this->rate_limit->is_ip_rate_limited( $client_ip ) ) {
			$this->logger->warning( "IP rate limit exceeded for: {$client_ip}" );
			return;
		}

		// Set rate limits
		$this->rate_limit->set_email_rate_limit( $email );
		$this->rate_limit->set_ip_rate_limit( $client_ip );

		$this->logger->info( "Processing form submission for form ID {$form_id}, email: {$email}" );

		// Prepare contact properties for Mailjet
		$contact_properties = array(
			'name'       => $name,
			'phone'      => $phone,
			'service'    => $service,
			'source'     => 'CF7',
			'form_id'    => (string) $form_id,
			'created_at' => current_time( 'mysql' ),
		);

		// Add contact to list (if enabled)
		$enable_contact_list = get_option( 'cideapps_cf7_mailjet_enable_contact_list', false );
		if ( $enable_contact_list ) {
			$list_id           = (int) get_option( 'cideapps_cf7_mailjet_list_id', 0 );
			$on_existing       = get_option( 'cideapps_cf7_mailjet_on_existing_contact', 'update_properties' );

			if ( ! empty( $list_id ) ) {
				$list_result = $this->mailjet_api->add_contact_to_list( $email, $contact_properties, $list_id, $on_existing );
				if ( is_wp_error( $list_result ) ) {
					$this->logger->error( "Error adding contact to list: " . $list_result->get_error_message() );
				} else {
					$this->logger->info( "Contact successfully added/updated in list for email: {$email}" );
				}
			} else {
				$this->logger->warning( "Contact list is enabled but list ID is not configured." );
			}
		}

		// Send autoreply (if enabled)
		$enable_autoreply = get_option( 'cideapps_cf7_mailjet_enable_autoreply', false );
		if ( $enable_autoreply ) {
			$template_id = (int) get_option( 'cideapps_cf7_mailjet_template_id', 0 );
			if ( ! empty( $template_id ) ) {
				$from_email = get_option( 'cideapps_cf7_mailjet_from_email', '' );
				$from_name  = get_option( 'cideapps_cf7_mailjet_from_name', '' );

				$template_variables = array(
					'name'    => $name,
					'email'   => $email,
					'phone'   => $phone,
					'service' => $service,
				);

				$email_result = $this->mailjet_api->send_email( $email, $template_id, $template_variables, $from_email, $from_name );
				if ( is_wp_error( $email_result ) ) {
					$this->logger->error( "Error sending autoreply email: " . $email_result->get_error_message() );
				} else {
					$this->logger->info( "Autoreply email successfully sent to: {$email}" );
				}
			} else {
				$this->logger->warning( "Autoreply is enabled but template ID is not configured." );
			}
		}
	}
}

