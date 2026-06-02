<?php

/**
 * Reads Contact Form 7 form tags for admin field mapping dropdowns.
 *
 * @package    Cideapps_Cf7_Mailjet
 * @subpackage Cideapps_Cf7_Mailjet/includes
 */

/**
 * Collects named CF7 fields and renders mapping selects in admin.
 *
 * @package    Cideapps_Cf7_Mailjet
 * @subpackage Cideapps_Cf7_Mailjet/includes
 */
class Cideapps_Cf7_Mailjet_Cf7_Field_Selector {

	/**
	 * CF7 tag types without a mappable field name (excluded from dropdowns).
	 *
	 * @var string[]
	 */
	const EXCLUDED_TAG_TYPES = array(
		'submit',
		'captcha',
		'acceptance',
		'quiz',
		'response',
		'count',
		'hidden',
	);

	/**
	 * Whether CF7 is available for tag scanning.
	 *
	 * @return bool
	 */
	public static function is_cf7_available() {
		return class_exists( 'WPCF7_ContactForm' );
	}

	/**
	 * Form IDs used as the tag source: enabled forms first, otherwise all known forms.
	 *
	 * @param int[] $enabled_form_ids Form IDs with the plugin checkbox enabled.
	 * @param int[] $all_form_ids     All CF7 form IDs on the site.
	 * @return int[]
	 */
	public static function resolve_source_form_ids( array $enabled_form_ids, array $all_form_ids ) {
		$enabled = array_values(
			array_filter(
				array_map( 'intval', $enabled_form_ids ),
				static function ( $id ) {
					return $id > 0;
				}
			)
		);

		if ( ! empty( $enabled ) ) {
			return $enabled;
		}

		return array_values(
			array_filter(
				array_map( 'intval', $all_form_ids ),
				static function ( $id ) {
					return $id > 0;
				}
			)
		);
	}

	/**
	 * Unique selectable fields from the given CF7 forms.
	 *
	 * @param int[] $form_ids CF7 contact form post IDs.
	 * @return array<int, array{name: string, type: string, basetype: string}>
	 */
	public static function collect_fields_for_form_ids( array $form_ids ) {
		if ( ! self::is_cf7_available() ) {
			return array();
		}

		$by_name = array();

		foreach ( $form_ids as $form_id ) {
			$form_id = (int) $form_id;
			if ( $form_id <= 0 ) {
				continue;
			}

			$contact_form = WPCF7_ContactForm::get_instance( $form_id );
			if ( ! $contact_form ) {
				continue;
			}

			$tags = $contact_form->scan_form_tags();
			if ( empty( $tags ) || ! is_array( $tags ) ) {
				continue;
			}

			foreach ( $tags as $tag ) {
				if ( ! self::is_selectable_tag( $tag ) ) {
					continue;
				}

				$name = (string) $tag->name;
				if ( isset( $by_name[ $name ] ) ) {
					continue;
				}

				$type     = isset( $tag->type ) ? (string) $tag->type : '';
				$basetype = isset( $tag->basetype ) ? (string) $tag->basetype : $type;

				$by_name[ $name ] = array(
					'name'     => $name,
					'type'     => $type,
					'basetype' => $basetype,
				);
			}
		}

		$fields = array_values( $by_name );
		usort(
			$fields,
			static function ( $a, $b ) {
				return strcmp( $a['name'], $b['name'] );
			}
		);

		return $fields;
	}

	/**
	 * Fields for admin: enabled forms, or all CF7 forms when none are enabled.
	 *
	 * @param int[] $enabled_form_ids Enabled form IDs.
	 * @param int[] $all_form_ids     All CF7 form IDs.
	 * @return array<int, array{name: string, type: string, basetype: string}>
	 */
	public static function collect_fields_for_admin( array $enabled_form_ids, array $all_form_ids ) {
		$source_ids = self::resolve_source_form_ids( $enabled_form_ids, $all_form_ids );
		return self::collect_fields_for_form_ids( $source_ids );
	}

	/**
	 * Human-readable label for a select option.
	 *
	 * @param array{name: string, type: string, basetype: string} $field Field descriptor.
	 * @return string
	 */
	public static function format_option_label( array $field ) {
		$type_label = $field['basetype'] !== '' ? $field['basetype'] : $field['type'];
		if ( '' === $type_label ) {
			return $field['name'];
		}

		return sprintf(
			'%s (%s)',
			$field['name'],
			$type_label
		);
	}

