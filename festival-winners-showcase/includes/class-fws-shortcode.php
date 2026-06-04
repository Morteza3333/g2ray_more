<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FWS_Shortcode {

	public static function init() {
		add_shortcode( 'festival_winners', array( __CLASS__, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function enqueue_assets() {
		global $post;
		if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'festival_winners' ) ) {
			return;
		}

		// CSS
		wp_enqueue_style( 'fws-swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), '11.0.0' );
		wp_enqueue_style( 'fws-frontend', FWS_URL . 'assets/css/frontend.css', array( 'fws-swiper' ), FWS_VERSION );

		// Inline style for custom colors
		$options = get_option( 'fws_settings' );
		$primary = isset( $options['primary_color'] ) ? $options['primary_color'] : '#3498db';
		$accent  = isset( $options['accent_color'] ) ? $options['accent_color'] : '#e74c3c';
		$bg      = isset( $options['bg_color'] ) ? $options['bg_color'] : '#1a1a1a';

		$custom_css = "
			:root {
				--fws-primary: {$primary};
				--fws-accent: {$accent};
				--fws-bg: {$bg};
			}
		";
		wp_add_inline_style( 'fws-frontend', $custom_css );

		// JS
		wp_enqueue_script( 'fws-gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js', array(), '3.12.2', true );
		wp_enqueue_script( 'fws-swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', array(), '11.0.0', true );
		wp_enqueue_script( 'fws-justified', 'https://cdnjs.cloudflare.com/ajax/libs/justifiedGallery/3.8.1/js/jquery.justifiedGallery.min.js', array( 'jquery' ), '3.8.1', true );
		wp_enqueue_style( 'fws-justified', 'https://cdnjs.cloudflare.com/ajax/libs/justifiedGallery/3.8.1/css/justifiedGallery.min.css', array(), '3.8.1' );

		wp_enqueue_script( 'fws-frontend', FWS_URL . 'assets/js/frontend.js', array( 'jquery', 'fws-gsap', 'fws-swiper', 'fws-justified' ), FWS_VERSION, true );

		wp_localize_script( 'fws-frontend', 'fws_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'fws_nonce' )
		));
	}

	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'category' => '', // slug
		), $atts, 'festival_winners' );

		$args = array(
			'post_type'      => 'winner_entry',
			'posts_per_page' => -1,
			'meta_key'       => '_fws_rank',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
		);

		if ( ! empty( $atts['category'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'winner_category',
					'field'    => 'slug',
					'terms'    => $atts['category'],
				),
			);
		}

		$query = new WP_Query( $args );

		ob_start();
		if ( $query->have_posts() ) {
			include FWS_PATH . 'templates/winners-grid.php';
		} else {
			echo '<p>' . __( 'No winners found.', 'festival-winners-showcase' ) . '</p>';
		}
		wp_reset_postdata();

		return ob_get_clean();
	}
}
FWS_Shortcode::init();
