<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
if (fusion_is_element_enabled('wpmf_avada_single_file')) {
    if (!class_exists('WpmfAvadaSingleFileClass')) {
        /**
         * Fusion Single File shortcode class.
         */
        class WpmfAvadaSingleFileClass extends Fusion_Element
        {
            /**
             * Constructor.
             */
            public function __construct()
            {
                parent::__construct();
                add_shortcode('wpmf_avada_single_file', array($this, 'render'));
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
                if (empty($args['url'])) {
                    $html = '<div class="wpmf-avada-container">
            <div id="avada-file-design-placeholder" class="avada-file-design-placeholder">
                        <span class="wpmf-avada-message">
                            ' . esc_html__('Please select a file to preview the download button', 'wpmf') . '
                        </span>
            </div>
          </div>';
                } else {
                    $align = (!empty($args['align'])) ? $args['align'] : 'left';
                    $html = do_shortcode('[wpmffiledesign url="' . esc_attr($args['url']) . '" align="' . esc_attr($align) . '"]');
                }
                return apply_filters('wpmfAvadaSingleFile_content', $html, $args);
            }
        }

    }

    new WpmfAvadaSingleFileClass();
}

/**
 * Map shortcode to Avada Builder.
 *
 * @return void
 */
function wpmfAvadaSingleFile()
{
    if (!function_exists('fusion_builder_frontend_data')) {
        return;
    }
    fusion_builder_map(
        fusion_builder_frontend_data(
            'WpmfAvadaSingleFileClass',
            array(
                'name' => esc_attr__('WPMF Media Download', 'wpmf'),
                'shortcode' => 'wpmf_avada_single_file',
                'icon' => 'wpmf-avada-icon wpmf-avada-singlefile-icon',
                'custom_settings_view_js'                 => WPMF_PLUGIN_URL . 'assets/js/avada/avada_singlefile_script.js',
                'front_end_custom_settings_view_js' => WPMF_PLUGIN_URL . 'assets/js/avada/avada_singlefile_script.js',
                'preview' => WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/avada-widgets/templates/single-file.php',
                'preview_id' => 'fusion-builder-wpmf-single-file-preview-template',
                'allow_generator' => true,
                'inline_editor' => true,
                'params' => array(
                    array(
                        'type' => 'wpmf_single_file',
                        'heading' => esc_html__('Select a file', 'wpmf'),
                        'description' => esc_html__('Select a file to display.', 'wpmf'),
                        'param_name' => 'single_file_button'
                    ),
                    array(
                        'type' => 'textfield',
                        'heading' => esc_html__('URL', 'wpmf'),
                        'description' => esc_html__('Add a file URL to display.', 'wpmf'),
                        'param_name' => 'url',
                        'value'       => ''
                    ),
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_attr__('Align', 'wpmf'),
                        'description' => esc_attr__('Select the file alignment.', 'wpmf'),
                        'param_name' => 'align',
                        'value' => array(
                            'left' => esc_attr__('Left', 'wpmf'),
                            'right' => esc_attr__('Right', 'wpmf'),
                            'center' => esc_attr__('Center', 'wpmf'),
                        ),
                        'default' => 'left'
                    )
                )
            )
        )
    );

    wp_enqueue_style(
        'wpmf-avada-style',
        WPMF_PLUGIN_URL . '/assets/css/avada_style.css',
        array(),
        WPMF_VERSION
    );
}
wpmfAvadaSingleFile();
add_action('fusion_builder_before_init', 'wpmfAvadaSingleFile');
