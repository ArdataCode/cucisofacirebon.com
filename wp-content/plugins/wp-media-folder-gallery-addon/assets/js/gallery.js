var wpmfGallery;
(function ($) {
    wpmfGallery = {
        wpmf_img_tags: '*',
        gallery_items: [],

        /**
         * Get all items in gallery
         * @param gallery
         * @returns {Array}
         */
        wpmfGalleryGetItems: function (gallery) {
            var lightbox_items = gallery.data('lightbox-items');
            var items = [];
            if (typeof lightbox_items === "undefined") {
                var $item_elements;
                if (gallery.hasClass('wpmf-flipster')) {
                    $item_elements = gallery.find('.wpmf-gallery-item .flipster__item__content > a[data-swipe="1"]');
                } else {
                    $item_elements = gallery.find('.wpmf-gallery-icon > a[data-swipe="1"]');
                }

                $item_elements.each(function () {
                    var src = $(this).attr('href');
                    var type = 'image';
                    if ($(this).hasClass('isvideo')) {
                        type = 'iframe';
                    }

                    var pos = items.map(function (e) {
                        return e.src;
                    }).indexOf(src);
                    if (pos === -1) {
                        items.push({src: src, type: type, caption: $(this).data('title')});
                    }
                });
            } else {
                items = lightbox_items;
            }
            return items;
        },

        callPopup: function() {
            var $fancbox;
            if ($.fancyboxMB) {
                $fancbox = $.fancyboxMB;
            } else {
                $fancbox = $.fancybox;
            }
            if ($fancbox) {
                var index = 0;
                $('.wpmf-gallerys-addon .wpmf-gallery-icon > a').unbind('click').bind('click', function (e) {
                    if ($(this).hasClass('wpmf_gallery_download_icon')) {
                        return;
                    }
                    if (parseInt($(this).data('swipe')) === 1) {
                        e.preventDefault();
                        var $this = $(this).closest('.wpmf-gallery-addon-wrap');
                        index = $(this).closest('.wpmf-gallery-item').data('index');
                        var items = wpmfGallery.wpmfGalleryGetItems($this);
                        if (!$fancbox.getInstance()) {
                            $fancbox.open(items, {
                                loop : true,
                                toolbar: true,
                                buttons: [
                                    "zoom",
                                    //"share",
                                    "slideShow",
                                    "fullScreen",
                                    //"download",
                                    //"thumbs",
                                    "close"
                                ],
                            }, index);
                        }
                    }
                });
            }
        },

        doGallery: function ($container, theme) {
            switch (theme) {
                case 'masonry':
               // case 'portfolio':
                    var id = $container.data('id');
                    if ($container.find('.wpmf-gallery-addon-wrap').is(':hidden')) {
                        return;
                    }

                    if ($container.find('.wpmf-gallery-addon-wrap').hasClass('masonry')) {
                        return;
                    }
                    imagesLoaded($container.find('.wpmf-gallery-addon-wrap'), function () {
                        $container.closest('.wpmf_gallery_wrap').find('.loading_gallery').hide();
                        wpmfGallery.galleryRunMasonry(400, $container, id);
                        $container.find('.wpmf-gallery-item').addClass('wpmf-gallery-item-show');
                        wpmfGallery.callPopup();
                    });
                    break;
                case 'default':
                case 'material':
                case 'square_grid':
                case 'portfolio':
                    var id = $container.data('id');
                    var columns = $container.find('.glrdefault').data('wpmfcolumns');
                    imagesLoaded($container.find('.glrdefault'), function () {
                        $container.closest('.wpmf_gallery_wrap').find('.loading_gallery').hide();
                        $container.find('figure').each(function (j, v) {
                            if ((j + 1) % columns === 0) {
                                $container.find('figure:nth(' + (j) + ')').after('<hr class="wpmfglr-line-break" />');
                            }
                        });
                        wpmfGallery.wpmfAutobrowse(id, $container.find('.glrdefault'), 'default');
                        wpmfGallery.callPopup();
                    });
                    break;
                case 'flowslide':
                    imagesLoaded($container, function () {
                        $container.closest('.wpmf_gallery_wrap').find('.loading_gallery').hide();
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
                                    $container.find('.flipster__container').height($(currentItem).height());
                                },
                                onItemStart: function (currentItem) {
                                    $container.find('.flipster__container').height($(currentItem).height());
                                }
                            });
                        } else {
                            $container.flipster({
                                style: 'coverflow',
                                spacing: 0,
                                loop: true,
                                autoplay: 5000,
                                onItemSwitch: function (currentItem, previousItem) {
                                    $container.find('.flipster__container').height($(currentItem).height());
                                },
                                onItemStart: function (currentItem) {
                                    $container.find('.flipster__container').height($(currentItem).height());
                                }
                            });
                        }
                        wpmfGallery.callPopup();
                    });
                    break;

                case 'slider':
                        if ($container.is(':hidden')) {
                            return;
                        }

                        if ($container.hasClass('slick-initialized') || $container.hasClass('wpmfslick_life')) {
                            return;
                        }

                        var animation = $container.data('animation');
                        var duration = parseInt($container.data('duration'));
                        var columns = parseInt($container.data('wpmfcolumns'));
                        var number_lines = parseInt($container.data('number_lines'));
                        var containerWidth = $container.width();
                        if (parseInt(columns) >= 4 && containerWidth <= 450) {
                            columns = 2;
                        }
                        var auto_animation = parseInt($container.data('auto_animation'));
                        imagesLoaded($container, function () {
                            $container.closest('.wpmf_gallery_wrap').find('.loading_gallery').hide();
                            var slick_args = {
                                infinite: true,
                                slidesToShow: parseInt(columns),
                                slidesToScroll: parseInt(columns),
                                pauseOnHover: true,
                                autoplay: (auto_animation === 1),
                                adaptiveHeight: (parseInt(columns) === 1),
                                autoplaySpeed: duration,
                                rows: number_lines,
                                fade: (animation === 'fade' && parseInt(columns) === 1),
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
                                $container.slick(slick_args);
                            }
                            $container.css('opacity', 1);
                            wpmfGallery.callPopup();
                        });
                    break;
                case 'custom_grid':
                    var gutter = $container.data('gutter');
                    imagesLoaded($container, function () {
                        $container.closest('.wpmf_gallery_wrap').find('.loading_gallery').hide();
                        var wrap_width = $container.width();
                        var one_col_width;
                        if ($container.closest('.elementor-element-edit-mode').length) {
                            one_col_width = (wrap_width - gutter*12)/12;
                        } else {
                            one_col_width = (wrap_width - gutter*11)/12;
                        }

                        $container.find('.grid-item').each(function() {
                            var dimensions = $(this).data('styles');
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

                            $(this).width(display_width);
                            $(this).height(display_height);
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
                        wpmfGallery.callPopup();
                    });
                    break;
            }
        },

        /* Init gallery */
        initGallery: function () {
            wpmfGallery.callPopup();
            /* re-call event with tags */
            wpmfGallery.wpmfEventGalleryTags();
            $('.wpmf_gallery_wrap .flipster').each(function () {
                var $flip = $(this);
                wpmfGallery.doGallery($flip, 'flowslide');
            });

            $('.wpmf-gallerys-addon').each(function () {
                var theme = $(this).data('theme');
                if (theme !== 'slider' && theme !== 'custom_grid') {
                    wpmfGallery.doGallery($(this), theme);
                }
            });

            /* init slider theme */
            $('.wpmfslick').each(function () {
                wpmfGallery.doGallery($(this), 'slider');
            });

            $('.wpmf-custom-grid').each(function () {
                wpmfGallery.doGallery($(this), 'custom_grid');
            });
        },

        /**
         * get column width, gutter width, count columns
         * @param $container
         * @returns {{columnWidth: number, gutterWidth, columns: Number}}
         */
        calculateGrid: function ($container) {
            var columns = parseInt($container.data('wpmfcolumns'));
            var gutterWidth = $container.data('gutter-width');
            var containerWidth = $container.width();

            if (isNaN(gutterWidth)) {
                gutterWidth = 5;
            } else if (gutterWidth > 500 || gutterWidth < 0) {
                gutterWidth = 5;
            }

            if (parseInt(columns) < 2 || containerWidth <= 450) {
                columns = 2;
            }

            gutterWidth = parseInt(gutterWidth);

            var allGutters = gutterWidth * (columns - 1);
            var contentWidth = containerWidth - allGutters;

            var columnWidth = Math.floor(contentWidth / columns);
            return {columnWidth: columnWidth, gutterWidth: gutterWidth, columns: columns};
        },

        /**
         * Run masonry gallery
         * @param duration
         * @param $container
         * @param id
         */
        galleryRunMasonry: function (duration, $container, id) {
            var layout = $container.data('layout');
            if ($container.is(':hidden')) {
                return;
            }

            var container = $container.find('.gallery-masonry');
            var $postBox = container.children('.wpmf-gallery-item');
            var o = wpmfGallery.calculateGrid($(container));
            var padding = o.gutterWidth;
            if (layout === 'horizontal') {
                if (container.hasClass('justified-gallery')) {
                    return;
                }

                var row_height = $container.data('row_height');
                if (typeof row_height === "undefined" || row_height === '') {
                    row_height = 200;
                }

                setTimeout(function () {
                    container.justifiedGallery({
                        rowHeight: row_height,
                        maxRowHeight: row_height,
                        //lastRow: 'left',
                        margins: padding
                    });
                },200);
                return;
            }

            if ($container.find('.wpmf-gallery-addon-wrap').hasClass('masonry')) {
                return;
            }

            $postBox.css({'width': o.columnWidth + 'px', 'margin-bottom': padding + 'px'});
            $(container).masonry({
                itemSelector: '.wpmf-gallery-item',
                columnWidth: o.columnWidth,
                gutter: padding,
                isAnimated: true,
                animationOptions: {
                    duration: duration,
                    easing: 'linear',
                    queue: false
                },
                isFitWidth: true
            });

            wpmfGallery.wpmfAutobrowse(id, container, 'masonry', o.columnWidth, padding);
        },

        /**
         * lazy load images in gallery
         * @param id theme id
         * @param container container parent of items
         * @param theme_type theme type
         * @param column_width item width
         * @param padding item padding
         */
        wpmfAutobrowse: function (id, container, theme_type, column_width, padding) {
            if (parseInt(wpmfgallery.progressive_loading) === 0) {
                return;
            }

            var count = $(container).data('count');
            var number = 8;
            var offset = 8;
            var current = 0;
            var theme = $(container).closest('.wpmf_gallery_box').data('theme');
            var settings = $(container).closest('.wpmf_gallery_wrap').data('top-gallery-settings');
            if (typeof settings.is_lazy_load === "undefined" || parseInt(settings.is_lazy_load) === 0) {
                return;
            }
            var tags = $(container).closest('.wpmf_gallery_wrap').find('.tab.filter-all-control.selected a').data('filter');
            container.autobrowse(
                {
                    url: function (offset) {
                        var ids = [];
                        $(container).find('.wpmf-gallery-item').each(function () {
                            ids.push($(this).data('id'));
                        });

                        var url = wpmfgallery.ajaxurl + '?action=wpmf_get_gallery_item&gallery_id=' + id + '&theme=' + theme + '&offset=' + offset + '&loaded_ids=' + ids.join();
                        if (typeof tags !== 'undefined' && tags !== '*') {
                            url += '&tags=' + tags;
                        }
                        return url;
                    },
                    postData: {settings: JSON.stringify(settings)},
                    timeout: 100,
                    template: function (response) {
                        var elems = [];
                        if (response.status) {
                            for (var i = 0; i < 8; i++) {
                                if (typeof response.items[i] !== "undefined") {
                                    var el = $(response.items[i]);
                                    elems[i] = $(el).get(0);
                                    if (theme_type === 'masonry') {
                                        $($(el).get(0)).hide().appendTo(container);
                                    } else {
                                        $($(el).get(0)).hide().appendTo(container).fadeIn(800);
                                    }
                                }
                            }

                            current += response.items.length;
                            if (theme_type === 'masonry') {
                                $(container).imagesLoaded(function () {
                                    $(elems).css({
                                        'width': column_width + 'px',
                                        'margin-bottom': padding + 'px',
                                        'opacity': 0
                                    }).show();

                                    $(container).masonry('appended', $(elems));
                                    $(elems).animate({
                                        opacity: 1,
                                    }, 100, function () {
                                        // Animation complete.
                                    });
                                    $(container).find('.wpmf-gallery-item').addClass('wpmf-gallery-item-show');
                                });
                            }
                            wpmfGallery.callPopup();
                        } else {
                            current += 8;
                        }
                    },
                    itemsReturned: function (response) {
                        if (current >= count) {
                            return 0;
                        }
                        return number;
                    },
                    offset: offset
                }
            );
        },

        wpmfStartElemenfolio: function () {

        },

        /* init tags event */
        wpmfEventGalleryTags: function () {
            $('.filter-all-control a').unbind('click').bind('click', function () {
                var $this = $(this);
                var galleryId = $this.closest('.wpmf_gallery_box').data('id');
                var $tree = $('.wpmf_gallery_tree[data-id="' + galleryId + '"]');
                var $container = $this.closest('.wpmf_gallery_wrap');
                var img_tags = $(this).data('filter');
                var settings = $container.data('top-gallery-settings');
                if (typeof img_tags !== "undefined") {
                    wpmfGallery.wpmf_img_tags = img_tags;
                }

                /* Load gallery */
                var data = {
                    action: "wpmf_load_gallery",
                    gallery_id: galleryId,
                    tags: wpmfGallery.wpmf_img_tags,
                    settings: settings,
                    wpmf_gallery_nonce: wpmfgallery.wpmf_gallery_nonce
                };

                data.selector = $this.closest('.wpmf_gallery_wrap').data('selector');
                if ($tree.length) {
                    var current = $tree.find('li.selected').data('id');
                    if (current === galleryId) {
                        data.settings = $this.closest('.wpmf_gallery_wrap').data('top-gallery-settings');
                    }
                    data.gallery_id = current;
                } else {
                    data.settings = $this.closest('.wpmf_gallery_wrap').data('top-gallery-settings');
                }

                $.ajax({
                    url: wpmfgallery.ajaxurl,
                    method: "POST",
                    dataType: 'json',
                    data: data,
                    beforeSend: function () {
                        $container.find('.wpmf_gallery_box *').hide();
                        $container.find('.wpmf_gallery_box .loading_gallery').show();
                    },
                    success: function (res) {
                        if (res.status) {
                            $this.closest('.wpmf_gallery_box').find('.loading_gallery').hide();
                            $container.find('.wpmf_gallery_box').html('').append(res.html);
                            wpmfGallery.initGallery();
                        }
                    }
                });
            });
        }
    };

    $(document).ready(function () {
        $('.wpmf-gallerys-life .wpmf-gallery-icon > a, .portfolio_lightbox, .wpmf_overlay').each(function () {
            var href = $(this).data('href');
            if (typeof href !== "undefined" && href !== '') {
                $(this).attr('href', href);
            }
        });

        if (wpmfgallery.wpmf_current_theme === 'Gleam') {
            setTimeout(function () {
                wpmfGallery.initGallery();
            }, 1000);
        } else {
            wpmfGallery.initGallery();
        }

        jQuery('.vc_tta-tab').on('click', function () {
            var id = jQuery(this).data('vc-target-model-id');
            if (typeof id === "undefined") {
                id = jQuery(this).find('a').attr('href');
                if (typeof id !== "undefined") {
                    setTimeout(function () {
                        var bodyContainers = jQuery('.vc_tta-panel' + id);
                        if (bodyContainers.find('.wpmf-gallerys').length) {
                            wpmfGallery.initGallery();
                        }
                    }, 200);
                }
            } else {
                setTimeout(function () {
                    var bodyContainers = jQuery('.vc_tta-panel[data-model-id="'+ id +'"]');
                    if (bodyContainers.find('.wpmf-gallerys').length) {
                        wpmfGallery.initGallery();
                    }
                }, 200);
            }
        });

        setTimeout(function () {
            $('.responsive-tabs__list__item').on('click', function () {
                var target = $(this).attr('aria-controls');
                var container = $('#' + target).find('.wpmf-gallerys-addon');
                if (container.length) {
                    var id = container.data('id');
                    wpmfGallery.galleryRunMasonry(400, container, id);
                }
            });

            $('.tabtitle.responsive-tabs__heading').on('click', function () {
                var container = $(this).next('.tabcontent.responsive-tabs__panel').find('.wpmf-gallerys-addon');
                if (container.length) {
                    var id = container.data('id');
                    wpmfGallery.galleryRunMasonry(400, container, id);
                }
            });
        }, 1000);

        // click to tab of advanced tab Blocks
        $('.advgb-tab').on('click', function (event) {
            event.preventDefault();
            var bodyContainers = $(this).closest('.advgb-tabs-wrapper').find('.advgb-tab-body-container');
            setTimeout(function () {
                var currentTabActive = $(event.target).closest('.advgb-tab');
                var href = currentTabActive.find('a').attr('href');
                if (bodyContainers.find('.advgb-tab-body[aria-labelledby="' + href.replace(/^#/, "") + '"] .wpmf-gallerys').length) {
                    wpmfGallery.initGallery();
                }
            }, 200);
        });

        // click to tab of Kadence Blocks
        $('.kt-tabs-title-list .kt-title-item').on('click', function (event) {
            event.preventDefault();
            var href = $(this).attr('id');
            var bodyContainers = $(this).closest('.kt-tabs-wrap').find('.kt-tabs-content-wrap');
            setTimeout(function () {
                if (bodyContainers.find('.kt-tab-inner-content[aria-labelledby="' + href + '"] .wpmf-gallerys').length) {
                    wpmfGallery.initGallery();
                }
            }, 200);
        });

        $('.accordion-header').on('click', function (event) {
            var bodyContainers = $(this).closest('.single-accordion').find('.accordion-inner');
            setTimeout(function () {
                if (bodyContainers.find('.wpmf-gallerys').length) {
                    wpmfGallery.initGallery();
                }
            }, 200);
        });

        // click to tab of Ultimate Blocks
        $('.wp-block-ub-tabbed-content-tab-title-wrap').on('click', function () {
            setTimeout(function () {
                var bodyContainers = $('.wp-block-ub-tabbed-content-tab-content-wrap.active');
                if (bodyContainers.find('.wpmf-gallerys').length) {
                    wpmfGallery.initGallery();
                }
            }, 200);
        });

        $('a[href^="#elementor-action"]').each(function () {
            var $this = $(this);
            var href = $this.attr('href');
            if (href.indexOf('action=popup') !== -1 || href.indexOf('action%3Dpopup') !== -1) {
                $this.on('click', function () {
                    setTimeout(function () {
                        var bodyContainers = $('.elementor-widget-container');
                        if (bodyContainers.find('.wpmf-gallerys').length) {
                            wpmfGallery.initGallery();
                        }
                    }, 500);
                });
            }
        });
    });

    $(document.body).on('post-load', function () {
        wpmfGallery.initGallery();
    });
})(jQuery);

