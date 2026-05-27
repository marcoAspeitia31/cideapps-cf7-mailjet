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

	$owner_notify_enabled_value = isset( $_POST['cideapps_cf7_mailjet_owner_notify_enabled'] ) && $_POST['cideapps_cf7_mailjet_owner_notify_enabled'] === '1' ? 1 : 0;
	update_option( 'cideapps_cf7_mailjet_owner_notify_enabled', $owner_notify_enabled_value );
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

$owner_notify_enabled_raw = get_option( 'cideapps_cf7_mailjet_owner_notify_enabled', 0 );
$owner_notify_enabled     = ( $owner_notify_enabled_raw === 1 || $owner_notify_enabled_raw === '1' || $owner_notify_enabled_raw === true );
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
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<form method="post" action="">
		<?php wp_nonce_field( 'cideapps_cf7_mailjet_settings', 'cideapps_cf7_mailjet_settings_nonce' ); ?>

		<h2 class="nav-tab-wrapper">
			<a href="#mailjet-settings" class="nav-tab nav-tab-active">Mailjet</a>
			<a href="#autoreply-settings" class="nav-tab">Autorespuesta</a>
			<a href="#list-settings" class="nav-tab">Lista</a>
			<a href="#cf7-settings" class="nav-tab">CF7</a>
			<a href="#security-settings" class="nav-tab">Seguridad</a>
		</h2>

		<div id="mailjet-settings" class="tab-content">
			<h2><?php esc_html_e( 'Configuración de Mailjet', 'cideapps-cf7-mailjet' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_public_key"><?php esc_html_e( 'Public Key', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<input type="text" id="cideapps_cf7_mailjet_public_key" name="cideapps_cf7_mailjet_public_key" value="<?php echo esc_attr( $public_key ); ?>" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_private_key"><?php esc_html_e( 'Private Key', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<input type="password" id="cideapps_cf7_mailjet_private_key" name="cideapps_cf7_mailjet_private_key" value="<?php echo esc_attr( $private_key ); ?>" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_from_email"><?php esc_html_e( 'From Email', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<input type="email" id="cideapps_cf7_mailjet_from_email" name="cideapps_cf7_mailjet_from_email" value="<?php echo esc_attr( $from_email ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Email desde el cual se enviará la autorespuesta', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_from_name"><?php esc_html_e( 'From Name', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<input type="text" id="cideapps_cf7_mailjet_from_name" name="cideapps_cf7_mailjet_from_name" value="<?php echo esc_attr( $from_name ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Nombre que aparecerá como remitente', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div id="autoreply-settings" class="tab-content" style="display:none;">
			<h2><?php esc_html_e( 'Configuración de Autorespuesta', 'cideapps-cf7-mailjet' ); ?></h2>
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
		</div>

		<div id="list-settings" class="tab-content" style="display:none;">
			<h2><?php esc_html_e( 'Configuración de Lista', 'cideapps-cf7-mailjet' ); ?></h2>
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
		</div>

		<div id="cf7-settings" class="tab-content" style="display:none;">
			<h2><?php esc_html_e( 'Configuración de Contact Form 7', 'cideapps-cf7-mailjet' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Formularios Habilitados', 'cideapps-cf7-mailjet' ); ?></th>
					<td>
						<?php if ( ! empty( $cf7_forms ) ) : ?>
							<?php foreach ( $cf7_forms as $form_id => $form_title ) : ?>
								<?php
								$form_id_int   = (int) $form_id;
								$current_mode  = isset( $form_mail_modes[ $form_id_int ] ) ? $form_mail_modes[ $form_id_int ] : 'cf7_mail';
								$is_enabled    = in_array( $form_id_int, array_map( 'intval', (array) $enabled_form_ids ), true );
								?>
								<div style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #dcdcde;">
									<label style="display:block; margin-bottom: 6px;">
										<input type="checkbox" name="cideapps_cf7_mailjet_enabled_form_ids[]" value="<?php echo esc_attr( $form_id_int ); ?>" <?php checked( $is_enabled ); ?> />
										<strong><?php echo esc_html( $form_title ); ?></strong> (ID: <?php echo esc_html( $form_id_int ); ?>)
									</label>
									<label for="cideapps_cf7_mailjet_form_mail_mode_<?php echo esc_attr( $form_id_int ); ?>" style="display:block; margin-left: 24px;">
										<?php esc_html_e( 'Modo de envío:', 'cideapps-cf7-mailjet' ); ?>
										<select id="cideapps_cf7_mailjet_form_mail_mode_<?php echo esc_attr( $form_id_int ); ?>" name="cideapps_cf7_mailjet_form_mail_modes[<?php echo esc_attr( $form_id_int ); ?>]" style="margin-left: 6px;">
											<option value="cf7_mail" <?php selected( $current_mode, 'cf7_mail' ); ?>><?php esc_html_e( 'CF7 + Mailjet', 'cideapps-cf7-mailjet' ); ?></option>
											<option value="mailjet_only" <?php selected( $current_mode, 'mailjet_only' ); ?>><?php esc_html_e( 'Solo Mailjet', 'cideapps-cf7-mailjet' ); ?></option>
										</select>
									</label>
									<p class="description" style="margin: 6px 0 0 24px;">
										<strong><?php esc_html_e( 'CF7 + Mailjet:', 'cideapps-cf7-mailjet' ); ?></strong>
										<?php esc_html_e( 'Contact Form 7 envía su correo nativo y después se ejecuta Mailjet.', 'cideapps-cf7-mailjet' ); ?>
										<br />
										<strong><?php esc_html_e( 'Solo Mailjet:', 'cideapps-cf7-mailjet' ); ?></strong>
										<?php esc_html_e( 'Contact Form 7 omite su correo nativo y el plugin procesa Mailjet vía API. Recomendado para VPS con SMTP bloqueado.', 'cideapps-cf7-mailjet' ); ?>
									</p>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<p><?php esc_html_e( 'No se encontraron formularios de Contact Form 7.', 'cideapps-cf7-mailjet' ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_email_field"><?php esc_html_e( 'Campo de Email', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<input type="text" id="cideapps_cf7_mailjet_email_field" name="cideapps_cf7_mailjet_email_field" value="<?php echo esc_attr( $email_field ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Nombre del campo de email en CF7 (por defecto: your-email)', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_name_field"><?php esc_html_e( 'Campo de Nombre', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<input type="text" id="cideapps_cf7_mailjet_name_field" name="cideapps_cf7_mailjet_name_field" value="<?php echo esc_attr( $name_field ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Nombre del campo de nombre en CF7 (por defecto: your-name)', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_phone_field"><?php esc_html_e( 'Campo de Teléfono', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<input type="text" id="cideapps_cf7_mailjet_phone_field" name="cideapps_cf7_mailjet_phone_field" value="<?php echo esc_attr( $phone_field ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Nombre del campo de teléfono en CF7 (por defecto: your-phone)', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_service_field"><?php esc_html_e( 'Campo de Servicio', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<input type="text" id="cideapps_cf7_mailjet_service_field" name="cideapps_cf7_mailjet_service_field" value="<?php echo esc_attr( $service_field ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Nombre del campo de servicio en CF7 (por defecto: service)', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_message_field"><?php esc_html_e( 'Campo de Mensaje', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<input type="text" id="cideapps_cf7_mailjet_message_field" name="cideapps_cf7_mailjet_message_field" value="<?php echo esc_attr( $message_field ); ?>" class="regular-text" />
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
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Notificación al Negocio (Solo Mailjet)', 'cideapps-cf7-mailjet' ); ?></th>
					<td>
						<label>
							<input type="checkbox" id="cideapps_cf7_mailjet_owner_notify_enabled" name="cideapps_cf7_mailjet_owner_notify_enabled" value="1" <?php checked( $owner_notify_enabled, true ); ?> />
							<?php esc_html_e( 'Enviar correo de notificación al negocio cuando el formulario está en modo "Solo Mailjet".', 'cideapps-cf7-mailjet' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Esto reemplaza el correo nativo de CF7 para modo Solo Mailjet.', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_owner_notify_to_email"><?php esc_html_e( 'Email destino negocio', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<input type="email" id="cideapps_cf7_mailjet_owner_notify_to_email" name="cideapps_cf7_mailjet_owner_notify_to_email" value="<?php echo esc_attr( $owner_notify_to_email ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Correo donde recibirás los datos del lead.', 'cideapps-cf7-mailjet' ); ?></p>
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

		<div id="security-settings" class="tab-content" style="display:none;">
			<h2><?php esc_html_e( 'Configuración de Seguridad', 'cideapps-cf7-mailjet' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_rate_limit_email_minutes"><?php esc_html_e( 'Rate Limit por Email (minutos)', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<input type="number" id="cideapps_cf7_mailjet_rate_limit_email_minutes" name="cideapps_cf7_mailjet_rate_limit_email_minutes" value="<?php echo esc_attr( $rate_limit_email_minutes ); ?>" class="small-text" min="0" />
						<p class="description"><?php esc_html_e( 'Tiempo en minutos entre envíos del mismo email (0 = deshabilitado)', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cideapps_cf7_mailjet_rate_limit_ip_minutes"><?php esc_html_e( 'Rate Limit por IP (minutos)', 'cideapps-cf7-mailjet' ); ?></label>
					</th>
					<td>
						<input type="number" id="cideapps_cf7_mailjet_rate_limit_ip_minutes" name="cideapps_cf7_mailjet_rate_limit_ip_minutes" value="<?php echo esc_attr( $rate_limit_ip_minutes ); ?>" class="small-text" min="0" />
						<p class="description"><?php esc_html_e( 'Tiempo en minutos entre envíos desde la misma IP (0 = deshabilitado)', 'cideapps-cf7-mailjet' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Logs de Depuración', 'cideapps-cf7-mailjet' ); ?></th>
					<td>
						<label>
							<input type="checkbox" id="cideapps_cf7_mailjet_debug_logs" name="cideapps_cf7_mailjet_debug_logs" value="1" <?php checked( $debug_logs, true ); ?> />
							<?php esc_html_e( 'Habilitar logs de depuración (usar error_log de PHP)', 'cideapps-cf7-mailjet' ); ?>
						</label>
					</td>
				</tr>
			</table>
		</div>

		<?php submit_button( __( 'Guardar Configuración', 'cideapps-cf7-mailjet' ), 'primary', 'cideapps_cf7_mailjet_settings_submit' ); ?>
	</form>

	<form method="post" action="" id="cideapps-cf7-mailjet-test-form" style="display: none;" aria-hidden="true">
		<?php wp_nonce_field( 'cideapps_cf7_mailjet_test_list' ); ?>
		<input type="hidden" name="cideapps_cf7_mailjet_test_list" value="1" />
	</form>
</div>

<script>
jQuery(document).ready(function($) {
	$('.nav-tab').on('click', function(e) {
		e.preventDefault();
		var target = $(this).attr('href');
		$('.nav-tab').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		$('.tab-content').hide();
		$(target).show();
	});
});
</script>

<style>
.tab-content {
	margin-top: 20px;
}
</style>
