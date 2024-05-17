// External Dependencies
import React, {Component} from 'react';
import $ from 'jquery';
// Internal Dependencies
import './style.css';

class WpmfGalleryAddonDivi extends Component {

    static slug = 'wpmf_gallery_addon_divi';

    constructor(props) {
        super(props);
        this.state = {
            html: '',
            loading: true
        };
        this.wrap = React.createRef();
    }

    componentWillMount() {
        if (this.props.items !== '') {
            this.loadHtml(this.props);
        }
    }

    componentWillReceiveProps(nextProps) {
        if (this.props.gallery_id !== nextProps.gallery_id || this.props.columns !== nextProps.columns || this.props.theme !== nextProps.theme || this.props.layout !== nextProps.layout || this.props.row_height !== nextProps.row_height || this.props.aspect_ratio !== nextProps.aspect_ratio || this.props.size !== nextProps.size || this.props.orderby !== nextProps.orderby || this.props.order !== nextProps.order || this.props.border_radius !== nextProps.border_radius || this.props.gutterwidth !== nextProps.gutterwidth
            || this.props.border_width !== nextProps.border_width || this.props.border_color !== nextProps.border_color || this.props.border_style !== nextProps.border_style
            || this.props.enable_shadow !== nextProps.enable_shadow || this.props.shadow_color !== nextProps.shadow_color || this.props.shadow_horizontal !== nextProps.shadow_horizontal || this.props.shadow_vertical !== nextProps.shadow_vertical || this.props.shadow_blur !== nextProps.shadow_blur || this.props.shadow_spread !== nextProps.shadow_spread
            || this.props.gallery_image_tags !== nextProps.gallery_image_tags || this.props.gallery_navigation !== nextProps.gallery_navigation || this.props.number_lines !== nextProps.number_lines
            || this.props.hover_color !== nextProps.hover_color || this.props.hover_opacity !== nextProps.hover_opacity || this.props.hover_title_position !== nextProps.hover_title_position
            || this.props.hover_title_size !== nextProps.hover_title_size || this.props.hover_title_color !== nextProps.hover_title_color || this.props.hover_desc_position !== nextProps.hover_desc_position
            || this.props.hover_desc_size !== nextProps.hover_desc_size || this.props.hover_desc_color !== nextProps.hover_desc_color) {
            this.loadHtml(nextProps);
        }
    }

    componentDidMount() {
        if (parseInt(this.props.gallery_id) !== 0) {
            let t = this;
            let a = setInterval(function () {
                $(t.wrap.current).find('.loading_gallery').hide();
                if (t.props.theme === 'masonry') {
                    if ($(t.wrap.current).find('.gallery-masonry').length) {
                        t.initMasonry();
                        clearInterval(a);
                    }
                }

                if (t.props.theme === 'custom_grid') {
                    if ($(t.wrap.current).find('.wpmf-custom-grid').length) {
                        t.initCustomGrid();
                        clearInterval(a);
                    }
                }

                if (t.props.theme === 'slider') {
                    if ($(t.wrap.current).find('.wpmfslick').length) {
                        t.initSlider();
                        clearInterval(a);
                    }
                }

                if (t.props.theme === 'flowslide') {
                    if ($(t.wrap.current).find('.wpmf-flipster').length) {
                        t.initFlowsSlide();
                        clearInterval(a);
                    }
                }
            }, 200);
        }
    }

    componentDidUpdate(prevProps) {
        // Deselect images when deselecting the block
        if (parseInt(this.props.gallery_id) !== 0 && !(this.props.gallery_id === prevProps.gallery_id && this.props.columns === prevProps.columns && this.props.theme === prevProps.theme && this.props.layout === prevProps.layout && this.props.row_height === prevProps.row_height && this.props.aspect_ratio === prevProps.aspect_ratio && this.props.size === prevProps.size && this.props.orderby === prevProps.orderby && this.props.order === prevProps.order && this.props.border_radius === prevProps.border_radius && this.props.gutterwidth === prevProps.gutterwidth && this.props.border_width === prevProps.border_width && this.props.border_color === prevProps.border_color && this.props.border_style === prevProps.border_style && this.props.enable_shadow === prevProps.enable_shadow && this.props.shadow_color === prevProps.shadow_color && this.props.shadow_horizontal === prevProps.shadow_horizontal && this.props.shadow_vertical === prevProps.shadow_vertical && this.props.shadow_blur === prevProps.shadow_blur && this.props.shadow_spread === prevProps.shadow_spread && this.props.gallery_image_tags === prevProps.gallery_image_tags && this.props.gallery_navigation === prevProps.gallery_navigation && this.props.number_lines === prevProps.number_lines && this.props.hover_color === prevProps.hover_color && this.props.hover_opacity === prevProps.hover_opacity && this.props.hover_title_position === prevProps.hover_title_position && this.props.hover_title_size === prevProps.hover_title_size && this.props.hover_title_color === prevProps.hover_title_color && this.props.hover_desc_position === prevProps.hover_desc_position && this.props.hover_desc_size === prevProps.hover_desc_size && this.props.hover_desc_color === prevProps.hover_desc_color)) {
            let t = this;
            let a = setInterval(function () {
                $(t.wrap.current).find('.loading_gallery').hide();
                if (t.props.theme === 'masonry') {
                    if ($(t.wrap.current).find('.gallery-masonry').length) {
                        t.initMasonry();
                        clearInterval(a);
                    }
                }

                if (t.props.theme === 'custom_grid') {
                    if ($(t.wrap.current).find('.wpmf-custom-grid').length) {
                        t.initCustomGrid();
                        clearInterval(a);
                    }
                }

                if (t.props.theme === 'slider') {
                    if ($(t.wrap.current).find('.wpmfslick').length) {
                        t.initSlider();
                        clearInterval(a);
                    }
                }

                if (t.props.theme === 'flowslide') {
                    if ($(t.wrap.current).find('.wpmf-flipster').length) {
                        t.initFlowsSlide();
                        clearInterval(a);
                    }
                }
            }, 200);
        }
    }

