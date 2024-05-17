<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class WpmfGalleryAddonElementorWidget
 */
class WpmfGalleryAddonElementorWidget extends \Elementor\Widget_Base
{
    /**
     * Get script depends
     *
     * @return array
     */
    public function get_script_depends() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Method extends from \Elementor\Widget_Base class
    {
        return array(
            'wordpresscanvas-imagesloaded',
            'wpmf-gallery-popup',
            'jquery-masonry',
            'wpmf-justified-script',
            'wpmf-slick-script',
            'wpmf-flipster-js'
        );
    }

    /**
     * Get style depends
     *
     * @return array
     */
    public function get_style_depends() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Method extends from \Elementor\Widget_Base class
    {
        return array(
            'wpmf-material-icon',
            'wpmf-gallery-css',
            'wpmf-slick-style',
            'wpmf-slick-theme-style',
            'wpmf-justified-style',
            'wpmf-flipster-css',
            'wpmf-gallery-popup-style',
            'wpmf-gallery-style'
        );
    }

    /**
     * Get widget name.
     *
     * Retrieve Gallery widget name.
     *
     * @return string Widget name.
     */
    public function get_name() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Method extends from \Elementor\Widget_Base class
    {
        return 'wpmf_gallery_addon';
    }

    /**
     * Get widget title.
     *
     * Retrieve Gallery widget title.
     *
     * @return string Widget title.
     */
    public function get_title() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Method extends from \Elementor\Widget_Base class
    {
        return esc_html__('WP Media Folder Gallery Addon', 'wp-media-folder-gallery-addon');
    }

    /**
     * Get widget icon.
     *
     * Retrieve Gallery widget icon.
     *
     * @return string Widget icon.
     */
    public function get_icon() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Method extends from \Elementor\Widget_Base class
    {
        return 'fa wpmf-gallery-addon-elementor-icon';
    }

    /**
     * Get widget categories.
     *
     * Retrieve the list of categories the Gallery widget belongs to.
     *
     * @return array Widget categories.
     */
    public function get_categories() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Method extends from \Elementor\Widget_Base class
    {
        return array('wpmf');
    }

