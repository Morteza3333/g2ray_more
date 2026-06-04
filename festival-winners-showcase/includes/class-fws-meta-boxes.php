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
			__( 'Winner Details', 'festival-winners-showcase' ),
			array( __CLASS__, 'render_details_meta_box' ),
			'winner_entry',
			'normal',
			'high'
		);

		add_meta_box(
			'fws_photo_gallery',
			__( 'Photo Series Gallery', 'festival-winners-showcase' ),
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
		<p>
			<label for="fws_rank"><?php _e( 'Rank:', 'festival-winners-showcase' ); ?></label><br>
			<select name="fws_rank" id="fws_rank" class="widefat">
				<option value="1" <?php selected( $rank, '1' ); ?>><?php _e( '1st Place', 'festival-winners-showcase' ); ?></option>
				<option value="2" <?php selected( $rank, '2' ); ?>><?php _e( '2nd Place', 'festival-winners-showcase' ); ?></option>
				<option value="3" <?php selected( $rank, '3' ); ?>><?php _e( '3rd Place', 'festival-winners-showcase' ); ?></option>
			</select>
		</p>
		<p>
			<label for="fws_photographer_name"><?php _e( 'Photographer Name:', 'festival-winners-showcase' ); ?></label>
			<input type="text" name="fws_photographer_name" id="fws_photographer_name" value="<?php echo esc_attr( $photographer_name ); ?>" class="widefat">
		</p>
		<p>
			<label for="fws_instagram_id"><?php _e( 'Instagram Username (without @):', 'festival-winners-showcase' ); ?></label>
			<input type="text" name="fws_instagram_id" id="fws_instagram_id" value="<?php echo esc_attr( $instagram_id ); ?>" class="widefat">
		</p>
		<?php
	}

	public static function render_gallery_meta_box( $post ) {
		$gallery_ids = get_post_meta( $post->ID, '_fws_gallery_ids', true );
		$gallery_array = ! empty( $gallery_ids ) ? explode( ',', $gallery_ids ) : array();

		?>
		<div id="fws-gallery-container">
			<ul id="fws-gallery-list" style="display: flex; flex-wrap: wrap; list-style: none; padding: 0; margin: 0;">
				<?php
				foreach ( $gallery_array as $img_id ) {
					$img_url = wp_get_attachment_image_url( $img_id, 'thumbnail' );
					if ( $img_url ) {
						echo '<li data-id="' . esc_attr( $img_id ) . '" style="margin: 5px; position: relative; border: 1px solid #ccc;">';
						echo '<img src="' . esc_url( $img_url ) . '" style="display: block; width: 100px; height: 100px; object-fit: cover;">';
						echo '<a href="#" class="fws-remove-image" style="position: absolute; top: -5px; right: -5px; background: red; color: white; border-radius: 50%; width: 20px; height: 20px; text-align: center; line-height: 20px; text-decoration: none;">&times;</a>';
						echo '</li>';
					}
				}
				?>
			</ul>
			<input type="hidden" name="fws_gallery_ids" id="fws_gallery_ids" value="<?php echo esc_attr( $gallery_ids ); ?>">
			<p>
				<button type="button" class="button" id="fws_add_gallery_images"><?php _e( 'Add Images to Series', 'festival-winners-showcase' ); ?></button>
			</p>
			<p class="description"><?php _e( 'Use this for "Photo Series" category. For "Single Photo", you can just use the Featured Image.', 'festival-winners-showcase' ); ?></p>
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
