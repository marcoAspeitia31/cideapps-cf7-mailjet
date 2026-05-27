<?php

/**
 * CF7 submission data extraction for Mailjet variables.
 *
 * @package    Cideapps_Cf7_Mailjet
 * @subpackage Cideapps_Cf7_Mailjet/includes
 */

/**
 * Builds sanitized field values and Mailjet template variables from a CF7 submission.
 *
 * @package    Cideapps_Cf7_Mailjet
 * @subpackage Cideapps_Cf7_Mailjet/includes
 */
class Cideapps_Cf7_Mailjet_Submission_Data {

	/**
	 * Cached persisted upload URLs for the current request (field => URLs).
	 *
	 * @since 1.3.0
	 * @var array<string, string[]>
	 */
	private $persisted_upload_cache = array();

	/**
	 * Core mapped field keys sent to Mailjet templates.
	 *
	 * @since 1.2.0
	 * @var string[]
	 */
	const CORE_VARIABLE_KEYS = array( 'name', 'email', 'phone', 'service', 'message', 'form_id' );

	/**
	 * CF7 metadata keys (Etapa 2) — merged when option is enabled.
	 *
	 * @since 1.2.0
	 * @var string[]
	 */
	const METADATA_VARIABLE_KEYS = array(
		'source_url',
		'source_page',
		'submitted_at',
		'user_agent',
		'remote_ip',
		'utm_source',
		'utm_medium',
		'utm_campaign',
		'utm_term',
		'utm_content',
	);

	/**
	 * Extract core mapped fields from posted data.
	 *
	 * @since 1.2.0
	 * @param WPCF7_ContactForm   $contact_form CF7 form.
	 * @param array               $posted_data  Posted data.
	 * @param Cideapps_Cf7_Mailjet_CF7_Handler $handler Handler for label resolution.
	 * @return array Keys: name, email, phone, service, message (strings).
	 */
	public function extract_core_fields( $contact_form, $posted_data, $handler ) {
		$email_field   = get_option( 'cideapps_cf7_mailjet_email_field', 'your-email' );
		$name_field    = get_option( 'cideapps_cf7_mailjet_name_field', 'your-name' );
		$phone_field   = get_option( 'cideapps_cf7_mailjet_phone_field', 'your-phone' );
		$service_field = get_option( 'cideapps_cf7_mailjet_service_field', 'service' );
		$message_field = get_option( 'cideapps_cf7_mailjet_message_field', 'your-message' );

		$email = isset( $posted_data[ $email_field ] ) ? sanitize_email( $posted_data[ $email_field ] ) : '';
		$name  = isset( $posted_data[ $name_field ] ) ? sanitize_text_field( $posted_data[ $name_field ] ) : '';
		$phone = isset( $posted_data[ $phone_field ] ) ? sanitize_text_field( $posted_data[ $phone_field ] ) : '';

		$message = '';
		if ( isset( $posted_data[ $message_field ] ) ) {
			if ( is_array( $posted_data[ $message_field ] ) ) {
				$message = implode( "\n", array_map( 'sanitize_textarea_field', $posted_data[ $message_field ] ) );
			} else {
				$message = sanitize_textarea_field( (string) $posted_data[ $message_field ] );
			}
		}

		$service            = '';
		$service_send_label = get_option( 'cideapps_cf7_mailjet_service_send_label', false );
		$service_raw        = '';

		if ( isset( $posted_data[ $service_field ] ) ) {
			if ( is_array( $posted_data[ $service_field ] ) ) {
				$service_items     = array();
				$service_raw_items = array();

				foreach ( $posted_data[ $service_field ] as $item ) {
					$item_sanitized = sanitize_text_field( $item );
					if ( ! empty( $item_sanitized ) ) {
						$service_raw_items[] = $item_sanitized;
						if ( $service_send_label ) {
							$service_items[] = $handler->resolve_cf7_label_from_value( $contact_form, $service_field, $item_sanitized );
						} else {
							$service_items[] = $item_sanitized;
						}
					}
				}

				$service     = implode( ', ', $service_items );
				$service_raw = implode( ', ', $service_raw_items );
			} else {
				$service     = sanitize_text_field( $posted_data[ $service_field ] );
				$service_raw = $service;

				if ( $service_send_label && ! empty( $service ) ) {
					$service = $handler->resolve_cf7_label_from_value( $contact_form, $service_field, $service );
				}
			}
		}

		return array(
			'email'       => $email,
			'name'        => $name,
			'phone'       => $phone,
			'service'     => $service,
			'message'     => $message,
			'service_raw' => $service_raw,
		);
	}

