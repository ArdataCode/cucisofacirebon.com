<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Class WpmfGalleryDivi
 */
class WpmfGalleryDivi extends ET_Builder_Module
{

    /**
     * Module slug
     *
     * @var string
     */
    public $slug = 'wpmf_gallery_divi';

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
        $this->name = esc_html__('WPMF Gallery', 'wpmf');
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
        $settings = wpmfGetOption('gallery_settings');
        return array(
            'theme' => array(
                'label' => esc_html__('Theme', 'wpmf'),
                'type' => 'select',
                'option_category' => 'configuration',
                'options' => array(
                    'default' => esc_html__('Default', 'wpmf'),
                    'masonry' => esc_html__('Masonry', 'wpmf'),
                    'portfolio' => esc_html__('Portfolio', 'wpmf'),
                    'slider' => esc_html__('Slider', 'wpmf')
                ),
                'default' => 'masonry',
                'default_on_front' => 'masonry'
            ),
            'items' => array(
                'label' => esc_html__('Images', 'wpmf'),
                'type' => 'upload-gallery',
                'option_category' => 'configuration',
                'computed_affects' => array(
                    '__gallery',
                ),
                'default' => '',
                'default_on_front' => '',
            ),
            'columns' => array(
                'label' => esc_html__('Columns', 'wpmf'),
                'type' => 'range',
                'option_category' => 'configuration',
                'default' => 3,
                'default_on_front' => $settings['theme']['masonry_theme']['columns'],
                'validate_unit'    => false,
                'unitless'         => true,
                'range_settings' => array(
                    'min' => 1,
                    'max' => 8,
                    'step' => 1
                )
            ),
            'size' => array(
                'label' => esc_html__('Image Size', 'wpmf'),
                'type' => 'select',
                'option_category' => 'configuration',
                'options' => apply_filters('image_size_names_choose', array(
                    'thumbnail' => __('Thumbnail', 'wpmf'),
                    'medium' => __('Medium', 'wpmf'),
                    'large' => __('Large', 'wpmf'),
                    'full' => __('Full Size', 'wpmf'),
                )),
                'default' => $settings['theme']['masonry_theme']['size'],
                'default_on_front' => $settings['theme']['masonry_theme']['size']
            ),
            'targetsize' => array(
                'label' => esc_html__('Lightbox Size', 'wpmf'),
                'type' => 'select',
                'option_category' => 'configuration',
                'options' => apply_filters('image_size_names_choose', array(
                    'thumbnail' => __('Thumbnail', 'wpmf'),
                    'medium' => __('Medium', 'wpmf'),
                    'large' => __('Large', 'wpmf'),
                    'full' => __('Full Size', 'wpmf'),
                )),
                'default' => $settings['theme']['masonry_theme']['targetsize'],
                'default_on_front' => $settings['theme']['masonry_theme']['targetsize']
            ),
            'action' => array(
                'label' => esc_html__('Action On Click', 'wpmf'),
                'type' => 'select',
                'option_category' => 'configuration',
                'options' => array(
                    'file' => esc_html__('Lightbox', 'wpmf'),
                    'post' => esc_html__('Attachment Page', 'wpmf'),
                    'none' => esc_html__('None', 'wpmf'),
                ),
                'default' => $settings['theme']['masonry_theme']['link'],
                'default_on_front' => $settings['theme']['masonry_theme']['link']
            ),
            'orderby' => array(
                'label' => esc_html__('Order by', 'wpmf'),
                'type' => 'select',
                'option_category' => 'configuration',
                'options' => array(
                    'post__in' => esc_html__('Custom', 'wpmf'),
                    'rand' => esc_html__('Random', 'wpmf'),
                    'title' => esc_html__('Title', 'wpmf'),
                    'date' => esc_html__('Date', 'wpmf')
                ),
                'default' => $settings['theme']['masonry_theme']['orderby'],
                'default_on_front' => $settings['theme']['masonry_theme']['orderby']
            ),
            'order' => array(
                'label' => esc_html__('Order', 'wpmf'),
                'type' => 'select',
                'option_category' => 'configuration',
                'options' => array(
                    'ASC' => esc_html__('Ascending', 'wpmf'),
                    'DESC' => esc_html__('Descending', 'wpmf')
                ),
                'default' => $settings['theme']['masonry_theme']['order'],
                'default_on_front' => $settings['theme']['masonry_theme']['order']
            ),
            'gutterwidth' => array(
                'label' => esc_html__('Gutter', 'wpmf'),
                'type' => 'range',
                'option_category' => 'configuration',
                'default' => 5,
                'default_on_front' => 5,
                'validate_unit'    => false,
                'unitless'         => true,
                'range_settings' => array(
                    'min' => 0,
                    'max' => 100,
                    'step' => 5
                )
            ),
            'border_radius' => array(
                'label' => esc_html__('Border Radius', 'wpmf'),
                'type' => 'range',
                'option_category' => 'configuration',
                'default' => 0,
                'default_on_front' => 0,
                'validate_unit'    => false,
                'unitless'         => true,
                'range_settings' => array(
                    'min' => 0,
                    'max' => 20,
                    'step' => 1
                )
            ),
            'border_style' => array(
                'label' => esc_html__('Border Type', 'wpmf'),
                'type' => 'select',
                'option_category' => 'configuration',
                'options' => array(
                    'solid' => esc_html__('Solid', 'wpmf'),
                    'double' => esc_html__('Double', 'wpmf'),
                    'dotted' => esc_html__('Dotted', 'wpmf'),
                    'dashed' => esc_html__('Dashed', 'wpmf'),
                    'groove' => esc_html__('Groove', 'wpmf')
                ),
                'default' => 'solid',
                'default_on_front' => 'solid'
            ),
            'border_width' => array(
                'label' => esc_html__('Border Width', 'wpmf'),
                'type' => 'range',
                'option_category' => 'configuration',
                'default' => 0,
                'default_on_front' => 0,
                'validate_unit'    => false,
                'unitless'         => true,
                'range_settings' => array(
                    'min' => 0,
                    'max' => 30,
                    'step' => 1
                )
            ),
            'border_color' => array(
                'label' => esc_html__('Border Color', 'wpmf'),
                'type' => 'color-alpha',
                'custom_color' => true,
                'default' => '#cccccc',
                'default_on_front' => '#cccccc'
            ),
            'enable_shadow' => array(
                'label' => esc_html__('Enable Shadow', 'wpmf'),
                'type' => 'yes_no_button',
                'option_category' => 'configuration',
                'options' => array(
                    'on' => esc_html__('On', 'wpmf'),
                    'off' => esc_html__('Off', 'wpmf'),
                ),
                'default' => 'off',
                'default_on_front' => 'off'
            ),
            'shadow_color' => array(
                'label' => esc_html__('Shadow Color', 'wpmf'),
                'type' => 'color-alpha',
                'custom_color' => true,
                'default' => '#cccccc',
                'default_on_front' => '#cccccc'
            ),
            'shadow_horizontal' => array(
                'label' => esc_html__('Horizontal', 'wpmf'),
                'type' => 'range',
                'option_category' => 'configuration',
                'validate_unit' => true,
                'default' => '0px',
                'default_unit' => 'px',
                'default_on_front' => '0px',
                'range_settings' => array(
                    'min' => '-50',
                    'max' => '50',
                    'step' => '1'
                )
            ),
            'shadow_vertical' => array(
                'label' => esc_html__('Vertical', 'wpmf'),
                'type' => 'range',
                'option_category' => 'configuration',
                'validate_unit' => true,
                'default' => '0px',
                'default_unit' => 'px',
                'default_on_front' => '0px',
                'range_settings' => array(
                    'min' => '-50',
                    'max' => '50',
                    'step' => '1'
                )
            ),
            'shadow_blur' => array(
                'label' => esc_html__('Blur', 'wpmf'),
                'type' => 'range',
                'option_category' => 'configuration',
                'validate_unit' => true,
                'default' => '0px',
                'default_unit' => 'px',
                'default_on_front' => '0px',
                'range_settings' => array(
                    'min' => '0',
                    'max' => '50',
                    'step' => '1'
                )
            ),
            'shadow_spread' => array(
                'label' => esc_html__('Spread', 'wpmf'),
                'type' => 'range',
                'option_category' => 'configuration',
                'validate_unit' => true,
                'default' => '0px',
                'default_unit' => 'px',
                'default_on_front' => '0px',
                'range_settings' => array(
                    'min' => '0',
                    'max' => '50',
                    'step' => '1'
                )
            ),
            'gallery_folders' => array(
                'label' => esc_html__('Gallery From Folder', 'wpmf'),
                'type' => 'yes_no_button',
                'option_category' => 'configuration',
                'options' => array(
                    'on' => esc_html__('On', 'wpmf'),
                    'off' => esc_html__('Off', 'wpmf'),
                ),
                'default' => 'off',
                'default_on_front' => 'off'
            ),
            'gallery_folder_id' => array(
                'label' => esc_html__('Choose a Folder', 'wpmf'),
                'type' => 'text',
                'option_category' => 'configuration',
                'default' => 0,
                'default_on_front' => 0
            )
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
        $gallery_folders = (!empty($this->props['gallery_folders']) && $this->props['gallery_folders'] === 'on') ? 1 : 0;
        if (!empty($this->props['enable_shadow']) && $this->props['enable_shadow'] === 'on') {
            $img_shadow = $this->props['shadow_horizontal'] . ' ' . $this->props['shadow_vertical'] . ' ' . $this->props['shadow_blur'] . ' ' . $this->props['shadow_spread'] . ' ' . $this->props['shadow_color'];
        } else {
            $img_shadow = '';
        }
        if (empty($this->props['items']) && empty($this->props['gallery_folder_id'])) {
            $html = '<div class="wpmf-divi-container">
            <div id="divi-gallery-placeholder" class="divi-gallery-placeholder">
                        <span class="wpmf-divi-message">
                            ' . esc_html__('Please add some images to the gallery to activate the preview', 'wpmf') . '
                        </span>
            </div>
          </div>';
            return $html;
        }

        return do_shortcode('[wpmf_gallery is_divi="1" include="'. esc_attr($this->props['items']) .'" display="' . esc_attr($this->props['theme']) . '" columns="' . esc_attr($this->props['columns']) . '" size="' . esc_attr($this->props['size']) . '" targetsize="' . esc_attr($this->props['targetsize']) . '" link="' . esc_attr($this->props['action']) . '" wpmf_orderby="' . esc_attr($this->props['orderby']) . '" wpmf_order="' . esc_attr($this->props['order']) . '" gutterwidth="' . esc_attr($this->props['gutterwidth']) . '" border_width="' . esc_attr($this->props['border_width']) . '" border_style="' . esc_attr($this->props['border_style']) . '" border_color="' . esc_attr($this->props['border_color']) . '" img_shadow="' . esc_attr($img_shadow) . '" img_border_radius="' . esc_attr($this->props['border_radius']) . '" wpmf_autoinsert="' . esc_attr($gallery_folders) . '" wpmf_folder_id="' . esc_attr($this->props['gallery_folder_id']) . '"]');
    }
}

new WpmfGalleryDivi;
