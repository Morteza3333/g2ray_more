(function($) {
    'use strict';

    $(window).on('load', function() {
        initFWS();
    });

    function initFWS() {
        // GSAP Entrance Animations
        if (typeof gsap !== 'undefined') {
            gsap.registerPlugin(ScrollTrigger);

            $('.fws-item').each(function(index, element) {
                gsap.to(element, {
                    scrollTrigger: {
                        trigger: element,
                        start: 'top 85%',
                        toggleActions: 'play none none none'
                    },
                    opacity: 1,
                    y: 0,
                    duration: 1,
                    ease: 'power3.out',
                    delay: index % 3 * 0.2 // Stagger effect for grid
                });
            });
        }

        // Swiper Instance
        var fwsSwiper;

        // Open Lightbox
        $('.fws-item-inner').on('click', function() {
            var slidesData = $(this).data('slides');
            if (!slidesData || !slidesData.length) return;

            var swiperWrapper = $('#fws-lightbox .swiper-wrapper');
            swiperWrapper.empty();

            slidesData.forEach(function(url) {
                swiperWrapper.append('<div class="swiper-slide"><img src="' + url + '"></div>');
            });

            $('#fws-lightbox').fadeIn(300).css('display', 'flex');
            $('body').addClass('fws-no-scroll');

            if (fwsSwiper) {
                fwsSwiper.destroy();
            }

            fwsSwiper = new Swiper('.fws-swiper', {
                loop: slidesData.length > 1,
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                keyboard: {
                    enabled: true,
                },
                grabCursor: true,
                effect: 'fade',
                fadeEffect: {
                    crossFade: true
                }
            });
        });

        // Close Lightbox
        $('.fws-lightbox-close, .fws-lightbox-bg').on('click', function() {
            $('#fws-lightbox').fadeOut(300);
            $('body').removeClass('fws-no-scroll');
        });

        // Close on Escape key
        $(document).on('keydown', function(e) {
            if (e.key === "Escape") {
                $('#fws-lightbox').fadeOut(300);
                $('body').removeClass('fws-no-scroll');
            }
        });
    }

})(jQuery);