	/**
	 * Build Mailjet template variables from core fields and optional metadata.
	 *
	 * @since 1.2.0
	 * @param array                $core_fields Core field values.
	 * @param int                  $form_id     CF7 form ID.
	 * @param WPCF7_Submission|null $submission  CF7 submission (for metadata).
	 * @return array Associative array for Mailjet Variables.
	 */
	public function build_template_variables( $core_fields, $form_id, $submission = null ) {
		$variables = array(
			'name'    => isset( $core_fields['name'] ) ? $core_fields['name'] : '',
			'email'   => isset( $core_fields['email'] ) ? $core_fields['email'] : '',
			'phone'   => isset( $core_fields['phone'] ) ? $core_fields['phone'] : '',
			'service' => isset( $core_fields['service'] ) ? $core_fields['service'] : '',
			'message' => isset( $core_fields['message'] ) ? $core_fields['message'] : '',
			'form_id' => (string) (int) $form_id,
		);

		$variables = array_merge( $variables, $this->build_dynamic_template_variables( $submission ) );

		if ( $this->is_submission_metadata_enabled() && $submission instanceof WPCF7_Submission ) {
			$variables = array_merge( $variables, $this->collect_cf7_metadata( $submission ) );
		}

		if ( $this->is_attachment_urls_enabled() && $submission instanceof WPCF7_Submission ) {
			$variables = array_merge( $variables, $this->collect_attachment_template_variables( $submission ) );
		}

		return $this->sanitize_mailjet_variables( $variables );
	}

	/**
	 * Build dynamic variables defined in admin mapping option.
	 *
	 * Mapping format (one per line): cf7_field:mailjet_key
	 * Also supports basic CF7 special mail-tags in source field, e.g. [_remote_ip].
	 *
	 * @since 1.2.0
	 * @param WPCF7_Submission|null $submission CF7 submission.
	 * @return array
	 */
	public function build_dynamic_template_variables( $submission = null ) {
		if ( ! ( $submission instanceof WPCF7_Submission ) ) {
			return array();
		}

		$mappings = $this->parse_dynamic_mappings_option();
		if ( empty( $mappings ) ) {
			return array();
		}

		$posted_data = $submission->get_posted_data();
		$variables   = array();

		foreach ( $mappings as $mapping ) {
			$source_key  = $mapping['source'];
			$target_key  = $mapping['target'];
			$raw_value   = '';

			if ( $this->is_special_mail_tag( $source_key ) ) {
				$raw_value = $this->resolve_special_mail_tag_value( $submission, $source_key );
			} elseif ( isset( $posted_data[ $source_key ] ) ) {
				$raw_value = $posted_data[ $source_key ];
			}

			if ( is_array( $raw_value ) ) {
				$raw_value = implode( ', ', array_map( 'sanitize_text_field', $raw_value ) );
			}

			$raw_value = (string) $raw_value;
			if ( '' === trim( $raw_value ) ) {
				continue;
			}

			$variables[ $target_key ] = $raw_value;
		}

		return $this->sanitize_mailjet_variables( $variables );
	}

	/**
	 * Parse dynamic mappings from admin option.
	 *
	 * Each line format: source:target
	 * Example: your-company:company
	 *
	 * @since 1.2.0
	 * @return array[] List of mappings with source and target.
	 */
	private function parse_dynamic_mappings_option() {
		return $this->parse_line_mappings_option( 'cideapps_cf7_mailjet_dynamic_mappings' );
	}

	/**
	 * Check whether source is a CF7 special mail-tag.
	 *
	 * @since 1.2.0
	 * @param string $source Source token.
	 * @return bool
	 */
	private function is_special_mail_tag( $source ) {
		return (bool) preg_match( '/^\[_[a-z0-9_]+\]$/i', (string) $source );
	}

	/**
	 * Resolve supported CF7 special mail-tags from submission metadata.
	 *
	 * @since 1.2.0
	 * @param WPCF7_Submission $submission CF7 submission.
	 * @param string           $tag        Special mail-tag.
	 * @return string
	 */
	private function resolve_special_mail_tag_value( $submission, $tag ) {
		switch ( $tag ) {
			case '[_remote_ip]':
				return (string) $submission->get_meta( 'remote_ip' );
			case '[_user_agent]':
				return (string) $submission->get_meta( 'user_agent' );
			case '[_url]':
				return (string) $submission->get_meta( 'url' );
			case '[_date]':
				return sanitize_text_field( wp_date( get_option( 'date_format' ) ) );
			case '[_time]':
				return sanitize_text_field( wp_date( get_option( 'time_format' ) ) );
			default:
				return '';
		}
	}

