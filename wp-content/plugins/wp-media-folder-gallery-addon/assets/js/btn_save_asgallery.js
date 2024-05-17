(function () {
    jQuery(document).ready(function ($) {

        if (typeof wp !== "undefined") {
            if (wp.media && $('body.upload-php table.media').length === 0) {
                if (typeof wp.media.view.AttachmentFilters === "undefined" || typeof wp.media.view.AttachmentsBrowser === "undefined") {
                    return;
                }

                /* base on /wp-includes/js/media-views.js */
                var myViewToolbar = wp.media.view.Toolbar;
                if (typeof myViewToolbar !== "undefined") {
                    wp.media.view.Toolbar = wp.media.view.Toolbar.extend({
                        refresh: function () {
                            myViewToolbar.prototype.refresh.apply(this, arguments);
                            if (typeof wp.data === "undefined" || (typeof wp.data !== "undefined" && typeof wp.data.select('core/editor') === "undefined")) {
                                if (this.options.controller.options.state === 'gallery-edit' && $('[id^="__wp-uploader-id-"]:visible .btn_save_as_gallery').length === 0) {
                                    $('.media-button.media-button-insert').before('<span class="spinner wpmf_as_gallery_spinner"></span><button class="button btn_save_as_gallery">' + wpmf_btn_asgallery.btn_save_as_gallery + '</button>');
                                    $('.btn_save_as_gallery').on('click', function () {
                                        /* get image selected*/
                                        var ids = [];
                                        $('.attachment.save-ready').each(function (i, v) {
                                            var id = $(v).data('id');
                                            ids.push(id);
                                        });

                                        /* get theme */
                                        var theme = $('.display').val();
                                        /* Save as gallery */
                                        $.ajax({
                                            url: ajaxurl,
                                            method: "POST",
                                            dataType: 'json',
                                            data: {
                                                action: "wpmfgallery",
                                                task: "create_gallery",
                                                theme: theme,
                                                parent: 0,
                                                desc: '',
                                                type: 'save_as_gallery',
                                                ids: ids.join(),
                                                wpmf_gallery_nonce: wpmf_btn_asgallery.wpmf_gallery_nonce
                                            },
                                            beforeSend: function () {
                                                $('.wpmf_as_gallery_spinner').css('visibility', 'visible').show();
                                            },
                                            success: function () {
                                                $('.wpmf_as_gallery_spinner').hide();
                                            }
                                        });
                                    });
                                }
                            }
                        }
                    });
                }
            }
        }
    });
}(jQuery));
