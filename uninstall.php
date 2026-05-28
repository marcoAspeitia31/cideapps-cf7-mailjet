<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://cideapps.com
 * @package    Cideapps_Cf7_Mailjet
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-cideapps-cf7-mailjet-uninstall.php';

Cideapps_Cf7_Mailjet_Uninstall::run();
