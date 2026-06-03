<?php
/**
 * The public-facing functionality of the plugin.
 */
class MVG_Public {
	private $version;

	public function __construct( $version ) {
		$this->version = $version;
	}

	public function enqueue_styles() {
		wp_enqueue_style( 'mvg-public-css', MVG_URL . 'public/css/mvg-public.css', array(), $this->version, 'all' );

		$settings = get_option( 'mvg_settings' );
		$custom_css = "
			:root {
				--mvg-primary: " . esc_attr( $settings['primary_color'] ) . ";
				--mvg-accent: " . esc_attr( $settings['accent_color'] ) . ";
				--mvg-bg: " . esc_attr( $settings['background_color'] ) . ";
				--mvg-text: " . esc_attr( $settings['text_color'] ) . ";
			}
		";
		wp_add_inline_style( 'mvg-public-css', $custom_css );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'mvg-public-js', MVG_URL . 'public/js/mvg-public.js', array( 'jquery' ), $this->version, true );
		wp_localize_script( 'mvg-public-js', 'mvg_vars', array(
			'rest_url' => get_rest_url( null, 'mvg/v1' ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
		) );
	}

	public function render_gallery( $atts ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mvg_videos';
		$videos = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY menu_order ASC" );

		if ( empty( $videos ) ) {
			return '<p>No videos found.</p>';
		}

		$featured_video = null;
		foreach ( $videos as $video ) {
			if ( $video->is_featured ) {
				$featured_video = $video;
				break;
			}
		}

		if ( ! $featured_video ) {
			$featured_video = $videos[0];
		}

		ob_start();
		require MVG_PATH . 'public/partials/mvg-public-display.php';
		return ob_get_clean();
	}

	public function get_video_embed( $video, $auto_play = false ) {
		$url = $video->url;
		$type = $video->type;
		$autoplay_attr = $auto_play ? 'autoplay=1&mute=1' : 'autoplay=0';

		if ( $type === 'youtube' ) {
			preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches );
			$video_id = $matches[1] ?? '';
			return '<iframe src="https://www.youtube.com/embed/' . esc_attr( $video_id ) . '?' . $autoplay_attr . '" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen loading="lazy"></iframe>';
		} elseif ( $type === 'vimeo' ) {
			preg_match( '/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches );
			$video_id = $matches[1] ?? '';
			$vimeo_autoplay_attr = $auto_play ? 'autoplay=1&muted=1' : 'autoplay=0';
			return '<iframe src="https://player.vimeo.com/video/' . esc_attr( $video_id ) . '?' . $vimeo_autoplay_attr . '" frameborder="0" allow="autoplay; fullscreen" allowfullscreen loading="lazy"></iframe>';
		} elseif ( $type === 'self' ) {
			$self_autoplay_attr = $auto_play ? 'autoplay muted' : '';
			return '<video src="' . esc_url( $url ) . '" controls ' . $self_autoplay_attr . ' loading="lazy"></video>';
		}
		return 'Invalid video type';
	}
}
