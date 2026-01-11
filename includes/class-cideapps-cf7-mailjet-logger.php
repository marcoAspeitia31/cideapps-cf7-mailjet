<?php

/**
 * Logger class
 *
 * @link       https://cideapps.com
 * @since      1.0.0
 *
 * @package    Cideapps_Cf7_Mailjet
 * @subpackage Cideapps_Cf7_Mailjet/includes
 */

/**
 * Logger class.
 *
 * Handles debug logging functionality.
 *
 * @package    Cideapps_Cf7_Mailjet
 * @subpackage Cideapps_Cf7_Mailjet/includes
 * @author     CIDEAPPS DIGITAL <contacto@cideapps.com>
 */
class Cideapps_Cf7_Mailjet_Logger {

	/**
	 * Log prefix
	 *
	 * @since    1.0.0
	 * @var      string    $prefix    Log message prefix
	 */
	private $prefix = '[CIDEAPPS-CF7-MAILJET]';

	/**
	 * Check if logging is enabled
	 *
	 * @since    1.0.0
	 * @return   bool    True if logging is enabled
	 */
	private function is_enabled() {
		return (bool) get_option( 'cideapps_cf7_mailjet_debug_logs', false );
	}

	/**
	 * Log a message
	 *
	 * @since    1.0.0
	 * @param    string    $message    Message to log
	 * @param    string    $type       Log type (info, error, warning)
	 * @return   void
	 */
	public function log( $message, $type = 'info' ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$log_message = $this->prefix . ' [' . strtoupper( $type ) . '] ' . $message;
		error_log( $log_message );
	}

	/**
	 * Log info message
	 *
	 * @since    1.0.0
	 * @param    string    $message    Message to log
	 * @return   void
	 */
	public function info( $message ) {
		$this->log( $message, 'info' );
	}

	/**
	 * Log error message
	 *
	 * @since    1.0.0
	 * @param    string    $message    Message to log
	 * @return   void
	 */
	public function error( $message ) {
		$this->log( $message, 'error' );
	}

	/**
	 * Log warning message
	 *
	 * @since    1.0.0
	 * @param    string    $message    Message to log
	 * @return   void
	 */
	public function warning( $message ) {
		$this->log( $message, 'warning' );
	}
}

