<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FWS_Settings {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function enqueue_assets( $hook ) {
		if ( 'festival-winners_page_fws-settings' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'fws-admin-settings', FWS_URL . 'assets/js/admin-settings.js', array( 'wp-color-picker', 'jquery' ), FWS_VERSION, true );
	}

	public static function add_settings_page() {
		add_submenu_page(
			'edit.php?post_type=winner_entry',
			__( 'FWS Settings', 'festival-winners-showcase' ),
			__( 'Settings', 'festival-winners-showcase' ),
			'manage_options',
			'fws-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function register_settings() {
		register_setting( 'fws_settings_group', 'fws_settings' );

		add_settings_section(
			'fws_appearance_section',
			__( 'Appearance Settings', 'festival-winners-showcase' ),
			null,
			'fws-settings'
		);

		add_settings_field(
			'fws_primary_color',
			__( 'Primary Color', 'festival-winners-showcase' ),
			array( __CLASS__, 'render_color_field' ),
			'fws-settings',
			'fws_appearance_section',
			array( 'field' => 'primary_color', 'default' => '#3498db' )
		);

		add_settings_field(
			'fws_accent_color',
			__( 'Accent Color', 'festival-winners-showcase' ),
			array( __CLASS__, 'render_color_field' ),
			'fws-settings',
			'fws_appearance_section',
			array( 'field' => 'accent_color', 'default' => '#e74c3c' )
		);

		add_settings_field(
			'fws_bg_color',
			__( 'Background Color', 'festival-winners-showcase' ),
			array( __CLASS__, 'render_color_field' ),
			'fws-settings',
			'fws_appearance_section',
			array( 'field' => 'bg_color', 'default' => '#1a1a1a' )
		);
	}

	public static function render_color_field( $args ) {
		$options = get_option( 'fws_settings' );
		$value = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : $args['default'];
		?>
		<input type="text" name="fws_settings[<?php echo esc_attr( $args['field'] ); ?>]" value="<?php echo esc_attr( $value ); ?>" class="fws-color-picker">
		<?php
	}

	public static function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php _e( 'Festival Winners Showcase Settings', 'festival-winners-showcase' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'fws_settings_group' );
				do_settings_sections( 'fws-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
FWS_Settings::init();
