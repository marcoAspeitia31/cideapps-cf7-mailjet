<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://cideapps.com
 * @since      1.0.0
 *
 * @package    Cideapps_Cf7_Mailjet
 * @subpackage Cideapps_Cf7_Mailjet/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Test list connection — only when the dedicated test form is submitted (not on "Guardar Configuración").
if (
	isset( $_POST['cideapps_cf7_mailjet_test_list'] )
	&& $_POST['cideapps_cf7_mailjet_test_list'] === '1'
	&& ! isset( $_POST['cideapps_cf7_mailjet_settings_submit'] )
) {
	if ( check_admin_referer( 'cideapps_cf7_mailjet_test_list' ) ) {
		$test_email = isset( $_POST['cideapps_cf7_mailjet_test_email'] ) ? sanitize_email( $_POST['cideapps_cf7_mailjet_test_email'] ) : '';
		if ( empty( $test_email ) ) {
			$current_user = wp_get_current_user();
			$test_email   = $current_user->user_email;
		}
		
		if ( ! empty( $test_email ) && is_email( $test_email ) ) {
			$test_public_key  = get_option( 'cideapps_cf7_mailjet_public_key', '' );
			$test_private_key = get_option( 'cideapps_cf7_mailjet_private_key', '' );
			$test_list_id     = (int) get_option( 'cideapps_cf7_mailjet_list_id', 0 );
			
			if ( ! empty( $test_public_key ) && ! empty( $test_private_key ) && ! empty( $test_list_id ) ) {
				// Get plugin directory path - this file is in admin/partials/, so we need to go up 2 levels
				$plugin_dir = dirname( dirname( dirname( __FILE__ ) ) );
				require_once $plugin_dir . '/includes/class-cideapps-cf7-mailjet-mailjet-api.php';
				$mailjet_api = new Cideapps_Cf7_Mailjet_API();
				
				$test_properties = array(
					'name' => 'Contacto de Prueba',
				);
				
				$test_result = $mailjet_api->add_contact_to_list( $test_email, $test_properties, $test_list_id, 'update_properties' );
				
				if ( is_wp_error( $test_result ) ) {
					$error_message = $test_result->get_error_message();
					$error_code    = $test_result->get_error_code();
					$error_data    = $test_result->get_error_data();
					$status        = isset( $error_data['status'] ) ? $error_data['status'] : 'unknown';
					$test_message  = sprintf( 
						__( 'Error al probar la lista: %s (Código: %s, Status: %s)', 'cideapps-cf7-mailjet' ),
						esc_html( $error_message ),
						esc_html( $error_code ),
						esc_html( $status )
					);
					$test_notice_type = 'error';
				} else {
					$test_message      = sprintf( 
						__( '✓ Prueba exitosa: El contacto %s se agregó correctamente a la lista (ID: %d)', 'cideapps-cf7-mailjet' ),
						esc_html( $test_email ),
						esc_html( $test_list_id )
					);
					$test_notice_type = 'success';
				}
			} else {
				$test_message      = __( 'Error: Faltan credenciales de Mailjet o List ID. Por favor, configura las credenciales y el List ID primero.', 'cideapps-cf7-mailjet' );
				$test_notice_type = 'error';
			}
		} else {
			$test_message      = __( 'Error: Email de prueba inválido.', 'cideapps-cf7-mailjet' );
			$test_notice_type = 'error';
		}
	}
}

