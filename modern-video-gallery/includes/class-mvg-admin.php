<?php
/**
 * The admin-specific functionality of the plugin.
 */
class MVG_Admin {
	private $version;

	public function __construct( $version ) {
		$this->version = $version;
	}

	public function enqueue_styles() {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'mvg-admin-css', MVG_URL . 'admin/css/mvg-admin.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts() {
		wp_enqueue_media();
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'mvg-admin-js', MVG_URL . 'admin/js/mvg-admin.js', array( 'jquery', 'wp-color-picker', 'jquery-ui-sortable' ), $this->version, false );
		wp_localize_script( 'mvg-admin-js', 'mvg_admin_vars', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'rest_url' => get_rest_url( null, 'mvg/v1' ),
		) );
	}

	public function add_plugin_admin_menu() {
		add_menu_page(
			'Modern Video Gallery',
			'Video Gallery',
			'manage_options',
			'modern-video-gallery',
			array( $this, 'display_plugin_admin_page' ),
			'dashicons-video-alt3',
			25
		);

		add_submenu_page(
			'modern-video-gallery',
			'Videos',
			'All Videos',
			'manage_options',
			'modern-video-gallery',
			array( $this, 'display_plugin_admin_page' )
		);

		add_submenu_page(
			'modern-video-gallery',
			'Settings',
			'Settings',
			'manage_options',
			'mvg-settings',
			array( $this, 'display_plugin_settings_page' )
		);
	}

	public function display_plugin_admin_page() {
		require_once MVG_PATH . 'admin/partials/mvg-admin-display.php';
	}

	public function display_plugin_settings_page() {
		require_once MVG_PATH . 'admin/partials/mvg-settings-display.php';
	}
}
