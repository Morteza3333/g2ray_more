jQuery(document).ready(function ($) {
    'use strict';

    var STORAGE_FONT_KEY = 'fc_selected_font';
    var STORAGE_SIZE_KEY = 'fc_selected_size';

    // Helper to set cookie
    function setCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    // Helper to get cookie
    function getCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

    // Apply font family
    function applyFont(cssFamily) {
        if (cssFamily) {
            $('body').addClass('fc-font-active');
            document.body.style.setProperty('--fc-active-font', cssFamily);
            try {
                localStorage.setItem(STORAGE_FONT_KEY, cssFamily);
            } catch (e) {}
            setCookie(STORAGE_FONT_KEY, cssFamily, 365);
        } else {
            $('body').removeClass('fc-font-active');
            document.body.style.removeProperty('--fc-active-font');
            try {
                localStorage.removeItem(STORAGE_FONT_KEY);
            } catch (e) {}
            setCookie(STORAGE_FONT_KEY, '', -1);
        }
    }

    // Apply font size
    function applyFontSize(sizeValue, $btn) {
        if (sizeValue) {
            $('body').addClass('fc-size-active');
            document.body.style.setProperty('--fc-active-size', sizeValue);
            try {
                localStorage.setItem(STORAGE_SIZE_KEY, sizeValue);
            } catch (e) {}
            setCookie(STORAGE_SIZE_KEY, sizeValue, 365);

            $('.fc-size-btn').removeClass('active');
            if ($btn) {
                $btn.addClass('active');
            } else {
                $('.fc-size-btn[data-size="' + sizeValue + '"]').addClass('active');
            }
        } else {
            $('body').removeClass('fc-size-active');
            document.body.style.removeProperty('--fc-active-size');
            $('.fc-size-btn').removeClass('active');
            try {
                localStorage.removeItem(STORAGE_SIZE_KEY);
            } catch (e) {}
            setCookie(STORAGE_SIZE_KEY, '', -1);
        }
    }

    // Restore saved settings on DOM ready
    var savedFont = null;
    var savedSize = null;

    try {
        savedFont = localStorage.getItem(STORAGE_FONT_KEY) || getCookie(STORAGE_FONT_KEY);
        savedSize = localStorage.getItem(STORAGE_SIZE_KEY) || getCookie(STORAGE_SIZE_KEY);
    } catch (e) {}

    if (savedFont) {
        $('#fc-select-font-family').val(savedFont);
        applyFont(savedFont);
    }

    if (savedSize) {
        applyFontSize(savedSize);
    }

    // Font Family Select Event
    $('#fc-select-font-family').on('change', function () {
        var selectedFont = $(this).val();
        applyFont(selectedFont);
    });

    // Size Button Click Event
    $(document).on('click', '.fc-size-btn', function (e) {
        e.preventDefault();
        var sizeVal = $(this).data('size');
        applyFontSize(sizeVal, $(this));
    });

    // Reset Button Click Event
    $(document).on('click', '#fc-reset-btn', function (e) {
        e.preventDefault();
        $('#fc-select-font-family').val('');
        applyFont('');
        applyFontSize('');
    });
});
