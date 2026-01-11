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
	$service_send_label_value = isset( $_POST['cideapps_cf7_mailjet_service_send_label'] ) && $_POST['cideapps_cf7_mailjet_service_send_label'] === '1' ? 1 : 0;
	update_option( 'cideapps_cf7_mailjet_service_send_label', $service_send_label_value );

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
$email_field             = get_option( 'cideapps_cf7_mailjet_email_field', 'your-email' );
$name_field              = get_option( 'cideapps_cf7_mailjet_name_field', 'your-name' );
$phone_field             = get_option( 'cideapps_cf7_mailjet_phone_field', 'your-phone' );
$service_field           = get_option( 'cideapps_cf7_mailjet_service_field', 'service' );
$service_send_label_raw  = get_option( 'cideapps_cf7_mailjet_service_send_label', 0 );
$service_send_label      = ( $service_send_label_raw === 1 || $service_send_label_raw === '1' || $service_send_label_raw === true );
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
								<label style="display:block; margin-bottom:5px;">
									<input type="checkbox" name="cideapps_cf7_mailjet_enabled_form_ids[]" value="<?php echo esc_attr( $form_id ); ?>" <?php checked( in_array( $form_id, $enabled_form_ids, true ) ); ?> />
									<?php echo esc_html( $form_title ); ?> (ID: <?php echo esc_html( $form_id ); ?>)
								</label>
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
					<th scope="row"><?php esc_html_e( 'Enviar Label del Servicio', 'cideapps-cf7-mailjet' ); ?></th>
					<td>
						<label>
							<input type="checkbox" id="cideapps_cf7_mailjet_service_send_label" name="cideapps_cf7_mailjet_service_send_label" value="1" <?php checked( $service_send_label, true ); ?> />
							<?php esc_html_e( 'Enviar label del servicio (en vez del value) a Mailjet', 'cideapps-cf7-mailjet' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Si está activado, se enviará el label humano (ej: "Apps Móviles") en lugar del value (ej: "apps-moviles") al template de Mailjet.', 'cideapps-cf7-mailjet' ); ?></p>
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
