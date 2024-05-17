'use strict';

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

(function (wpI18n, wpBlocks, wpElement, wpEditor, wpComponents) {
    var __ = wpI18n.__;
    var Component = wpElement.Component,
        Fragment = wpElement.Fragment;
    var registerBlockType = wpBlocks.registerBlockType;
    var BlockControls = wpEditor.BlockControls,
        BlockAlignmentToolbar = wpEditor.BlockAlignmentToolbar,
        InspectorControls = wpEditor.InspectorControls,
        PanelColorSettings = wpEditor.PanelColorSettings;
    var PanelBody = wpComponents.PanelBody,
        Modal = wpComponents.Modal,
        FocusableIframe = wpComponents.FocusableIframe,
        IconButton = wpComponents.IconButton,
        Toolbar = wpComponents.Toolbar,
        SelectControl = wpComponents.SelectControl,
        ToggleControl = wpComponents.ToggleControl,
        RangeControl = wpComponents.RangeControl,
        Placeholder = wpComponents.Placeholder;

    var $ = jQuery;
    var el = wpElement.createElement;
    var iconblock = el('svg', { width: 24, height: 24 }, el('path', { d: "M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-7 2v5l-1-.75L9 9V4h2zm7 16H6V4h1v9l3-2.25L13 13V4h5v16zm-6.72-2.04L9.5 15.81 7 19h10l-3.22-4.26z" }));
    var save_params = {};

    var WpmfGallery = function (_Component) {
        _inherits(WpmfGallery, _Component);

        function WpmfGallery() {
            _classCallCheck(this, WpmfGallery);

            var _this = _possibleConstructorReturn(this, (WpmfGallery.__proto__ || Object.getPrototypeOf(WpmfGallery)).apply(this, arguments));

            _this.state = {
                isOpen: false,
                title: '',
                gallery_items: []
            };

            _this.openModal = _this.openModal.bind(_this);
            _this.closeModal = _this.closeModal.bind(_this);
            _this.addEventListener = _this.addEventListener.bind(_this);
            _this.componentDidMount = _this.componentDidMount.bind(_this);
            return _this;
        }

        _createClass(WpmfGallery, [{
            key: 'componentWillMount',
            value: function componentWillMount() {
                var attributes = this.props.attributes;

                this.initLoadTheme();
            }
        }, {
            key: 'componentDidMount',
            value: function componentDidMount() {
                window.addEventListener("message", this.addEventListener, false);
            }
        }, {
            key: 'componentDidUpdate',
            value: function componentDidUpdate(prevProps) {
                var attributes = this.props.attributes;

                if (attributes.html !== '' && (prevProps.attributes.display_tree !== attributes.display_tree || prevProps.attributes.display_tag !== attributes.display_tag || prevProps.attributes.show_buttons !== attributes.show_buttons || prevProps.attributes.display !== attributes.display || prevProps.attributes.layout !== attributes.layout || prevProps.attributes.row_height !== attributes.row_height || prevProps.attributes.aspect_ratio !== attributes.aspect_ratio || prevProps.attributes.img_border_radius !== attributes.img_border_radius || prevProps.attributes.borderWidth !== attributes.borderWidth || prevProps.attributes.borderColor !== attributes.borderColor || prevProps.attributes.borderStyle !== attributes.borderStyle || prevProps.attributes.gutterwidth !== attributes.gutterwidth || prevProps.attributes.hoverShadowH !== attributes.hoverShadowH || prevProps.attributes.hoverShadowV !== attributes.hoverShadowV || prevProps.attributes.hoverShadowBlur !== attributes.hoverShadowBlur || prevProps.attributes.hoverShadowSpread !== attributes.hoverShadowSpread || prevProps.attributes.hoverShadowColor !== attributes.hoverShadowColor || prevProps.attributes.size !== attributes.size || prevProps.attributes.crop_image !== attributes.crop_image || prevProps.attributes.columns !== attributes.columns || prevProps.attributes.wpmf_orderby !== attributes.wpmf_orderby || prevProps.attributes.number_lines !== attributes.number_lines || prevProps.attributes.hover_color !== attributes.hover_color || prevProps.attributes.hover_opacity !== attributes.hover_opacity || prevProps.attributes.hover_title_position !== attributes.hover_title_position || prevProps.attributes.hover_title_size !== attributes.hover_title_size || prevProps.attributes.hover_title_color !== attributes.hover_title_color || prevProps.attributes.hover_desc_position !== attributes.hover_desc_position || prevProps.attributes.hover_desc_size !== attributes.hover_desc_size || prevProps.attributes.hover_desc_color !== attributes.hover_desc_color || prevProps.attributes.wpmf_order !== attributes.wpmf_order) || prevProps.attributes.galleryId !== attributes.galleryId) {
                    this.initLoadTheme();
                }

                if (attributes.html !== '') {
                    this.initTheme();
                }
            }
        }, {
            key: 'getTree',
            value: function getTree(categories, trees, parent) {
                var ij = 0;
                while (ij < categories.length) {
                    if (categories[ij].parent === parent) {
                        trees.push(categories[ij]);
                        this.getTree(categories, trees, categories[ij].term_id);
                    }
                    ij++;
                }
                return trees;
            }
        }, {
            key: 'initLoadTheme',
            value: function initLoadTheme() {
                var _props = this.props,
                    attributes = _props.attributes,
                    setAttributes = _props.setAttributes,
                    clientId = _props.clientId;
                var galleryId = attributes.galleryId,
                    display = attributes.display,
                    layout = attributes.layout,
                    row_height = attributes.row_height,
                    aspect_ratio = attributes.aspect_ratio,
                    display_tree = attributes.display_tree,
                    display_tag = attributes.display_tag,
                    columns = attributes.columns,
                    size = attributes.size,
                    crop_image = attributes.crop_image,
                    targetsize = attributes.targetsize,
                    link = attributes.link,
                    wpmf_orderby = attributes.wpmf_orderby,
                    wpmf_order = attributes.wpmf_order,
                    animation = attributes.animation,
                    duration = attributes.duration,
                    auto_animation = attributes.auto_animation,
                    number_lines = attributes.number_lines,
                    show_buttons = attributes.show_buttons,
                    gutterwidth = attributes.gutterwidth,
                    img_border_radius = attributes.img_border_radius,
                    borderWidth = attributes.borderWidth,
                    borderColor = attributes.borderColor,
                    borderStyle = attributes.borderStyle,
                    hoverShadowH = attributes.hoverShadowH,
                    hoverShadowV = attributes.hoverShadowV,
                    hoverShadowBlur = attributes.hoverShadowBlur,
                    hoverShadowSpread = attributes.hoverShadowSpread,
                    hoverShadowColor = attributes.hoverShadowColor,
                    hover_color = attributes.hover_color,
                    hover_opacity = attributes.hover_opacity,
                    hover_title_position = attributes.hover_title_position,
                    hover_title_size = attributes.hover_title_size,
                    hover_title_color = attributes.hover_title_color,
                    hover_desc_position = attributes.hover_desc_position,
                    hover_desc_size = attributes.hover_desc_size,
                    hover_desc_color = attributes.hover_desc_color;


                if (parseInt(galleryId) !== 0) {
                    var params = {
                        gallery_id: galleryId,
                        display: display,
                        layout: layout,
                        row_height: row_height,
                        aspect_ratio: aspect_ratio,
                        display_tree: display_tree,
                        display_tag: display_tag,
                        columns: columns,
                        size: size,
                        crop_image: crop_image,
                        targetsize: targetsize,
                        link: link,
                        orderby: wpmf_orderby,
                        order: wpmf_order,
                        animation: animation,
                        duration: duration,
                        auto_animation: auto_animation,
                        number_lines: number_lines,
                        show_buttons: show_buttons,
                        gutterwidth: gutterwidth,
                        img_border_radius: img_border_radius,
                        border_style: borderStyle,
                        border_width: borderWidth,
                        border_color: borderColor.replace('#', ''),
                        hoverShadowH: hoverShadowH,
                        hoverShadowV: hoverShadowV,
                        hoverShadowBlur: hoverShadowBlur,
                        hoverShadowSpread: hoverShadowSpread,
                        hoverShadowColor: hoverShadowColor.replace('#', ''),
                        hover_color: hover_color.replace('#', ''),
                        hover_opacity: hover_opacity,
                        hover_title_position: hover_title_position,
                        hover_title_size: hover_title_size,
                        hover_title_color: hover_title_color.replace('#', ''),
                        hover_desc_position: hover_desc_position,
                        hover_desc_size: hover_desc_size,
                        hover_desc_color: hover_desc_color.replace('#', '')
                    };

                    $('#block-' + clientId + ' .wpmf-gallery-addon-block-preview').html('<p style="text-align: center">' + wpmfgalleryblocks.l18n.loading + '</p>');
                    fetch(encodeURI(wpmfgalleryblocks.vars.ajaxurl + ('?action=wpmf_load_gallery_html&datas=' + JSON.stringify(params) + '&wpmf_gallery_nonce=' + wpmfgalleryblocks.vars.wpmf_gallery_nonce))).then(function (res) {
                        return res.json();
                    }).then(function (result) {
                        if (result.status) {
                            setAttributes({
                                html: result.html,
                                theme: result.theme
                            });
                        }
                    },
                    // errors
                    function (error) {});
                }
            }
        }, {
            key: 'initTheme',
            value: function initTheme() {
                var _props2 = this.props,
                    attributes = _props2.attributes,
                    clientId = _props2.clientId;
                var theme = attributes.theme,
                    columns = attributes.columns,
                    display_tree = attributes.display_tree,
                    layout = attributes.layout,
                    row_height = attributes.row_height,
                    gutterwidth = attributes.gutterwidth;

                var $container = $('#block-' + clientId + ' .wpmf-gallery-addon-block-preview');
                imagesLoaded($container, function () {
                    if (theme === 'slider') {
                        var $slider_container = $('#block-' + clientId + ' .wpmfslick');
                        var animation = $slider_container.data('animation');
                        var duration = parseInt($slider_container.data('duration'));
                        var auto_animation = parseInt($slider_container.data('auto_animation'));
                        var number_lines = parseInt($slider_container.data('number_lines'));
                        if ($slider_container.is(':hidden')) {
                            return;
                        }

                        var slick_args = {
                            infinite: true,
                            slidesToShow: parseInt(columns),
                            slidesToScroll: parseInt(columns),
                            pauseOnHover: true,
                            autoplay: auto_animation === 1,
                            adaptiveHeight: parseInt(columns) === 1,
                            autoplaySpeed: duration,
                            rows: number_lines,
                            fade: animation === 'fade',
                            responsive: [{
                                breakpoint: 1024,
                                settings: {
                                    slidesToShow: 3,
                                    slidesToScroll: 3,
                                    infinite: true,
                                    dots: true
                                }
                            }, {
                                breakpoint: 600,
                                settings: {
                                    slidesToShow: 2,
                                    slidesToScroll: 2
                                }
                            }, {
                                breakpoint: 480,
                                settings: {
                                    slidesToShow: 1,
                                    slidesToScroll: 1
                                }
                            }]
                        };

                        if (!$slider_container.hasClass('slick-initialized')) {
                            $slider_container.slick(slick_args);
                        }
                    }

                    if (theme === 'flowslide') {
                        var $flow_container = $('#block-' + clientId + ' .flipster');
                        var enableNavButtons = $flow_container.data('button');
                        if (typeof enableNavButtons !== "undefined" && parseInt(enableNavButtons) === 1) {
                            $flow_container.flipster({
                                style: 'coverflow',
                                buttons: 'custom',
                                spacing: 0,
                                loop: true,
                                autoplay: 5000,
                                buttonNext: '<i class="flipto-next material-icons"> keyboard_arrow_right </i>',
                                buttonPrev: '<i class="flipto-prev material-icons"> keyboard_arrow_left </i>',
                                onItemSwitch: function onItemSwitch(currentItem, previousItem) {
                                    $flow_container.find('.flipster__container').height($(currentItem).height());
                                },
                                onItemStart: function onItemStart(currentItem) {
                                    $flow_container.find('.flipster__container').height($(currentItem).height());
                                }
                            });
                        } else {
                            $flow_container.flipster({
                                style: 'coverflow',
                                spacing: 0,
                                loop: true,
                                autoplay: 5000,
                                onItemSwitch: function onItemSwitch(currentItem, previousItem) {
                                    $flow_container.find('.flipster__container').height($(currentItem).height());
                                },
                                onItemStart: function onItemStart(currentItem) {
                                    $flow_container.find('.flipster__container').height($(currentItem).height());
                                }
                            });
                        }
                    }

                    if (theme === 'masonry') {
                        var $masonry = $container.find('.gallery-masonry');
                        if (layout === 'vertical') {
                            $masonry.masonry({
                                itemSelector: '.wpmf-gallery-item',
                                gutter: 0,
                                transitionDuration: 0,
                                percentPosition: true
                            });
                        } else {
                            $masonry.justifiedGallery({
                                rowHeight: row_height,
                                margins: gutterwidth
                            });
                            $masonry.find('.wpmf-gallery-item').addClass('wpmf-gallery-item-show');
                        }
                        $masonry.find('.wpmf-gallery-item').addClass('wpmf-gallery-item-show');
                    }

                    if (theme === 'custom_grid') {
                        var $custom_grid = $container.find('.wpmf-custom-grid');
                        if ($custom_grid.hasClass('wpmfInitPackery')) {
                            return;
                        }

                        var wrap_width = $custom_grid.width();
                        var one_col_width = (wrap_width - gutterwidth * 12) / 12;
                        $custom_grid.find('.grid-item').each(function () {
                            var dimensions = $(this).data('styles');
                            var w = typeof dimensions.width !== "undefined" ? parseInt(dimensions.width) : 2;
                            var h = typeof dimensions.height !== "undefined" ? parseInt(dimensions.height) : 2;
                            var g = (parseInt(w) - 1) * gutterwidth;
                            var display_width = one_col_width;
                            var display_height = one_col_width;

                            if (w > 1) {
                                display_width = one_col_width * w + g;
                            }

                            if (w == h) {
                                display_height = display_width;
                            } else {
                                if (h > 1) {
                                    display_height = one_col_width * h + (h - 1) * gutterwidth;
                                }
                            }
                            $(this).width(display_width);
                            $(this).height(display_height);
                        });

                        $custom_grid.isotope({
                            itemSelector: '.grid-item',
                            layoutMode: 'packery',
                            resizable: true,
                            initLayout: true,
                            packery: {
                                gutter: parseInt(gutterwidth)
                            }
                        });

                        $custom_grid.addClass('wpmfInitPackery');
                    }
                });
            }
        }, {
            key: 'openModal',
            value: function openModal() {
                if (!this.state.isOpen) {
                    this.setState({ isOpen: true });
                }
            }
        }, {
            key: 'closeModal',
            value: function closeModal() {
                if (this.state.isOpen) {
                    this.setState({ isOpen: false });
                    var _props3 = this.props,
                        attributes = _props3.attributes,
                        setAttributes = _props3.setAttributes;
                    var galleryId = attributes.galleryId,
                        display = attributes.display,
                        layout = attributes.layout,
                        row_height = attributes.row_height,
                        aspect_ratio = attributes.aspect_ratio,
                        display_tree = attributes.display_tree,
                        display_tag = attributes.display_tag,
                        columns = attributes.columns,
                        size = attributes.size,
                        targetsize = attributes.targetsize,
                        link = attributes.link,
                        wpmf_orderby = attributes.wpmf_orderby,
                        wpmf_order = attributes.wpmf_order,
                        animation = attributes.animation,
                        duration = attributes.duration,
                        auto_animation = attributes.auto_animation,
                        number_lines = attributes.number_lines,
                        show_buttons = attributes.show_buttons;


                    if (typeof save_params.galleryId === 'undefined') {
                        return;
                    }

                    if (parseInt(save_params.galleryId) !== parseInt(galleryId) || save_params.display !== display || save_params.layout !== layout || parseInt(save_params.row_height) !== parseInt(row_height) || save_params.aspect_ratio !== aspect_ratio || save_params.display_tree !== display_tree || save_params.display_tag !== display_tag || save_params.columns !== columns || save_params.link !== link || save_params.wpmf_order !== wpmf_order || save_params.wpmf_orderby !== wpmf_orderby || save_params.size !== size || save_params.targetsize !== targetsize || save_params.animation !== animation || parseInt(save_params.duration) !== parseInt(duration) || parseInt(save_params.auto_animation) !== parseInt(auto_animation) || parseInt(save_params.number_lines) !== parseInt(number_lines) || parseInt(save_params.show_buttons) !== parseInt(show_buttons)) {
                        setAttributes({
                            galleryId: parseInt(save_params.galleryId),
                            display: save_params.display,
                            layout: save_params.layout,
                            row_height: parseInt(save_params.row_height),
                            aspect_ratio: save_params.aspect_ratio,
                            display_tree: parseInt(save_params.display_tree),
                            display_tag: parseInt(save_params.display_tag),
                            columns: save_params.columns,
                            link: save_params.link,
                            wpmf_order: save_params.wpmf_order,
                            wpmf_orderby: save_params.wpmf_orderby,
                            size: save_params.size,
                            targetsize: save_params.targetsize,
                            animation: save_params.animation,
                            duration: parseInt(save_params.duration),
                            auto_animation: parseInt(save_params.auto_animation),
                            number_lines: parseInt(save_params.number_lines),
                            show_buttons: parseInt(save_params.show_buttons)
                        });
                    }
                }
            }
        }, {
            key: 'addEventListener',
            value: function addEventListener(e) {
                if (!e.data.galleryId) {
                    return;
                }

                if (e.data.type !== 'wpmfgalleryinsert') {
                    return;
                }

                if (e.data.idblock !== this.props.clientId) {
                    return;
                }

                save_params = e.data;
                this.closeModal();
            }
        }, {
            key: 'render',
            value: function render() {
                var _this2 = this;

                var listBorderStyles = [{ label: __('None', 'wp-media-folder-gallery-addon'), value: 'none' }, { label: __('Solid', 'wp-media-folder-gallery-addon'), value: 'solid' }, { label: __('Dotted', 'wp-media-folder-gallery-addon'), value: 'dotted' }, { label: __('Dashed', 'wp-media-folder-gallery-addon'), value: 'dashed' }, { label: __('Double', 'wp-media-folder-gallery-addon'), value: 'double' }, { label: __('Groove', 'wp-media-folder-gallery-addon'), value: 'groove' }, { label: __('Ridge', 'wp-media-folder-gallery-addon'), value: 'ridge' }, { label: __('Inset', 'wp-media-folder-gallery-addon'), value: 'inset' }, { label: __('Outset', 'wp-media-folder-gallery-addon'), value: 'outset' }];

                var _props4 = this.props,
                    attributes = _props4.attributes,
                    setAttributes = _props4.setAttributes;
                var align = attributes.align,
                    galleryId = attributes.galleryId,
                    display = attributes.display,
                    display_tree = attributes.display_tree,
                    display_tag = attributes.display_tag,
                    layout = attributes.layout,
                    row_height = attributes.row_height,
                    aspect_ratio = attributes.aspect_ratio,
                    columns = attributes.columns,
                    size = attributes.size,
                    crop_image = attributes.crop_image,
                    targetsize = attributes.targetsize,
                    link = attributes.link,
                    wpmf_orderby = attributes.wpmf_orderby,
                    wpmf_order = attributes.wpmf_order,
                    img_border_radius = attributes.img_border_radius,
                    borderWidth = attributes.borderWidth,
                    borderStyle = attributes.borderStyle,
                    borderColor = attributes.borderColor,
                    hoverShadowH = attributes.hoverShadowH,
                    hoverShadowV = attributes.hoverShadowV,
                    hoverShadowBlur = attributes.hoverShadowBlur,
                    hoverShadowSpread = attributes.hoverShadowSpread,
                    hoverShadowColor = attributes.hoverShadowColor,
                    gutterwidth = attributes.gutterwidth,
                    animation = attributes.animation,
                    duration = attributes.duration,
                    auto_animation = attributes.auto_animation,
                    number_lines = attributes.number_lines,
                    show_buttons = attributes.show_buttons,
                    html = attributes.html,
                    tree_width = attributes.tree_width,
                    cover = attributes.cover,
                    hover_color = attributes.hover_color,
                    hover_opacity = attributes.hover_opacity,
                    hover_title_position = attributes.hover_title_position,
                    hover_title_size = attributes.hover_title_size,
                    hover_title_color = attributes.hover_title_color,
                    hover_desc_position = attributes.hover_desc_position,
                    hover_desc_size = attributes.hover_desc_size,
                    hover_desc_color = attributes.hover_desc_color;


                var list_sizes = Object.keys(wpmfgalleryblocks.vars.sizes).map(function (key, label) {
                    return {
                        label: wpmfgalleryblocks.vars.sizes[key],
                        value: key
                    };
                });

                return React.createElement(
                    Fragment,
                    null,
                    typeof cover !== "undefined" && React.createElement(
                        'div',
                        { className: 'wpmf-cover' },
                        React.createElement('img', { src: cover })
                    ),
                    typeof cover === "undefined" && galleryId !== 0 && React.createElement(
                        Fragment,
                        null,
                        React.createElement(
                            BlockControls,
                            null,
                            React.createElement(
                                Toolbar,
                                null,
                                React.createElement(BlockAlignmentToolbar, { value: align,
                                    onChange: function onChange(align) {
                                        return setAttributes({ align: align });
                                    } }),
                                React.createElement(IconButton, {
                                    className: 'components-toolbar__control',
                                    label: wpmfgalleryblocks.l18n.edit,
                                    icon: 'edit',
                                    onClick: function onClick() {
                                        return _this2.setState({ isOpen: true });
                                    }
                                }),
                                React.createElement(IconButton, {
                                    className: 'components-toolbar__control',
                                    label: wpmfgalleryblocks.l18n.remove,
                                    icon: 'no',
                                    onClick: function onClick() {
                                        return setAttributes({ galleryId: 0 });
                                    }
                                }),
                                React.createElement(IconButton, {
                                    className: 'components-toolbar__control',
                                    label: __('Refresh', 'wp-media-folder-gallery-addon'),
                                    icon: 'update',
                                    onClick: function onClick() {
                                        return _this2.initLoadTheme();
                                    }
                                })
                            )
                        ),
                        React.createElement(
                            InspectorControls,
                            null,
                            React.createElement(
                                PanelBody,
                                { title: __('Gallery Settings', 'wp-media-folder-gallery-addon') },
                                React.createElement(ToggleControl, {
                                    label: __('Gallery navigation', 'wp-media-folder-gallery-addon'),
                                    checked: display_tree,
                                    onChange: function onChange() {
                                        return setAttributes({ display_tree: display_tree === 1 ? 0 : 1 });
                                    }
                                }),
                                React.createElement(ToggleControl, {
                                    label: __('Display images tags', 'wp-media-folder-gallery-addon'),
                                    checked: display_tag,
                                    onChange: function onChange() {
                                        return setAttributes({ display_tag: display_tag === 1 ? 0 : 1 });
                                    }
                                }),
                                React.createElement(SelectControl, {
                                    label: __('Theme', 'wp-media-folder-gallery-addon'),
                                    value: display,
                                    options: [{ label: __('Use theme setting', 'wp-media-folder-gallery-addon'), value: '' }, { label: __('Default', 'wp-media-folder-gallery-addon'), value: 'default' }, { label: __('Masonry', 'wp-media-folder-gallery-addon'), value: 'masonry' }, { label: __('Portfolio', 'wp-media-folder-gallery-addon'), value: 'portfolio' }, { label: __('Slider', 'wp-media-folder-gallery-addon'), value: 'slider' }, { label: __('Flow slide', 'wp-media-folder-gallery-addon'), value: 'flowslide' }, { label: __('Square grid', 'wp-media-folder-gallery-addon'), value: 'square_grid' }, { label: __('Material', 'wp-media-folder-gallery-addon'), value: 'material' }, { label: __('Custom grid', 'wp-media-folder-gallery-addon'), value: 'custom_grid' }],
                                    onChange: function onChange(value) {
                                        return setAttributes({ display: value });
                                    }
                                }),
                                display === 'masonry' && React.createElement(SelectControl, {
                                    label: __('Layout', 'wp-media-folder-gallery-addon'),
                                    value: layout,
                                    options: [{ label: __('Vertical', 'wp-media-folder-gallery-addon'), value: 'vertical' }, { label: __('Horizontal', 'wp-media-folder-gallery-addon'), value: 'horizontal' }],
                                    onChange: function onChange(value) {
                                        return setAttributes({ layout: value });
                                    }
                                }),
                                display === 'masonry' && layout === 'horizontal' && React.createElement(RangeControl, {
                                    label: __('Row height', 'wp-media-folder-gallery-addon'),
                                    value: parseInt(row_height),
                                    onChange: function onChange(value) {
                                        return setAttributes({ row_height: parseInt(value) });
                                    },
                                    min: 50,
                                    max: 500,
                                    step: 1
                                }),
                                (display === 'slider' || display === 'portfolio' || display === 'default' || display === 'material' || display === 'square_grid') && React.createElement(SelectControl, {
                                    label: __('Aspect ratio', 'wp-media-folder-gallery-addon'),
                                    value: aspect_ratio,
                                    options: [{ label: 'Default', value: 'default' }, { label: '1:1', value: '1_1' }, { label: '3:2', value: '3_2' }, { label: '2:3', value: '2_3' }, { label: '4:3', value: '4_3' }, { label: '3:4', value: '3_4' }, { label: '16:9', value: '16_9' }, { label: '9:16', value: '9_16' }, { label: '21:9', value: '21_9' }, { label: '9:21', value: '9_21' }],
                                    onChange: function onChange(value) {
                                        return setAttributes({ aspect_ratio: value });
                                    }
                                }),
                                display !== 'flow_slide' && display !== 'custom_grid' && (layout === 'vertical' || display !== 'masonry') && React.createElement(SelectControl, {
                                    label: __('Columns', 'wp-media-folder-gallery-addon'),
                                    value: columns,
                                    options: [{ label: 1, value: '1' }, { label: 2, value: '2' }, { label: 3, value: '3' }, { label: 4, value: '4' }, { label: 5, value: '5' }, { label: 6, value: '6' }, { label: 7, value: '7' }, { label: 8, value: '8' }, { label: 9, value: '9' }],
                                    onChange: function onChange(value) {
                                        return setAttributes({ columns: value });
                                    }
                                }),
                                React.createElement(SelectControl, {
                                    label: __('Gallery image size', 'wp-media-folder-gallery-addon'),
                                    value: size,
                                    options: list_sizes,
                                    onChange: function onChange(value) {
                                        return setAttributes({ size: value });
                                    }
                                }),
                                display === 'slider' && React.createElement(ToggleControl, {
                                    label: wpmf.l18n.crop_image,
                                    checked: crop_image,
                                    onChange: function onChange() {
                                        return setAttributes({ crop_image: crop_image === 1 ? 0 : 1 });
                                    }
                                }),
                                React.createElement(SelectControl, {
                                    label: __('Lightbox size', 'wp-media-folder-gallery-addon'),
                                    value: targetsize,
                                    options: list_sizes,
                                    onChange: function onChange(value) {
                                        return setAttributes({ targetsize: value });
                                    }
                                }),
                                React.createElement(SelectControl, {
                                    label: __('Action on click', 'wp-media-folder-gallery-addon'),
                                    value: link,
                                    options: [{ label: __('Lightbox', 'wp-media-folder-gallery-addon'), value: 'file' }, { label: __('Attachment Page', 'wp-media-folder-gallery-addon'), value: 'post' }, { label: __('None', 'wp-media-folder-gallery-addon'), value: 'none' }],
                                    onChange: function onChange(value) {
                                        return setAttributes({ link: value });
                                    }
                                }),
                                React.createElement(SelectControl, {
                                    label: __('Order by', 'wp-media-folder-gallery-addon'),
                                    value: wpmf_orderby,
                                    options: [{ label: __('Custom', 'wp-media-folder-gallery-addon'), value: 'post__in' }, { label: __('Random', 'wp-media-folder-gallery-addon'), value: 'rand' }, { label: __('Title', 'wp-media-folder-gallery-addon'), value: 'title' }, { label: __('Date', 'wp-media-folder-gallery-addon'), value: 'date' }],
                                    onChange: function onChange(value) {
                                        return setAttributes({ wpmf_orderby: value });
                                    }
                                }),
                                React.createElement(SelectControl, {
                                    label: __('Order', 'wp-media-folder-gallery-addon'),
                                    value: wpmf_order,
                                    options: [{ label: __('Ascending', 'wp-media-folder-gallery-addon'), value: 'ASC' }, { label: __('Descending', 'wp-media-folder-gallery-addon'), value: 'DESC' }],
                                    onChange: function onChange(value) {
                                        return setAttributes({ wpmf_order: value });
                                    }
                                }),
                                display === 'slider' && React.createElement(
                                    Fragment,
                                    null,
                                    React.createElement(SelectControl, {
                                        label: __('Transition Type', 'wp-media-folder-gallery-addon'),
                                        value: animation,
                                        options: [{ label: __('Slide', 'wp-media-folder-gallery-addon'), value: 'slide' }, { label: __('Fade', 'wp-media-folder-gallery-addon'), value: 'fade' }],
                                        onChange: function onChange(value) {
                                            return setAttributes({ animation: value });
                                        }
                                    }),
                                    React.createElement(RangeControl, {
                                        label: __('Transition Duration (ms)', 'wp-media-folder-gallery-addon'),
                                        value: duration || 0,
                                        onChange: function onChange(value) {
                                            return setAttributes({ duration: value });
                                        },
                                        min: 0,
                                        max: 10000,
                                        step: 1000
                                    }),
                                    React.createElement(ToggleControl, {
                                        label: __('Automatic Animation', 'wp-media-folder-gallery-addon'),
                                        checked: auto_animation,
                                        onChange: function onChange() {
                                            return setAttributes({ auto_animation: auto_animation === 1 ? 0 : 1 });
                                        }
                                    }),
                                    React.createElement(SelectControl, {
                                        label: __('Number Lines', 'wp-media-folder-gallery-addon'),
                                        value: number_lines,
                                        options: [{ label: 1, value: 1 }, { label: 2, value: 2 }, { label: 3, value: 3 }],
                                        onChange: function onChange(value) {
                                            return setAttributes({ number_lines: parseInt(value) });
                                        }
                                    })
                                ),
                                display === 'flowslide' && React.createElement(
                                    Fragment,
                                    null,
                                    React.createElement(ToggleControl, {
                                        label: __('Show Buttons', 'wp-media-folder-gallery-addon'),
                                        checked: show_buttons,
                                        onChange: function onChange() {
                                            return setAttributes({ show_buttons: show_buttons === 1 ? 0 : 1 });
                                        }
                                    })
                                )
                            ),
                            React.createElement(
                                PanelBody,
                                { title: __('Border', 'wp-media-folder-gallery-addon'), initialOpen: false },
                                React.createElement(RangeControl, {
                                    label: __('Border radius', 'wp-media-folder-gallery-addon'),
                                    'aria-label': __('Add rounded corners to the gallery items.', 'wp-media-folder-gallery-addon'),
                                    value: img_border_radius,
                                    onChange: function onChange(value) {
                                        return setAttributes({ img_border_radius: value });
                                    },
                                    min: 0,
                                    max: 20,
                                    step: 1
                                }),
                                React.createElement(SelectControl, {
                                    label: __('Border style', 'wp-media-folder-gallery-addon'),
                                    value: borderStyle,
                                    options: listBorderStyles,
                                    onChange: function onChange(value) {
                                        return setAttributes({ borderStyle: value });
                                    }
                                }),
                                borderStyle !== 'none' && React.createElement(
                                    Fragment,
                                    null,
                                    React.createElement(PanelColorSettings, {
                                        title: __('Border Color', 'wp-media-folder-gallery-addon'),
                                        initialOpen: false,
                                        colorSettings: [{
                                            label: __('Border Color', 'wp-media-folder-gallery-addon'),
                                            value: borderColor,
                                            onChange: function onChange(value) {
                                                return setAttributes({ borderColor: value === undefined ? '#2196f3' : value });
                                            }
                                        }]
                                    }),
                                    React.createElement(RangeControl, {
                                        label: __('Border width', 'wp-media-folder-gallery-addon'),
                                        value: borderWidth || 0,
                                        onChange: function onChange(value) {
                                            return setAttributes({ borderWidth: value });
                                        },
                                        min: 0,
                                        max: 10
                                    })
                                )
                            ),
                            React.createElement(
                                PanelBody,
                                { title: __('Margin', 'wp-media-folder-gallery-addon'), initialOpen: false },
                                React.createElement(RangeControl, {
                                    label: __('Gutter', 'wp-media-folder-gallery-addon'),
                                    value: gutterwidth,
                                    onChange: function onChange(value) {
                                        return setAttributes({ gutterwidth: value });
                                    },
                                    min: 0,
                                    max: 100,
                                    step: 5
                                })
                            ),
                            React.createElement(
                                PanelBody,
                                { title: __('Shadow', 'wp-media-folder-gallery-addon'), initialOpen: false },
                                React.createElement(RangeControl, {
                                    label: __('Shadow H offset', 'wp-media-folder-gallery-addon'),
                                    value: hoverShadowH || 0,
                                    onChange: function onChange(value) {
                                        return setAttributes({ hoverShadowH: value });
                                    },
                                    min: -50,
                                    max: 50
                                }),
                                React.createElement(RangeControl, {
                                    label: __('Shadow V offset', 'wp-media-folder-gallery-addon'),
                                    value: hoverShadowV || 0,
                                    onChange: function onChange(value) {
                                        return setAttributes({ hoverShadowV: value });
                                    },
                                    min: -50,
                                    max: 50
                                }),
                                React.createElement(RangeControl, {
                                    label: __('Shadow blur', 'wp-media-folder-gallery-addon'),
                                    value: hoverShadowBlur || 0,
                                    onChange: function onChange(value) {
                                        return setAttributes({ hoverShadowBlur: value });
                                    },
                                    min: 0,
                                    max: 50
                                }),
                                React.createElement(RangeControl, {
                                    label: __('Shadow spread', 'wp-media-folder-gallery-addon'),
                                    value: hoverShadowSpread || 0,
                                    onChange: function onChange(value) {
                                        return setAttributes({ hoverShadowSpread: value });
                                    },
                                    min: 0,
                                    max: 50
                                }),
                                React.createElement(PanelColorSettings, {
                                    title: __('Color Settings', 'wp-media-folder-gallery-addon'),
                                    initialOpen: false,
                                    colorSettings: [{
                                        label: __('Shadow Color', 'wp-media-folder-gallery-addon'),
                                        value: hoverShadowColor,
                                        onChange: function onChange(value) {
                                            return setAttributes({ hoverShadowColor: value === undefined ? '#ccc' : value });
                                        }
                                    }]
                                })
                            ),
                            React.createElement(
                                PanelBody,
                                { title: __('Hover', 'wp-media-folder-gallery-addon'), initialOpen: false },
                                React.createElement(PanelColorSettings, {
                                    title: __('Hover color', 'wp-media-folder-gallery-addon'),
                                    initialOpen: false,
                                    colorSettings: [{
                                        label: __('Hover Color', 'wp-media-folder-gallery-addon'),
                                        value: hover_color,
                                        onChange: function onChange(value) {
                                            return setAttributes({ hover_color: value === undefined ? '#000' : value });
                                        }
                                    }]
                                }),
                                React.createElement(RangeControl, {
                                    label: __('Hover opacity', 'wp-media-folder-gallery-addon'),
                                    value: hover_opacity,
                                    onChange: function onChange(value) {
                                        return setAttributes({ hover_opacity: value });
                                    },
                                    min: 0,
                                    max: 1,
                                    step: 0.1
                                }),
                                React.createElement(SelectControl, {
                                    label: __('Title position', 'wp-media-folder-gallery-addon'),
                                    value: hover_title_position,
                                    options: [{ label: __('None', 'wp-media-folder-gallery-addon'), value: 'none' }, { label: __('Top left', 'wp-media-folder-gallery-addon'), value: 'top_left' }, { label: __('Top right', 'wp-media-folder-gallery-addon'), value: 'top_right' }, { label: __('Top center', 'wp-media-folder-gallery-addon'), value: 'top_center' }, { label: __('Bottom left', 'wp-media-folder-gallery-addon'), value: 'bottom_left' }, { label: __('Bottom right', 'wp-media-folder-gallery-addon'), value: 'bottom_right' }, { label: __('Bottom center', 'wp-media-folder-gallery-addon'), value: 'bottom_center' }, { label: __('Center center', 'wp-media-folder-gallery-addon'), value: 'center_center' }],
                                    onChange: function onChange(value) {
                                        return setAttributes({ hover_title_position: value });
                                    }
                                }),
                                React.createElement(SelectControl, {
                                    label: __('Description position', 'wp-media-folder-gallery-addon'),
                                    value: hover_desc_position,
                                    options: [{ label: __('None', 'wp-media-folder-gallery-addon'), value: 'none' }, { label: __('Top left', 'wp-media-folder-gallery-addon'), value: 'top_left' }, { label: __('Top right', 'wp-media-folder-gallery-addon'), value: 'top_right' }, { label: __('Top center', 'wp-media-folder-gallery-addon'), value: 'top_center' }, { label: __('Bottom left', 'wp-media-folder-gallery-addon'), value: 'bottom_left' }, { label: __('Bottom right', 'wp-media-folder-gallery-addon'), value: 'bottom_right' }, { label: __('Bottom center', 'wp-media-folder-gallery-addon'), value: 'bottom_center' }, { label: __('Center center', 'wp-media-folder-gallery-addon'), value: 'center_center' }],
                                    onChange: function onChange(value) {
                                        return setAttributes({ hover_desc_position: value });
                                    }
                                }),
                                React.createElement(RangeControl, {
                                    label: __('Title size', 'wp-media-folder-gallery-addon'),
                                    value: hover_title_size || 16,
                                    onChange: function onChange(value) {
                                        return setAttributes({ hover_title_size: value });
                                    },
                                    min: 0,
                                    step: 1,
                                    max: 150
                                }),
                                React.createElement(RangeControl, {
                                    label: __('Description size', 'wp-media-folder-gallery-addon'),
                                    value: hover_desc_size || 16,
                                    onChange: function onChange(value) {
                                        return setAttributes({ hover_desc_size: value });
                                    },
                                    min: 0,
                                    step: 1,
                                    max: 150
                                }),
                                React.createElement(PanelColorSettings, {
                                    title: __('Title color', 'wp-media-folder-gallery-addon'),
                                    initialOpen: false,
                                    colorSettings: [{
                                        label: __('Title color', 'wp-media-folder-gallery-addon'),
                                        value: hover_title_color,
                                        onChange: function onChange(value) {
                                            return setAttributes({ hover_title_color: value === undefined ? '#fff' : value });
                                        }
                                    }]
                                }),
                                React.createElement(PanelColorSettings, {
                                    title: __('Description color', 'wp-media-folder-gallery-addon'),
                                    initialOpen: false,
                                    colorSettings: [{
                                        label: __('Description color', 'wp-media-folder-gallery-addon'),
                                        value: hover_desc_color,
                                        onChange: function onChange(value) {
                                            return setAttributes({ hover_desc_color: value === undefined ? '#fff' : value });
                                        }
                                    }]
                                })
                            )
                        )
                    ),
                    typeof cover === "undefined" && galleryId === 0 && React.createElement(
                        Placeholder,
                        {
                            icon: iconblock,
                            label: __('WP Media Folder Gallery Addon', 'wp-media-folder-gallery-addon'),
                            instructions: __('Select or create a WP Media Folder Addon image gallery', 'wp-media-folder-gallery-addon')
                        },
                        React.createElement(
                            'button',
                            { className: 'components-button is-button is-default is-primary is-large aligncenter',
                                onClick: this.openModal },
                            wpmfgalleryblocks.l18n.select_gallery_title
                        )
                    ),
                    typeof cover === "undefined" && this.state.isOpen ? React.createElement(
                        Modal,
                        {
                            className: 'wpmfGalleryModal',
                            title: wpmfgalleryblocks.l18n.gallery_title,
                            onRequestClose: this.closeModal,
                            shouldCloseOnClickOutside: false },
                        React.createElement(FocusableIframe, {
                            src: wpmfgalleryblocks.vars.admin_gallery_page + ('&idblock=' + this.props.clientId + '&gallery_id=' + galleryId + '&display=' + display + '&layout=' + layout + '&row_height=' + row_height + '&aspect_ratio=' + aspect_ratio + '&display_tree=' + display_tree + '&display_tag=' + display_tag + '&columns=' + columns + '&size=' + size + '&targetsize=' + targetsize + '&link=' + link + '&wpmf_orderby=' + wpmf_orderby + '&wpmf_order=' + wpmf_order + '&animation=' + animation + '&duration=' + duration + '&auto_animation=' + auto_animation + '&number_lines=' + number_lines + '&show_buttons=' + show_buttons + '&tree_width=' + tree_width + '&gutterwidth=' + gutterwidth + '&hover_color=' + hover_color.replace('#', '') + '&hover_opacity=' + hover_opacity + '&hover_title_position=' + hover_title_position + '&hover_title_size=' + hover_title_size + '&hover_title_color=' + hover_title_color.replace('#', '') + '&hover_desc_position=' + hover_desc_position + '&hover_desc_size=' + hover_desc_size + '&hover_desc_color=' + hover_desc_color.replace('#', ''))
                        })
                    ) : null,
                    typeof cover === "undefined" && this.state.title !== '' && React.createElement(
                        'div',
                        { className: 'wpmf_glraddon_title_block' },
                        __('Gallery title: ', 'wp-media-folder-gallery-addon') + this.state.title
                    ),
                    typeof cover === "undefined" && html !== '' && React.createElement('div', { className: 'wpmf-gallery-addon-block-preview', dangerouslySetInnerHTML: { __html: html } }),
                    typeof cover === "undefined" && html === '' && parseInt(galleryId) !== 0 && React.createElement('div', { className: 'wpmf-gallery-addon-block-preview', dangerouslySetInnerHTML: { __html: '<p class="wpmf_glraddon_block_loading">' + __('Loading...', 'wp-media-folder-gallery-addon') + '</p>' } })
                );
            }
        }]);

        return WpmfGallery;
    }(Component);

    registerBlockType('wpmf/block-gallery', {
        title: wpmfgalleryblocks.l18n.gallery_title,
        icon: iconblock,
        category: 'wp-media-folder',
        keywords: [__('gallery', 'wp-media-folder-gallery-addon'), __('file', 'wp-media-folder-gallery-addon')],
        example: {
            attributes: {
                cover: wpmfgalleryblocks.vars.block_cover
            }
        },
        attributes: {
            galleryId: {
                type: 'number',
                default: 0
            },
            display: {
                type: 'string',
                default: ''
            },
            layout: {
                type: 'string',
                default: 'vertical'
            },
            row_height: {
                type: 'number',
                default: 200
            },
            aspect_ratio: {
                type: 'string',
                default: 'default'
            },
            display_tree: {
                type: 'number',
                default: 0
            },
            display_tag: {
                type: 'number',
                default: 0
            },
            columns: {
                type: 'string',
                default: '3'
            },
            size: {
                type: 'string',
                default: 'medium'
            },
            crop_image: {
                type: 'number',
                default: 1
            },
            targetsize: {
                type: 'string',
                default: 'large'
            },
            link: {
                type: 'string',
                default: 'file'
            },
            wpmf_orderby: {
                type: 'string',
                default: 'post__in'
            },
            wpmf_order: {
                type: 'string',
                default: 'ASC'
            },
            animation: {
                type: 'string',
                default: 'slide'
            },
            duration: {
                type: 'number',
                default: 4000
            },
            auto_animation: {
                type: 'number',
                default: 1
            },
            number_lines: {
                type: 'number',
                default: 1
            },
            show_buttons: {
                type: 'number',
                default: 1
            },
            align: {
                type: 'string',
                default: 'center'
            },
            img_border_radius: {
                type: 'number',
                default: 0
            },
            borderWidth: {
                type: 'number',
                default: 1
            },
            borderColor: {
                type: 'string',
                default: 'transparent'
            },
            borderStyle: {
                type: 'string',
                default: 'none'
            },
            hoverShadowH: {
                type: 'number',
                default: 0
            },
            hoverShadowV: {
                type: 'number',
                default: 0
            },
            hoverShadowBlur: {
                type: 'number',
                default: 0
            },
            hoverShadowSpread: {
                type: 'number',
                default: 0
            },
            hoverShadowColor: {
                type: 'string',
                default: '#ccc'
            },
            gutterwidth: {
                type: 'number',
                default: 15
            },
            tree_width: {
                type: 'number',
                default: 250
            },
            theme: {
                type: 'default',
                default: ''
            },
            html: {
                type: 'string',
                default: ''
            },
            cover: {
                type: 'string',
                source: 'attribute',
                selector: 'img',
                attribute: 'src'
            },
            hover_color: {
                type: 'string',
                default: '#000'
            },
            hover_opacity: {
                type: 'number',
                default: 0.4
            },
            hover_title_position: {
                type: 'string',
                default: 'center_center'
            },
            hover_title_size: {
                type: 'number',
                default: 16
            },
            hover_title_color: {
                type: 'string',
                default: '#fff'
            },
            hover_desc_position: {
                type: 'string',
                default: 'none'
            },
            hover_desc_size: {
                type: 'number',
                default: 14
            },
            hover_desc_color: {
                type: 'string',
                default: '#fff'
            }
        },
        edit: WpmfGallery,
        save: function save(_ref) {
            var attributes = _ref.attributes;
            var galleryId = attributes.galleryId,
                display = attributes.display,
                layout = attributes.layout,
                row_height = attributes.row_height,
                aspect_ratio = attributes.aspect_ratio,
                display_tree = attributes.display_tree,
                display_tag = attributes.display_tag,
                animation = attributes.animation,
                duration = attributes.duration,
                auto_animation = attributes.auto_animation,
                crop_image = attributes.crop_image,
                show_buttons = attributes.show_buttons,
                columns = attributes.columns,
                size = attributes.size,
                targetsize = attributes.targetsize,
                link = attributes.link,
                wpmf_orderby = attributes.wpmf_orderby,
                wpmf_order = attributes.wpmf_order,
                img_border_radius = attributes.img_border_radius,
                gutterwidth = attributes.gutterwidth,
                hoverShadowH = attributes.hoverShadowH,
                hoverShadowV = attributes.hoverShadowV,
                hoverShadowBlur = attributes.hoverShadowBlur,
                hoverShadowSpread = attributes.hoverShadowSpread,
                hoverShadowColor = attributes.hoverShadowColor,
                borderWidth = attributes.borderWidth,
                borderStyle = attributes.borderStyle,
                borderColor = attributes.borderColor,
                number_lines = attributes.number_lines,
                hover_color = attributes.hover_color,
                hover_opacity = attributes.hover_opacity,
                hover_title_position = attributes.hover_title_position,
                hover_title_size = attributes.hover_title_size,
                hover_title_color = attributes.hover_title_color,
                hover_desc_position = attributes.hover_desc_position,
                hover_desc_size = attributes.hover_desc_size,
                hover_desc_color = attributes.hover_desc_color;

            var gallery_shortcode = '[wpmfgallery';
            if (layout !== 'vertical') {
                gallery_shortcode += ' layout="' + layout + '"';
            }

            if (parseInt(row_height) !== 200) {
                gallery_shortcode += ' row_height="' + row_height + '"';
            }

            if (aspect_ratio !== 'default') {
                gallery_shortcode += ' aspect_ratio="' + aspect_ratio + '"';
            }

            gallery_shortcode += ' gallery_id="' + galleryId + '"';
            gallery_shortcode += ' size="' + size + '"';
            gallery_shortcode += ' columns="' + columns + '"';
            gallery_shortcode += ' targetsize="' + targetsize + '"';
            gallery_shortcode += ' link="' + link + '"';
            gallery_shortcode += ' wpmf_orderby="' + wpmf_orderby + '"';
            gallery_shortcode += ' wpmf_order="' + wpmf_order + '"';
            gallery_shortcode += ' display_tree="' + display_tree + '"';
            gallery_shortcode += ' display_tag="' + display_tag + '"';

            if (parseInt(number_lines) !== 1) {
                gallery_shortcode += ' number_lines="' + number_lines + '"';
            }

            if (parseInt(crop_image) === 0) {
                gallery_shortcode += ' crop_image="' + crop_image + '"';
            }

            if (display !== '') {
                gallery_shortcode += ' display="' + display + '"';
            }

            if (parseInt(img_border_radius) !== 0) {
                gallery_shortcode += ' img_border_radius="' + img_border_radius + '"';
            }

            if (parseInt(gutterwidth) !== 5) {
                gallery_shortcode += ' gutterwidth="' + gutterwidth + '"';
            }

            if (typeof hoverShadowH !== "undefined" && typeof hoverShadowV !== "undefined" && typeof hoverShadowBlur !== "undefined" && typeof hoverShadowSpread !== "undefined" && (parseInt(hoverShadowH) !== 0 || parseInt(hoverShadowV) !== 0 || parseInt(hoverShadowBlur) !== 0 || parseInt(hoverShadowSpread) !== 0)) {
                gallery_shortcode += ' img_shadow="' + hoverShadowH + 'px ' + hoverShadowV + 'px ' + hoverShadowBlur + 'px ' + hoverShadowSpread + 'px ' + hoverShadowColor + '"';
            }

            if (borderStyle !== 'none') {
                gallery_shortcode += ' border_width="' + borderWidth + '"';
                gallery_shortcode += ' border_style="' + borderStyle + '"';
                gallery_shortcode += ' border_color="' + borderColor + '"';
            }

            if (animation !== 'slide') {
                gallery_shortcode += ' animation="' + animation + '"';
            }

            if (parseInt(duration) !== 4000) {
                gallery_shortcode += ' duration="' + duration + '"';
            }

            if (parseInt(auto_animation) !== 1) {
                gallery_shortcode += ' auto_animation="' + auto_animation + '"';
            }

            if (parseInt(show_buttons) !== 1) {
                gallery_shortcode += ' show_buttons="' + show_buttons + '"';
            }

            if (hover_color !== '#000') {
                gallery_shortcode += ' hover_color="' + hover_color + '"';
            }

            if (hover_opacity != 0.4) {
                gallery_shortcode += ' hover_opacity="' + hover_opacity + '"';
            }

            if (hover_title_position !== 'center_center') {
                gallery_shortcode += ' hover_title_position="' + hover_title_position + '"';
            }

            if (parseInt(hover_title_size) !== 16) {
                gallery_shortcode += ' hover_title_size="' + hover_title_size + '"';
            }

            if (hover_title_color !== '#fff') {
                gallery_shortcode += ' hover_title_color="' + hover_title_color + '"';
            }

            if (hover_desc_position !== 'none') {
                gallery_shortcode += ' hover_desc_position="' + hover_desc_position + '"';
            }

            if (parseInt(hover_desc_size) !== 14) {
                gallery_shortcode += ' hover_desc_size="' + hover_desc_size + '"';
            }

            if (hover_desc_color !== '#fff') {
                gallery_shortcode += ' hover_desc_color="' + hover_desc_color + '"';
            }
            gallery_shortcode += ']';
            return gallery_shortcode;
        },
        getEditWrapperProps: function getEditWrapperProps(attributes) {
            var align = attributes.align;

            var props = { 'data-resized': true };

            if ('left' === align || 'right' === align || 'center' === align) {
                props['data-align'] = align;
            }

            return props;
        }
    });
})(wp.i18n, wp.blocks, wp.element, wp.editor, wp.components);
