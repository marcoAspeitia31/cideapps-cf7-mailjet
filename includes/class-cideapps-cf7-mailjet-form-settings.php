<?php

/**
 * Per-form CF7 field mapping settings with fallback to global options.
 *
 * @package    Cideapps_Cf7_Mailjet
 * @subpackage Cideapps_Cf7_Mailjet/includes
 */

/**
 * Stores and resolves core field mappings per Contact Form 7 form ID.
 *
 * @package    Cideapps_Cf7_Mailjet
 * @subpackage Cideapps_Cf7_Mailjet/includes
 */
class Cideapps_Cf7_Mailjet_Form_Settings {

	/**
	 * wp_option key for per-form settings.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'cideapps_cf7_mailjet_form_settings';

	/**
	 * Per-form flag: when true or absent, global field mappings apply.
	 *
	 * @var string
	 */
	const USE_GLOBAL_FIELD_MAPPINGS_KEY = 'use_global_field_mappings';

	/**
	 * Logical mapping keys allowed in per-form overrides.
	 *
	 * @var string[]
	 */
	const FIELD_MAPPING_KEYS = array(
		'email_field',
		'name_field',
		'phone_field',
		'service_field',
		'message_field',
	);

	/**
	 * Maps logical keys to global wp_option names.
	 *
	 * @var array<string, string>
	 */
	const GLOBAL_OPTION_BY_KEY = array(
		'email_field'   => 'cideapps_cf7_mailjet_email_field',
		'name_field'    => 'cideapps_cf7_mailjet_name_field',
		'phone_field'   => 'cideapps_cf7_mailjet_phone_field',
		'service_field' => 'cideapps_cf7_mailjet_service_field',
		'message_field' => 'cideapps_cf7_mailjet_message_field',
	);

	/**
	 * Default values when a global option is not set.
	 *
	 * @var array<string, string>
	 */
	const GLOBAL_DEFAULTS = array(
		'email_field'   => 'your-email',
		'name_field'    => 'your-name',
		'phone_field'   => 'your-phone',
		'service_field' => 'service',
		'message_field' => 'your-message',
	);

	/**
	 * All per-form settings from the database.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_all_settings() {
		$raw = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$normalized = array();
		foreach ( $raw as $form_id => $settings ) {
			$form_id = (int) $form_id;
			if ( $form_id <= 0 || ! is_array( $settings ) ) {
				continue;
			}
			$normalized[ $form_id ] = $settings;
		}

		return $normalized;
	}

	/**
	 * Raw settings array for one form (may include non-mapping keys).
	 *
	 * @param int $form_id CF7 contact form post ID.
	 * @return array<string, mixed> Empty if none stored.
	 */
	public static function get_form_settings_raw( $form_id ) {
		$form_id = (int) $form_id;
		if ( $form_id <= 0 ) {
			return array();
		}

		$all = self::get_all_settings();
		if ( ! isset( $all[ $form_id ] ) || ! is_array( $all[ $form_id ] ) ) {
			return array();
		}

		return $all[ $form_id ];
	}

	/**
	 * Whether a form uses global field mappings (default: true).
	 *
	 * @param int $form_id CF7 contact form post ID.
	 * @return bool
	 */
	public static function uses_global_field_mappings( $form_id ) {
		$form_id = (int) $form_id;
		if ( $form_id <= 0 ) {
			return true;
		}

		$settings = self::get_form_settings_raw( $form_id );
		if ( empty( $settings ) ) {
			return true;
		}

		if ( ! array_key_exists( self::USE_GLOBAL_FIELD_MAPPINGS_KEY, $settings ) ) {
			return true;
		}

		return self::to_bool( $settings[ self::USE_GLOBAL_FIELD_MAPPINGS_KEY ] );
	}

	/**
	 * Resolved CF7 field name for a mapping key (per-form override or global fallback).
	 *
	 * @param int    $form_id CF7 contact form post ID.
	 * @param string $key     One of FIELD_MAPPING_KEYS.
	 * @return string Sanitized field name.
	 */
	public static function get_field_mapping( $form_id, $key ) {
		if ( ! self::is_valid_field_mapping_key( $key ) ) {
			return self::get_global_field_mapping( $key );
		}

		if ( self::uses_global_field_mappings( $form_id ) ) {
			return self::get_global_field_mapping( $key );
		}

		$settings = self::get_form_settings_raw( $form_id );
		if ( array_key_exists( $key, $settings ) ) {
			return sanitize_text_field( (string) $settings[ $key ] );
		}

		return self::get_global_field_mapping( $key );
	}

