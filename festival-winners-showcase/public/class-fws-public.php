<?php

class FWS_Public {
	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function enqueue_styles() {
		wp_enqueue_style( 'swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), '11.0.0', 'all' );
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/fws-public.css', array( 'swiper' ), $this->version, 'all' );

		$custom_css = FWS_Settings::get_dynamic_css();
		wp_add_inline_style( $this->plugin_name, $custom_css );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', array(), '11.0.0', true );
		wp_enqueue_script( 'gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js', array(), '3.12.2', true );
		wp_enqueue_script( 'gsap-scroll-trigger', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js', array( 'gsap' ), '3.12.2', true );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/fws-public.js', array( 'jquery', 'swiper', 'gsap' ), $this->version, true );

		$settings = FWS_Settings::get_settings();
		wp_localize_script( $this->plugin_name, 'fws_pub_vars', array(
			'animation_intensity' => $settings['animation_intensity'] ?? 'medium',
		) );
	}

	public function register_shortcodes() {
		add_shortcode( 'festival_winners', array( $this, 'render_winners_gallery' ) );
	}

	public function render_winners_gallery( $atts ) {
		$winners = get_posts( array(
			'post_type'      => 'winner_entry',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		) );

		if ( empty( $winners ) ) {
			return '';
		}

		ob_start();
		include plugin_dir_path( __FILE__ ) . 'partials/fws-public-display.php';
		return ob_get_clean();
	}
}
