<?php
/**
 * Provide a public-facing view for the plugin
 */
$settings = get_option( 'mvg_settings' );
$auto_play = $settings['auto_play'] ? '1' : '0';
?>

<div class="mvg-gallery-container" data-autoplay="<?php echo esc_attr( $auto_play ); ?>">
	<div class="mvg-main-player-section">
		<div id="mvg-main-player-container" class="mvg-player-wrapper">
			<?php echo $this->get_video_embed( $featured_video, $auto_play ); ?>
		</div>
		<div class="mvg-video-details">
			<h2 id="mvg-current-title"><?php echo esc_html( $featured_video->title ); ?></h2>
		</div>
	</div>

	<div class="mvg-video-list-section">
		<div class="mvg-list-header">
			<h3>Up Next</h3>
		</div>
		<div class="mvg-playlist">
			<?php foreach ( $videos as $video ) :
				$active_class = ( $video->id == $featured_video->id ) ? 'is-active' : '';
				?>
				<div class="mvg-playlist-item <?php echo esc_attr( $active_class ); ?>"
					 data-id="<?php echo esc_attr( $video->id ); ?>"
					 data-url="<?php echo esc_url( $video->url ); ?>"
					 data-type="<?php echo esc_attr( $video->type ); ?>"
					 data-title="<?php echo esc_attr( $video->title ); ?>">
					<div class="mvg-playlist-thumb">
						<img src="<?php echo esc_url( $video->thumbnail_url ?: 'https://via.placeholder.com/120x68?text=No+Thumb' ); ?>" alt="<?php echo esc_attr( $video->title ); ?>" loading="lazy">
						<div class="mvg-play-overlay">
							<span class="dashicons dashicons-controls-play"></span>
						</div>
					</div>
					<div class="mvg-playlist-info">
						<h4><?php echo esc_html( $video->title ); ?></h4>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
