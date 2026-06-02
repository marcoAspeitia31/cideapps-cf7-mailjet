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
	public function enqueue_styles( $hook_suffix = '' ) {
		if ( 'settings_page_cideapps-cf7-mailjet' !== $hook_suffix ) {
			return;
		}

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
	public function enqueue_scripts( $hook_suffix = '' ) {
		if ( 'settings_page_cideapps-cf7-mailjet' !== $hook_suffix ) {
			return;
		}

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
	 * Note: This method is kept for potential future use with WordPress Settings API.
	 * Currently, settings are processed manually in the display file.
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		if ( 'POST' !== strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		if ( 'cideapps-cf7-mailjet' !== $page || 'forms' !== $tab ) {
			return;
		}

		if ( ! isset( $_POST['cideapps_cf7_mailjet_reset_form_id'] ) ) {
			return;
		}

		$redirect_url = add_query_arg(
			array(
				'page' => 'cideapps-cf7-mailjet',
				'tab'  => 'forms',
			),
			admin_url( 'options-general.php' )
		);

		if ( ! current_user_can( 'manage_options' ) ) {
			$redirect_url = add_query_arg( 'cideapps_cf7_mailjet_notice', 'reset_forbidden', $redirect_url );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		$nonce_valid = isset( $_POST['cideapps_cf7_mailjet_reset_form_nonce'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cideapps_cf7_mailjet_reset_form_nonce'] ) ), 'cideapps_cf7_mailjet_reset_form' );
		if ( ! $nonce_valid ) {
			$redirect_url = add_query_arg( 'cideapps_cf7_mailjet_notice', 'reset_invalid_nonce', $redirect_url );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		$reset_form_id = (int) wp_unslash( $_POST['cideapps_cf7_mailjet_reset_form_id'] );
		if ( $reset_form_id <= 0 ) {
			$redirect_url = add_query_arg( 'cideapps_cf7_mailjet_notice', 'reset_invalid_form', $redirect_url );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		$enabled_form_ids = get_option( 'cideapps_cf7_mailjet_enabled_form_ids', array() );
		$enabled_form_ids = is_array( $enabled_form_ids ) ? array_map( 'intval', $enabled_form_ids ) : array();
		$enabled_form_ids = array_values(
			array_filter(
				$enabled_form_ids,
				static function ( $form_id ) use ( $reset_form_id ) {
					return (int) $form_id !== $reset_form_id;
				}
			)
		);
		update_option( 'cideapps_cf7_mailjet_enabled_form_ids', $enabled_form_ids );

		$form_mail_modes = get_option( 'cideapps_cf7_mailjet_form_mail_modes', array() );
		$form_mail_modes = is_array( $form_mail_modes ) ? $form_mail_modes : array();
		unset( $form_mail_modes[ $reset_form_id ] );
		update_option( 'cideapps_cf7_mailjet_form_mail_modes', $form_mail_modes );

		$form_settings = get_option( 'cideapps_cf7_mailjet_form_settings', array() );
		$form_settings = is_array( $form_settings ) ? $form_settings : array();
		unset( $form_settings[ $reset_form_id ] );
		update_option( 'cideapps_cf7_mailjet_form_settings', $form_settings );

		$redirect_url = add_query_arg( 'cideapps_cf7_mailjet_notice', 'reset_success', $redirect_url );
		wp_safe_redirect( $redirect_url );
		exit;
	}

}
