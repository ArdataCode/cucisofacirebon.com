/* global fusionAppConfig, FusionPageBuilderViewManager, imagesLoaded */
/* jshint -W098 */
/* eslint no-unused-vars: 0 */
var FusionPageBuilder = FusionPageBuilder || {};

(function () {
    jQuery(document).ready(function ($) {
        setTimeout(function () {
            $(document).on("change", '.wpmf_fusion_gallery_addon #gallery_id', function (e) {
                var title = $(this).find('option:selected').html();
                title = title.replace(/[--]/g, '');
                $('.wpmf_fusion_gallery_addon #gallery_title').val(title).change();
            });
        },200);
    });
}(jQuery));
