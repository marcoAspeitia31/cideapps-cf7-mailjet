<?php

/**
 * Scheduled cleanup for plugin upload copies.
 *
 * @package    Cideapps_Cf7_Mailjet
 * @subpackage Cideapps_Cf7_Mailjet/includes
 */

/**
 * Deletes aged files under uploads/cideapps-cf7-mailjet/ only.
 *
 * @package    Cideapps_Cf7_Mailjet
 * @subpackage Cideapps_Cf7_Mailjet/includes
 */
class Cideapps_Cf7_Mailjet_Upload_Cleanup {

	/**
	 * WP-Cron hook name.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'cideapps_cf7_mailjet_upload_cleanup';

	/**
	 * Upload subdirectory under wp-content/uploads.
	 *
	 * @var string
	 */
	const UPLOAD_SUBDIR = 'cideapps-cf7-mailjet';

	/**
	 * Option key for retention days (0 = disabled).
	 *
	 * @var string
	 */
	const OPTION_RETENTION_DAYS = 'cideapps_cf7_mailjet_attachment_retention_days';

	/**
	 * Default retention when option is not set.
	 *
	 * @var int
	 */
	const DEFAULT_RETENTION_DAYS = 30;

	/**
	 * Max files deleted per cron run.
	 *
	 * @var int
	 */
	const MAX_FILES_PER_RUN = 500;

	/**
	 * Filenames never deleted inside the upload directory.
	 *
	 * @var string[]
	 */
	const PROTECTED_FILENAMES = array( '.htaccess', 'index.php', 'index.html' );

	/**
	 * Get configured retention days.
	 *
	 * @return int 0 means cleanup disabled.
	 */
	public static function get_retention_days() {
		$raw = get_option( self::OPTION_RETENTION_DAYS, self::DEFAULT_RETENTION_DAYS );
		return max( 0, (int) $raw );
	}

	/**
	 * Absolute path to plugin upload root.
	 *
	 * @return string Empty if uploads directory is unavailable.
	 */
	public static function get_upload_base_dir() {
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return '';
		}

		return trailingslashit( $upload_dir['basedir'] ) . self::UPLOAD_SUBDIR;
	}

	/**
	 * Schedule daily cleanup when retention is enabled and no event exists yet.
	 *
	 * Does not clear existing scheduled events.
	 *
	 * @return void
	 */
	public static function schedule_cron() {
		if ( self::get_retention_days() <= 0 ) {
			self::unschedule_cron();
			return;
		}

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Clear all scheduled cleanup events (retention disabled or plugin deactivation).
	 *
	 * @return void
	 */
	public static function unschedule_cron() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		while ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
			$timestamp = wp_next_scheduled( self::CRON_HOOK );
		}
	}

	/**
	 * Rebuild cron schedule after retention setting changes in admin.
	 *
	 * @return void
	 */
	public static function reschedule_cron() {
		self::unschedule_cron();
		self::schedule_cron();
	}

	/**
	 * Cron callback: delete files older than the retention threshold.
	 *
	 * @return array{deleted:int,skipped:int,errors:int}
	 */
	public static function run_cleanup() {
		$retention_days = self::get_retention_days();
		$result         = array(
			'deleted' => 0,
			'skipped' => 0,
			'errors'  => 0,
		);

		if ( $retention_days <= 0 ) {
			return $result;
		}

		$base_dir = self::get_upload_base_dir();
		if ( '' === $base_dir || ! is_dir( $base_dir ) ) {
			return $result;
		}

		$base_real = realpath( $base_dir );
		if ( false === $base_real ) {
			return $result;
		}

		$base_real = trailingslashit( $base_real );
		$cutoff    = time() - ( $retention_days * DAY_IN_SECONDS );

		try {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $base_real, FilesystemIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::LEAVES_ONLY
			);
		} catch ( Exception $e ) {
			self::log_message( 'Upload cleanup: could not read upload directory.', 'error' );
			$result['errors']++;
			return $result;
		}

		foreach ( $iterator as $file_info ) {
			if ( $result['deleted'] >= self::MAX_FILES_PER_RUN ) {
				self::log_message(
					sprintf(
						'Upload cleanup: reached cap of %d files per run.',
						self::MAX_FILES_PER_RUN
					),
					'info'
				);
				break;
			}

			if ( ! $file_info->isFile() ) {
				continue;
			}

			$path = $file_info->getPathname();
			if ( ! self::is_path_inside_base( $path, $base_real ) ) {
				$result['skipped']++;
				continue;
			}

			if ( in_array( strtolower( $file_info->getFilename() ), self::PROTECTED_FILENAMES, true ) ) {
				$result['skipped']++;
				continue;
			}

			$mtime = @filemtime( $path );
			if ( false === $mtime || $mtime > $cutoff ) {
				$result['skipped']++;
				continue;
			}

			if ( @unlink( $path ) ) {
				$result['deleted']++;
			} else {
				$result['errors']++;
			}
		}

		if ( $result['deleted'] > 0 || $result['errors'] > 0 ) {
			self::log_message(
				sprintf(
					'Upload cleanup finished (retention %d days): deleted=%d, skipped=%d, errors=%d',
					$retention_days,
					$result['deleted'],
					$result['skipped'],
					$result['errors']
				),
				'info'
			);
		}

		return $result;
	}

	/**
	 * Verify file path is inside the plugin upload root.
	 *
	 * @param string $path      Candidate path.
	 * @param string $base_real Real upload root with trailing slash.
	 * @return bool
	 */
	private static function is_path_inside_base( $path, $base_real ) {
		$file_real = realpath( $path );
		if ( false === $file_real ) {
			return false;
		}

		return 0 === strpos( trailingslashit( $file_real ), $base_real );
	}

	/**
	 * Write to plugin logger when debug_logs is enabled.
	 *
	 * @param string $message Log message.
	 * @param string $type    Log type: info, warning, error.
	 * @return void
	 */
	private static function log_message( $message, $type = 'info' ) {
		if ( ! class_exists( 'Cideapps_Cf7_Mailjet_Logger' ) ) {
			require_once __DIR__ . '/class-cideapps-cf7-mailjet-logger.php';
		}

		$logger = new Cideapps_Cf7_Mailjet_Logger();
		if ( 'error' === $type ) {
			$logger->error( $message );
			return;
		}
		if ( 'warning' === $type ) {
			$logger->warning( $message );
			return;
		}
		$logger->info( $message );
	}
}
