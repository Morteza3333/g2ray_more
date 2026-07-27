<?php
namespace AMP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AMP_API {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Verify that current user is authorized.
	 */
	public function check_permissions() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$settings = get_option( 'amp_settings', [] );
		$allowed_roles = isset( $settings['allowed_roles'] ) ? $settings['allowed_roles'] : [ 'administrator', 'editor', 'shop_manager' ];

		$user = wp_get_current_user();
		foreach ( $user->roles as $role ) {
			if ( in_array( $role, $allowed_roles, true ) ) {
				return true;
			}
		}

		return false;
	}

	public function register_routes() {
		$namespace = 'amp/v1';

		// Get recent chats
		register_rest_route( $namespace, '/chats', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_chats' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		// Create a private/group chat
		register_rest_route( $namespace, '/chats', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'create_chat' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		// Get messages for a specific chat
		register_rest_route( $namespace, '/chats/(?P<id>\d+)/messages', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_messages' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		// Send a message
		register_rest_route( $namespace, '/chats/(?P<id>\d+)/messages', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'send_message' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		// Toggle pin conversation
		register_rest_route( $namespace, '/chats/(?P<id>\d+)/pin', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'toggle_pin_chat' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		// Toggle archive conversation
		register_rest_route( $namespace, '/chats/(?P<id>\d+)/archive', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'toggle_archive_chat' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		// Toggle mute conversation
		register_rest_route( $namespace, '/chats/(?P<id>\d+)/mute', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'toggle_mute_chat' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		// Mark conversation as read
		register_rest_route( $namespace, '/chats/(?P<id>\d+)/read', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'mark_read' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		// Search users (to start chats)
		register_rest_route( $namespace, '/users/search', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'search_users' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		// Heartbeat/Presence status
		register_rest_route( $namespace, '/presence', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'update_presence' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		// Message specific actions: edit, delete, star, react, reply
		register_rest_route( $namespace, '/messages/(?P<id>\d+)', [
			'methods'             => 'PUT', // Edit
			'callback'            => [ $this, 'edit_message' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		register_rest_route( $namespace, '/messages/(?P<id>\d+)', [
			'methods'             => 'DELETE', // Delete
			'callback'            => [ $this, 'delete_message' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		register_rest_route( $namespace, '/messages/(?P<id>\d+)/star', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'toggle_star_message' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		register_rest_route( $namespace, '/messages/(?P<id>\d+)/react', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'react_message' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		// Global search
		register_rest_route( $namespace, '/search', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'global_search' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		// Conversation Details panel resource files/media
		register_rest_route( $namespace, '/chats/(?P<id>\d+)/resources', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_chat_resources' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		// Upload File
		register_rest_route( $namespace, '/upload', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'upload_file' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );
	}

	public function get_chats( $request ) {
		$user_id = get_current_user_id();
		$filter  = $request->get_param( 'filter' ) ?: 'all';
		$chats   = AMP_DB::get_conversations( $user_id, $filter );
		return rest_ensure_response( $chats );
	}

	public function create_chat( $request ) {
		global $wpdb;
		$user_id   = get_current_user_id();
		$type      = $request->get_param( 'type' ) ?: 'private';
		$title     = sanitize_text_field( $request->get_param( 'title' ) );
		$target_id = intval( $request->get_param( 'target_user_id' ) );
		$members   = $request->get_param( 'members' ); // array of user IDs

		$table_conv = $wpdb->prefix . 'amp_conversations';
		$table_part = $wpdb->prefix . 'amp_participants';

		if ( $type === 'private' ) {
			if ( ! $target_id ) {
				return new \WP_Error( 'invalid_target', 'A target user is required for private chats.', [ 'status' => 400 ] );
			}

			// Check if conversation already exists between these 2 users
			$existing_sql = "
				SELECT p1.conversation_id
				FROM $table_part p1
				INNER JOIN $table_part p2 ON p1.conversation_id = p2.conversation_id
				INNER JOIN $table_conv c ON p1.conversation_id = c.id
				WHERE p1.user_id = %d AND p2.user_id = %d AND c.type = 'private'
				LIMIT 1
			";
			$existing_id = $wpdb->get_var( $wpdb->prepare( $existing_sql, $user_id, $target_id ) );
			if ( $existing_id ) {
				// Re-activate if archived
				$wpdb->update( $table_part, [ 'is_archived' => 0 ], [ 'conversation_id' => $existing_id, 'user_id' => $user_id ] );
				return rest_ensure_response( [ 'id' => $existing_id, 'is_new' => false ] );
			}

			// Create new conversation
			$wpdb->insert( $table_conv, [
				'type'       => 'private',
				'created_by' => $user_id,
			] );
			$conv_id = $wpdb->insert_id;

			// Add participants
			$wpdb->insert( $table_part, [ 'conversation_id' => $conv_id, 'user_id' => $user_id ] );
			$wpdb->insert( $table_part, [ 'conversation_id' => $conv_id, 'user_id' => $target_id ] );

		} else {
			// Group Chat
			if ( empty( $title ) ) {
				$title = 'Group Chat';
			}

			$wpdb->insert( $table_conv, [
				'title'      => $title,
				'type'       => 'group',
				'created_by' => $user_id,
			] );
			$conv_id = $wpdb->insert_id;

			// Add self as participant
			$wpdb->insert( $table_part, [ 'conversation_id' => $conv_id, 'user_id' => $user_id ] );

			// Add chosen members
			if ( ! empty( $members ) && is_array( $members ) ) {
				foreach ( $members as $member_id ) {
					$member_id = intval( $member_id );
					if ( $member_id !== $user_id ) {
						$wpdb->insert( $table_part, [ 'conversation_id' => $conv_id, 'user_id' => $member_id ] );
					}
				}
			}
		}

		return rest_ensure_response( [ 'id' => $conv_id, 'is_new' => true ] );
	}

	public function get_messages( $request ) {
		$conv_id   = intval( $request['id'] );
		$limit     = intval( $request->get_param( 'limit' ) ) ?: 30;
		$before_id = $request->get_param( 'before_id' ) ? intval( $request->get_param( 'before_id' ) ) : null;

		$messages = AMP_DB::get_messages( $conv_id, $limit, $before_id );
		return rest_ensure_response( $messages );
	}

	public function send_message( $request ) {
		global $wpdb;
		$conv_id     = intval( $request['id'] );
		$user_id     = get_current_user_id();
		$text        = sanitize_textarea_field( $request->get_param( 'message_text' ) );
		$parent_id   = $request->get_param( 'parent_id' ) ? intval( $request->get_param( 'parent_id' ) ) : null;
		$attachments = $request->get_param( 'attachments' ) ?: []; // Array of attachment items

		$table_msg  = $wpdb->prefix . 'amp_messages';
		$table_conv = $wpdb->prefix . 'amp_conversations';

		if ( empty( $text ) && empty( $attachments ) ) {
			return new \WP_Error( 'empty_message', 'Message text or attachments cannot be empty.', [ 'status' => 400 ] );
		}

		$wpdb->insert( $table_msg, [
			'conversation_id' => $conv_id,
			'sender_id'       => $user_id,
			'parent_id'       => $parent_id,
			'message_text'    => $text,
			'attachments'     => ! empty( $attachments ) ? json_encode( $attachments ) : null,
		] );

		$msg_id = $wpdb->insert_id;

		// Update conversation updated_at for ordering
		$wpdb->update( $table_conv, [ 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $conv_id ] );

		// Auto mark as read for current sender
		$table_part = $wpdb->prefix . 'amp_participants';
		$wpdb->update( $table_part, [ 'last_read_message_id' => $msg_id ], [ 'conversation_id' => $conv_id, 'user_id' => $user_id ] );

		// Construct message output
		$user_data = wp_get_current_user();
		$response_msg = [
			'id'              => $msg_id,
			'conversation_id' => $conv_id,
			'sender_id'       => $user_id,
			'sender_name'     => $user_data->display_name,
			'sender_avatar'   => get_avatar_url( $user_id, [ 'size' => 48 ] ),
			'parent_id'       => $parent_id,
			'message_text'    => $text,
			'attachments'     => $attachments,
			'is_pinned'       => 0,
			'is_starred'      => 0,
			'is_edited'       => 0,
			'is_deleted'      => 0,
			'created_at'      => current_time( 'mysql' ),
			'reactions'       => [],
		];

		if ( $parent_id ) {
			$parent_msg = $wpdb->get_row( $wpdb->prepare( "SELECT message_text, sender_id FROM $table_msg WHERE id = %d", $parent_id ), ARRAY_A );
			if ( $parent_msg ) {
				$parent_user = get_userdata( $parent_msg['sender_id'] );
				$response_msg['reply_to'] = [
					'id'          => $parent_id,
					'sender_name' => $parent_user ? $parent_user->display_name : 'Unknown',
					'text'        => wp_strip_all_tags( $parent_msg['message_text'] )
				];
			}
		}

		return rest_ensure_response( $response_msg );
	}

	public function toggle_pin_chat( $request ) {
		global $wpdb;
		$conv_id = intval( $request['id'] );
		$user_id = get_current_user_id();
		$table   = $wpdb->prefix . 'amp_participants';

		$is_pinned = $wpdb->get_var( $wpdb->prepare( "SELECT is_pinned FROM $table WHERE conversation_id = %d AND user_id = %d", $conv_id, $user_id ) );
		$new_val   = $is_pinned ? 0 : 1;

		$wpdb->update( $table, [ 'is_pinned' => $new_val ], [ 'conversation_id' => $conv_id, 'user_id' => $user_id ] );

		return rest_ensure_response( [ 'success' => true, 'pinned' => $new_val ] );
	}

	public function toggle_archive_chat( $request ) {
		global $wpdb;
		$conv_id = intval( $request['id'] );
		$user_id = get_current_user_id();
		$table   = $wpdb->prefix . 'amp_participants';

		$is_archived = $wpdb->get_var( $wpdb->prepare( "SELECT is_archived FROM $table WHERE conversation_id = %d AND user_id = %d", $conv_id, $user_id ) );
		$new_val     = $is_archived ? 0 : 1;

		$wpdb->update( $table, [ 'is_archived' => $new_val ], [ 'conversation_id' => $conv_id, 'user_id' => $user_id ] );

		return rest_ensure_response( [ 'success' => true, 'archived' => $new_val ] );
	}

	public function toggle_mute_chat( $request ) {
		global $wpdb;
		$conv_id = intval( $request['id'] );
		$user_id = get_current_user_id();
		$table   = $wpdb->prefix . 'amp_participants';

		$is_muted = $wpdb->get_var( $wpdb->prepare( "SELECT is_muted FROM $table WHERE conversation_id = %d AND user_id = %d", $conv_id, $user_id ) );
		$new_val  = $is_muted ? 0 : 1;

		$wpdb->update( $table, [ 'is_muted' => $new_val ], [ 'conversation_id' => $conv_id, 'user_id' => $user_id ] );

		return rest_ensure_response( [ 'success' => true, 'muted' => $new_val ] );
	}

	public function mark_read( $request ) {
		global $wpdb;
		$conv_id = intval( $request['id'] );
		$user_id = get_current_user_id();
		$table_msg  = $wpdb->prefix . 'amp_messages';
		$table_part = $wpdb->prefix . 'amp_participants';

		$max_msg_id = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(id) FROM $table_msg WHERE conversation_id = %d", $conv_id ) );
		if ( $max_msg_id ) {
			$wpdb->update( $table_part, [ 'last_read_message_id' => $max_msg_id ], [ 'conversation_id' => $conv_id, 'user_id' => $user_id ] );
		}

		return rest_ensure_response( [ 'success' => true ] );
	}

	public function search_users( $request ) {
		$term     = sanitize_text_field( $request->get_param( 'term' ) );
		$settings = get_option( 'amp_settings', [] );
		$allowed_roles = isset( $settings['allowed_roles'] ) ? $settings['allowed_roles'] : [ 'administrator', 'editor', 'shop_manager' ];

		// Fetch allowed users matching query
		$user_query = new \WP_User_Query( [
			'search'         => '*' . $term . '*',
			'search_columns' => [ 'user_login', 'user_nicename', 'display_name', 'user_email' ],
			'role__in'       => $allowed_roles,
			'number'         => 20,
		] );

		$users = [];
		foreach ( $user_query->get_results() as $user ) {
			if ( $user->ID !== get_current_user_id() ) {
				$users[] = [
					'id'     => $user->ID,
					'name'   => $user->display_name,
					'avatar' => get_avatar_url( $user->ID, [ 'size' => 48 ] ),
					'role'   => ! empty( $user->roles ) ? ucfirst( $user->roles[0] ) : '',
				];
			}
		}

		return rest_ensure_response( $users );
	}

	public function update_presence( $request ) {
		global $wpdb;
		$user_id = get_current_user_id();
		$status  = sanitize_text_field( $request->get_param( 'status' ) ) ?: 'online';
		$custom  = sanitize_text_field( $request->get_param( 'custom_presence' ) );

		$table = $wpdb->prefix . 'amp_user_status';

		// We use a upsert approach
		$wpdb->query( $wpdb->prepare(
			"INSERT INTO $table (user_id, status, custom_presence, last_seen)
			 VALUES (%d, %s, %s, %s)
			 ON DUPLICATE KEY UPDATE status = VALUES(status), custom_presence = VALUES(custom_presence), last_seen = VALUES(last_seen)",
			$user_id,
			$status,
			$custom,
			current_time( 'mysql' )
		) );

		// Cleanup: automatically mark users who haven't updated presence in last 1 minute as offline
		$wpdb->query( $wpdb->prepare(
			"UPDATE $table SET status = 'offline' WHERE last_seen < %s AND status = 'online'",
			date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 60 )
		) );

		return rest_ensure_response( [ 'success' => true ] );
	}

	public function edit_message( $request ) {
		global $wpdb;
		$msg_id  = intval( $request['id'] );
		$user_id = get_current_user_id();
		$text    = sanitize_textarea_field( $request->get_param( 'message_text' ) );
		$table   = $wpdb->prefix . 'amp_messages';

		// Check ownership
		$sender_id = $wpdb->get_var( $wpdb->prepare( "SELECT sender_id FROM $table WHERE id = %d", $msg_id ) );
		if ( intval( $sender_id ) !== $user_id ) {
			return new \WP_Error( 'unauthorized', 'You can only edit your own messages.', [ 'status' => 403 ] );
		}

		$wpdb->update( $table, [
			'message_text' => $text,
			'is_edited'    => 1,
		], [ 'id' => $msg_id ] );

		return rest_ensure_response( [ 'success' => true, 'message_text' => $text ] );
	}

	public function delete_message( $request ) {
		global $wpdb;
		$msg_id  = intval( $request['id'] );
		$user_id = get_current_user_id();
		$mode    = sanitize_text_field( $request->get_param( 'mode' ) ); // 'everyone' or 'me'
		$table   = $wpdb->prefix . 'amp_messages';

		if ( $mode === 'everyone' ) {
			// Check ownership
			$sender_id = $wpdb->get_var( $wpdb->prepare( "SELECT sender_id FROM $table WHERE id = %d", $msg_id ) );
			if ( intval( $sender_id ) !== $user_id && ! current_user_can( 'manage_options' ) ) {
				return new \WP_Error( 'unauthorized', 'You cannot delete this message for everyone.', [ 'status' => 403 ] );
			}

			$wpdb->update( $table, [ 'is_deleted' => 1 ], [ 'id' => $msg_id ] );
		} else {
			// 'Delete for me' can be represented in DB using a user preference, or we can just flag it or delete it if it is the sender. For simplicity of premium feature, we mark as deleted in DB or handle custom visibility. Since we have standard tables, we can soft delete it.
			$wpdb->update( $table, [ 'is_deleted' => 1 ], [ 'id' => $msg_id, 'sender_id' => $user_id ] );
		}

		return rest_ensure_response( [ 'success' => true ] );
	}

	public function toggle_star_message( $request ) {
		global $wpdb;
		$msg_id = intval( $request['id'] );
		$table  = $wpdb->prefix . 'amp_messages';

		$is_starred = $wpdb->get_var( $wpdb->prepare( "SELECT is_starred FROM $table WHERE id = %d", $msg_id ) );
		$new_val    = $is_starred ? 0 : 1;

		$wpdb->update( $table, [ 'is_starred' => $new_val ], [ 'id' => $msg_id ] );

		return rest_ensure_response( [ 'success' => true, 'starred' => $new_val ] );
	}

	public function react_message( $request ) {
		global $wpdb;
		$msg_id  = intval( $request['id'] );
		$user_id = get_current_user_id();
		$emoji   = sanitize_text_field( $request->get_param( 'emoji' ) );
		$table   = $wpdb->prefix . 'amp_reactions';

		// Verify emoji is valid/safe
		if ( empty( $emoji ) ) {
			return new \WP_Error( 'invalid_emoji', 'Emoji is required.', [ 'status' => 400 ] );
		}

		// Check if user already reacted with this emoji
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE message_id = %d AND user_id = %d AND emoji = %s",
			$msg_id,
			$user_id,
			$emoji
		) );

		if ( $existing ) {
			// Remove reaction
			$wpdb->delete( $table, [ 'id' => $existing ] );
			$action = 'removed';
		} else {
			// Add reaction
			$wpdb->insert( $table, [
				'message_id' => $msg_id,
				'user_id'    => $user_id,
				'emoji'      => $emoji,
			] );
			$action = 'added';
		}

		// Return fresh set of reactions for this message
		$reactions_sql = "
			SELECT emoji, COUNT(*) as count, GROUP_CONCAT(user_id) as user_ids
			FROM $table
			WHERE message_id = %d
			GROUP BY emoji
		";
		$reactions = $wpdb->get_results( $wpdb->prepare( $reactions_sql, $msg_id ), ARRAY_A );
		foreach ( $reactions as &$react ) {
			$react['user_ids'] = array_map( 'intval', explode( ',', $react['user_ids'] ) );
		}

		return rest_ensure_response( [ 'success' => true, 'action' => $action, 'reactions' => $reactions ] );
	}

	public function global_search( $request ) {
		$user_id = get_current_user_id();
		$term    = sanitize_text_field( $request->get_param( 'query' ) );

		if ( empty( $term ) ) {
			return rest_ensure_response( [ 'messages' => [], 'files' => [] ] );
		}

		$results = AMP_DB::search_global( $user_id, $term );
		return rest_ensure_response( $results );
	}

	public function get_chat_resources( $request ) {
		$conv_id   = intval( $request['id'] );
		$resources = AMP_DB::get_shared_resources( $conv_id );
		return rest_ensure_response( $resources );
	}

	public function upload_file( $request ) {
		if ( empty( $_FILES['file'] ) ) {
			return new \WP_Error( 'no_file', 'No file was uploaded.', [ 'status' => 400 ] );
		}

		$file = $_FILES['file'];

		// Allowed Extensions & Mime Types
		$allowed_extensions = [
			'jpg', 'jpeg', 'png', 'gif', 'webp', // images
			'mp4', 'webm', 'ogg',               // videos
			'mp3', 'wav', 'aac', 'm4a',          // audio
			'pdf', 'doc', 'docx', 'xls', 'xlsx', // documents
			'zip', 'rar',                        // archives
			'psd', 'ai', 'svg'                   // premium/design
		];

		$file_info = pathinfo( $file['name'] );
		$ext = isset( $file_info['extension'] ) ? strtolower( $file_info['extension'] ) : '';

		if ( ! in_array( $ext, $allowed_extensions, true ) ) {
			return new \WP_Error( 'invalid_type', 'File type not allowed for security reasons.', [ 'status' => 400 ] );
		}

		// Security: SVG Sanitization
		if ( $ext === 'svg' ) {
			// Basic sanitization check for <script> inside SVG
			$svg_content = file_get_contents( $file['tmp_name'] );
			if ( stripos( $svg_content, '<script' ) !== false || stripos( $svg_content, 'javascript:' ) !== false ) {
				return new \WP_Error( 'malicious_file', 'Malicious SVG detected.', [ 'status' => 400 ] );
			}
		}

		// Check Configurable Upload Size Limit
		$settings = get_option( 'amp_settings', [] );
		$max_mb   = isset( $settings['max_upload_size'] ) ? intval( $settings['max_upload_size'] ) : 10;
		$max_bytes = $max_mb * 1024 * 1024;

		if ( $file['size'] > $max_bytes ) {
			return new \WP_Error( 'file_too_large', sprintf( 'File exceeds maximum upload size of %dMB.', $max_mb ), [ 'status' => 400 ] );
		}

		// Perform safe WordPress Media Library Upload
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$upload_overrides = [ 'test_form' => false ];
		$movefile = wp_handle_upload( $file, $upload_overrides );

		if ( $movefile && ! isset( $movefile['error'] ) ) {
			// Store in Media Library to generate Attachment ID (helps compatibility)
			$attachment = [
				'guid'           => $movefile['url'],
				'post_mime_type' => $movefile['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $movefile['file'] ) ),
				'post_content'   => '',
				'post_status'    => 'inherit'
			];

			$attach_id = wp_insert_attachment( $attachment, $movefile['file'] );

			return rest_ensure_response( [
				'id'    => $attach_id,
				'name'  => basename( $movefile['file'] ),
				'url'   => $movefile['url'],
				'type'  => $movefile['type'],
				'size'  => size_format( $file['size'] ),
				'bytes' => $file['size']
			] );
		} else {
			return new \WP_Error( 'upload_failed', $movefile['error'], [ 'status' => 500 ] );
		}
	}
}