	/**
	 * Global field mapping for a logical key.
	 *
	 * @param string $key One of FIELD_MAPPING_KEYS.
	 * @return string
	 */
	public static function get_global_field_mapping( $key ) {
		if ( ! self::is_valid_field_mapping_key( $key ) ) {
			return '';
		}

		$option_name = self::GLOBAL_OPTION_BY_KEY[ $key ];
		$default     = isset( self::GLOBAL_DEFAULTS[ $key ] ) ? self::GLOBAL_DEFAULTS[ $key ] : '';

		return sanitize_text_field( (string) get_option( $option_name, $default ) );
	}

	/**
	 * Global wp_option name for a logical mapping key.
	 *
	 * @param string $key One of FIELD_MAPPING_KEYS.
	 * @return string Empty if key is invalid.
	 */
	public static function get_global_option_name( $key ) {
		if ( ! self::is_valid_field_mapping_key( $key ) ) {
			return '';
		}

		return self::GLOBAL_OPTION_BY_KEY[ $key ];
	}

	/**
	 * Whether a key is an allowed field mapping identifier.
	 *
	 * @param string $key Logical mapping key.
	 * @return bool
	 */
	public static function is_valid_field_mapping_key( $key ) {
		return in_array( $key, self::FIELD_MAPPING_KEYS, true );
	}

	/**
	 * Sanitize per-form settings from admin POST or programmatic input.
	 *
	 * Structure: [ form_id => [ 'use_global_field_mappings' => bool, ...mapping keys ] ].
	 * When use_global is true, mapping keys for that form are omitted from the result.
	 *
	 * @param mixed $raw Raw input (typically $_POST fragment).
	 * @return array<int, array<string, mixed>>
	 */
	public static function sanitize_settings( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $raw as $form_id => $settings ) {
			$form_id = (int) $form_id;
			if ( $form_id <= 0 || ! is_array( $settings ) ) {
				continue;
			}

			$use_global = true;
			if ( array_key_exists( self::USE_GLOBAL_FIELD_MAPPINGS_KEY, $settings ) ) {
				$use_global = self::to_bool( $settings[ self::USE_GLOBAL_FIELD_MAPPINGS_KEY ] );
			}

			$form_row = array(
				self::USE_GLOBAL_FIELD_MAPPINGS_KEY => $use_global,
			);

			if ( ! $use_global ) {
				foreach ( self::FIELD_MAPPING_KEYS as $mapping_key ) {
					if ( ! array_key_exists( $mapping_key, $settings ) ) {
						continue;
					}
					$form_row[ $mapping_key ] = sanitize_text_field( (string) $settings[ $mapping_key ] );
				}
			}

			$sanitized[ $form_id ] = $form_row;
		}

		return $sanitized;
	}

	/**
	 * Persist sanitized per-form settings.
	 *
	 * @param array<int, array<string, mixed>> $settings Output of sanitize_settings().
	 * @return bool Whether update_option reported success.
	 */
	public static function update_settings( array $settings ) {
		return update_option( self::OPTION_NAME, $settings );
	}

	/**
	 * Merge sanitized rows from admin POST into stored per-form settings.
	 *
	 * Forms omitted from POST keep their previous stored row.
	 *
	 * @param array<int, array<string, mixed>> $incoming Output of sanitize_settings().
	 * @return array<int, array<string, mixed>>
	 */
	public static function merge_sanitized_settings( array $incoming ) {
		$merged = self::get_all_settings();

		foreach ( $incoming as $form_id => $row ) {
			$form_id = (int) $form_id;
			if ( $form_id <= 0 || ! is_array( $row ) ) {
				continue;
			}
			$merged[ $form_id ] = $row;
		}

		return $merged;
	}

	/**
	 * Normalize loose truthy/falsey values to boolean.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	private static function to_bool( $value ) {
		return $value === true || $value === 1 || $value === '1';
	}
}
