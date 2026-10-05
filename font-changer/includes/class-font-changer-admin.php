<?php
if (!defined('ABSPATH')) {
    exit;
}

class Font_Changer_Admin {

    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // AJAX endpoints
        add_action('wp_ajax_font_changer_add_font', array($this, 'ajax_add_font'));
        add_action('wp_ajax_font_changer_delete_font', array($this, 'ajax_delete_font'));
        add_action('wp_ajax_font_changer_save_settings', array($this, 'ajax_save_settings'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'مدیریت فونت‌های سایت',
            'مدیریت فونت',
            'manage_options',
            'font-changer-settings',
            array($this, 'render_admin_page'),
            'dashicons-editor-customchar',
            81
        );
    }

    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_font-changer-settings') {
            return;
        }

        wp_enqueue_media(); // Enqueue WordPress media uploader scripts

        wp_enqueue_style(
            'font-changer-admin-css',
            FONT_CHANGER_URL . 'assets/css/font-changer-admin.css',
            array(),
            FONT_CHANGER_VERSION
        );

        wp_enqueue_script(
            'font-changer-admin-js',
            FONT_CHANGER_URL . 'assets/js/font-changer-admin.js',
            array('jquery'),
            FONT_CHANGER_VERSION,
            true
        );

        wp_localize_script('font-changer-admin-js', 'fontChangerAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('font_changer_admin_nonce'),
            'messages' => array(
                'confirm_delete' => 'آیا از حذف این فونت اطمینان دارید؟',
                'saving'         => 'در حال ذخیره‌سازی...',
                'saved'          => 'تغییرات با موفقیت ذخیره شد.',
                'error'          => 'خطایی رخ داد. لطفا دوباره تلاش کنید.'
            )
        ));
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('شما دسترسی کافی به این صفحه را ندارید.');
        }

        $fonts = get_option('font_changer_fonts', array());
        $settings = get_option('font_changer_settings', array());

        ?>
        <div class="wrap fc-admin-wrap">
            <h1 class="fc-admin-title">
                <span class="dashicons dashicons-editor-customchar"></span>
                مدیریت فونت و سایز متون سایت
            </h1>

            <div class="fc-container">
                <!-- Sidebar / Quick Notice -->
                <div class="fc-card fc-intro-card">
                    <h2>راهنمای استفاده و شورت‌کد</h2>
                    <p>برای نمایش باکس تغییر فونت و سایز متن در هر قسمت از سایت (مانند هدر سایت، سایدبار یا برگه)، شورت کد زیر را قرار دهید:</p>
                    <div class="fc-shortcode-box">
                        <code>[font_changer]</code>
                        <button type="button" class="fc-btn fc-btn-sm fc-btn-copy" onclick="navigator.clipboard.writeText('[font_changer]'); alert('شورت کد کپی شد!');">کپی شورت‌کد</button>
                    </div>
                    <p class="fc-tip">💡 کاربر با استفاده از این شورت‌کد می‌تواند به راحتی فونت سایت را تغییر داده و سایز فونت را در ۳ حالت (عادی، بزرگ، خیلی بزرگ) تنظیم کند. انتخاب کاربر در مرورگر ذخیره می‌گردد.</p>
                </div>

                <!-- Section: Installed Fonts -->
                <div class="fc-card">
                    <h2>فونت‌های فعال و نصب شده</h2>
                    <table class="wp-list-table widefat fixed striped fc-font-table">
                        <thead>
                            <tr>
                                <th>نام فونت</th>
                                <th>نوع فونت</th>
                                <th>خانواده CSS (Font Family)</th>
                                <th>پیش‌نمایش</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody id="fc-font-list">
                            <?php if (!empty($fonts)) : ?>
                                <?php foreach ($fonts as $font_id => $font) : ?>
                                    <tr data-font-id="<?php echo esc_attr($font_id); ?>">
                                        <td><strong><?php echo esc_html($font['name']); ?></strong></td>
                                        <td>
                                            <span class="fc-badge fc-badge-<?php echo esc_attr($font['type']); ?>">
                                                <?php
                                                    if ($font['type'] === 'bundled') echo 'سیستمی افزونه';
                                                    elseif ($font['type'] === 'uploaded') echo 'فایل آپلود شده';
                                                    elseif ($font['type'] === 'google') echo 'فونت وب / گوگل';
                                                    else echo 'فونت سیستمی';
                                                ?>
                                            </span>
                                        </td>
                                        <td><code><?php echo esc_html($font['css_family']); ?></code></td>
                                        <td>
                                            <span style="font-family: <?php echo esc_attr($font['css_family']); ?>; font-size: 16px;">
                                                نمونه متن فارسی ۱۲۳
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (empty($font['is_default'])) : ?>
                                                <button type="button" class="button button-link-delete fc-delete-font" data-id="<?php echo esc_attr($font_id); ?>">حذف</button>
                                            <?php else : ?>
                                                <span class="description">پیش‌فرض</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5">هیچ فونتی یافت نشد.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Section: Add New Font -->
                <div class="fc-card">
                    <h2>افزودن فونت جدید</h2>
                    <form id="fc-add-font-form" class="fc-form">
                        <div class="fc-form-group">
                            <label for="fc_font_name">نام فونت (به فارسی یا انگلیسی):</label>
                            <input type="text" id="fc_font_name" name="font_name" class="regular-text" placeholder="مثلا: وزیر، ایران یکان، Shabnam" required>
                        </div>

                        <div class="fc-form-group">
                            <label for="fc_font_type">نوع اضافه کردن فونت:</label>
                            <select id="fc_font_type" name="font_type" class="regular-text">
                                <option value="upload">آپلود فایل فونت (WOFF2, WOFF, TTF)</option>
                                <option value="external">فونت وب / لینک خارجی (Google Fonts یا URL فایل)</option>
                                <option value="custom_name">نام فونت موجود در قالب / سیستم (Font-Family)</option>
                            </select>
                        </div>

                        <!-- Upload Option -->
                        <div class="fc-form-group fc-font-source-field" id="fc-field-upload">
                            <label for="fc_font_file_url">آدرس فایل فونت (یا آپلود کنید):</label>
                            <div class="fc-input-group">
                                <input type="text" id="fc_font_file_url" name="font_file_url" class="regular-text" placeholder="https://site.com/wp-content/uploads/font.woff2">
                                <button type="button" class="button button-secondary" id="fc_upload_font_btn">انتخاب / آپلود فایل فونت</button>
                            </div>
                            <p class="description">فرمت‌های پشتیبانی شده: woff2 (توصیه شده)، woff، ttf، otf.</p>
                        </div>

                        <!-- External Option -->
                        <div class="fc-form-group fc-font-source-field" id="fc-field-external" style="display:none;">
                            <label for="fc_font_external_url">لینک لینک یا CSS فونت خارجی:</label>
                            <input type="url" id="fc_font_external_url" name="font_external_url" class="regular-text" placeholder="https://fonts.googleapis.com/css2?family=Vazirmatn&display=swap">
                        </div>

                        <!-- Font Family CSS Field -->
                        <div class="fc-form-group">
                            <label for="fc_css_family">نام خانوادگی فونت در CSS (Font-Family):</label>
                            <input type="text" id="fc_css_family" name="css_family" class="regular-text" placeholder="'MyCustomFont', sans-serif" required>
                            <p class="description">نامی که در دستور font-family استفاده می‌شود.</p>
                        </div>

                        <button type="submit" class="button button-primary" id="fc-save-font-btn">افزودن فونت به لیست</button>
                    </form>
                </div>

                <!-- Section: Global Settings -->
                <div class="fc-card">
                    <h2>تنظیمات عمومی</h2>
                    <form id="fc-settings-form" class="fc-form">
                        <div class="fc-form-group">
                            <label for="fc_target_selector">تگ‌ها و المان‌های هدف تغییر فونت (CSS Selector):</label>
                            <textarea id="fc_target_selector" name="target_selector" class="large-text" rows="3"><?php echo esc_textarea(isset($settings['target_selector']) ? $settings['target_selector'] : 'body, body p, body h1, body h2, body h3, body h4, body h5, body h6, body a, body span, body li, body input, body button, body select, body textarea'); ?></textarea>
                            <p class="description">تگ‌ها یا کلاس‌هایی که می‌خواهید فونت و اندازه آن‌ها تغییر کند. (برای تغییر کل سایت مقدار پیش‌فرض دست نخورده بماند).</p>
                        </div>

                        <div class="fc-form-group">
                            <label>تنظیمات اندازه فونت ۳ سایز (اندازه نسبی):</label>
                            <div class="fc-sizes-grid">
                                <div>
                                    <label for="fc_size_small">سایز ۱ (کوچک / عادی):</label>
                                    <input type="text" id="fc_size_small" name="size_small" value="<?php echo esc_attr(isset($settings['size_small']) ? $settings['size_small'] : '90%'); ?>" placeholder="90%">
                                </div>
                                <div>
                                    <label for="fc_size_normal">سایز ۲ (متوسط / بزرگ):</label>
                                    <input type="text" id="fc_size_normal" name="size_normal" value="<?php echo esc_attr(isset($settings['size_normal']) ? $settings['size_normal'] : '100%'); ?>" placeholder="100%">
                                </div>
                                <div>
                                    <label for="fc_size_large">سایز ۳ (بزرگ / خیلی بزرگ):</label>
                                    <input type="text" id="fc_size_large" name="size_large" value="<?php echo esc_attr(isset($settings['size_large']) ? $settings['size_large'] : '115%'); ?>" placeholder="115%">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="button button-primary" id="fc-save-settings-btn">ذخیره تنظیمات عمومی</button>
                        <span id="fc-settings-msg" class="fc-msg"></span>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    public function ajax_add_font() {
        check_ajax_referer('font_changer_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'عدم داشتن دسترسی لازم'));
        }

        $font_name    = isset($_POST['font_name']) ? sanitize_text_field($_POST['font_name']) : '';
        $font_type    = isset($_POST['font_type']) ? sanitize_text_field($_POST['font_type']) : 'uploaded';
        $font_file    = isset($_POST['font_file_url']) ? esc_url_raw($_POST['font_file_url']) : '';
        $ext_url      = isset($_POST['font_external_url']) ? esc_url_raw($_POST['font_external_url']) : '';
        $css_family   = isset($_POST['css_family']) ? sanitize_text_field($_POST['css_family']) : '';

        if (empty($font_name) || empty($css_family)) {
            wp_send_json_error(array('message' => 'لطفاً نام فونت و نام فونت CSS را وارد کنید.'));
        }

        $fonts = get_option('font_changer_fonts', array());
        $font_id = sanitize_title($font_name) . '_' . substr(md5(time() . rand(100,999)), 0, 5);

        $fonts[$font_id] = array(
            'id'         => $font_id,
            'name'       => $font_name,
            'type'       => $font_type,
            'src'        => ($font_type === 'external') ? $ext_url : $font_file,
            'css_family' => $css_family,
            'is_default' => false
        );

        update_option('font_changer_fonts', $fonts);

        wp_send_json_success(array(
            'message' => 'فونت با موفقیت افزوده شد.',
            'font'    => $fonts[$font_id]
        ));
    }

    public function ajax_delete_font() {
        check_ajax_referer('font_changer_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'عدم داشتن دسترسی لازم'));
        }

        $font_id = isset($_POST['font_id']) ? sanitize_text_field($_POST['font_id']) : '';

        if (empty($font_id)) {
            wp_send_json_error(array('message' => 'شناسه فونت معتبر نیست.'));
        }

        $fonts = get_option('font_changer_fonts', array());

        if (isset($fonts[$font_id])) {
            if (!empty($fonts[$font_id]['is_default'])) {
                wp_send_json_error(array('message' => 'فونت پیش‌فرض قابل حذف نیست.'));
            }
            unset($fonts[$font_id]);
            update_option('font_changer_fonts', $fonts);
            wp_send_json_success(array('message' => 'فونت با موفقیت حذف شد.'));
        } else {
            wp_send_json_error(array('message' => 'فونت پیدا نشد.'));
        }
    }

    public function ajax_save_settings() {
        check_ajax_referer('font_changer_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'عدم داشتن دسترسی لازم'));
        }

        $target_selector = isset($_POST['target_selector']) ? sanitize_textarea_field($_POST['target_selector']) : '';
        $size_small      = isset($_POST['size_small']) ? sanitize_text_field($_POST['size_small']) : '90%';
        $size_normal     = isset($_POST['size_normal']) ? sanitize_text_field($_POST['size_normal']) : '100%';
        $size_large      = isset($_POST['size_large']) ? sanitize_text_field($_POST['size_large']) : '115%';

        $settings = array(
            'target_selector' => $target_selector,
            'size_small'      => $size_small,
            'size_normal'     => $size_normal,
            'size_large'      => $size_large,
        );

        update_option('font_changer_settings', $settings);

        wp_send_json_success(array('message' => 'تنظیمات با موفقیت ذخیره شد.'));
    }
}
