jQuery(document).ready(function ($) {
    'use strict';

    // Toggle font type fields
    $('#fc_font_type').on('change', function () {
        var type = $(this).val();
        $('.fc-font-source-field').hide();
        if (type === 'upload') {
            $('#fc-field-upload').show();
        } else if (type === 'external') {
            $('#fc-field-external').show();
        }
    }).trigger('change');

    // WP Media Uploader for Font Files
    var mediaUploader;
    $('#fc_upload_font_btn').on('click', function (e) {
        e.preventDefault();

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'انتخاب یا آپلود فایل فونت',
            button: { text: 'استفاده از این فونت' },
            multiple: false
        });

        mediaUploader.on('select', function () {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#fc_font_file_url').val(attachment.url);

            // Auto fill css family name if empty
            if (!$('#fc_font_name').val()) {
                var title = attachment.title.replace(/[-_]/g, ' ');
                $('#fc_font_name').val(title);
                $('#fc_css_family').val("'" + attachment.title + "', sans-serif");
            }
        });

        mediaUploader.open();
    });

    // Add Font AJAX
    $('#fc-add-font-form').on('submit', function (e) {
        e.preventDefault();

        var $btn = $('#fc-save-font-btn');
        $btn.prop('disabled', true).text(fontChangerAdmin.messages.saving);

        var data = {
            action: 'font_changer_add_font',
            nonce: fontChangerAdmin.nonce,
            font_name: $('#fc_font_name').val(),
            font_type: $('#fc_font_type').val(),
            font_file_url: $('#fc_font_file_url').val(),
            font_external_url: $('#fc_font_external_url').val(),
            css_family: $('#fc_css_family').val()
        };

        $.post(fontChangerAdmin.ajax_url, data, function (res) {
            $btn.prop('disabled', false).text('افزودن فونت به لیست');

            if (res.success) {
                alert(res.data.message);
                location.reload();
            } else {
                alert(res.data.message || fontChangerAdmin.messages.error);
            }
        }).fail(function () {
            $btn.prop('disabled', false).text('افزودن فونت به لیست');
            alert(fontChangerAdmin.messages.error);
        });
    });

    // Delete Font AJAX
    $(document).on('click', '.fc-delete-font', function (e) {
        e.preventDefault();

        if (!confirm(fontChangerAdmin.messages.confirm_delete)) {
            return;
        }

        var $btn = $(this);
        var fontId = $btn.data('id');
        var $tr = $btn.closest('tr');

        var data = {
            action: 'font_changer_delete_font',
            nonce: fontChangerAdmin.nonce,
            font_id: fontId
        };

        $.post(fontChangerAdmin.ajax_url, data, function (res) {
            if (res.success) {
                $tr.fadeOut(300, function () { $(this).remove(); });
            } else {
                alert(res.data.message || fontChangerAdmin.messages.error);
            }
        });
    });

    // Save Settings AJAX
    $('#fc-settings-form').on('submit', function (e) {
        e.preventDefault();

        var $btn = $('#fc-save-settings-btn');
        var $msg = $('#fc-settings-msg');
        $btn.prop('disabled', true);
        $msg.removeClass('fc-msg-success fc-msg-error').text(fontChangerAdmin.messages.saving);

        var data = {
            action: 'font_changer_save_settings',
            nonce: fontChangerAdmin.nonce,
            target_selector: $('#fc_target_selector').val(),
            size_small: $('#fc_size_small').val(),
            size_normal: $('#fc_size_normal').val(),
            size_large: $('#fc_size_large').val()
        };

        $.post(fontChangerAdmin.ajax_url, data, function (res) {
            $btn.prop('disabled', false);

            if (res.success) {
                $msg.addClass('fc-msg-success').text(fontChangerAdmin.messages.saved);
            } else {
                $msg.addClass('fc-msg-error').text(res.data.message || fontChangerAdmin.messages.error);
            }

            setTimeout(function () { $msg.text(''); }, 4000);
        });
    });
});
