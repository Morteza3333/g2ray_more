<?php
/**
 * Provide a settings view for the plugin
 */
$settings = get_option( 'mvg_settings' );
?>
<div class="wrap mvg-admin-wrap">
	<h1>Gallery Settings</h1>

	<div class="mvg-card">
		<form id="mvg-settings-form" class="mvg-settings-form">
			<div class="mvg-form-group">
				<label for="primary_color">Primary Color</label>
				<input type="text" id="primary_color" name="primary_color" class="mvg-color-picker" value="<?php echo esc_attr( $settings['primary_color'] ); ?>">
			</div>

			<div class="mvg-form-group">
				<label for="accent_color">Accent Color</label>
				<input type="text" id="accent_color" name="accent_color" class="mvg-color-picker" value="<?php echo esc_attr( $settings['accent_color'] ); ?>">
			</div>

			<div class="mvg-form-group">
				<label for="background_color">Background Color</label>
				<input type="text" id="background_color" name="background_color" class="mvg-color-picker" value="<?php echo esc_attr( $settings['background_color'] ); ?>">
			</div>

			<div class="mvg-form-group">
				<label for="text_color">Text Color</label>
				<input type="text" id="text_color" name="text_color" class="mvg-color-picker" value="<?php echo esc_attr( $settings['text_color'] ); ?>">
			</div>

			<div class="mvg-form-group">
				<label for="auto_play">Auto-play Main Video</label>
				<select id="auto_play" name="auto_play">
					<option value="0" <?php selected( $settings['auto_play'], 0 ); ?>>No</option>
					<option value="1" <?php selected( $settings['auto_play'], 1 ); ?>>Yes (Muted)</option>
				</select>
			</div>

			<div class="mvg-form-group" style="margin-top: 30px;">
				<button type="submit" class="button button-primary">Save Settings</button>
				<span id="mvg-settings-feedback" style="margin-left: 10px; color: green; display: none;">Settings saved!</span>
			</div>
		</form>
	</div>

	<div class="mvg-card" style="margin-top: 20px;">
		<h2>Live Preview</h2>
		<p class="description">To see your changes, please save them first and then view a page where you've added the <code>[video_gallery]</code> shortcode.</p>
		<div id="mvg-admin-preview-container" style="background: #020617; padding: 20px; border-radius: 8px; text-align: center;">
			<p style="color: #f8fafc;">Live preview of the gallery will appear here when active.</p>
		</div>
	</div>
</div>
