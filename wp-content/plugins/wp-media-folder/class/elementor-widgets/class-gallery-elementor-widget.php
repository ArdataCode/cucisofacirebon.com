<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class WpmfGalleryElementorWidget
 */
class WpmfGalleryElementorWidget extends \Elementor\Widget_Base
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
            'wpmf-slick-script',
            'wpmf-gallery'
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
            'wpmf-slick-style',
            'wpmf-slick-theme-style',
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
        return 'wpmf_gallery';
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
        return esc_html__('WP Media Folder Gallery', 'wpmf');
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
        return 'fa wpmf-gallery-elementor-icon';
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
        $settings = wpmfGetOption('gallery_settings');
        $this->start_controls_section(
            'gallery_settings',
            array(
                'label' => esc_html__('Gallery Settings', 'wpmf'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT
            )
        );

        $this->add_control(
            'wpmf_theme',
            array(
                'label' => esc_html__('Theme', 'wpmf'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'default' => esc_html__('Default', 'wpmf'),
                    'masonry' => esc_html__('Masonry', 'wpmf'),
                    'portfolio' => esc_html__('Portfolio', 'wpmf'),
                    'slider' => esc_html__('Slider', 'wpmf')
                ),
                'default' => 'masonry'
            )
        );

        $this->add_control(
            'wpmf_gallery',
            array(
                'label' => esc_html__('Add Images', 'wpmf'),
                'type' => \Elementor\Controls_Manager::GALLERY,
                'default' => array()
            )
        );

        $this->add_control(
            'wpmf_gallery_columns',
            array(
                'label' => esc_html__('Columns', 'wpmf'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => $settings['theme']['masonry_theme']['columns'],
                'min' => 1,
                'max' => 8,
                'step' => 1
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
                'label' => esc_html__('Crop Image', 'wpmf'),
                'description' => esc_html__('Only use for slider theme', 'wpmf'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Enable', 'wpmf'),
                'label_off' => __('Disable', 'wpmf'),
                'return_value' => 'yes',
                'default' => 'yes'
            )
        );

        $this->add_control(
            'wpmf_gallery_targetsize',
            array(
                'label' => esc_html__('Lightbox Size', 'wpmf'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => apply_filters('image_size_names_choose', array(
                    'thumbnail' => __('Thumbnail', 'wpmf'),
                    'medium' => __('Medium', 'wpmf'),
                    'large' => __('Large', 'wpmf'),
                    'full' => __('Full Size', 'wpmf'),
                )),
                'default' => $settings['theme']['masonry_theme']['targetsize']
            )
        );

        $this->add_control(
            'wpmf_gallery_action',
            array(
                'label' => esc_html__('Action On Click', 'wpmf'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'file' => esc_html__('Lightbox', 'wpmf'),
                    'post' => esc_html__('Attachment Page', 'wpmf'),
                    'none' => esc_html__('None', 'wpmf'),
                ),
                'default' => $settings['theme']['masonry_theme']['link']
            )
        );

        $this->add_control(
            'wpmf_gallery_orderby',
            array(
                'label' => esc_html__('Order by', 'wpmf'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'post__in' => esc_html__('Custom', 'wpmf'),
                    'rand' => esc_html__('Random', 'wpmf'),
                    'title' => esc_html__('Title', 'wpmf'),
                    'date' => esc_html__('Date', 'wpmf')
                ),
                'default' => $settings['theme']['masonry_theme']['orderby']
            )
        );

        $this->add_control(
            'wpmf_gallery_order',
            array(
                'label' => esc_html__('Order', 'wpmf'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'ASC' => esc_html__('Ascending', 'wpmf'),
                    'DESC' => esc_html__('Descending', 'wpmf')
                ),
                'default' => $settings['theme']['masonry_theme']['order']
            )
        );

        $this->end_controls_section();

        // margin tab
        $this->start_controls_section(
            'wpmf_gallery_margin',
            array(
                'label' => esc_html__('Margin', 'wpmf'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT
            )
        );

        $this->add_control(
            'wpmf_gallery_gutterwidth',
            array(
                'label' => esc_html__('Gutter', 'wpmf'),
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
                'label' => esc_html__('Border', 'wpmf'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT
            )
        );

        $this->add_control(
            'wpmf_gallery_image_radius',
            array(
                'label' => esc_html__('Border Radius', 'wpmf'),
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
                'label' => esc_html__('Border Type', 'wpmf'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'solid' => esc_html__('Solid', 'wpmf'),
                    'double' => esc_html__('Double', 'wpmf'),
                    'dotted' => esc_html__('Dotted', 'wpmf'),
                    'dashed' => esc_html__('Dashed', 'wpmf'),
                    'groove' => esc_html__('Groove', 'wpmf')
                ),
                'default' => 'solid'
            )
        );

        $this->add_control(
            'wpmf_gallery_border_width',
            array(
                'label' => esc_html__('Border Width', 'wpmf'),
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
                'label' => esc_html__('Border Color', 'wpmf'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#cccccc'
            )
        );

        $this->end_controls_section();

        // shadow tab
        $this->start_controls_section(
            'wpmf_gallery_shadow',
            array(
                'label' => esc_html__('Shadow', 'wpmf'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT
            )
        );

        $this->add_control(
            'wpmf_gallery_enable_shadow',
            array(
                'label' => esc_html__('Enable', 'wpmf'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Enable', 'wpmf'),
                'label_off' => __('Disable', 'wpmf'),
                'return_value' => 'yes',
                'default' => 'no'
            )
        );

        $this->add_control(
            'wpmf_gallery_shadow_color',
            array(
                'label' => esc_html__('Color', 'wpmf'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#cccccc'
            )
        );

        $this->add_control(
            'wpmf_gallery_shadow_horizontal',
            array(
                'label' => esc_html__('Horizontal', 'wpmf'),
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
                'label' => esc_html__('Vertical', 'wpmf'),
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
                'label' => esc_html__('Blur', 'wpmf'),
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
                'label' => esc_html__('Spread', 'wpmf'),
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

        // media from folder tab
        $this->start_controls_section(
            'wpmf_gallery_from_folder',
            array(
                'label' => esc_html__('Gallery From Folder', 'wpmf'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT
            )
        );

        $this->add_control(
            'wpmf_gallery_folder',
            array(
                'label' => esc_html__('Gallery From Folder', 'wpmf'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Enable', 'wpmf'),
                'label_off' => __('Disable', 'wpmf'),
                'return_value' => 'yes',
                'default' => 'no'
            )
        );

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

                if (isset($folder['drive_type']) &&  $folder['drive_type'] !== '') {
                    $list_cloud[$folder['id']] = str_repeat('&nbsp;&nbsp;', $folder['depth']) . $folder['label'];
                } else {
                    $list_local[$folder['id']] = str_repeat('&nbsp;&nbsp;', $folder['depth']) . $folder['label'];
                }
            } else {
                $list_local[0] = $folder['label'];
            }
        }

        $this->add_control(
            'wpmf_gallery_folder_id',
            array(
                'label' => esc_html__('Choose a Folder', 'wpmf'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $list_local + $list_cloud,
                'default' => 0
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
        $theme = (!empty($settings['wpmf_theme'])) ? $settings['wpmf_theme'] : 'default';
        $columns = (!empty($settings['wpmf_gallery_columns'])) ? $settings['wpmf_gallery_columns'] : 3;
        $size = (!empty($settings['wpmf_gallery_size_size'])) ? $settings['wpmf_gallery_size_size'] : 'thumbnail';
        $crop_image = (!empty($settings['wpmf_gallery_crop_image']) && $settings['wpmf_gallery_crop_image'] === 'yes') ? 1 : 0;

        $targetsize = (!empty($settings['wpmf_gallery_targetsize'])) ? $settings['wpmf_gallery_targetsize'] : 'large';
        $action = (!empty($settings['wpmf_gallery_action'])) ? $settings['wpmf_gallery_action'] : 'file';
        $orderby = (!empty($settings['wpmf_gallery_orderby'])) ? $settings['wpmf_gallery_orderby'] : 'post__in';
        $order = (!empty($settings['wpmf_gallery_order'])) ? $settings['wpmf_gallery_order'] : 'ASC';
        $gutterwidth = (!empty($settings['wpmf_gallery_gutterwidth'])) ? $settings['wpmf_gallery_gutterwidth'] : 5;

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

        $enable_gallery_folder = (!empty($settings['wpmf_gallery_folder']) && $settings['wpmf_gallery_folder'] === 'yes') ? 1 : 0;
        $folder_id = (!empty($settings['wpmf_gallery_folder_id'])) ? $settings['wpmf_gallery_folder_id'] : 0;
        $gallery_items = $settings['wpmf_gallery'];
        $ids = array();
        foreach ($gallery_items as $gallery_item) {
            $ids[] = $gallery_item['id'];
        }
        if (is_admin()) {
            require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-display-gallery.php');
            $gallery = new WpmfDisplayGallery();
            $style = '';
            switch ($theme) {
                case 'default':
                case 'masonry':
                    if ($img_shadow !== '') {
                        $style .= '.elementor-element-' . $this->get_id() . ' .wpmf-gallery-item img:hover {box-shadow: ' . $img_shadow . ' !important; transition: all 200ms ease;}';
                    }

                    if ($border_style !== 'none') {
                        $style .= '.elementor-element-' . $this->get_id() . ' .wpmf-gallery-item img {border: ' . $border_color . ' '. $border_width .'px '. $border_style .'}';
                    }
                    break;
                case 'portfolio':
                    if ($img_shadow !== '') {
                        $style .= '.elementor-element-' . $this->get_id() . ' .wpmf-gallery-item .wpmf_overlay:hover {box-shadow: ' . $img_shadow . ' !important; transition: all 200ms ease;}';
                    }

                    if ($border_style !== 'none') {
                        $style .= '.elementor-element-' . $this->get_id() . ' .wpmf-gallery-item img {border: ' . $border_color . ' '. $border_width .'px '. $border_style .'}';
                    }
                    break;
                case 'slider':
                    $style = '';
                    if ($img_shadow !== '') {
                        if ((int) $columns > 1) {
                            $style .= '.elementor-element-' . $this->get_id() . ' .wpmf-gallery-item:hover {box-shadow: ' . $img_shadow . ' !important; transition: all 200ms ease;}';
                        }
                    }

                    if ($border_style !== 'none') {
                        if ((int) $columns === 1) {
                            $style .= '.elementor-element-' . $this->get_id() . ' .wpmf-gallery-item img {border: ' . $border_color . ' '. $border_width .'px '. $border_style .';}';
                        } else {
                            $style .= '.elementor-element-' . $this->get_id() . ' .wpmf-gallery-item {border: ' . $border_color . ' '. $border_width .'px '. $border_style .';}';
                        }
                    }
                    break;
            }
            ?>
            <style id="elementor-style-<?php echo esc_attr($this->get_id()) ?>">
                <?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Style inline ?>
            </style>
            <script type="text/javascript">
                var wpmfggr = '<?php echo json_encode($gallery->localizeScript()); ?>';
                wpmfggr = JSON.parse(wpmfggr);
                wpmfggr.slider_animation = 'slide';
            </script>
            <?php
            // phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Load script from file
            ?>
            <script type="text/javascript" src="<?php echo esc_url(WPMF_PLUGIN_URL . 'assets/js/display-gallery/site_gallery.js?v=' . WPMF_VERSION) ?>"></script>
            <?php
            // phpcs:enable
        }

        if (empty($settings['wpmf_gallery']) && empty($enable_gallery_folder) && empty($folder_id)) {
            ?>
            <div class="wpmf-elementor-placeholder" style="text-align: center">
                <img style="background: url(<?php echo esc_url(WPMF_PLUGIN_URL . 'assets/images/gallery_place_holder.svg'); ?>) no-repeat scroll center center #fafafa; height: 200px; border-radius: 2px; width: 100%;" src="<?php echo esc_url(WPMF_PLUGIN_URL . 'assets/images/t.gif'); ?>">
                <span style="position: absolute; bottom: 12px; width: 100%; left: 0;font-size: 13px; text-align: center;"><?php esc_html_e('Please add some images to the gallery to activate the preview', 'wpmf'); ?></span>
            </div>
            <?php
        } else {
            echo do_shortcode('[wpmf_gallery include="'. esc_attr(implode(',', $ids)) .'" display="' . esc_attr($theme) . '" columns="' . esc_attr($columns) . '" size="' . esc_attr($size) . '" targetsize="' . esc_attr($targetsize) . '" link="' . esc_attr($action) . '" wpmf_orderby="' . esc_attr($orderby) . '" wpmf_order="' . esc_attr($order) . '" gutterwidth="' . esc_attr($gutterwidth) . '" border_width="' . esc_attr($border_width) . '" border_style="' . esc_attr($border_style) . '" border_color="' . esc_attr($border_color) . '" img_shadow="' . esc_attr($img_shadow) . '" img_border_radius="' . esc_attr($border_radius) . '" wpmf_autoinsert="' . esc_attr($enable_gallery_folder) . '" wpmf_folder_id="' . esc_attr($folder_id) . '" crop_image="'. $crop_image .'"]');
        }
    }
}
