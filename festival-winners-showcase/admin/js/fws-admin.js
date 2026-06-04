(function($) {
    'use strict';

    $(function() {
        // Color Picker
        $('.fws-color-picker').wpColorPicker();

        // Media Uploader for Gallery
        var file_frame;
        $('.fws-add-gallery').on('click', function(e) {
            e.preventDefault();

            if (file_frame) {
                file_frame.open();
                return;
            }

            file_frame = wp.media({
                title: fws_admin.media_title,
                button: {
                    text: fws_admin.media_button,
                },
                multiple: true
            });

            file_frame.on('select', function() {
                var selection = file_frame.state().get('selection');
                var ids = $('#winner_gallery_ids').val() ? $('#winner_gallery_ids').val().split(',') : [];

                selection.map(function(attachment) {
                    attachment = attachment.toJSON();
                    if (ids.indexOf(attachment.id.toString()) === -1) {
                        ids.push(attachment.id);
                        $('#fws-gallery-container').append(
                            '<div class="fws-gallery-item" data-id="' + attachment.id + '">' +
                            '<img src="' + (attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url) + '">' +
                            '<a class="fws-remove-image">×</a>' +
                            '</div>'
                        );
                    }
                });

                $('#winner_gallery_ids').val(ids.join(','));
            });

            file_frame.open();
        });

        // Remove Image from Gallery
        $(document).on('click', '.fws-remove-image', function(e) {
            e.preventDefault();
            var item = $(this).closest('.fws-gallery-item');
            var id = item.data('id').toString();
            var ids = $('#winner_gallery_ids').val().split(',');

            ids = ids.filter(function(v) { return v !== id; });
            $('#winner_gallery_ids').val(ids.join(','));
            item.remove();
        });

        // Sortable Gallery
        $('#fws-gallery-container').sortable({
            update: function() {
                var ids = [];
                $('#fws-gallery-container .fws-gallery-item').each(function() {
                    ids.push($(this).data('id'));
                });
                $('#winner_gallery_ids').val(ids.join(','));
            }
        });

        // Post Table Sortable (Drag & Drop Reordering)
        if ($('.wp-list-table #the-list').length > 0 && $('body').hasClass('post-type-winner_entry')) {
            $('.wp-list-table #the-list').sortable({
                items: 'tr',
                cursor: 'move',
                axis: 'y',
                update: function(event, ui) {
                    var order = [];
                    $('.wp-list-table #the-list tr').each(function() {
                        var id = $(this).attr('id');
                        if (id) {
                            order.push(id.replace('post-', ''));
                        }
                    });

                    $.ajax({
                        url: fws_admin.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'update_winner_order',
                            order: order,
                            nonce: fws_admin.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                // Visual feedback if needed
                            }
                        }
                    });
                }
            });
        }
    });

})(jQuery);
