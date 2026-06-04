<?php
/**
 * The plugin bootstrap file
 *
 * @link              https://example.com
 * @since             1.0.0
 * @package           Festival_Winners_Showcase
 *
 * @wordpress-plugin
 * Plugin Name:       Festival Winners Showcase
 * Plugin URI:        https://example.com/plugin
 * Description:       A premium, high-end WordPress plugin for showcasing photography festival winners with a modern SPA admin panel and artistic frontend gallery. Fully localized in Persian.
 * Version:           1.0.0
 * Author:            Jules
 * Author URI:        https://example.com
 * Text Domain:       festival-winners-showcase
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'FWS_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 */
function activate_fws() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-fws-activator.php';
	FWS_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_fws() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-fws-deactivator.php';
	FWS_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_fws' );
register_deactivation_hook( __FILE__, 'deactivate_fws' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-fws.php';

/**
 * Begins execution of the plugin.
 */
function run_fws() {
	$plugin = new FWS();
	$plugin->run();
}
run_fws();
