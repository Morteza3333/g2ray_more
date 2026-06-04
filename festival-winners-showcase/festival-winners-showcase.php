<?php
/**
 * Plugin Name: Festival Winners Showcase
 * Description: A premium WordPress plugin to showcase festival winners with high-end animations and a justified gallery layout.
 * Version: 1.0.0
 * Author: Jules
 * Text Domain: festival-winners-showcase
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define constants
define( 'FWS_VERSION', '1.0.0' );
define( 'FWS_PATH', plugin_dir_path( __FILE__ ) );
define( 'FWS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main Plugin Class
 */
class Festival_Winners_Showcase {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->init();
	}

	private function init() {
		// Load includes
		$this->includes();

		// Register activation and deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	private function includes() {
		require_once FWS_PATH . 'includes/class-fws-post-types.php';
		require_once FWS_PATH . 'includes/class-fws-meta-boxes.php';
		require_once FWS_PATH . 'includes/class-fws-settings.php';
		require_once FWS_PATH . 'includes/class-fws-shortcode.php';
	}

	public function activate() {
		// Activation logic (e.g., flush rewrite rules)
	}

	public function deactivate() {
		// Deactivation logic
	}
}

// Initialize the plugin
Festival_Winners_Showcase::get_instance();
