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
	 * Delivery mode: CF7 native mail + Mailjet after mail_sent.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	const DELIVERY_MODE_CF7_MAIL = 'cf7_mail';

	/**
	 * Delivery mode: skip CF7 mail, Mailjet only via API.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	const DELIVERY_MODE_MAILJET_ONLY = 'mailjet_only';

	/**
	 * Idempotency transient TTL in seconds (5 minutes).
	 *
	 * @since 1.1.0
	 * @var int
	 */
	const IDEMPOTENCY_TTL = 300;

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
	 * Submission data builder.
	 *
	 * @since 1.2.0
	 * @var Cideapps_Cf7_Mailjet_Submission_Data
	 */
	private $submission_data;

	/**
	 * Initialize the class
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->mailjet_api     = new Cideapps_Cf7_Mailjet_API();
		$this->rate_limit      = new Cideapps_Cf7_Mailjet_Rate_Limit();
		$this->logger          = new Cideapps_Cf7_Mailjet_Logger();
		$this->submission_data = new Cideapps_Cf7_Mailjet_Submission_Data();
	}

	/**
	 * Handle CF7 form submission (wpcf7_mail_sent).
	 *
	 * @since 1.0.0
	 * @param WPCF7_ContactForm $contact_form CF7 contact form object.
	 * @return void
	 */
	public function handle_form_submission( $contact_form ) {
		$this->process_submission( $contact_form );
	}

	/**
	 * Maybe skip CF7 native mail when form uses mailjet_only delivery mode.
	 *
	 * @since 1.1.0
	 * @param bool              $skip         Whether CF7 should skip mail.
	 * @param WPCF7_ContactForm $contact_form CF7 contact form object.
	 * @return bool
	 */
	public function maybe_skip_cf7_mail( $skip, $contact_form ) {
		$form_id = (int) $contact_form->id();

		if ( ! $this->is_form_enabled( $form_id ) ) {
			return $skip;
		}

		$mode = $this->get_delivery_mode( $form_id );
		$this->logger->info( "Delivery mode for form {$form_id}: {$mode}" );

		if ( self::DELIVERY_MODE_MAILJET_ONLY === $mode ) {
			$this->logger->info( 'wpcf7_skip_mail applied: yes' );
			return true;
		}

		$this->logger->info( 'wpcf7_skip_mail applied: no' );
		return $skip;
	}

	/**
	 * Process a validated CF7 submission and run Mailjet actions.
	 *
	 * @since 1.1.0
	 * @param WPCF7_ContactForm $contact_form CF7 contact form object.
	 * @return void
	 */
	public function process_submission( $contact_form ) {
		$form_id = (int) $contact_form->id();

		if ( ! $this->is_form_enabled( $form_id ) ) {
			$this->logger->info( "Form ID {$form_id} is not enabled. Skipping." );
			return;
		}

		$mode = $this->get_delivery_mode( $form_id );
		$this->logger->info( "Delivery mode for form {$form_id}: {$mode}" );

		$submission = WPCF7_Submission::get_instance();
		if ( ! $submission ) {
			$this->logger->error( "Could not get CF7 submission object for form ID {$form_id}" );
			return;
		}

		$posted_data = $submission->get_posted_data();
		if ( empty( $posted_data ) ) {
			$this->logger->error( "No posted data found for form ID {$form_id}" );
			return;
		}

		$core_fields = $this->submission_data->extract_core_fields( $contact_form, $posted_data, $this );
		$email       = $core_fields['email'];
		$name        = $core_fields['name'];
		$phone       = $core_fields['phone'];
		$service     = $core_fields['service'];
		$message     = $core_fields['message'];

		if ( ! empty( $core_fields['service_raw'] ) && $service !== $core_fields['service_raw'] ) {
			$this->logger->info( "Service field resolved: value(s) '{$core_fields['service_raw']}' -> label(s) '{$service}'" );
		}

		if ( empty( $email ) || ! is_email( $email ) ) {
			$this->logger->error( "Invalid or missing email for form ID {$form_id}" );
			return;
		}

		$idempotency_key = $this->get_submission_idempotency_key( $form_id, $email, $posted_data );
		if ( $this->is_submission_already_processed( $idempotency_key ) ) {
			$this->logger->info( "Skipped: submission already processed for form ID {$form_id} (idempotency)." );
			return;
		}

		$this->mark_submission_processed( $idempotency_key );

		$client_ip = $this->rate_limit->get_client_ip();

		if ( $this->rate_limit->is_email_rate_limited( $email ) ) {
			$this->logger->warning( "Email rate limit exceeded for: {$email}" );
			return;
		}

		if ( $this->rate_limit->is_ip_rate_limited( $client_ip ) ) {
			$this->logger->warning( "IP rate limit exceeded for: {$client_ip}" );
			return;
		}

		$this->rate_limit->set_email_rate_limit( $email );
		$this->rate_limit->set_ip_rate_limit( $client_ip );

		$this->logger->info( "Processing form submission for form ID {$form_id}, email: {$email}" );

		$contact_properties            = $this->submission_data->build_contact_properties( $core_fields );
		$template_variables            = $this->submission_data->build_template_variables( $core_fields, $form_id, $submission );
		$business_notification_variables = $template_variables;

		// 1) Business notification first (only for mailjet_only mode).
		if ( self::DELIVERY_MODE_MAILJET_ONLY === $mode ) {
			$this->send_business_notification( $contact_form, $posted_data, $email, $business_notification_variables, $submission );
		}

		$enable_contact_list_raw = get_option( 'cideapps_cf7_mailjet_enable_contact_list', 0 );
		$enable_contact_list     = ( $enable_contact_list_raw === 1 || $enable_contact_list_raw === '1' || $enable_contact_list_raw === true );

		// 2) Add lead to contact list.
		if ( $enable_contact_list ) {
			$list_id     = (int) get_option( 'cideapps_cf7_mailjet_list_id', 0 );
			$on_existing = get_option( 'cideapps_cf7_mailjet_on_existing_contact', 'update_properties' );

			$this->logger->info( "Contact list enabled. List ID: {$list_id}, On existing: {$on_existing}, Email: {$email}" );

			if ( ! empty( $list_id ) ) {
				$this->logger->info( "Calling add_contact_to_list for email: {$email}, list_id: {$list_id}" );
				$list_result = $this->mailjet_api->add_contact_to_list( $email, $contact_properties, $list_id, $on_existing );

				if ( is_wp_error( $list_result ) ) {
					$error_message = $list_result->get_error_message();
					$error_code    = $list_result->get_error_code();
					$error_data    = $list_result->get_error_data();
					$status        = isset( $error_data['status'] ) ? $error_data['status'] : 'unknown';
					$this->logger->error( "Error adding contact to list - Code: {$error_code}, Status: {$status}, Message: {$error_message}" );
				} else {
					$this->logger->info( "Contact successfully added/updated in list for email: {$email}" );
				}
			} else {
				$this->logger->warning( 'Contact list is enabled but list ID is not configured (empty or 0).' );
			}
		} else {
			$this->logger->info( 'Contact list is disabled (enable_contact_list value: ' . var_export( $enable_contact_list_raw, true ) . ')' );
		}

		// 3) Send customer autoreply.
		$enable_autoreply = get_option( 'cideapps_cf7_mailjet_enable_autoreply', false );
		if ( $enable_autoreply ) {
			$template_id = (int) get_option( 'cideapps_cf7_mailjet_template_id', 0 );
			if ( ! empty( $template_id ) ) {
				$from_email = get_option( 'cideapps_cf7_mailjet_from_email', '' );
				$from_name  = get_option( 'cideapps_cf7_mailjet_from_name', '' );

				$email_result = $this->mailjet_api->send_email( $email, $template_id, $business_notification_variables, $from_email, $from_name );
				if ( is_wp_error( $email_result ) ) {
					$this->logger->error( 'Error sending autoreply email: ' . $email_result->get_error_message() );
				} else {
					$this->logger->info( "Autoreply email successfully sent to: {$email}" );
				}
			} else {
				$this->logger->warning( 'Autoreply is enabled but template ID is not configured.' );
			}
		}

		if ( self::DELIVERY_MODE_MAILJET_ONLY === $mode ) {
			$this->logger->info( "Mailjet-only path completed for form ID {$form_id}." );
		}
	}

	/**
	 * Send owner/business notification email when form runs in mailjet_only mode.
	 *
	 * @since 1.1.0
	 * @param WPCF7_ContactForm $contact_form CF7 contact form object.
	 * @param array             $posted_data  Posted form data.
	 * @param string            $lead_email   Lead email.
	 * @param array             $variables    Common template variables.
	 * @return void
	 */
	private function send_business_notification( $contact_form, $posted_data, $lead_email, $variables, $submission = null ) {
		$enabled_raw = get_option( 'cideapps_cf7_mailjet_owner_notify_enabled', 0 );
		$enabled     = ( $enabled_raw === 1 || $enabled_raw === '1' || $enabled_raw === true );
		if ( ! $enabled ) {
			$this->logger->info( 'Business notification disabled for mailjet_only mode.' );
			return;
		}

		$to_email = sanitize_email( get_option( 'cideapps_cf7_mailjet_owner_notify_to_email', '' ) );
		if ( empty( $to_email ) || ! is_email( $to_email ) ) {
			$this->logger->warning( 'Business notification enabled but destination email is missing or invalid.' );
			return;
		}

		$mode = get_option( 'cideapps_cf7_mailjet_owner_notify_mode', 'template' );
		if ( ! in_array( $mode, array( 'template', 'html_default' ), true ) ) {
			$mode = 'template';
		}

		$from_email = get_option( 'cideapps_cf7_mailjet_from_email', '' );
		$from_name  = get_option( 'cideapps_cf7_mailjet_from_name', '' );

		if ( 'template' === $mode ) {
			$template_id = (int) get_option( 'cideapps_cf7_mailjet_owner_notify_template_id', 0 );
			if ( empty( $template_id ) ) {
				$this->logger->warning( 'Business notification mode is template but Template ID is not configured.' );
				return;
			}

			$result = $this->mailjet_api->send_email( $to_email, $template_id, $variables, $from_email, $from_name, $lead_email );
		} else {
			$subject = get_option( 'cideapps_cf7_mailjet_owner_notify_subject', __( 'Nuevo lead desde formulario web', 'cideapps-cf7-mailjet' ) );
			$html    = $this->build_default_business_notification_html( $contact_form, $posted_data, $submission );

			$result = $this->mailjet_api->send_html_email( $to_email, $subject, $html, $from_email, $from_name, $lead_email );
		}

		if ( is_wp_error( $result ) ) {
			$this->logger->error( 'Error sending business notification email: ' . $result->get_error_message() );
		} else {
			$this->logger->info( "Business notification email sent to: {$to_email}" );
		}
	}

	/**
	 * Build default HTML body for business notification email.
	 *
	 * @since 1.1.0
	 * @param WPCF7_ContactForm $contact_form CF7 contact form object.
	 * @param array             $posted_data  Posted form data.
	 * @return string
	 */
	private function build_default_business_notification_html( $contact_form, $posted_data, $submission = null ) {
		$form_title = method_exists( $contact_form, 'title' ) ? $contact_form->title() : '';

		$attachment_urls_by_field = array();
		if ( $submission instanceof WPCF7_Submission && $this->submission_data->is_attachment_urls_enabled() ) {
			$attachment_urls_by_field = $this->submission_data->get_persisted_attachment_urls_by_field( $submission );
		}

		$rows = '';
		foreach ( $posted_data as $field_name => $field_value ) {
			if ( isset( $attachment_urls_by_field[ $field_name ] ) ) {
				$links = array();
				foreach ( $attachment_urls_by_field[ $field_name ] as $file_url ) {
					$links[] = '<a href="' . esc_url( $file_url ) . '" target="_blank" rel="noopener noreferrer">' .
						esc_html( wp_basename( $file_url ) ) .
						'</a>';
				}
				$value = implode( '<br />', $links );
			} elseif ( is_array( $field_value ) ) {
				$value = esc_html( implode( ', ', array_map( 'sanitize_text_field', $field_value ) ) );
			} else {
				$value = esc_html( sanitize_text_field( $field_value ) );
			}

			if ( '' === trim( wp_strip_all_tags( (string) $value ) ) ) {
				continue;
			}

			$rows .= '<tr><td style="padding:8px;border:1px solid #ddd;"><strong>' .
				esc_html( $field_name ) .
				'</strong></td><td style="padding:8px;border:1px solid #ddd;">' .
				$value .
				'</td></tr>';
		}

		$html  = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#222;">';
		$html .= '<h2 style="margin:0 0 16px;">' . esc_html__( 'Nuevo lead recibido', 'cideapps-cf7-mailjet' ) . '</h2>';
		if ( ! empty( $form_title ) ) {
			$html .= '<p><strong>' . esc_html__( 'Formulario:', 'cideapps-cf7-mailjet' ) . '</strong> ' . esc_html( $form_title ) . '</p>';
		}
		$html .= '<table style="border-collapse:collapse;width:100%;max-width:720px;">';
		$html .= '<tbody>' . $rows . '</tbody>';
		$html .= '</table></div>';

		return $html;
	}

	/**
	 * Check whether a CF7 form is enabled in plugin settings.
	 *
	 * @since 1.1.0
	 * @param int $form_id CF7 form ID.
	 * @return bool
	 */
	private function is_form_enabled( $form_id ) {
		$enabled_forms = get_option( 'cideapps_cf7_mailjet_enabled_form_ids', array() );
		if ( ! is_array( $enabled_forms ) ) {
			$enabled_forms = array();
		}

		return in_array( (int) $form_id, array_map( 'intval', $enabled_forms ), true );
	}

	/**
	 * Get delivery mode for a form.
	 *
	 * @since 1.1.0
	 * @param int $form_id CF7 form ID.
	 * @return string cf7_mail|mailjet_only
	 */
	public function get_delivery_mode( $form_id ) {
		$modes   = get_option( 'cideapps_cf7_mailjet_form_mail_modes', array() );
		$form_id = (int) $form_id;

		if ( is_array( $modes ) && isset( $modes[ $form_id ] ) && self::DELIVERY_MODE_MAILJET_ONLY === $modes[ $form_id ] ) {
			return self::DELIVERY_MODE_MAILJET_ONLY;
		}

		return self::DELIVERY_MODE_CF7_MAIL;
	}

	/**
	 * Build idempotency transient key for a submission.
	 *
	 * @since 1.1.0
	 * @param int    $form_id     CF7 form ID.
	 * @param string $email       Sanitized submitter email.
	 * @param array  $posted_data Posted form data.
	 * @return string
	 */
	private function get_submission_idempotency_key( $form_id, $email, $posted_data ) {
		$data_hash = md5( wp_json_encode( $posted_data ) );
		$email_key = md5( strtolower( sanitize_email( $email ) ) );

		return 'cf7_mj_proc_' . md5( (int) $form_id . '|' . $email_key . '|' . $data_hash );
	}

	/**
	 * Check if submission was already processed recently.
	 *
	 * @since 1.1.0
	 * @param string $key Transient key.
	 * @return bool
	 */
	private function is_submission_already_processed( $key ) {
		return (bool) get_transient( $key );
	}

	/**
	 * Mark submission as processed to prevent duplicate API calls.
	 *
	 * @since 1.1.0
	 * @param string $key Transient key.
	 * @return void
	 */
	private function mark_submission_processed( $key ) {
		set_transient( $key, time(), self::IDEMPOTENCY_TTL );
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
	public function resolve_cf7_label_from_value( $contact_form, $field_name, $submitted_value ) {
		if ( empty( $submitted_value ) ) {
			return '';
		}

		$submitted = trim( (string) $submitted_value );

		$tags = $contact_form->scan_form_tags();
		if ( empty( $tags ) ) {
			return sanitize_text_field( $submitted );
		}

		$field_tag = null;
		foreach ( $tags as $tag ) {
			if ( isset( $tag->name ) && $tag->name === $field_name ) {
				$field_tag = $tag;
				break;
			}
		}

		if ( ! $field_tag ) {
			return sanitize_text_field( $submitted );
		}

		$values     = isset( $field_tag->values ) && is_array( $field_tag->values ) ? $field_tag->values : array();
		$labels     = isset( $field_tag->labels ) && is_array( $field_tag->labels ) ? $field_tag->labels : array();
		$raw_values = isset( $field_tag->raw_values ) && is_array( $field_tag->raw_values ) ? $field_tag->raw_values : array();

		if ( ! empty( $labels ) && count( $labels ) === count( $values ) ) {
			foreach ( $values as $index => $value ) {
				if ( trim( (string) $value ) === $submitted ) {
					return sanitize_text_field( $labels[ $index ] );
				}
			}
		}

		if ( ! empty( $raw_values ) ) {
			foreach ( $raw_values as $raw ) {
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

		if ( ! empty( $labels ) ) {
			foreach ( $labels as $label ) {
				if ( trim( (string) $label ) === $submitted ) {
					return sanitize_text_field( $label );
				}
			}
		}

		return sanitize_text_field( $submitted );
	}
}
