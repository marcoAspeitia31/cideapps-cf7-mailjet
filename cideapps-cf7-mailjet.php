<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://cideapps.com
 * @since             1.0.0
 * @package           Cideapps_Cf7_Mailjet
 *
 * @wordpress-plugin
 * Plugin Name:       Cideapps CF7 Mailjet
 * Plugin URI:        https://cideapps.com
 * Description:       Plugin para conectar CF7 con un autoreplay de Mailjet
 * Version:           1.0.0
 * Author:            CIDEAPPS DIGITAL
 * Author URI:        https://cideapps.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cideapps-cf7-mailjet
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'CIDEAPPS_CF7_MAILJET_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-cideapps-cf7-mailjet-activator.php
 */
function activate_cideapps_cf7_mailjet() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-cideapps-cf7-mailjet-activator.php';
	Cideapps_Cf7_Mailjet_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-cideapps-cf7-mailjet-deactivator.php
 */
function deactivate_cideapps_cf7_mailjet() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-cideapps-cf7-mailjet-deactivator.php';
	Cideapps_Cf7_Mailjet_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_cideapps_cf7_mailjet' );
register_deactivation_hook( __FILE__, 'deactivate_cideapps_cf7_mailjet' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-cideapps-cf7-mailjet.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_cideapps_cf7_mailjet() {

	$plugin = new Cideapps_Cf7_Mailjet();
	$plugin->run();

}
run_cideapps_cf7_mailjet();
