<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Element Description: VC Gallery
 */
if (class_exists('WPBakeryShortCode')) {
    /**
     * Class WpmfBakeryGallery
     */
    class WpmfBakeryGallery extends WPBakeryShortCode
    {
        /**
         * WpmfBakeryGallery constructor.
         *
         * @return void
         */
        function __construct() // phpcs:ignore Squiz.Scope.MethodScope.Missing -- Method extends from WPBakeryShortCode class
        {
            // Stop all if VC is not enabled
            if (!defined('WPB_VC_VERSION')) {
                return;
            }

            $main_class = wpmfGetMainClass();
            $settings = wpmfGetOption('gallery_settings');
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

                    $label = str_repeat('--', $folder['depth']) . $folder['label'];
                    if (isset($folder['drive_type']) &&  $folder['drive_type'] !== '') {
                        $list_cloud[$label] = $folder['id'];
                    } else {
                        $list_local[$label] = $folder['id'];
                    }
                } else {
                    $list_local[$folder['label']] = 0;
                }
            }

            // Map the block with vc_map()
            vc_map(
                array(
                    'name' => esc_html__('WPMF Gallery', 'wpmf'),
                    'description' => esc_html__('Responsive image gallery with themes', 'wpmf'),
                    'base' => 'vc_wpmf_gallery',
                    'category' => 'JoomUnited',
                    'icon' => WPMF_PLUGIN_URL . '/assets/images/gallery-bakery.svg',
                    'front_enqueue_js' => array(
                        WPMF_PLUGIN_URL . 'assets/js/slick/slick.min.js',
                        WPMF_PLUGIN_URL . '/assets/js/vc_front.js'
                    ),
                    'front_enqueue_css' => array(
                        WPMF_PLUGIN_URL . 'assets/js/slick/slick.css',
                        WPMF_PLUGIN_URL . 'assets/js/slick/slick-theme.css',
                        WPMF_PLUGIN_URL . '/assets/css/display-gallery/style-display-gallery.css',
                    ),
                    'params' => array(
                        array(
                            'type' => 'dropdown',
                            'heading' => esc_html__('Theme', 'wpmf'),
                            'param_name' => 'theme',
                            'class' => 'wpmf_vc_dropdown',
                            'value' => array(
                                esc_html__('Default', 'wpmf') => 'default',
                                esc_html__('Masonry', 'wpmf') => 'masonry',
                                esc_html__('Portfolio', 'wpmf') => 'portfolio',
                                esc_html__('Slider', 'wpmf') => 'slider'
                            ),
                            'std' => 'masonry',
                            'group' => esc_html__('General', 'wpmf')
                        ),
                        array(
                            'type' => 'attach_images',
                            'heading' => esc_html__('Images', 'wpmf'),
                            'param_name' => 'items',
                            'value' => '',
                            'description' => esc_html__('Select images from media library.', 'wpmf'),
                            'dependency' => array(
                                'element' => 'source',
                                'value' => 'media_library',
                            ),
                            'group' => esc_html__('General', 'wpmf')
                        ),
                        array(
                            'type' => 'wpmf_number',
                            'heading' => esc_html__('Columns', 'wpmf'),
                            'param_name' => 'columns',
                            'value' => $settings['theme']['masonry_theme']['columns'],
                            'min' => 1,
                            'max' => 8,
                            'step' => 1,
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('General', 'wpmf')
                        ),
                        array(
                            'type' => 'wpmf_number',
                            'heading' => esc_html__('Gutter', 'wpmf'),
                            'param_name' => 'gutterwidth',
                            'value' => 5,
                            'min' => 0,
                            'max' => 100,
                            'step' => 5,
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('General', 'wpmf')
                        ),
                        array(
                            'type' => 'textfield',
                            'heading' => esc_html__('Image size', 'wpmf'),
                            'param_name' => 'size',
                            'value' => $settings['theme']['masonry_theme']['size'],
                            'description' => esc_html__('Enter image size. Example: thumbnail, medium, large, full or other sizes defined by current theme. Alternatively enter image size in pixels: 200x100 (Width x Height). Leave empty to use "medium" size.', 'wpmf'),
                            'dependency' => array(
                                'element' => 'source',
                                'value' => 'media_library',
                            ),
                            'group' => esc_html__('General', 'wpmf')
                        ),
                        array(
                            'type' => 'checkbox',
                            'heading' => esc_html__('Crop image', 'wpmf'),
                            'description' => esc_html__('Only apply for slider theme', 'wpmf'),
                            'param_name' => 'crop_image',
                            'value' => array(esc_html__('Yes', 'wpmf') => 'yes'),
                            'group' => esc_html__('General', 'wpmf'),
                            'dependency' => array(
                                'element' => 'theme',
                                'value' => 'slider',
                            ),
                        ),
                        array(
                            'type' => 'dropdown',
                            'heading' => esc_html__('Action On Click', 'wpmf'),
                            'param_name' => 'link',
                            'class' => 'wpmf_vc_dropdown',
                            'std' => $settings['theme']['masonry_theme']['link'],
                            'value' => array(
                                esc_html__('Lightbox', 'wpmf') => 'file',
                                esc_html__('Attachment Page', 'wpmf') => 'post',
                                esc_html__('None', 'wpmf') => 'none'
                            ),
                            'group' => esc_html__('General', 'wpmf')
                        ),
                        array(
                            'type' => 'textfield',
                            'heading' => esc_html__('Lightbox size', 'wpmf'),
                            'param_name' => 'targetsize',
                            'value' => $settings['theme']['masonry_theme']['targetsize'],
                            'description' => esc_html__('Enter image size. Example: thumbnail, medium, large, full or other sizes defined by current theme. Alternatively enter image size in pixels: 200x100 (Width x Height). Leave empty to use "large" size.', 'wpmf'),
                            'dependency' => array(
                                'element' => 'source',
                                'value' => 'media_library',
                            ),
                            'group' => esc_html__('General', 'wpmf')
                        ),
                        array(
                            'type' => 'dropdown',
                            'heading' => esc_html__('Order by', 'wpmf'),
                            'param_name' => 'orderby',
                            'class' => 'wpmf_vc_dropdown',
                            'std' => $settings['theme']['masonry_theme']['orderby'],
                            'value' => array(
                                esc_html__('Custom', 'wpmf') => 'post__in',
                                esc_html__('Random', 'wpmf') => 'rand',
                                esc_html__('Title', 'wpmf') => 'title',
                                esc_html__('Date', 'wpmf') => 'date'
                            ),
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('General', 'wpmf')
                        ),
                        array(
                            'type' => 'dropdown',
                            'heading' => esc_html__('Order', 'wpmf'),
                            'param_name' => 'order',
                            'class' => 'wpmf_vc_dropdown',
                            'std' => $settings['theme']['masonry_theme']['order'],
                            'value' => array(
                                esc_html__('Ascending', 'wpmf') => 'ASC',
                                esc_html__('Descending', 'wpmf') => 'DESC'
                            ),
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('General', 'wpmf')
                        ),
                        array(
                            'type' => 'wpmf_number',
                            'heading' => esc_html__('Border Radius', 'wpmf'),
                            'param_name' => 'border_radius',
                            'value' => 0,
                            'min' => 0,
                            'max' => 20,
                            'step' => 1,
                            'group' => esc_html__('Border', 'wpmf')
                        ),
                        array(
                            'type' => 'wpmf_number',
                            'heading' => esc_html__('Border Width', 'wpmf'),
                            'param_name' => 'border_width',
                            'value' => 0,
                            'min' => 0,
                            'max' => 30,
                            'step' => 1,
                            'group' => esc_html__('Border', 'wpmf')
                        ),
                        array(
                            'type' => 'dropdown',
                            'heading' => esc_html__('Border Type', 'wpmf'),
                            'param_name' => 'border_style',
                            'class' => 'wpmf_vc_dropdown',
                            'std' => 'solid',
                            'value' => array(
                                esc_html__('Solid', 'wpmf') => 'solid',
                                esc_html__('Double', 'wpmf') => 'double',
                                esc_html__('Dotted', 'wpmf') => 'dotted',
                                esc_html__('Dashed', 'wpmf') => 'dashed',
                                esc_html__('Groove', 'wpmf') => 'groove'
                            ),
                            'group' => esc_html__('Border', 'wpmf')
                        ),
                        array(
                            'type' => 'colorpicker',
                            'heading' => esc_html__('Border Color', 'wpmf'),
                            'param_name' => 'border_color',
                            'edit_field_class' => 'vc_col-sm-6',
                            'std' => '#cccccc',
                            'group' => esc_html__('Border', 'wpmf')
                        ),
                        array(
                            'type' => 'checkbox',
                            'heading' => esc_html__('Enable', 'wpmf'),
                            'param_name' => 'enable_shadow',
                            'value' => array(esc_html__('Yes', 'wpmf') => 'yes'),
                            'group' => esc_html__('Shadow', 'wpmf')
                        ),
                        array(
                            'type' => 'wpmf_number',
                            'heading' => esc_html__('Horizontal', 'wpmf'),
                            'param_name' => 'shadow_horizontal',
                            'value' => 0,
                            'min' => -50,
                            'max' => 50,
                            'step' => 1,
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('Shadow', 'wpmf')
                        ),
                        array(
                            'type' => 'wpmf_number',
                            'heading' => esc_html__('Vertical', 'wpmf'),
                            'param_name' => 'shadow_vertical',
                            'value' => 0,
                            'min' => -50,
                            'max' => 50,
                            'step' => 1,
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('Shadow', 'wpmf')
                        ),
                        array(
                            'type' => 'wpmf_number',
                            'heading' => esc_html__('Blur', 'wpmf'),
                            'param_name' => 'shadow_blur',
                            'value' => 0,
                            'min' => 0,
                            'max' => 50,
                            'step' => 1,
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('Shadow', 'wpmf')
                        ),
                        array(
                            'type' => 'wpmf_number',
                            'heading' => esc_html__('Spread', 'wpmf'),
                            'param_name' => 'shadow_spread',
                            'value' => 0,
                            'min' => 0,
                            'max' => 50,
                            'step' => 1,
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('Shadow', 'wpmf')
                        ),
                        array(
                            'type' => 'colorpicker',
                            'heading' => esc_html__('Shadow Color', 'wpmf'),
                            'param_name' => 'shadow_color',
                            'edit_field_class' => 'vc_col-sm-6',
                            'std' => '#cccccc',
                            'group' => esc_html__('Shadow', 'wpmf')
                        ),
                        array(
                            'type' => 'checkbox',
                            'heading' => esc_html__('Enable', 'wpmf'),
                            'param_name' => 'gallery_folders',
                            'value' => array(esc_html__('Yes', 'wpmf') => 'yes'),
                            'group' => esc_html__('Gallery From folder', 'wpmf')
                        ),
                        array(
                            'type' => 'dropdown',
                            'heading' => esc_html__('Select a Folder', 'wpmf'),
                            'param_name' => 'gallery_folder_id',
                            'class' => 'wpmf_vc_dropdown',
                            'std' => 0,
                            'value' => $list_local + $list_cloud,
                            'group' => esc_html__('Gallery From folder', 'wpmf')
                        ),
                        array(
                            'type' => 'checkbox',
                            'heading' => esc_html__('Include images from subfolder', 'wpmf'),
                            'param_name' => 'include_children',
                            'value' => array(esc_html__('Yes', 'wpmf') => 'yes'),
                            'group' => esc_html__('Gallery From folder', 'wpmf')
                        ),

                    )
                )
            );
            add_shortcode('vc_wpmf_gallery', array($this, 'vcWpmfGalleryHtml'));
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
            if (empty($atts['items']) && (empty($atts['gallery_folders']) || (!empty($atts['gallery_folders']) && empty($atts['gallery_folder_id'])))) {
                $html = '<div class="wpmf-vc-container">
            <div id="vc-gallery-placeholder" class="vc-gallery-placeholder">
                        <span class="wpmf-vc-message">
                            ' . esc_html__('Please add some images to the gallery to activate the preview', 'wpmf') . '
                        </span>
            </div>
          </div>';
            } else {
                $items = (!empty($atts['items'])) ? $atts['items'] : '';
                $theme = (!empty($atts['theme'])) ? $atts['theme'] : 'masonry';
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

                if ($enable_shadow) {
                    $img_shadow = $shadow_horizontal . 'px ' . $shadow_vertical . 'px ' . $shadow_blur . 'px ' . $shadow_spread . 'px ' . $shadow_color;
                } else {
                    $img_shadow = '';
                }
                $gallery_folders = (isset($atts['gallery_folders']) && $atts['gallery_folders'] === 'yes') ? 1 : 0;
                $include_children = (isset($atts['include_children']) && $atts['include_children'] === 'yes') ? 1 : 0;
                $folder_id = (!empty($atts['gallery_folder_id'])) ? $atts['gallery_folder_id'] : 0;
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
                if (isset($_REQUEST['vc_editable'])) {
                    $html = do_shortcode('[wpmf_gallery include="'. esc_attr($items) .'" display="' . esc_attr($theme) . '" columns="' . esc_attr($columns) . '" size="' . esc_attr($size) . '" targetsize="' . esc_attr($targetsize) . '" link="none" wpmf_orderby="' . esc_attr($orderby) . '" wpmf_order="' . esc_attr($order) . '" gutterwidth="' . esc_attr($gutterwidth) . '" img_border_radius="' . esc_attr($border_radius) . '" border_width="' . esc_attr($border_width) . '" border_style="' . esc_attr($border_style) . '" border_color="' . esc_attr($border_color) . '" img_shadow="' . esc_attr($img_shadow) . '" wpmf_autoinsert="' . esc_attr($gallery_folders) . '" include_children="' . esc_attr($include_children) . '" wpmf_folder_id="' . esc_attr($folder_id) . '" crop_image="'. $crop_image .'"]');
                } else {
                    $html = do_shortcode('[wpmf_gallery include="'. esc_attr($items) .'" display="' . esc_attr($theme) . '" columns="' . esc_attr($columns) . '" size="' . esc_attr($size) . '" targetsize="' . esc_attr($targetsize) . '" link="' . esc_attr($link) . '" wpmf_orderby="' . esc_attr($orderby) . '" wpmf_order="' . esc_attr($order) . '" gutterwidth="' . esc_attr($gutterwidth) . '" img_border_radius="' . esc_attr($border_radius) . '" border_width="' . esc_attr($border_width) . '" border_style="' . esc_attr($border_style) . '" border_color="' . esc_attr($border_color) . '" img_shadow="' . esc_attr($img_shadow) . '" wpmf_autoinsert="' . esc_attr($gallery_folders) . '" include_children="' . esc_attr($include_children) . '" wpmf_folder_id="' . esc_attr($folder_id) . '" crop_image="'. $crop_image .'"]');
                }
            }
            return $html;
        }
    }

    new WpmfBakeryGallery();
}
