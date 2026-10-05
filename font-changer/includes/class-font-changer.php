<?php
if (!defined('ABSPATH')) {
    exit;
}

class Font_Changer {

    protected $admin;
    protected $frontend;

    public function __construct() {
        $this->load_dependencies();
    }

    private function load_dependencies() {
        require_once FONT_CHANGER_PATH . 'includes/class-font-changer-admin.php';
        require_once FONT_CHANGER_PATH . 'includes/class-font-changer-frontend.php';

        $this->admin = new Font_Changer_Admin();
        $this->frontend = new Font_Changer_Frontend();
    }

    public static function activate() {
        // Set default options if not existing
        $default_fonts = array(
            'vazirmatn' => array(
                'id'         => 'vazirmatn',
                'name'       => 'وزیر متن (پیش‌فرض)',
                'type'       => 'bundled',
                'src'        => FONT_CHANGER_URL . 'fonts/Vazirmatn-Regular.woff2',
                'css_family' => "'Vazirmatn', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
                'is_default' => true
            ),
            'tahoma' => array(
                'id'         => 'tahoma',
                'name'       => 'تاهما (سیستمی)',
                'type'       => 'system',
                'src'        => '',
                'css_family' => "Tahoma, Arial, sans-serif",
                'is_default' => false
            ),
            'iransans' => array(
                'id'         => 'iransans',
                'name'       => 'ایران سنس (سیستمی/سفارشی)',
                'type'       => 'system',
                'src'        => '',
                'css_family' => "'IRANSans', 'IranSans', 'Segoe UI', sans-serif",
                'is_default' => false
            ),
            'sahel' => array(
                'id'         => 'sahel',
                'name'       => 'ساحل',
                'type'       => 'system',
                'src'        => '',
                'css_family' => "'Sahel', sans-serif",
                'is_default' => false
            )
        );

        if (!get_option('font_changer_fonts')) {
            add_option('font_changer_fonts', $default_fonts);
        }

        $default_settings = array(
            'target_selector' => 'body, body p, body h1, body h2, body h3, body h4, body h5, body h6, body a, body span, body li, body input, body button, body select, body textarea',
            'default_font'    => 'vazirmatn',
            'size_small'      => '90%',
            'size_normal'     => '100%',
            'size_large'      => '115%',
            'enable_widget'   => '1'
        );

        if (!get_option('font_changer_settings')) {
            add_option('font_changer_settings', $default_settings);
        }
    }

    public static function deactivate() {
        // Clean up or clear transient caches if any
    }

    public function run() {
        $this->admin->init();
        $this->frontend->init();
    }
}
