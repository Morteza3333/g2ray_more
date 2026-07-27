<?php
namespace AMP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AMP_Activator {

	public static function activate() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Conversations table
		$table_conversations = $wpdb->prefix . 'amp_conversations';
		// Participants table
		$table_participants = $wpdb->prefix . 'amp_participants';
		// Messages table
		$table_messages = $wpdb->prefix . 'amp_messages';
		// Message reactions table
		$table_reactions = $wpdb->prefix . 'amp_reactions';
		// User status table
		$table_user_status = $wpdb->prefix . 'amp_user_status';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql_conversations = "CREATE TABLE $table_conversations (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			title varchar(255) DEFAULT NULL,
			type enum('private', 'group') NOT NULL DEFAULT 'private',
			created_by bigint(20) UNSIGNED NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;";

		$sql_participants = "CREATE TABLE $table_participants (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			conversation_id bigint(20) UNSIGNED NOT NULL,
			user_id bigint(20) UNSIGNED NOT NULL,
			is_pinned tinyint(1) NOT NULL DEFAULT 0,
			is_archived tinyint(1) NOT NULL DEFAULT 0,
			is_muted tinyint(1) NOT NULL DEFAULT 0,
			last_read_message_id bigint(20) UNSIGNED DEFAULT NULL,
			joined_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY conv_user (conversation_id, user_id)
		) $charset_collate;";

		$sql_messages = "CREATE TABLE $table_messages (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			conversation_id bigint(20) UNSIGNED NOT NULL,
			sender_id bigint(20) UNSIGNED NOT NULL,
			parent_id bigint(20) UNSIGNED DEFAULT NULL,
			message_text text DEFAULT NULL,
			attachments longtext DEFAULT NULL,
			is_pinned tinyint(1) NOT NULL DEFAULT 0,
			is_starred tinyint(1) NOT NULL DEFAULT 0,
			is_edited tinyint(1) NOT NULL DEFAULT 0,
			is_deleted tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY conv_idx (conversation_id)
		) $charset_collate;";

		$sql_reactions = "CREATE TABLE $table_reactions (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			message_id bigint(20) UNSIGNED NOT NULL,
			user_id bigint(20) UNSIGNED NOT NULL,
			emoji varchar(50) NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY msg_user_emoji (message_id, user_id, emoji)
		) $charset_collate;";

		$sql_user_status = "CREATE TABLE $table_user_status (
			user_id bigint(20) UNSIGNED NOT NULL,
			status enum('online', 'offline', 'dnd') NOT NULL DEFAULT 'offline',
			custom_presence varchar(255) DEFAULT NULL,
			last_seen datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (user_id)
		) $charset_collate;";

		dbDelta( $sql_conversations );
		dbDelta( $sql_participants );
		dbDelta( $sql_messages );
		dbDelta( $sql_reactions );
		dbDelta( $sql_user_status );

		// Set default permissions and options
		if ( ! get_option( 'amp_settings' ) ) {
			$default_settings = [
				'allowed_roles'   => [ 'administrator', 'editor', 'shop_manager' ],
				'max_upload_size' => 10, // 10 MB
				'accent_color'    => '#6366f1', // Beautiful modern indigo
				'polling_interval'=> 3000, // 3 seconds
				'auto_mode'       => 'auto', // theme mode
			];
			update_option( 'amp_settings', $default_settings );
		}
	}
}
