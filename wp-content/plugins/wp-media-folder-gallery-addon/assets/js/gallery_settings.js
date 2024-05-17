(function ($) {
    var current_gallery = 0;
    $(document).ready(function () {
        /**
         * Copy gallery shortcode
         */
        $('.copy_shortcode_gallery').on('click',function () {
            var shortcode_value = $('.gallery_shortcode_input').val();
            wpmfFoldersModule.setClipboardText(shortcode_value, glraddon_settings.l18n.success_copy_shortcode);
        });

        /**
         * Change gallery theme in shortcode settings
         */
        $('.choose_gallery_theme').on('change',function(){
            current_gallery = $('.choose_gallery_id').val();
            var theme = $(this).val();
            if (typeof theme === "undefined") {
                theme = 'default';
            }
            $('.shortcode_theme_wrap').css('display', 'none');
            $('.shortcode_' + theme + '_theme').css('display', 'inline-block');
            var renderShortCode = '[wpmfgallery';
            renderShortCode += ' gallery_id="' + current_gallery + '"';
            renderShortCode += ' display="' + theme + '"';

            if($('[name="gallery_shortcode[display_tree]"]:checked').length) {
                renderShortCode += ' display_tree="1"';
            } else {
                renderShortCode += ' display_tree="0"';
            }

            if($('[name="gallery_shortcode[display_tag]"]:checked').length) {
                renderShortCode += ' display_tag="1"';
            } else {
                renderShortCode += ' display_tag="0"';
            }

            renderShortCode += ' customlink="0"';
            $('.shortcode_' + theme + '_theme .shortcode_param').each(function(){
                var param = $(this).data('param');
                var value = '';
                if (param === 'auto_animation') {
                    if($('[name="gallery_shortcode[theme][slider_theme][auto_animation]"]:checked').length) {
                        value = 1;
                    } else {
                        value = 0;
                    }
                } else {
                    value = $(this).val();
                }

                renderShortCode += ' ' + param + '="' + value + '"';
            });
            renderShortCode += ']';
            $('.gallery_shortcode_input').val(renderShortCode);
        });

        /**
         * Change gallery params in shortcode settings
         */
        $('.shortcode_param').on('change',function(){
            current_gallery = $('.choose_gallery_id').val();
            var theme = $('.choose_gallery_theme').val();
            if (typeof theme === "undefined") {
                theme = 'default';
            }

            $('.shortcode_theme_wrap').hide();
            $('.shortcode_' + theme + '_theme').show();

            var renderShortCode = '[wpmfgallery';
            renderShortCode += ' gallery_id="' + current_gallery + '"';
            renderShortCode += ' display="' + theme + '"';

            if($('[name="gallery_shortcode[display_tree]"]:checked').length) {
                renderShortCode += ' display_tree="1"';
            } else {
                renderShortCode += ' display_tree="0"';
            }

            if($('[name="gallery_shortcode[display_tag]"]:checked').length) {
                renderShortCode += ' display_tag="1"';
            } else {
                renderShortCode += ' display_tag="0"';
            }

            if($('[name="gallery_shortcode[crop_image]"]:checked').length) {
                renderShortCode += ' crop_image="1"';
            } else {
                renderShortCode += ' crop_image="0"';
            }

            renderShortCode += ' customlink="0"';
            $('.shortcode_' + theme + '_theme .shortcode_param').each(function(){
                var param = $(this).data('param');
                var value = '';
                if (param === 'auto_animation') {
                    if($('[name="gallery_shortcode[theme][slider_theme][auto_animation]"]:checked').length) {
                        value = 1;
                    } else {
                        value = 0;
                    }
                } else {
                    value = $(this).val();
                }

                renderShortCode += ' ' + param + '="' + value + '"';
            });
            renderShortCode += ']';
            $('.gallery_shortcode_input').val(renderShortCode);
        });
    });
})(jQuery);