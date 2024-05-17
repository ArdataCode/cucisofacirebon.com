<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
if (fusion_is_element_enabled('wpmf_fusion_gallery')) {
    if (!class_exists('WpmfAvadaGalleryClass')) {
        /**
         * Fusion Gallery shortcode class.
         */
        class WpmfAvadaGalleryClass extends Fusion_Element
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
                add_shortcode('wpmf_fusion_gallery', array($this, 'render'));
                add_action('wp_ajax_wpmf_fusion_gallery_get_images', array($this, 'fusionGalleryGetImages'));
            }

            /**
             * Get gallery images
             *
             * @return void
             */
            public function fusionGalleryGetImages()
            {
                // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce is not required
                $items = (isset($_POST['items'])) ? $_POST['items'] : '';
                // phpcs:enable
                $image_ids = explode(',', $items);
                $image_ids = array_filter(array_unique($image_ids));
                $html = '';
                if (!empty($image_ids)) {
                    $args = array(
                        'post_status' => 'inherit',
                        'post_type' => 'attachment',
                        'post_mime_type' => 'image',
                        'orderby' => 'post__in',
                        'order' => 'ASC',
                        'include' => $image_ids
                    );

                    $attachments = get_posts($args);
                    foreach ($attachments as $attachment) {
                        $image_url = wp_get_attachment_image_url($attachment->ID, 'thumbnail');
                        $image = '<img src="'. esc_url($image_url) .'">';
                        $image = '<div class="wpmf-image-preview"><div class="square_thumbnail"><div class="img_centered">'. $image .'</div></div></div>';
                        $html .= '<div class="wpmf-fusion-image-child" data-id="' . esc_attr($attachment->ID) . '"><button type="button" class="fusiona-remove-image fusion-wpmf-gallery-remove-image"> </button>' . $image . '</div>';
                    }
                }
                wp_send_json(array('status' => true, 'html' => $html));
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
                $attrs = FusionBuilder::set_shortcode_defaults(self::get_element_defaults(), $args, 'wpmf_fusion_gallery');
                $attrs = apply_filters('fusion_builder_default_args', $attrs, 'wpmf_fusion_gallery', $args);
                foreach ($attrs as $k => $v) {
                    ${$k} = $v;
                }
                $items = trim($items, ',');
                if (empty($items) && ($gallery_folders === 'no' || ($gallery_folders === 'yes' && empty($gallery_folder_id)))) {
                    $html = '<div class="wpmf-avada-container">
            <div id="avada-gallery-placeholder" class="avada-gallery-placeholder">
                        <span class="wpmf-avada-message">
                            ' . esc_html__('Please add some images to the gallery to activate the preview', 'wpmf') . '
                        </span>
            </div>
          </div>';
                } else {
                    if ($enable_shadow) {
                        $img_shadow = $shadow_horizontal . 'px ' . $shadow_vertical . 'px ' . $shadow_blur . 'px ' . $shadow_spread . 'px ' . $shadow_color;
                    } else {
                        $img_shadow = '';
                    }
                    $gallery_folders = (isset($attrs['gallery_folders']) && $attrs['gallery_folders'] === 'yes') ? 1 : 0;
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
                    $is_builder = (function_exists('fusion_is_preview_frame') && fusion_is_preview_frame()) || (function_exists('fusion_is_builder_frame') && fusion_is_builder_frame());
                    $style = '';
                    switch ($theme) {
                        case 'default':
                        case 'masonry':
                        case 'portfolio':
                            if ($img_shadow !== '') {
                                $style .= '#gallery-' . $this->gallery_counter . ' .wpmf-gallery-item img:hover {box-shadow: ' . $img_shadow . ' !important; transition: all 200ms ease;}';
                            }

                            if ($border_style !== 'none') {
                                $style .= '#gallery-' . $this->gallery_counter . ' .wpmf-gallery-item img {border: ' . $border_color . ' ' . $border_width . 'px ' . $border_style . '}';
                            }
                            break;
                        case 'slider':
                            if ($img_shadow !== '') {
                                if ((int)$columns > 1) {
                                    $style .= '#gallery-' . $this->gallery_counter . ' .wpmf-gallery-item:hover {box-shadow: ' . $img_shadow . ' !important; transition: all 200ms ease;}';
                                }
                            }

                            if ($border_style !== 'none') {
                                if ((int)$columns === 1) {
                                    $style .= '#gallery-' . $this->gallery_counter . ' .wpmf-gallery-item img {border: ' . $border_color . ' ' . $border_width . 'px ' . $border_style . ';}';
                                } else {
                                    $style .= '#gallery-' . $this->gallery_counter . ' .wpmf-gallery-item {border: ' . $border_color . ' ' . $border_width . 'px ' . $border_style . ';}';
                                }
                            }
                            break;
                    }

                    if ('' !== $style) {
                        $style = '<style type="text/css">' . $style . '</style>';
                    }

                    if ($is_builder) {
                        $html = do_shortcode('[wpmf_gallery include="' . esc_attr($items) . '" display="' . esc_attr($theme) . '" columns="' . esc_attr($columns) . '" size="' . esc_attr($size) . '" targetsize="' . esc_attr($targetsize) . '" link="none" wpmf_orderby="' . esc_attr($orderby) . '" wpmf_order="' . esc_attr($order) . '" gutterwidth="' . esc_attr($gutterwidth) . '" img_border_radius="' . esc_attr($border_radius) . '" border_width="' . esc_attr($border_width) . '" border_style="' . esc_attr($border_style) . '" border_color="' . esc_attr($border_color) . '" img_shadow="' . esc_attr($img_shadow) . '" wpmf_autoinsert="' . esc_attr($gallery_folders) . '" wpmf_folder_id="' . esc_attr($gallery_folder_id) . '" crop_image="'. esc_attr($crop_image) .'"]');
                    } else {
                        $html = do_shortcode('[wpmf_gallery include="' . esc_attr($items) . '" display="' . esc_attr($theme) . '" columns="' . esc_attr($columns) . '" size="' . esc_attr($size) . '" targetsize="' . esc_attr($targetsize) . '" link="' . esc_attr($link) . '" wpmf_orderby="' . esc_attr($orderby) . '" wpmf_order="' . esc_attr($order) . '" gutterwidth="' . esc_attr($gutterwidth) . '" img_border_radius="' . esc_attr($border_radius) . '" border_width="' . esc_attr($border_width) . '" border_style="' . esc_attr($border_style) . '" border_color="' . esc_attr($border_color) . '" img_shadow="' . esc_attr($img_shadow) . '" wpmf_autoinsert="' . esc_attr($gallery_folders) . '" wpmf_folder_id="' . esc_attr($gallery_folder_id) . '" crop_image="'. esc_attr($crop_image) .'"]');
                    }

                    $html = $style . $html;
                    $this->gallery_counter++;
                }

                return apply_filters('wpmf_fusion_gallery_element_content', $html, $args);
            }

            /**
             * Gets the default values.
             *
             * @return array
             */
            public static function get_element_defaults() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Method extends from Fusion_Element class
            {
                $settings = wpmfGetOption('gallery_settings');
                $masonry_settings = $settings['theme']['masonry_theme'];
                $defaults = array(
                    'theme' => 'masonry',
                    'items' => '',
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
                    'gallery_folders' => 'no',
                    'gallery_folder_id' => '0',
                );

                return $defaults;
            }
        }
    }

    new WpmfAvadaGalleryClass();
}

