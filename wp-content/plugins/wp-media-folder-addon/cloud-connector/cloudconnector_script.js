jQuery(document).ready(function ($) {
    $('.ggd-automatic-connect, .ggd-automatic-disconnect').insertAfter($('.ggd-connector-button'));
    $('.dropbox-automatic-connect, .dropbox-automatic-disconnect').insertAfter($('.dropbox-connector-button'));
    $('.onedrive-automatic-connect, .od-audisconnect').insertAfter($('.od-connector-button'));
    $('.onedrive-business-automatic-connect, .odb-audisconnect').insertAfter($('.odb-connector-button'));
    $('.gpt-automatic-connect, .gpt-automatic-disconnect').insertAfter($('.gpt-connector-button'));

    $(".ggd-automatic-connect, .onedrive-automatic-connect, .onedrive-business-automatic-connect, .dropbox-automatic-connect, .gpt-automatic-connect").on('click', function (e) {
        if ($(this).hasClass('ju-disconnected-autoconnect')) {
            return false;
        }
        e.preventDefault();
        var data_link = $(this).data('link');
        var data_network = $(this).data('network');
        var extension = 'wp-media-folder-addon.zip';
        window.open(cloudconnector_var.connector + data_network + '/login?sendback=' + data_link + '&extension=' + extension + '&ju_token=' + cloudconnector_var.ju_token,
            'cloudconnectwindow',
            'location=yes,height=620,width=560,scrollbars=yes,status=yes'
        );
    });

    $(".ggd-automatic-disconnect, .dropbox-automatic-disconnect").on('click', function (e) {
        if ($(this).hasClass('ju-disconnected-autoconnect')) {
            return false;
        }
        e.preventDefault();
        var $that = $(e.target);
        if (confirm('Are you sure to disconnect?')) {
            window.location = $that.attr('href');
        }
        return false;
    });

    $('.ggd-mode-radio-field .ju-radiobox').on('click', function () {
        var val = $(this).val();
        if (val === 'automatic') {
            $('input[name="googleClientId"]').parents('.ggd-connector-form').hide();
            $('input[name="googleClientSecret"]').parents('.ggd-connector-form').hide();
            $('input[name="javaScript_origins"]').parents('.ggd-connector-form').hide();
            $('input[name="redirect_uris"]').parents('.ggd-connector-form').hide();
            $('.ggd-connector-button').hide();
            $('.ggd-ju-connect-message').show();
            $('.ggd-connector-button').parent().next().hide();

            if ($('.ggd-automatic-disconnect').hasClass("ju-visibled")) {
                $('.ggd-automatic-disconnect').show();
            } else {
                $('.ggd-automatic-disconnect').hide();
            }

            if ($('.ggd-automatic-connect').hasClass("ju-visibled")) {
                $('.ggd-automatic-connect').show();
            } else {
                $('.ggd-automatic-connect').hide();
            }
        } else {
            $('input[name="googleClientId"]').parents('.ggd-connector-form').show();
            $('input[name="googleClientSecret"]').parents('.ggd-connector-form').show();
            $('input[name="javaScript_origins"]').parents('.ggd-connector-form').show();
            $('input[name="redirect_uris"]').parents('.ggd-connector-form').show();
            $('.ggd-connector-button').show();
            $('.ggd-connector-button').parent().next().show();
            $('.ggd-ju-connect-message, .ggd-automatic-connect, .ggd-automatic-disconnect').hide();
        }

        $.ajax({
            url: cloudconnector_var.ajaxurl + '?action=cloudconnector_wpmf_ggd_changemode',
            method: 'POST',
            dataType: 'json',
            data: {
                value: val,
                cloudconnect_nonce : cloudconnector_var.nonce
            },
            success: function (res) {

            }
        })

    });

    $('.od-mode-radio-field .ju-radiobox').on('click', function () {
        var val = $(this).val();

        if (val === 'automatic') {
            $('input[name="OneDriveClientId"]').parents('.od-connector-form').hide();
            $('input[name="OneDriveClientSecret"]').parents('.od-connector-form').hide();
            $('input[name="redirect_uris"]').parents('.od-connector-form').hide();
            $('.od-connector-button').hide();
            $('.od-ju-connect-message').show();

            if ($('.od-audisconnect.wpmf_onedrive_logout').hasClass("ju-visibled")) {
                $('.od-audisconnect.wpmf_onedrive_logout').show();
            } else {
                $('.od-audisconnect.wpmf_onedrive_logout').hide();
            }

            if ($('.onedrive-automatic-connect').hasClass("ju-visibled")) {
                $('.onedrive-automatic-connect').show();
            } else {
                $('.onedrive-automatic-connect').hide();
            }
        } else {
            $('input[name="OneDriveClientId"]').parents('.od-connector-form').show();
            $('input[name="OneDriveClientSecret"]').parents('.od-connector-form').show();
            $('input[name="redirect_uris"]').parents('.od-connector-form').show();
            $('.od-connector-button').show();
            $('.od-ju-connect-message, .onedrive-automatic-connect, .od-audisconnect.wpmf_onedrive_logout').hide();
        }

        $.ajax({
            url: cloudconnector_var.ajaxurl + '?action=cloudconnector_wpmf_onedrive_changemode',
            method: 'POST',
            dataType: 'json',
            data: {
                value: val,
                cloudconnect_nonce : cloudconnector_var.nonce
            },
            success: function (res) {

            }
        })

    });

    $('.odb-mode-radio-field .ju-radiobox').on('click', function () {
        var val = $(this).val();

        if (val === 'automatic') {
            $('input[name="OneDriveBusinessClientId"]').parents('.odb-connector-form').hide();
            $('input[name="OneDriveBusinessClientSecret"]').parents('.odb-connector-form').hide();
            $('input[name="redirect_uris"]').parents('.odb-connector-form').hide();
            $('.odb-connector-button').hide();
            $('.odb-ju-connect-message').show();

            if ($('.odb-audisconnect.wpmf_onedrive_business_logout').hasClass("ju-visibled")) {
                $('.odb-audisconnect.wpmf_onedrive_business_logout').show();
            } else {
                $('.odb-audisconnect.wpmf_onedrive_business_logout').hide();
            }

            if ($('.onedrive-business-automatic-connect').hasClass("ju-visibled")) {
                $('.onedrive-business-automatic-connect').show();
            } else {
                $('.onedrive-business-automatic-connect').hide();
            }
        } else {
            $('input[name="OneDriveBusinessClientId"]').parents('.odb-connector-form').show();
            $('input[name="OneDriveBusinessClientSecret"]').parents('.odb-connector-form').show();
            $('input[name="redirect_uris"]').parents('.odb-connector-form').show();
            $('.odb-connector-button').show();

            $('.odb-ju-connect-message, .onedrive-business-automatic-connect').hide();
            $('.odb-audisconnect.wpmf_onedrive_business_logout').hide();
        }

        $.ajax({
            url: cloudconnector_var.ajaxurl + '?action=cloudconnector_wpmf_onedrive_business_changemode',
            method: 'POST',
            dataType: 'json',
            data: {
                value: val,
                cloudconnect_nonce : cloudconnector_var.nonce
            },
            success: function (res) {

            }
        })

    });

    $('.dropbox-mode-radio-field .ju-radiobox').on('click', function () {
        var val = $(this).val();

        if (val === 'automatic') {
            $('input[name="dropboxKey"]').parents('.dropbox-connector-form').hide();
            $('input[name="dropboxSecret"]').parents('.dropbox-connector-form').hide();
            $('input[name="dropboxAuthor"]').parents('.dropbox-connector-form').hide();
            $('.dropbox-connector-button').hide();
            $('.dropbox-connector-button').parent().next().hide();
            $('.dropbox-ju-connect-message').show();

            if ($('.dropbox-automatic-disconnect').hasClass("ju-visibled")) {
                $('.dropbox-automatic-disconnect').show();
            } else {
                $('.dropbox-automatic-disconnect').hide();
            }

            if ($('.dropbox-automatic-connect').hasClass("ju-visibled")) {
                $('.dropbox-automatic-connect').show();
            } else {
                $('.dropbox-automatic-connect').hide();
            }
        } else {
            $('input[name="dropboxKey"]').parents('.dropbox-connector-form').show();
            $('input[name="dropboxSecret"]').parents('.dropbox-connector-form').show();
            $('input[name="dropboxAuthor"]').parents('.dropbox-connector-form').show();
            $('.dropbox-connector-button').show();
            $('.dropbox-connector-button').parent().next().show();
            $('.dropbox-ju-connect-message, .dropbox-automatic-disconnect, .dropbox-automatic-connect').hide();
        }

        $.ajax({
            url: cloudconnector_var.ajaxurl + '?action=cloudconnector_wpmf_dropbox_changemode',
            method: 'POST',
            dataType: 'json',
            data: {
                value: val,
                cloudconnect_nonce : cloudconnector_var.nonce
            },
            success: function (res) {

            }
        })

    });

    $('.gpt-mode-radio-field .ju-radiobox').on('click', function () {
        var val = $(this).val();
        if (val === 'automatic') {
            $('input[name="googlePhotoClientId"]').parents('.gpt-connector-form').hide();
            $('input[name="googlePhotoClientSecret"]').parents('.gpt-connector-form').hide();
            $('input[name="javaScript_origins"]').parents('.gpt-connector-form').hide();
            $('input[name="redirect_uris"]').parents('.gpt-connector-form').hide();
            $('.gpt-connector-button').hide();
            $('.gpt-ju-connect-message').show();
            $('.gpt-connector-button').parent().next().hide();

            if ($('.gpt-automatic-disconnect').hasClass("ju-visibled")) {
                $('.gpt-automatic-disconnect').show();
            } else {
                $('.gpt-automatic-disconnect').hide();
            }

            if ($('.gpt-automatic-connect').hasClass("ju-visibled")) {
                $('.gpt-automatic-connect').show();
            } else {
                $('.gpt-automatic-connect').hide();
            }
        } else {
            $('input[name="googlePhotoClientId"]').parents('.gpt-connector-form').show();
            $('input[name="googlePhotoClientSecret"]').parents('.gpt-connector-form').show();
            $('input[name="javaScript_origins"]').parents('.gpt-connector-form').show();
            $('input[name="redirect_uris"]').parents('.gpt-connector-form').show();
            $('.gpt-connector-button').show();
            $('.gpt-connector-button').parent().next().show();
            $('.gpt-ju-connect-message, .gpt-automatic-connect, .gpt-automatic-disconnect').hide();
        }

        $.ajax({
            url: cloudconnector_var.ajaxurl + '?action=cloudconnector_wpmf_gpt_changemode',
            method: 'POST',
            dataType: 'json',
            data: {
                value: val,
                cloudconnect_nonce : cloudconnector_var.nonce
            },
            success: function (res) {

            }
        })

    });
});