// Handle form submission
if ( isset( $_POST['cideapps_cf7_mailjet_settings_submit'] ) && check_admin_referer( 'cideapps_cf7_mailjet_settings', 'cideapps_cf7_mailjet_settings_nonce' ) ) {
	// Mailjet credentials
	if ( isset( $_POST['cideapps_cf7_mailjet_public_key'] ) ) {
		update_option( 'cideapps_cf7_mailjet_public_key', sanitize_text_field( wp_unslash( $_POST['cideapps_cf7_mailjet_public_key'] ) ) );
	}
	if ( isset( $_POST['cideapps_cf7_mailjet_private_key'] ) ) {
		update_option( 'cideapps_cf7_mailjet_private_key', sanitize_text_field( wp_unslash( $_POST['cideapps_cf7_mailjet_private_key'] ) ) );
	}
	if ( isset( $_POST['cideapps_cf7_mailjet_from_email'] ) ) {
		update_option( 'cideapps_cf7_mailjet_from_email', sanitize_email( wp_unslash( $_POST['cideapps_cf7_mailjet_from_email'] ) ) );
	}
	if ( isset( $_POST['cideapps_cf7_mailjet_from_name'] ) ) {
		update_option( 'cideapps_cf7_mailjet_from_name', sanitize_text_field( wp_unslash( $_POST['cideapps_cf7_mailjet_from_name'] ) ) );
	}

	// Autoreply
	$enable_autoreply_value = isset( $_POST['cideapps_cf7_mailjet_enable_autoreply'] ) && $_POST['cideapps_cf7_mailjet_enable_autoreply'] === '1' ? 1 : 0;
	update_option( 'cideapps_cf7_mailjet_enable_autoreply', $enable_autoreply_value );
	if ( isset( $_POST['cideapps_cf7_mailjet_template_id'] ) ) {
		update_option( 'cideapps_cf7_mailjet_template_id', intval( $_POST['cideapps_cf7_mailjet_template_id'] ) );
	}

	// Contact list
	$enable_contact_list_value = isset( $_POST['cideapps_cf7_mailjet_enable_contact_list'] ) && $_POST['cideapps_cf7_mailjet_enable_contact_list'] === '1' ? 1 : 0;
	update_option( 'cideapps_cf7_mailjet_enable_contact_list', $enable_contact_list_value );
	if ( isset( $_POST['cideapps_cf7_mailjet_list_id'] ) ) {
		update_option( 'cideapps_cf7_mailjet_list_id', intval( $_POST['cideapps_cf7_mailjet_list_id'] ) );
	}
	if ( isset( $_POST['cideapps_cf7_mailjet_on_existing_contact'] ) ) {
		update_option( 'cideapps_cf7_mailjet_on_existing_contact', sanitize_text_field( wp_unslash( $_POST['cideapps_cf7_mailjet_on_existing_contact'] ) ) );
	}

	// CF7
	if ( isset( $_POST['cideapps_cf7_mailjet_enabled_form_ids'] ) && is_array( $_POST['cideapps_cf7_mailjet_enabled_form_ids'] ) ) {
		$form_ids = array_map( 'intval', $_POST['cideapps_cf7_mailjet_enabled_form_ids'] );
		update_option( 'cideapps_cf7_mailjet_enabled_form_ids', $form_ids );
	} else {
		update_option( 'cideapps_cf7_mailjet_enabled_form_ids', array() );
	}
	if ( isset( $_POST['cideapps_cf7_mailjet_form_mail_modes'] ) && is_array( $_POST['cideapps_cf7_mailjet_form_mail_modes'] ) ) {
		$form_mail_modes = array();
		$allowed_modes   = array( 'cf7_mail', 'mailjet_only' );
		foreach ( $_POST['cideapps_cf7_mailjet_form_mail_modes'] as $form_id => $mode ) {
			$form_id = (int) $form_id;
			$mode    = sanitize_text_field( wp_unslash( $mode ) );
			if ( $form_id > 0 && in_array( $mode, $allowed_modes, true ) ) {
				$form_mail_modes[ $form_id ] = $mode;
			}
		}
		update_option( 'cideapps_cf7_mailjet_form_mail_modes', $form_mail_modes );
	}
	$form_settings_raw = isset( $_POST['cideapps_cf7_mailjet_form_settings'] ) && is_array( $_POST['cideapps_cf7_mailjet_form_settings'] )
		? wp_unslash( $_POST['cideapps_cf7_mailjet_form_settings'] )
		: array();
	$form_settings_incoming = Cideapps_Cf7_Mailjet_Form_Settings::sanitize_settings( $form_settings_raw );
	$form_settings_merged   = Cideapps_Cf7_Mailjet_Form_Settings::merge_sanitized_settings( $form_settings_incoming );
	Cideapps_Cf7_Mailjet_Form_Settings::update_settings( $form_settings_merged );
	if ( isset( $_POST['cideapps_cf7_mailjet_email_field'] ) ) {
		update_option( 'cideapps_cf7_mailjet_email_field', sanitize_text_field( wp_unslash( $_POST['cideapps_cf7_mailjet_email_field'] ) ) );
	}
	if ( isset( $_POST['cideapps_cf7_mailjet_name_field'] ) ) {
		update_option( 'cideapps_cf7_mailjet_name_field', sanitize_text_field( wp_unslash( $_POST['cideapps_cf7_mailjet_name_field'] ) ) );
	}
	if ( isset( $_POST['cideapps_cf7_mailjet_phone_field'] ) ) {
		update_option( 'cideapps_cf7_mailjet_phone_field', sanitize_text_field( wp_unslash( $_POST['cideapps_cf7_mailjet_phone_field'] ) ) );
	}
	if ( isset( $_POST['cideapps_cf7_mailjet_service_field'] ) ) {
		update_option( 'cideapps_cf7_mailjet_service_field', sanitize_text_field( wp_unslash( $_POST['cideapps_cf7_mailjet_service_field'] ) ) );
	}
	if ( isset( $_POST['cideapps_cf7_mailjet_message_field'] ) ) {
		update_option( 'cideapps_cf7_mailjet_message_field', sanitize_text_field( wp_unslash( $_POST['cideapps_cf7_mailjet_message_field'] ) ) );
	}
	$service_send_label_value = isset( $_POST['cideapps_cf7_mailjet_service_send_label'] ) && $_POST['cideapps_cf7_mailjet_service_send_label'] === '1' ? 1 : 0;
	update_option( 'cideapps_cf7_mailjet_service_send_label', $service_send_label_value );
	$enable_submission_metadata_value = isset( $_POST['cideapps_cf7_mailjet_enable_submission_metadata'] ) && $_POST['cideapps_cf7_mailjet_enable_submission_metadata'] === '1' ? 1 : 0;
	update_option( 'cideapps_cf7_mailjet_enable_submission_metadata', $enable_submission_metadata_value );
	$dynamic_sources = isset( $_POST['cideapps_cf7_mailjet_dynamic_mappings_source'] ) && is_array( $_POST['cideapps_cf7_mailjet_dynamic_mappings_source'] )
		? array_map( 'wp_unslash', $_POST['cideapps_cf7_mailjet_dynamic_mappings_source'] )
		: array();
	$dynamic_targets = isset( $_POST['cideapps_cf7_mailjet_dynamic_mappings_target'] ) && is_array( $_POST['cideapps_cf7_mailjet_dynamic_mappings_target'] )
		? array_map( 'wp_unslash', $_POST['cideapps_cf7_mailjet_dynamic_mappings_target'] )
		: array();

	$sanitized_lines = array();
	$has_repeatables = ! empty( $dynamic_sources ) || ! empty( $dynamic_targets );

	if ( $has_repeatables ) {
		$max = max( count( $dynamic_sources ), count( $dynamic_targets ) );
		for ( $i = 0; $i < $max; $i++ ) {
			$source = isset( $dynamic_sources[ $i ] ) ? trim( (string) $dynamic_sources[ $i ] ) : '';
			$target = isset( $dynamic_targets[ $i ] ) ? sanitize_key( trim( (string) $dynamic_targets[ $i ] ) ) : '';

			if ( '' === $source || '' === $target ) {
				continue;
			}

			$sanitized_lines[] = $source . ':' . $target;
		}

		update_option( 'cideapps_cf7_mailjet_dynamic_mappings', implode( "\n", $sanitized_lines ) );
	} elseif ( isset( $_POST['cideapps_cf7_mailjet_dynamic_mappings'] ) ) {
		// Backward-compatibility: accept the old textarea format if present.
		$dynamic_mappings_raw = wp_unslash( $_POST['cideapps_cf7_mailjet_dynamic_mappings'] );
		$dynamic_lines        = preg_split( '/\r\n|\r|\n/', (string) $dynamic_mappings_raw );

		foreach ( $dynamic_lines as $dynamic_line ) {
			$dynamic_line = trim( (string) $dynamic_line );
			if ( '' === $dynamic_line ) {
				continue;
			}

			$parts = explode( ':', $dynamic_line, 2 );
			if ( count( $parts ) !== 2 ) {
				continue;
			}

			$source = trim( $parts[0] );
			$target = sanitize_key( trim( $parts[1] ) );

			if ( '' === $source || '' === $target ) {
				continue;
			}

			$sanitized_lines[] = $source . ':' . $target;
		}

		update_option( 'cideapps_cf7_mailjet_dynamic_mappings', implode( "\n", $sanitized_lines ) );
	}

	$enable_attachment_urls_value = isset( $_POST['cideapps_cf7_mailjet_enable_attachment_urls'] ) && $_POST['cideapps_cf7_mailjet_enable_attachment_urls'] === '1' ? 1 : 0;
	update_option( 'cideapps_cf7_mailjet_enable_attachment_urls', $enable_attachment_urls_value );

	$attachment_sources = isset( $_POST['cideapps_cf7_mailjet_attachment_mappings_source'] ) && is_array( $_POST['cideapps_cf7_mailjet_attachment_mappings_source'] )
		? array_map( 'wp_unslash', $_POST['cideapps_cf7_mailjet_attachment_mappings_source'] )
		: array();
	$attachment_targets = isset( $_POST['cideapps_cf7_mailjet_attachment_mappings_target'] ) && is_array( $_POST['cideapps_cf7_mailjet_attachment_mappings_target'] )
		? array_map( 'wp_unslash', $_POST['cideapps_cf7_mailjet_attachment_mappings_target'] )
		: array();

	$attachment_lines      = array();
	$has_attachment_rows   = ! empty( $attachment_sources ) || ! empty( $attachment_targets );

	if ( $has_attachment_rows ) {
		$max_attachments = max( count( $attachment_sources ), count( $attachment_targets ) );
		for ( $i = 0; $i < $max_attachments; $i++ ) {
			$source = isset( $attachment_sources[ $i ] ) ? trim( (string) $attachment_sources[ $i ] ) : '';
			$target = isset( $attachment_targets[ $i ] ) ? sanitize_key( trim( (string) $attachment_targets[ $i ] ) ) : '';

			if ( '' === $source || '' === $target ) {
				continue;
			}

			$attachment_lines[] = $source . ':' . $target;
		}

		update_option( 'cideapps_cf7_mailjet_attachment_mappings', implode( "\n", $attachment_lines ) );
	}

	if ( isset( $_POST['cideapps_cf7_mailjet_owner_notify_to_email'] ) ) {
		update_option( 'cideapps_cf7_mailjet_owner_notify_to_email', sanitize_email( wp_unslash( $_POST['cideapps_cf7_mailjet_owner_notify_to_email'] ) ) );
	}
	$owner_notify_mode = isset( $_POST['cideapps_cf7_mailjet_owner_notify_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['cideapps_cf7_mailjet_owner_notify_mode'] ) ) : 'template';
	if ( ! in_array( $owner_notify_mode, array( 'template', 'html_default' ), true ) ) {
		$owner_notify_mode = 'template';
	}
	update_option( 'cideapps_cf7_mailjet_owner_notify_mode', $owner_notify_mode );
	if ( isset( $_POST['cideapps_cf7_mailjet_owner_notify_template_id'] ) ) {
		update_option( 'cideapps_cf7_mailjet_owner_notify_template_id', intval( $_POST['cideapps_cf7_mailjet_owner_notify_template_id'] ) );
	}
	if ( isset( $_POST['cideapps_cf7_mailjet_owner_notify_subject'] ) ) {
		update_option( 'cideapps_cf7_mailjet_owner_notify_subject', sanitize_text_field( wp_unslash( $_POST['cideapps_cf7_mailjet_owner_notify_subject'] ) ) );
	}

	// Security
	if ( isset( $_POST['cideapps_cf7_mailjet_rate_limit_email_minutes'] ) ) {
		update_option( 'cideapps_cf7_mailjet_rate_limit_email_minutes', intval( $_POST['cideapps_cf7_mailjet_rate_limit_email_minutes'] ) );
	}
	if ( isset( $_POST['cideapps_cf7_mailjet_rate_limit_ip_minutes'] ) ) {
		update_option( 'cideapps_cf7_mailjet_rate_limit_ip_minutes', intval( $_POST['cideapps_cf7_mailjet_rate_limit_ip_minutes'] ) );
	}
	$debug_logs_value = isset( $_POST['cideapps_cf7_mailjet_debug_logs'] ) && $_POST['cideapps_cf7_mailjet_debug_logs'] === '1' ? 1 : 0;
	update_option( 'cideapps_cf7_mailjet_debug_logs', $debug_logs_value );

	$uninstall_delete_uploads_value = isset( $_POST['cideapps_cf7_mailjet_uninstall_delete_uploads'] ) && $_POST['cideapps_cf7_mailjet_uninstall_delete_uploads'] === '1' ? 1 : 0;
	update_option( 'cideapps_cf7_mailjet_uninstall_delete_uploads', $uninstall_delete_uploads_value );

	if ( isset( $_POST['cideapps_cf7_mailjet_attachment_retention_days'] ) ) {
		$retention_days = max( 0, min( 3650, (int) $_POST['cideapps_cf7_mailjet_attachment_retention_days'] ) );
		update_option( Cideapps_Cf7_Mailjet_Upload_Cleanup::OPTION_RETENTION_DAYS, $retention_days );
	}

	require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/includes/class-cideapps-cf7-mailjet-upload-cleanup.php';
	Cideapps_Cf7_Mailjet_Upload_Cleanup::reschedule_cron();

	// Show success message
	$settings_saved = true;
}

// Show success message if settings were saved
if ( isset( $settings_saved ) && $settings_saved ) {
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Configuración guardada correctamente.', 'cideapps-cf7-mailjet' ) . '</p></div>';
}

// Show test result message if test was performed
if ( isset( $test_message ) && isset( $test_notice_type ) ) {
	echo '<div class="notice notice-' . esc_attr( $test_notice_type ) . ' is-dismissible"><p>' . wp_kses_post( $test_message ) . '</p></div>';
}

if ( isset( $_GET['cideapps_cf7_mailjet_notice'] ) ) {
	$notice_code = sanitize_key( wp_unslash( $_GET['cideapps_cf7_mailjet_notice'] ) );
	if ( 'reset_success' === $notice_code ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Formulario restablecido correctamente.', 'cideapps-cf7-mailjet' ) . '</p></div>';
	} elseif ( 'reset_invalid_form' === $notice_code ) {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'No se pudo restablecer: formulario inválido.', 'cideapps-cf7-mailjet' ) . '</p></div>';
	} elseif ( 'reset_invalid_nonce' === $notice_code ) {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'No se pudo restablecer: validación de seguridad fallida.', 'cideapps-cf7-mailjet' ) . '</p></div>';
	} elseif ( 'reset_forbidden' === $notice_code ) {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'No tienes permisos para restablecer formularios.', 'cideapps-cf7-mailjet' ) . '</p></div>';
	}
}

