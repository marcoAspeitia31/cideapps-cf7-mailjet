<?php

/**
 * Rate limiting class
 *
 * @link       https://cideapps.com
 * @since      1.0.0
 *
 * @package    Cideapps_Cf7_Mailjet
 * @subpackage Cideapps_Cf7_Mailjet/includes
 */

/**
 * Rate limiting class.
 *
 * Implements rate limiting using WordPress transients to prevent abuse.
 *
 * @package    Cideapps_Cf7_Mailjet
 * @subpackage Cideapps_Cf7_Mailjet/includes
 * @author     CIDEAPPS DIGITAL <contacto@cideapps.com>
 */
class Cideapps_Cf7_Mailjet_Rate_Limit {

	/**
	 * Check if email is rate limited
	 *
	 * @since    1.0.0
	 * @param    string    $email    Email address to check
	 * @return   bool      True if rate limited, false otherwise
	 */
	public function is_email_rate_limited( $email ) {
		$minutes = (int) get_option( 'cideapps_cf7_mailjet_rate_limit_email_minutes', 10 );
		if ( $minutes <= 0 ) {
			return false; // Rate limiting disabled
		}

		$key   = 'cf7_mj_email_' . md5( sanitize_email( $email ) );
		$value = get_transient( $key );

		return ! empty( $value );
	}

	/**
	 * Set rate limit for email
	 *
	 * @since    1.0.0
	 * @param    string    $email    Email address to rate limit
	 * @return   void
	 */
	public function set_email_rate_limit( $email ) {
		$minutes = (int) get_option( 'cideapps_cf7_mailjet_rate_limit_email_minutes', 10 );
		if ( $minutes <= 0 ) {
			return; // Rate limiting disabled
		}

		$key = 'cf7_mj_email_' . md5( sanitize_email( $email ) );
		set_transient( $key, time(), $minutes * MINUTE_IN_SECONDS );
	}

	/**
	 * Check if IP is rate limited
	 *
	 * @since    1.0.0
	 * @param    string    $ip    IP address to check
	 * @return   bool      True if rate limited, false otherwise
	 */
	public function is_ip_rate_limited( $ip ) {
		$minutes = (int) get_option( 'cideapps_cf7_mailjet_rate_limit_ip_minutes', 10 );
		if ( $minutes <= 0 ) {
			return false; // Rate limiting disabled
		}

		$key   = 'cf7_mj_ip_' . md5( sanitize_text_field( $ip ) );
		$value = get_transient( $key );

		return ! empty( $value );
	}

	/**
	 * Set rate limit for IP
	 *
	 * @since    1.0.0
	 * @param    string    $ip    IP address to rate limit
	 * @return   void
	 */
	public function set_ip_rate_limit( $ip ) {
		$minutes = (int) get_option( 'cideapps_cf7_mailjet_rate_limit_ip_minutes', 10 );
		if ( $minutes <= 0 ) {
			return; // Rate limiting disabled
		}

		$key = 'cf7_mj_ip_' . md5( sanitize_text_field( $ip ) );
		set_transient( $key, time(), $minutes * MINUTE_IN_SECONDS );
	}

	/**
	 * Get client IP address
	 *
	 * @since    1.0.0
	 * @return   string    Client IP address
	 */
	public function get_client_ip() {
		$ip_keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
					$ip = trim( $ip );
					if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
						return $ip;
					}
				}
			}
		}

		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	}
}

