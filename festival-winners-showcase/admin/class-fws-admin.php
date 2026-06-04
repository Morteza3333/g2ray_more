<?php

class FWS_Admin {
	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function enqueue_styles( $hook ) {
		if ( 'toplevel_page_fws-admin' !== $hook && 'winner_entry' !== get_post_type() ) {
			return;
		}
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/fws-admin.css', array(), $this->version, 'all' );
		wp_enqueue_media();
	}

	public function enqueue_scripts( $hook ) {
		if ( 'toplevel_page_fws-admin' !== $hook && 'winner_entry' !== get_post_type() ) {
			return;
		}
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/fws-admin.js', array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker' ), $this->version, false );

		wp_localize_script( $this->plugin_name, 'fws_vars', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'rest_url' => get_rest_url( null, 'fws/v1' ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'i18n'     => array(
				'confirm_delete' => __( 'آیا از حذف این مورد اطمینان دارید؟', 'festival-winners-showcase' ),
				'saved'          => __( 'تنظیمات با موفقیت ذخیره شد.', 'festival-winners-showcase' ),
				'error'          => __( 'خطایی رخ داده است.', 'festival-winners-showcase' ),
			)
		) );
	}

	public function add_plugin_admin_menu() {
		add_menu_page(
			__( 'مدیریت جشنواره', 'festival-winners-showcase' ),
			__( 'جشنواره عکس', 'festival-winners-showcase' ),
			'manage_options',
			'fws-admin',
			array( $this, 'display_plugin_admin_page' ),
			'dashicons-camera',
			5
		);
	}

	public function display_plugin_admin_page() {
		include_once plugin_dir_path( __FILE__ ) . 'partials/fws-admin-display.php';
	}

	public function add_winner_meta_boxes() {
		add_meta_box(
			'winner_details',
			__( 'جزئیات برنده', 'festival-winners-showcase' ),
			array( $this, 'render_winner_meta_box' ),
			'winner_entry',
			'normal',
			'high'
		);
	}

	public function render_winner_meta_box( $post ) {
		$rank = get_post_meta( $post->ID, '_winner_rank', true );
		$photographer = get_post_meta( $post->ID, '_winner_photographer', true );
		$instagram = get_post_meta( $post->ID, '_winner_instagram', true );
		$gallery = get_post_meta( $post->ID, '_winner_gallery', true );

		wp_nonce_field( 'fws_winner_meta_box', 'fws_winner_meta_box_nonce' );
		?>
		<div class="fws-meta-field">
			<label for="winner_rank"><?php _e( 'رتبه (۱ تا ۳):', 'festival-winners-showcase' ); ?></label>
			<input type="number" id="winner_rank" name="winner_rank" value="<?php echo esc_attr( $rank ); ?>" min="1" max="3">
		</div>
		<div class="fws-meta-field">
			<label for="winner_photographer"><?php _e( 'نام عکاس:', 'festival-winners-showcase' ); ?></label>
			<input type="text" id="winner_photographer" name="winner_photographer" value="<?php echo esc_attr( $photographer ); ?>">
		</div>
		<div class="fws-meta-field">
			<label for="winner_instagram"><?php _e( 'آیدی اینستاگرام (بدون @):', 'festival-winners-showcase' ); ?></label>
			<input type="text" id="winner_instagram" name="winner_instagram" value="<?php echo esc_attr( $instagram ); ?>">
		</div>
		<div class="fws-meta-field">
			<label><?php _e( 'گالری تصاویر (مخصوص مجموعه عکس):', 'festival-winners-showcase' ); ?></label>
			<div id="fws-gallery-container">
				<?php
				if ( ! empty( $gallery ) ) {
					$ids = explode( ',', $gallery );
					foreach ( $ids as $id ) {
						$url = wp_get_attachment_thumb_url( $id );
						echo '<div class="fws-gallery-item" data-id="' . $id . '"><img src="' . $url . '"><span class="remove">×</span></div>';
					}
				}
				?>
			</div>
			<input type="hidden" id="winner_gallery" name="winner_gallery" value="<?php echo esc_attr( $gallery ); ?>">
			<button type="button" class="button" id="fws-upload-gallery"><?php _e( 'افزودن به گالری', 'festival-winners-showcase' ); ?></button>
		</div>
		<?php
	}

	public function save_winner_meta( $post_id ) {
		if ( ! isset( $_POST['fws_winner_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['fws_winner_meta_box_nonce'], 'fws_winner_meta_box' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['winner_rank'] ) ) {
			update_post_meta( $post_id, '_winner_rank', sanitize_text_field( $_POST['winner_rank'] ) );
		}
		if ( isset( $_POST['winner_photographer'] ) ) {
			update_post_meta( $post_id, '_winner_photographer', sanitize_text_field( $_POST['winner_photographer'] ) );
		}
		if ( isset( $_POST['winner_instagram'] ) ) {
			update_post_meta( $post_id, '_winner_instagram', sanitize_text_field( $_POST['winner_instagram'] ) );
		}
		if ( isset( $_POST['winner_gallery'] ) ) {
			update_post_meta( $post_id, '_winner_gallery', sanitize_text_field( $_POST['winner_gallery'] ) );
		}
	}
}