    calculateGrid($container) {
        let columns = parseInt($container.data('wpmfcolumns'));
        let gutterWidth = $container.data('gutterWidth');
        let containerWidth = $container.width();

        if (isNaN(gutterWidth)) {
            gutterWidth = 5;
        } else if (gutterWidth > 500 || gutterWidth < 0) {
            gutterWidth = 5;
        }

        if (parseInt(columns) < 2 || containerWidth <= 450) {
            columns = 2;
        }

        gutterWidth = parseInt(gutterWidth);

        let allGutters = gutterWidth * (columns - 1);
        let contentWidth = containerWidth - allGutters;

        let columnWidth = Math.floor(contentWidth / columns);

        return {columnWidth: columnWidth, gutterWidth: gutterWidth, columns: columns};
    };

    initFlowsSlide() {
        let $container = $(this.wrap.current).find('.wpmf-flipster');
        let enableNavButtons = $container.data('button');
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
    }

    initMasonry() {
        let $container = $(this.wrap.current).find('.gallery-masonry');
        let layout = $container.closest('.wpmf-gallerys-addon').data('layout');
        let padding = $container.data('gutter-width');
        if ($container.is(':hidden')) {
            return;
        }

        if ($container.hasClass('masonry')) {
            $container.masonry('destroy');
        }

        if ($container.hasClass('justified-gallery')) {
            $container.justifiedGallery('destroy');
        }

        if (layout === 'horizontal') {
            var row_height = $container.closest('.wpmf-gallerys-addon').data('row_height');
            if (typeof row_height === "undefined" || row_height === '') {
                row_height = 200;
            }

            $container.imagesLoaded(function () {
                setTimeout(function () {
                    $container.justifiedGallery({
                        rowHeight: row_height,
                        margins: padding
                    });
                },200);
            });
            return;
        }

        var t = this;
        $container.imagesLoaded(function () {
            var $postBox = $container.children('.wpmf-gallery-item');
            var o = t.calculateGrid($container);
            $postBox.css({'width': o.columnWidth + 'px', 'margin-bottom': o.gutterWidth + 'px'});
            $container.masonry({
                itemSelector: '.wpmf-gallery-item',
                columnWidth: o.columnWidth,
                gutter: o.gutterWidth,
                isFitWidth: true,
                transitionDuration: 0,
            });
            $container.css('visibility', 'visible');
        });
    }

    initCustomGrid() {
        let $container = $(this.wrap.current).find('.wpmf-custom-grid');
        if ($container.is(':hidden')) {
            return;
        }

        if ($container.hasClass('masonry')) {
            $container.masonry('destroy');
        }

        if ($container.hasClass('justified-gallery')) {
            $container.justifiedGallery('destroy');
        }

        if ($container.hasClass('wpmfInitPackery')) {
            $container.isotope('destroy');
        }

        $container.imagesLoaded(function () {
            var gutter = $container.data('gutter');
            $container.closest('.wpmf_gallery_wrap').find('.loading_gallery').hide();
            var wrap_width = $container.width();
            var one_col_width = (wrap_width - gutter*12)/12;
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
        });
    }

