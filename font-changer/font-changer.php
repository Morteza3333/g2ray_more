<?php
/**
 * Plugin Name: Font Changer | تغییر دهنده فونت و اندازه متن
 * Plugin URI:  https://wordpress.org/plugins/font-changer
 * Description: افزونه حرفه‌ای تغییر فونت و اندازه فونت کل سایت توسط کاربر با قابلیت اضافه و حذف فونت در پنل مدیریت و شورت‌کد هدر.
 * Version:     1.0.0
 * Author:      Jules
 * Text Domain: font-changer
 * Domain Path: /languages
 * License:     GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('FONT_CHANGER_VERSION', '1.0.0');
define('FONT_CHANGER_FILE', __FILE__);
define('FONT_CHANGER_PATH', plugin_dir_path(__FILE__));
define('FONT_CHANGER_URL', plugin_dir_url(__FILE__));

/**
 * Main Plugin Class
 */
require_once FONT_CHANGER_PATH . 'includes/class-font-changer.php';

/**
 * Activation Hook
 */
register_activation_hook(__FILE__, array('Font_Changer', 'activate'));

/**
 * Deactivation Hook
 */
register_deactivation_hook(__FILE__, array('Font_Changer', 'deactivate'));

/**
 * Initialize Plugin
 */
function font_changer_init() {
    $plugin = new Font_Changer();
    $plugin->run();
}
add_action('plugins_loaded', 'font_changer_init');
