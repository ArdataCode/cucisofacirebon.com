<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
if (fusion_is_element_enabled('wpmf_fusion_gallery_addon')) {
    if (!class_exists('WpmfAvadaGalleryAddonClass')) {
        /**
         * Fusion Gallery addon shortcode class.
         */
        class WpmfAvadaGalleryAddonClass extends Fusion_Element
        {
            /**
             * The gallery counter.
             *
             * @var integer
             */
            private $gallery_counter = 1;

            /**
             * Constructor.
             */
            public function __construct()
            {
                parent::__construct();
                add_shortcode('wpmf_fusion_gallery_addon', array($this, 'render'));
            }

            /**
             * Render the shortcode
             *
             * @param array  $args    Shortcode parameters.
             * @param string $content Content between shortcode.
             *
             * @return string
             */
            public function render($args, $content = '')
            {
                $attrs = FusionBuilder::set_shortcode_defaults(self::get_element_defaults(), $args, 'wpmf_fusion_gallery_addon');
                $attrs = apply_filters('fusion_builder_default_args', $attrs, 'wpmf_fusion_gallery_addon', $args);
                if (empty($attrs['gallery_id'])) {
                    $html = '<div class="wpmf-avada-container">
            <div id="avada-gallery-addon-placeholder" class="avada-gallery-addon-placeholder">
                        <span class="wpmf-avada-message">
                            ' . esc_html__('Please select a gallery to activate the preview', 'wp-media-folder-gallery-addon') . '
                        </span>
            </div>
          </div>';
                } else {
                    foreach ($attrs as $k => $v) {
                        ${$k} = $v;
                    }
                    $gallery_navigation = (isset($gallery_navigation) && $gallery_navigation === 'yes') ? 1 : 0;
                    $gallery_image_tags = (isset($gallery_image_tags) && $gallery_image_tags === 'yes') ? 1 : 0;
                    if ($enable_shadow) {
                        $img_shadow = $shadow_horizontal . 'px ' . $shadow_vertical . 'px ' . $shadow_blur . 'px ' . $shadow_spread . 'px ' . $shadow_color;
                    } else {
                        $img_shadow = '';
                    }
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
                    $is_builder = (function_exists('fusion_is_preview_frame') && fusion_is_preview_frame()) || (function_exists('fusion_is_builder_frame') && fusion_is_builder_frame());
                    $style = '';
                    switch ($theme) {
                        case 'default':
                        case 'masonry':
                        case 'portfolio':
                        case 'square_grid':
                        case 'custom_grid':
                            if ($img_shadow !== '') {
                                $style .= '#wpmf-gallery-' . $this->gallery_counter . ' .wpmf-gallery-item img:not(.glrsocial_image):hover, #wpmf-gallery-' . $this->gallery_counter . ' .wpmf-gallery-item .wpmf_overlay {box-shadow: ' . $img_shadow . ' !important; transition: all 200ms ease;}';
                            }

                            if ($border_style !== 'none') {
                                $style .= '#wpmf-gallery-' . $this->gallery_counter . ' .wpmf-gallery-item img:not(.glrsocial_image) {border: ' . $border_color . ' ' . $border_width . 'px ' . $border_style . '}';
                            }
                            break;
                        case 'slider':
                            if ($img_shadow !== '') {
                                if ((int)$columns > 1) {
                                    $style .= '#wpmf-gallery-' . $this->gallery_counter . ' .wpmf-gallery-item:hover {box-shadow: ' . $img_shadow . ' !important; transition: all 200ms ease;}';
                                }
                            }

                            if ($border_style !== 'none') {
                                if ((int)$columns === 1) {
                                    $style .= '#wpmf-gallery-' . $this->gallery_counter . ' .wpmf-gallery-item img:not(.glrsocial_image) {border: ' . $border_color . ' ' . $border_width . 'px ' . $border_style . ';}';
                                } else {
                                    $style .= '#wpmf-gallery-' . $this->gallery_counter . ' .wpmf-gallery-item .wpmf-gallery-icon {border: ' . $border_color . ' ' . $border_width . 'px ' . $border_style . ';}';
                                }
                            }
                            break;
                        case 'material':
                            if ($img_shadow !== '') {
                                $style .= '#wpmf-gallery-' . $this->gallery_counter . ' .wpmf-gallery-item .wpmf-card-image:hover {box-shadow: ' . $img_shadow . ' !important; transition: all 200ms ease;}';
                            }

                            if ($border_style !== 'none') {
                                $style .= '#wpmf-gallery-' . $this->gallery_counter . ' .wpmf-gallery-item img:not(.glrsocial_image) {border: ' . $border_color . ' ' . $border_width . 'px ' . $border_style . '}';
                            }
                            break;
                        case 'flowslide':
                            if ($img_shadow !== '') {
                                $style .= '#wpmf-gallery-' . $this->gallery_counter . ' .wpmf-gallery-item.flipster__item--current img:not(.glrsocial_image):hover, #wpmf-gallery-' . $this->gallery_counter . ' .wpmf-gallery-item.flipster__item--current .wpmf_overlay {box-shadow: ' . $img_shadow . ' !important; transition: all 200ms ease;}';
                            }

                            if ($border_style !== 'none') {
                                $style .= '#wpmf-gallery-' . $this->gallery_counter . ' .wpmf-gallery-item img:not(.glrsocial_image) {border: ' . $border_color . ' ' . $border_width . 'px ' . $border_style . '}';
                            }
                            break;
                    }

                    if ('' !== $style) {
                        $style = '<style type="text/css">' . $style . '</style>';
                    }

                    if ($is_builder) {
                        $html = do_shortcode('[wpmfgallery display_tree="' . esc_attr($gallery_navigation) . '" display_tag="' . esc_attr($gallery_image_tags) . '" is_lazy_load="0" gallery_id="'. esc_attr($gallery_id) .'" display="' . esc_attr($theme) . '" layout="' . esc_attr($layout) . '" row_height="' . esc_attr($row_height) . '" aspect_ratio="' . esc_attr($aspect_ratio) . '" columns="' . esc_attr($columns) . '" size="' . esc_attr($size) . '" targetsize="' . esc_attr($targetsize) . '" link="none" wpmf_orderby="' . esc_attr($orderby) . '" wpmf_order="' . esc_attr($order) . '" gutterwidth="' . esc_attr($gutterwidth) . '" img_border_radius="' . esc_attr($border_radius) . '" border_width="' . esc_attr($border_width) . '" border_style="' . esc_attr($border_style) . '" border_color="' . esc_attr($border_color) . '" img_shadow="' . esc_attr($img_shadow) . '" number_lines="'. esc_attr($number_lines) .'" crop_image="'. esc_attr($crop_image) .'" hover_color="'. $hover_color .'" hover_opacity="'. $hover_opacity .'" hover_title_position="'. $hover_title_position .'" hover_title_size="'. $hover_title_size .'" hover_title_color="'. $hover_title_color .'" hover_desc_position="'. $hover_desc_position .'" hover_desc_size="'. $hover_desc_size .'" hover_desc_color="'. $hover_desc_color .'"]');
                    } else {
                        $html = do_shortcode('[wpmfgallery display_tree="' . esc_attr($gallery_navigation) . '" display_tag="' . esc_attr($gallery_image_tags) . '" is_lazy_load="1" gallery_id="'. esc_attr($gallery_id) .'" display="' . esc_attr($theme) . '" layout="' . esc_attr($layout) . '" row_height="' . esc_attr($row_height) . '" aspect_ratio="' . esc_attr($aspect_ratio) . '" columns="' . esc_attr($columns) . '" size="' . esc_attr($size) . '" targetsize="' . esc_attr($targetsize) . '" link="' . esc_attr($link) . '" wpmf_orderby="' . esc_attr($orderby) . '" wpmf_order="' . esc_attr($order) . '" gutterwidth="' . esc_attr($gutterwidth) . '" img_border_radius="' . esc_attr($border_radius) . '" border_width="' . esc_attr($border_width) . '" border_style="' . esc_attr($border_style) . '" border_color="' . esc_attr($border_color) . '" img_shadow="' . esc_attr($img_shadow) . '" number_lines="'. esc_attr($number_lines) .'" crop_image="'. esc_attr($crop_image) .'" hover_color="'. $hover_color .'" hover_opacity="'. $hover_opacity .'" hover_title_position="'. $hover_title_position .'" hover_title_size="'. $hover_title_size .'" hover_title_color="'. $hover_title_color .'" hover_desc_position="'. $hover_desc_position .'" hover_desc_size="'. $hover_desc_size .'" hover_desc_color="'. $hover_desc_color .'"]');
                    }

                    $html = $style.$html;
                    $this->gallery_counter++;
                }

                return apply_filters('wpmf_fusion_gallery_addon_element_content', $html, $args);
            }

            /**
             * Gets the default values.
             *
             * @return array
             */
            public static function get_element_defaults() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Method extends from Fusion_Element class
            {
                $settings = get_option('wpmf_gallery_settings');
                $masonry_settings = $settings['theme']['masonry_theme'];
                $defaults = array(
                    'gallery_title' => '',
                    'gallery_id' => 0,
                    'theme' => 'masonry',
                    'layout' => 'vertical',
                    'row_height' => 200,
                    'aspect_ratio' => 'default',
                    'columns' => (isset($masonry_settings['columns'])) ? (int)$masonry_settings['columns'] : 3,
                    'gutterwidth' => (isset($masonry_settings['gutterwidth'])) ? (int)$masonry_settings['gutterwidth'] : 5,
                    'size' => (isset($masonry_settings['size'])) ? $masonry_settings['size'] : 'medium',
                    'link' => (isset($masonry_settings['link'])) ? $masonry_settings['link'] : 'file',
                    'targetsize' => (isset($masonry_settings['targetsize'])) ? $masonry_settings['targetsize'] : 'large',
                    'orderby' => (isset($masonry_settings['orderby'])) ? $masonry_settings['orderby'] : 'post__in',
                    'order' => (isset($masonry_settings['order'])) ? $masonry_settings['order'] : 'ASC',
                    'border_radius' => 0,
                    'border_width' => 0,
                    'border_style' => 'solid',
                    'border_color' => '#cccccc',
                    'enable_shadow' => 'no',
                    'crop_image' => 'yes',
                    'shadow_horizontal' => 0,
                    'shadow_vertical' => 0,
                    'shadow_blur' => 0,
                    'shadow_spread' => 0,
                    'shadow_color' => '#cccccc',
                    'gallery_navigation' => 0,
                    'gallery_image_tags' => 0,
                    'number_lines' => 1,
                    'hover_color' => '#000',
                    'hover_opacity' => '0.4',
                    'hover_title_position' => 'center_center',
                    'hover_title_size' => 16,
                    'hover_title_color' => '#fff',
                    'hover_desc_position' => 'none',
                    'hover_desc_size' => 14,
                    'hover_desc_color' => '#fff'
                );

                return $defaults;
            }
        }
    }

    new WpmfAvadaGalleryAddonClass();
}