    /**
     * run masonry layout
     */
    initSlider() {
        let $slider_container = $(this.wrap.current).find('.wpmfslick');
        if ($slider_container.is(':hidden')) {
            return;
        }

        let number_lines = parseInt($slider_container.data('number_lines'));
        let columns = parseInt($slider_container.data('wpmfcolumns'));
        let autoplay = $slider_container.data('auto_animation');
        if ($slider_container.hasClass('slick-initialized')) {
            $slider_container.slick('destroy');
        }

        $slider_container.imagesLoaded(function () {
            var slick_args = {
                infinite: true,
                slidesToShow: parseInt(columns),
                slidesToScroll: parseInt(columns),
                pauseOnHover: true,
                autoplay: (autoplay === 1),
                adaptiveHeight: (parseInt(columns) === 1),
                autoplaySpeed: 5000,
                rows: number_lines,
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

            $slider_container.slick(slick_args);
        });
    }

    loadHtml(props) {
        if (!this.state.loading) {
            this.setState({
                loading: true
            });
        }
        let img_shadow = '';
        if (props.enable_shadow === 'on') {
            img_shadow = props.shadow_horizontal + ' ' + props.shadow_vertical + ' ' + props.shadow_blur + ' ' + props.shadow_spread + ' ' + props.shadow_color;
        }
        let datas = {
            gallery_id: props.gallery_id,
            display: props.theme,
            layout: props.layout,
            row_height: props.row_height,
            aspect_ratio: props.aspect_ratio,
            columns: props.columns,
            number_lines: props.number_lines,
            size: props.size,
            targetsize: props.targetsize,
            link: props.action,
            orderby: props.orderby,
            order: props.order,
            gutterwidth: props.gutterwidth,
            border_width: props.border_width,
            border_style: props.border_style,
            border_color: props.border_color,
            img_shadow: img_shadow,
            border_radius: props.border_radius,
            display_tree: props.gallery_navigation,
            display_tag: props.gallery_image_tags,
            hover_color: props.hover_color,
            hover_opacity: props.hover_opacity,
            hover_title_position: props.hover_title_position,
            hover_title_size: props.hover_title_size,
            hover_title_color: props.hover_title_color,
            hover_desc_position: props.hover_desc_position,
            hover_desc_size: props.hover_desc_size,
            hover_desc_color: props.hover_desc_color
        };

        fetch(window.et_fb_options.ajaxurl + `?action=wpmf_divi_load_gallery_addon_html&et_admin_load_nonce=${window.et_fb_options.et_admin_load_nonce}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(datas)
        })
            .then(res => res.json())
            .then(
                (result) => {
                    this.setState({
                        html: result.html,
                        loading: false
                    });
                },
                // errors
                (error) => {
                    this.setState({
                        html: '',
                        loading: true
                    });
                }
            );
    }

    render() {
        const loadingIcon = (
            <svg className={'wpfd-loading'} width="100%" height="100%" xmlns="http://www.w3.org/2000/svg"
                 viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">
                <g transform="translate(25 50)">
                    <circle cx="0" cy="0" r="10" fill="#cfcfcf" transform="scale(0.590851 0.590851)">
                        <animateTransform attributeName="transform" type="scale" begin="-0.8666666666666667s" calcMode="spline"
                                          keySplines="0.3 0 0.7 1;0.3 0 0.7 1" values="0.5;1;0.5" keyTimes="0;0.5;1" dur="2.6s"
                                          repeatCount="indefinite"/>
                    </circle>
                </g>
                <g transform="translate(50 50)">
                    <circle cx="0" cy="0" r="10" fill="#cfcfcf" transform="scale(0.145187 0.145187)">
                        <animateTransform attributeName="transform" type="scale" begin="-0.43333333333333335s" calcMode="spline"
                                          keySplines="0.3 0 0.7 1;0.3 0 0.7 1" values="0.5;1;0.5" keyTimes="0;0.5;1" dur="2.6s"
                                          repeatCount="indefinite"/>
                    </circle>
                </g>
                <g transform="translate(75 50)">
                    <circle cx="0" cy="0" r="10" fill="#cfcfcf" transform="scale(0.0339143 0.0339143)">
                        <animateTransform attributeName="transform" type="scale" begin="0s" calcMode="spline"
                                          keySplines="0.3 0 0.7 1;0.3 0 0.7 1" values="0.5;1;0.5" keyTimes="0;0.5;1" dur="2.6s"
                                          repeatCount="indefinite"/>
                    </circle>
                </g>
            </svg>
        );

        if (parseInt(this.props.gallery_id) === 0) {
            return (
                <div className="wpmf-divi-container wpmf-gallery-divi-wrap" ref={this.wrap}>
                    <div id="divi-gallery-addon-placeholder" className="divi-gallery-addon-placeholder">
                        <span className="wpmf-divi-message">
                            {'Please select a gallery to activate the preview'}
                        </span>
                    </div>
                </div>
            );
        }

        if (this.state.loading) {
            return (
                <div className="wpmf-divi-container wpmf-gallery-divi-wrap" ref={this.wrap}>
                    <div className={'wpmf-loading-wrapper'}>
                        <i className={'wpmf-loading'}>{loadingIcon}</i>
                    </div>
                </div>
            );
        }

        if (!this.state.loading) {
            return (
                <div className="wpmf-gallery-divi-wrap" ref={this.wrap}
                     dangerouslySetInnerHTML={{__html: this.state.html}}/>
            );
        }
    }
}

export default WpmfGalleryAddonDivi;
