(function($) {
	'use strict';

	const FWS_Admin = {
		winners: [],
		init: function() {
			this.cacheDOM();
			this.bindEvents();
			this.initColorPicker();
			this.loadWinners();
			this.initSortable();
		},

		cacheDOM: function() {
			this.$navItems = $('.fws-nav li');
			this.$tabContents = $('.fws-tab-content');
			this.$singleList = $('#single-winners-list');
			this.$seriesList = $('#series-winners-list');
			this.$saveBtn = $('#fws-save-all');
			this.$toast = $('#fws-toast');
			this.$editModal = $('#fws-edit-modal');
			this.$winnerForm = $('#fws-winner-form');
			this.$galleryContainer = $('#edit-gallery-container');
		},

		bindEvents: function() {
			const self = this;

			// Tab switching
			this.$navItems.on('click', function() {
				const tabId = $(this).data('tab');
				self.$navItems.removeClass('active');
				$(this).addClass('active');
				self.$tabContents.removeClass('active');
				$('#' + tabId).addClass('active');
			});

			// Add Winner
			$('.fws-add-winner').on('click', function() {
				const category = $(this).data('category');
				self.createWinner(category);
			});

			// Edit Winner
			$(document).on('click', '.fws-edit-btn', function(e) {
				e.preventDefault();
				const id = $(this).closest('.fws-winner-card').data('id');
				self.openEditor(id);
			});

			// Close Modal
			$('.fws-modal-close, .fws-modal-overlay').on('click', function(e) {
				if (e.target === this || $(e.target).hasClass('fws-modal-close')) {
					self.$editModal.removeClass('active');
				}
			});

			// Save Winner
			this.$winnerForm.on('submit', function(e) {
				e.preventDefault();
				self.saveWinner();
			});

			// Delete Winner
			$('.fws-delete-winner-btn').on('click', function() {
				if (confirm(fws_vars.i18n.confirm_delete)) {
					self.deleteWinner($('#edit-id').val());
				}
			});

			// Media Uploader for Thumbnail
			$('#fws-set-thumb').on('click', function(e) {
				e.preventDefault();
				const frame = wp.media({ title: 'انتخاب تصویر', multiple: false });
				frame.on('select', function() {
					const attachment = frame.state().get('selection').first().toJSON();
					$('#edit-thumbnail-id').val(attachment.id);
					$('#edit-thumb-preview').html(`<img src="${attachment.url}">`);
				});
				frame.open();
			});

			// Media Uploader for Gallery
			$('#fws-add-gallery').on('click', function(e) {
				e.preventDefault();
				const frame = wp.media({ title: 'انتخاب تصاویر گالری', multiple: true });
				frame.on('select', function() {
					const selection = frame.state().get('selection');
					selection.map(function(attachment) {
						attachment = attachment.toJSON();
						self.addGalleryItem(attachment.id, attachment.url);
					});
					self.updateGalleryIds();
				});
				frame.open();
			});

			// Remove Gallery Item
			$(document).on('click', '.fws-gallery-item .remove', function() {
				$(this).parent().remove();
				self.updateGalleryIds();
			});

			// Save settings
			this.$saveBtn.on('click', function() {
				self.saveSettings();
			});
		},

		loadWinners: function() {
			const self = this;
			$.ajax({
				url: fws_vars.rest_url + '/winners',
				beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', fws_vars.nonce); },
				success: function(data) {
					self.winners = data;
					self.renderWinners();
				}
			});
		},

		renderWinners: function() {
			this.$singleList.empty();
			this.$seriesList.empty();

			this.winners.forEach(winner => {
				const card = this.createWinnerCard(winner);
				if (winner.category && winner.category.includes('series')) {
					this.$seriesList.append(card);
				} else {
					this.$singleList.append(card);
				}
			});
		},

		createWinnerCard: function(winner) {
			const bestBadge = winner.best_of_festival ? '<span class="fws-best-badge">⭐ بهترین</span>' : '';
			return `
				<div class="fws-winner-card" data-id="${winner.id}">
					<div class="fws-winner-thumb">
						<img src="${winner.thumbnail || ''}" alt="">
					</div>
					<div class="fws-winner-info">
						<strong>${winner.title} ${bestBadge}</strong>
						<div class="fws-winner-meta-inline">
							<span>رتبه: ${winner.rank || '-'}</span>
							<span>عکاس: ${winner.photographer || '-'}</span>
						</div>
					</div>
					<div class="fws-winner-actions">
						<button class="fws-edit-btn dashicons dashicons-edit"></button>
					</div>
				</div>
			`;
		},

		createWinner: function(category) {
			const self = this;
			$.ajax({
				method: 'POST',
				url: fws_vars.rest_url + '/winners',
				data: JSON.stringify({ title: 'برنده جدید', category: [category] }),
				contentType: 'application/json',
				beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', fws_vars.nonce); },
				success: function(res) {
					self.showToast(fws_vars.i18n.saved);
					self.loadWinners();
					setTimeout(() => self.openEditor(res.id), 500);
				}
			});
		},

		openEditor: function(id) {
			const winner = this.winners.find(w => w.id == id);
			if (!winner) return;

			$('#edit-id').val(winner.id);
			$('#edit-title').val(winner.title);
			$('#edit-rank').val(winner.rank);
			$('#edit-photographer').val(winner.photographer);
			$('#edit-instagram').val(winner.instagram);
			$('#edit-best-of-festival').prop('checked', winner.best_of_festival);
			$('#edit-thumbnail-id').val('');
			$('#edit-thumb-preview').html(winner.thumbnail ? `<img src="${winner.thumbnail}">` : '');

			this.$galleryContainer.empty();
			if (winner.category && winner.category.includes('series')) {
				$('#edit-gallery-section').show();
				$('#edit-gallery-ids').val(winner.gallery);
				if (winner.gallery_urls) {
					const ids = winner.gallery.split(',');
					winner.gallery_urls.forEach((url, index) => {
						this.addGalleryItem(ids[index], url);
					});
				}
			} else {
				$('#edit-gallery-section').hide();
			}

			this.$editModal.addClass('active');
		},

		addGalleryItem: function(id, url) {
			this.$galleryContainer.append(`
				<div class="fws-gallery-item" data-id="${id}">
					<img src="${url}">
					<span class="remove">×</span>
				</div>
			`);
		},

		updateGalleryIds: function() {
			const ids = [];
			this.$galleryContainer.find('.fws-gallery-item').each(function() {
				ids.push($(this).data('id'));
			});
			$('#edit-gallery-ids').val(ids.join(','));
		},

		saveWinner: function() {
			const self = this;
			const id = $('#edit-id').val();
			const data = {
				title: $('#edit-title').val(),
				rank: $('#edit-rank').val(),
				photographer: $('#edit-photographer').val(),
				instagram: $('#edit-instagram').val(),
				best_of_festival: $('#edit-best-of-festival').is(':checked'),
				thumbnail_id: $('#edit-thumbnail-id').val(),
				gallery: $('#edit-gallery-ids').val()
			};

			$.ajax({
				method: 'POST',
				url: fws_vars.rest_url + '/winners/' + id,
				data: JSON.stringify(data),
				contentType: 'application/json',
				beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', fws_vars.nonce); },
				success: function() {
					self.$editModal.removeClass('active');
					self.showToast(fws_vars.i18n.saved);
					self.loadWinners();
				}
			});
		},

		deleteWinner: function(id) {
			const self = this;
			$.ajax({
				method: 'DELETE',
				url: fws_vars.rest_url + '/winners/' + id,
				beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', fws_vars.nonce); },
				success: function() {
					self.$editModal.removeClass('active');
					self.showToast(fws_vars.i18n.saved);
					self.loadWinners();
				}
			});
		},

		saveSettings: function() {
			const self = this;
			const data = {};
			$('#fws-design-form, #fws-general-form').serializeArray().forEach(item => data[item.name] = item.value);

			$.ajax({
				method: 'POST',
				url: fws_vars.rest_url + '/settings',
				data: JSON.stringify(data),
				contentType: 'application/json',
				beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', fws_vars.nonce); },
				success: function() { self.showToast(fws_vars.i18n.saved); }
			});
		},

		initSortable: function() {
			const self = this;
			$(".sortable").sortable({
				update: function() {
					const order = $(this).sortable('toArray', { attribute: 'data-id' });
					$.ajax({
						method: 'POST',
						url: fws_vars.rest_url + '/reorder',
						data: JSON.stringify({ order: order }),
						contentType: 'application/json',
						beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', fws_vars.nonce); }
					});
				}
			});
		},

		initColorPicker: function() {
			const self = this;
			$('.fws-color-picker').wpColorPicker({
				change: function(event, ui) {
					const color = ui.color.toString();
					const name = $(this).attr('name');
					self.updateLivePreview(name, color);
				}
			});
			$('.fws-color-picker').each(function() {
				self.updateLivePreview($(this).attr('name'), $(this).val());
			});
		},

		updateLivePreview: function(name, color) {
			if (name === 'primary_color') $('.fws-preview-circle.primary').css('background', color);
			if (name === 'accent_color') $('.fws-preview-circle.accent').css('background', color);
			if (name === 'bg_color') $('.fws-preview-circle.bg').css('background', color);
		},

		showToast: function(message, type = 'success') {
			this.$toast.text(message).addClass('show ' + type);
			setTimeout(() => this.$toast.removeClass('show ' + type), 3000);
		}
	};

	$(document).ready(function() { FWS_Admin.init(); });

})(jQuery);
