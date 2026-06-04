(function($) {
    'use strict';

    var FWS = {
        init: function() {
            this.initGrid();
            this.initModal();
        },

        initGrid: function() {
            var $grid = $('#fws-winners-grid');
            if (!$grid.length) return;

            $grid.justifiedGallery({
                rowHeight: 300,
                margins: 15,
                lastRow: 'nojustify',
                captions: false,
                rtl: true
            }).on('jg.complete', function() {
                gsap.from('.fws-winner-card', {
                    duration: 0.8,
                    opacity: 0,
                    y: 30,
                    stagger: 0.1,
                    ease: 'power3.out'
                });
            });
        },

        initModal: function() {
            var self = this;
            var $modal = $('#fws-modal');
            var $swiperWrapper = $('#fws-swiper-wrapper');
            var swiperInstance = null;

            $('.fws-winner-card').on('click', function(e) {
                e.preventDefault();
                var data = $(this).data('winner');

                // Populate Modal Data
                $('#fws-modal-title').text(data.title);
                $('#fws-modal-rank').text('نفر ' + data.rank);
                $('#fws-modal-photographer').text(data.photographer);
                $('#fws-modal-insta-id').text(data.instagram);
                $('#fws-modal-insta-link').attr('href', 'https://instagram.com/' + data.instagram);

                // Populate Swiper Slides
                $swiperWrapper.empty();
                data.images.forEach(function(img) {
                    $swiperWrapper.append(
                        '<div class="swiper-slide">' +
                        '<img src="' + img.full + '" alt="">' +
                        '</div>'
                    );
                });

                // Show Modal
                $modal.addClass('active');
                $('body').addClass('fws-modal-open');

                // Init or Update Swiper
                if (swiperInstance) {
                    swiperInstance.destroy();
                }

                swiperInstance = new Swiper('.fws-swiper', {
                    slidesPerView: 1,
                    spaceBetween: 30,
                    loop: data.images.length > 1,
                    navigation: {
                        nextEl: '.swiper-button-next',
                        prevEl: '.swiper-button-prev',
                    },
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true
                    },
                    keyboard: {
                        enabled: true,
                    },
                    rtl: true
                });

                // GSAP Modal Entrance
                gsap.fromTo('.fws-modal-content',
                    { opacity: 0, scale: 0.9, y: 20 },
                    { opacity: 1, scale: 1, y: 0, duration: 0.5, ease: 'back.out(1.7)' }
                );
            });

            $('.fws-modal-close, #fws-modal').on('click', function(e) {
                if (e.target === this || $(e.target).hasClass('fws-modal-close')) {
                    $modal.removeClass('active');
                    $('body').removeClass('fws-modal-open');
                }
            });
        }
    };

    $(document).ready(function() {
        FWS.init();
    });

})(jQuery);
