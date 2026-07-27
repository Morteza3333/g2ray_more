<?php
/**
 * Plugin Name:       Admin Messenger Pro
 * Plugin URI:        https://example.com/admin-messenger-pro
 * Description:       An ultra-modern, premium real-time messaging system designed exclusively for WordPress administrators and selected staff members.
 * Version:           1.0.0
 * Author:            Jules
 * Author URI:        https://example.com
 * License:           GPL2
 * Text Domain:       admin-messenger-pro
 * Domain Path:       /languages
 * Requires PHP:      8.0
 * Requires at least: 6.8
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'AMP_VERSION', '1.0.0' );
define( 'AMP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AMP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AMP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Autoload Classes
spl_autoload_register( function ( $class ) {
	$prefix = 'AMP\\';
	$len    = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );
	$parts          = explode( '\\', $relative_class );
	$class_name     = end( $parts );
	if ( strpos( $class_name, 'AMP_' ) === 0 ) {
		$class_name = substr( $class_name, 4 );
	}
	$filename       = 'class-amp-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

	// Remove class name from parts to get subfolders if any
	array_pop( $parts );
	$subpath = '';
	if ( ! empty( $parts ) ) {
		$subpath = implode( '/', array_map( 'strtolower', $parts ) ) . '/';
	}

	$file = AMP_PLUGIN_DIR . 'includes/' . $subpath . $filename;

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

/**
 * Activate the plugin
 */
function activate_admin_messenger_pro() {
	require_once AMP_PLUGIN_DIR . 'includes/class-amp-activator.php';
	\AMP\AMP_Activator::activate();
}
register_activation_hook( __FILE__, 'activate_admin_messenger_pro' );

/**
 * Deactivate the plugin
 */
function deactivate_admin_messenger_pro() {
	// Optional deactivation logic
}
register_deactivation_hook( __FILE__, 'deactivate_admin_messenger_pro' );

/**
 * Initialize Plugin Core
 */
function init_admin_messenger_pro() {
	// Initialize core components
	\AMP\AMP_Admin::get_instance();
	\AMP\AMP_API::get_instance();
}
add_action( 'plugins_loaded', 'init_admin_messenger_pro' );
