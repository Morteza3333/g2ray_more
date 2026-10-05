<?php
if (!defined('ABSPATH')) {
    exit;
}

class Font_Changer_Frontend {

    public function init() {
        add_shortcode('font_changer', array($this, 'render_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_head', array($this, 'inject_dynamic_css_and_fouc_script'), 1);
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'font-changer-frontend-css',
            FONT_CHANGER_URL . 'assets/css/font-changer-frontend.css',
            array(),
            FONT_CHANGER_VERSION
        );

        wp_enqueue_script(
            'font-changer-frontend-js',
            FONT_CHANGER_URL . 'assets/js/font-changer-frontend.js',
            array('jquery'),
            FONT_CHANGER_VERSION,
            true
        );

        $fonts = get_option('font_changer_fonts', array());
        $settings = get_option('font_changer_settings', array());

        wp_localize_script('font-changer-frontend-js', 'fontChangerVars', array(
            'fonts'          => $fonts,
            'settings'       => $settings,
            'default_selector' => isset($settings['target_selector']) ? $settings['target_selector'] : 'body'
        ));
    }

    public function inject_dynamic_css_and_fouc_script() {
        $fonts = get_option('font_changer_fonts', array());
        $settings = get_option('font_changer_settings', array());

        $target_selector = isset($settings['target_selector']) && !empty($settings['target_selector'])
            ? $settings['target_selector']
            : 'body, body p, body h1, body h2, body h3, body h4, body h5, body h6, body a, body span, body li';

        $size_small  = isset($settings['size_small']) ? $settings['size_small'] : '90%';
        $size_normal = isset($settings['size_normal']) ? $settings['size_normal'] : '100%';
        $size_large  = isset($settings['size_large']) ? $settings['size_large'] : '115%';

        echo "<style id='font-changer-dynamic-css'>\n";

        // Output @font-face rules for custom uploaded or bundled fonts
        foreach ($fonts as $font_id => $font) {
            if (!empty($font['src']) && ($font['type'] === 'upload' || $font['type'] === 'uploaded' || $font['type'] === 'bundled')) {
                $clean_family = str_replace(array("'", '"'), '', explode(',', $font['css_family'])[0]);
                echo "@font-face {\n";
                echo "  font-family: '{$clean_family}';\n";
                echo "  src: url('{$font['src']}');\n";
                echo "  font-weight: normal;\n";
                echo "  font-style: normal;\n";
                echo "  font-display: swap;\n";
                echo "}\n";
            } elseif ($font['type'] === 'external' && !empty($font['src'])) {
                echo "@import url('{$font['src']}');\n";
            }
        }

        // Output base target selectors with CSS variable defaults
        echo ":root {\n";
        echo "  --fc-active-font: inherit;\n";
        echo "  --fc-active-size: {$size_normal};\n";
        echo "  --fc-size-s1: {$size_small};\n";
        echo "  --fc-size-s2: {$size_normal};\n";
        echo "  --fc-size-s3: {$size_large};\n";
        echo "}\n";

        echo "body.fc-font-active {$target_selector} {\n";
        echo "  font-family: var(--fc-active-font) !important;\n";
        echo "}\n";

        echo "body.fc-size-active {$target_selector} {\n";
        echo "  font-size: var(--fc-active-size) !important;\n";
        echo "}\n";

        echo "</style>\n";

        // Inline FOUC prevention script in head
        echo "<script>\n";
        echo "(function(){\n";
        echo "  try {\n";
        echo "    var savedFont = localStorage.getItem('fc_selected_font');\n";
        echo "    var savedSize = localStorage.getItem('fc_selected_size');\n";
        echo "    var html = document.documentElement;\n";
        echo "    if (savedFont) {\n";
        echo "      document.addEventListener('DOMContentLoaded', function() {\n";
        echo "        document.body.classList.add('fc-font-active');\n";
        echo "        document.body.style.setProperty('--fc-active-font', savedFont);\n";
        echo "      });\n";
        echo "    }\n";
        echo "    if (savedSize) {\n";
        echo "      document.addEventListener('DOMContentLoaded', function() {\n";
        echo "        document.body.classList.add('fc-size-active');\n";
        echo "        document.body.style.setProperty('--fc-active-size', savedSize);\n";
        echo "      });\n";
        echo "    }\n";
        echo "  } catch(e){}\n";
        echo "})();\n";
        echo "</script>\n";
    }

    public function render_shortcode($atts = array()) {
        $atts = shortcode_atts(array(
            'title' => 'تنظیمات متن',
            'layout' => 'compact' // compact or expanded
        ), $atts, 'font_changer');

        $fonts = get_option('font_changer_fonts', array());

        ob_start();
        ?>
        <div class="fc-widget-wrapper fc-layout-<?php echo esc_attr($atts['layout']); ?>" id="fc-widget">
            <div class="fc-widget-body">
                <!-- Font Family Selector -->
                <div class="fc-widget-item fc-font-selector">
                    <span class="fc-widget-label">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h16v3"></path><path d="M9 20h6"></path><path d="M12 4v16"></path></svg>
                        فونت:
                    </span>
                    <select id="fc-select-font-family" class="fc-select">
                        <option value="">پیش‌فرض سایت</option>
                        <?php foreach ($fonts as $font_id => $font) : ?>
                            <option value="<?php echo esc_attr($font['css_family']); ?>">
                                <?php echo esc_html($font['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Font Size 3-Step Controls -->
                <div class="fc-widget-item fc-size-controls">
                    <span class="fc-widget-label">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
                        اندازه:
                    </span>
                    <div class="fc-size-btn-group" role="group" aria-label="تغییر سایز فونت">
                        <button type="button" class="fc-size-btn fc-btn-s1" data-size="var(--fc-size-s1)" title="سایز عادی / استاندارد">
                            <span style="font-size: 13px;">A</span>
                        </button>
                        <button type="button" class="fc-size-btn fc-btn-s2" data-size="var(--fc-size-s2)" title="سایز متوسط / بزرگ">
                            <span style="font-size: 16px; font-weight: bold;">A</span>
                        </button>
                        <button type="button" class="fc-size-btn fc-btn-s3" data-size="var(--fc-size-s3)" title="سایز بزرگ / خیلی بزرگ">
                            <span style="font-size: 20px; font-weight: bold;">A+</span>
                        </button>
                    </div>
                </div>

                <!-- Reset Button -->
                <div class="fc-widget-item fc-reset-control">
                    <button type="button" class="fc-reset-btn" id="fc-reset-btn" title="بازنشانی به حالت اول">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
