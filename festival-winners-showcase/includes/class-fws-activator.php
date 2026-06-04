<?php

class FWS_Activator {
	public static function activate() {
		// Flush rewrite rules after CPT registration
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fws-i18n.php';
		$i18n = new FWS_i18n();
		$i18n->register_post_types();

		// Create default terms
		if ( ! term_exists( 'single', 'winner_category' ) ) {
			wp_insert_term( 'تک عکس', 'winner_category', array( 'slug' => 'single' ) );
		}
		if ( ! term_exists( 'series', 'winner_category' ) ) {
			wp_insert_term( 'مجموعه عکس', 'winner_category' , array( 'slug' => 'series' ) );
		}

		flush_rewrite_rules();

		// Set default settings
		if ( ! get_option( 'fws_settings' ) ) {
			update_option( 'fws_settings', array(
				'primary_color'   => '#0073aa',
				'accent_color'    => '#ffb200',
				'background_mode' => 'dark',
				'bg_color'        => '#0a0a0b',
			) );
		}
	}
}
