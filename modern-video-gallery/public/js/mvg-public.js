(function($) {
    'use strict';

    $(function() {
        const $gallery = $('.mvg-gallery-container');
        if (!$gallery.length) return;

        const $playerContainer = $('#mvg-main-player-container');
        const $currentTitle = $('#mvg-current-title');
        const $playlistItems = $('.mvg-playlist-item');
        const autoPlay = $gallery.data('autoplay') == 1;

        $playlistItems.on('click', function() {
            const $item = $(this);
            if ($item.hasClass('is-active')) return;

            const url = $item.data('url');
            const type = $item.data('type');
            const title = $item.data('title');

            // Update UI
            $playlistItems.removeClass('is-active');
            $item.addClass('is-active');

            // Fade out, update, fade in
            $playerContainer.css('opacity', '0.5');

            setTimeout(() => {
                const embedHtml = getEmbedHtml(url, type, autoPlay);
                $playerContainer.html(embedHtml);
                $currentTitle.text(title);
                $playerContainer.css('opacity', '1');
            }, 300);
        });

        function getEmbedHtml(url, type, autoPlay) {
            const autoplayAttr = autoPlay ? 'autoplay=1&mute=1' : 'autoplay=0';

            if (type === 'youtube') {
                const videoId = getYoutubeId(url);
                return `<iframe src="https://www.youtube.com/embed/${videoId}?${autoplayAttr}" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>`;
            } else if (type === 'vimeo') {
                const videoId = getVimeoId(url);
                const vimeoAutoplay = autoPlay ? 'autoplay=1&muted=1' : 'autoplay=0';
                return `<iframe src="https://player.vimeo.com/video/${videoId}?${vimeoAutoplay}" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>`;
            } else if (type === 'self') {
                const selfAutoplay = autoPlay ? 'autoplay muted' : '';
                return `<video src="${url}" controls ${selfAutoplay}></video>`;
            }
            return 'Invalid video type';
        }

        function getYoutubeId(url) {
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
            const match = url.match(regExp);
            return (match && match[2].length === 11) ? match[2] : null;
        }

        function getVimeoId(url) {
            const regExp = /vimeo\.com\/(?:video\/)?(\d+)/;
            const match = url.match(regExp);
            return match ? match[1] : null;
        }
    });

})(jQuery);
