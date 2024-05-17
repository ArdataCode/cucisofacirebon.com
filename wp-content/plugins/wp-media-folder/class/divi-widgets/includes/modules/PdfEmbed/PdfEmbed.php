<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Class WpmfPdfEmbedDivi
 */
class WpmfPdfEmbedDivi extends ET_Builder_Module
{

    /**
     * Module slug
     *
     * @var string
     */
    public $slug = 'wpmf_pdf_embed';

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
        $this->name = esc_html__('WPMF PDF Embed', 'wpmf');
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
                'data_type' => 'application/pdf',
                'option_category' => 'configuration',
                'upload_button_text' => esc_attr__('Select an PDF', 'wpmf'),
                'choose_text' => esc_attr__('Choose an PDF', 'wpmf'),
                'update_text' => esc_attr__('Set As PDF', 'wpmf'),
                'hide_metadata' => true,
                'affects' => array(
                    'alt',
                    'title_text',
                ),
                'description' => esc_html__('Upload your desired PDF, or type in the URL to the image you would like to display.', 'wpmf'),
            ),
            'embed' => array(
                'label' => esc_html__('Embed', 'wpmf'),
                'type' => 'yes_no_button',
                'option_category' => 'configuration',
                'options' => array(
                    'on' => esc_html__('On', 'wpmf'),
                    'off' => esc_html__('Off', 'wpmf'),
                ),
                'default' => 'on',
                'default_on_front' => 'on'
            ),
            'target' => array(
                'label' => esc_html__('Target', 'wpmf'),
                'type' => 'select',
                'option_category' => 'configuration',
                'options' => array(
                    '_blank' => esc_html__('New Window', 'wpmf'),
                    'self' => esc_html__('Same Window', 'wpmf'),
                ),
                'default_on_front' => 'self',
                'depends_show_if' => 'on'
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
            <div id="divi-pdf-embed-placeholder" class="divi-pdf-embed-placeholder">
                        <span class="wpmf-divi-message">
                            ' . esc_html__('Please select a PDF file to activate the preview', 'wpmf') . '
                        </span>
            </div>
          </div>';
            return $html;
        }
        $url = str_replace(array('-pdf.jpg', '-pdf.jpeg', '-pdf.png'), '.pdf', $this->props['url']);
        $width = (!empty($this->props['width'])) ? $this->props['width'] : '';
        $height = (!empty($this->props['height'])) ? $this->props['height'] : '';
        return do_shortcode('[wpmfpdf url="' . esc_url($url) . '" width="'. $width .'" height="'. $height .'" embed="' . esc_attr($this->props['embed']) . '" target="' . esc_attr($this->props['target']) . '"]');
    }
}

new WpmfPdfEmbedDivi;
