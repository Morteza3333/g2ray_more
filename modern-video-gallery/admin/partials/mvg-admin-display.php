<?php
/**
 * Provide a admin area view for the plugin
 */
?>
<div class="wrap mvg-admin-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="mvg-admin-container">
		<div class="mvg-card mvg-add-video-form">
			<h2>Add New Video</h2>
			<form id="mvg-video-form">
				<div class="mvg-form-group">
					<label for="video-title">Title</label>
					<input type="text" id="video-title" name="title" required placeholder="Video Title">
				</div>
				<div class="mvg-form-group">
					<label for="video-url">Video URL (YouTube, Vimeo, or MP4)</label>
					<input type="url" id="video-url" name="url" required placeholder="https://www.youtube.com/watch?v=...">
				</div>
				<div class="mvg-form-group">
					<label for="video-thumbnail">Thumbnail URL</label>
					<div class="mvg-input-with-button">
						<input type="url" id="video-thumbnail" name="thumbnail_url" placeholder="https://...">
						<button type="button" id="mvg-upload-thumbnail" class="button">Upload</button>
					</div>
				</div>
				<div class="mvg-form-group">
					<label for="video-type">Video Type</label>
					<select id="video-type" name="type">
						<option value="youtube">YouTube</option>
						<option value="vimeo">Vimeo</option>
						<option value="self">Self-hosted (MP4)</option>
					</select>
				</div>
				<button type="submit" class="button button-primary">Add Video</button>
			</form>
		</div>

		<div class="mvg-card mvg-video-list-container">
			<h2>Manage Videos</h2>
			<p class="description">Drag and drop to reorder videos. The first video (or marked as featured) will be the main video.</p>
			<div id="mvg-video-list-loader" class="mvg-loader" style="display:none;">Loading...</div>
			<ul id="mvg-video-list" class="mvg-sortable-list">
				<!-- Videos will be loaded here via JS -->
			</ul>
		</div>
	</div>
</div>

<script id="mvg-video-item-template" type="text/template">
	<li class="mvg-video-item" data-id="{{id}}">
		<div class="mvg-video-handle dashicons dashicons-menu"></div>
		<div class="mvg-video-thumb">
			<img src="{{thumbnail_url}}" alt="{{title}}">
		</div>
		<div class="mvg-video-info">
			<span class="mvg-video-title">{{title}}</span>
			<span class="mvg-video-meta">{{type}} | {{url}}</span>
		</div>
		<div class="mvg-video-actions">
			<button class="button mvg-set-featured {{featured_class}}" title="Set as Featured">
				<span class="dashicons dashicons-star-{{star_type}}"></span>
			</button>
			<button class="button mvg-delete-video" title="Delete Video">
				<span class="dashicons dashicons-trash"></span>
			</button>
		</div>
	</li>
</script>
