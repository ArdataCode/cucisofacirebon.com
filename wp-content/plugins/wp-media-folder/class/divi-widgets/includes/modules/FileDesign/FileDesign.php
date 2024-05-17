<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Class WpmfPdfEmbedDivi
 */
class WpmfFileDesignDivi extends ET_Builder_Module
{

    /**
     * Module slug
     *
     * @var string
     */
    public $slug = 'wpmf_file_design';

    /**
     * Whether module support visual builder. e.g `on` or `off`.
     *
     * @var string
     */
    public $vb_support = 'on';

    /**
     * Credits of all custom modules.
     *
     * @var array
     */
    protected $module_credits = array(
        'module_uri' => 'https://www.joomunited.com/',
        'author' => 'Joomunited',
        'author_uri' => 'https://www.joomunited.com/',
    );

    /**
     * Init
     *
     * @return void
     */
    public function init()
    {
        $this->name = esc_html__('WPMF media download', 'wpmf');
    }

    /**
     * Advanced Fields Config
     *
     * @return array
     */
    public function get_advanced_fields_config() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Method extends from ET_Builder_Module class
    {
        return array(
            'button'       => false,
            'link_options' => false
        );
    }

    /**
     * Get the settings fields data for this element.
     *
     * @return array
     */
    public function get_fields() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Method extends from ET_Builder_Module class
    {
        return array(
            'url' => array(
                'type' => 'upload',
                'data_type' => array('application/*', 'video', 'audio', 'text'),
                'option_category' => 'configuration',
                'upload_button_text' => esc_attr__('Upload a File', 'wpmf'),
                'choose_text' => esc_attr__('Choose a File', 'wpmf'),
                'update_text' => esc_attr__('Set As File', 'wpmf'),
                'hide_metadata' => true,
                'affects' => array(
                    'alt',
                    'title_text',
                ),
                'description' => esc_html__('Upload your desired file, or type in the URL to the image you would like to display.', 'wpmf'),
            ),
            'align' => array(
                'label' => esc_html__('File Alignment', 'wpmf'),
                'type' => 'text_align',
                'option_category' => 'layout',
                'options' => et_builder_get_text_orientation_options(array('justified')),
                'default_on_front' => 'left',
                'tab_slug' => 'advanced',
                'toggle_slug' => 'alignment',
                'description' => esc_html__('Here you can choose the image alignment.', 'wpmf'),
                'options_icon' => 'module_align',
            ),
        );
    }

    /**
     * Render content
     *
     * @param array  $attrs       List of attributes.
     * @param string $content     Content being processed.
     * @param string $render_slug Slug of module that is used for rendering output.
     *
     * @return string
     */
    public function render($attrs, $content, $render_slug) // phpcs:ignore PEAR.Functions.ValidDefaultValue.NotAtEnd -- Method extends from ET_Builder_Module class
    {
        if (empty($this->props['url'])) {
            $html = '<div class="wpmf-divi-container">
            <div id="divi-file-design-placeholder" class="divi-file-design-placeholder">
                        <span class="wpmf-divi-message">
                            ' . esc_html__('Please select a PDF file to activate the preview', 'wpmf') . '
                        </span>
            </div>
          </div>';
            return $html;
        }
        return do_shortcode('[wpmffiledesign url="' . esc_attr($this->props['url']) . '" align="' . esc_attr($this->props['align']) . '"]');
    }
}

new WpmfFileDesignDivi;
