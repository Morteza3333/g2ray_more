<div class="wrap fws-admin-wrap" dir="rtl">
	<header class="fws-header">
		<h1><?php echo esc_html( get_admin_page_title() ); ?> <span class="badge">V1.0</span></h1>
		<div class="fws-header-actions">
			<button id="fws-save-all" class="button button-primary"><?php _e( 'ذخیره تمامی تغییرات', 'festival-winners-showcase' ); ?></button>
		</div>
	</header>

	<nav class="fws-nav">
		<ul>
			<li class="active" data-tab="single-winners">🥇 <span><?php _e( 'تک عکس', 'festival-winners-showcase' ); ?></span></li>
			<li data-tab="series-winners">🖼 <span><?php _e( 'مجموعه عکس', 'festival-winners-showcase' ); ?></span></li>
			<li data-tab="design-settings">🎨 <span><?php _e( 'طراحی و ظاهر', 'festival-winners-showcase' ); ?></span></li>
			<li data-tab="general-settings">⚙️ <span><?php _e( 'تنظیمات عمومی', 'festival-winners-showcase' ); ?></span></li>
			<li data-tab="help-guide">📘 <span><?php _e( 'راهنما', 'festival-winners-showcase' ); ?></span></li>
		</ul>
	</nav>

	<main class="fws-content">
		<section class="fws-preview-bar">
			<div class="fws-card">
				<div class="fws-card-header">
					<h3>👀 <?php _e( 'پیش‌نمایش زنده رنگ‌ها', 'festival-winners-showcase' ); ?></h3>
				</div>
				<div class="fws-card-body">
					<div id="fws-live-preview" class="fws-live-preview-box">
						<div class="fws-preview-item">
							<div class="fws-preview-circle primary"></div>
							<span><?php _e( 'اصلی', 'festival-winners-showcase' ); ?></span>
						</div>
						<div class="fws-preview-item">
							<div class="fws-preview-circle accent"></div>
							<span><?php _e( 'ثانویه', 'festival-winners-showcase' ); ?></span>
						</div>
						<div class="fws-preview-item">
							<div class="fws-preview-circle bg"></div>
							<span><?php _e( 'پس‌زمینه', 'festival-winners-showcase' ); ?></span>
						</div>
					</div>
				</div>
			</div>
		</section>

		<section id="single-winners" class="fws-tab-content active">
			<div class="fws-card">
				<div class="fws-card-header">
					<h2>🥇 <?php _e( 'برندگان بخش تک عکس', 'festival-winners-showcase' ); ?></h2>
					<p><?php _e( 'در این بخش می‌توانید برندگان بخش تک عکس را مدیریت کنید.', 'festival-winners-showcase' ); ?></p>
				</div>
				<div class="fws-card-body">
					<div id="single-winners-list" class="fws-winners-list sortable" data-type="single">
						<!-- Winners loaded via JS -->
						<div class="fws-skeleton"></div>
					</div>
					<div class="fws-actions">
						<button class="button button-secondary fws-add-winner" data-category="single"><?php _e( 'افزودن برنده جدید', 'festival-winners-showcase' ); ?></button>
					</div>
				</div>
			</div>
		</section>

		<section id="series-winners" class="fws-tab-content">
			<div class="fws-card">
				<div class="fws-card-header">
					<h2>🖼 <?php _e( 'برندگان بخش مجموعه عکس', 'festival-winners-showcase' ); ?></h2>
					<p><?php _e( 'در این بخش می‌توانید برندگان بخش مجموعه عکس را مدیریت کنید.', 'festival-winners-showcase' ); ?></p>
				</div>
				<div class="fws-card-body">
					<div id="series-winners-list" class="fws-winners-list sortable" data-type="series">
						<!-- Winners loaded via JS -->
						<div class="fws-skeleton"></div>
					</div>
					<div class="fws-actions">
						<a href="<?php echo admin_url('post-new.php?post_type=winner_entry'); ?>" class="button button-secondary"><?php _e( 'افزودن برنده جدید', 'festival-winners-showcase' ); ?></a>
					</div>
				</div>
			</div>
		</section>

		<section id="design-settings" class="fws-tab-content">
			<div class="fws-card">
				<div class="fws-card-header">
					<h2>🎨 <?php _e( 'تنظیمات طراحی و رنگ‌بندی', 'festival-winners-showcase' ); ?></h2>
				</div>
				<div class="fws-card-body">
					<?php $settings = get_option( 'fws_settings', array() ); ?>
					<form id="fws-design-form">
						<div class="fws-field-group">
							<label><?php _e( 'رنگ اصلی:', 'festival-winners-showcase' ); ?></label>
							<input type="text" name="primary_color" class="fws-color-picker" value="<?php echo esc_attr( $settings['primary_color'] ?? '#0073aa' ); ?>">
						</div>
						<div class="fws-field-group">
							<label><?php _e( 'رنگ ثانویه (Accent):', 'festival-winners-showcase' ); ?></label>
							<input type="text" name="accent_color" class="fws-color-picker" value="<?php echo esc_attr( $settings['accent_color'] ?? '#ffb200' ); ?>">
						</div>
						<div class="fws-field-group">
							<label><?php _e( 'رنگ پس‌زمینه:', 'festival-winners-showcase' ); ?></label>
							<input type="text" name="bg_color" class="fws-color-picker" value="<?php echo esc_attr( $settings['bg_color'] ?? '#0a0a0b' ); ?>">
						</div>
					</form>
				</div>
			</div>
		</section>

		<section id="general-settings" class="fws-tab-content">
			<div class="fws-card">
				<div class="fws-card-header">
					<h2>⚙️ <?php _e( 'تنظیمات عمومی', 'festival-winners-showcase' ); ?></h2>
				</div>
				<div class="fws-card-body">
					<form id="fws-general-form">
						<div class="fws-field-group">
							<label><?php _e( 'عنوان جشنواره:', 'festival-winners-showcase' ); ?></label>
							<input type="text" name="festival_title" class="regular-text" value="<?php echo esc_attr( $settings['festival_title'] ?? '' ); ?>">
						</div>
						<div class="fws-field-group">
							<label><?php _e( 'شدت انیمیشن‌ها:', 'festival-winners-showcase' ); ?></label>
							<select name="animation_intensity">
								<option value="none" <?php selected( $settings['animation_intensity'] ?? '', 'none' ); ?>><?php _e( 'بدون انیمیشن', 'festival-winners-showcase' ); ?></option>
								<option value="low" <?php selected( $settings['animation_intensity'] ?? '', 'low' ); ?>><?php _e( 'کم', 'festival-winners-showcase' ); ?></option>
								<option value="medium" <?php selected( $settings['animation_intensity'] ?? '', 'medium' ); ?>><?php _e( 'متوسط', 'festival-winners-showcase' ); ?></option>
								<option value="high" <?php selected( $settings['animation_intensity'] ?? '', 'high' ); ?>><?php _e( 'زیاد', 'festival-winners-showcase' ); ?></option>
							</select>
						</div>
					</form>
				</div>
			</div>
		</section>

		<section id="help-guide" class="fws-tab-content">
			<div class="fws-card">
				<div class="fws-card-header">
					<h2>📘 <?php _e( 'راهنمای استفاده از افزونه', 'festival-winners-showcase' ); ?></h2>
				</div>
				<div class="fws-card-body">
					<div class="fws-guide-content">
						<h3>شروع کار</h3>
						<p>برای نمایش برندگان در سایت، از شورت‌کد <code>[festival_winners]</code> استفاده کنید.</p>

						<h3>افزودن برنده</h3>
						<p>ابتدا از منوی سمت راست وردپرس یا دکمه "افزودن برنده جدید" در همین صفحه، اطلاعات برنده را وارد کنید. حتما تصویر شاخص و دسته‌بندی (تک عکس یا مجموعه عکس) را انتخاب کنید.</p>

						<h3>مدیریت ترتیب</h3>
						<p>شما می‌توانید با کشیدن و رها کردن (Drag & Drop) کارت‌های برندگان در این صفحه، ترتیب نمایش آن‌ها را در سایت تغییر دهید.</p>

						<h3>پیش‌نمایش زنده</h3>
						<p>در سمت راست (در دسکتاپ)، بخشی برای پیش‌نمایش زنده رنگ‌های انتخاب شده وجود دارد که به شما کمک می‌کند قبل از ذخیره، ترکیب رنگی خود را مشاهده کنید.</p>

						<h3>مجموعه عکس</h3>
						<p>برای برندگان بخش مجموعه عکس، می‌توانید چندین تصویر را در گالری ویرایشگر آپلود کنید. این تصاویر به صورت یک اسلایدر حرفه‌ای در نمای تمام‌صفحه به کاربر نمایش داده می‌شوند.</p>
					</div>
				</div>
			</div>
		</section>
	</main>

	<div id="fws-toast" class="fws-toast"></div>

	<!-- Inline Editor Modal -->
	<div id="fws-edit-modal" class="fws-modal-overlay">
		<div class="fws-modal-content fws-card">
			<div class="fws-card-header">
				<h2 id="fws-editor-title"><?php _e( 'ویرایش برنده', 'festival-winners-showcase' ); ?></h2>
				<span class="fws-modal-close">&times;</span>
			</div>
			<div class="fws-card-body">
				<form id="fws-winner-form">
					<input type="hidden" name="id" id="edit-id">
					<div class="fws-field-group">
						<label><?php _e( 'عنوان اثر:', 'festival-winners-showcase' ); ?></label>
						<input type="text" name="title" id="edit-title" class="widefat">
					</div>
					<div class="fws-row">
						<div class="fws-col">
							<label><?php _e( 'رتبه:', 'festival-winners-showcase' ); ?></label>
							<input type="number" name="rank" id="edit-rank" min="1" max="3">
						</div>
						<div class="fws-col">
							<label><?php _e( 'نام عکاس:', 'festival-winners-showcase' ); ?></label>
							<input type="text" name="photographer" id="edit-photographer">
						</div>
						<div class="fws-col">
							<label>
								<input type="checkbox" name="best_of_festival" id="edit-best-of-festival">
								<?php _e( 'بهترین جشنواره', 'festival-winners-showcase' ); ?>
							</label>
						</div>
					</div>
					<div class="fws-field-group">
						<label><?php _e( 'آیدی اینستاگرام:', 'festival-winners-showcase' ); ?></label>
						<input type="text" name="instagram" id="edit-instagram">
					</div>
					<div class="fws-field-group">
						<label><?php _e( 'تصویر اصلی:', 'festival-winners-showcase' ); ?></label>
						<div id="edit-thumb-preview" class="fws-thumb-preview"></div>
						<button type="button" class="button" id="fws-set-thumb"><?php _e( 'انتخاب تصویر', 'festival-winners-showcase' ); ?></button>
						<input type="hidden" name="thumbnail_id" id="edit-thumbnail-id">
					</div>
					<div id="edit-gallery-section" class="fws-field-group" style="display:none;">
						<label><?php _e( 'گالری تصاویر مجموعه:', 'festival-winners-showcase' ); ?></label>
						<div id="edit-gallery-container" class="fws-gallery-grid sortable"></div>
						<button type="button" class="button" id="fws-add-gallery"><?php _e( 'افزودن به گالری', 'festival-winners-showcase' ); ?></button>
						<input type="hidden" name="gallery" id="edit-gallery-ids">
					</div>
					<div class="fws-modal-actions">
						<button type="submit" class="button button-primary"><?php _e( 'ذخیره تغییرات', 'festival-winners-showcase' ); ?></button>
						<button type="button" class="button fws-delete-winner-btn"><?php _e( 'حذف برنده', 'festival-winners-showcase' ); ?></button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
