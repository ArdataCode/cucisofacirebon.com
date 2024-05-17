/* global fusionAppConfig, FusionPageBuilderViewManager, imagesLoaded */
/* jshint -W098 */
/* eslint no-unused-vars: 0 */
var FusionPageBuilder = FusionPageBuilder || {};

(function () {
    /**
     * run masonry layout
     */
    function wpmfGalleryAddonAvadaInitSlider($container, params) {
        $container.imagesLoaded(function () {
            var slick_args = {
                infinite: true,
                slidesToShow: parseInt(params.columns),
                slidesToScroll: parseInt(params.columns),
                pauseOnHover: false,
                autoplay: false,
                adaptiveHeight: (parseInt(columns) === 1),
                autoplaySpeed: 5000,
                rows: parseInt(params.number_lines),
                responsive: [
                    {
                        breakpoint: 1024,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 3,
                            infinite: true,
                            dots: true
                        }
                    },
                    {
                        breakpoint: 600,
                        settings: {
                            slidesToShow: 2,
                            slidesToScroll: 2
                        }
                    },
                    {
                        breakpoint: 480,
                        settings: {
                            slidesToShow: 1,
                            slidesToScroll: 1
                        }
                    }
                ]
            };

            if (!$container.hasClass('slick-initialized')) {
                setTimeout(function () {
                    $container.slick(slick_args);
                }, 120);
            }
        });
    }

    function wpmfGalleryAddonAvadaInitMasonry($container) {
        var layout = $container.closest('.wpmf-gallerys-addon').data('layout');
        var padding = $container.data('gutter-width');
        if (layout === 'horizontal') {
            var row_height = $container.closest('.wpmf-gallerys-addon').data('row_height');
            if (typeof row_height === "undefined" || row_height === '') {
                row_height = 200;
            }
            $container.imagesLoaded(function () {
                $container.justifiedGallery({
                    rowHeight: row_height,
                    margins: padding
                });
            });
            return;
        }

        var $grid = $container.isotope({
            itemSelector: '.wpmf-gallery-item',
            percentPosition: true,
            layoutMode: 'packery',
            resizable: true,
            initLayout: true
        });

        // layout Isotope after each image loads
        $grid.find('.wpmf-gallery-item').imagesLoaded().progress( function() {
            setTimeout(function () {
                $grid.isotope('layout');
                $grid.find('.wpmf-gallery-item').addClass('masonry-brick');
            },200);
        });
    }

    function wpmfGalleryAddonAvadaInitCustomGrid($container) {
        if ($container.hasClass('wpmfInitPackery')) {
            $container.isotope('destroy');
        }
        $container.imagesLoaded(function () {
            var gutter = $container.data('gutter');
            $container.closest('.wpmf_gallery_wrap').find('.loading_gallery').hide();
            var wrap_width = $container.width();
            var one_col_width = (wrap_width - gutter*12)/12;
            $container.find('.grid-item').each(function() {
                var dimensions = jQuery(this).data('styles');
                var w = (typeof dimensions.width !== "undefined") ? parseInt(dimensions.width) : 2;
                var h = (typeof dimensions.height !== "undefined") ? parseInt(dimensions.height) : 2;
                var g = (parseInt(w) - 1)*gutter;
                var display_width = one_col_width;
                var display_height = one_col_width;

                if (w > 1) {
                    display_width = one_col_width*w + g;
                }

                if (w == h) {
                    display_height = display_width;
                } else {
                    if (h > 1) {
                        display_height = (one_col_width*h) + (h - 1)*gutter;
                    }
                }

                jQuery(this).width(display_width);
                jQuery(this).height(display_height);
            });

            $container.isotope({
                itemSelector: '.grid-item',
                layoutMode: 'packery',
                resizable: true,
                initLayout: true,
                packery: {
                    gutter: gutter
                }
            });
            $container.addClass('wpmfInitPackery');
        });
    }

    function wpmfAvadaInitFlowsSlide($container) {
        imagesLoaded($container, function () {
            var enableNavButtons = $container.data('button');
            if (typeof enableNavButtons !== "undefined" && parseInt(enableNavButtons) === 1) {
                $container.flipster({
                    style: 'coverflow',
                    buttons: 'custom',
                    spacing: 0,
                    loop: true,
                    autoplay: 5000,
                    buttonNext: '<i class="flipto-next material-icons"> keyboard_arrow_right </i>',
                    buttonPrev: '<i class="flipto-prev material-icons"> keyboard_arrow_left </i>',
                    onItemSwitch: function (currentItem, previousItem) {
                        $container.find('.flipster__container').height(jQuery(currentItem).height());
                    },
                    onItemStart: function (currentItem) {
                        $container.find('.flipster__container').height(jQuery(currentItem).height());
                    }
                });
            } else {
                $container.flipster({
                    style: 'coverflow',
                    spacing: 0,
                    loop: true,
                    autoplay: 5000,
                    onItemSwitch: function (currentItem, previousItem) {
                        $container.find('.flipster__container').height(jQuery(currentItem).height());
                    },
                    onItemStart: function (currentItem) {
                        $container.find('.flipster__container').height(jQuery(currentItem).height());
                    }
                });
            }
        });
    }
    
    jQuery(document).ready(function () {
        var wpmfGalleryAddonElementSettingsView = FusionPageBuilder.ElementSettingsView;
        FusionPageBuilder.ElementSettingsView = FusionPageBuilder.ElementSettingsView.extend({
            optionChanged: function(event) {
                wpmfGalleryAddonElementSettingsView.prototype.optionChanged.apply(this, arguments);
                var wrap = this.$el;
                var element_type = this.model.attributes.element_type;
                var $target    = jQuery( event.target ),
                    $option    = $target.closest( '.fusion-builder-option' ),
                    paramName;
                paramName  = this.getParamName($target, $option);
                if (element_type === 'wpmf_fusion_gallery_addon') {
                    if (paramName === 'gallery_id') {
                        var title = wrap.find('.wpmf_fusion_gallery_addon li[data-option-id="gallery_id"] .fusion-option-selected').html();
                        title = title.replace(/[--]/g, '');
                        this.elementView.changeParam('gallery_title', title );
                    }
                }
            }
        });

        FusionPageBuilder.wpmf_fusion_gallery_addon = FusionPageBuilder.ElementView.extend({
            onRender: function () {
                this.afterPatch();
            },

            beforePatch: function() {
                var container = this.$el;
                var gallery_container = container.find('.wpmf_gallery_wrap');
                gallery_container.remove();
            },

            afterPatch: function() {
                var container = this.$el;
                var params = this.model.attributes.params;
                container.find('.loading_gallery').hide();
                if (parseInt(params.gallery_id) !== 0) {
                    var masonry_container = container.find('.gallery-masonry');
                    if (masonry_container.length) {
                        if (masonry_container.find('.wpmf-gallery-item').length) {
                            wpmfGalleryAddonAvadaInitMasonry(masonry_container);
                        }
                    }

                    var custom_grid_container = container.find('.wpmf-custom-grid');
                    if (custom_grid_container.length) {
                        if (custom_grid_container.find('.wpmf-gallery-item').length) {
                            setTimeout(function () {
                                wpmfGalleryAddonAvadaInitCustomGrid(custom_grid_container);
                            },200);
                        }
                    }

                    var a = setInterval(function () {
                        var slider_container = container.find('.wpmfslick');
                        if (slider_container.length) {
                            wpmfGalleryAddonAvadaInitSlider(slider_container, params);
                            clearInterval(a);
                        }
                    }, 200);

                    var flowslider_container = container.find('.flipster');
                    if (flowslider_container.length) {
                        wpmfAvadaInitFlowsSlide(flowslider_container);
                    }
                }
            }
        });
    });
}(jQuery));