// Get Contact Form 7 forms
$cf7_forms = array();
if ( class_exists( 'WPCF7_ContactForm' ) ) {
	$cf7_forms_query = get_posts(
		array(
			'post_type'      => 'wpcf7_contact_form',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	foreach ( $cf7_forms_query as $form ) {
		$cf7_forms[ $form->ID ] = $form->post_title;
	}
}

$plugin_includes_dir = dirname( dirname( dirname( __FILE__ ) ) ) . '/includes';
require_once $plugin_includes_dir . '/class-cideapps-cf7-mailjet-form-settings.php';
require_once $plugin_includes_dir . '/class-cideapps-cf7-mailjet-cf7-field-selector.php';

// Get current settings
$public_key              = get_option( 'cideapps_cf7_mailjet_public_key', '' );
$private_key             = get_option( 'cideapps_cf7_mailjet_private_key', '' );
$from_email              = get_option( 'cideapps_cf7_mailjet_from_email', '' );
$from_name               = get_option( 'cideapps_cf7_mailjet_from_name', '' );
$enable_autoreply_raw    = get_option( 'cideapps_cf7_mailjet_enable_autoreply', 0 );
$enable_autoreply        = ( $enable_autoreply_raw === 1 || $enable_autoreply_raw === '1' || $enable_autoreply_raw === true );
$template_id             = get_option( 'cideapps_cf7_mailjet_template_id', 0 );
$enable_contact_list_raw = get_option( 'cideapps_cf7_mailjet_enable_contact_list', 0 );
$enable_contact_list     = ( $enable_contact_list_raw === 1 || $enable_contact_list_raw === '1' || $enable_contact_list_raw === true );
$list_id                 = get_option( 'cideapps_cf7_mailjet_list_id', 0 );
$on_existing_contact     = get_option( 'cideapps_cf7_mailjet_on_existing_contact', 'update_properties' );
$on_existing_contact_label = ( 'skip' === $on_existing_contact )
	? __( 'Omitir', 'cideapps-cf7-mailjet' )
	: __( 'Actualizar propiedades', 'cideapps-cf7-mailjet' );
$enabled_form_ids        = get_option( 'cideapps_cf7_mailjet_enabled_form_ids', array() );
$form_mail_modes         = get_option( 'cideapps_cf7_mailjet_form_mail_modes', array() );
if ( ! is_array( $form_mail_modes ) ) {
	$form_mail_modes = array();
}
$email_field             = get_option( 'cideapps_cf7_mailjet_email_field', 'your-email' );
$name_field              = get_option( 'cideapps_cf7_mailjet_name_field', 'your-name' );
$phone_field             = get_option( 'cideapps_cf7_mailjet_phone_field', 'your-phone' );
$service_field           = get_option( 'cideapps_cf7_mailjet_service_field', 'service' );
$message_field           = get_option( 'cideapps_cf7_mailjet_message_field', 'your-message' );
$cf7_mapping_fields      = Cideapps_Cf7_Mailjet_Cf7_Field_Selector::collect_fields_for_admin(
	array_map( 'intval', (array) $enabled_form_ids ),
	array_map( 'intval', array_keys( $cf7_forms ) )
);
$cf7_mapping_source_ids  = Cideapps_Cf7_Mailjet_Cf7_Field_Selector::resolve_source_form_ids(
	array_map( 'intval', (array) $enabled_form_ids ),
	array_map( 'intval', array_keys( $cf7_forms ) )
);
$cf7_use_field_selectors = Cideapps_Cf7_Mailjet_Cf7_Field_Selector::is_cf7_available();
$service_send_label_raw  = get_option( 'cideapps_cf7_mailjet_service_send_label', 0 );
$service_send_label      = ( $service_send_label_raw === 1 || $service_send_label_raw === '1' || $service_send_label_raw === true );
$enable_submission_metadata_raw = get_option( 'cideapps_cf7_mailjet_enable_submission_metadata', 0 );
$enable_submission_metadata     = ( $enable_submission_metadata_raw === 1 || $enable_submission_metadata_raw === '1' || $enable_submission_metadata_raw === true );
$dynamic_mappings             = get_option( 'cideapps_cf7_mailjet_dynamic_mappings', '' );
$dynamic_mappings_lines       = is_string( $dynamic_mappings ) ? preg_split( '/\r\n|\r|\n/', $dynamic_mappings ) : array();
$dynamic_mappings_rows        = array();
foreach ( (array) $dynamic_mappings_lines as $dynamic_mappings_line ) {
	$dynamic_mappings_line = trim( (string) $dynamic_mappings_line );
	if ( '' === $dynamic_mappings_line ) {
		continue;
	}

	$parts = explode( ':', $dynamic_mappings_line, 2 );
	if ( count( $parts ) !== 2 ) {
		continue;
	}

	$source = trim( (string) $parts[0] );
	$target = sanitize_key( trim( (string) $parts[1] ) );

	if ( '' === $source || '' === $target ) {
		continue;
	}

	$dynamic_mappings_rows[] = array(
		'source' => $source,
		'target' => $target,
	);
}
if ( empty( $dynamic_mappings_rows ) ) {
	$dynamic_mappings_rows[] = array(
		'source' => '',
		'target' => '',
	);
}

$enable_attachment_urls_raw = get_option( 'cideapps_cf7_mailjet_enable_attachment_urls', 0 );
$enable_attachment_urls     = ( $enable_attachment_urls_raw === 1 || $enable_attachment_urls_raw === '1' || $enable_attachment_urls_raw === true );
$attachment_mappings        = get_option( 'cideapps_cf7_mailjet_attachment_mappings', '' );
$attachment_mappings_lines  = is_string( $attachment_mappings ) ? preg_split( '/\r\n|\r|\n/', $attachment_mappings ) : array();
$attachment_mappings_rows   = array();
foreach ( (array) $attachment_mappings_lines as $attachment_mappings_line ) {
	$attachment_mappings_line = trim( (string) $attachment_mappings_line );
	if ( '' === $attachment_mappings_line ) {
		continue;
	}

	$parts = explode( ':', $attachment_mappings_line, 2 );
	if ( count( $parts ) !== 2 ) {
		continue;
	}

	$source = trim( (string) $parts[0] );
	$target = sanitize_key( trim( (string) $parts[1] ) );

	if ( '' === $source || '' === $target ) {
		continue;
	}

	$attachment_mappings_rows[] = array(
		'source' => $source,
		'target' => $target,
	);
}
if ( empty( $attachment_mappings_rows ) ) {
	$attachment_mappings_rows[] = array(
		'source' => '',
		'target' => '',
	);
}

$owner_notify_to_email    = get_option( 'cideapps_cf7_mailjet_owner_notify_to_email', '' );
$owner_notify_mode        = get_option( 'cideapps_cf7_mailjet_owner_notify_mode', 'template' );
if ( ! in_array( $owner_notify_mode, array( 'template', 'html_default' ), true ) ) {
	$owner_notify_mode = 'template';
}
$owner_notify_template_id = (int) get_option( 'cideapps_cf7_mailjet_owner_notify_template_id', 0 );
$owner_notify_subject     = get_option( 'cideapps_cf7_mailjet_owner_notify_subject', __( 'Nuevo lead desde formulario web', 'cideapps-cf7-mailjet' ) );
$rate_limit_email_minutes = get_option( 'cideapps_cf7_mailjet_rate_limit_email_minutes', 10 );
$rate_limit_ip_minutes    = get_option( 'cideapps_cf7_mailjet_rate_limit_ip_minutes', 10 );
$debug_logs_raw           = get_option( 'cideapps_cf7_mailjet_debug_logs', 0 );
$debug_logs               = ( $debug_logs_raw === 1 || $debug_logs_raw === '1' || $debug_logs_raw === true );
$uninstall_delete_uploads_raw = get_option( 'cideapps_cf7_mailjet_uninstall_delete_uploads', 0 );
$uninstall_delete_uploads     = ( $uninstall_delete_uploads_raw === 1 || $uninstall_delete_uploads_raw === '1' || $uninstall_delete_uploads_raw === true );
if ( ! class_exists( 'Cideapps_Cf7_Mailjet_Upload_Cleanup' ) ) {
	require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/includes/class-cideapps-cf7-mailjet-upload-cleanup.php';
}
$attachment_retention_days = Cideapps_Cf7_Mailjet_Upload_Cleanup::get_retention_days();

// Admin navigation (v1.4 Epic B1) — see docs/UX-NAVIGATION-BLUEPRINT-v1.4.md.
$allowed_admin_tabs = array( 'mailjet', 'forms', 'security' );
$active_admin_tab   = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'forms';
if ( ! in_array( $active_admin_tab, $allowed_admin_tabs, true ) ) {
	$active_admin_tab = 'forms';
}
$form_id_view           = isset( $_GET['form_id'] ) ? (int) $_GET['form_id'] : 0;
$show_form_detail       = $form_id_view > 0 && isset( $cf7_forms[ $form_id_view ] );
$form_id_view_invalid   = $form_id_view > 0 && ! $show_form_detail;
$admin_page_base        = add_query_arg( 'page', 'cideapps-cf7-mailjet', admin_url( 'options-general.php' ) );
$admin_form_action      = add_query_arg( 'tab', $active_admin_tab, $admin_page_base );
if ( $show_form_detail ) {
	$admin_form_action = add_query_arg( 'form_id', $form_id_view, $admin_form_action );
}
$cideapps_cf7_mailjet_admin_tab_url = static function( $tab, $form_id = 0 ) use ( $admin_page_base ) {
	$url = add_query_arg( 'tab', $tab, $admin_page_base );
	if ( $form_id > 0 && 'forms' === $tab ) {
		$url = add_query_arg( 'form_id', (int) $form_id, $url );
	}
	return $url;
};
$cideapps_cf7_mailjet_tab_panel_style = static function( $tab ) use ( $active_admin_tab ) {
	return $tab === $active_admin_tab ? '' : 'display:none;';
};
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<form method="post" action="<?php echo esc_url( $admin_form_action ); ?>">
		<?php wp_nonce_field( 'cideapps_cf7_mailjet_settings', 'cideapps_cf7_mailjet_settings_nonce' ); ?>

		<h2 class="nav-tab-wrapper">
			<a href="<?php echo esc_url( call_user_func( $cideapps_cf7_mailjet_admin_tab_url, 'mailjet' ) ); ?>" class="nav-tab <?php echo 'mailjet' === $active_admin_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Mailjet', 'cideapps-cf7-mailjet' ); ?></a>
			<a href="<?php echo esc_url( call_user_func( $cideapps_cf7_mailjet_admin_tab_url, 'forms' ) ); ?>" class="nav-tab <?php echo 'forms' === $active_admin_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Formularios', 'cideapps-cf7-mailjet' ); ?></a>
			<a href="<?php echo esc_url( call_user_func( $cideapps_cf7_mailjet_admin_tab_url, 'security' ) ); ?>" class="nav-tab <?php echo 'security' === $active_admin_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Seguridad', 'cideapps-cf7-mailjet' ); ?></a>
		</h2>

		<div id="mailjet-settings" class="tab-content cideapps-cf7-tab-mailjet" style="<?php echo esc_attr( call_user_func( $cideapps_cf7_mailjet_tab_panel_style, 'mailjet' ) ); ?>">
			<h2><?php esc_html_e( 'Mailjet', 'cideapps-cf7-mailjet' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Conexión con tu cuenta Mailjet y remitente por defecto. Los formularios, listas y plantillas se configuran en el tab Formularios.', 'cideapps-cf7-mailjet' ); ?>
			</p>

			<?php if ( empty( $public_key ) || empty( $private_key ) ) : ?>
				<div class="notice notice-warning inline">
					<p>
						<?php esc_html_e( 'Introduce la API Key y la Secret Key para habilitar envíos por Mailjet.', 'cideapps-cf7-mailjet' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<h3 class="title"><?php esc_html_e( 'Credenciales', 'cideapps-cf7-mailjet' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_public_key"><?php esc_html_e( 'API Key', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<input type="text" id="cideapps_cf7_mailjet_public_key" name="cideapps_cf7_mailjet_public_key" value="<?php echo esc_attr( $public_key ); ?>" class="regular-text" autocomplete="off" />
						<p class="description"><?php esc_html_e( 'Clave pública de la API (Mailjet → Account Settings → API Keys).', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_private_key"><?php esc_html_e( 'Secret Key', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<input type="password" id="cideapps_cf7_mailjet_private_key" name="cideapps_cf7_mailjet_private_key" value="<?php echo esc_attr( $private_key ); ?>" class="regular-text" autocomplete="off" />
						<p class="description"><?php esc_html_e( 'Clave secreta de la API. No la compartas ni la expongas en el front-end.', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
			</table>

			<h3 class="title"><?php esc_html_e( 'Remitente', 'cideapps-cf7-mailjet' ); ?></h3>
			<p class="description cideapps-cf7-mailjet-sender-intro">
				<?php esc_html_e( 'Remitente por defecto para autorespuestas al usuario y para correos enviados por Mailjet API (por ejemplo notificación interna en modo Mailjet API). Debe ser un sender verificado en Mailjet.', 'cideapps-cf7-mailjet' ); ?>
			</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_from_email"><?php esc_html_e( 'From Email', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<input type="email" id="cideapps_cf7_mailjet_from_email" name="cideapps_cf7_mailjet_from_email" value="<?php echo esc_attr( $from_email ); ?>" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_from_name"><?php esc_html_e( 'From Name', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<input type="text" id="cideapps_cf7_mailjet_from_name" name="cideapps_cf7_mailjet_from_name" value="<?php echo esc_attr( $from_name ); ?>" class="regular-text" />
					</td>
				</tr>
			</table>

			<p class="description cideapps-cf7-mailjet-list-test-hint">
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: %s: link to Forms tab global list settings. */
						__( 'Para guardar contactos en una lista o <strong>probar la conexión</strong> con un contacto de prueba, abre %s.', 'cideapps-cf7-mailjet' ),
						'<a href="' . esc_url( call_user_func( $cideapps_cf7_mailjet_admin_tab_url, 'forms' ) . '#cideapps-cf7-global-site-settings' ) . '">' . esc_html__( 'Formularios → Configuración global del sitio → Lista Mailjet', 'cideapps-cf7-mailjet' ) . '</a>'
					)
				);
				?>
			</p>
		</div>

		<div id="forms-settings" class="tab-content" style="<?php echo esc_attr( call_user_func( $cideapps_cf7_mailjet_tab_panel_style, 'forms' ) ); ?>">
			<?php if ( $form_id_view_invalid ) : ?>
				<div class="notice notice-warning inline"><p><?php esc_html_e( 'El formulario solicitado no existe. Se muestra la lista de formularios.', 'cideapps-cf7-mailjet' ); ?></p></div>
			<?php endif; ?>

			<div id="form-detail" class="cideapps-cf7-admin-form-detail" style="<?php echo $show_form_detail ? '' : 'display:none;'; ?>">
				<?php if ( $show_form_detail ) : ?>
					<?php
					$form_detail_enabled = in_array( (int) $form_id_view, array_map( 'intval', (array) $enabled_form_ids ), true );
					$form_detail_mode    = isset( $form_mail_modes[ $form_id_view ] ) ? $form_mail_modes[ $form_id_view ] : 'cf7_mail';
					if ( ! in_array( $form_detail_mode, array( 'cf7_mail', 'mailjet_only' ), true ) ) {
						$form_detail_mode = 'cf7_mail';
					}
					$form_detail_status_label  = $form_detail_enabled ? __( 'Activo', 'cideapps-cf7-mailjet' ) : __( 'Inactivo', 'cideapps-cf7-mailjet' );
					$form_detail_channel_label = 'mailjet_only' === $form_detail_mode ? __( 'Mailjet API', 'cideapps-cf7-mailjet' ) : __( 'Email nativo de Contact Form 7', 'cideapps-cf7-mailjet' );
					$dynamic_mappings_count    = 0;
					foreach ( $dynamic_mappings_rows as $dynamic_row ) {
						if ( ! empty( $dynamic_row['source'] ) || ! empty( $dynamic_row['target'] ) ) {
							$dynamic_mappings_count++;
						}
					}
					$attachment_mappings_count = 0;
					foreach ( $attachment_mappings_rows as $attachment_row ) {
						if ( ! empty( $attachment_row['source'] ) || ! empty( $attachment_row['target'] ) ) {
							$attachment_mappings_count++;
						}
					}
					$detail_uses_global_variables = Cideapps_Cf7_Mailjet_Form_Settings::uses_global_field_mappings( $form_id_view );
					?>
					<div class="cideapps-cf7-detail-header">
						<p class="cideapps-cf7-admin-back">
							<a href="<?php echo esc_url( call_user_func( $cideapps_cf7_mailjet_admin_tab_url, 'forms' ) ); ?>">&larr; <?php esc_html_e( 'Volver a formularios', 'cideapps-cf7-mailjet' ); ?></a>
						</p>
						<h2 class="cideapps-cf7-detail-title">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: CF7 form title. */
									__( 'Formulario: %s', 'cideapps-cf7-mailjet' ),
									$cf7_forms[ $form_id_view ]
								)
							);
							?>
						</h2>
						<p class="description cideapps-cf7-detail-id"><?php echo esc_html( sprintf( __( 'ID: %d', 'cideapps-cf7-mailjet' ), (int) $form_id_view ) ); ?></p>
						<div class="cideapps-cf7-detail-summary">
							<span class="cideapps-cf7-summary-pill"><?php echo esc_html( sprintf( __( 'Estado: %s', 'cideapps-cf7-mailjet' ), $form_detail_status_label ) ); ?></span>
							<span class="cideapps-cf7-summary-pill"><?php echo esc_html( sprintf( __( 'Canal: %s', 'cideapps-cf7-mailjet' ), $form_detail_channel_label ) ); ?></span>
						</div>
						<p class="description"><?php esc_html_e( 'Shell visual de edición por formulario. En esta fase no se agregan controles nuevos de persistencia.', 'cideapps-cf7-mailjet' ); ?></p>
						<div class="cideapps-cf7-detail-actions cideapps-cf7-detail-actions-top">
							<a href="<?php echo esc_url( call_user_func( $cideapps_cf7_mailjet_admin_tab_url, 'forms' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Cancelar', 'cideapps-cf7-mailjet' ); ?></a>
							<?php submit_button( __( 'Guardar Configuración', 'cideapps-cf7-mailjet' ), 'primary', 'cideapps_cf7_mailjet_settings_submit', false ); ?>
						</div>
					</div>

					<div class="cideapps-cf7-detail-card">
						<h3 class="title"><?php esc_html_e( 'General', 'cideapps-cf7-mailjet' ); ?></h3>
						<p class="description"><?php esc_html_e( 'Controla la integración y el canal de este formulario. Solo afecta al formulario abierto en detalle.', 'cideapps-cf7-mailjet' ); ?></p>
						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Integración', 'cideapps-cf7-mailjet' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="cideapps_cf7_mailjet_enabled_form_ids[]" value="<?php echo esc_attr( (int) $form_id_view ); ?>" <?php checked( $form_detail_enabled ); ?> />
										<?php esc_html_e( 'Activa para este formulario', 'cideapps-cf7-mailjet' ); ?>
									</label>
									<p class="description"><?php echo esc_html( sprintf( __( 'Estado actual: %s', 'cideapps-cf7-mailjet' ), $form_detail_status_label ) ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="cideapps_cf7_mailjet_detail_form_mail_mode"><?php esc_html_e( 'Canal de notificación interna', 'cideapps-cf7-mailjet' ); ?></label>
								</th>
								<td>
									<select id="cideapps_cf7_mailjet_detail_form_mail_mode" name="cideapps_cf7_mailjet_form_mail_modes[<?php echo esc_attr( (int) $form_id_view ); ?>]">
										<option value="cf7_mail" <?php selected( $form_detail_mode, 'cf7_mail' ); ?>><?php esc_html_e( 'Email nativo de Contact Form 7', 'cideapps-cf7-mailjet' ); ?></option>
										<option value="mailjet_only" <?php selected( $form_detail_mode, 'mailjet_only' ); ?>><?php esc_html_e( 'Mailjet API', 'cideapps-cf7-mailjet' ); ?></option>
									</select>
									<p class="description"><?php echo esc_html( sprintf( __( 'Canal actual: %s', 'cideapps-cf7-mailjet' ), $form_detail_channel_label ) ); ?></p>
								</td>
							</tr>
						</table>
					</div>

					<div class="cideapps-cf7-detail-card">
						<h3 class="title"><?php esc_html_e( 'Variables', 'cideapps-cf7-mailjet' ); ?></h3>
						<p class="description"><?php esc_html_e( 'Define los mappings de variables para este formulario o usa la configuración global.', 'cideapps-cf7-mailjet' ); ?></p>
						<p class="cideapps-cf7-detail-toggle">
							<label for="cideapps_cf7_mailjet_detail_use_global_variables">
								<input type="hidden" name="cideapps_cf7_mailjet_form_settings[<?php echo esc_attr( (int) $form_id_view ); ?>][use_global_field_mappings]" value="0" />
								<input type="checkbox" id="cideapps_cf7_mailjet_detail_use_global_variables" name="cideapps_cf7_mailjet_form_settings[<?php echo esc_attr( (int) $form_id_view ); ?>][use_global_field_mappings]" value="1" <?php checked( $detail_uses_global_variables, true ); ?> />
								<?php esc_html_e( 'Usar configuración global de variables', 'cideapps-cf7-mailjet' ); ?>
							</label>
						</p>
						<?php if ( $detail_uses_global_variables ) : ?>
							<p class="description cideapps-cf7-detail-inherit-note"><?php esc_html_e( 'Este formulario hereda los mappings globales.', 'cideapps-cf7-mailjet' ); ?></p>
							<p class="description"><?php esc_html_e( 'Desactiva esta opción para editar mappings personalizados de Email, Nombre, Teléfono, Servicio y Mensaje.', 'cideapps-cf7-mailjet' ); ?></p>
						<?php elseif ( $cf7_use_field_selectors ) : ?>
							<table class="form-table">
								<tr>
									<th scope="row">
										<label for="<?php echo esc_attr( sprintf( '%s_%d_email_field', Cideapps_Cf7_Mailjet_Form_Settings::OPTION_NAME, (int) $form_id_view ) ); ?>"><?php esc_html_e( 'Email', 'cideapps-cf7-mailjet' ); ?></label>
									</th>
									<td><?php Cideapps_Cf7_Mailjet_Cf7_Field_Selector::render_form_mapping_select( $form_id_view, 'email_field', Cideapps_Cf7_Mailjet_Form_Settings::get_field_mapping( $form_id_view, 'email_field' ) ); ?></td>
								</tr>
								<tr>
									<th scope="row">
										<label for="<?php echo esc_attr( sprintf( '%s_%d_name_field', Cideapps_Cf7_Mailjet_Form_Settings::OPTION_NAME, (int) $form_id_view ) ); ?>"><?php esc_html_e( 'Nombre', 'cideapps-cf7-mailjet' ); ?></label>
									</th>
									<td><?php Cideapps_Cf7_Mailjet_Cf7_Field_Selector::render_form_mapping_select( $form_id_view, 'name_field', Cideapps_Cf7_Mailjet_Form_Settings::get_field_mapping( $form_id_view, 'name_field' ) ); ?></td>
								</tr>
								<tr>
									<th scope="row">
										<label for="<?php echo esc_attr( sprintf( '%s_%d_phone_field', Cideapps_Cf7_Mailjet_Form_Settings::OPTION_NAME, (int) $form_id_view ) ); ?>"><?php esc_html_e( 'Teléfono', 'cideapps-cf7-mailjet' ); ?></label>
									</th>
									<td><?php Cideapps_Cf7_Mailjet_Cf7_Field_Selector::render_form_mapping_select( $form_id_view, 'phone_field', Cideapps_Cf7_Mailjet_Form_Settings::get_field_mapping( $form_id_view, 'phone_field' ) ); ?></td>
								</tr>
								<tr>
									<th scope="row">
										<label for="<?php echo esc_attr( sprintf( '%s_%d_service_field', Cideapps_Cf7_Mailjet_Form_Settings::OPTION_NAME, (int) $form_id_view ) ); ?>"><?php esc_html_e( 'Servicio', 'cideapps-cf7-mailjet' ); ?></label>
									</th>
									<td><?php Cideapps_Cf7_Mailjet_Cf7_Field_Selector::render_form_mapping_select( $form_id_view, 'service_field', Cideapps_Cf7_Mailjet_Form_Settings::get_field_mapping( $form_id_view, 'service_field' ) ); ?></td>
								</tr>
								<tr>
									<th scope="row">
										<label for="<?php echo esc_attr( sprintf( '%s_%d_message_field', Cideapps_Cf7_Mailjet_Form_Settings::OPTION_NAME, (int) $form_id_view ) ); ?>"><?php esc_html_e( 'Mensaje', 'cideapps-cf7-mailjet' ); ?></label>
									</th>
									<td><?php Cideapps_Cf7_Mailjet_Cf7_Field_Selector::render_form_mapping_select( $form_id_view, 'message_field', Cideapps_Cf7_Mailjet_Form_Settings::get_field_mapping( $form_id_view, 'message_field' ) ); ?></td>
								</tr>
							</table>
							<p class="description"><?php esc_html_e( 'Si un tag guardado ya no existe en este formulario, se conserva como opción seleccionada.', 'cideapps-cf7-mailjet' ); ?></p>
						<?php else : ?>
							<p class="description"><?php esc_html_e( 'Contact Form 7 no está disponible; no se pueden listar campos para mappings personalizados.', 'cideapps-cf7-mailjet' ); ?></p>
						<?php endif; ?>
					</div>

					<div class="cideapps-cf7-detail-card">
						<h3 class="title"><?php esc_html_e( 'Notificación interna', 'cideapps-cf7-mailjet' ); ?></h3>
						<div class="notice notice-info inline cideapps-cf7-detail-notice">
							<p><?php esc_html_e( 'Configuración global en v1.4.0.', 'cideapps-cf7-mailjet' ); ?></p>
							<p><?php esc_html_e( 'Actualmente esta configuración es global y aplica a todos los formularios del sitio.', 'cideapps-cf7-mailjet' ); ?></p>
							<p><?php esc_html_e( 'Los cambios realizados en esta sección afectarán a todos los formularios configurados en el plugin.', 'cideapps-cf7-mailjet' ); ?></p>
						</div>
						<?php if ( 'mailjet_only' === $form_detail_mode ) : ?>
							<p class="description"><?php esc_html_e( 'Este canal usa Mailjet API para la notificación interna.', 'cideapps-cf7-mailjet' ); ?></p>
						<?php else : ?>
							<p class="description"><?php esc_html_e( 'La notificación principal al negocio se gestiona desde la pestaña Mail de Contact Form 7.', 'cideapps-cf7-mailjet' ); ?></p>
						<?php endif; ?>
						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="cideapps_cf7_mailjet_owner_notify_to_email"><?php esc_html_e( 'Email destino negocio', 'cideapps-cf7-mailjet' ); ?></label>
								</th>
								<td>
									<input type="email" id="cideapps_cf7_mailjet_owner_notify_to_email" name="cideapps_cf7_mailjet_owner_notify_to_email" value="<?php echo esc_attr( $owner_notify_to_email ); ?>" class="regular-text" />
									<p class="description"><?php esc_html_e( 'Correo donde recibirás los datos del lead. (Almacenamiento global en v1.4.0; per-form en v1.4.1+.)', 'cideapps-cf7-mailjet' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="cideapps_cf7_mailjet_owner_notify_mode"><?php esc_html_e( 'Modo de notificación negocio', 'cideapps-cf7-mailjet' ); ?></label>
								</th>
								<td>
									<select id="cideapps_cf7_mailjet_owner_notify_mode" name="cideapps_cf7_mailjet_owner_notify_mode">
										<option value="template" <?php selected( $owner_notify_mode, 'template' ); ?>><?php esc_html_e( 'Template ID de Mailjet', 'cideapps-cf7-mailjet' ); ?></option>
										<option value="html_default" <?php selected( $owner_notify_mode, 'html_default' ); ?>><?php esc_html_e( 'HTML por defecto del plugin', 'cideapps-cf7-mailjet' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="cideapps_cf7_mailjet_owner_notify_template_id"><?php esc_html_e( 'Template ID negocio', 'cideapps-cf7-mailjet' ); ?></label>
								</th>
								<td>
									<input type="number" id="cideapps_cf7_mailjet_owner_notify_template_id" name="cideapps_cf7_mailjet_owner_notify_template_id" value="<?php echo esc_attr( $owner_notify_template_id ); ?>" class="regular-text" />
									<p class="description"><?php esc_html_e( 'Se usa cuando el modo es "Template ID de Mailjet".', 'cideapps-cf7-mailjet' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="cideapps_cf7_mailjet_owner_notify_subject"><?php esc_html_e( 'Asunto (HTML por defecto)', 'cideapps-cf7-mailjet' ); ?></label>
								</th>
								<td>
									<input type="text" id="cideapps_cf7_mailjet_owner_notify_subject" name="cideapps_cf7_mailjet_owner_notify_subject" value="<?php echo esc_attr( $owner_notify_subject ); ?>" class="regular-text" />
									<p class="description"><?php esc_html_e( 'Se usa cuando el modo es "HTML por defecto del plugin".', 'cideapps-cf7-mailjet' ); ?></p>
								</td>
							</tr>
						</table>
					</div>

					<?php
					$cideapps_cf7_global_autoreply_url = call_user_func( $cideapps_cf7_mailjet_admin_tab_url, 'forms' ) . '#cideapps-cf7-global-autoreply';
					$cideapps_cf7_global_list_url      = call_user_func( $cideapps_cf7_mailjet_admin_tab_url, 'forms' ) . '#cideapps-cf7-global-list-mailjet';
					?>
					<div class="cideapps-cf7-detail-card">
						<h3 class="title"><?php esc_html_e( 'Autorespuesta', 'cideapps-cf7-mailjet' ); ?></h3>
						<div class="notice notice-info inline cideapps-cf7-detail-notice">
							<p><?php esc_html_e( 'Configuración global en v1.4.0.', 'cideapps-cf7-mailjet' ); ?></p>
							<p><?php esc_html_e( 'Actualmente esta configuración es global y aplica a todos los formularios del sitio.', 'cideapps-cf7-mailjet' ); ?></p>
						</div>
						<p class="description"><?php esc_html_e( 'Se envía al email del visitante cuando el formulario se procesa correctamente.', 'cideapps-cf7-mailjet' ); ?></p>
						<table class="form-table cideapps-cf7-detail-readonly-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Estado', 'cideapps-cf7-mailjet' ); ?></th>
								<td>
									<span class="cideapps-cf7-detail-readonly-value">
										<?php
										echo esc_html(
											$enable_autoreply
												? __( 'Activada', 'cideapps-cf7-mailjet' )
												: __( 'Desactivada', 'cideapps-cf7-mailjet' )
										);
										?>
									</span>
								</td>
							</tr>
							<?php if ( $enable_autoreply ) : ?>
								<tr>
									<th scope="row"><?php esc_html_e( 'Template ID', 'cideapps-cf7-mailjet' ); ?></th>
									<td>
										<span class="cideapps-cf7-detail-readonly-value"><?php echo esc_html( (string) (int) $template_id ); ?></span>
									</td>
								</tr>
							<?php endif; ?>
						</table>
						<p class="cideapps-cf7-detail-global-link">
							<a href="<?php echo esc_url( $cideapps_cf7_global_autoreply_url ); ?>"><?php esc_html_e( 'Editar en configuración global del sitio', 'cideapps-cf7-mailjet' ); ?></a>
						</p>
					</div>

					<div class="cideapps-cf7-detail-card">
						<h3 class="title"><?php esc_html_e( 'Lista', 'cideapps-cf7-mailjet' ); ?></h3>
						<div class="notice notice-info inline cideapps-cf7-detail-notice">
							<p><?php esc_html_e( 'Configuración global en v1.4.0.', 'cideapps-cf7-mailjet' ); ?></p>
							<p><?php esc_html_e( 'Actualmente esta configuración es global y aplica a todos los formularios del sitio.', 'cideapps-cf7-mailjet' ); ?></p>
						</div>
						<p class="description"><?php esc_html_e( 'El contacto se guarda en Mailjet según la lista y la política configuradas.', 'cideapps-cf7-mailjet' ); ?></p>
						<table class="form-table cideapps-cf7-detail-readonly-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Estado', 'cideapps-cf7-mailjet' ); ?></th>
								<td>
									<span class="cideapps-cf7-detail-readonly-value">
										<?php
										echo esc_html(
											$enable_contact_list
												? __( 'Activada', 'cideapps-cf7-mailjet' )
												: __( 'Desactivada', 'cideapps-cf7-mailjet' )
										);
										?>
									</span>
								</td>
							</tr>
							<?php if ( $enable_contact_list ) : ?>
								<tr>
									<th scope="row"><?php esc_html_e( 'List ID', 'cideapps-cf7-mailjet' ); ?></th>
									<td>
										<span class="cideapps-cf7-detail-readonly-value"><?php echo esc_html( (string) (int) $list_id ); ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Si el contacto ya existe', 'cideapps-cf7-mailjet' ); ?></th>
									<td>
										<span class="cideapps-cf7-detail-readonly-value"><?php echo esc_html( $on_existing_contact_label ); ?></span>
									</td>
								</tr>
							<?php endif; ?>
						</table>
						<p class="description"><?php esc_html_e( 'La prueba de conexión y alta de contacto de prueba está en la configuración global del sitio.', 'cideapps-cf7-mailjet' ); ?></p>
						<p class="cideapps-cf7-detail-global-link">
							<a href="<?php echo esc_url( $cideapps_cf7_global_list_url ); ?>"><?php esc_html_e( 'Editar en configuración global del sitio', 'cideapps-cf7-mailjet' ); ?></a>
						</p>
					</div>

					<div class="cideapps-cf7-detail-card">
						<h3 class="title"><?php esc_html_e( 'Metadata', 'cideapps-cf7-mailjet' ); ?></h3>
						<p class="description cideapps-cf7-detail-badge"><?php esc_html_e( 'Global (solo lectura en detalle)', 'cideapps-cf7-mailjet' ); ?></p>
						<p class="description"><?php echo esc_html( $enable_submission_metadata ? __( 'Metadata CF7 activa a nivel global.', 'cideapps-cf7-mailjet' ) : __( 'Metadata CF7 desactivada a nivel global.', 'cideapps-cf7-mailjet' ) ); ?></p>
						<p class="description"><?php echo esc_html( sprintf( __( 'Mappings dinámicos configurados: %d', 'cideapps-cf7-mailjet' ), (int) $dynamic_mappings_count ) ); ?></p>
						<p class="description"><a href="<?php echo esc_url( call_user_func( $cideapps_cf7_mailjet_admin_tab_url, 'forms' ) ); ?>#cideapps-cf7-global-site-settings"><?php esc_html_e( 'Ver configuración global del sitio', 'cideapps-cf7-mailjet' ); ?></a></p>
					</div>

					<div class="cideapps-cf7-detail-card">
						<h3 class="title"><?php esc_html_e( 'Adjuntos', 'cideapps-cf7-mailjet' ); ?></h3>
						<p class="description cideapps-cf7-detail-badge"><?php esc_html_e( 'Global (solo lectura en detalle)', 'cideapps-cf7-mailjet' ); ?></p>
						<p class="description"><?php echo esc_html( $enable_attachment_urls ? __( 'Copiado de adjuntos y URLs Mailjet activo a nivel global.', 'cideapps-cf7-mailjet' ) : __( 'Copiado de adjuntos y URLs Mailjet desactivado a nivel global.', 'cideapps-cf7-mailjet' ) ); ?></p>
						<p class="description"><?php echo esc_html( sprintf( __( 'Mappings de adjuntos configurados: %d', 'cideapps-cf7-mailjet' ), (int) $attachment_mappings_count ) ); ?></p>
						<p class="description"><?php echo esc_html( sprintf( __( 'Retención actual: %d días (configurable en Seguridad).', 'cideapps-cf7-mailjet' ), (int) $attachment_retention_days ) ); ?></p>
						<p class="description"><a href="<?php echo esc_url( call_user_func( $cideapps_cf7_mailjet_admin_tab_url, 'security' ) ); ?>"><?php esc_html_e( 'Ir a Seguridad', 'cideapps-cf7-mailjet' ); ?></a></p>
					</div>

					<div class="cideapps-cf7-detail-actions cideapps-cf7-detail-actions-bottom">
						<a href="<?php echo esc_url( call_user_func( $cideapps_cf7_mailjet_admin_tab_url, 'forms' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Cancelar', 'cideapps-cf7-mailjet' ); ?></a>
						<?php submit_button( __( 'Guardar Configuración', 'cideapps-cf7-mailjet' ), 'primary', 'cideapps_cf7_mailjet_settings_submit', false ); ?>
					</div>
				<?php endif; ?>
			</div>

			<div id="forms-list" class="cideapps-cf7-admin-forms-list" style="<?php echo $show_form_detail ? 'display:none;' : ''; ?>">
				<h2><?php esc_html_e( 'Formularios', 'cideapps-cf7-mailjet' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Formularios de Contact Form 7 detectados en este sitio. Usa Editar para configurar cada uno.', 'cideapps-cf7-mailjet' ); ?></p>

				<details id="cideapps-cf7-global-site-settings" class="cideapps-cf7-global-site-settings">
					<summary><?php esc_html_e( 'Configuración global del sitio', 'cideapps-cf7-mailjet' ); ?></summary>
					<p class="description"><?php esc_html_e( 'Valores que aplican a todo el sitio. La notificación interna al negocio se configura por formulario desde Editar en la tabla.', 'cideapps-cf7-mailjet' ); ?></p>

					<h3 id="cideapps-cf7-global-autoreply" class="title"><?php esc_html_e( 'Autorespuesta', 'cideapps-cf7-mailjet' ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Habilitar Autorespuesta', 'cideapps-cf7-mailjet' ); ?></th>
					<td>
						<label>
							<input type="checkbox" id="cideapps_cf7_mailjet_enable_autoreply" name="cideapps_cf7_mailjet_enable_autoreply" value="1" <?php checked( $enable_autoreply, true ); ?> />
							<?php esc_html_e( 'Enviar autorespuesta cuando se envíe un formulario', 'cideapps-cf7-mailjet' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_template_id"><?php esc_html_e( 'Template ID', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<input type="number" id="cideapps_cf7_mailjet_template_id" name="cideapps_cf7_mailjet_template_id" value="<?php echo esc_attr( $template_id ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'ID del template de Mailjet para la autorespuesta', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
			</table>

					<h3 id="cideapps-cf7-global-list-mailjet" class="title"><?php esc_html_e( 'Lista Mailjet', 'cideapps-cf7-mailjet' ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Habilitar Lista de Contactos', 'cideapps-cf7-mailjet' ); ?></th>
					<td>
						<label>
							<input type="checkbox" id="cideapps_cf7_mailjet_enable_contact_list" name="cideapps_cf7_mailjet_enable_contact_list" value="1" <?php checked( $enable_contact_list, true ); ?> />
							<?php esc_html_e( 'Guardar contactos en lista de Mailjet', 'cideapps-cf7-mailjet' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_list_id"><?php esc_html_e( 'List ID', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<input type="number" id="cideapps_cf7_mailjet_list_id" name="cideapps_cf7_mailjet_list_id" value="<?php echo esc_attr( $list_id ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'ID de la lista de Mailjet donde se guardarán los contactos', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_on_existing_contact"><?php esc_html_e( 'Si el contacto ya existe', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<select id="cideapps_cf7_mailjet_on_existing_contact" name="cideapps_cf7_mailjet_on_existing_contact">
							<option value="update_properties" <?php selected( $on_existing_contact, 'update_properties' ); ?>><?php esc_html_e( 'Actualizar propiedades', 'cideapps-cf7-mailjet' ); ?></option>
							<option value="skip" <?php selected( $on_existing_contact, 'skip' ); ?>><?php esc_html_e( 'Omitir', 'cideapps-cf7-mailjet' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Probar Conexión', 'cideapps-cf7-mailjet' ); ?></th>
					<td style="margin-top: 10px;">
						<p>
							<label for="cideapps_cf7_mailjet_test_email"><?php esc_html_e( 'Email de prueba:', 'cideapps-cf7-mailjet' ); ?></label>
							<input type="email" id="cideapps_cf7_mailjet_test_email" form="cideapps-cf7-mailjet-test-form" name="cideapps_cf7_mailjet_test_email" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" class="regular-text" style="margin-left: 5px;" />
						</p>
						<p>
							<button type="submit" form="cideapps-cf7-mailjet-test-form" class="button button-secondary"><?php esc_html_e( 'Probar conexión y agregar contacto de prueba', 'cideapps-cf7-mailjet' ); ?></button>
						</p>
						<p class="description"><?php esc_html_e( 'Prueba la conexión con Mailjet y agrega un contacto de prueba a la lista configurada. Se usará tu email actual si no especificas uno.', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
			</table>
				</details>

					<?php if ( ! empty( $cf7_forms ) ) : ?>
						<table class="wp-list-table widefat fixed striped cideapps-cf7-forms-table">
							<thead>
								<tr>
									<th scope="col" class="column-form"><?php esc_html_e( 'Formulario', 'cideapps-cf7-mailjet' ); ?></th>
									<th scope="col" class="column-status"><?php esc_html_e( 'Estado', 'cideapps-cf7-mailjet' ); ?></th>
									<th scope="col" class="column-channel"><?php esc_html_e( 'Canal', 'cideapps-cf7-mailjet' ); ?></th>
									<th scope="col" class="column-action"><?php esc_html_e( 'Acción', 'cideapps-cf7-mailjet' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $cf7_forms as $form_id => $form_title ) : ?>
									<?php
									$form_id_int  = (int) $form_id;
									$has_saved_mode = isset( $form_mail_modes[ $form_id_int ] );
									$current_mode = $has_saved_mode ? $form_mail_modes[ $form_id_int ] : 'cf7_mail';
									if ( ! in_array( $current_mode, array( 'cf7_mail', 'mailjet_only' ), true ) ) {
										$current_mode = 'cf7_mail';
									}
									$is_enabled = in_array( $form_id_int, array_map( 'intval', (array) $enabled_form_ids ), true );
									$channel_label = 'mailjet_only' === $current_mode
										? __( 'Mailjet API', 'cideapps-cf7-mailjet' )
										: __( 'Email nativo de Contact Form 7', 'cideapps-cf7-mailjet' );
									$status_label = $is_enabled
										? __( 'Activo', 'cideapps-cf7-mailjet' )
										: __( 'Inactivo', 'cideapps-cf7-mailjet' );
									$edit_url = call_user_func( $cideapps_cf7_mailjet_admin_tab_url, 'forms', $form_id_int );
									?>
									<tr>
										<td class="column-form">
											<strong><?php echo esc_html( $form_title ); ?></strong>
											<br />
											<span class="description"><?php echo esc_html( sprintf( 'ID: %d', $form_id_int ) ); ?></span>
										</td>
										<td class="column-status"><?php echo esc_html( $status_label ); ?></td>
										<td class="column-channel"><?php echo esc_html( $channel_label ); ?></td>
										<td class="column-action">
											<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-secondary"><?php esc_html_e( 'Editar', 'cideapps-cf7-mailjet' ); ?></a>
											<button
												type="submit"
												name="cideapps_cf7_mailjet_reset_form_id"
												value="<?php echo esc_attr( $form_id_int ); ?>"
												class="button button-link-delete cideapps-cf7-reset-form-btn"
												onclick="return confirm('<?php echo esc_js( __( '¿Restablecer este formulario? Solo se limpiará su configuración en este plugin.', 'cideapps-cf7-mailjet' ) ); ?>');"
											><?php esc_html_e( 'Restablecer', 'cideapps-cf7-mailjet' ); ?></button>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<?php wp_nonce_field( 'cideapps_cf7_mailjet_reset_form', 'cideapps_cf7_mailjet_reset_form_nonce' ); ?>
						<div class="cideapps-cf7-forms-list-preservation" hidden aria-hidden="true">
							<?php foreach ( $cf7_forms as $form_id => $form_title ) : ?>
								<?php
								$form_id_int  = (int) $form_id;
								if ( $show_form_detail && $form_id_int === (int) $form_id_view ) {
									continue;
								}
								$has_saved_mode = isset( $form_mail_modes[ $form_id_int ] );
								$current_mode = $has_saved_mode ? $form_mail_modes[ $form_id_int ] : 'cf7_mail';
								if ( ! in_array( $current_mode, array( 'cf7_mail', 'mailjet_only' ), true ) ) {
									$current_mode = 'cf7_mail';
								}
								$is_enabled = in_array( $form_id_int, array_map( 'intval', (array) $enabled_form_ids ), true );
								?>
								<?php if ( $is_enabled ) : ?>
									<input type="hidden" name="cideapps_cf7_mailjet_enabled_form_ids[]" value="<?php echo esc_attr( $form_id_int ); ?>" />
								<?php endif; ?>
								<?php if ( $has_saved_mode ) : ?>
									<input type="hidden" name="cideapps_cf7_mailjet_form_mail_modes[<?php echo esc_attr( $form_id_int ); ?>]" value="<?php echo esc_attr( $current_mode ); ?>" />
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
						<p class="description cideapps-cf7-forms-table-note">
							<?php esc_html_e( 'Estado = integración activa en este plugin. Canal y mappings se editan en la vista del formulario (Editar).', 'cideapps-cf7-mailjet' ); ?>
						</p>
					<?php else : ?>
						<p><?php esc_html_e( 'No se encontraron formularios de Contact Form 7.', 'cideapps-cf7-mailjet' ); ?></p>
					<?php endif; ?>

			<table class="form-table">
				<?php if ( $cf7_use_field_selectors ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Campos CF7 detectados', 'cideapps-cf7-mailjet' ); ?></th>
					<td>
						<p class="description">
							<?php echo esc_html( Cideapps_Cf7_Mailjet_Cf7_Field_Selector::get_tag_source_description( (array) $enabled_form_ids, $cf7_mapping_source_ids ) ); ?>
							<?php
							printf(
								' %s',
								esc_html(
									sprintf(
										/* translators: %d: number of unique CF7 field names. */
										_n(
											'%d nombre de campo único en la lista.',
											'%d nombres de campo únicos en la lista.',
											count( $cf7_mapping_fields ),
											'cideapps-cf7-mailjet'
										),
										count( $cf7_mapping_fields )
									)
								)
							);
							?>
						</p>
						<p class="description"><?php esc_html_e( 'Los valores guardados que ya no existen en el formulario se conservan como opción seleccionada. Guarda la configuración para aplicar cambios.', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
				<?php endif; ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Mappings globales de campos', 'cideapps-cf7-mailjet' ); ?></th>
					<td>
						<p class="description" style="margin-top: 0;">
							<?php esc_html_e( 'Valores por defecto del sitio. Los formularios con «Usar mappings globales de campos» activado heredan esta configuración.', 'cideapps-cf7-mailjet' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_email_field"><?php esc_html_e( 'Campo de Email', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<?php if ( $cf7_use_field_selectors ) : ?>
							<?php Cideapps_Cf7_Mailjet_Cf7_Field_Selector::render_mapping_select( 'cideapps_cf7_mailjet_email_field', 'cideapps_cf7_mailjet_email_field', $email_field, $cf7_mapping_fields ); ?>
						<?php else : ?>
							<input type="text" id="cideapps_cf7_mailjet_email_field" name="cideapps_cf7_mailjet_email_field" value="<?php echo esc_attr( $email_field ); ?>" class="regular-text" />
						<?php endif; ?>
						<p class="description"><?php esc_html_e( 'Nombre del campo de email en CF7 (por defecto: your-email)', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_name_field"><?php esc_html_e( 'Campo de Nombre', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<?php if ( $cf7_use_field_selectors ) : ?>
							<?php Cideapps_Cf7_Mailjet_Cf7_Field_Selector::render_mapping_select( 'cideapps_cf7_mailjet_name_field', 'cideapps_cf7_mailjet_name_field', $name_field, $cf7_mapping_fields ); ?>
						<?php else : ?>
							<input type="text" id="cideapps_cf7_mailjet_name_field" name="cideapps_cf7_mailjet_name_field" value="<?php echo esc_attr( $name_field ); ?>" class="regular-text" />
						<?php endif; ?>
						<p class="description"><?php esc_html_e( 'Nombre del campo de nombre en CF7 (por defecto: your-name)', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_phone_field"><?php esc_html_e( 'Campo de Teléfono', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<?php if ( $cf7_use_field_selectors ) : ?>
							<?php Cideapps_Cf7_Mailjet_Cf7_Field_Selector::render_mapping_select( 'cideapps_cf7_mailjet_phone_field', 'cideapps_cf7_mailjet_phone_field', $phone_field, $cf7_mapping_fields ); ?>
						<?php else : ?>
							<input type="text" id="cideapps_cf7_mailjet_phone_field" name="cideapps_cf7_mailjet_phone_field" value="<?php echo esc_attr( $phone_field ); ?>" class="regular-text" />
						<?php endif; ?>
						<p class="description"><?php esc_html_e( 'Nombre del campo de teléfono en CF7 (por defecto: your-phone)', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_service_field"><?php esc_html_e( 'Campo de Servicio', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<?php if ( $cf7_use_field_selectors ) : ?>
							<?php Cideapps_Cf7_Mailjet_Cf7_Field_Selector::render_mapping_select( 'cideapps_cf7_mailjet_service_field', 'cideapps_cf7_mailjet_service_field', $service_field, $cf7_mapping_fields ); ?>
						<?php else : ?>
							<input type="text" id="cideapps_cf7_mailjet_service_field" name="cideapps_cf7_mailjet_service_field" value="<?php echo esc_attr( $service_field ); ?>" class="regular-text" />
						<?php endif; ?>
						<p class="description"><?php esc_html_e( 'Nombre del campo de servicio en CF7 (por defecto: service)', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_message_field"><?php esc_html_e( 'Campo de Mensaje', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<?php if ( $cf7_use_field_selectors ) : ?>
							<?php Cideapps_Cf7_Mailjet_Cf7_Field_Selector::render_mapping_select( 'cideapps_cf7_mailjet_message_field', 'cideapps_cf7_mailjet_message_field', $message_field, $cf7_mapping_fields ); ?>
						<?php else : ?>
							<input type="text" id="cideapps_cf7_mailjet_message_field" name="cideapps_cf7_mailjet_message_field" value="<?php echo esc_attr( $message_field ); ?>" class="regular-text" />
						<?php endif; ?>
						<p class="description">
							<?php esc_html_e( 'Nombre del campo de mensaje en CF7 (por defecto: your-message). En plantillas Mailjet usa:', 'cideapps-cf7-mailjet' ); ?>
							<code>{{var:message}}</code>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Enviar Label del Servicio', 'cideapps-cf7-mailjet' ); ?></th>
					<td>
						<label>
							<input type="checkbox" id="cideapps_cf7_mailjet_service_send_label" name="cideapps_cf7_mailjet_service_send_label" value="1" <?php checked( $service_send_label, true ); ?> />
							<?php esc_html_e( 'Enviar label del servicio (en vez del value) a Mailjet', 'cideapps-cf7-mailjet' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Si está activado, se enviará el label humano (ej: "Apps Móviles") en lugar del value (ej: "apps-moviles") al template de Mailjet.', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Metadata CF7 en Mailjet', 'cideapps-cf7-mailjet' ); ?></th>
					<td>
						<label>
							<input type="checkbox" id="cideapps_cf7_mailjet_enable_submission_metadata" name="cideapps_cf7_mailjet_enable_submission_metadata" value="1" <?php checked( $enable_submission_metadata, true ); ?> />
							<?php esc_html_e( 'Incluir metadata automática de CF7 en variables Mailjet', 'cideapps-cf7-mailjet' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Variables disponibles en plantillas:', 'cideapps-cf7-mailjet' ); ?>
							<code>{{var:source_url}}</code>,
							<code>{{var:source_page}}</code>,
							<code>{{var:submitted_at}}</code>,
							<code>{{var:user_agent}}</code>,
							<code>{{var:remote_ip}}</code>,
							<code>{{var:utm_source}}</code>,
							<code>{{var:utm_campaign}}</code>,
							<code>{{var:utm_medium}}</code>,
							<code>{{var:utm_term}}</code>,
							<code>{{var:utm_content}}</code>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_dynamic_mappings"><?php esc_html_e( 'Campos Dinámicos (CF7 -> Mailjet)', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<div id="cideapps-cf7-mailjet-dynamic-mappings" class="cideapps-cf7-mappings">
							<?php foreach ( $dynamic_mappings_rows as $index => $row ) : ?>
								<div class="cideapps-cf7-mappings__row">
									<input
										type="text"
										name="cideapps_cf7_mailjet_dynamic_mappings_source[]"
										value="<?php echo esc_attr( $row['source'] ); ?>"
										class="regular-text cideapps-cf7-mappings__source"
										placeholder="<?php echo esc_attr__( 'origen (ej: your-company o [_url])', 'cideapps-cf7-mailjet' ); ?>"
									/>
									<span class="cideapps-cf7-mappings__arrow" aria-hidden="true">→</span>
									<input
										type="text"
										name="cideapps_cf7_mailjet_dynamic_mappings_target[]"
										value="<?php echo esc_attr( $row['target'] ); ?>"
										class="regular-text cideapps-cf7-mappings__target"
										placeholder="<?php echo esc_attr__( 'variable Mailjet (ej: company)', 'cideapps-cf7-mailjet' ); ?>"
									/>
									<button type="button" class="button cideapps-cf7-mappings__remove"><?php esc_html_e( 'Remove', 'cideapps-cf7-mailjet' ); ?></button>
								</div>
							<?php endforeach; ?>
						</div>
						<p>
							<button type="button" class="button button-secondary" id="cideapps-cf7-mailjet-dynamic-mappings-add"><?php esc_html_e( 'Add', 'cideapps-cf7-mailjet' ); ?></button>
						</p>
						<input type="hidden" id="cideapps_cf7_mailjet_dynamic_mappings" name="cideapps_cf7_mailjet_dynamic_mappings" value="<?php echo esc_attr( (string) $dynamic_mappings ); ?>" />
						<p class="description">
							<?php esc_html_e( 'Una línea por mapeo con formato origen:variable_mailjet. Ejemplos:', 'cideapps-cf7-mailjet' ); ?>
							<code>your-company:company</code>,
							<code>your-budget:budget</code>,
							<code>[_remote_ip]:visitor_ip</code>,
							<code>[_url]:landing_url</code>
						</p>
						<p class="description">
							<?php esc_html_e( 'Mail-tags especiales soportados: [_remote_ip], [_user_agent], [_url], [_date], [_time].', 'cideapps-cf7-mailjet' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Adjuntos CF7 (URLs en Mailjet)', 'cideapps-cf7-mailjet' ); ?></th>
					<td>
						<label>
							<input type="checkbox" id="cideapps_cf7_mailjet_enable_attachment_urls" name="cideapps_cf7_mailjet_enable_attachment_urls" value="1" <?php checked( $enable_attachment_urls, true ); ?> />
							<?php esc_html_e( 'Copiar archivos subidos a uploads y enviar URLs públicas a Mailjet', 'cideapps-cf7-mailjet' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'CF7 elimina archivos temporales al terminar el envío; el plugin los guarda en uploads/cideapps-cf7-mailjet/ para que el enlace siga funcionando en el correo.', 'cideapps-cf7-mailjet' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_attachment_mappings"><?php esc_html_e( 'Mapeo de Adjuntos (CF7 -> Mailjet)', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<div id="cideapps-cf7-mailjet-attachment-mappings" class="cideapps-cf7-mappings">
							<?php foreach ( $attachment_mappings_rows as $attachment_row ) : ?>
								<div class="cideapps-cf7-mappings__row">
									<input
										type="text"
										name="cideapps_cf7_mailjet_attachment_mappings_source[]"
										value="<?php echo esc_attr( $attachment_row['source'] ); ?>"
										class="regular-text cideapps-cf7-mappings__source"
										placeholder="<?php echo esc_attr__( 'campo file CF7 (ej: your-cv)', 'cideapps-cf7-mailjet' ); ?>"
									/>
									<span class="cideapps-cf7-mappings__arrow" aria-hidden="true">→</span>
									<input
										type="text"
										name="cideapps_cf7_mailjet_attachment_mappings_target[]"
										value="<?php echo esc_attr( $attachment_row['target'] ); ?>"
										class="regular-text cideapps-cf7-mappings__target"
										placeholder="<?php echo esc_attr__( 'variable Mailjet (ej: cv_url)', 'cideapps-cf7-mailjet' ); ?>"
									/>
									<button type="button" class="button cideapps-cf7-mappings__remove"><?php esc_html_e( 'Quitar', 'cideapps-cf7-mailjet' ); ?></button>
								</div>
							<?php endforeach; ?>
						</div>
						<p>
							<button type="button" class="button button-secondary" id="cideapps-cf7-mailjet-attachment-mappings-add"><?php esc_html_e( 'Añadir', 'cideapps-cf7-mailjet' ); ?></button>
						</p>
						<input type="hidden" id="cideapps_cf7_mailjet_attachment_mappings" name="cideapps_cf7_mailjet_attachment_mappings" value="<?php echo esc_attr( (string) $attachment_mappings ); ?>" />
						<p class="description">
							<?php esc_html_e( 'Si dejas el mapeo vacío, se usará automáticamente el nombre del campo + _url (ej: your-cv -> your_cv_url).', 'cideapps-cf7-mailjet' ); ?>
							<?php esc_html_e( 'También se envía la variable', 'cideapps-cf7-mailjet' ); ?>
							<code>{{var:attachments_all}}</code>
							<?php esc_html_e( 'con todas las URLs.', 'cideapps-cf7-mailjet' ); ?>
						</p>
						<p class="description">
							<?php
							echo wp_kses_post(
								sprintf(
									/* translators: %s: link to Security settings tab. */
									__( 'La retención y limpieza de archivos se configura en <a href="%s">Seguridad</a>.', 'cideapps-cf7-mailjet' ),
									esc_url( call_user_func( $cideapps_cf7_mailjet_admin_tab_url, 'security' ) )
								)
							);
							?>
						</p>
					</td>
				</tr>
			</table>
			</div><!-- #forms-list -->
		</div><!-- #forms-settings -->

		<div id="security-settings" class="tab-content cideapps-cf7-tab-security" style="<?php echo esc_attr( call_user_func( $cideapps_cf7_mailjet_tab_panel_style, 'security' ) ); ?>">
			<h2><?php esc_html_e( 'Seguridad', 'cideapps-cf7-mailjet' ); ?></h2>
			<p class="description cideapps-cf7-security-intro">
				<?php esc_html_e( 'Operación, protección y mantenimiento del plugin. No configura Mailjet, formularios, plantillas ni envíos.', 'cideapps-cf7-mailjet' ); ?>
			</p>

			<h3 class="title"><?php esc_html_e( 'Límites de envío', 'cideapps-cf7-mailjet' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'Reduce envíos abusivos repetidos desde el mismo contacto o la misma IP. Usa 0 para desactivar cada límite.', 'cideapps-cf7-mailjet' ); ?>
			</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_rate_limit_email_minutes"><?php esc_html_e( 'Rate limit por email (minutos)', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<input type="number" id="cideapps_cf7_mailjet_rate_limit_email_minutes" name="cideapps_cf7_mailjet_rate_limit_email_minutes" value="<?php echo esc_attr( $rate_limit_email_minutes ); ?>" class="small-text" min="0" />
						<p class="description"><?php esc_html_e( 'Tiempo mínimo entre dos envíos con el mismo email del formulario.', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_rate_limit_ip_minutes"><?php esc_html_e( 'Rate limit por IP (minutos)', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<input type="number" id="cideapps_cf7_mailjet_rate_limit_ip_minutes" name="cideapps_cf7_mailjet_rate_limit_ip_minutes" value="<?php echo esc_attr( $rate_limit_ip_minutes ); ?>" class="small-text" min="0" />
						<p class="description"><?php esc_html_e( 'Tiempo mínimo entre dos envíos desde la misma dirección IP.', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
			</table>

			<h3 class="title"><?php esc_html_e( 'Depuración', 'cideapps-cf7-mailjet' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Debug logs', 'cideapps-cf7-mailjet' ); ?></th>
					<td>
						<label>
							<input type="checkbox" id="cideapps_cf7_mailjet_debug_logs" name="cideapps_cf7_mailjet_debug_logs" value="1" <?php checked( $debug_logs, true ); ?> />
							<?php esc_html_e( 'Habilitar logs de depuración en el log de PHP', 'cideapps-cf7-mailjet' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Escribe entradas con el prefijo [CIDEAPPS-CF7-MAILJET] en el error_log configurado en el servidor (por ejemplo wp-content/debug.log si WP_DEBUG_LOG está activo).', 'cideapps-cf7-mailjet' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<h3 class="title"><?php esc_html_e( 'Adjuntos', 'cideapps-cf7-mailjet' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'Limpieza de copias en uploads/cideapps-cf7-mailjet/ creadas para enlaces en correos Mailjet.', 'cideapps-cf7-mailjet' ); ?>
			</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_attachment_retention_days"><?php esc_html_e( 'Días de retención de adjuntos', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<input type="number" id="cideapps_cf7_mailjet_attachment_retention_days" name="cideapps_cf7_mailjet_attachment_retention_days" value="<?php echo esc_attr( $attachment_retention_days ); ?>" class="small-text" min="0" max="3650" />
						<p class="description">
							<?php esc_html_e( 'Al guardar, el plugin reprograma el cron diario existente (WP-Cron) que elimina archivos más antiguos que este número de días. Solo afecta a uploads/cideapps-cf7-mailjet/.', 'cideapps-cf7-mailjet' ); ?>
						</p>
						<p class="description">
							<?php esc_html_e( '0 = desactivado: no se programa el cron y no se borran archivos automáticamente. Valor recomendado en producción: 30.', 'cideapps-cf7-mailjet' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<h3 class="title"><?php esc_html_e( 'Desinstalación', 'cideapps-cf7-mailjet' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Borrar uploads al desinstalar', 'cideapps-cf7-mailjet' ); ?></th>
					<td>
						<label>
							<input type="checkbox" id="cideapps_cf7_mailjet_uninstall_delete_uploads" name="cideapps_cf7_mailjet_uninstall_delete_uploads" value="1" <?php checked( $uninstall_delete_uploads, true ); ?> />
							<?php esc_html_e( 'Eliminar la carpeta uploads/cideapps-cf7-mailjet/ al desinstalar el plugin', 'cideapps-cf7-mailjet' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Solo aplica cuando eliminas el plugin desde Plugins → Eliminar. No borra nada si solo desactivas el plugin.', 'cideapps-cf7-mailjet' ); ?>
						</p>
						<p class="description">
							<?php esc_html_e( 'Desactivado por defecto por seguridad. Actívalo solo si quieres limpiar adjuntos copiados al desinstalar.', 'cideapps-cf7-mailjet' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<?php if ( ! $show_form_detail ) : ?>
			<?php submit_button( __( 'Guardar Configuración', 'cideapps-cf7-mailjet' ), 'primary', 'cideapps_cf7_mailjet_settings_submit' ); ?>
		<?php endif; ?>
	</form>

	<form method="post" action="" id="cideapps-cf7-mailjet-test-form" style="display: none;" aria-hidden="true">
		<?php wp_nonce_field( 'cideapps_cf7_mailjet_test_list' ); ?>
		<input type="hidden" name="cideapps_cf7_mailjet_test_list" value="1" />
	</form>
</div>
