<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Element Description: VC Single File
 */
if (class_exists('WPBakeryShortCode')) {
    /**
     * Class WpmfBakerySingleFile
     */
    class WpmfBakerySingleFile extends WPBakeryShortCode
    {
        /**
         * WpmfBakerySingleFile constructor.
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
                    'name' => esc_html__('WPMF Media Download', 'wpmf'),
                    'description' => esc_html__('Display media download', 'wpmf'),
                    'base' => 'vc_sing_file',
                    'category' => 'JoomUnited',
                    'icon' => WPMF_PLUGIN_URL . '/assets/images/file_design-bakery.svg',
                    'admin_enqueue_js' => WPMF_PLUGIN_URL . '/assets/js/vc_script.js',
                    'front_enqueue_js' => WPMF_PLUGIN_URL . '/assets/js/vc_script.js',
                    'params' => array(
                        array(
                            'type' => 'wpmf_media',
                            'block_name' => 'singlefile_url',
                            'heading' => esc_html__('URL', 'wpmf'),
                            'class' => 'wpmf_vc_select_file vc_general vc_ui-button vc_ui-button-success',
                            'button_label' => esc_html__('Select a File', 'wpmf'),
                            'param_name' => 'url',
                            'value' => '',
                            'group' => esc_html__('Settings', 'wpmf')
                        ),
                        array(
                            'type' => 'dropdown',
                            'heading' => esc_html__('Align', 'wpmf'),
                            'param_name' => 'align',
                            'value' => array(
                                esc_html__('Left', 'wpmf') => 'left',
                                esc_html__('Right', 'wpmf') => 'right',
                                esc_html__('Center', 'wpmf') => 'center'
                            ),
                            'std' => 'left',
                            'group' => esc_html__('Settings', 'wpmf')
                        )
                    )
                )
            );
            add_shortcode('vc_sing_file', array($this, 'vcSingFileHtml'));
        }

        /**
         * Render html
         *
         * @param array $atts Param details
         *
         * @return string
         */
        public function vcSingFileHtml($atts)
        {
            if (empty($atts['url'])) {
                $html = '<div class="wpmf-vc-container">
            <div id="vc-file-design-placeholder" class="vc-file-design-placeholder">
                        <span class="wpmf-vc-message">
                            ' . esc_html__('Please select a file to preview the download button', 'wpmf') . '
                        </span>
            </div>
          </div>';
            } else {
                $align = (!empty($atts['align'])) ? $atts['align'] : 'left';
                $html = do_shortcode('[wpmffiledesign url="' . esc_attr($atts['url']) . '" align="' . esc_attr($align) . '"]');
            }
            return $html;
        }
    }

    new WpmfBakerySingleFile();
}
