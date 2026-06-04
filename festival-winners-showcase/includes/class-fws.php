<?php

class FWS {
	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		$this->plugin_name = 'festival-winners-showcase';
		$this->version = FWS_VERSION;

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_api_hooks();
	}

	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fws-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fws-i18n.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fws-settings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-fws-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-fws-public.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fws-api.php';

		$this->loader = new FWS_Loader();
	}

	private function set_locale() {
		$plugin_i18n = new FWS_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
		$this->loader->add_action( 'init', $plugin_i18n, 'register_post_types' );
	}

	private function define_admin_hooks() {
		$plugin_admin = new FWS_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_winner_meta_boxes' );
		$this->loader->add_action( 'save_post', $plugin_admin, 'save_winner_meta' );
	}

	private function define_public_hooks() {
		$plugin_public = new FWS_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );
	}

	private function define_api_hooks() {
		$plugin_api = new FWS_API();
		$this->loader->add_action( 'rest_api_init', $plugin_api, 'register_routes' );
	}

	public function run() {
		$this->loader->run();
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_loader() {
		return $this->loader;
	}

	public function get_version() {
		return $this->version;
	}
}
