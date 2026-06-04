(function($) {
    'use strict';

    const FWS_Public = {
        init: function() {
            this.initAnimations();
            this.initModal();
        },

        initAnimations: function() {
            if (typeof gsap === 'undefined' || !fws_pub_vars.animation_intensity || fws_pub_vars.animation_intensity === 'none') return;

            gsap.registerPlugin(ScrollTrigger);

            let duration = 1;
            let yOffset = 50;

            if (fws_pub_vars.animation_intensity === 'low') { duration = 0.5; yOffset = 20; }
            if (fws_pub_vars.animation_intensity === 'high') { duration = 1.5; yOffset = 80; }

            gsap.utils.toArray('.fws-reveal').forEach((elem) => {
                gsap.fromTo(elem,
                    { opacity: 0, y: yOffset },
                    {
                        opacity: 1,
                        y: 0,
                        duration: duration,
                        scrollTrigger: {
                            trigger: elem,
                            start: "top 85%",
                            toggleActions: "play none none none"
                        }
                    }
                );
            });
        },

        initModal: function() {
            const self = this;
            const $modal = $('#fws-modal');
            let mainSwiper;

            $('.fws-item-inner').on('click', function() {
                const $item = $(this);
                const galleryUrls = $item.data('gallery');
                const mainImg = $item.data('main-img');

                $modal.addClass('active');
                $('body').css('overflow', 'hidden');

                self.renderSlides(mainImg, galleryUrls);

                if (mainSwiper) mainSwiper.destroy();
                mainSwiper = new Swiper('.fws-main-swiper', {
                    pagination: { el: '.swiper-pagination', clickable: true },
                    navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
                    keyboard: true,
                    spaceBetween: 30,
                });
            });

            $('.fws-close, #fws-modal').on('click', function(e) {
                if (e.target === this || $(e.target).hasClass('fws-close')) {
                    $modal.removeClass('active');
                    $('body').css('overflow', '');
                }
            });
        },

        renderSlides: function(mainImg, galleryUrls) {
            const $wrapper = $('.fws-main-swiper .swiper-wrapper');
            $wrapper.empty();

            // First slide is always the main image
            $wrapper.append(`<div class="swiper-slide"><img src="${mainImg}" loading="lazy"></div>`);

            // If series, add gallery images
            if (galleryUrls && Array.isArray(galleryUrls)) {
                galleryUrls.forEach(url => {
                    $wrapper.append(`<div class="swiper-slide"><img src="${url}" loading="lazy"></div>`);
                });
            }
        }
    };

    $(window).on('load', function() {
        FWS_Public.init();
    });

})(jQuery);