	/**
	 * Whether to include CF7 submission metadata in Mailjet variables.
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	public function is_submission_metadata_enabled() {
		$raw = get_option( 'cideapps_cf7_mailjet_enable_submission_metadata', 0 );
		return ( $raw === 1 || $raw === '1' || $raw === true );
	}

	/**
	 * Whether to persist CF7 uploads and expose public URLs in Mailjet variables.
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	public function is_attachment_urls_enabled() {
		$raw = get_option( 'cideapps_cf7_mailjet_enable_attachment_urls', 0 );
		return ( $raw === 1 || $raw === '1' || $raw === true );
	}

	/**
	 * Collect attachment URL variables for Mailjet templates.
	 *
	 * Copies files from CF7 temp storage to uploads/cideapps-cf7-mailjet/
	 * so links remain available after CF7 deletes temp files.
	 *
	 * @since 1.3.0
	 * @param WPCF7_Submission $submission CF7 submission.
	 * @return array
	 */
	public function collect_attachment_template_variables( $submission ) {
		$uploaded_files = $submission->uploaded_files();
		if ( empty( $uploaded_files ) || ! is_array( $uploaded_files ) ) {
			return array();
		}

		$mappings       = $this->parse_attachment_mappings_option();
		$variables      = array();
		$all_urls       = array();
		$field_url_map  = $this->persist_uploaded_files_map( $uploaded_files );

		if ( empty( $field_url_map ) ) {
			return array();
		}

		if ( empty( $mappings ) ) {
			foreach ( $field_url_map as $field_name => $urls ) {
				$key = sanitize_key( $field_name . '_url' );
				if ( '' === $key ) {
					continue;
				}
				$variables[ $key ] = $this->join_urls( $urls );
				$all_urls          = array_merge( $all_urls, $urls );
			}
		} else {
			foreach ( $mappings as $mapping ) {
				$field_name = $mapping['source'];
				$target_key = $mapping['target'];

				if ( ! isset( $field_url_map[ $field_name ] ) ) {
					continue;
				}

				$variables[ $target_key ] = $this->join_urls( $field_url_map[ $field_name ] );
				$all_urls                 = array_merge( $all_urls, $field_url_map[ $field_name ] );
			}
		}

		if ( ! empty( $all_urls ) ) {
			$variables['attachments_all'] = $this->join_urls( array_values( array_unique( $all_urls ) ) );
		}

		return $this->sanitize_mailjet_variables( $variables );
	}

	/**
	 * Get persisted public URLs grouped by CF7 file field name.
	 *
	 * @since 1.3.0
	 * @param WPCF7_Submission $submission CF7 submission.
	 * @return array<string, string[]> Field name => list of URLs.
	 */
	public function get_persisted_attachment_urls_by_field( $submission ) {
		$uploaded_files = $submission->uploaded_files();
		if ( empty( $uploaded_files ) || ! is_array( $uploaded_files ) ) {
			return array();
		}

		return $this->persist_uploaded_files_map( $uploaded_files );
	}

	/**
	 * Persist uploaded files and return their public URLs by field.
	 *
	 * @since 1.3.0
	 * @param array $uploaded_files CF7 uploaded_files() array.
	 * @return array<string, string[]>
	 */
	private function persist_uploaded_files_map( $uploaded_files ) {
		if ( ! empty( $this->persisted_upload_cache ) ) {
			return $this->persisted_upload_cache;
		}

		$map = array();

		foreach ( $uploaded_files as $field_name => $paths ) {
			$field_name = (string) $field_name;
			$urls       = array();

			foreach ( (array) $paths as $path ) {
				$url = $this->persist_cf7_upload( (string) $path );
				if ( ! empty( $url ) ) {
					$urls[] = $url;
				}
			}

			if ( ! empty( $urls ) ) {
				$map[ $field_name ] = $urls;
			}
		}

		$this->persisted_upload_cache = $map;

		return $map;
	}

	/**
	 * Copy a CF7 temp upload into a persistent public uploads subdirectory.
	 *
	 * @since 1.3.0
	 * @param string $file_path Absolute path to uploaded file.
	 * @return string Public URL or empty string on failure.
	 */
	private function persist_cf7_upload( $file_path ) {
		if ( empty( $file_path ) || ! @is_file( $file_path ) || ! @is_readable( $file_path ) ) {
			return '';
		}

		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return '';
		}

