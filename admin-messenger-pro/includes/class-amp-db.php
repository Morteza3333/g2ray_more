<?php
namespace AMP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AMP_DB {

	public static function get_conversations( $user_id, $filter = 'all' ) {
		global $wpdb;

		$table_conv   = $wpdb->prefix . 'amp_conversations';
		$table_part   = $wpdb->prefix . 'amp_participants';
		$table_msg    = $wpdb->prefix . 'amp_messages';
		$table_status = $wpdb->prefix . 'amp_user_status';

		$where_clause = "p.user_id = %d";
		if ( $filter === 'pinned' ) {
			$where_clause .= " AND p.is_pinned = 1";
		} elseif ( $filter === 'archived' ) {
			$where_clause .= " AND p.is_archived = 1";
		} else {
			$where_clause .= " AND p.is_archived = 0";
		}

		$sql = "
			SELECT
				c.*,
				p.is_pinned,
				p.is_archived,
				p.is_muted,
				p.last_read_message_id,
				(
					SELECT COUNT(*)
					FROM $table_msg m2
					WHERE m2.conversation_id = c.id
					  AND m2.id > IFNULL(p.last_read_message_id, 0)
					  AND m2.sender_id != p.user_id
					  AND m2.is_deleted = 0
				) as unread_count
			FROM $table_conv c
			INNER JOIN $table_part p ON c.id = p.conversation_id
			WHERE $where_clause
			ORDER BY p.is_pinned DESC, c.updated_at DESC
		";

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $user_id ), ARRAY_A );

		// Enriched conversations with target participant details if private, or members count if group
		foreach ( $results as &$conv ) {
			$conv_id = $conv['id'];
			if ( $conv['type'] === 'private' ) {
				// Get other participant
				$part_sql = "SELECT user_id FROM $table_part WHERE conversation_id = %d AND user_id != %d LIMIT 1";
				$other_user_id = $wpdb->get_var( $wpdb->prepare( $part_sql, $conv_id, $user_id ) );
				if ( $other_user_id ) {
					$user_data = get_userdata( $other_user_id );
					if ( $user_data ) {
						$conv['title'] = $user_data->display_name;
						$conv['avatar'] = get_avatar_url( $other_user_id, [ 'size' => 64 ] );
						$conv['other_user_id'] = $other_user_id;

						// Get presence status
						$status_sql = "SELECT status, custom_presence, last_seen FROM $table_status WHERE user_id = %d";
						$status_row = $wpdb->get_row( $wpdb->prepare( $status_sql, $other_user_id ), ARRAY_A );
						if ( $status_row ) {
							$conv['status'] = $status_row['status'];
							$conv['custom_presence'] = $status_row['custom_presence'];
							$conv['last_seen'] = $status_row['last_seen'];
						} else {
							$conv['status'] = 'offline';
							$conv['custom_presence'] = '';
							$conv['last_seen'] = '';
						}
					}
				}
			} else {
				// Group avatar (placeholder or letters)
				$conv['avatar'] = '';
				// Get group members count
				$count_sql = "SELECT COUNT(*) FROM $table_part WHERE conversation_id = %d";
				$conv['members_count'] = $wpdb->get_var( $wpdb->prepare( $count_sql, $conv_id ) );
			}

			// Get last message
			$last_msg_sql = "
				SELECT id, sender_id, message_text, attachments, created_at, is_deleted
				FROM $table_msg
				WHERE conversation_id = %d AND is_deleted = 0
				ORDER BY id DESC LIMIT 1
			";
			$last_msg = $wpdb->get_row( $wpdb->prepare( $last_msg_sql, $conv_id ), ARRAY_A );
			if ( $last_msg ) {
				if ( ! empty( $last_msg['attachments'] ) ) {
					$attachments = json_decode( $last_msg['attachments'], true );
					if ( ! empty( $attachments ) ) {
						$last_msg['message_text'] = '📎 Shared ' . count( $attachments ) . ' file(s)';
					}
				}
				$conv['last_message'] = $last_msg;
			} else {
				$conv['last_message'] = null;
			}
		}

		return $results;
	}

	public static function get_messages( $conversation_id, $limit = 30, $before_id = null ) {
		global $wpdb;

		$table_msg = $wpdb->prefix . 'amp_messages';
		$table_reactions = $wpdb->prefix . 'amp_reactions';

		$where = "conversation_id = %d AND is_deleted = 0";
		$params = [ $conversation_id ];

		if ( $before_id ) {
			$where .= " AND id < %d";
			$params[] = $before_id;
		}

		$sql = "
			SELECT * FROM $table_msg
			WHERE $where
			ORDER BY id DESC
			LIMIT %d
		";
		$params[] = $limit;

		$messages = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

		// Sort messages back to chronological order for output
		$messages = array_reverse( $messages );

		// Enrich with sender details, reactions, and parsing attachments
		foreach ( $messages as &$msg ) {
			$sender_id = $msg['sender_id'];
			$user_data = get_userdata( $sender_id );
			$msg['sender_name'] = $user_data ? $user_data->display_name : 'Deleted User';
			$msg['sender_avatar'] = get_avatar_url( $sender_id, [ 'size' => 48 ] );

			$msg['attachments'] = $msg['attachments'] ? json_decode( $msg['attachments'], true ) : [];

			// Get reactions
			$reaction_sql = "
				SELECT emoji, COUNT(*) as count, GROUP_CONCAT(user_id) as user_ids
				FROM $table_reactions
				WHERE message_id = %d
				GROUP BY emoji
			";
			$reactions = $wpdb->get_results( $wpdb->prepare( $reaction_sql, $msg['id'] ), ARRAY_A );
			foreach ( $reactions as &$react ) {
				$react['user_ids'] = array_map( 'intval', explode( ',', $react['user_ids'] ) );
			}
			$msg['reactions'] = $reactions;

			// Handle Thread Reply previews
			if ( ! empty( $msg['parent_id'] ) ) {
				$parent_sql = "SELECT message_text, sender_id FROM $table_msg WHERE id = %d";
				$parent_msg = $wpdb->get_row( $wpdb->prepare( $parent_sql, $msg['parent_id'] ), ARRAY_A );
				if ( $parent_msg ) {
					$parent_user = get_userdata( $parent_msg['sender_id'] );
					$msg['reply_to'] = [
						'id'          => $msg['parent_id'],
						'sender_name' => $parent_user ? $parent_user->display_name : 'Unknown',
						'text'        => wp_strip_all_tags( $parent_msg['message_text'] )
					];
				}
			}
		}

		return $messages;
	}

	public static function search_global( $user_id, $query ) {
		global $wpdb;

		$table_msg  = $wpdb->prefix . 'amp_messages';
		$table_part = $wpdb->prefix . 'amp_participants';

		$like = '%' . $wpdb->esc_like( $query ) . '%';

		// Get conversations user is a part of
		$convs_sql = "SELECT conversation_id FROM $table_part WHERE user_id = %d";
		$conv_ids = $wpdb->get_col( $wpdb->prepare( $convs_sql, $user_id ) );

		if ( empty( $conv_ids ) ) {
			return [ 'messages' => [], 'files' => [] ];
		}

		$in_clause = implode( ',', array_map( 'intval', $conv_ids ) );

		// Message results
		$msg_sql = "
			SELECT m.*, c.title as conversation_title, c.type as conversation_type
			FROM $table_msg m
			INNER JOIN {$wpdb->prefix}amp_conversations c ON m.conversation_id = c.id
			WHERE m.conversation_id IN ($in_clause)
			  AND m.message_text LIKE %s
			  AND m.is_deleted = 0
			ORDER BY m.id DESC
			LIMIT 30
		";
		$messages = $wpdb->get_results( $wpdb->prepare( $msg_sql, $like ), ARRAY_A );
		foreach ( $messages as &$msg ) {
			$user_data = get_userdata( $msg['sender_id'] );
			$msg['sender_name'] = $user_data ? $user_data->display_name : 'Unknown';
		}

		// File results (stored as json in attachments)
		$file_sql = "
			SELECT m.*, c.title as conversation_title, c.type as conversation_type
			FROM $table_msg m
			INNER JOIN {$wpdb->prefix}amp_conversations c ON m.conversation_id = c.id
			WHERE m.conversation_id IN ($in_clause)
			  AND m.attachments LIKE %s
			  AND m.is_deleted = 0
			ORDER BY m.id DESC
		";
		$file_candidates = $wpdb->get_results( $wpdb->prepare( $file_sql, $like ), ARRAY_A );
		$files = [];

		foreach ( $file_candidates as $msg ) {
			$attachments = json_decode( $msg['attachments'], true );
			if ( ! empty( $attachments ) ) {
				foreach ( $attachments as $file ) {
					if ( stripos( $file['name'], $query ) !== false ) {
						$files[] = [
							'message_id'         => $msg['id'],
							'conversation_id'    => $msg['conversation_id'],
							'conversation_title' => $msg['conversation_title'],
							'sender_id'          => $msg['sender_id'],
							'file'               => $file,
							'created_at'         => $msg['created_at']
						];
					}
				}
			}
		}

		return [
			'messages' => $messages,
			'files'    => array_slice( $files, 0, 30 )
		];
	}

	public static function get_shared_resources( $conversation_id ) {
		global $wpdb;

		$table_msg = $wpdb->prefix . 'amp_messages';
		$sql = "
			SELECT id, sender_id, attachments, created_at
			FROM $table_msg
			WHERE conversation_id = %d AND attachments IS NOT NULL AND attachments != '[]' AND is_deleted = 0
			ORDER BY id DESC
		";

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $conversation_id ), ARRAY_A );
		$shared_media = [];
		$shared_files = [];

		foreach ( $results as $row ) {
			$attachments = json_decode( $row['attachments'], true );
			if ( ! empty( $attachments ) ) {
				$sender = get_userdata( $row['sender_id'] );
				$sender_name = $sender ? $sender->display_name : 'Unknown';

				foreach ( $attachments as $file ) {
					$resource = [
						'message_id'  => $row['id'],
						'sender_name' => $sender_name,
						'name'        => $file['name'],
						'url'         => $file['url'],
						'type'        => $file['type'],
						'size'        => $file['size'],
						'created_at'  => $row['created_at']
					];

					if ( strpos( $file['type'], 'image' ) !== false || strpos( $file['type'], 'video' ) !== false ) {
						$shared_media[] = $resource;
					} else {
						$shared_files[] = $resource;
					}
				}
			}
		}

		return [
			'media' => $shared_media,
			'files' => $shared_files
		];
	}

	public static function get_dashboard_metrics() {
		global $wpdb;

		$table_conv = $wpdb->prefix . 'amp_conversations';
		$table_msg  = $wpdb->prefix . 'amp_messages';
		$table_status = $wpdb->prefix . 'amp_user_status';

		$total_conversations = $wpdb->get_var( "SELECT COUNT(*) FROM $table_conv" );
		$total_messages      = $wpdb->get_var( "SELECT COUNT(*) FROM $table_msg WHERE is_deleted = 0" );
		$online_users        = $wpdb->get_var( "SELECT COUNT(*) FROM $table_status WHERE status = 'online'" );

		// Storage and Files calculations
		$attachments_rows = $wpdb->get_col( "SELECT attachments FROM $table_msg WHERE attachments IS NOT NULL AND attachments != '[]' AND is_deleted = 0" );
		$total_files = 0;
		$total_bytes = 0;

		foreach ( $attachments_rows as $row ) {
			$attachments = json_decode( $row, true );
			if ( ! empty( $attachments ) ) {
				foreach ( $attachments as $file ) {
					$total_files++;
					if ( isset( $file['bytes'] ) ) {
						$total_bytes += intval( $file['bytes'] );
					}
				}
			}
		}

		// Convert bytes to standard humans format
		if ( $total_bytes >= 1073741824 ) {
			$storage = round( $total_bytes / 1073741824, 2 ) . ' GB';
		} elseif ( $total_bytes >= 1048576 ) {
			$storage = round( $total_bytes / 1048576, 2 ) . ' MB';
		} else {
			$storage = round( $total_bytes / 1024, 2 ) . ' KB';
		}

		return [
			'conversations' => $total_conversations,
			'messages'      => $total_messages,
			'online_users'  => $online_users,
			'total_files'   => $total_files,
			'storage_usage' => $storage
		];
	}
}
