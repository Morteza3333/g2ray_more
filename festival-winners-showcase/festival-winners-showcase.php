<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://example.com
 * @since             1.0.0
 * @package           Festival_Winners_Showcase
 *
 * @wordpress-plugin
 * Plugin Name:       نمایش برندگان جشنواره عکاسی
 * Plugin URI:        https://example.com/plugin-uri
 * Description:       یک پلاگین حرفه‌ای و مدرن برای نمایش برندگان جشنواره‌های عکاسی با قابلیت‌های پیشرفته و طراحی لوکس.
 * Version:           1.0.0
 * Author:            Jules
 * Author URI:        https://example.com/author-uri
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       festival-winners-showcase
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define( 'FWS_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-fws-activator.php
 */
function activate_festival_winners_showcase() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-fws-activator.php';
	FWS_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-fws-deactivator.php
 */
function deactivate_festival_winners_showcase() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-fws-deactivator.php';
	FWS_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_festival_winners_showcase' );
register_deactivation_hook( __FILE__, 'deactivate_festival_winners_showcase' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-festival-winners-showcase.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks, then kicking off
 * the plugin from this point in the file does not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_festival_winners_showcase() {

	$plugin = new Festival_Winners_Showcase();
	$plugin->run();

}
run_festival_winners_showcase();