		$subdir      = '/cideapps-cf7-mailjet/' . gmdate( 'Y/m' );
		$target_dir  = $upload_dir['basedir'] . $subdir;
		wp_mkdir_p( $target_dir );

		$filename    = wp_unique_filename( $target_dir, wp_basename( $file_path ) );
		$target_path = trailingslashit( $target_dir ) . $filename;

		if ( ! @copy( $file_path, $target_path ) ) {
			return '';
		}

		// Restrict direct PHP execution in upload folder.
		$this->maybe_add_uploads_htaccess( $upload_dir['basedir'] . '/cideapps-cf7-mailjet' );

		return esc_url_raw( trailingslashit( $upload_dir['baseurl'] . $subdir ) . $filename );
	}

	/**
	 * Add .htaccess to block PHP execution in plugin upload directory (Apache).
	 *
	 * @since 1.3.0
	 * @param string $dir Directory path.
	 * @return void
	 */
	private function maybe_add_uploads_htaccess( $dir ) {
		if ( ! wp_mkdir_p( $dir ) ) {
			return;
		}

		$htaccess = trailingslashit( $dir ) . '.htaccess';
		if ( file_exists( $htaccess ) ) {
			return;
		}

		$rules = "<Files *>\n  Require all denied\n</Files>\n<FilesMatch \"\\.(?i:pdf|jpe?g|png|gif|webp|docx?|xlsx?|txt|csv|zip)$\">\n  Require all granted\n</FilesMatch>\n";
		@file_put_contents( $htaccess, $rules ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	/**
	 * Parse attachment field mappings from admin option.
	 *
	 * @since 1.3.0
	 * @return array[] List of mappings with source and target.
	 */
	private function parse_attachment_mappings_option() {
		return $this->parse_line_mappings_option( 'cideapps_cf7_mailjet_attachment_mappings' );
	}

	/**
	 * Parse line-based mappings stored as "source:target" per line.
	 *
	 * @since 1.3.0
	 * @param string $option_name Option key.
	 * @return array[]
	 */
	private function parse_line_mappings_option( $option_name ) {
		$raw = get_option( $option_name, '' );
		if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
			return array();
		}

		$lines    = preg_split( '/\r\n|\r|\n/', $raw );
		$mappings = array();

		foreach ( $lines as $line ) {
			$line = trim( (string) $line );
			if ( '' === $line ) {
				continue;
			}

			$parts = explode( ':', $line, 2 );
			if ( count( $parts ) !== 2 ) {
				continue;
			}

			$source = trim( $parts[0] );
			$target = sanitize_key( trim( $parts[1] ) );

			if ( '' === $source || '' === $target ) {
				continue;
			}

			$mappings[] = array(
				'source' => $source,
				'target' => $target,
			);
		}

		return $mappings;
	}

	/**
	 * Join multiple URLs for Mailjet variable value.
	 *
	 * @since 1.3.0
	 * @param string[] $urls URLs.
	 * @return string
	 */
	private function join_urls( $urls ) {
		$urls = array_values( array_filter( array_map( 'esc_url_raw', (array) $urls ) ) );
		return implode( "\n", $urls );
	}

	/**
	 * Collect CF7 submission metadata for Mailjet (URL, page, date, browser, IP, UTM).
	 *
	 * @since 1.2.0
	 * @param WPCF7_Submission $submission CF7 submission instance.
	 * @return array
	 */
	public function collect_cf7_metadata( $submission ) {
		$meta = array();

		$source_url = (string) $submission->get_meta( 'url' );
		$meta['source_url'] = esc_url_raw( $source_url );

		$container_post_id = (int) $submission->get_meta( 'container_post_id' );
		if ( $container_post_id > 0 ) {
			$meta['source_page'] = sanitize_text_field( get_the_title( $container_post_id ) );
			$permalink           = get_permalink( $container_post_id );
			if ( $permalink ) {
				$meta['source_page'] .= ' (' . esc_url_raw( $permalink ) . ')';
			}
		} else {
			$meta['source_page'] = $meta['source_url'];
		}

		$timestamp = (int) $submission->get_meta( 'timestamp' );
		if ( $timestamp > 0 ) {
			$meta['submitted_at'] = sanitize_text_field(
				wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp )
			);
		} else {
			$meta['submitted_at'] = sanitize_text_field( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) );
		}

		$meta['user_agent'] = sanitize_text_field( (string) $submission->get_meta( 'user_agent' ) );
		$meta['remote_ip']  = $this->mask_ip_for_display( (string) $submission->get_meta( 'remote_ip' ) );

		$utm = $this->parse_utm_from_url( $source_url );
		$meta = array_merge( $meta, $utm );

		return $this->sanitize_mailjet_variables( $meta );
	}

	/**
	 * Parse UTM parameters from a URL.
	 *
	 * @since 1.2.0
	 * @param string $url Request URL.
	 * @return array
	 */
	private function parse_utm_from_url( $url ) {
		$utm = array(
			'utm_source'   => '',
			'utm_medium'   => '',
			'utm_campaign' => '',
			'utm_term'     => '',
			'utm_content'  => '',
		);

		if ( empty( $url ) ) {
			return $utm;
		}

		$query = wp_parse_url( $url, PHP_URL_QUERY );
		if ( empty( $query ) ) {
			return $utm;
		}

		parse_str( $query, $params );

		foreach ( array_keys( $utm ) as $key ) {
			if ( ! empty( $params[ $key ] ) ) {
				$utm[ $key ] = sanitize_text_field( (string) $params[ $key ] );
			}
		}

		return $utm;
	}

	/**
	 * Mask IP for display (privacy-friendly partial IP).
	 *
	 * @since 1.2.0
	 * @param string $ip IP address.
	 * @return string
	 */
	private function mask_ip_for_display( $ip ) {
		if ( empty( $ip ) ) {
			return '';
		}

		if ( false !== strpos( $ip, ':' ) ) {
			$parts = explode( ':', $ip );
			$last  = array_pop( $parts );
			return implode( ':', $parts ) . ':****';
		}

		$parts = explode( '.', $ip );
		if ( count( $parts ) === 4 ) {
			$parts[3] = 'xxx';
			return implode( '.', $parts );
		}

		return sanitize_text_field( $ip );
	}

	/**
	 * Sanitize variables for Mailjet API (preserve line breaks in message).
	 *
	 * @since 1.2.0
	 * @param array $variables Raw variables.
	 * @return array
	 */
	public function sanitize_mailjet_variables( $variables ) {
		$sanitized = array();

		foreach ( $variables as $key => $value ) {
			$key = sanitize_key( $key );
			if ( '' === $key ) {
				continue;
			}

			if ( 'message' === $key ) {
				$sanitized[ $key ] = sanitize_textarea_field( (string) $value );
			} elseif ( $this->is_url_variable_key( $key ) || $this->value_looks_like_url( $value ) ) {
				$sanitized[ $key ] = $this->sanitize_url_variable_value( (string) $value );
			} else {
				$sanitized[ $key ] = sanitize_text_field( (string) $value );
			}
		}

		return $sanitized;
	}

	/**
	 * Whether a Mailjet variable key should be treated as URL content.
	 *
	 * @since 1.3.0
	 * @param string $key Variable key.
	 * @return bool
	 */
	private function is_url_variable_key( $key ) {
		if ( in_array( $key, array( 'attachments_all', 'attachment_url', 'attachment_urls' ), true ) ) {
			return true;
		}

		return (bool) preg_match( '/_url(s)?$/', (string) $key );
	}

	/**
	 * Whether a value looks like a URL (single or multiline URLs).
	 *
	 * @since 1.3.0
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	private function sanitize_url_variable_value( $value ) {
		$lines = preg_split( '/\r\n|\r|\n/', $value );
		$urls  = array();

		foreach ( (array) $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$url = esc_url_raw( $line );
			if ( ! empty( $url ) ) {
				$urls[] = $url;
			}
		}

		return implode( "\n", $urls );
	}

	/**
	 * Whether a value looks like a URL (single or multiline URLs).
	 *
	 * @since 1.3.0
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	private function value_looks_like_url( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return false;
		}

		$lines = preg_split( '/\r\n|\r|\n/', $value );
		foreach ( (array) $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			if ( ! filter_var( $line, FILTER_VALIDATE_URL ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Build contact list properties (excludes long message body).
	 *
	 * @since 1.2.0
	 * @param array $core_fields Core fields from extract_core_fields().
	 * @return array
	 */
	public function build_contact_properties( $core_fields ) {
		$properties = array();

		if ( ! empty( $core_fields['name'] ) ) {
			$properties['name'] = sanitize_text_field( $core_fields['name'] );
		}
		if ( ! empty( $core_fields['phone'] ) ) {
			$properties['phone'] = sanitize_text_field( $core_fields['phone'] );
		}
		if ( ! empty( $core_fields['service'] ) ) {
			$properties['service'] = sanitize_text_field( $core_fields['service'] );
		}

		return $properties;
	}
}
