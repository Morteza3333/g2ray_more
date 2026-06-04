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
		wp_enqueue_style( 'fws-admin-settings-css', FWS_URL . 'assets/css/admin-settings.css', array(), FWS_VERSION );
	}

	public static function add_settings_page() {
		add_submenu_page(
			'edit.php?post_type=winner_entry',
			__( 'تنظیمات جشنواره', 'festival-winners-showcase' ),
			__( 'تنظیمات', 'festival-winners-showcase' ),
			'manage_options',
			'fws-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function register_settings() {
		register_setting( 'fws_settings_group', 'fws_settings' );

		add_settings_section(
			'fws_appearance_section',
			__( 'تنظیمات ظاهری و رنگ‌بندی', 'festival-winners-showcase' ),
			null,
			'fws-settings'
		);

		add_settings_field(
			'fws_primary_color',
			__( 'رنگ اصلی گالری', 'festival-winners-showcase' ),
			array( __CLASS__, 'render_color_field' ),
			'fws-settings',
			'fws_appearance_section',
			array(
				'field' => 'primary_color',
				'default' => '#3498db',
				'description' => 'این رنگ برای عنوان‌ها، نام عکاس و آیکون‌های اصلی در سایت استفاده می‌شود. پیشنهادی: رنگی متناسب با لوگوی جشنواره خود انتخاب کنید.'
			)
		);

		add_settings_field(
			'fws_accent_color',
			__( 'رنگ تاکید (نشان رتبه)', 'festival-winners-showcase' ),
			array( __CLASS__, 'render_color_field' ),
			'fws-settings',
			'fws_appearance_section',
			array(
				'field' => 'accent_color',
				'default' => '#e74c3c',
				'description' => 'این رنگ برای کادر رتبه‌بندی (نفر اول، دوم و...) استفاده می‌شود تا به خوبی در دید باشد.'
			)
		);

		add_settings_field(
			'fws_bg_color',
			__( 'رنگ پس‌زمینه کادر', 'festival-winners-showcase' ),
			array( __CLASS__, 'render_color_field' ),
			'fws-settings',
			'fws_appearance_section',
			array(
				'field' => 'bg_color',
				'default' => '#1a1a1a',
				'description' => 'رنگ زمینه اصلی که عکس‌ها روی آن قرار می‌گیرند. انتخاب رنگ تیره باعث جلوه بیشتر عکس‌های هنری می‌شود.'
			)
		);
	}

	public static function render_color_field( $args ) {
		$options = get_option( 'fws_settings' );
		$value = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : $args['default'];
		?>
		<div class="fws-settings-field">
			<input type="text" name="fws_settings[<?php echo esc_attr( $args['field'] ); ?>]" value="<?php echo esc_attr( $value ); ?>" class="fws-color-picker">
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		</div>
		<?php
	}

	public static function render_settings_page() {
		?>
		<div class="wrap fws-settings-wrap">
			<div class="fws-settings-header">
				<h1><?php _e( '⚙️ تنظیمات نمایش برندگان جشنواره', 'festival-winners-showcase' ); ?></h1>
				<p><?php _e( 'ظاهر بخش نمایش آثار را مطابق با هویت بصری جشنواره خود مدیریت کنید.', 'festival-winners-showcase' ); ?></p>
			</div>

			<div class="fws-settings-main">
				<form method="post" action="options.php">
					<?php
					settings_fields( 'fws_settings_group' );
					do_settings_sections( 'fws-settings' );
					submit_button( __( 'ذخیره تنظیمات جدید', 'festival-winners-showcase' ) );
					?>
				</form>
			</div>

			<div class="fws-settings-guide">
				<h2><?php _e( '📘 راهنمای راه‌اندازی سریع', 'festival-winners-showcase' ); ?></h2>
				<ul>
					<li><strong>۱. تعریف دسته‌بندی:</strong> ابتدا از منوی "دسته‌بندی‌ها"، دو دسته‌ی "تک عکس" و "مجموعه عکس" ایجاد کنید.</li>
					<li><strong>۲. ثبت اثر:</strong> از منوی "افزودن جدید"، نام اثر را بنویسید و در پایین صفحه اطلاعات عکاس و رتبه را مشخص کنید.</li>
					<li><strong>۳. تعیین نوع اثر:</strong> اگر اثر "تک عکس" است فقط تصویر شاخص بگذارید. اگر "مجموعه" است، عکس‌ها را در بخش گالری پایین صفحه اضافه کنید.</li>
					<li><strong>۴. نمایش در سایت:</strong> کد <code>[festival_winners]</code> را کپی کرده و در برگه مورد نظر خود قرار دهید.</li>
				</ul>
				<p style="margin-top:20px; font-size:13px; color:#666; border-top:1px solid #ddd; padding-top:15px;">
					<?php _e( 'نکته: برای فیلتر کردن دسته‌ای خاص از کد <code>[festival_winners category="slug-daste"]</code> استفاده کنید.', 'festival-winners-showcase' ); ?>
				</p>
			</div>
		</div>
		<?php
	}
}
FWS_Settings::init();
