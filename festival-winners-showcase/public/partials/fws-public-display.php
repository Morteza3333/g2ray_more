<div class="fws-gallery-wrapper" dir="rtl">
    <?php
    $single_winners = array();
    $series_winners = array();

    foreach ( $winners as $winner ) {
        $terms = wp_get_post_terms( $winner->ID, 'winner_category', array( 'fields' => 'slugs' ) );
        if ( in_array( 'series', $terms ) ) {
            $series_winners[] = $winner;
        } else {
            $single_winners[] = $winner;
        }
    }
    ?>

    <?php if ( ! empty( $single_winners ) ) : ?>
    <section class="fws-section fws-single-section">
        <div class="fws-section-header">
            <h2 class="fws-section-title"><?php _e( '🥇 برندگان تک عکس', 'festival-winners-showcase' ); ?></h2>
            <div class="fws-separator"></div>
        </div>
        <div class="fws-grid">
            <?php foreach ( $single_winners as $winner ) :
                $rank = get_post_meta( $winner->ID, '_winner_rank', true );
                $photographer = get_post_meta( $winner->ID, '_winner_photographer', true );
                $instagram = get_post_meta( $winner->ID, '_winner_instagram', true );
            ?>
            <?php $is_best = get_post_meta($winner->ID, '_best_of_festival', true) === 'yes'; ?>
            <div class="fws-item fws-reveal <?php echo $is_best ? 'fws-best-highlight' : ''; ?>" data-rank="<?php echo esc_attr($rank); ?>">
                <div class="fws-item-inner" data-id="<?php echo $winner->ID; ?>" data-main-img="<?php echo get_the_post_thumbnail_url($winner->ID, 'full'); ?>">
                    <div class="fws-image-container">
                        <?php echo get_the_post_thumbnail( $winner->ID, 'large' ); ?>
                        <div class="fws-overlay">
                            <div class="fws-info">
                                <span class="fws-rank-badge"><?php echo $rank == 1 ? '🥇' : ($rank == 2 ? '🥈' : '🥉'); ?></span>
                                <h3 class="fws-winner-title"><?php echo get_the_title($winner->ID); ?></h3>
                                <p class="fws-photographer"><?php echo esc_html($photographer); ?></p>
                                <?php if($instagram): ?>
                                    <a href="https://instagram.com/<?php echo esc_attr($instagram); ?>" target="_blank" class="fws-insta">@<?php echo esc_html($instagram); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if ( ! empty( $series_winners ) ) : ?>
    <section class="fws-section fws-series-section">
        <div class="fws-section-header">
            <h2 class="fws-section-title"><?php _e( '🖼 برندگان مجموعه عکس', 'festival-winners-showcase' ); ?></h2>
            <div class="fws-separator"></div>
        </div>
        <div class="fws-grid">
            <?php foreach ( $series_winners as $winner ) :
                $rank = get_post_meta( $winner->ID, '_winner_rank', true );
                $photographer = get_post_meta( $winner->ID, '_winner_photographer', true );
                $gallery = get_post_meta( $winner->ID, '_winner_gallery', true );
            ?>
            <?php $is_best = get_post_meta($winner->ID, '_best_of_festival', true) === 'yes'; ?>
            <div class="fws-item fws-reveal <?php echo $is_best ? 'fws-best-highlight' : ''; ?>" data-type="series">
                <?php
                    $gallery_urls = array();
                    if(!empty($gallery)) {
                        $ids = explode(',', $gallery);
                        foreach($ids as $id) {
                            $url = wp_get_attachment_url($id);
                            if($url) $gallery_urls[] = $url;
                        }
                    }
                ?>
                <div class="fws-item-inner" data-id="<?php echo $winner->ID; ?>" data-main-img="<?php echo get_the_post_thumbnail_url($winner->ID, 'full'); ?>" data-gallery='<?php echo json_encode($gallery_urls); ?>'>
                    <div class="fws-image-container">
                        <?php echo get_the_post_thumbnail( $winner->ID, 'large' ); ?>
                        <div class="fws-overlay">
                            <div class="fws-info">
                                <span class="fws-rank-badge"><?php echo $rank == 1 ? '🥇' : ($rank == 2 ? '🥈' : '🥉'); ?></span>
                                <h3 class="fws-winner-title">
                                    <?php echo get_the_title($winner->ID); ?>
                                    <?php if($is_best): ?> <span class="fws-best-label">⭐ <?php _e('برترین', 'festival-winners-showcase'); ?></span> <?php endif; ?>
                                </h3>
                                <h3 class="fws-winner-title">
                                    <?php echo get_the_title($winner->ID); ?>
                                    <?php if($is_best): ?> <span class="fws-best-label">⭐ <?php _e('برترین', 'festival-winners-showcase'); ?></span> <?php endif; ?>
                                </h3>
                                <p class="fws-photographer"><?php echo esc_html($photographer); ?></p>
                                <span class="fws-series-tag"><?php _e( 'مشاهده مجموعه', 'festival-winners-showcase' ); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Fullscreen Slider Modal -->
    <div id="fws-modal" class="fws-modal">
        <span class="fws-close">&times;</span>
        <div class="swiper fws-main-swiper">
            <div class="swiper-wrapper">
                <!-- Slides injected via JS -->
            </div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-pagination"></div>
        </div>
    </div>
</div>
