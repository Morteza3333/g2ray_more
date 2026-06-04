<?php
/**
 * The settings functionality of the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 * @package    Festival_Winners_Showcase
 * @subpackage Festival_Winners_Showcase/admin
 */

class FWS_Settings {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function add_plugin_admin_menu() {
		add_menu_page(
			__( 'تنظیمات جشنواره', 'festival-winners-showcase' ),
			__( '⚙️ تنظیمات جشنواره', 'festival-winners-showcase' ),
			'manage_options',
			'fws-settings',
			array( $this, 'display_plugin_setup_page' ),
			'dashicons-admin-generic',
			100
		);

        add_submenu_page(
            'fws-settings',
            __( 'راهنما', 'festival-winners-showcase' ),
            __( '❓ راهنما', 'festival-winners-showcase' ),
            'manage_options',
            'fws-help',
            array( $this, 'display_help_page' )
        );
	}

	public function display_plugin_setup_page() {
		?>
		<div class="wrap fws-settings-wrap" style="direction: rtl;">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'fws_settings_group' );
				do_settings_sections( 'fws-settings' );
				submit_button( __( 'ذخیره تنظیمات', 'festival-winners-showcase' ) );
				?>
			</form>
		</div>
		<?php
	}

    public function display_help_page() {
        require_once plugin_dir_path( __FILE__ ) . 'partials/fws-admin-help.php';
    }

	public function page_init() {
		register_setting(
			'fws_settings_group',
			'fws_settings',
			array( $this, 'sanitize' )
		);

		add_settings_section(
			'fws_style_section',
			__( 'تنظیمات ظاهری و استایل', 'festival-winners-showcase' ),
			array( $this, 'print_section_info' ),
			'fws-settings'
		);

		add_settings_field(
			'primary_color',
			__( 'رنگ اصلی:', 'festival-winners-showcase' ),
			array( $this, 'primary_color_callback' ),
			'fws-settings',
			'fws_style_section'
		);

		add_settings_field(
			'accent_color',
			__( 'رنگ تاکید (Accent):', 'festival-winners-showcase' ),
			array( $this, 'accent_color_callback' ),
			'fws-settings',
			'fws_style_section'
		);

		add_settings_field(
			'theme_mode',
			__( 'حالت قالب:', 'festival-winners-showcase' ),
			array( $this, 'theme_mode_callback' ),
			'fws-settings',
			'fws_style_section'
		);
	}

	public function sanitize( $input ) {
		$new_input = array();
		if ( isset( $input['primary_color'] ) ) {
			$new_input['primary_color'] = sanitize_hex_color( $input['primary_color'] );
		}
		if ( isset( $input['accent_color'] ) ) {
			$new_input['accent_color'] = sanitize_hex_color( $input['accent_color'] );
		}
		if ( isset( $input['theme_mode'] ) ) {
			$new_input['theme_mode'] = sanitize_text_field( $input['theme_mode'] );
		}

		return $new_input;
	}

	public function print_section_info() {
		_e( 'در این بخش می‌توانید رنگ‌بندی و استایل کلی گالری را متناسب با برند خود تغییر دهید.', 'festival-winners-showcase' );
	}

	public function primary_color_callback() {
		$options = get_option( 'fws_settings' );
		$val = isset( $options['primary_color'] ) ? $options['primary_color'] : '#1a1a1a';
		printf(
			'<input type="text" name="fws_settings[primary_color]" value="%s" class="fws-color-picker" />',
			esc_attr( $val )
		);
	}

	public function accent_color_callback() {
		$options = get_option( 'fws_settings' );
		$val = isset( $options['accent_color'] ) ? $options['accent_color'] : '#c5a059';
		printf(
			'<input type="text" name="fws_settings[accent_color]" value="%s" class="fws-color-picker" />',
			esc_attr( $val )
		);
	}

	public function theme_mode_callback() {
		$options = get_option( 'fws_settings' );
		$val = isset( $options['theme_mode'] ) ? $options['theme_mode'] : 'dark';
		?>
		<select name="fws_settings[theme_mode]">
			<option value="dark" <?php selected( $val, 'dark' ); ?>><?php _e( 'تیره (Dark)', 'festival-winners-showcase' ); ?></option>
			<option value="light" <?php selected( $val, 'light' ); ?>><?php _e( 'روشن (Light)', 'festival-winners-showcase' ); ?></option>
		</select>
		<?php
	}
}