/**
 * Map shortcode to Avada Builder.
 *
 * @return void
 */
function wpmfFusionElementGalleryAddon()
{
    $settings = get_option('wpmf_gallery_settings');
    $defaults = $settings['theme']['masonry_theme'];
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
    $galleries_list[0] = $label;
    foreach ($galleries as $gallery) {
        $label = str_repeat('--', $gallery->depth) . $gallery->name;
        $galleries_list[$gallery->term_id] = $label;
    }

    fusion_builder_map(
        fusion_builder_frontend_data(
            'WpmfAvadaGalleryAddonClass',
            array(
                'name' => esc_attr__('WPMF Gallery Addon', 'wp-media-folder-gallery-addon'),
                'shortcode' => 'wpmf_fusion_gallery_addon',
                'icon' => 'wpmf-avada-icon wpmf-avada-gallery-addon-icon',
                'preview' => WPMF_GALLERY_ADDON_PLUGIN_DIR . 'avada-widgets/templates/gallery.php',
                'preview_id' => 'fusion-builder-wpmf-gallery-addon-preview-template',
                'allow_generator' => true,
                'sortable' => false,
                'help_url' => '#',
                'params' => array(
                    array(
                        'type' => 'textfield',
                        'heading' => esc_html__('Gallery Title', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'gallery_title',
                        'value'       => '',
                        'hidden' => true,
                    ),
                    array(
                        'type' => 'select',
                        'heading' => esc_html__('Choose a Gallery', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'gallery_id',
                        'value' => $galleries_list
                    ),
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_attr__('Theme', 'wp-media-folder-gallery-addon'),
                        'description' => __('Select the gallery layout type.', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'theme',
                        'value' => array(
                            'default' => esc_html__('Default', 'wp-media-folder-gallery-addon'),
                            'masonry' => esc_html__('Masonry', 'wp-media-folder-gallery-addon'),
                            'portfolio' => esc_html__('Portfolio', 'wp-media-folder-gallery-addon'),
                            'slider' => esc_html__('Slider', 'wp-media-folder-gallery-addon'),
                            'flowslide' => esc_html__('Flow slide', 'wp-media-folder-gallery-addon'),
                            'square_grid' => esc_html__('Square grid', 'wp-media-folder-gallery-addon'),
                            'material' => esc_html__('Material', 'wp-media-folder-gallery-addon'),
                            'custom_grid' => esc_html__('Custom grid', 'wp-media-folder-gallery-addon')
                        ),
                        'default' => 'masonry',
                    ),
                    array(
                        'type' => 'select',
                        'heading' => esc_attr__('Layout', 'wp-media-folder-gallery-addon'),
                        'description' => __('Layout for masonry and square grid theme', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'layout',
                        'value' => array(
                            'vertical' => esc_attr__('Vertical', 'wp-media-folder-gallery-addon'),
                            'horizontal' => esc_attr__('Horizontal', 'wp-media-folder-gallery-addon')
                        ),
                        'default' => 'vertical'
                    ),
                    array(
                        'type' => 'range',
                        'heading' => esc_attr__('Row height', 'wp-media-folder-gallery-addon'),
                        'description' => __('Row height for masonry and square grid theme', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'row_height',
                        'value' => 200,
                        'min' => 50,
                        'max' => 500,
                        'step' => 1,
                        'dependency' => array(
                            array(
                                'element' => 'layout',
                                'value' => 'horizontal',
                                'operator' => '==',
                            ),
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'heading' => esc_attr__('Aspect ratio', 'wp-media-folder-gallery-addon'),
                        'description' => __('Aspect ratio for default, material, slider, portfolio and square grid theme', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'aspect_ratio',
                        'value' => array(
                            'default' => esc_html__('Default', 'wp-media-folder-gallery-addon'),
                            '1_1' => '1:1',
                            '3_2' => '3:2',
                            '2_3' => '2:3',
                            '4_3' => '4:3',
                            '3_4' => '3:4',
                            '16_9' => '16:9',
                            '9_16' => '9:16',
                            '21_9' => '21:9',
                            '9_21' => '9:21'
                        ),
                        'default' => 'default'
                    ),
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_attr__('Enable Gallery Navigation', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'gallery_navigation',
                        'value' => array(
                            'yes' => esc_attr__('Yes', 'wp-media-folder-gallery-addon'),
                            'no' => esc_attr__('No', 'wp-media-folder-gallery-addon'),
                        ),
                        'default' => 'no',
                    ),
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_attr__('Enable Images Tags', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'gallery_image_tags',
                        'value' => array(
                            'yes' => esc_attr__('Yes', 'wp-media-folder-gallery-addon'),
                            'no' => esc_attr__('No', 'wp-media-folder-gallery-addon'),
                        ),
                        'default' => 'no',
                    ),
                    array(
                        'type' => 'range',
                        'heading' => esc_attr__('Columns', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'columns',
                        'value' => $defaults['columns'],
                        'min' => '1',
                        'max' => '8',
                        'step' => '1'
                    ),
                    array(
                        'type' => 'select',
                        'heading' => esc_attr__('Number Lines', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'number_lines',
                        'value' => array(
                            '1' => 1,
                            '2' => 2,
                            '3' => 3
                        ),
                        'default' => 1,
                        'dependency' => array(
                            array(
                                'element' => 'theme',
                                'value' => 'slider',
                                'operator' => '==',
                            ),
                        )
                    ),
                    array(
                        'type' => 'select',
                        'heading' => esc_attr__('Image Size', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'size',
                        'value' => apply_filters('image_size_names_choose', array(
                            'thumbnail' => __('Thumbnail', 'wp-media-folder-gallery-addon'),
                            'medium' => __('Medium', 'wp-media-folder-gallery-addon'),
                            'large' => __('Large', 'wp-media-folder-gallery-addon'),
                            'full' => __('Full Size', 'wp-media-folder-gallery-addon'),
                        )),
                        'default' => $defaults['size']
                    ),
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_attr__('Crop Image', 'wp-media-folder-gallery-addon'),
                        'description' => esc_attr__('Only apply for slider theme', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'crop_image',
                        'value' => array(
                            'yes' => esc_attr__('Yes', 'wp-media-folder-gallery-addon'),
                            'no' => esc_attr__('No', 'wp-media-folder-gallery-addon'),
                        ),
                        'default' => 'yes',
                    ),
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_attr__('Action On Click', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'link',
                        'value' => array(
                            'file' => esc_html__('Lightbox', 'wp-media-folder-gallery-addon'),
                            'post' => esc_html__('Attachment Page', 'wp-media-folder-gallery-addon'),
                            'none' => esc_html__('None', 'wp-media-folder-gallery-addon'),
                        ),
                        'default' => $defaults['link'],
                    ),
                    array(
                        'type' => 'select',
                        'heading' => esc_attr__('Lightbox Size', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'targetsize',
                        'value' => apply_filters('image_size_names_choose', array(
                            'thumbnail' => __('Thumbnail', 'wp-media-folder-gallery-addon'),
                            'medium' => __('Medium', 'wp-media-folder-gallery-addon'),
                            'large' => __('Large', 'wp-media-folder-gallery-addon'),
                            'full' => __('Full Size', 'wp-media-folder-gallery-addon'),
                        )),
                        'default' => $defaults['targetsize'],
                        'dependency' => array(
                            array(
                                'element' => 'link',
                                'value' => 'file',
                                'operator' => '==',
                            ),
                        ),
                    ),
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_attr__('Order by', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'orderby',
                        'value' => array(
                            'post__in' => esc_html__('Custom', 'wp-media-folder-gallery-addon'),
                            'rand' => esc_html__('Random', 'wp-media-folder-gallery-addon'),
                            'title' => esc_html__('Title', 'wp-media-folder-gallery-addon'),
                            'date' => esc_html__('Date', 'wp-media-folder-gallery-addon')
                        ),
                        'default' => $defaults['orderby']
                    ),
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_attr__('Order', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'order',
                        'value' => array(
                            'ASC' => esc_html__('Ascending', 'wp-media-folder-gallery-addon'),
                            'DESC' => esc_html__('Descending', 'wp-media-folder-gallery-addon')
                        ),
                        'default' => $defaults['order']
                    ),
                    array(
                        'type' => 'range',
                        'heading' => esc_attr__('Gutter', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'gutterwidth',
                        'value' => '5',
                        'min' => '0',
                        'max' => '100',
                        'step' => '5',
                    ),
                    array(
                        'type' => 'range',
                        'heading' => esc_attr__('Border Radius', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'border_radius',
                        'value' => '0',
                        'min' => '0',
                        'max' => '20',
                        'step' => '1',
                    ),
                    array(
                        'type' => 'range',
                        'heading' => esc_attr__('Border Width', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'border_width',
                        'value' => '0',
                        'min' => '0',
                        'max' => '30',
                        'step' => '1',

                    ),
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_attr__('Border Type', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'border_style',
                        'value' => array(
                            'solid' => esc_html__('Solid', 'wp-media-folder-gallery-addon'),
                            'double' => esc_html__('Double', 'wp-media-folder-gallery-addon'),
                            'dotted' => esc_html__('Dotted', 'wp-media-folder-gallery-addon'),
                            'dashed' => esc_html__('Dashed', 'wp-media-folder-gallery-addon'),
                            'groove' => esc_html__('Groove', 'wp-media-folder-gallery-addon')
                        ),
                        'default' => 'solid',
                        'dependency' => array(
                            array(
                                'element' => 'border_width',
                                'value' => '0',
                                'operator' => '!=',
                            ),
                        ),

                    ),
                    array(
                        'type' => 'colorpickeralpha',
                        'heading' => esc_attr__('Border Color', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'border_color',
                        'value' => '',
                        'default' => '#cccccc',
                        'dependency' => array(
                            array(
                                'element' => 'border_width',
                                'value' => '0',
                                'operator' => '!=',
                            ),
                        ),
                    ),
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_attr__('Enable Shadow', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'enable_shadow',
                        'value' => array(
                            'yes' => esc_attr__('Yes', 'wp-media-folder-gallery-addon'),
                            'no' => esc_attr__('No', 'wp-media-folder-gallery-addon'),
                        ),
                        'default' => 'no',
                    ),
                    array(
                        'type' => 'colorpickeralpha',
                        'heading' => esc_attr__('Shadow Color', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'shadow_color',
                        'value' => '',
                        'default' => '#cccccc',
                        'dependency' => array(
                            array(
                                'element' => 'enable_shadow',
                                'value' => 'yes',
                                'operator' => '==',
                            ),
                        ),
                    ),
                    array(
                        'type' => 'range',
                        'heading' => esc_attr__('Shadow Horizontal', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'shadow_horizontal',
                        'value' => '0',
                        'min' => '-50',
                        'max' => '50',
                        'step' => '1',
                        'dependency' => array(
                            array(
                                'element' => 'enable_shadow',
                                'value' => 'yes',
                                'operator' => '==',
                            ),
                        ),
                    ),
                    array(
                        'type' => 'range',
                        'heading' => esc_attr__('Shadow Vertical', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'shadow_vertical',
                        'value' => '0',
                        'min' => '-50',
                        'max' => '50',
                        'step' => '1',
                        'dependency' => array(
                            array(
                                'element' => 'enable_shadow',
                                'value' => 'yes',
                                'operator' => '==',
                            ),
                        ),
                    ),
                    array(
                        'type' => 'range',
                        'heading' => esc_attr__('Shadow Blur', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'shadow_blur',
                        'value' => '0',
                        'min' => '0',
                        'max' => '50',
                        'step' => '1',
                        'dependency' => array(
                            array(
                                'element' => 'enable_shadow',
                                'value' => 'yes',
                                'operator' => '==',
                            ),
                        ),
                    ),
                    array(
                        'type' => 'range',
                        'heading' => esc_attr__('Shadow Spread', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'shadow_spread',
                        'value' => '0',
                        'min' => '0',
                        'max' => '50',
                        'step' => '1',
                        'dependency' => array(
                            array(
                                'element' => 'enable_shadow',
                                'value' => 'yes',
                                'operator' => '==',
                            ),
                        ),
                    ),
                    array(
                        'type' => 'colorpickeralpha',
                        'heading' => esc_attr__('Hover Color', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'hover_color',
                        'value' => '',
                        'default' => '#000'
                    ),
                    array(
                        'type' => 'range',
                        'heading' => esc_attr__('Hover Opacity', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'hover_opacity',
                        'value' => '0.4',
                        'min' => '0',
                        'max' => '1',
                        'step' => '0.1'
                    ),
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_attr__('Title Position', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'hover_title_position',
                        'value' => array(
                            'none' => esc_html__('None', 'wp-media-folder-gallery-addon'),
                            'top_left' => esc_html__('Top left', 'wp-media-folder-gallery-addon'),
                            'top_right' => esc_html__('Top right', 'wp-media-folder-gallery-addon'),
                            'top_center' => esc_html__('Top center', 'wp-media-folder-gallery-addon'),
                            'bottom_left' => esc_html__('Bottom left', 'wp-media-folder-gallery-addon'),
                            'bottom_right' => esc_html__('Bottom right', 'wp-media-folder-gallery-addon'),
                            'bottom_center' => esc_html__('Bottom center', 'wp-media-folder-gallery-addon'),
                            'center_center' => esc_html__('Center center', 'wp-media-folder-gallery-addon'),
                        ),
                        'default' => 'center_center',
                    ),
                    array(
                        'type' => 'range',
                        'heading' => esc_attr__('Title Size', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'hover_title_size',
                        'value' => '16',
                        'min' => '0',
                        'max' => '150',
                        'step' => '1'
                    ),
                    array(
                        'type' => 'colorpickeralpha',
                        'heading' => esc_attr__('Title Color', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'hover_title_color',
                        'value' => '',
                        'default' => '#fff'
                    ),
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_attr__('Description Position', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'hover_desc_position',
                        'value' => array(
                            'none' => esc_html__('None', 'wp-media-folder-gallery-addon'),
                            'top_left' => esc_html__('Top left', 'wp-media-folder-gallery-addon'),
                            'top_right' => esc_html__('Top right', 'wp-media-folder-gallery-addon'),
                            'top_center' => esc_html__('Top center', 'wp-media-folder-gallery-addon'),
                            'bottom_left' => esc_html__('Bottom left', 'wp-media-folder-gallery-addon'),
                            'bottom_right' => esc_html__('Bottom right', 'wp-media-folder-gallery-addon'),
                            'bottom_center' => esc_html__('Bottom center', 'wp-media-folder-gallery-addon'),
                            'center_center' => esc_html__('Center center', 'wp-media-folder-gallery-addon'),
                        ),
                        'default' => 'none',
                    ),
                    array(
                        'type' => 'range',
                        'heading' => esc_attr__('Description Size', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'hover_desc_size',
                        'value' => '14',
                        'min' => '0',
                        'max' => '150',
                        'step' => '1'
                    ),
                    array(
                        'type' => 'colorpickeralpha',
                        'heading' => esc_attr__('Description Color', 'wp-media-folder-gallery-addon'),
                        'param_name' => 'hover_desc_color',
                        'value' => '',
                        'default' => '#fff'
                    )
                ),
            )
        )
    );

    wp_enqueue_style(
        'wpmf-avada-style',
        WPMF_PLUGIN_URL . '/assets/css/avada_style.css',
        array(),
        WPMF_VERSION
    );

    wp_enqueue_style(
        'wpmf-slick-style',
        WPMF_PLUGIN_URL . 'assets/js/slick/slick.css',
        array(),
        WPMF_VERSION
    );

    wp_enqueue_style(
        'wpmf-slick-theme-style',
        WPMF_PLUGIN_URL . 'assets/js/slick/slick-theme.css',
        array(),
        WPMF_VERSION
    );

    wp_enqueue_style(
        'wpmf-flipster-css',
        WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/css/jquery.flipster.css',
        array(),
        WPMF_GALLERY_ADDON_VERSION
    );

    wp_enqueue_style(
        'wpmf-justified-style',
        WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/js/justified-gallery/justifiedGallery.min.css',
        array(),
        WPMF_GALLERY_ADDON_VERSION
    );

    wp_enqueue_style(
        'wpmf-avada-gallery-style',
        WPMF_PLUGIN_URL . 'assets/css/display-gallery/style-display-gallery.css',
        array(),
        WPMF_VERSION
    );

    wp_enqueue_style(
        'wpmf-avada-gallery-addon-style',
        WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/css/gallery.css',
        array(),
        WPMF_GALLERY_ADDON_VERSION
    );
}

wpmfFusionElementGalleryAddon();
add_action('fusion_builder_before_init', 'wpmfFusionElementGalleryAddon');
