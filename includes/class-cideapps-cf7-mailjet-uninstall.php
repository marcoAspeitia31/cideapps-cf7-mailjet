<?php

/**
 * Plugin uninstall cleanup.
 *
 * @package    Cideapps_Cf7_Mailjet
 * @subpackage Cideapps_Cf7_Mailjet/includes
 */

/**
 * Removes plugin data when the plugin is uninstalled (not on deactivation).
 *
 * @package    Cideapps_Cf7_Mailjet
 * @subpackage Cideapps_Cf7_Mailjet/includes
 */
class Cideapps_Cf7_Mailjet_Uninstall {

	/**
	 * Option: delete uploads folder on uninstall when enabled (default off).
	 *
	 * @var string
	 */
	const OPTION_DELETE_UPLOADS = 'cideapps_cf7_mailjet_uninstall_delete_uploads';

	/**
	 * wp_options keys owned by this plugin (whitelist only).
	 *
	 * @var string[]
	 */
	private static $option_names = array(
		'cideapps_cf7_mailjet_public_key',
		'cideapps_cf7_mailjet_private_key',
		'cideapps_cf7_mailjet_from_email',
		'cideapps_cf7_mailjet_from_name',
		'cideapps_cf7_mailjet_enable_autoreply',
		'cideapps_cf7_mailjet_template_id',
		'cideapps_cf7_mailjet_enable_contact_list',
		'cideapps_cf7_mailjet_list_id',
		'cideapps_cf7_mailjet_on_existing_contact',
		'cideapps_cf7_mailjet_enabled_form_ids',
		'cideapps_cf7_mailjet_form_mail_modes',
		'cideapps_cf7_mailjet_email_field',
		'cideapps_cf7_mailjet_name_field',
		'cideapps_cf7_mailjet_phone_field',
		'cideapps_cf7_mailjet_service_field',
		'cideapps_cf7_mailjet_message_field',
		'cideapps_cf7_mailjet_service_send_label',
		'cideapps_cf7_mailjet_enable_submission_metadata',
		'cideapps_cf7_mailjet_dynamic_mappings',
		'cideapps_cf7_mailjet_enable_attachment_urls',
		'cideapps_cf7_mailjet_attachment_mappings',
		'cideapps_cf7_mailjet_owner_notify_enabled',
		'cideapps_cf7_mailjet_owner_notify_to_email',
		'cideapps_cf7_mailjet_owner_notify_mode',
		'cideapps_cf7_mailjet_owner_notify_template_id',
		'cideapps_cf7_mailjet_owner_notify_subject',
		'cideapps_cf7_mailjet_rate_limit_email_minutes',
		'cideapps_cf7_mailjet_rate_limit_ip_minutes',
		'cideapps_cf7_mailjet_debug_logs',
		'cideapps_cf7_mailjet_attachment_retention_days',
		self::OPTION_DELETE_UPLOADS,
	);

	/**
	 * Transient name suffix prefixes (after _transient_ / _transient_timeout_).
	 *
	 * @var string[]
	 */
	private static $transient_prefixes = array(
		'cf7_mj_email_',
		'cf7_mj_ip_',
		'cf7_mj_proc_',
	);

	/**
	 * Run uninstall cleanup.
	 *
	 * @return void
	 */
	public static function run() {
		require_once __DIR__ . '/class-cideapps-cf7-mailjet-upload-cleanup.php';

		$delete_uploads = self::should_delete_uploads_on_uninstall();

		Cideapps_Cf7_Mailjet_Upload_Cleanup::unschedule_cron();
		self::delete_transients();
		self::delete_options();

		if ( $delete_uploads ) {
			self::delete_upload_directory();
		}
	}

	/**
	 * Whether admin enabled upload deletion on uninstall (read before options are removed).
	 *
	 * @return bool
	 */
	private static function should_delete_uploads_on_uninstall() {
		$raw = get_option( self::OPTION_DELETE_UPLOADS, 0 );
		return ( $raw === 1 || $raw === '1' || $raw === true );
	}

	/**
	 * Delete whitelisted plugin options.
	 *
	 * @return void
	 */
	private static function delete_options() {
		foreach ( self::$option_names as $option_name ) {
			delete_option( $option_name );
		}
	}

	/**
	 * Delete plugin transients by known prefixes only.
	 *
	 * @return void
	 */
	private static function delete_transients() {
		global $wpdb;

		if ( ! isset( $wpdb->options ) ) {
			return;
		}

		$like_clauses = array();
		foreach ( self::$transient_prefixes as $prefix ) {
			$like_clauses[] = $wpdb->prepare( 'option_name LIKE %s', '_transient_' . $prefix . '%' );
			$like_clauses[] = $wpdb->prepare( 'option_name LIKE %s', '_transient_timeout_' . $prefix . '%' );
		}

		if ( empty( $like_clauses ) ) {
			return;
		}

		$sql = "DELETE FROM {$wpdb->options} WHERE " . implode( ' OR ', $like_clauses );
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Remove plugin upload directory when opt-in is enabled.
	 *
	 * @return void
	 */
	private static function delete_upload_directory() {
		$base_dir = Cideapps_Cf7_Mailjet_Upload_Cleanup::get_upload_base_dir();
		if ( '' === $base_dir || ! is_dir( $base_dir ) ) {
			return;
		}

		$base_real = realpath( $base_dir );
		if ( false === $base_real || ! is_dir( $base_real ) ) {
			return;
		}

		$base_real = trailingslashit( $base_real );
		self::delete_directory_contents( $base_real );

		@rmdir( $base_real );
	}

	/**
	 * Recursively delete files and subdirectories under a verified base path.
	 *
	 * @param string $dir_real Directory path with trailing slash.
	 * @return void
	 */
	private static function delete_directory_contents( $dir_real ) {
		if ( ! is_dir( $dir_real ) ) {
			return;
		}

		$entries = @scandir( $dir_real );
		if ( ! is_array( $entries ) ) {
			return;
		}

		foreach ( $entries as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}

			$path = $dir_real . $entry;
			if ( is_dir( $path ) ) {
				$child_real = realpath( $path );
				if ( false === $child_real || ! is_dir( $child_real ) ) {
					continue;
				}
				if ( 0 !== strpos( trailingslashit( $child_real ), $dir_real ) ) {
					continue;
				}
				self::delete_directory_contents( trailingslashit( $child_real ) );
				@rmdir( $child_real );
				continue;
			}

			if ( is_file( $path ) ) {
				$file_real = realpath( $path );
				if ( false === $file_real || 0 !== strpos( trailingslashit( $file_real ), $dir_real ) ) {
					continue;
				}
				@unlink( $file_real );
			}
		}
	}

	/**
	 * Return whitelisted option names (for documentation / tests).
	 *
	 * @return string[]
	 */
	public static function get_option_names() {
		return self::$option_names;
	}
}
