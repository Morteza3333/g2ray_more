<?php
/**
 * Fired during plugin activation.
 */
class MVG_Activator {
	public static function activate() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mvg_videos';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			url varchar(255) NOT NULL,
			thumbnail_url varchar(255) DEFAULT '',
			type varchar(50) DEFAULT 'youtube',
			menu_order int(11) DEFAULT 0,
			is_featured tinyint(1) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		// Default settings
		if ( ! get_option( 'mvg_settings' ) ) {
			update_option( 'mvg_settings', array(
				'primary_color'    => '#0f172a', // Navy dark
				'accent_color'     => '#38bdf8', // Sky blue
				'background_color' => '#020617', // Deeper navy
				'text_color'       => '#f8fafc',
				'auto_play'        => 0,
			) );
		}
	}
}
