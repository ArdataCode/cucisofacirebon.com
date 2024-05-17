<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Element Description: VC Gallery
 */
if (class_exists('WPBakeryShortCode')) {
    /**
     * Class WpmfBakeryGalleryAddon
     */
    class WpmfBakeryGalleryAddon extends WPBakeryShortCode
    {
        /**
         * WpmfBakeryGalleryAddon constructor.
         *
         * @return void
         */
        function __construct() // phpcs:ignore Squiz.Scope.MethodScope.Missing -- Method extends from WPBakeryShortCode class
        {
            // Stop all if VC is not enabled
            if (!defined('WPB_VC_VERSION')) {
                return;
            }

            $settings = get_option('wpmf_gallery_settings');
            $galleries = get_categories(
                array(
                    'hide_empty' => false,
                    'taxonomy' => WPMF_GALLERY_ADDON_TAXO,
                    'pll_get_terms_not_translated' => 1
                )
            );

            if (count($galleries) < 100) {
                $galleries = wpmfParentSort($galleries);
            }

            $galleries_list = array();
            $label = esc_html__('Select a gallery', 'wp-media-folder-gallery-addon');
            $galleries_list[$label] = 0;
            foreach ($galleries as $gallery) {
                $label = str_repeat('--', $gallery->depth) . $gallery->name;
                $galleries_list[$label] = $gallery->term_id;
            }

            // Map the block with vc_map()
            vc_map(
                array(
                    'name' => esc_html__('WPMF Gallery Addon', 'wp-media-folder-gallery-addon'),
                    'description' => esc_html__('Responsive image gallery with themes', 'wp-media-folder-gallery-addon'),
                    'base' => 'vc_wpmf_gallery_addon',
                    'category' => 'JoomUnited',
                    'icon' => WPMF_PLUGIN_URL . '/assets/images/gallery_addon-bakery.svg',
                    'front_enqueue_js' => array(
                        WPMF_PLUGIN_URL . 'assets/js/slick/slick.min.js',
                        WPMF_PLUGIN_URL . '/assets/js/display-gallery/imagesloaded.pkgd.min.js',
                        WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/js/justified-gallery/jquery.justifiedGallery.min.js',
                        WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/js/jquery.flipster.js',
                        WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/js/isotope.pkgd.js',
                        WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/packery/packery.pkgd.min.js',
                        WPMF_PLUGIN_URL . '/assets/js/vc_front.js'
                    ),
                    'front_enqueue_css' => array(
                        WPMF_PLUGIN_URL . 'assets/js/slick/slick.css',
                        WPMF_PLUGIN_URL . 'assets/js/slick/slick-theme.css',
                        WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/js/justified-gallery/justifiedGallery.min.css',
                        WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/css/jquery.flipster.css',
                        WPMF_PLUGIN_URL . '/assets/css/display-gallery/style-display-gallery.css',
                    ),
                    'params' => array(
                        array(
                            'type' => 'dropdown',
                            'heading' => esc_html__('Choose a Gallery', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'gallery_id',
                            'class' => 'wpmf_vc_dropdown',
                            'value' => $galleries_list,
                            'group' => esc_html__('General', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'dropdown',
                            'heading' => esc_html__('Theme', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'theme',
                            'class' => 'wpmf_vc_dropdown',
                            'value' => array(
                                esc_html__('Default', 'wp-media-folder-gallery-addon') => 'default',
                                esc_html__('Masonry', 'wp-media-folder-gallery-addon') => 'masonry',
                                esc_html__('Portfolio', 'wp-media-folder-gallery-addon') => 'portfolio',
                                esc_html__('Slider', 'wp-media-folder-gallery-addon') => 'slider',
                                esc_html__('Flow slide', 'wp-media-folder-gallery-addon') => 'flowslide',
                                esc_html__('Square grid', 'wp-media-folder-gallery-addon') => 'square_grid',
                                esc_html__('Material', 'wp-media-folder-gallery-addon') => 'material',
                                esc_html__('Custom grid', 'wp-media-folder-gallery-addon') => 'custom_grid'
                            ),
                            'std' => 'masonry',
                            'group' => esc_html__('General', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'dropdown',
                            'heading' => esc_html__('Layout', 'wp-media-folder-gallery-addon'),
                            'description' => esc_html__('Layout for masonry and square grid theme', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'layout',
                            'class' => 'wpmf_vc_dropdown',
                            'std' => 'vertical',
                            'value' => array(
                                esc_html__('Vertical', 'wp-media-folder-gallery-addon') => 'vertical',
                                esc_html__('Horizontal', 'wp-media-folder-gallery-addon') => 'horizontal'
                            ),
                            'group' => esc_html__('General', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'wpmf_number',
                            'heading' => esc_html__('Row height', 'wp-media-folder-gallery-addon'),
                            'description' => esc_html__('Row height for masonry and square grid theme', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'row_height',
                            'value' => 200,
                            'min' => 50,
                            'max' => 500,
                            'step' => 1,
                            'edit_field_class' => 'vc_col-sm-6',
                            'dependency' => array(
                                'element' => 'layout',
                                'value' => 'horizontal',
                            ),
                            'group' => esc_html__('General', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'dropdown',
                            'heading' => esc_html__('Aspect ratio', 'wp-media-folder-gallery-addon'),
                            'description' => esc_html__('Aspect ratio for default, material, slider, portfolio and square grid theme', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'aspect_ratio',
                            'class' => 'wpmf_vc_dropdown',
                            'std' => 'default',
                            'value' => array(
                                esc_html__('Default', 'wp-media-folder-gallery-addon') => 'default',
                                '1:1' => '1_1',
                                '3:2' => '3_2',
                                '2:3' => '2_3',
                                '4:3' => '4_3',
                                '3:4' => '3_4',
                                '16:9' => '16_9',
                                '9:16' => '9_16',
                                '21:9' => '21_9',
                                '9:21' => '9_21'
                            ),
                            'group' => esc_html__('General', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'wpmf_number',
                            'heading' => esc_html__('Columns', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'columns',
                            'value' => $settings['theme']['masonry_theme']['columns'],
                            'min' => 1,
                            'max' => 8,
                            'step' => 1,
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('General', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'wpmf_number',
                            'heading' => esc_html__('Gutter', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'gutterwidth',
                            'value' => 5,
                            'min' => 0,
                            'max' => 100,
                            'step' => 5,
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('General', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'textfield',
                            'heading' => esc_html__('Image size', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'size',
                            'value' => $settings['theme']['masonry_theme']['size'],
                            'description' => esc_html__('Enter image size. Example: thumbnail, medium, large, full or other sizes defined by current theme. Alternatively enter image size in pixels: 200x100 (Width x Height). Leave empty to use "medium" size.', 'wp-media-folder-gallery-addon'),
                            'dependency' => array(
                                'element' => 'source',
                                'value' => 'media_library',
                            ),
                            'group' => esc_html__('General', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'checkbox',
                            'heading' => esc_html__('Crop image', 'wp-media-folder-gallery-addon'),
                            'description' => esc_html__('Only apply for slider theme', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'crop_image',
                            'value' => array(esc_html__('Yes', 'wp-media-folder-gallery-addon') => 'yes'),
                            'group' => esc_html__('General', 'wp-media-folder-gallery-addon'),
                            'dependency' => array(
                                'element' => 'theme',
                                'value' => 'slider',
                            ),
                        ),
                        array(
                            'type' => 'dropdown',
                            'heading' => esc_html__('Action On Click', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'link',
                            'class' => 'wpmf_vc_dropdown',
                            'std' => $settings['theme']['masonry_theme']['link'],
                            'value' => array(
                                esc_html__('Lightbox', 'wp-media-folder-gallery-addon') => 'file',
                                esc_html__('Attachment Page', 'wp-media-folder-gallery-addon') => 'post',
                                esc_html__('None', 'wp-media-folder-gallery-addon') => 'none'
                            ),
                            'group' => esc_html__('General', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'textfield',
                            'heading' => esc_html__('Lightbox size', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'targetsize',
                            'value' => $settings['theme']['masonry_theme']['targetsize'],
                            'description' => esc_html__('Enter image size. Example: thumbnail, medium, large, full or other sizes defined by current theme. Alternatively enter image size in pixels: 200x100 (Width x Height). Leave empty to use "large" size.', 'wp-media-folder-gallery-addon'),
                            'dependency' => array(
                                'element' => 'source',
                                'value' => 'media_library',
                            ),
                            'group' => esc_html__('General', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'dropdown',
                            'heading' => esc_html__('Order by', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'orderby',
                            'class' => 'wpmf_vc_dropdown',
                            'std' => $settings['theme']['masonry_theme']['orderby'],
                            'value' => array(
                                esc_html__('Custom', 'wp-media-folder-gallery-addon') => 'post__in',
                                esc_html__('Random', 'wp-media-folder-gallery-addon') => 'rand',
                                esc_html__('Title', 'wp-media-folder-gallery-addon') => 'title',
                                esc_html__('Date', 'wp-media-folder-gallery-addon') => 'date'
                            ),
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('General', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'dropdown',
                            'heading' => esc_html__('Order', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'order',
                            'class' => 'wpmf_vc_dropdown',
                            'std' => $settings['theme']['masonry_theme']['order'],
                            'value' => array(
                                esc_html__('Ascending', 'wp-media-folder-gallery-addon') => 'ASC',
                                esc_html__('Descending', 'wp-media-folder-gallery-addon') => 'DESC'
                            ),
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('General', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'dropdown',
                            'heading' => esc_html__('Number lines', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'number_lines',
                            'class' => 'wpmf_vc_dropdown',
                            'std' => 1,
                            'value' => array(
                                1 => '1',
                                2 => '2',
                                3 => '3'
                            ),
                            'group' => esc_html__('General', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'wpmf_number',
                            'heading' => esc_html__('Border Radius', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'border_radius',
                            'value' => 0,
                            'min' => 0,
                            'max' => 20,
                            'step' => 1,
                            'group' => esc_html__('Border', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'wpmf_number',
                            'heading' => esc_html__('Border Width', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'border_width',
                            'value' => 0,
                            'min' => 0,
                            'max' => 30,
                            'step' => 1,
                            'group' => esc_html__('Border', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'dropdown',
                            'heading' => esc_html__('Border Type', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'border_style',
                            'class' => 'wpmf_vc_dropdown',
                            'std' => 'solid',
                            'value' => array(
                                esc_html__('Solid', 'wp-media-folder-gallery-addon') => 'solid',
                                esc_html__('Double', 'wp-media-folder-gallery-addon') => 'double',
                                esc_html__('Dotted', 'wp-media-folder-gallery-addon') => 'dotted',
                                esc_html__('Dashed', 'wp-media-folder-gallery-addon') => 'dashed',
                                esc_html__('Groove', 'wp-media-folder-gallery-addon') => 'groove'
                            ),
                            'group' => esc_html__('Border', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'colorpicker',
                            'heading' => esc_html__('Border Color', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'border_color',
                            'edit_field_class' => 'vc_col-sm-6',
                            'std' => '#cccccc',
                            'group' => esc_html__('Border', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'checkbox',
                            'heading' => esc_html__('Enable', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'enable_shadow',
                            'value' => array(esc_html__('Yes', 'wp-media-folder-gallery-addon') => 'yes'),
                            'group' => esc_html__('Shadow', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'wpmf_number',
                            'heading' => esc_html__('Horizontal', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'shadow_horizontal',
                            'value' => 0,
                            'min' => -50,
                            'max' => 50,
                            'step' => 1,
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('Shadow', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'wpmf_number',
                            'heading' => esc_html__('Vertical', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'shadow_vertical',
                            'value' => 0,
                            'min' => -50,
                            'max' => 50,
                            'step' => 1,
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('Shadow', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'wpmf_number',
                            'heading' => esc_html__('Blur', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'shadow_blur',
                            'value' => 0,
                            'min' => 0,
                            'max' => 50,
                            'step' => 1,
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('Shadow', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'wpmf_number',
                            'heading' => esc_html__('Spread', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'shadow_spread',
                            'value' => 0,
                            'min' => 0,
                            'max' => 50,
                            'step' => 1,
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('Shadow', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'colorpicker',
                            'heading' => esc_html__('Shadow Color', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'shadow_color',
                            'edit_field_class' => 'vc_col-sm-6',
                            'std' => '#cccccc',
                            'group' => esc_html__('Shadow', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'checkbox',
                            'heading' => esc_html__('Enable Gallery Navigation', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'gallery_navigation',
                            'value' => array(esc_html__('Yes', 'wp-media-folder-gallery-addon') => 'yes'),
                            'group' => esc_html__('Advanced', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'checkbox',
                            'heading' => esc_html__('Enable Images Tags', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'gallery_image_tags',
                            'value' => array(esc_html__('Yes', 'wp-media-folder-gallery-addon') => 'yes'),
                            'group' => esc_html__('Advanced', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'colorpicker',
                            'heading' => esc_html__('Hover Color', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'hover_color',
                            'edit_field_class' => 'vc_col-sm-12',
                            'std' => '#000',
                            'group' => esc_html__('Hover', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'wpmf_number',
                            'heading' => esc_html__('Hover Opacity', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'hover_opacity',
                            'value' => 0.4,
                            'min' => 0,
                            'max' => 1,
                            'step' => 0.1,
                            'edit_field_class' => 'vc_col-sm-12',
                            'group' => esc_html__('Hover', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'dropdown',
                            'heading' => esc_html__('Title Position', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'hover_title_position',
                            'class' => 'wpmf_vc_dropdown',
                            'value' => array(
                                esc_html__('None', 'wp-media-folder-gallery-addon') => 'none',
                                esc_html__('Top left', 'wp-media-folder-gallery-addon') => 'top_left',
                                esc_html__('Top right', 'wp-media-folder-gallery-addon') => 'top_right',
                                esc_html__('Top center', 'wp-media-folder-gallery-addon') => 'top_center',
                                esc_html__('Bottom left', 'wp-media-folder-gallery-addon') => 'bottom_left',
                                esc_html__('Bottom right', 'wp-media-folder-gallery-addon') => 'bottom_right',
                                esc_html__('Bottom center', 'wp-media-folder-gallery-addon') => 'bottom_center',
                                esc_html__('Center center', 'wp-media-folder-gallery-addon') => 'center_center'
                            ),
                            'std' => 'center_center',
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('Hover', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'dropdown',
                            'heading' => esc_html__('Description Position', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'hover_desc_position',
                            'class' => 'wpmf_vc_dropdown',
                            'value' => array(
                                esc_html__('None', 'wp-media-folder-gallery-addon') => 'none',
                                esc_html__('Top left', 'wp-media-folder-gallery-addon') => 'top_left',
                                esc_html__('Top right', 'wp-media-folder-gallery-addon') => 'top_right',
                                esc_html__('Top center', 'wp-media-folder-gallery-addon') => 'top_center',
                                esc_html__('Bottom left', 'wp-media-folder-gallery-addon') => 'bottom_left',
                                esc_html__('Bottom right', 'wp-media-folder-gallery-addon') => 'bottom_right',
                                esc_html__('Bottom center', 'wp-media-folder-gallery-addon') => 'bottom_center',
                                esc_html__('Center center', 'wp-media-folder-gallery-addon') => 'center_center'
                            ),
                            'std' => 'none',
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('Hover', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'wpmf_number',
                            'heading' => esc_html__('Title Size', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'hover_title_size',
                            'value' => 16,
                            'min' => 0,
                            'max' => 150,
                            'step' => 1,
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('Hover', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'wpmf_number',
                            'heading' => esc_html__('Description Size', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'hover_desc_size',
                            'value' => 14,
                            'min' => 0,
                            'max' => 150,
                            'step' => 1,
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('Hover', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'colorpicker',
                            'heading' => esc_html__('Title Color', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'hover_title_color',
                            'edit_field_class' => 'vc_col-sm-6',
                            'std' => '#fff',
                            'group' => esc_html__('Hover', 'wp-media-folder-gallery-addon')
                        ),
                        array(
                            'type' => 'colorpicker',
                            'heading' => esc_html__('Description Color', 'wp-media-folder-gallery-addon'),
                            'param_name' => 'hover_desc_color',
                            'edit_field_class' => 'vc_col-sm-6',
                            'std' => '#fff',
                            'group' => esc_html__('Hover', 'wp-media-folder-gallery-addon')
                        )
                    )
                )
            );
            add_shortcode('vc_wpmf_gallery_addon', array($this, 'vcWpmfGalleryHtml'));
        }

        /**
         * Render html
         *
         * @param array $atts Param details
         *
         * @return string
         */
        public function vcWpmfGalleryHtml($atts)
        {
            if (empty($atts['gallery_id'])) {
                $html = '<div class="wpmf-vc-container">
            <div id="vc-gallery-addon-placeholder" class="vc-gallery-addon-placeholder">
                        <span class="wpmf-vc-message">
                            ' . esc_html__('Please select a gallery to activate the preview', 'wp-media-folder-gallery-addon') . '
                        </span>
            </div>
          </div>';
            } else {
                $gallery_id = (!empty($atts['gallery_id'])) ? $atts['gallery_id'] : 0;
                $gallery_navigation = (isset($atts['gallery_navigation']) && $atts['gallery_navigation'] === 'yes') ? 1 : 0;
                $gallery_image_tags = (isset($atts['gallery_image_tags']) && $atts['gallery_image_tags'] === 'yes') ? 1 : 0;
                $theme = (!empty($atts['theme'])) ? $atts['theme'] : 'masonry';
                $layout = (!empty($atts['layout'])) ? $atts['layout'] : 'vertical';
                $row_height = (!empty($atts['row_height'])) ? $atts['row_height'] : 150;
                $aspect_ratio = (!empty($atts['aspect_ratio'])) ? $atts['aspect_ratio'] : 'default';
                $columns = (!empty($atts['columns'])) ? $atts['columns'] : 3;
                $size = (!empty($atts['size'])) ? $atts['size'] : 'medium';
                $crop_image = (isset($atts['crop_image']) && $atts['crop_image'] === 'yes') ? 1 : 0;
                $targetsize = (!empty($atts['targetsize'])) ? $atts['targetsize'] : 'large';
                $link = (!empty($atts['link'])) ? $atts['link'] : 'file';
                $orderby = (!empty($atts['orderby'])) ? $atts['orderby'] : 'post__in';
                $order = (!empty($atts['order'])) ? $atts['order'] : 'ASC';
                $gutterwidth = (!empty($atts['gutterwidth'])) ? $atts['gutterwidth'] : 5;
                $border_radius = (!empty($atts['border_radius'])) ? $atts['border_radius'] : 0;
                $border_style = (!empty($atts['border_style'])) ? $atts['border_style'] : 'solid';
                $border_width = (!empty($atts['border_width'])) ? $atts['border_width'] : 0;
                $border_color = (!empty($atts['border_color'])) ? $atts['border_color'] : '#cccccc';
                $enable_shadow = (isset($atts['enable_shadow']) && $atts['enable_shadow'] === 'yes') ? true : false;
                $shadow_horizontal = (!empty($atts['shadow_horizontal'])) ? $atts['shadow_horizontal'] : 0;
                $shadow_vertical = (!empty($atts['shadow_vertical'])) ? $atts['shadow_vertical'] : 0;
                $shadow_blur = (!empty($atts['shadow_blur'])) ? $atts['shadow_blur'] : 0;
                $shadow_spread = (!empty($atts['shadow_spread'])) ? $atts['shadow_spread'] : 0;
                $shadow_color = (!empty($atts['shadow_color'])) ? $atts['shadow_color'] : '#cccccc';
                $number_lines = (!empty($atts['number_lines'])) ? $atts['number_lines'] : 1;

                $hover_color = (!empty($atts['hover_color'])) ? $atts['hover_color'] : '#000';
                $hover_opacity = (!empty($atts['hover_opacity'])) ? $atts['hover_opacity'] : 0.4;
                $hover_title_position = (!empty($atts['hover_title_position'])) ? $atts['hover_title_position'] : 'center_center';
                $hover_title_size = (!empty($atts['hover_title_size'])) ? $atts['hover_title_size'] : 16;
                $hover_title_color = (!empty($atts['hover_title_color'])) ? $atts['hover_title_color'] : '#fff';
                $hover_desc_position = (!empty($atts['hover_desc_position'])) ? $atts['hover_desc_position'] : 'none';
                $hover_desc_size = (!empty($atts['hover_desc_size'])) ? $atts['hover_desc_size'] : 14;
                $hover_desc_color = (!empty($atts['hover_desc_color'])) ? $atts['hover_desc_color'] : '#fff';

                if ($enable_shadow) {
                    $img_shadow = $shadow_horizontal . 'px ' . $shadow_vertical . 'px ' . $shadow_blur . 'px ' . $shadow_spread . 'px ' . $shadow_color;
                } else {
                    $img_shadow = '';
                }
                $include_children = (isset($atts['include_children']) && $atts['include_children'] === 'yes') ? 1 : 0;
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
                if (isset($_REQUEST['vc_editable'])) {
                    $html = do_shortcode('[wpmfgallery display_tree="' . esc_attr($gallery_navigation) . '" display_tag="' . esc_attr($gallery_image_tags) . '" is_lazy_load="0" gallery_id="'. esc_attr($gallery_id) .'" display="' . esc_attr($theme) . '" layout="' . esc_attr($layout) . '" row_height="' . esc_attr($row_height) . '" aspect_ratio="' . esc_attr($aspect_ratio) . '"  columns="' . esc_attr($columns) . '" size="' . esc_attr($size) . '" targetsize="' . esc_attr($targetsize) . '" link="none" wpmf_orderby="' . esc_attr($orderby) . '" wpmf_order="' . esc_attr($order) . '" gutterwidth="' . esc_attr($gutterwidth) . '" img_border_radius="' . esc_attr($border_radius) . '" border_width="' . esc_attr($border_width) . '" border_style="' . esc_attr($border_style) . '" border_color="' . esc_attr($border_color) . '" img_shadow="' . esc_attr($img_shadow) . '" number_lines="'. esc_attr($number_lines) .'" include_children="' . esc_attr($include_children) . '" crop_image="'. $crop_image .'" hover_color="'. $hover_color .'" hover_opacity="'. $hover_opacity .'" hover_title_position="'. $hover_title_position .'" hover_title_size="'. $hover_title_size .'" hover_title_color="'. $hover_title_color .'" hover_desc_position="'. $hover_desc_position .'" hover_desc_size="'. $hover_desc_size .'" hover_desc_color="'. $hover_desc_color .'"]');
                } else {
                    $html = do_shortcode('[wpmfgallery display_tree="' . esc_attr($gallery_navigation) . '" display_tag="' . esc_attr($gallery_image_tags) . '" is_lazy_load="1" gallery_id="'. esc_attr($gallery_id) .'" display="' . esc_attr($theme) . '" layout="' . esc_attr($layout) . '" row_height="' . esc_attr($row_height) . '" aspect_ratio="' . esc_attr($aspect_ratio) . '" size="' . esc_attr($size) . '" targetsize="' . esc_attr($targetsize) . '" link="' . esc_attr($link) . '" wpmf_orderby="' . esc_attr($orderby) . '" wpmf_order="' . esc_attr($order) . '" gutterwidth="' . esc_attr($gutterwidth) . '" img_border_radius="' . esc_attr($border_radius) . '" border_width="' . esc_attr($border_width) . '" border_style="' . esc_attr($border_style) . '" border_color="' . esc_attr($border_color) . '" img_shadow="' . esc_attr($img_shadow) . '" number_lines="'. esc_attr($number_lines) .'" include_children="' . esc_attr($include_children) . '" crop_image="'. $crop_image .'" hover_color="'. $hover_color .'" hover_opacity="'. $hover_opacity .'" hover_title_position="'. $hover_title_position .'" hover_title_size="'. $hover_title_size .'" hover_title_color="'. $hover_title_color .'" hover_desc_position="'. $hover_desc_position .'" hover_desc_size="'. $hover_desc_size .'" hover_desc_color="'. $hover_desc_color .'"]');
                }
            }
            return $html;
        }
    }

    new WpmfBakeryGalleryAddon();
}