/**
 * Map shortcode to Avada Builder.
 *
 * @return void
 */
function wpmfFusionElementGallery()
{
    if (!function_exists('fusion_builder_frontend_data')) {
        return;
    }
    $settings = wpmfGetOption('gallery_settings');
    $defaults = $settings['theme']['masonry_theme'];
    $main_class = wpmfGetMainClass();
    $getFolders = $main_class->getAttachmentTerms('builder');
    $folders = $getFolders['attachment_terms'];
    $folders_order = $getFolders['attachment_terms_order'];
    $list_cloud = array();
    $list_local = array();
    foreach ($folders_order as $folder_order) {
        $folder = $folders[$folder_order];
        if ($folder['id'] !== 0) {
            if (!isset($folder['depth'])) {
                $folder['depth'] = 0;
            }

            if (isset($folder['drive_type']) && $folder['drive_type'] !== '') {
                $list_cloud[$folder['id']] = str_repeat('--', $folder['depth']) . $folder['label'];
            } else {
                $list_local[$folder['id']] = str_repeat('--', $folder['depth']) . $folder['label'];
            }
        } else {
            $list_local[0] = $folder['label'];
        }
    }

    fusion_builder_map(
        fusion_builder_frontend_data(
            'WpmfAvadaGalleryClass',
            array(
                'name' => esc_attr__('WPMF Gallery', 'wpmf'),
                'shortcode' => 'wpmf_fusion_gallery',
                'icon' => 'wpmf-avada-icon wpmf-avada-gallery-icon',
                'allow_generator' => true,
                'preview' => WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/avada-widgets/templates/gallery.php',
                'preview_id' => 'fusion-builder-wpmf-gallery-preview-template',
                'custom_settings_view_js' => WPMF_PLUGIN_URL . 'assets/js/avada/avada_gallery_script.js',
                'front_end_custom_settings_view_js' => WPMF_PLUGIN_URL . 'assets/js/avada/avada_gallery_script.js',
                'sortable' => false,
                'params' => array(
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_html__('Theme', 'wpmf'),
                        'description' => esc_html__('Select the gallery layout type.', 'wpmf'),
                        'param_name' => 'theme',
                        'value' => array(
                            'default' => esc_html__('Default', 'wpmf'),
                            'masonry' => esc_html__('Masonry', 'wpmf'),
                            'portfolio' => esc_html__('Portfolio', 'wpmf'),
                            'slider' => esc_html__('Slider', 'wpmf')
                        ),
                        'default' => 'masonry'
                    ),
                    array(
                        'type' => 'wpmf_gallery_select',
                        'heading' => esc_html__('Select images', 'wpmf'),
                        'description' => esc_html__('This option allows you to select multiple images at once. It saves time instead of adding one image at a time. Use Ctrl or Shift key to select multiple images.', 'wpmf'),
                        'param_name' => 'wpmf_gallery_select',
                        'group' => esc_html__('Images', 'wpmf'),
                    ),
                    array(
                        'type' => 'textfield',
                        'heading' => esc_html__('Images', 'wpmf'),
                        'param_name' => 'items',
                        'value' => '',
                        'group' => esc_html__('Images', 'wpmf')
                    ),
                    array(
                        'type' => 'range',
                        'heading' => esc_attr__('Columns', 'wpmf'),
                        'param_name' => 'columns',
                        'value' => $defaults['columns'],
                        'min' => '1',
                        'max' => '8',
                        'step' => '1'
                    ),
                    array(
                        'type' => 'select',
                        'heading' => esc_attr__('Image Size', 'wpmf'),
                        'param_name' => 'size',
                        'value' => apply_filters('image_size_names_choose', array(
                            'thumbnail' => __('Thumbnail', 'wpmf'),
                            'medium' => __('Medium', 'wpmf'),
                            'large' => __('Large', 'wpmf'),
                            'full' => __('Full Size', 'wpmf'),
                        )),
                        'default' => $defaults['size']
                    ),
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_attr__('Crop Image', 'wpmf'),
                        'description' => esc_attr__('Only apply for slider theme', 'wpmf'),
                        'param_name' => 'crop_image',
                        'value' => array(
                            'yes' => esc_attr__('Yes', 'wpmf'),
                            'no' => esc_attr__('No', 'wpmf'),
                        ),
                        'default' => 'yes',
                    ),
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_attr__('Action On Click', 'wpmf'),
                        'param_name' => 'link',
                        'value' => array(
                            'file' => esc_html__('Lightbox', 'wpmf'),
                            'post' => esc_html__('Attachment Page', 'wpmf'),
                            'none' => esc_html__('None', 'wpmf'),
                        ),
                        'default' => $defaults['link'],
                    ),
                    array(
                        'type' => 'select',
                        'heading' => esc_attr__('Lightbox Size', 'wpmf'),
                        'param_name' => 'targetsize',
                        'value' => apply_filters('image_size_names_choose', array(
                            'thumbnail' => __('Thumbnail', 'wpmf'),
                            'medium' => __('Medium', 'wpmf'),
                            'large' => __('Large', 'wpmf'),
                            'full' => __('Full Size', 'wpmf'),
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
                        'heading' => esc_attr__('Order by', 'wpmf'),
                        'param_name' => 'orderby',
                        'value' => array(
                            'post__in' => esc_html__('Custom', 'wpmf'),
                            'rand' => esc_html__('Random', 'wpmf'),
                            'title' => esc_html__('Title', 'wpmf'),
                            'date' => esc_html__('Date', 'wpmf')
                        ),
                        'default' => $defaults['orderby']
                    ),
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_attr__('Order', 'wpmf'),
                        'param_name' => 'order',
                        'value' => array(
                            'ASC' => esc_html__('Ascending', 'wpmf'),
                            'DESC' => esc_html__('Descending', 'wpmf')
                        ),
                        'default' => $defaults['order']
                    ),
                    array(
                        'type' => 'range',
                        'heading' => esc_attr__('Gutter', 'wpmf'),
                        'param_name' => 'gutterwidth',
                        'value' => '5',
                        'min' => '0',
                        'max' => '100',
                        'step' => '5'
                    ),
                    array(
                        'type' => 'range',
                        'heading' => esc_attr__('Border Radius', 'wpmf'),
                        'param_name' => 'border_radius',
                        'value' => '0',
                        'min' => '0',
                        'max' => '20',
                        'step' => '1'
                    ),
                    array(
                        'type' => 'range',
                        'heading' => esc_attr__('Border Width', 'wpmf'),
                        'param_name' => 'border_width',
                        'value' => '0',
                        'min' => '0',
                        'max' => '30',
                        'step' => '1'
                    ),
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_attr__('Border Type', 'wpmf'),
                        'param_name' => 'border_style',
                        'value' => array(
                            'solid' => esc_html__('Solid', 'wpmf'),
                            'double' => esc_html__('Double', 'wpmf'),
                            'dotted' => esc_html__('Dotted', 'wpmf'),
                            'dashed' => esc_html__('Dashed', 'wpmf'),
                            'groove' => esc_html__('Groove', 'wpmf')
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
                        'heading' => esc_attr__('Border Color', 'wpmf'),
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
                        'heading' => esc_attr__('Enable Shadow', 'wpmf'),
                        'param_name' => 'enable_shadow',
                        'value' => array(
                            'yes' => esc_attr__('Yes', 'wpmf'),
                            'no' => esc_attr__('No', 'wpmf'),
                        ),
                        'default' => 'no',
                    ),
                    array(
                        'type' => 'colorpickeralpha',
                        'heading' => esc_attr__('Shadow Color', 'wpmf'),
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
                        'heading' => esc_attr__('Shadow Horizontal', 'wpmf'),
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
                        'heading' => esc_attr__('Shadow Vertical', 'wpmf'),
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
                        'heading' => esc_attr__('Shadow Blur', 'wpmf'),
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
                        'heading' => esc_attr__('Shadow Spread', 'wpmf'),
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
                        'type' => 'radio_button_set',
                        'heading' => esc_attr__('Gallery From Folder', 'wpmf'),
                        'param_name' => 'gallery_folders',
                        'value' => array(
                            'yes' => esc_attr__('Yes', 'wpmf'),
                            'no' => esc_attr__('No', 'wpmf'),
                        ),
                        'default' => 'no'
                    ),
                    array(
                        'type' => 'select',
                        'heading' => esc_attr__('Select a Folder', 'wpmf'),
                        'param_name' => 'gallery_folder_id',
                        'value' => $list_local + $list_cloud,
                        'default' => 0,
                        'dependency' => array(
                            array(
                                'element' => 'gallery_folders',
                                'value' => 'yes',
                                'operator' => '==',
                            ),
                        )
                    ),
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
        'wpmf-avada-gallery-style',
        WPMF_PLUGIN_URL . 'assets/css/display-gallery/style-display-gallery.css',
        array(),
        WPMF_VERSION
    );
}

wpmfFusionElementGallery();
add_action('fusion_builder_before_init', 'wpmfFusionElementGallery');
