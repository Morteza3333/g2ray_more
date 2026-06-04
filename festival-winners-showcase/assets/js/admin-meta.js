jQuery(document).ready(function($) {
    var frame;
    var $galleryList = $('#fws-gallery-list');
    var $galleryInput = $('#fws_gallery_ids');

    $('#fws_add_gallery_images').on('click', function(e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: 'Select Images for Series',
            button: {
                text: 'Add to Gallery'
            },
            multiple: true
        });

        frame.on('select', function() {
            var selection = frame.state().get('selection');
            var ids = $galleryInput.val() ? $galleryInput.val().split(',') : [];

            selection.map(function(attachment) {
                attachment = attachment.toJSON();
                if (ids.indexOf(attachment.id.toString()) === -1) {
                    ids.push(attachment.id);
                    $galleryList.append(
                        '<li data-id="' + attachment.id + '" style="margin: 5px; position: relative; border: 1px solid #ccc;">' +
                        '<img src="' + (attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url) + '" style="display: block; width: 100px; height: 100px; object-fit: cover;">' +
                        '<a href="#" class="fws-remove-image" style="position: absolute; top: -5px; right: -5px; background: red; color: white; border-radius: 50%; width: 20px; height: 20px; text-align: center; line-height: 20px; text-decoration: none;">&times;</a>' +
                        '</li>'
                    );
                }
            });

            $galleryInput.val(ids.join(','));
        });

        frame.open();
    });

    $galleryList.on('click', '.fws-remove-image', function(e) {
        e.preventDefault();
        var $li = $(this).closest('li');
        var id = $li.data('id').toString();
        var ids = $galleryInput.val().split(',');

        ids = ids.filter(function(v) {
            return v !== id;
        });

        $galleryInput.val(ids.join(','));
        $li.remove();
    });

    // Sortable functionality could be added here with jquery-ui-sortable if needed
});
