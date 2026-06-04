<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 * @package    Festival_Winners_Showcase
 * @subpackage Festival_Winners_Showcase/admin
 */

class FWS_Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/fws-admin.css', array(), $this->version, 'all' );
        wp_enqueue_style( 'wp-color-picker' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/fws-admin.js', array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker' ), $this->version, false );
        wp_localize_script( $this->plugin_name, 'fws_admin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'fws_admin_nonce' ),
            'media_title' => __( 'انتخاب یا آپلود تصاویر', 'festival-winners-showcase' ),
            'media_button' => __( 'استفاده از این تصاویر', 'festival-winners-showcase' ),
        ) );
	}

    public function add_meta_boxes() {
        add_meta_box(
            'winner_details',
            __( 'جزئیات برنده', 'festival-winners-showcase' ),
            array( $this, 'render_winner_details_meta_box' ),
            'winner_entry',
            'normal',
            'high'
        );
    }

    public function render_winner_details_meta_box( $post ) {
        wp_nonce_field( 'fws_save_winner_details', 'fws_winner_details_nonce' );

        $rank = get_post_meta( $post->ID, '_winner_rank', true );
        $photographer_name = get_post_meta( $post->ID, '_photographer_name', true );
        $instagram_id = get_post_meta( $post->ID, '_instagram_id', true );
        $gallery_ids = get_post_meta( $post->ID, '_winner_gallery_ids', true );

        ?>
        <div class="fws-meta-box">
            <p>
                <label for="winner_rank"><?php _e( 'رتبه برنده:', 'festival-winners-showcase' ); ?></label>
                <select name="winner_rank" id="winner_rank">
                    <option value="1" <?php selected( $rank, '1' ); ?>>🥇 رتبه اول</option>
                    <option value="2" <?php selected( $rank, '2' ); ?>>🥈 رتبه دوم</option>
                    <option value="3" <?php selected( $rank, '3' ); ?>>🥉 رتبه سوم</option>
                </select>
            </p>
            <p>
                <label for="photographer_name"><?php _e( 'نام عکاس:', 'festival-winners-showcase' ); ?></label>
                <input type="text" name="photographer_name" id="photographer_name" value="<?php echo esc_attr( $photographer_name ); ?>" class="widefat">
            </p>
            <p>
                <label for="instagram_id"><?php _e( 'آیدی اینستاگرام (بدون @):', 'festival-winners-showcase' ); ?></label>
                <input type="text" name="instagram_id" id="instagram_id" value="<?php echo esc_attr( $instagram_id ); ?>" class="widefat">
            </p>
            <div class="fws-gallery-selection">
                <label><?php _e( 'گالری تصاویر (مخصوص بخش مجموعه‌عکس):', 'festival-winners-showcase' ); ?></label>
                <div id="fws-gallery-container" class="fws-gallery-items">
                    <?php
                    if ( ! empty( $gallery_ids ) ) {
                        $ids = explode( ',', $gallery_ids );
                        foreach ( $ids as $id ) {
                            $image = wp_get_attachment_image_src( $id, 'thumbnail' );
                            if ( $image ) {
                                echo '<div class="fws-gallery-item" data-id="' . $id . '"><img src="' . $image[0] . '"><a class="fws-remove-image">×</a></div>';
                            }
                        }
                    }
                    ?>
                </div>
                <input type="hidden" name="winner_gallery_ids" id="winner_gallery_ids" value="<?php echo esc_attr( $gallery_ids ); ?>">
                <button type="button" class="button fws-add-gallery"><?php _e( 'افزودن به گالری', 'festival-winners-showcase' ); ?></button>
            </div>
        </div>
        <style>
            .fws-gallery-items { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; }
            .fws-gallery-item { position: relative; border: 1px solid #ddd; padding: 5px; background: #fff; }
            .fws-gallery-item img { display: block; max-width: 100px; height: auto; }
            .fws-remove-image { position: absolute; top: -5px; right: -5px; background: red; color: #fff; border-radius: 50%; width: 20px; height: 20px; text-align: center; line-height: 18px; cursor: pointer; text-decoration: none; }
        </style>
        <?php
    }

    public function save_meta_boxes( $post_id ) {
        if ( ! isset( $_POST['fws_winner_details_nonce'] ) || ! wp_verify_nonce( $_POST['fws_winner_details_nonce'], 'fws_save_winner_details' ) ) {
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
        if ( isset( $_POST['photographer_name'] ) ) {
            update_post_meta( $post_id, '_photographer_name', sanitize_text_field( $_POST['photographer_name'] ) );
        }
        if ( isset( $_POST['instagram_id'] ) ) {
            update_post_meta( $post_id, '_instagram_id', sanitize_text_field( $_POST['instagram_id'] ) );
        }
        if ( isset( $_POST['winner_gallery_ids'] ) ) {
            update_post_meta( $post_id, '_winner_gallery_ids', sanitize_text_field( $_POST['winner_gallery_ids'] ) );
        }
    }

    public function update_winner_order() {
        check_ajax_referer( 'fws_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $order = $_POST['order'];
        foreach ( $order as $index => $post_id ) {
            wp_update_post( array(
                'ID' => (int) $post_id,
                'menu_order' => $index
            ) );
        }

        wp_send_json_success();
    }
}
