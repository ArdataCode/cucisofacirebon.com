<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Element Description: VC PDF Embed
 */
if (class_exists('WPBakeryShortCode')) {
    /**
     * Class WpmfBakeryPdfEmbed
     */
    class WpmfBakeryPdfEmbed extends WPBakeryShortCode
    {
        /**
         * WpmfBakeryPdfEmbed constructor.
         *
         * @return void
         */
        function __construct() // phpcs:ignore Squiz.Scope.MethodScope.Missing -- Method extends from WPBakeryShortCode class
        {
            // Stop all if VC is not enabled
            if (!defined('WPB_VC_VERSION')) {
                return;
            }

            // Map the block with vc_map()
            vc_map(
                array(
                    'name' => esc_html__('WPMF PDF Embed', 'wpmf'),
                    'description' => esc_html__('Display PDF embed', 'wpmf'),
                    'base' => 'vc_pdf_embed',
                    'category' => 'JoomUnited',
                    'icon' => WPMF_PLUGIN_URL . '/assets/images/pdf_embed-bakery.svg',
                    'admin_enqueue_js' => WPMF_PLUGIN_URL . '/assets/js/vc_script.js',
                    'front_enqueue_js' => WPMF_PLUGIN_URL . '/assets/js/vc_script.js',
                    'params' => array(
                        array(
                            'type' => 'wpmf_media',
                            'block_name' => 'pdfembed_url',
                            'heading' => esc_html__('URL', 'wpmf'),
                            'class' => 'wpmf_vc_select_pdf vc_general vc_ui-button vc_ui-button-success',
                            'button_label' => esc_html__('Select a PDF', 'wpmf'),
                            'param_name' => 'url',
                            'value' => '',
                            'group' => esc_html__('Settings', 'wpmf')
                        ),
                        array(
                            'type' => 'dropdown',
                            'heading' => esc_html__('Embed', 'wpmf'),
                            'param_name' => 'embed',
                            'class' => 'wpmf_vc_dropdown',
                            'std' => 'on',
                            'value' => array(
                                esc_html__('On', 'wpmf') => 'on',
                                esc_html__('Off', 'wpmf') => 'off'
                            ),
                            'group' => esc_html__('Settings', 'wpmf')
                        ),
                        array(
                            'type' => 'dropdown',
                            'heading' => esc_html__('Target', 'wpmf'),
                            'param_name' => 'target',
                            'class' => 'wpmf_vc_dropdown',
                            'std' => 'self',
                            'value' => array(
                                esc_html__('New Window', 'wpmf') => '_blank',
                                esc_html__('Same Window', 'wpmf') => 'self'
                            ),
                            'group' => esc_html__('Settings', 'wpmf')
                        ),
                        array(
                            'type' => 'textfield',
                            'heading' => esc_html__('Width', 'wpmf'),
                            'param_name' => 'width',
                            'value' => '',
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('Settings', 'wpmf')
                        ),
                        array(
                            'type' => 'textfield',
                            'heading' => esc_html__('Height', 'wpmf'),
                            'param_name' => 'height',
                            'value' => '',
                            'edit_field_class' => 'vc_col-sm-6',
                            'group' => esc_html__('Settings', 'wpmf')
                        ),
                    )
                )
            );
            add_shortcode('vc_pdf_embed', array($this, 'vcPdfEmbedHtml'));
        }

        /**
         * Render html
         *
         * @param array $atts Param details
         *
         * @return string
         */
        public function vcPdfEmbedHtml($atts)
        {
            if (empty($atts['url'])) {
                $html = '<div class="wpmf-vc-container">
            <div id="vc-pdf-embed-placeholder" class="vc-pdf-embed-placeholder">
                        <span class="wpmf-vc-message">
                            ' . esc_html__('Please select a PDF file to activate the preview', 'wpmf') . '
                        </span>
            </div>
          </div>';
            } else {
                $embed = (!empty($atts['embed'])) ? $atts['embed'] : 'on';
                $target = (!empty($atts['target'])) ? $atts['target'] : 'self';
                $width = (!empty($atts['width'])) ? $atts['width'] : '';
                $height = (!empty($atts['height'])) ? $atts['height'] : '';
                $html = do_shortcode('[wpmfpdf url="' . esc_url($atts['url']) . '" width="'. (int)$width .'" height="'. (int)$height .'" embed="' . esc_attr($embed) . '" target="' . esc_attr($target) . '"]');
            }
            return $html;
        }
    }

    new WpmfBakeryPdfEmbed();
}
