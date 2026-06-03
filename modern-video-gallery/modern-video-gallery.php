<?php
/**
 * Plugin Name: Modern Video Gallery
 * Description: A professional video gallery plugin with a modern, minimal, and elegant UI.
 * Version: 1.0.0
 * Author: Jules
 * Text Domain: modern-video-gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MVG_VERSION', '1.0.0' );
define( 'MVG_PATH', plugin_dir_path( __FILE__ ) );
define( 'MVG_URL', plugin_dir_url( __FILE__ ) );

// Load dependencies
require_once MVG_PATH . 'includes/class-mvg-loader.php';
require_once MVG_PATH . 'includes/class-mvg-activator.php';
require_once MVG_PATH . 'includes/class-mvg-deactivator.php';
require_once MVG_PATH . 'includes/class-mvg-admin.php';
require_once MVG_PATH . 'includes/class-mvg-public.php';
require_once MVG_PATH . 'includes/class-mvg-api.php';

function run_modern_video_gallery() {
	$loader = new MVG_Loader();

	$activator = new MVG_Activator();
	register_activation_hook( __FILE__, array( $activator, 'activate' ) );

	$deactivator = new MVG_Deactivator();
	register_deactivation_hook( __FILE__, array( $deactivator, 'deactivate' ) );

	$admin = new MVG_Admin( MVG_VERSION );
	$loader->add_action( 'admin_menu', $admin, 'add_plugin_admin_menu' );
	$loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
	$loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );

	$api = new MVG_API();
	$loader->add_action( 'rest_api_init', $api, 'register_routes' );

	$public = new MVG_Public( MVG_VERSION );
	$loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_styles' );
	$loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_scripts' );
	$loader->add_shortcode( 'video_gallery', $public, 'render_gallery' );

	$loader->run();
}

run_modern_video_gallery();
