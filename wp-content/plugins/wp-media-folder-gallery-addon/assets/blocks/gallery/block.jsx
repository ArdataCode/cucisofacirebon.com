(function (wpI18n, wpBlocks, wpElement, wpEditor, wpComponents) {
    const {__} = wpI18n;
    const {Component, Fragment} = wpElement;
    const {registerBlockType} = wpBlocks;
    const {BlockControls, BlockAlignmentToolbar, InspectorControls, PanelColorSettings} = wpEditor;
    const {PanelBody, Modal, FocusableIframe, IconButton, Toolbar, SelectControl, ToggleControl, RangeControl, Placeholder} = wpComponents;
    const $ = jQuery;
    const el = wpElement.createElement;
    const iconblock = el('svg', {width: 24, height: 24},
        el('path', {d: "M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-7 2v5l-1-.75L9 9V4h2zm7 16H6V4h1v9l3-2.25L13 13V4h5v16zm-6.72-2.04L9.5 15.81 7 19h10l-3.22-4.26z"})
    );
    let save_params = {};
    class WpmfGallery extends Component {
        constructor() {
            super(...arguments);
            this.state = {
                isOpen: false,
                title: '',
                gallery_items: []
            };

            this.openModal = this.openModal.bind(this);
            this.closeModal = this.closeModal.bind(this);
            this.addEventListener = this.addEventListener.bind(this);
            this.componentDidMount = this.componentDidMount.bind(this);
        }

        componentWillMount() {
            const {attributes} = this.props;
            this.initLoadTheme();
        }

        componentDidMount() {
            window.addEventListener("message", this.addEventListener, false);
        }

        componentDidUpdate(prevProps) {
            const {attributes} = this.props;
            if ((attributes.html !== '' && (prevProps.attributes.display_tree !== attributes.display_tree
                || prevProps.attributes.display_tag !== attributes.display_tag
                || prevProps.attributes.show_buttons !== attributes.show_buttons
                || prevProps.attributes.display !== attributes.display
                || prevProps.attributes.layout !== attributes.layout
                || prevProps.attributes.row_height !== attributes.row_height
                || prevProps.attributes.aspect_ratio !== attributes.aspect_ratio
                || prevProps.attributes.img_border_radius !== attributes.img_border_radius
                || prevProps.attributes.borderWidth !== attributes.borderWidth
                || prevProps.attributes.borderColor !== attributes.borderColor
                || prevProps.attributes.borderStyle !== attributes.borderStyle
                || prevProps.attributes.gutterwidth !== attributes.gutterwidth
                || prevProps.attributes.hoverShadowH !== attributes.hoverShadowH
                || prevProps.attributes.hoverShadowV !== attributes.hoverShadowV
                || prevProps.attributes.hoverShadowBlur !== attributes.hoverShadowBlur
                || prevProps.attributes.hoverShadowSpread !== attributes.hoverShadowSpread
                || prevProps.attributes.hoverShadowColor !== attributes.hoverShadowColor
                || prevProps.attributes.size !== attributes.size
                || prevProps.attributes.crop_image !== attributes.crop_image
                || prevProps.attributes.columns !== attributes.columns
                || prevProps.attributes.wpmf_orderby !== attributes.wpmf_orderby
                || prevProps.attributes.number_lines !== attributes.number_lines
                || prevProps.attributes.hover_color !== attributes.hover_color
                || prevProps.attributes.hover_opacity !== attributes.hover_opacity
                || prevProps.attributes.hover_title_position !== attributes.hover_title_position
                || prevProps.attributes.hover_title_size !== attributes.hover_title_size
                || prevProps.attributes.hover_title_color !== attributes.hover_title_color
                || prevProps.attributes.hover_desc_position !== attributes.hover_desc_position
                || prevProps.attributes.hover_desc_size !== attributes.hover_desc_size
                || prevProps.attributes.hover_desc_color !== attributes.hover_desc_color
                || prevProps.attributes.wpmf_order !== attributes.wpmf_order)) || prevProps.attributes.galleryId !== attributes.galleryId) {
                this.initLoadTheme();
            }

            if (attributes.html !== '') {
                this.initTheme();
            }
        }

        getTree(categories, trees, parent) {
            let ij = 0;
            while (ij < categories.length) {
                if (categories[ij].parent === parent) {
                    trees.push(categories[ij]);
                    this.getTree(categories, trees, categories[ij].term_id);
                }
                ij++;
            }
            return trees;
        }

        initLoadTheme() {
            const {attributes, setAttributes, clientId} = this.props;
            const {
                galleryId,
                display,
                layout,
                row_height,
                aspect_ratio,
                display_tree,
                display_tag,
                columns,
                size,
                crop_image,
                targetsize,
                link,
                wpmf_orderby,
                wpmf_order,
                animation,
                duration,
                auto_animation,
                number_lines,
                show_buttons,
                gutterwidth,
                img_border_radius,
                borderWidth,
                borderColor,
                borderStyle,
                hoverShadowH,
                hoverShadowV,
                hoverShadowBlur,
                hoverShadowSpread,
                hoverShadowColor,
                hover_color,
                hover_opacity,
                hover_title_position,
                hover_title_size,
                hover_title_color,
                hover_desc_position,
                hover_desc_size,
                hover_desc_color
            } = attributes;

            if (parseInt(galleryId) !== 0) {
                let params = {
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

                $(`#block-${clientId} .wpmf-gallery-addon-block-preview`).html(`<p style="text-align: center">${wpmfgalleryblocks.l18n.loading}</p>`);
                fetch(encodeURI(wpmfgalleryblocks.vars.ajaxurl + `?action=wpmf_load_gallery_html&datas=${JSON.stringify(params)}&wpmf_gallery_nonce=${wpmfgalleryblocks.vars.wpmf_gallery_nonce}`))
                    .then(res => res.json())
                    .then(
                        (result) => {
                            if (result.status) {
                                setAttributes({
                                    html: result.html,
                                    theme: result.theme
                                });
                            }
                        },
                        // errors
                        (error) => {
                        }
                    );
            }
        }

        initTheme() {
            const {attributes, clientId} = this.props;
            const {theme, columns, display_tree, layout, row_height, gutterwidth} = attributes;
            let $container = $(`#block-${clientId} .wpmf-gallery-addon-block-preview`);
            imagesLoaded($container, function () {
                if (theme === 'slider') {
                    let $slider_container = $(`#block-${clientId} .wpmfslick`);
                    let animation = $slider_container.data('animation');
                    let duration = parseInt($slider_container.data('duration'));
                    let auto_animation = parseInt($slider_container.data('auto_animation'));
                    let number_lines = parseInt($slider_container.data('number_lines'));
                    if ($slider_container.is(':hidden')) {
                        return;
                    }

                    var slick_args = {
                        infinite: true,
                        slidesToShow: parseInt(columns),
                        slidesToScroll: parseInt(columns),
                        pauseOnHover: true,
                        autoplay: (auto_animation === 1),
                        adaptiveHeight: (parseInt(columns) === 1),
                        autoplaySpeed: duration,
                        rows: number_lines,
                        fade: (animation === 'fade'),
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

                    if (!$slider_container.hasClass('slick-initialized')) {
                        $slider_container.slick(slick_args);
                    }
                }

                if (theme === 'flowslide') {
                    let $flow_container = $(`#block-${clientId} .flipster`);
                    let enableNavButtons = $flow_container.data('button');
                    if (typeof enableNavButtons !== "undefined" && parseInt(enableNavButtons) === 1) {
                        $flow_container.flipster({
                            style: 'coverflow',
                            buttons: 'custom',
                            spacing: 0,
                            loop: true,
                            autoplay: 5000,
                            buttonNext: '<i class="flipto-next material-icons"> keyboard_arrow_right </i>',
                            buttonPrev: '<i class="flipto-prev material-icons"> keyboard_arrow_left </i>',
                            onItemSwitch: function (currentItem, previousItem) {
                                $flow_container.find('.flipster__container').height($(currentItem).height());
                            },
                            onItemStart: function (currentItem) {
                                $flow_container.find('.flipster__container').height($(currentItem).height());
                            }
                        });
                    } else {
                        $flow_container.flipster({
                            style: 'coverflow',
                            spacing: 0,
                            loop: true,
                            autoplay: 5000,
                            onItemSwitch: function (currentItem, previousItem) {
                                $flow_container.find('.flipster__container').height($(currentItem).height());
                            },
                            onItemStart: function (currentItem) {
                                $flow_container.find('.flipster__container').height($(currentItem).height());
                            }
                        });
                    }
                }

                if (theme === 'masonry') {
                    let $masonry = $container.find('.gallery-masonry');
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
                    let $custom_grid = $container.find('.wpmf-custom-grid');
                    if ($custom_grid.hasClass('wpmfInitPackery')) {
                        return;
                    }

                    let wrap_width = $custom_grid.width();
                    let one_col_width = (wrap_width - gutterwidth*12)/12;
                    $custom_grid.find('.grid-item').each(function() {
                        let dimensions = $(this).data('styles');
                        let w = (typeof dimensions.width !== "undefined") ? parseInt(dimensions.width) : 2;
                        let h = (typeof dimensions.height !== "undefined") ? parseInt(dimensions.height) : 2;
                        let g = (parseInt(w) - 1)*gutterwidth;
                        let display_width = one_col_width;
                        let display_height = one_col_width;

                        if (w > 1) {
                            display_width = one_col_width*w + g;
                        }

                        if (w == h) {
                            display_height = display_width;
                        } else {
                            if (h > 1) {
                                display_height = (one_col_width*h) + (h - 1)*gutterwidth;
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

        openModal() {
            if (!this.state.isOpen) {
                this.setState({isOpen: true});
            }
        }

        closeModal() {
            if (this.state.isOpen) {
                this.setState({isOpen: false});
                const {attributes, setAttributes} = this.props;
                const {
                    galleryId,
                    display,
                    layout,
                    row_height,
                    aspect_ratio,
                    display_tree,
                    display_tag,
                    columns,
                    size,
                    targetsize,
                    link,
                    wpmf_orderby,
                    wpmf_order,
                    animation,
                    duration,
                    auto_animation,
                    number_lines,
                    show_buttons,
                } = attributes;

                if (typeof save_params.galleryId === 'undefined') {
                    return;
                }

                if (parseInt(save_params.galleryId) !== parseInt(galleryId)
                    || save_params.display !== display
                    || save_params.layout !== layout
                    || parseInt(save_params.row_height) !== parseInt(row_height)
                    || save_params.aspect_ratio !== aspect_ratio
                    || save_params.display_tree !== display_tree
                    || save_params.display_tag !== display_tag
                    || save_params.columns !== columns
                    || save_params.link !== link
                    || save_params.wpmf_order !== wpmf_order
                    || save_params.wpmf_orderby !== wpmf_orderby
                    || save_params.size !== size
                    || save_params.targetsize !== targetsize
                    || save_params.animation !== animation
                    || parseInt(save_params.duration) !== parseInt(duration)
                    || parseInt(save_params.auto_animation) !== parseInt(auto_animation)
                    || parseInt(save_params.number_lines) !== parseInt(number_lines)
                    || parseInt(save_params.show_buttons) !== parseInt(show_buttons)
                ) {
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

        addEventListener(e) {
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

        render() {
            const listBorderStyles = [
                {label: __('None', 'wp-media-folder-gallery-addon'), value: 'none'},
                {label: __('Solid', 'wp-media-folder-gallery-addon'), value: 'solid'},
                {label: __('Dotted', 'wp-media-folder-gallery-addon'), value: 'dotted'},
                {label: __('Dashed', 'wp-media-folder-gallery-addon'), value: 'dashed'},
                {label: __('Double', 'wp-media-folder-gallery-addon'), value: 'double'},
                {label: __('Groove', 'wp-media-folder-gallery-addon'), value: 'groove'},
                {label: __('Ridge', 'wp-media-folder-gallery-addon'), value: 'ridge'},
                {label: __('Inset', 'wp-media-folder-gallery-addon'), value: 'inset'},
                {label: __('Outset', 'wp-media-folder-gallery-addon'), value: 'outset'},
            ];
            
            const {attributes, setAttributes} = this.props;
            const {
                align,
                galleryId,
                display,
                display_tree,
                display_tag,
                layout,
                row_height,
                aspect_ratio,
                columns,
                size,
                crop_image,
                targetsize,
                link,
                wpmf_orderby,
                wpmf_order,
                img_border_radius,
                borderWidth,
                borderStyle,
                borderColor,
                hoverShadowH,
                hoverShadowV,
                hoverShadowBlur,
                hoverShadowSpread,
                hoverShadowColor,
                gutterwidth,
                animation,
                duration,
                auto_animation,
                number_lines,
                show_buttons,
                html,
                tree_width,
                cover,
                hover_color,
                hover_opacity,
                hover_title_position,
                hover_title_size,
                hover_title_color,
                hover_desc_position,
                hover_desc_size,
                hover_desc_color
            } = attributes;

            const list_sizes = Object.keys(wpmfgalleryblocks.vars.sizes).map((key, label) => {
                return {
                    label: wpmfgalleryblocks.vars.sizes[key],
                    value: key
                }
            });

            return (
                <Fragment>
                    {
                        typeof cover !== "undefined" && <div className="wpmf-cover"><img src={cover} /></div>
                    }

                    {typeof cover === "undefined" && galleryId !== 0 && (
                        <Fragment>
                            <BlockControls>
                                <Toolbar>
                                    <BlockAlignmentToolbar value={align}
                                                           onChange={(align) => setAttributes({align: align})}/>
                                    <IconButton
                                        className="components-toolbar__control"
                                        label={wpmfgalleryblocks.l18n.edit}
                                        icon={'edit'}
                                        onClick={() => this.setState({isOpen: true})}
                                    />

                                    <IconButton
                                        className="components-toolbar__control"
                                        label={wpmfgalleryblocks.l18n.remove}
                                        icon={'no'}
                                        onClick={() => setAttributes({galleryId: 0})}
                                    />

                                    <IconButton
                                        className="components-toolbar__control"
                                        label={__('Refresh', 'wp-media-folder-gallery-addon')}
                                        icon="update"
                                        onClick={() => this.initLoadTheme()}
                                    />
                                </Toolbar>
                            </BlockControls>
                            <InspectorControls>
                                <PanelBody title={__('Gallery Settings', 'wp-media-folder-gallery-addon')}>
                                    <ToggleControl
                                        label={__('Gallery navigation', 'wp-media-folder-gallery-addon')}
                                        checked={display_tree}
                                        onChange={() => setAttributes({display_tree: (display_tree === 1) ? 0 : 1})}
                                    />

                                    <ToggleControl
                                        label={__('Display images tags', 'wp-media-folder-gallery-addon')}
                                        checked={display_tag}
                                        onChange={() => setAttributes({display_tag: (display_tag === 1) ? 0 : 1})}
                                    />

                                    <SelectControl
                                        label={__('Theme', 'wp-media-folder-gallery-addon')}
                                        value={display}
                                        options={[
                                            {label: __('Use theme setting', 'wp-media-folder-gallery-addon'), value: ''},
                                            {label: __('Default', 'wp-media-folder-gallery-addon'), value: 'default'},
                                            {label: __('Masonry', 'wp-media-folder-gallery-addon'), value: 'masonry'},
                                            {label: __('Portfolio', 'wp-media-folder-gallery-addon'), value: 'portfolio'},
                                            {label: __('Slider', 'wp-media-folder-gallery-addon'), value: 'slider'},
                                            {label: __('Flow slide', 'wp-media-folder-gallery-addon'), value: 'flowslide'},
                                            {label: __('Square grid', 'wp-media-folder-gallery-addon'), value: 'square_grid'},
                                            {label: __('Material', 'wp-media-folder-gallery-addon'), value: 'material'},
                                            {label: __('Custom grid', 'wp-media-folder-gallery-addon'), value: 'custom_grid'}
                                        ]}
                                        onChange={(value) => setAttributes({display: value})}
                                    />

                                    {
                                        (display === 'masonry') &&
                                        <SelectControl
                                            label={__('Layout', 'wp-media-folder-gallery-addon')}
                                            value={layout}
                                            options={[
                                                {label: __('Vertical', 'wp-media-folder-gallery-addon'), value: 'vertical'},
                                                {label: __('Horizontal', 'wp-media-folder-gallery-addon'), value: 'horizontal'},
                                            ]}
                                            onChange={(value) => setAttributes({layout: value})}
                                        />
                                    }

                                    {
                                        (display === 'masonry' && layout === 'horizontal') &&
                                        <RangeControl
                                            label={__('Row height', 'wp-media-folder-gallery-addon')}
                                            value={parseInt(row_height)}
                                            onChange={(value) => setAttributes({row_height: parseInt(value)})}
                                            min={50}
                                            max={500}
                                            step={1}
                                        />
                                    }

                                    {
                                        (display === 'slider' || display === 'portfolio' || display === 'default' || display === 'material' || display === 'square_grid') &&
                                        <SelectControl
                                            label={__('Aspect ratio', 'wp-media-folder-gallery-addon')}
                                            value={aspect_ratio}
                                            options={[
                                                {label: 'Default', value: 'default'},
                                                {label: '1:1', value: '1_1'},
                                                {label: '3:2', value: '3_2'},
                                                {label: '2:3', value: '2_3'},
                                                {label: '4:3', value: '4_3'},
                                                {label: '3:4', value: '3_4'},
                                                {label: '16:9', value: '16_9'},
                                                {label: '9:16', value: '9_16'},
                                                {label: '21:9', value: '21_9'},
                                                {label: '9:21', value: '9_21'}
                                            ]}
                                            onChange={(value) => setAttributes({aspect_ratio: value})}
                                        />
                                    }

                                    {
                                        (display !== 'flow_slide' && display !== 'custom_grid' && (layout === 'vertical' || display !== 'masonry')) &&
                                    <SelectControl
                                        label={__('Columns', 'wp-media-folder-gallery-addon')}
                                        value={columns}
                                        options={[
                                            {label: 1, value: '1'},
                                            {label: 2, value: '2'},
                                            {label: 3, value: '3'},
                                            {label: 4, value: '4'},
                                            {label: 5, value: '5'},
                                            {label: 6, value: '6'},
                                            {label: 7, value: '7'},
                                            {label: 8, value: '8'},
                                            {label: 9, value: '9'},
                                        ]}
                                        onChange={(value) => setAttributes({columns: value})}
                                    />
                                    }

                                    <SelectControl
                                        label={__('Gallery image size', 'wp-media-folder-gallery-addon')}
                                        value={size}
                                        options={list_sizes}
                                        onChange={(value) => setAttributes({size: value})}
                                    />

                                    {
                                        display === 'slider' && <ToggleControl
                                            label={wpmf.l18n.crop_image}
                                            checked={crop_image}
                                            onChange={() => setAttributes({crop_image: (crop_image === 1) ? 0 : 1})}
                                        />
                                    }

                                    <SelectControl
                                        label={__('Lightbox size', 'wp-media-folder-gallery-addon')}
                                        value={targetsize}
                                        options={list_sizes}
                                        onChange={(value) => setAttributes({targetsize: value})}
                                    />

                                    <SelectControl
                                        label={__('Action on click', 'wp-media-folder-gallery-addon')}
                                        value={link}
                                        options={[
                                            {label: __('Lightbox', 'wp-media-folder-gallery-addon'), value: 'file'},
                                            {label: __('Attachment Page', 'wp-media-folder-gallery-addon'), value: 'post'},
                                            {label: __('None', 'wp-media-folder-gallery-addon'), value: 'none'},
                                        ]}
                                        onChange={(value) => setAttributes({link: value})}
                                    />

                                    <SelectControl
                                        label={__('Order by', 'wp-media-folder-gallery-addon')}
                                        value={wpmf_orderby}
                                        options={[
                                            {label: __('Custom', 'wp-media-folder-gallery-addon'), value: 'post__in'},
                                            {label: __('Random', 'wp-media-folder-gallery-addon'), value: 'rand'},
                                            {label: __('Title', 'wp-media-folder-gallery-addon'), value: 'title'},
                                            {label: __('Date', 'wp-media-folder-gallery-addon'), value: 'date'}
                                        ]}
                                        onChange={(value) => setAttributes({wpmf_orderby: value})}
                                    />

                                    <SelectControl
                                        label={__('Order', 'wp-media-folder-gallery-addon')}
                                        value={wpmf_order}
                                        options={[
                                            {label: __('Ascending', 'wp-media-folder-gallery-addon'), value: 'ASC'},
                                            {label: __('Descending', 'wp-media-folder-gallery-addon'), value: 'DESC'}
                                        ]}
                                        onChange={(value) => setAttributes({wpmf_order: value})}
                                    />

                                    {
                                        (display === 'slider') &&
                                        <Fragment>
                                            <SelectControl
                                                label={__('Transition Type', 'wp-media-folder-gallery-addon')}
                                                value={animation}
                                                options={[
                                                    {label: __('Slide', 'wp-media-folder-gallery-addon'), value: 'slide'},
                                                    {label: __('Fade', 'wp-media-folder-gallery-addon'), value: 'fade'}
                                                ]}
                                                onChange={(value) => setAttributes({animation: value})}
                                            />

                                            <RangeControl
                                                label={__('Transition Duration (ms)', 'wp-media-folder-gallery-addon')}
                                                value={duration || 0}
                                                onChange={(value) => setAttributes({duration: value})}
                                                min={0}
                                                max={10000}
                                                step={1000}
                                            />

                                            <ToggleControl
                                                label={__('Automatic Animation', 'wp-media-folder-gallery-addon')}
                                                checked={auto_animation}
                                                onChange={() => setAttributes({auto_animation: (auto_animation === 1) ? 0 : 1})}
                                            />

                                            <SelectControl
                                                label={__('Number Lines', 'wp-media-folder-gallery-addon')}
                                                value={number_lines}
                                                options={[
                                                    {label: 1, value: 1},
                                                    {label: 2, value: 2},
                                                    {label: 3, value: 3}
                                                ]}
                                                onChange={(value) => setAttributes({number_lines: parseInt(value)})}
                                            />
                                        </Fragment>
                                    }

                                    {
                                        (display === 'flowslide') &&
                                        <Fragment>
                                            <ToggleControl
                                                label={__('Show Buttons', 'wp-media-folder-gallery-addon')}
                                                checked={show_buttons}
                                                onChange={() => setAttributes({show_buttons: (show_buttons === 1) ? 0 : 1})}
                                            />
                                        </Fragment>
                                    }
                                </PanelBody>

                                <PanelBody title={__('Border', 'wp-media-folder-gallery-addon')} initialOpen={false}>
                                    <RangeControl
                                        label={__('Border radius', 'wp-media-folder-gallery-addon')}
                                        aria-label={__('Add rounded corners to the gallery items.', 'wp-media-folder-gallery-addon')}
                                        value={img_border_radius}
                                        onChange={(value) => setAttributes({img_border_radius: value})}
                                        min={0}
                                        max={20}
                                        step={1}
                                    />
                                    <SelectControl
                                        label={__('Border style', 'wp-media-folder-gallery-addon')}
                                        value={borderStyle}
                                        options={listBorderStyles}
                                        onChange={(value) => setAttributes({borderStyle: value})}
                                    />
                                    {borderStyle !== 'none' && (
                                        <Fragment>
                                            <PanelColorSettings
                                                title={__('Border Color', 'wp-media-folder-gallery-addon')}
                                                initialOpen={false}
                                                colorSettings={[
                                                    {
                                                        label: __('Border Color', 'wp-media-folder-gallery-addon'),
                                                        value: borderColor,
                                                        onChange: (value) => setAttributes({borderColor: value === undefined ? '#2196f3' : value}),
                                                    },
                                                ]}
                                            />
                                            <RangeControl
                                                label={__('Border width', 'wp-media-folder-gallery-addon')}
                                                value={borderWidth || 0}
                                                onChange={(value) => setAttributes({borderWidth: value})}
                                                min={0}
                                                max={10}
                                            />
                                        </Fragment>
                                    )}
                                </PanelBody>
                                <PanelBody title={__('Margin', 'wp-media-folder-gallery-addon')} initialOpen={false}>
                                    <RangeControl
                                        label={__('Gutter', 'wp-media-folder-gallery-addon')}
                                        value={gutterwidth}
                                        onChange={(value) => setAttributes({gutterwidth: value})}
                                        min={0}
                                        max={100}
                                        step={5}
                                    />
                                </PanelBody>
                                <PanelBody title={__('Shadow', 'wp-media-folder-gallery-addon')} initialOpen={false}>
                                    <RangeControl
                                        label={__('Shadow H offset', 'wp-media-folder-gallery-addon')}
                                        value={hoverShadowH || 0}
                                        onChange={(value) => setAttributes({hoverShadowH: value})}
                                        min={-50}
                                        max={50}
                                    />
                                    <RangeControl
                                        label={__('Shadow V offset', 'wp-media-folder-gallery-addon')}
                                        value={hoverShadowV || 0}
                                        onChange={(value) => setAttributes({hoverShadowV: value})}
                                        min={-50}
                                        max={50}
                                    />
                                    <RangeControl
                                        label={__('Shadow blur', 'wp-media-folder-gallery-addon')}
                                        value={hoverShadowBlur || 0}
                                        onChange={(value) => setAttributes({hoverShadowBlur: value})}
                                        min={0}
                                        max={50}
                                    />
                                    <RangeControl
                                        label={__('Shadow spread', 'wp-media-folder-gallery-addon')}
                                        value={hoverShadowSpread || 0}
                                        onChange={(value) => setAttributes({hoverShadowSpread: value})}
                                        min={0}
                                        max={50}
                                    />

                                    <PanelColorSettings
                                        title={__('Color Settings', 'wp-media-folder-gallery-addon')}
                                        initialOpen={false}
                                        colorSettings={[
                                            {
                                                label: __('Shadow Color', 'wp-media-folder-gallery-addon'),
                                                value: hoverShadowColor,
                                                onChange: (value) => setAttributes({hoverShadowColor: value === undefined ? '#ccc' : value}),
                                            }
                                        ]}
                                    />
                                </PanelBody>
                                <PanelBody title={__('Hover', 'wp-media-folder-gallery-addon')} initialOpen={false}>
                                    <PanelColorSettings
                                        title={__('Hover color', 'wp-media-folder-gallery-addon')}
                                        initialOpen={false}
                                        colorSettings={[
                                            {
                                                label: __('Hover Color', 'wp-media-folder-gallery-addon'),
                                                value: hover_color,
                                                onChange: (value) => setAttributes({hover_color: value === undefined ? '#000' : value}),
                                            }
                                        ]}
                                    />
                                    <RangeControl
                                        label={__('Hover opacity', 'wp-media-folder-gallery-addon')}
                                        value={hover_opacity}
                                        onChange={(value) => setAttributes({hover_opacity: value})}
                                        min={0}
                                        max={1}
                                        step={0.1}
                                    />
                                    <SelectControl
                                        label={__('Title position', 'wp-media-folder-gallery-addon')}
                                        value={hover_title_position}
                                        options={[
                                            {label: __('None', 'wp-media-folder-gallery-addon'), value: 'none'},
                                            {label: __('Top left', 'wp-media-folder-gallery-addon'), value: 'top_left'},
                                            {label: __('Top right', 'wp-media-folder-gallery-addon'), value: 'top_right'},
                                            {label: __('Top center', 'wp-media-folder-gallery-addon'), value: 'top_center'},
                                            {label: __('Bottom left', 'wp-media-folder-gallery-addon'), value: 'bottom_left'},
                                            {label: __('Bottom right', 'wp-media-folder-gallery-addon'), value: 'bottom_right'},
                                            {label: __('Bottom center', 'wp-media-folder-gallery-addon'), value: 'bottom_center'},
                                            {label: __('Center center', 'wp-media-folder-gallery-addon'), value: 'center_center'},
                                        ]}
                                        onChange={(value) => setAttributes({hover_title_position: value})}
                                    />
                                    <SelectControl
                                        label={__('Description position', 'wp-media-folder-gallery-addon')}
                                        value={hover_desc_position}
                                        options={[
                                            {label: __('None', 'wp-media-folder-gallery-addon'), value: 'none'},
                                            {label: __('Top left', 'wp-media-folder-gallery-addon'), value: 'top_left'},
                                            {label: __('Top right', 'wp-media-folder-gallery-addon'), value: 'top_right'},
                                            {label: __('Top center', 'wp-media-folder-gallery-addon'), value: 'top_center'},
                                            {label: __('Bottom left', 'wp-media-folder-gallery-addon'), value: 'bottom_left'},
                                            {label: __('Bottom right', 'wp-media-folder-gallery-addon'), value: 'bottom_right'},
                                            {label: __('Bottom center', 'wp-media-folder-gallery-addon'), value: 'bottom_center'},
                                            {label: __('Center center', 'wp-media-folder-gallery-addon'), value: 'center_center'},
                                        ]}
                                        onChange={(value) => setAttributes({hover_desc_position: value})}
                                    />
                                    <RangeControl
                                        label={__('Title size', 'wp-media-folder-gallery-addon')}
                                        value={hover_title_size || 16}
                                        onChange={(value) => setAttributes({hover_title_size: value})}
                                        min={0}
                                        step={1}
                                        max={150}
                                    />
                                    <RangeControl
                                        label={__('Description size', 'wp-media-folder-gallery-addon')}
                                        value={hover_desc_size || 16}
                                        onChange={(value) => setAttributes({hover_desc_size: value})}
                                        min={0}
                                        step={1}
                                        max={150}
                                    />
                                    <PanelColorSettings
                                        title={__('Title color', 'wp-media-folder-gallery-addon')}
                                        initialOpen={false}
                                        colorSettings={[
                                            {
                                                label: __('Title color', 'wp-media-folder-gallery-addon'),
                                                value: hover_title_color,
                                                onChange: (value) => setAttributes({hover_title_color: value === undefined ? '#fff' : value}),
                                            }
                                        ]}
                                    />
                                    <PanelColorSettings
                                        title={__('Description color', 'wp-media-folder-gallery-addon')}
                                        initialOpen={false}
                                        colorSettings={[
                                            {
                                                label: __('Description color', 'wp-media-folder-gallery-addon'),
                                                value: hover_desc_color,
                                                onChange: (value) => setAttributes({hover_desc_color: value === undefined ? '#fff' : value}),
                                            }
                                        ]}
                                    />
                                </PanelBody>
                            </InspectorControls>
                        </Fragment>
                    )}

                    {typeof cover === "undefined" && galleryId === 0 &&
                        <Placeholder
                            icon={iconblock}
                            label={__('WP Media Folder Gallery Addon', 'wp-media-folder-gallery-addon')}
                            instructions={__('Select or create a WP Media Folder Addon image gallery', 'wp-media-folder-gallery-addon')}
                        >
                            <button className="components-button is-button is-default is-primary is-large aligncenter"
                                    onClick={this.openModal}>{wpmfgalleryblocks.l18n.select_gallery_title}</button>
                        </Placeholder>}
                    {typeof cover === "undefined" && this.state.isOpen ?
                        <Modal
                            className="wpmfGalleryModal"
                            title={wpmfgalleryblocks.l18n.gallery_title}
                            onRequestClose={this.closeModal}
                            shouldCloseOnClickOutside={false}>
                            <FocusableIframe
                                src={wpmfgalleryblocks.vars.admin_gallery_page + `&idblock=${this.props.clientId}&gallery_id=${galleryId}&display=${display}&layout=${layout}&row_height=${row_height}&aspect_ratio=${aspect_ratio}&display_tree=${display_tree}&display_tag=${display_tag}&columns=${columns}&size=${size}&targetsize=${targetsize}&link=${link}&wpmf_orderby=${wpmf_orderby}&wpmf_order=${wpmf_order}&animation=${animation}&duration=${duration}&auto_animation=${auto_animation}&number_lines=${number_lines}&show_buttons=${show_buttons}&tree_width=${tree_width}&gutterwidth=${gutterwidth}&hover_color=${hover_color.replace('#', '')}&hover_opacity=${hover_opacity}&hover_title_position=${hover_title_position}&hover_title_size=${hover_title_size}&hover_title_color=${hover_title_color.replace('#', '')}&hover_desc_position=${hover_desc_position}&hover_desc_size=${hover_desc_size}&hover_desc_color=${hover_desc_color.replace('#', '')}`}
                            />
                        </Modal>
                        : null}
                    {
                        typeof cover === "undefined" && this.state.title !== '' && <div className="wpmf_glraddon_title_block">{__('Gallery title: ', 'wp-media-folder-gallery-addon') + this.state.title }</div>
                    }

                    {
                        typeof cover === "undefined" && html !== '' && <div className="wpmf-gallery-addon-block-preview" dangerouslySetInnerHTML={{__html: html}}></div>
                    }

                    {
                        typeof cover === "undefined" && html === '' && parseInt(galleryId) !== 0 && <div className="wpmf-gallery-addon-block-preview" dangerouslySetInnerHTML={{__html: `<p class="wpmf_glraddon_block_loading">${__('Loading...', 'wp-media-folder-gallery-addon')}</p>`}}></div>
                    }
                </Fragment>
            );
        }
    }

    registerBlockType('wpmf/block-gallery', {
        title: wpmfgalleryblocks.l18n.gallery_title,
        icon: iconblock,
        category: 'wp-media-folder',
        keywords: [
            __('gallery', 'wp-media-folder-gallery-addon'),
            __('file', 'wp-media-folder-gallery-addon')
        ],
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
                default: 1,
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
                attribute: 'src',
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
            },
        },
        edit: WpmfGallery,
        save: ({attributes}) => {
            const {
                galleryId,
                display,
                layout,
                row_height,
                aspect_ratio,
                display_tree,
                display_tag,
                animation,
                duration,
                auto_animation,
                crop_image,
                show_buttons,
                columns,
                size,
                targetsize,
                link,
                wpmf_orderby,
                wpmf_order,
                img_border_radius,
                gutterwidth,
                hoverShadowH,
                hoverShadowV,
                hoverShadowBlur,
                hoverShadowSpread,
                hoverShadowColor,
                borderWidth,
                borderStyle,
                borderColor,
                number_lines,
                hover_color,
                hover_opacity,
                hover_title_position,
                hover_title_size,
                hover_title_color,
                hover_desc_position,
                hover_desc_size,
                hover_desc_color
            } = attributes;
            let gallery_shortcode = '[wpmfgallery';
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
                gallery_shortcode += ` img_shadow="${hoverShadowH}px ${hoverShadowV}px ${hoverShadowBlur}px ${hoverShadowSpread}px ${hoverShadowColor}"`;
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
        getEditWrapperProps(attributes) {
            const {align} = attributes;
            const props = {'data-resized': true};

            if ('left' === align || 'right' === align || 'center' === align) {
                props['data-align'] = align;
            }

            return props;
        }
    });
})(wp.i18n, wp.blocks, wp.element, wp.editor, wp.components);