<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
if (fusion_is_element_enabled('wpmf_avada_pdf_embed')) {
    if (!class_exists('WpmfAvadaPdfEmbedClass')) {
        /**
         * Fusion PDF Embed shortcode class.
         */
        class WpmfAvadaPdfEmbedClass extends Fusion_Element
        {
            /**
             * Constructor.
             */
            public function __construct()
            {
                parent::__construct();
                add_shortcode('wpmf_avada_pdf_embed', array($this, 'render'));
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
            <div id="avada-pdf-embed-placeholder" class="avada-pdf-embed-placeholder">
                        <span class="wpmf-avada-message">
                            ' . esc_html__('Please select a PDF file to activate the preview', 'wpmf') . '
                        </span>
            </div>
          </div>';
                } else {
                    $embed = (!empty($args['embed'])) ? $args['embed'] : 'on';
                    $target = (!empty($args['target'])) ? $args['target'] : 'self';
                    $html = do_shortcode('[wpmfpdf url="' . esc_url($args['url']) . '" embed="' . esc_attr($embed) . '" target="' . esc_attr($target) . '"]');
                }
                return apply_filters('wpmfAvadaPdfEmbed_content', $html, $args);
            }
        }

    }

    new WpmfAvadaPdfEmbedClass();
}

/**
 * Map shortcode to Avada Builder.
 *
 * @return void
 */
function wpmfAvadaPdfEmbed()
{
    if (!function_exists('fusion_builder_frontend_data')) {
        return;
    }
    fusion_builder_map(
        fusion_builder_frontend_data(
            'WpmfAvadaPdfEmbedClass',
            array(
                'name' => esc_attr__('WPMF PDF Embed', 'wpmf'),
                'shortcode' => 'wpmf_avada_pdf_embed',
                'icon' => 'wpmf-avada-icon wpmf-avada-pdf-embed-icon',
                'custom_settings_view_js' => WPMF_PLUGIN_URL . 'assets/js/avada/avada_pdfembed_script.js',
                'front_end_custom_settings_view_js' => WPMF_PLUGIN_URL . 'assets/js/avada/avada_pdfembed_script.js',
                'preview' => WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/avada-widgets/templates/pdf-embed.php',
                'preview_id' => 'fusion-builder-wpmf-pdf-embed-preview-template',
                'allow_generator' => true,
                'inline_editor' => true,
                'params' => array(
                    array(
                        'type' => 'wpmf_pdf_embed',
                        'heading' => esc_html__('Select a file', 'wpmf'),
                        'description' => esc_html__('Select a PDF file to display.', 'wpmf'),
                        'param_name' => 'pdf_embed_button'
                    ),
                    array(
                        'type' => 'textfield',
                        'heading' => esc_html__('URL', 'wpmf'),
                        'description' => esc_html__('Add a PDF URL to display.', 'wpmf'),
                        'param_name' => 'url',
                        'value' => ''
                    ),
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_attr__('Embed', 'wpmf'),
                        'description' => esc_html__('When enabling this option you will have the possibility to embed PDF file.', 'wpmf'),
                        'param_name' => 'embed',
                        'value' => array(
                            'on' => esc_attr__('On', 'wpmf'),
                            'off' => esc_attr__('Off', 'wpmf'),
                        ),
                        'default' => 'on'
                    ),
                    array(
                        'type' => 'radio_button_set',
                        'heading' => esc_attr__('Target', 'wpmf'),
                        'description' => '',
                        'param_name' => 'target',
                        'value' => array(
                            '_blank' => esc_attr__('New Window', 'wpmf'),
                            'self' => esc_attr__('Same Window', 'wpmf'),
                        ),
                        'default' => 'self',
                        'dependency' => array(
                            array(
                                'element' => 'embed',
                                'value' => 'off',
                                'operator' => '==',
                            ),
                        ),
                    ),
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

    wp_enqueue_style(
        'pdfemba_embed_pdf_css',
        WPMF_PLUGIN_URL . 'assets/css/pdfemb-embed-pdf.css'
    );
}

wpmfAvadaPdfEmbed();
add_action('fusion_builder_before_init', 'wpmfAvadaPdfEmbed');