	/**
	 * Description of which forms were scanned for tags.
	 *
	 * @param int[] $enabled_form_ids Enabled form IDs.
	 * @param int[] $source_form_ids  IDs actually scanned.
	 * @return string
	 */
	public static function get_tag_source_description( array $enabled_form_ids, array $source_form_ids ) {
		$enabled = array_filter( array_map( 'intval', $enabled_form_ids ) );

		if ( ! empty( $enabled ) ) {
			return sprintf(
				/* translators: %d: number of enabled CF7 forms. */
				_n(
					'Campos detectados en %d formulario habilitado.',
					'Campos detectados en %d formularios habilitados.',
					count( $source_form_ids ),
					'cideapps-cf7-mailjet'
				),
				count( $source_form_ids )
			);
		}

		if ( empty( $source_form_ids ) ) {
			return __( 'No hay formularios de Contact Form 7 para leer campos.', 'cideapps-cf7-mailjet' );
		}

		return sprintf(
			/* translators: %d: number of CF7 forms. */
			_n(
				'Ningún formulario habilitado: se listan campos de %d formulario CF7 del sitio.',
				'Ningún formulario habilitado: se listan campos de %d formularios CF7 del sitio.',
				count( $source_form_ids ),
				'cideapps-cf7-mailjet'
			),
			count( $source_form_ids )
		);
	}

	/**
	 * Outputs a <select> for a mapping option. Preserves values not present in scanned tags.
	 *
	 * @param string $id            HTML id attribute.
	 * @param string $name            Form input name (option key).
	 * @param string $current_value   Saved option value.
	 * @param array  $fields          Output of collect_fields_for_admin().
	 * @return void
	 */
	public static function render_mapping_select( $id, $name, $current_value, array $fields ) {
		$current_value = (string) $current_value;
		$known_names   = array();

		foreach ( $fields as $field ) {
			$known_names[] = $field['name'];
		}

		printf(
			'<select id="%1$s" name="%2$s" class="regular-text">',
			esc_attr( $id ),
			esc_attr( $name )
		);

		echo '<option value="">' . esc_html__( '— Sin asignar —', 'cideapps-cf7-mailjet' ) . '</option>';

		foreach ( $fields as $field ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $field['name'] ),
				selected( $current_value, $field['name'], false ),
				esc_html( self::format_option_label( $field ) )
			);
		}

		if ( '' !== $current_value && ! in_array( $current_value, $known_names, true ) ) {
			printf(
				'<option value="%1$s" selected="selected">%2$s</option>',
				esc_attr( $current_value ),
				esc_html(
					sprintf(
						/* translators: %s: CF7 field name stored in options. */
						__( '%s (valor guardado)', 'cideapps-cf7-mailjet' ),
						$current_value
					)
				)
			);
		}

		echo '</select>';
	}

	/**
	 * Renders a per-form mapping select (tags from a single CF7 form only).
	 *
	 * @param int    $form_id       CF7 contact form post ID.
	 * @param string $mapping_key   One of Form_Settings::FIELD_MAPPING_KEYS.
	 * @param string $current_value Value to preselect.
	 * @return void
	 */
	public static function render_form_mapping_select( $form_id, $mapping_key, $current_value ) {
		$form_id = (int) $form_id;
		if ( $form_id <= 0 || ! class_exists( 'Cideapps_Cf7_Mailjet_Form_Settings' ) ) {
			return;
		}

		if ( ! Cideapps_Cf7_Mailjet_Form_Settings::is_valid_field_mapping_key( $mapping_key ) ) {
			return;
		}

		$fields = self::collect_fields_for_form_ids( array( $form_id ) );
		$name   = sprintf(
			'%s[%d][%s]',
			Cideapps_Cf7_Mailjet_Form_Settings::OPTION_NAME,
			$form_id,
			$mapping_key
		);
		$id     = sprintf(
			'%s_%d_%s',
			Cideapps_Cf7_Mailjet_Form_Settings::OPTION_NAME,
			$form_id,
			$mapping_key
		);

		self::render_mapping_select( $id, $name, (string) $current_value, $fields );
	}

	/**
	 * Whether a CF7 form tag should appear in mapping dropdowns.
	 *
	 * @param object $tag WPCF7_FormTag instance.
	 * @return bool
	 */
	private static function is_selectable_tag( $tag ) {
		if ( ! is_object( $tag ) || empty( $tag->name ) ) {
			return false;
		}

		$type     = isset( $tag->type ) ? (string) $tag->type : '';
		$basetype = isset( $tag->basetype ) ? (string) $tag->basetype : $type;

		if ( in_array( $type, self::EXCLUDED_TAG_TYPES, true ) ) {
			return false;
		}

		if ( $basetype !== '' && in_array( $basetype, self::EXCLUDED_TAG_TYPES, true ) ) {
			return false;
		}

		return true;
	}
}
