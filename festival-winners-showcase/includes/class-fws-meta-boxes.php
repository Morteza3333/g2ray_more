<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FWS_Meta_Boxes {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function enqueue_assets( $hook ) {
		if ( 'post.php' != $hook && 'post-new.php' != $hook ) {
			return;
		}
		global $post;
		if ( ! $post || 'winner_entry' !== $post->post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'fws-admin-meta', FWS_URL . 'assets/js/admin-meta.js', array( 'jquery' ), FWS_VERSION, true );
		wp_enqueue_style( 'fws-admin-meta', FWS_URL . 'assets/css/admin-meta.css', array(), FWS_VERSION );
	}

	public static function add_meta_boxes() {
		add_meta_box(
			'fws_winner_details',
			__( 'اطلاعات برنده جشنواره', 'festival-winners-showcase' ),
			array( __CLASS__, 'render_details_meta_box' ),
			'winner_entry',
			'normal',
			'high'
		);

		add_meta_box(
			'fws_photo_gallery',
			__( 'گالری تصاویر (برای مجموعه عکس)', 'festival-winners-showcase' ),
			array( __CLASS__, 'render_gallery_meta_box' ),
			'winner_entry',
			'normal',
			'high'
		);
	}

	public static function render_details_meta_box( $post ) {
		wp_nonce_field( 'fws_save_meta', 'fws_meta_nonce' );

		$rank = get_post_meta( $post->ID, '_fws_rank', true );
		$photographer_name = get_post_meta( $post->ID, '_fws_photographer_name', true );
		$instagram_id = get_post_meta( $post->ID, '_fws_instagram_id', true );

		?>
		<div class="fws-meta-field">
			<label for="fws_rank"><?php _e( 'رتبه برنده:', 'festival-winners-showcase' ); ?></label>
			<select name="fws_rank" id="fws_rank" class="widefat">
				<option value="1" <?php selected( $rank, '1' ); ?>><?php _e( 'نفر اول', 'festival-winners-showcase' ); ?></option>
				<option value="2" <?php selected( $rank, '2' ); ?>><?php _e( 'نفر دوم', 'festival-winners-showcase' ); ?></option>
				<option value="3" <?php selected( $rank, '3' ); ?>><?php _e( 'نفر سوم', 'festival-winners-showcase' ); ?></option>
			</select>
			<p class="description"><?php _e( 'رتبه کسب شده توسط عکاس را انتخاب کنید.', 'festival-winners-showcase' ); ?></p>
		</div>
		<div class="fws-meta-field">
			<label for="fws_photographer_name"><?php _e( 'نام و نام خانوادگی عکاس:', 'festival-winners-showcase' ); ?></label>
			<input type="text" name="fws_photographer_name" id="fws_photographer_name" value="<?php echo esc_attr( $photographer_name ); ?>" class="widefat">
			<p class="description"><?php _e( 'نام کامل عکاس را وارد کنید.', 'festival-winners-showcase' ); ?></p>
		</div>
		<div class="fws-meta-field">
			<label for="fws_instagram_id"><?php _e( 'آیدی اینستاگرام (بدون @):', 'festival-winners-showcase' ); ?></label>
			<input type="text" name="fws_instagram_id" id="fws_instagram_id" value="<?php echo esc_attr( $instagram_id ); ?>" class="widefat" placeholder="مثلا: jules_photographer">
			<p class="description"><?php _e( 'آیدی اینستاگرام جهت لینک دادن به پروفایل عکاس.', 'festival-winners-showcase' ); ?></p>
		</div>
		<?php
	}

	public static function render_gallery_meta_box( $post ) {
		$gallery_ids = get_post_meta( $post->ID, '_fws_gallery_ids', true );
		$gallery_array = ! empty( $gallery_ids ) ? explode( ',', $gallery_ids ) : array();

		?>
		<div id="fws-gallery-container">
			<ul id="fws-gallery-list">
				<?php
				foreach ( $gallery_array as $img_id ) {
					$img_url = wp_get_attachment_image_url( $img_id, 'thumbnail' );
					if ( $img_url ) {
						echo '<li data-id="' . esc_attr( $img_id ) . '">';
						echo '<img src="' . esc_url( $img_url ) . '">';
						echo '<a href="#" class="fws-remove-image" title="حذف">&times;</a>';
						echo '</li>';
					}
				}
				?>
			</ul>
			<input type="hidden" name="fws_gallery_ids" id="fws_gallery_ids" value="<?php echo esc_attr( $gallery_ids ); ?>">
			<div class="fws-gallery-actions">
				<button type="button" class="button button-primary" id="fws_add_gallery_images"><?php _e( 'افزودن تصاویر به مجموعه', 'festival-winners-showcase' ); ?></button>
			</div>
			<p class="description"><?php _e( 'اگر این مورد یک "مجموعه عکس" است، تصاویر آن را از اینجا اضافه کنید. برای "تک عکس" فقط کافیست "تصویر شاخص" را تنظیم کنید.', 'festival-winners-showcase' ); ?></p>
		</div>
		<?php
	}

	public static function save_meta_boxes( $post_id ) {
		if ( ! isset( $_POST['fws_meta_nonce'] ) || ! wp_verify_nonce( $_POST['fws_meta_nonce'], 'fws_save_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'fws_rank'              => '_fws_rank',
			'fws_photographer_name' => '_fws_photographer_name',
			'fws_instagram_id'      => '_fws_instagram_id',
			'fws_gallery_ids'       => '_fws_gallery_ids',
		);

		foreach ( $fields as $key => $meta_key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $key ] ) );
			}
		}
	}
}
FWS_Meta_Boxes::init();
