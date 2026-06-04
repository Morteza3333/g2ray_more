<?php
/**
 * Handle the [festival_winners] shortcode
 *
 * @link       https://example.com
 * @since      1.0.0
 * @package    Festival_Winners_Showcase
 * @subpackage Festival_Winners_Showcase/includes
 */

class FWS_Shortcode {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function register_shortcodes() {
		add_shortcode( 'festival_winners', array( $this, 'render_festival_winners' ) );
	}

	public function render_festival_winners( $atts ) {
		$atts = shortcode_atts( array(
			'category' => '',
			'limit'    => -1,
		), $atts, 'festival_winners' );

		$args = array(
			'post_type'      => 'winner_entry',
			'posts_per_page' => $atts['limit'],
			'orderby'        => 'menu_order',
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

		if ( ! $query->have_posts() ) {
			return '<p>' . __( 'هنوز هیچ برنده‌ای ثبت نشده است.', 'festival-winners-showcase' ) . '</p>';
		}

        $options = get_option( 'fws_settings' );
        $primary_color = isset( $options['primary_color'] ) ? $options['primary_color'] : '#1a1a1a';
        $accent_color = isset( $options['accent_color'] ) ? $options['accent_color'] : '#c5a059';
        $theme_mode = isset( $options['theme_mode'] ) ? $options['theme_mode'] : 'dark';

		ob_start();
		include plugin_dir_path( dirname( __FILE__ ) ) . 'templates/gallery.php';
		return ob_get_clean();
	}
}
