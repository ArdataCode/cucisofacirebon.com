// External Dependencies
import React, {Component} from 'react';
import $ from 'jquery';
// Internal Dependencies
import './style.css';

class WpmfGalleryDivi extends Component {

    static slug = 'wpmf_gallery_divi';

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
        if (this.props.items !== nextProps.items || this.props.columns !== nextProps.columns || this.props.theme !== nextProps.theme || this.props.size !== nextProps.size || this.props.orderby !== nextProps.orderby || this.props.order !== nextProps.order || this.props.border_radius !== nextProps.border_radius || this.props.gutterwidth !== nextProps.gutterwidth
            || this.props.border_width !== nextProps.border_width || this.props.border_color !== nextProps.border_color || this.props.border_style !== nextProps.border_style
            || this.props.enable_shadow !== nextProps.enable_shadow || this.props.shadow_color !== nextProps.shadow_color || this.props.shadow_horizontal !== nextProps.shadow_horizontal || this.props.shadow_vertical !== nextProps.shadow_vertical || this.props.shadow_blur !== nextProps.shadow_blur || this.props.shadow_spread !== nextProps.shadow_spread
            || this.props.gallery_folders !== nextProps.gallery_folders || this.props.gallery_folder_id !== nextProps.gallery_folder_id) {
            this.loadHtml(nextProps);
        }
    }

    componentDidMount() {
        if (this.props.items !== '') {
            let t = this;
            let a = setInterval(function () {
                if (t.props.theme === 'masonry' || t.props.theme === 'portfolio') {
                    if ($(t.wrap.current).find('.gallery-masonry').length) {
                        t.initMasonry();
                        clearInterval(a);
                    }
                }

                if (t.props.theme === 'slider') {
                    if ($(t.wrap.current).find('.wpmfslick').length) {
                        t.initSlider();
                        clearInterval(a);
                    }
                }
            }, 200);
        }
    }

    componentDidUpdate(prevProps) {
        // Deselect images when deselecting the block
        if (this.props.items !== '' && !(this.props.items === prevProps.items && this.props.columns === prevProps.columns && this.props.theme === prevProps.theme && this.props.size === prevProps.size && this.props.orderby === prevProps.orderby && this.props.order === prevProps.order && this.props.border_radius === prevProps.border_radius && this.props.gutterwidth === prevProps.gutterwidth && this.props.border_width === prevProps.border_width && this.props.border_color === prevProps.border_color && this.props.border_style === prevProps.border_style && this.props.enable_shadow === prevProps.enable_shadow && this.props.shadow_color === prevProps.shadow_color && this.props.shadow_horizontal === prevProps.shadow_horizontal && this.props.shadow_vertical === prevProps.shadow_vertical && this.props.shadow_blur === prevProps.shadow_blur && this.props.shadow_spread === prevProps.shadow_spread && this.props.gallery_folders === prevProps.gallery_folders && this.props.gallery_folder_id === prevProps.gallery_folder_id)) {
            let t = this;
            let a = setInterval(function () {
                if (t.props.theme === 'masonry' || t.props.theme === 'portfolio') {
                    if ($(t.wrap.current).find('.gallery-masonry').length) {
                        t.initMasonry();
                        clearInterval(a);
                    }
                }

                if (t.props.theme === 'slider') {
                    if ($(t.wrap.current).find('.wpmfslick').length) {
                        t.initSlider();
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

    initMasonry() {
        let $container = $(this.wrap.current).find('.gallery-masonry');
        if ($container.is(':hidden')) {
            return;
        }

        if ($container.hasClass('masonry')) {
            $container.masonry('destroy');
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

    /**
     * run masonry layout
     */
    initSlider() {
        let $slider_container = $(this.wrap.current).find('.wpmfslick');
        if ($slider_container.is(':hidden')) {
            return;
        }

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
                rows: 1,
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
            items: props.items,
            display: props.theme,
            columns: props.columns,
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
            wpmf_autoinsert: props.gallery_folders,
            wpmf_folder_id: props.gallery_folder_id
        };

        fetch(window.et_fb_options.ajaxurl + `?action=wpmf_divi_load_gallery_html&et_admin_load_nonce=${window.et_fb_options.et_admin_load_nonce}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(datas)
        } )
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

        if (this.props.items === '' && parseInt(this.props.gallery_folder_id) === 0) {
            return (
                <div className="wpmf-divi-container wpmf-gallery-divi-wrap" ref={this.wrap}>
                    <div id="divi-gallery-placeholder" className="divi-gallery-placeholder">
                        <span className="wpmf-divi-message">
                            {'Please add some images to the gallery to activate the preview'}
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
                <div className="wpmf-gallery-divi-wrap" ref={this.wrap} dangerouslySetInnerHTML={{__html: this.state.html}}/>
            );
        }
    }
}

export default WpmfGalleryDivi;
