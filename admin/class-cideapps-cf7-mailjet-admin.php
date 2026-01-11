<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://cideapps.com
 * @since      1.0.0
 *
 * @package    Cideapps_Cf7_Mailjet
 * @subpackage Cideapps_Cf7_Mailjet/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Cideapps_Cf7_Mailjet
 * @subpackage Cideapps_Cf7_Mailjet/admin
 * @author     CIDEAPPS DIGITAL <contacto@cideapps.com>
 */
class Cideapps_Cf7_Mailjet_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Cideapps_Cf7_Mailjet_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Cideapps_Cf7_Mailjet_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/cideapps-cf7-mailjet-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Cideapps_Cf7_Mailjet_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Cideapps_Cf7_Mailjet_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/cideapps-cf7-mailjet-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Register the settings page menu
	 *
	 * @since    1.0.0
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'CF7 Mailjet', 'cideapps-cf7-mailjet' ),
			__( 'CF7 Mailjet', 'cideapps-cf7-mailjet' ),
			'manage_options',
			'cideapps-cf7-mailjet',
			array( $this, 'display_settings_page' )
		);
	}

	/**
	 * Display the settings page
	 *
	 * @since    1.0.0
	 */
	public function display_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		require_once plugin_dir_path( __FILE__ ) . 'partials/cideapps-cf7-mailjet-admin-display.php';
	}

	/**
	 * Register settings
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		// Mailjet credentials
		register_setting( 'cideapps_cf7_mailjet_settings', 'cideapps_cf7_mailjet_public_key', 'sanitize_text_field' );
		register_setting( 'cideapps_cf7_mailjet_settings', 'cideapps_cf7_mailjet_private_key', 'sanitize_text_field' );
		register_setting( 'cideapps_cf7_mailjet_settings', 'cideapps_cf7_mailjet_from_email', 'sanitize_email' );
		register_setting( 'cideapps_cf7_mailjet_settings', 'cideapps_cf7_mailjet_from_name', 'sanitize_text_field' );

		// Autoreply
		register_setting( 'cideapps_cf7_mailjet_settings', 'cideapps_cf7_mailjet_enable_autoreply', array( $this, 'sanitize_checkbox' ) );
		register_setting( 'cideapps_cf7_mailjet_settings', 'cideapps_cf7_mailjet_template_id', 'intval' );

		// Contact list
		register_setting( 'cideapps_cf7_mailjet_settings', 'cideapps_cf7_mailjet_enable_contact_list', array( $this, 'sanitize_checkbox' ) );
		register_setting( 'cideapps_cf7_mailjet_settings', 'cideapps_cf7_mailjet_list_id', 'intval' );
		register_setting( 'cideapps_cf7_mailjet_settings', 'cideapps_cf7_mailjet_on_existing_contact', 'sanitize_text_field' );

		// CF7
		register_setting( 'cideapps_cf7_mailjet_settings', 'cideapps_cf7_mailjet_enabled_form_ids', array( $this, 'sanitize_array' ) );
		register_setting( 'cideapps_cf7_mailjet_settings', 'cideapps_cf7_mailjet_email_field', 'sanitize_text_field' );
		register_setting( 'cideapps_cf7_mailjet_settings', 'cideapps_cf7_mailjet_name_field', 'sanitize_text_field' );
		register_setting( 'cideapps_cf7_mailjet_settings', 'cideapps_cf7_mailjet_phone_field', 'sanitize_text_field' );
		register_setting( 'cideapps_cf7_mailjet_settings', 'cideapps_cf7_mailjet_service_field', 'sanitize_text_field' );

		// Security
		register_setting( 'cideapps_cf7_mailjet_settings', 'cideapps_cf7_mailjet_rate_limit_email_minutes', 'intval' );
		register_setting( 'cideapps_cf7_mailjet_settings', 'cideapps_cf7_mailjet_rate_limit_ip_minutes', 'intval' );
		register_setting( 'cideapps_cf7_mailjet_settings', 'cideapps_cf7_mailjet_debug_logs', array( $this, 'sanitize_checkbox' ) );
	}

	/**
	 * Sanitize checkbox value
	 *
	 * @since    1.0.0
	 * @param    mixed    $value    Input value
	 * @return   bool     Sanitized checkbox value
	 */
	public function sanitize_checkbox( $value ) {
		return isset( $value ) && $value === '1';
	}

	/**
	 * Sanitize array value
	 *
	 * @since    1.0.0
	 * @param    mixed    $value    Input value
	 * @return   array    Sanitized array value
	 */
	public function sanitize_array( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}
		return array_map( 'intval', $value );
	}

}