function wpmfStartElemenGallery(){
    jQuery('#elementor-preview-iframe').contents().find('.flipster').imagesLoaded( function() {
        wpmfGallery.doGallery(jQuery('#elementor-preview-iframe').contents().find('.flipster'), 'flowslide');
    });

    jQuery('#elementor-preview-iframe').contents().find('.wpmfslick').imagesLoaded( function() {
        wpmfGallery.doGallery(jQuery('#elementor-preview-iframe').contents().find('.wpmfslick'), 'slider');
    });

    jQuery('#elementor-preview-iframe').contents().find('.wpmf-custom-grid').imagesLoaded( function() {
        wpmfGallery.doGallery(jQuery('#elementor-preview-iframe').contents().find('.wpmf-custom-grid'), 'custom_grid');
    });

    jQuery('#elementor-preview-iframe').contents().find('.gallery-masonry').imagesLoaded( function() {
        var galleries = jQuery('#elementor-preview-iframe').contents().find('.wpmf_gallery_box');
        galleries.each(function () {
            var $this = jQuery(this);
            jQuery(this).find('.loading_gallery').hide();
            var layout = jQuery(this).find('.wpmf-gallerys-addon').data('layout');
            if (layout === 'vertical') {
                jQuery(this).find('.gallery-masonry').isotope({
                    itemSelector: '.wpmf-gallery-item',
                    layoutMode: 'masonry',
                    percentPosition: true,
                    masonry: {
                        columnWidth: '.wpmf-gallery-item'
                    }
                });
            } else {
                var row_height = jQuery(this).find('.wpmf-gallerys-addon').data('row_height');
                if (typeof row_height === "undefined" || row_height === '') {
                    row_height = 200;
                }
                var padding = jQuery(this).find('.gallery-masonry').data('gutter-width');
                setTimeout(function () {
                    $this.find('.gallery-masonry').justifiedGallery({
                        rowHeight: row_height,
                        margins: padding
                    });
                },500);
            }

            jQuery(this).find('.gallery-masonry .wpmf-gallery-item').addClass('wpmf-gallery-item-show');
        });
    });
}

jQuery(window).on('load', function(){
    if (typeof elementorFrontend !== "undefined") {
        elementorFrontend.hooks.addAction('frontend/element_ready/widget', function($scope){
            wpmfStartElemenGallery();
        });
    }
});