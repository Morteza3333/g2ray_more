(function($) {
    'use strict';

    $(function() {
        const $videoForm = $('#mvg-video-form');
        const $videoList = $('#mvg-video-list');
        const $loader = $('#mvg-video-list-loader');
        const template = $('#mvg-video-item-template').html();

        // Initialize color pickers
        $('.mvg-color-picker').wpColorPicker();

        // Load videos
        function loadVideos() {
            $loader.show();
            $.ajax({
                url: mvg_admin_vars.rest_url + '/videos',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', mvg_admin_vars.nonce);
                },
                success: function(videos) {
                    $videoList.empty();
                    videos.forEach(function(video) {
                        renderVideoItem(video);
                    });
                    $loader.hide();
                }
            });
        }

        function renderVideoItem(video) {
            let itemHtml = template
                .replace(/{{id}}/g, video.id)
                .replace(/{{title}}/g, video.title)
                .replace(/{{url}}/g, video.url)
                .replace(/{{thumbnail_url}}/g, video.thumbnail_url || 'https://via.placeholder.com/120x68?text=No+Thumb')
                .replace(/{{type}}/g, video.type)
                .replace('{{featured_class}}', video.is_featured == 1 ? 'active' : '')
                .replace('{{star_type}}', video.is_featured == 1 ? 'filled' : 'empty');

            $videoList.append(itemHtml);
        }

        // Add video
        $videoForm.on('submit', function(e) {
            e.preventDefault();
            const data = {
                title: $('#video-title').val(),
                url: $('#video-url').val(),
                thumbnail_url: $('#video-thumbnail').val(),
                type: $('#video-type').val()
            };

            $.ajax({
                url: mvg_admin_vars.rest_url + '/videos',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', mvg_admin_vars.nonce);
                },
                data: data,
                success: function() {
                    $videoForm[0].reset();
                    loadVideos();
                }
            });
        });

        // Delete video
        $videoList.on('click', '.mvg-delete-video', function() {
            if (!confirm('Are you sure you want to delete this video?')) return;
            const id = $(this).closest('.mvg-video-item').data('id');

            $.ajax({
                url: mvg_admin_vars.rest_url + '/videos/' + id,
                method: 'DELETE',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', mvg_admin_vars.nonce);
                },
                success: function() {
                    loadVideos();
                }
            });
        });

        // Set featured
        $videoList.on('click', '.mvg-set-featured', function() {
            const id = $(this).closest('.mvg-video-item').data('id');

            $.ajax({
                url: mvg_admin_vars.rest_url + '/videos/' + id,
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', mvg_admin_vars.nonce);
                },
                data: { is_featured: 1 },
                success: function() {
                    loadVideos();
                }
            });
        });

        // Reorder videos
        $videoList.sortable({
            handle: '.mvg-video-handle',
            update: function() {
                const order = $videoList.sortable('toArray', { attribute: 'data-id' });
                $.ajax({
                    url: mvg_admin_vars.rest_url + '/reorder',
                    method: 'POST',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', mvg_admin_vars.nonce);
                    },
                    data: { order: order }
                });
            }
        });

        // Media Uploader
        $('#mvg-upload-thumbnail').on('click', function(e) {
            e.preventDefault();
            const mediaUploader = wp.media({
                title: 'Select Thumbnail',
                button: { text: 'Use this image' },
                multiple: false
            });

            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#video-thumbnail').val(attachment.url);
            });

            mediaUploader.open();
        });

        // Settings form
        $('#mvg-settings-form').on('submit', function(e) {
            e.preventDefault();
            const data = $(this).serializeArray().reduce(function(obj, item) {
                obj[item.name] = item.value;
                return obj;
            }, {});

            $.ajax({
                url: mvg_admin_vars.rest_url + '/settings',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', mvg_admin_vars.nonce);
                },
                data: data,
                success: function() {
                    $('#mvg-settings-feedback').fadeIn().delay(2000).fadeOut();
                }
            });
        });

        if ($videoList.length) {
            loadVideos();
        }
    });

})(jQuery);