    /**
     * Register Gallery widget controls.
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     *
     * @return void
     */
    protected function register_controls() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps, PSR2.Methods.MethodDeclaration.Underscore -- Method extends from \Elementor\Widget_Base class
    {
        $settings = get_option('wpmf_gallery_settings');
        $this->start_controls_section(
            'gallery_settings',
            array(
                'label' => esc_html__('Gallery Settings', 'wp-media-folder-gallery-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT
            )
        );

        $this->add_control(
            'wpmf_gallery_navigation',
            array(
                'label' => esc_html__('Gallery Navigation', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Enable', 'wp-media-folder-gallery-addon'),
                'label_off' => __('Disable', 'wp-media-folder-gallery-addon'),
                'return_value' => 'yes',
                'default' => 'no'
            )
        );

        $this->add_control(
            'wpmf_gallery_image_tags',
            array(
                'label' => esc_html__('Display Images Tags', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Enable', 'wp-media-folder-gallery-addon'),
                'label_off' => __('Disable', 'wp-media-folder-gallery-addon'),
                'return_value' => 'yes',
                'default' => 'no'
            )
        );

        $this->add_control(
            'wpmf_theme',
            array(
                'label' => esc_html__('Theme', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'default' => esc_html__('Default', 'wp-media-folder-gallery-addon'),
                    'masonry' => esc_html__('Masonry', 'wp-media-folder-gallery-addon'),
                    'portfolio' => esc_html__('Portfolio', 'wp-media-folder-gallery-addon'),
                    'slider' => esc_html__('Slider', 'wp-media-folder-gallery-addon'),
                    'flowslide' => esc_html__('Flow slide', 'wp-media-folder-gallery-addon'),
                    'square_grid' => esc_html__('Square grid', 'wp-media-folder-gallery-addon'),
                    'material' => esc_html__('Material', 'wp-media-folder-gallery-addon'),
                    'custom_grid' => esc_html__('Custom grid', 'wp-media-folder-gallery-addon')
                ),
                'default' => 'masonry'
            )
        );

        $this->add_control(
            'layout',
            array(
                'label' => esc_html__('Layout', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'vertical' => esc_html__('Vertical', 'wp-media-folder-gallery-addon'),
                    'horizontal' => esc_html__('Horizontal', 'wp-media-folder-gallery-addon')
                ),
                'default' => 'vertical',
                'condition' => array(
                    'wpmf_theme' => array('masonry')
                )
            )
        );

        $this->add_control(
            'row_height',
            array(
                'label' => esc_html__('Row height', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 200,
                'min' => 50,
                'max' => 500,
                'step' => 1,
                'condition' => array(
                    'wpmf_theme' => array('masonry'),
                    'layout' => array('horizontal')
                )
            )
        );

        $this->add_control(
            'aspect_ratio',
            array(
                'label' => esc_html__('Aspect ratio', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
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
                'default' => 'default',
                'condition' => array(
                    'wpmf_theme' => array('default', 'square_grid', 'material', 'slider', 'portfolio')
                )
            )
        );

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
        $galleries_list[0] = esc_html__('Select a gallery', 'wp-media-folder-gallery-addon');
        foreach ($galleries as $gallery) {
            $galleries_list[$gallery->term_id] = str_repeat('&nbsp;&nbsp;', $gallery->depth) . $gallery->name;
        }

        $this->add_control(
            'wpmf_gallery_id',
            array(
                'label' => esc_html__('Choose a Gallery', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $galleries_list,
                'default' => 0
            )
        );

        $this->add_control(
            'wpmf_gallery_columns',
            array(
                'label' => esc_html__('Columns', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => $settings['theme']['masonry_theme']['columns'],
                'min' => 1,
                'max' => 8,
                'step' => 1
            )
        );

        $this->add_control(
            'wpmf_gallery_number_lines',
            array(
                'label' => esc_html__('Slider Line numbers', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 1,
                'min' => 1,
                'max' => 3,
                'step' => 1,
                'condition' => array(
                    'wpmf_theme' => array('slider')
                )
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Image_Size::get_type(),
            array(
                'name' => 'wpmf_gallery_size',
                'exclude' => array('custom'),
                'default' => $settings['theme']['masonry_theme']['size']
            )
        );

        $this->add_control(
            'wpmf_gallery_crop_image',
            array(
                'label' => esc_html__('Crop Image', 'wp-media-folder-gallery-addon'),
                'description' => esc_html__('Only use for slider theme', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Enable', 'wp-media-folder-gallery-addon'),
                'label_off' => __('Disable', 'wp-media-folder-gallery-addon'),
                'return_value' => 'yes',
                'default' => 'yes'
            )
        );

        $this->add_control(
            'wpmf_gallery_targetsize',
            array(
                'label' => esc_html__('Lightbox Size', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => apply_filters('image_size_names_choose', array(
                    'thumbnail' => __('Thumbnail', 'wp-media-folder-gallery-addon'),
                    'medium' => __('Medium', 'wp-media-folder-gallery-addon'),
                    'large' => __('Large', 'wp-media-folder-gallery-addon'),
                    'full' => __('Full Size', 'wp-media-folder-gallery-addon'),
                )),
                'default' => $settings['theme']['masonry_theme']['targetsize']
            )
        );

        $this->add_control(
            'wpmf_gallery_action',
            array(
                'label' => esc_html__('Action On Click', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'file' => esc_html__('Lightbox', 'wp-media-folder-gallery-addon'),
                    'post' => esc_html__('Attachment Page', 'wp-media-folder-gallery-addon'),
                    'none' => esc_html__('None', 'wp-media-folder-gallery-addon'),
                ),
                'default' => $settings['theme']['masonry_theme']['link']
            )
        );

        $this->add_control(
            'wpmf_gallery_orderby',
            array(
                'label' => esc_html__('Orderby', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'post__in' => esc_html__('Custom', 'wp-media-folder-gallery-addon'),
                    'rand' => esc_html__('Random', 'wp-media-folder-gallery-addon'),
                    'title' => esc_html__('Title', 'wp-media-folder-gallery-addon'),
                    'date' => esc_html__('Date', 'wp-media-folder-gallery-addon')
                ),
                'default' => $settings['theme']['masonry_theme']['orderby']
            )
        );

        $this->add_control(
            'wpmf_gallery_order',
            array(
                'label' => esc_html__('Order', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'ASC' => esc_html__('Ascending', 'wp-media-folder-gallery-addon'),
                    'DESC' => esc_html__('Descending', 'wp-media-folder-gallery-addon')
                ),
                'default' => $settings['theme']['masonry_theme']['order']
            )
        );

        $this->end_controls_section();

        // margin tab
        $this->start_controls_section(
            'wpmf_gallery_margin',
            array(
                'label' => esc_html__('Margin', 'wp-media-folder-gallery-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT
            )
        );

        $this->add_control(
            'wpmf_gallery_gutterwidth',
            array(
                'label' => esc_html__('Gutter', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    '0' => 0,
                    '5' => 5,
                    '10' => 10,
                    '15' => 15,
                    '20' => 20,
                    '25' => 25,
                    '30' => 30,
                    '35' => 35,
                    '40' => 40,
                    '45' => 45,
                    '100' => 100,
                ),
                'default' => 5
            )
        );

        $this->end_controls_section();

        // border tab
        $this->start_controls_section(
            'wpmf_gallery_border',
            array(
                'label' => esc_html__('Border', 'wp-media-folder-gallery-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT
            )
        );

        $this->add_control(
            'wpmf_gallery_image_radius',
            array(
                'label' => esc_html__('Border Radius', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 0,
                'min' => 0,
                'max' => 20,
                'step' => 1
            )
        );

        $this->add_control(
            'wpmf_gallery_border_type',
            array(
                'label' => esc_html__('Border Type', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'solid' => esc_html__('Solid', 'wp-media-folder-gallery-addon'),
                    'double' => esc_html__('Double', 'wp-media-folder-gallery-addon'),
                    'dotted' => esc_html__('Dotted', 'wp-media-folder-gallery-addon'),
                    'dashed' => esc_html__('Dashed', 'wp-media-folder-gallery-addon'),
                    'groove' => esc_html__('Groove', 'wp-media-folder-gallery-addon')
                ),
                'default' => 'solid'
            )
        );

        $this->add_control(
            'wpmf_gallery_border_width',
            array(
                'label' => esc_html__('Border Width', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 0,
                'min' => 0,
                'max' => 30,
                'step' => 1
            )
        );

        $this->add_control(
            'wpmf_gallery_border_color',
            array(
                'label' => esc_html__('Border Color', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#cccccc'
            )
        );

        $this->end_controls_section();

        // shadow tab
        $this->start_controls_section(
            'wpmf_gallery_shadow',
            array(
                'label' => esc_html__('Shadow', 'wp-media-folder-gallery-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT
            )
        );

        $this->add_control(
            'wpmf_gallery_enable_shadow',
            array(
                'label' => esc_html__('Enable', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Enable', 'wp-media-folder-gallery-addon'),
                'label_off' => __('Disable', 'wp-media-folder-gallery-addon'),
                'return_value' => 'yes',
                'default' => 'no'
            )
        );

        $this->add_control(
            'wpmf_gallery_shadow_color',
            array(
                'label' => esc_html__('Color', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#cccccc'
            )
        );

        $this->add_control(
            'wpmf_gallery_shadow_horizontal',
            array(
                'label' => esc_html__('Horizontal', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range' => array(
                    'px' => array(
                        'min' => -50,
                        'max' => 50,
                        'step' => 1
                    )
                ),
                'default' => array(
                    'unit' => 'px',
                    'size' => 0
                )
            )
        );

        $this->add_control(
            'wpmf_gallery_shadow_vertical',
            array(
                'label' => esc_html__('Vertical', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range' => array(
                    'px' => array(
                        'min' => -50,
                        'max' => 50,
                        'step' => 1
                    )
                ),
                'default' => array(
                    'unit' => 'px',
                    'size' => 0
                )
            )
        );

        $this->add_control(
            'wpmf_gallery_shadow_blur',
            array(
                'label' => esc_html__('Blur', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range' => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 50,
                        'step' => 1
                    )
                ),
                'default' => array(
                    'unit' => 'px',
                    'size' => 0
                )
            )
        );

        $this->add_control(
            'wpmf_gallery_shadow_spread',
            array(
                'label' => esc_html__('Spread', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range' => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 50,
                        'step' => 1
                    )
                ),
                'default' => array(
                    'unit' => 'px',
                    'size' => 0
                )
            )
        );

        $this->end_controls_section();

        // shadow tab
        $this->start_controls_section(
            'wpmf_gallery_hover',
            array(
                'label' => esc_html__('Hover', 'wp-media-folder-gallery-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT
            )
        );

        $this->add_control(
            'wpmf_gallery_hover_color',
            array(
                'label' => esc_html__('Hover Color', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#000'
            )
        );

        $this->add_control(
            'wpmf_gallery_hover_opacity',
            array(
                'label' => esc_html__('Hover Opacity', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 0.4,
                'min' => 0,
                'max' => 1,
                'step' => 0.1
            )
        );

        $this->add_control(
            'wpmf_gallery_hover_title_position',
            array(
                'label' => esc_html__('Title Position', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'none' => esc_html__('None', 'wp-media-folder-gallery-addon'),
                    'top_left' => esc_html__('Top left', 'wp-media-folder-gallery-addon'),
                    'top_right' => esc_html__('Top right', 'wp-media-folder-gallery-addon'),
                    'top_center' => esc_html__('Top center', 'wp-media-folder-gallery-addon'),
                    'bottom_left' => esc_html__('Bottom left', 'wp-media-folder-gallery-addon'),
                    'bottom_right' => esc_html__('Bottom right', 'wp-media-folder-gallery-addon'),
                    'bottom_center' => esc_html__('Bottom center', 'wp-media-folder-gallery-addon'),
                    'center_center' => esc_html__('Center center', 'wp-media-folder-gallery-addon')
                ),
                'default' => 'center_center'
            )
        );

        $this->add_control(
            'wpmf_gallery_hover_title_size',
            array(
                'label' => esc_html__('Title Size', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 16,
                'min' => 0,
                'max' => 150,
                'step' => 1
            )
        );

        $this->add_control(
            'wpmf_gallery_hover_title_color',
            array(
                'label' => esc_html__('Title Color', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#fff'
            )
        );

        $this->add_control(
            'wpmf_gallery_hover_desc_position',
            array(
                'label' => esc_html__('Description Position', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'none' => esc_html__('None', 'wp-media-folder-gallery-addon'),
                    'top_left' => esc_html__('Top left', 'wp-media-folder-gallery-addon'),
                    'top_right' => esc_html__('Top right', 'wp-media-folder-gallery-addon'),
                    'top_center' => esc_html__('Top center', 'wp-media-folder-gallery-addon'),
                    'bottom_left' => esc_html__('Bottom left', 'wp-media-folder-gallery-addon'),
                    'bottom_right' => esc_html__('Bottom right', 'wp-media-folder-gallery-addon'),
                    'bottom_center' => esc_html__('Bottom center', 'wp-media-folder-gallery-addon'),
                    'center_center' => esc_html__('Center center', 'wp-media-folder-gallery-addon')
                ),
                'default' => 'none'
            )
        );

        $this->add_control(
            'wpmf_gallery_hover_desc_size',
            array(
                'label' => esc_html__('Description Size', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 14,
                'min' => 0,
                'max' => 150,
                'step' => 1
            )
        );

        $this->add_control(
            'wpmf_gallery_hover_desc_color',
            array(
                'label' => esc_html__('Description Color', 'wp-media-folder-gallery-addon'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#fff'
            )
        );

        $this->end_controls_section();
    }

    /**
     * Render Gallery widget output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.
     *
     * @return void|string
     */
    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $gallery_id = (!empty($settings['wpmf_gallery_id'])) ? $settings['wpmf_gallery_id'] : 0;
        $theme = (!empty($settings['wpmf_theme'])) ? $settings['wpmf_theme'] : 'default';
        $layout = (!empty($settings['layout'])) ? $settings['layout'] : 'vertical';
        $row_height = (!empty($settings['row_height'])) ? $settings['row_height'] : 150;
        $aspect_ratio = (!empty($settings['aspect_ratio'])) ? $settings['aspect_ratio'] : 'default';

        $columns = (!empty($settings['wpmf_gallery_columns'])) ? $settings['wpmf_gallery_columns'] : 3;
        $number_lines = (!empty($settings['wpmf_gallery_number_lines'])) ? $settings['wpmf_gallery_number_lines'] : 1;
        $size = (!empty($settings['wpmf_gallery_size_size'])) ? $settings['wpmf_gallery_size_size'] : 'thumbnail';
        $crop_image = (!empty($settings['wpmf_gallery_crop_image']) && $settings['wpmf_gallery_crop_image'] === 'yes') ? 1 : 0;

        $targetsize = (!empty($settings['wpmf_gallery_targetsize'])) ? $settings['wpmf_gallery_targetsize'] : 'large';
        $action = (!empty($settings['wpmf_gallery_action'])) ? $settings['wpmf_gallery_action'] : 'file';
        $orderby = (!empty($settings['wpmf_gallery_orderby'])) ? $settings['wpmf_gallery_orderby'] : 'post__in';
        $order = (!empty($settings['wpmf_gallery_order'])) ? $settings['wpmf_gallery_order'] : 'ASC';
        $gutterwidth = (isset($settings['wpmf_gallery_gutterwidth'])) ? $settings['wpmf_gallery_gutterwidth'] : 5;

        $border_radius = (!empty($settings['wpmf_gallery_image_radius'])) ? $settings['wpmf_gallery_image_radius'] : 0;
        $border_style = (!empty($settings['wpmf_gallery_border_type'])) ? $settings['wpmf_gallery_border_type'] : 'solid';
        $border_color = (!empty($settings['wpmf_gallery_border_color'])) ? $settings['wpmf_gallery_border_color'] : 'transparent';
        $border_width = (!empty($settings['wpmf_gallery_border_width'])) ? $settings['wpmf_gallery_border_width'] : 0;
        $enable_gallery_shadow = (!empty($settings['wpmf_gallery_enable_shadow']) && $settings['wpmf_gallery_enable_shadow'] === 'yes') ? 1 : 0;
        $shadow_horizontal = !empty($settings['wpmf_gallery_shadow_horizontal']) ? $settings['wpmf_gallery_shadow_horizontal'] : 0;
        $shadow_vertical = !empty($settings['wpmf_gallery_shadow_vertical']) ? $settings['wpmf_gallery_shadow_vertical'] : 0;
        $shadow_blur = !empty($settings['wpmf_gallery_shadow_blur']) ? $settings['wpmf_gallery_shadow_blur'] : 0;
        $shadow_spread = !empty($settings['wpmf_gallery_shadow_spread']) ? $settings['wpmf_gallery_shadow_spread'] : 0;
        $shadow_color = (!empty($settings['wpmf_gallery_shadow_color'])) ? $settings['wpmf_gallery_shadow_color'] : '#cccccc';
        if (!empty($enable_gallery_shadow)) {
            $img_shadow = $shadow_horizontal['size'] . 'px ' . $shadow_vertical['size'] . 'px ' . $shadow_blur['size'] . 'px ' . $shadow_spread['size'] . 'px ' . $shadow_color;
        } else {
            $img_shadow = '';
        }

        $gallery_navigation = (!empty($settings['wpmf_gallery_navigation']) && $settings['wpmf_gallery_navigation'] === 'yes') ? 1 : 0;
        $gallery_image_tags = (!empty($settings['wpmf_gallery_image_tags']) && $settings['wpmf_gallery_image_tags'] === 'yes') ? 1 : 0;

        $hover_color = (!empty($settings['wpmf_gallery_hover_color'])) ? $settings['wpmf_gallery_hover_color'] : '#000';
        $hover_opacity = (!empty($settings['wpmf_gallery_hover_opacity'])) ? $settings['wpmf_gallery_hover_opacity'] : 0.4;
        $hover_title_position = (!empty($settings['wpmf_gallery_hover_title_position'])) ? $settings['wpmf_gallery_hover_title_position'] : 'center_center';
        $hover_title_size = (!empty($settings['wpmf_gallery_hover_title_size'])) ? $settings['wpmf_gallery_hover_title_size'] : 16;
        $hover_title_color = (!empty($settings['wpmf_gallery_hover_title_color'])) ? $settings['wpmf_gallery_hover_title_color'] : '#fff';
        $hover_desc_position = (!empty($settings['wpmf_gallery_hover_desc_position'])) ? $settings['wpmf_gallery_hover_desc_position'] : 'none';
        $hover_desc_size = (!empty($settings['wpmf_gallery_hover_desc_size'])) ? $settings['wpmf_gallery_hover_desc_size'] : 14;
        $hover_desc_color = (!empty($settings['wpmf_gallery_hover_desc_color'])) ? $settings['wpmf_gallery_hover_desc_color'] : '#fff';
        if (is_admin()) {
            $style = '';
            switch ($theme) {
                case 'default':
                case 'masonry':
                case 'portfolio':
                case 'square_grid':
                case 'custom_grid':
                    if ($img_shadow !== '') {
                        $style .= '.elementor-element-' . $this->get_id() . ' .wpmf-gallery-item img:not(.glrsocial_image):hover, .elementor-element-' . $this->get_id() . ' .wpmf-gallery-item .wpmf_overlay {box-shadow: ' . $img_shadow . ' !important; transition: all 200ms ease;}';
                    }

                    if ($border_style !== 'none') {
                        $style .= '.elementor-element-' . $this->get_id() . ' .wpmf-gallery-item img:not(.glrsocial_image) {border: ' . $border_color . ' ' . $border_width . 'px ' . $border_style . '}';
                    }
                    break;
                case 'flowslide':
                    if ($img_shadow !== '') {
                        $style .= '.elementor-element-' . $this->get_id() . ' .wpmf-gallery-item.flipster__item--current img:not(.glrsocial_image):hover, .elementor-element-' . $this->get_id() . ' .wpmf-gallery-item.flipster__item--current .wpmf_overlay {box-shadow: ' . $img_shadow . ' !important; transition: all 200ms ease;}';
                    }

                    if ($border_style !== 'none') {
                        $style .= '.elementor-element-' . $this->get_id() . ' .wpmf-gallery-item img:not(.glrsocial_image) {border: ' . $border_color . ' ' . $border_width . 'px ' . $border_style . '}';
                    }
                    break;
                case 'slider':
                    if ($img_shadow !== '') {
                        if ((int)$columns > 1) {
                            $style .= '.elementor-element-' . $this->get_id() . ' .wpmf-gallery-item:hover {box-shadow: ' . $img_shadow . ' !important; transition: all 200ms ease;}';
                        }
                    }

                    if ($border_style !== 'none') {
                        if ((int)$columns === 1) {
                            $style .= '.elementor-element-' . $this->get_id() . ' .wpmf-gallery-item img:not(.glrsocial_image) {border: ' . $border_color . ' ' . $border_width . 'px ' . $border_style . ';}';
                        } else {
                            $style .= '.elementor-element-' . $this->get_id() . ' .wpmf-gallery-item .wpmf-gallery-icon {border: ' . $border_color . ' ' . $border_width . 'px ' . $border_style . ';}';
                        }
                    }
                    break;
                case 'material':
                    if ($img_shadow !== '') {
                        $style .= '.elementor-element-' . $this->get_id() . ' .wpmf-gallery-item .wpmf-card-image:hover {box-shadow: ' . $img_shadow . ' !important; transition: all 200ms ease;}';
                    }

                    if ($border_style !== 'none') {
                        $style .= '.elementor-element-' . $this->get_id() . ' .wpmf-gallery-item img:not(.glrsocial_image) {border: ' . $border_color . ' ' . $border_width . 'px ' . $border_style . '}';
                    }
                    break;
            }
            ?>
            <style id="elementor-style-<?php echo esc_attr($this->get_id()) ?>">
                <?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Style inline ?>
            </style>
            <?php
        }

        if (!empty($settings['wpmf_gallery_id'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
            if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'elementor') {
                echo do_shortcode('[wpmfgallery gallery_id="' . esc_attr($gallery_id) . '" is_lazy_load="0" display_tree="' . esc_attr($gallery_navigation) . '" display_tag="' . esc_attr($gallery_image_tags) . '" display="' . esc_attr($theme) . '" layout="' . esc_attr($layout) . '" row_height="' . esc_attr($row_height) . '" aspect_ratio="' . esc_attr($aspect_ratio) . '" columns="' . esc_attr($columns) . '" size="' . esc_attr($size) . '" targetsize="' . esc_attr($targetsize) . '" link="' . esc_attr($action) . '" wpmf_orderby="' . esc_attr($orderby) . '" wpmf_order="' . esc_attr($order) . '" gutterwidth="' . esc_attr($gutterwidth) . '" border_width="' . esc_attr($border_width) . '" border_style="' . esc_attr($border_style) . '" border_color="' . esc_attr($border_color) . '" img_shadow="' . esc_attr($img_shadow) . '" img_border_radius="' . esc_attr($border_radius) . '" number_lines="' . esc_attr($number_lines) . '" crop_image="'. $crop_image .'" hover_color="'. $hover_color .'" hover_opacity="'. $hover_opacity .'" hover_title_position="'. $hover_title_position .'" hover_title_size="'. $hover_title_size .'" hover_title_color="'. $hover_title_color .'" hover_desc_position="'. $hover_desc_position .'" hover_desc_size="'. $hover_desc_size .'" hover_desc_color="'. $hover_desc_color .'"]');
            } else {
                echo do_shortcode('[wpmfgallery gallery_id="' . esc_attr($gallery_id) . '" is_lazy_load="1" display_tree="' . esc_attr($gallery_navigation) . '" display_tag="' . esc_attr($gallery_image_tags) . '" display="' . esc_attr($theme) . '" layout="' . esc_attr($layout) . '" row_height="' . esc_attr($row_height) . '" aspect_ratio="' . esc_attr($aspect_ratio) . '" columns="' . esc_attr($columns) . '" size="' . esc_attr($size) . '" targetsize="' . esc_attr($targetsize) . '" link="' . esc_attr($action) . '" wpmf_orderby="' . esc_attr($orderby) . '" wpmf_order="' . esc_attr($order) . '" gutterwidth="' . esc_attr($gutterwidth) . '" border_width="' . esc_attr($border_width) . '" border_style="' . esc_attr($border_style) . '" border_color="' . esc_attr($border_color) . '" img_shadow="' . esc_attr($img_shadow) . '" img_border_radius="' . esc_attr($border_radius) . '" number_lines="' . esc_attr($number_lines) . '" crop_image="'. $crop_image .'" hover_color="'. $hover_color .'" hover_opacity="'. $hover_opacity .'" hover_title_position="'. $hover_title_position .'" hover_title_size="'. $hover_title_size .'" hover_title_color="'. $hover_title_color .'" hover_desc_position="'. $hover_desc_position .'" hover_desc_size="'. $hover_desc_size .'" hover_desc_color="'. $hover_desc_color .'"]');
            }
        } else {
            ?>
            <div class="wpmf-elementor-placeholder" style="text-align: center">
                <img style="background: url(<?php echo esc_url(WPMF_PLUGIN_URL . 'assets/images/gallery_addon_place_holder.svg'); ?>) no-repeat scroll center center #fafafa; height: 200px; border-radius: 2px; width: 100%;" src="<?php echo esc_url(WPMF_PLUGIN_URL . 'assets/images/t.gif'); ?>">
                <span style="position: absolute; bottom: 12px; width: 100%; left: 0;font-size: 13px; text-align: center;"><?php esc_html_e('Please select a gallery to activate the preview', 'wp-media-folder-gallery-addon'); ?></span>
            </div>
            <?php
        }
    }
}
