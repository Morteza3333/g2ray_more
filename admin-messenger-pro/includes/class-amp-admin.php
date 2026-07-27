<?php
namespace AMP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AMP_Admin {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', [ $this, 'register_admin_pages' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_dashboard_setup', [ $this, 'add_dashboard_widget' ] );
	}

	/**
	 * Verify that current user is authorized to access AMP.
	 */
	public function current_user_can_access() {
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

	public function register_admin_pages() {
		// Only show menu if user role is permitted
		if ( ! $this->current_user_can_access() ) {
			return;
		}

		// Main Chat Room Page
		add_menu_page(
			'Admin Messenger',
			'Messenger Pro',
			'read', // Broad capabilities; we check role permissions dynamically in controller
			'admin-messenger-pro',
			[ $this, 'render_messenger_page' ],
			'dashicons-format-chat',
			3
		);

		// Settings Sub-Page (only for actual administrators)
		add_submenu_page(
			'admin-messenger-pro',
			'Messenger Settings',
			'Settings',
			'manage_options',
			'admin-messenger-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	public function enqueue_assets( $hook ) {
		if ( 'toplevel_page_admin-messenger-pro' !== $hook && 'messenger-pro_page_admin-messenger-settings' !== $hook && 'index.php' !== $hook ) {
			return;
		}

		// Enqueue standard dashicons & media uploader for fallback or standard behaviors
		wp_enqueue_media();

		// Glassmorphic Theme CSS
		wp_enqueue_style(
			'amp-chat-css',
			AMP_PLUGIN_URL . 'assets/css/amp-chat.css',
			[],
			AMP_VERSION
		);

		// Core Premium Vanilla JavaScript Application
		wp_enqueue_script(
			'amp-chat-js',
			AMP_PLUGIN_URL . 'assets/js/amp-chat.js',
			[],
			AMP_VERSION,
			true
		);

		// Pass necessary variables to JS environment
		$settings = get_option( 'amp_settings', [] );
		$current_user = wp_get_current_user();

		wp_localize_script( 'amp-chat-js', 'ampVars', [
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'restUrl'        => esc_url_raw( rest_url( 'amp/v1' ) ),
			'nonce'          => wp_create_nonce( 'wp_rest' ),
			'currentUserId'  => get_current_user_id(),
			'currentUserName'=> $current_user->display_name,
			'currentUserAvatar'=> get_avatar_url( get_current_user_id() ),
			'pollingInterval'=> isset( $settings['polling_interval'] ) ? intval( $settings['polling_interval'] ) : 3000,
			'accentColor'    => isset( $settings['accent_color'] ) ? sanitize_hex_color( $settings['accent_color'] ) : '#6366f1',
			'themeMode'      => isset( $settings['theme_mode'] ) ? sanitize_text_field( $settings['theme_mode'] ) : 'auto',
			'maxUploadSize'  => isset( $settings['max_upload_size'] ) ? intval( $settings['max_upload_size'] ) : 10,
		] );
	}

	public function render_messenger_page() {
		// Verify access control
		if ( ! $this->current_user_can_access() ) {
			wp_die( 'You do not have sufficient permissions to access the Admin Messenger.' );
		}

		$current_user = wp_get_current_user();
		?>
		<div class="amp-app-container">
			<div class="amp-glass-wrapper">

				<!-- Sidebar (Left) -->
				<div class="amp-sidebar">
					<!-- User Profile Header -->
					<div class="amp-profile-section">
						<div class="amp-avatar-wrapper">
							<img src="<?php echo esc_url( get_avatar_url( $current_user->ID ) ); ?>" class="amp-profile-avatar" alt="Avatar">
							<span class="amp-presence-dot online"></span>
						</div>
						<div class="amp-profile-info">
							<span class="amp-profile-name"><?php echo esc_html( $current_user->display_name ); ?></span>
							<select id="amp-my-presence" class="amp-presence-select">
								<option value="online">🟢 Online</option>
								<option value="dnd">🔴 Do Not Disturb</option>
								<option value="offline">⚪ Invisible</option>
							</select>
						</div>
					</div>

					<!-- Search & Tabs -->
					<div class="amp-search-box">
						<input type="text" id="amp-global-search" placeholder="Search chats, messages, files...">
						<i class="dashicons dashicons-search"></i>
					</div>

					<div class="amp-tabs">
						<button class="amp-tab-btn active" data-tab="all">All</button>
						<button class="amp-tab-btn" data-tab="pinned">📌 Pinned</button>
						<button class="amp-tab-btn" data-tab="groups">👥 Groups</button>
						<button class="amp-tab-btn" data-tab="archived">📦 Archived</button>
					</div>

					<!-- Recent Chat List -->
					<div class="amp-chats-list" id="amp-chats-list-container">
						<div class="amp-loading-spinner"></div>
					</div>

					<!-- New Chat / Group Trigger Buttons -->
					<div class="amp-sidebar-actions">
						<button id="amp-new-private-btn" class="amp-action-btn-primary">
							<span class="dashicons dashicons-plus"></span> New Chat
						</button>
						<button id="amp-new-group-btn" class="amp-action-btn-secondary">
							<span class="dashicons dashicons-groups"></span> New Group
						</button>
					</div>
				</div>

				<!-- Chat Window (Center) -->
				<div class="amp-chat-window amp-empty" id="amp-chat-window-main">
					<!-- Empty state placeholder -->
					<div class="amp-chat-placeholder">
						<span class="dashicons dashicons-format-chat amp-placeholder-icon"></span>
						<h3>Welcome to Admin Messenger Pro</h3>
						<p>Select a conversation from the sidebar or start a new chat to begin collaborating in real-time.</p>
					</div>

					<!-- Chat Main UI Header -->
					<div class="amp-chat-header" style="display: none;">
						<div class="amp-chat-header-info">
							<img src="" id="amp-active-avatar" class="amp-active-avatar" alt="Avatar">
							<div>
								<h4 id="amp-active-title">Loading...</h4>
								<span id="amp-active-presence" class="amp-active-presence">offline</span>
							</div>
						</div>
						<div class="amp-chat-header-actions">
							<button id="amp-search-in-chat" title="Search inside conversation">
								<span class="dashicons dashicons-search"></span>
							</button>
							<button id="amp-active-pin" title="Pin Conversation">
								<span class="dashicons dashicons-admin-links"></span>
							</button>
							<button id="amp-active-archive" title="Archive Conversation">
								<span class="dashicons dashicons-archive"></span>
							</button>
							<button id="amp-active-mute" title="Mute/Unmute Notifications">
								<span class="dashicons dashicons-volume-off"></span>
							</button>
							<button id="amp-info-toggle" title="Conversation Info">
								<span class="dashicons dashicons-info"></span>
							</button>
						</div>
					</div>

					<!-- Messages Scroller Area -->
					<div class="amp-chat-scroller" id="amp-chat-messages" style="display: none;">
						<!-- Dynamic message bubbles go here -->
					</div>

					<!-- Quote / Thread Reply Banner -->
					<div class="amp-reply-banner" id="amp-reply-banner" style="display: none;">
						<div class="amp-reply-banner-content">
							<strong id="amp-reply-banner-sender">Replying to ...</strong>
							<p id="amp-reply-banner-text">Text preview</p>
						</div>
						<button id="amp-reply-banner-close" class="dashicons dashicons-no-alt"></button>
					</div>

					<!-- Active Typing Indicator Banner -->
					<div class="amp-typing-indicator" id="amp-typing-indicator" style="display: none;">
						<span class="amp-typing-dots"><span></span><span></span><span></span></span>
						<span id="amp-typing-text">Someone is typing...</span>
					</div>

					<!-- Chat Form / Input Box (Bottom) -->
					<div class="amp-chat-footer" style="display: none;">
						<div class="amp-footer-actions">
							<!-- Voice Message Record Button -->
							<button id="amp-voice-record-btn" class="amp-footer-btn" title="Record Voice Message">
								<span class="dashicons dashicons-microphone"></span>
							</button>
							<!-- Add Attachment Button -->
							<button id="amp-attach-file-btn" class="amp-footer-btn" title="Attach Files">
								<span class="dashicons dashicons-paperclip"></span>
							</button>
						</div>

						<div class="amp-input-wrapper">
							<textarea id="amp-message-textarea" placeholder="Type your message here... Use @username to mention. Supports Markdown."></textarea>
							<!-- Emoji Picker Toggle -->
							<button id="amp-emoji-picker-btn" class="amp-emoji-toggle">😊</button>
						</div>

						<button id="amp-send-message-btn" class="amp-send-btn">
							<span class="dashicons dashicons-share-alt2"></span>
						</button>
					</div>
				</div>

				<!-- Right Panel (Sidebar details) -->
				<div class="amp-right-panel collapsed" id="amp-right-panel-main">
					<div class="amp-panel-header">
						<h3>Conversation Details</h3>
						<button id="amp-panel-close" class="dashicons dashicons-no-alt"></button>
					</div>
					<div class="amp-panel-body">
						<div class="amp-panel-section">
							<h4>📌 Pinned Messages</h4>
							<div class="amp-panel-pinned-list" id="amp-pinned-messages-list">
								<p class="amp-empty-text">No pinned messages yet.</p>
							</div>
						</div>
						<div class="amp-panel-section">
							<h4>📎 Shared Files</h4>
							<div class="amp-panel-files-list" id="amp-shared-files-list">
								<p class="amp-empty-text">No files shared yet.</p>
							</div>
						</div>
						<div class="amp-panel-section">
							<h4>🖼️ Shared Media</h4>
							<div class="amp-panel-media-grid" id="amp-shared-media-grid">
								<p class="amp-empty-text">No media shared yet.</p>
							</div>
						</div>
					</div>
				</div>

			</div>
		</div>

		<!-- Dialog: Create Private Chat -->
		<div class="amp-dialog-overlay" id="amp-dialog-private" style="display: none;">
			<div class="amp-dialog-box amp-glass">
				<h3>Start a Private Chat</h3>
				<div class="amp-dialog-body">
					<div class="amp-search-box">
						<input type="text" id="amp-user-search-input" placeholder="Search staff by name or username...">
						<i class="dashicons dashicons-search"></i>
					</div>
					<div class="amp-dialog-user-results" id="amp-user-search-results">
						<!-- User items go here -->
					</div>
				</div>
				<div class="amp-dialog-footer">
					<button class="amp-btn-secondary" id="amp-dialog-private-cancel">Cancel</button>
				</div>
			</div>
		</div>

		<!-- Dialog: Create Group Chat -->
		<div class="amp-dialog-overlay" id="amp-dialog-group" style="display: none;">
			<div class="amp-dialog-box amp-glass">
				<h3>Create a New Collaboration Group</h3>
				<div class="amp-dialog-body">
					<label for="amp-group-title">Group Title</label>
					<input type="text" id="amp-group-title" placeholder="e.g. Sales Team, Developers...">

					<label style="margin-top: 15px; display: block;">Select Members</label>
					<div class="amp-search-box">
						<input type="text" id="amp-group-user-search" placeholder="Search members to add...">
						<i class="dashicons dashicons-search"></i>
					</div>
					<div class="amp-dialog-user-results" id="amp-group-user-results" style="max-height: 150px; overflow-y: auto;">
						<!-- User search matching -->
					</div>
					<div class="amp-selected-members-container" id="amp-group-selected-members">
						<!-- Selected member tags -->
					</div>
				</div>
				<div class="amp-dialog-footer">
					<button class="amp-btn-secondary" id="amp-dialog-group-cancel">Cancel</button>
					<button class="amp-btn-primary" id="amp-dialog-group-submit">Create Group</button>
				</div>
			</div>
		</div>

		<!-- Dialog: Lightbox Image/Video Viewer -->
		<div class="amp-lightbox-overlay" id="amp-lightbox" style="display: none;">
			<button class="amp-lightbox-close dashicons dashicons-no-alt"></button>
			<div class="amp-lightbox-content">
				<img src="" id="amp-lightbox-img" style="display: none;">
				<video id="amp-lightbox-video" controls style="display: none;"></video>
			</div>
		</div>
		<?php
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Access denied.' );
		}

		// Handle Form Save
		if ( isset( $_POST['amp_submit_settings'] ) && check_admin_referer( 'amp_save_settings', 'amp_nonce' ) ) {
			$allowed_roles = isset( $_POST['allowed_roles'] ) ? array_map( 'sanitize_text_field', $_POST['allowed_roles'] ) : [];
			$max_size      = isset( $_POST['max_upload_size'] ) ? intval( $_POST['max_upload_size'] ) : 10;
			$accent_color  = isset( $_POST['accent_color'] ) ? sanitize_hex_color( $_POST['accent_color'] ) : '#6366f1';
			$polling_int   = isset( $_POST['polling_interval'] ) ? intval( $_POST['polling_interval'] ) : 3000;
			$theme_mode    = isset( $_POST['theme_mode'] ) ? sanitize_text_field( $_POST['theme_mode'] ) : 'auto';

			$new_settings = [
				'allowed_roles'   => $allowed_roles,
				'max_upload_size' => $max_size,
				'accent_color'    => $accent_color,
				'polling_interval'=> $polling_int,
				'theme_mode'      => $theme_mode,
			];

			update_option( 'amp_settings', $new_settings );
			echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
		}

		$settings      = get_option( 'amp_settings', [] );
		$allowed_roles = isset( $settings['allowed_roles'] ) ? $settings['allowed_roles'] : [ 'administrator', 'editor', 'shop_manager' ];
		$max_size      = isset( $settings['max_upload_size'] ) ? intval( $settings['max_upload_size'] ) : 10;
		$accent_color  = isset( $settings['accent_color'] ) ? $settings['accent_color'] : '#6366f1';
		$polling_int   = isset( $settings['polling_interval'] ) ? intval( $settings['polling_interval'] ) : 3000;
		$theme_mode    = isset( $settings['theme_mode'] ) ? $settings['theme_mode'] : 'auto';

		// All available roles
		global $wp_roles;
		$roles = $wp_roles->get_names();
		?>
		<div class="wrap amp-settings-wrap">
			<h1>Admin Messenger Pro Settings</h1>
			<p class="description">Configure the premium features, styling constraints, and user access policies for your real-time messenger.</p>

			<form method="POST" action="" class="amp-settings-form">
				<?php wp_nonce_field( 'amp_save_settings', 'amp_nonce' ); ?>

				<table class="form-table" role="presentation">
					<tbody>
						<!-- Access Control Roles -->
						<tr>
							<th scope="row"><label>Allowed Staff Roles</label></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span>Allowed Staff Roles</span></legend>
									<?php foreach ( $roles as $role_key => $role_name ) : ?>
										<label style="display: block; margin-bottom: 5px;">
											<input type="checkbox" name="allowed_roles[]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, $allowed_roles, true ) ); ?>>
											<?php echo esc_html( $role_name ); ?>
										</label>
									<?php endforeach; ?>
									<p class="description">Only users with checked roles will be authorized to access the Admin Messenger chat environment and widgets.</p>
								</fieldset>
							</td>
						</tr>

						<!-- Accent Color Configurable -->
						<tr>
							<th scope="row"><label for="accent_color">Premium Accent Color</label></th>
							<td>
								<input type="color" id="accent_color" name="accent_color" value="<?php echo esc_attr( $accent_color ); ?>" style="width: 60px; height: 35px; border-radius: 5px; border: 1px solid #ccc; cursor: pointer;">
								<p class="description">Customize the primary glowing accent hue throughout the Glassmorphism layout UI.</p>
							</td>
						</tr>

						<!-- Theme Mode Selection -->
						<tr>
							<th scope="row"><label for="theme_mode">Theme Display Mode</label></th>
							<td>
								<select id="theme_mode" name="theme_mode">
									<option value="auto" <?php selected( $theme_mode, 'auto' ); ?>>💻 Auto System Mode</option>
									<option value="dark" <?php selected( $theme_mode, 'dark' ); ?>>🌙 Dark Mode</option>
									<option value="light" <?php selected( $theme_mode, 'light' ); ?>>☀️ Light Mode</option>
								</select>
							</td>
						</tr>

						<!-- Polling Interval -->
						<tr>
							<th scope="row"><label for="polling_interval">Adaptive Polling Frequency</label></th>
							<td>
								<select id="polling_interval" name="polling_interval">
									<option value="1500" <?php selected( $polling_int, 1500 ); ?>>1.5 seconds (Near Real-time)</option>
									<option value="3000" <?php selected( $polling_int, 3000 ); ?>>3 seconds (Optimized Balanced)</option>
									<option value="5000" <?php selected( $polling_int, 5000 ); ?>>5 seconds (Server ECO-friendly)</option>
								</select>
								<p class="description">Fallback AJAX polling frequency when browser focuses on the active chat.</p>
							</td>
						</tr>

						<!-- Max Upload size -->
						<tr>
							<th scope="row"><label for="max_upload_size">Max File Upload Size (MB)</label></th>
							<td>
								<input type="number" id="max_upload_size" name="max_upload_size" value="<?php echo esc_attr( $max_size ); ?>" min="1" max="100" class="small-text"> MB
								<p class="description">Define the absolute limit size of shared images, documents, videos or archives.</p>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button( 'Save Premium Configuration', 'primary', 'amp_submit_settings' ); ?>
			</form>
		</div>
		<?php
	}

	public function add_dashboard_widget() {
		// Only display dashboard widget for users permitted to access Messenger Pro
		if ( ! $this->current_user_can_access() ) {
			return;
		}

		wp_add_dashboard_widget(
			'amp_dashboard_widget',
			'💬 Admin Messenger Pro Overview',
			[ $this, 'render_dashboard_widget' ]
		);
	}

	public function render_dashboard_widget() {
		$metrics = AMP_DB::get_dashboard_metrics();
		?>
		<div class="amp-dash-widget-container">
			<div class="amp-dash-grid">
				<div class="amp-dash-card">
					<span class="amp-dash-num"><?php echo esc_html( $metrics['online_users'] ); ?></span>
					<span class="amp-dash-lbl">🟢 Active Online Staff</span>
				</div>
				<div class="amp-dash-card">
					<span class="amp-dash-num"><?php echo esc_html( $metrics['messages'] ); ?></span>
					<span class="amp-dash-lbl">💬 Total Messages</span>
				</div>
				<div class="amp-dash-card">
					<span class="amp-dash-num"><?php echo esc_html( $metrics['total_files'] ); ?></span>
					<span class="amp-dash-lbl">📎 Shared Attachments</span>
				</div>
				<div class="amp-dash-card">
					<span class="amp-dash-num"><?php echo esc_html( $metrics['storage_usage'] ); ?></span>
					<span class="amp-dash-lbl">💾 Storage Consumed</span>
				</div>
			</div>
			<div class="amp-dash-footer" style="margin-top: 15px; text-align: right;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=admin-messenger-pro' ) ); ?>" class="button button-primary">Open Messenger Panel</a>
			</div>
		</div>
		<?php
	}
}
