<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/** @var WP_Query $query */
?>

<div class="fws-container">
    <div class="fws-grid-wrapper" id="fws-winners-grid">
        <?php while ( $query->have_posts() ) : $query->the_post();
            $rank = get_post_meta( get_the_ID(), '_fws_rank', true );
            $photographer = get_post_meta( get_the_ID(), '_fws_photographer_name', true );
            $insta = get_post_meta( get_the_ID(), '_fws_instagram_id', true );
            $gallery_ids = get_post_meta( get_the_ID(), '_fws_gallery_ids', true );
            $has_gallery = ! empty( $gallery_ids );
            $thumb_id = get_post_thumbnail_id();

            // Collect all image data for the slider
            $slider_images = array();
            if ( $thumb_id ) {
                $slider_images[] = array(
                    'full' => wp_get_attachment_image_url( $thumb_id, 'full' ),
                    'thumb' => wp_get_attachment_image_url( $thumb_id, 'large' )
                );
            }
            if ( $has_gallery ) {
                $ids = explode( ',', $gallery_ids );
                foreach ( $ids as $id ) {
                    if ( $id == $thumb_id ) continue; // Skip if already added as thumb
                    $slider_images[] = array(
                        'full' => wp_get_attachment_image_url( $id, 'full' ),
                        'thumb' => wp_get_attachment_image_url( $id, 'large' )
                    );
                }
            }

            $suffix = 'th';
            if ($rank == 1) $suffix = 'st';
            elseif ($rank == 2) $suffix = 'nd';
            elseif ($rank == 3) $suffix = 'rd';

            $entry_data = array(
                'title' => get_the_title(),
                'rank' => $rank,
                'photographer' => $photographer,
                'instagram' => $insta,
                'images' => $slider_images
            );
        ?>
            <a href="#" class="fws-winner-card" data-winner='<?php echo esc_attr( json_encode( $entry_data ) ); ?>'>
                <?php if ( has_post_thumbnail() ) : ?>
                    <?php the_post_thumbnail( 'large' ); ?>
                <?php endif; ?>
                <div class="fws-card-overlay">
                    <div class="fws-card-rank"><?php echo esc_html( $rank . $suffix ); ?> <?php _e( 'Place', 'festival-winners-showcase' ); ?></div>
                    <div class="fws-card-info">
                        <h3><?php the_title(); ?></h3>
                        <p><?php echo esc_html( $photographer ); ?></p>
                    </div>
                </div>
            </a>
        <?php endwhile; ?>
    </div>
</div>

<!-- Modal / Slider structure -->
<div id="fws-modal" class="fws-modal">
    <div class="fws-modal-close">&times;</div>
    <div class="fws-modal-content">
        <div class="fws-modal-body">
            <div class="fws-slider-container">
                <div class="swiper fws-swiper">
                    <div class="swiper-wrapper" id="fws-swiper-wrapper">
                        <!-- Slides will be injected here -->
                    </div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
            <div class="fws-info-panel">
                <div class="fws-info-header">
                    <h2 id="fws-modal-title"></h2>
                    <div id="fws-modal-rank" class="fws-rank-badge"></div>
                </div>
                <div class="fws-info-photographer">
                    <p class="label"><?php _e( 'Photographer', 'festival-winners-showcase' ); ?></p>
                    <p id="fws-modal-photographer" class="value"></p>
                </div>
                <div class="fws-info-instagram">
                    <p class="label"><?php _e( 'Instagram', 'festival-winners-showcase' ); ?></p>
                    <p class="value"><a href="#" id="fws-modal-insta-link" target="_blank">@<span id="fws-modal-insta-id"></span></a></p>
                </div>
                <div class="fws-info-desc">
                    <?php // Content could go here ?>
                </div>
            </div>
        </div>
    </div>
</div>
