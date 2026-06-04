<?php
/**
 * Frontend Gallery Template
 *
 * @link       https://example.com
 * @since      1.0.0
 * @package    Festival_Winners_Showcase
 * @subpackage Festival_Winners_Showcase/templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Variables passed from shortcode class: $query, $primary_color, $accent_color, $theme_mode
?>

<div class="fws-gallery-wrapper fws-theme-<?php echo esc_attr( $theme_mode ); ?>"
     style="--fws-primary: <?php echo esc_attr( $primary_color ); ?>; --fws-accent: <?php echo esc_attr( $accent_color ); ?>; direction: rtl;">

    <div class="fws-grid" id="fws-masonry-grid">
        <?php
        while ( $query->have_posts() ) : $query->the_post();
            $post_id = get_the_ID();
            $rank = get_post_meta( $post_id, '_winner_rank', true );
            $photographer = get_post_meta( $post_id, '_photographer_name', true );
            $instagram = get_post_meta( $post_id, '_instagram_id', true );
            $gallery_ids = get_post_meta( $post_id, '_winner_gallery_ids', true );
            $thumbnail_id = get_post_thumbnail_id();
            $full_image_url = wp_get_attachment_image_src( $thumbnail_id, 'full' )[0];

            $rank_emoji = '';
            switch($rank) {
                case '1': $rank_emoji = '🥇'; break;
                case '2': $rank_emoji = '🥈'; break;
                case '3': $rank_emoji = '🥉'; break;
            }

            // Prepare gallery for Swiper
            $slides = array();
            $slides[] = $full_image_url;
            if ( ! empty( $gallery_ids ) ) {
                $ids = explode( ',', $gallery_ids );
                foreach ( $ids as $id ) {
                    $img = wp_get_attachment_image_src( $id, 'full' );
                    if ( $img ) {
                        $slides[] = $img[0];
                    }
                }
            }
            $slides_json = htmlspecialchars(json_encode($slides), ENT_QUOTES, 'UTF-8');
            ?>

            <div class="fws-item" data-gsap="fade-up">
                <div class="fws-item-inner" data-slides='<?php echo $slides_json; ?>'>
                    <div class="fws-image-container">
                        <?php the_post_thumbnail( 'large', array( 'class' => 'fws-main-img', 'loading' => 'lazy' ) ); ?>
                        <div class="fws-overlay">
                            <div class="fws-zoom-icon">✨</div>
                        </div>
                    </div>
                    <div class="fws-info">
                        <div class="fws-rank-badge"><?php echo $rank_emoji; ?></div>
                        <h3 class="fws-winner-title"><?php the_title(); ?></h3>
                        <p class="fws-photographer"><?php echo esc_html( $photographer ); ?></p>
                        <?php if ( ! empty( $instagram ) ) : ?>
                            <a href="https://instagram.com/<?php echo esc_attr( $instagram ); ?>" target="_blank" class="fws-instagram" rel="nofollow">
                                @<?php echo esc_html( $instagram ); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php endwhile; wp_reset_postdata(); ?>
    </div>
</div>

<!-- Swiper Lightbox (Hidden initially) -->
<div class="fws-lightbox" id="fws-lightbox">
    <div class="fws-lightbox-bg"></div>
    <div class="fws-lightbox-close">×</div>
    <div class="swiper fws-swiper">
        <div class="swiper-wrapper">
            <!-- Slides will be injected by JS -->
        </div>
        <div class="swiper-pagination"></div>
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
    </div>
</div>
