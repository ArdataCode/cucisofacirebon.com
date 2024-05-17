<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Class WpmfGlrAddonFrontEnd
 * This class that holds most of the front-end functionality for WP Media Folder Gallery
 */
class WpmfGlrAddonFrontEnd
{
    /**
     * WpmfGlrAddonFrontEnd constructor.
     */
    public function __construct()
    {
        if (is_plugin_active('wp-media-folder/wp-media-folder.php')) {
            add_action('wp_enqueue_scripts', array($this, 'galleryScripts'));
            add_shortcode('wpmfgallery', array($this, 'galleryShortcode'));
            add_action('wp_ajax_nopriv_wpmf_load_gallery', array($this, 'loadGallery'));
            add_action('wp_ajax_wpmf_load_gallery', array($this, 'loadGallery'));
            add_action('wp_ajax_get_galleries', array($this, 'getGalleries'));
            add_action('wp_ajax_nopriv_get_galleries', array($this, 'getGalleries'));
            add_action('wp_ajax_nopriv_getParentsCats', array($this, 'getParentsCats'));
            add_action('wp_ajax_getParentsCats', array($this, 'getParentsCats'));
            add_action('wp_ajax_nopriv_wpmf_get_gallery_item', array($this, 'getGalleryItem'));
            add_action('wp_ajax_wpmf_get_gallery_item', array($this, 'getGalleryItem'));
            add_action('wp_ajax_wpmf_divi_load_gallery_addon_html', array($this, 'loadGalleryHtml'));
        }
    }

    /**
     * Load gallery html
     *
     * @return void
     */
    public function loadGalleryHtml()
    {
        if (empty($_REQUEST['et_admin_load_nonce'])
            || !wp_verify_nonce($_REQUEST['et_admin_load_nonce'], 'et_admin_load_nonce')) {
            wp_send_json(array('status' => false, 'html' => '<p>'. esc_html__('Load failed!', 'wp-media-folder-gallery-addon') .'</p>'));
        }
        $request_body = file_get_contents('php://input');
        $data = json_decode($request_body, true);
        $gallery_navigation = (!empty($data['display_tree']) && $data['display_tree'] === 'on') ? 1 : 0;
        $gallery_image_tags = (!empty($data['display_tag']) && $data['display_tag'] === 'on') ? 1 : 0;
        if (empty($data['gallery_id'])) {
            $html = '<div class="wpmf-divi-container">
            <div id="divi-gallery-placeholder" class="divi-gallery-placeholder">
                        <span class="wpmf-divi-message">
                            ' . esc_html__('Please add some images to the gallery to activate the preview', 'wp-media-folder-gallery-addon') . '
                        </span>
            </div>
          </div>';
            wp_send_json(array('status' => false, 'html' => $html));
        }

        $html = do_shortcode('[wpmfgallery is_divi="1" is_lazy_load="0" display_tree="'. esc_attr($gallery_navigation) .'" display_tag="'. esc_attr($gallery_image_tags) .'" gallery_id="'. esc_attr($data['gallery_id']) .'" display="' . esc_attr($data['display']) . '" layout="' . esc_attr($data['layout']) . '" row_height="' . esc_attr($data['row_height']) . '" aspect_ratio="'. esc_attr($data['aspect_ratio']) .'" columns="' . esc_attr($data['columns']) . '" size="' . esc_attr($data['size']) . '" targetsize="' . esc_attr($data['targetsize']) . '" link="' . esc_attr($data['link']) . '" wpmf_orderby="' . esc_attr($data['orderby']) . '" wpmf_order="' . esc_attr($data['order']) . '" gutterwidth="' . esc_attr($data['gutterwidth']) . '" border_width="' . esc_attr($data['border_width']) . '" border_style="' . esc_attr($data['border_style']) . '" border_color="' . esc_attr($data['border_color']) . '" img_shadow="' . esc_attr($data['img_shadow']) . '" img_border_radius="' . esc_attr($data['border_radius']) . '" number_lines="' . esc_attr($data['number_lines']) . '" hover_color="'. $data['hover_color'] .'" hover_opacity="'. $data['hover_opacity'] .'" hover_title_position="'. $data['hover_title_position'] .'" hover_title_size="'. $data['hover_title_size'] .'" hover_title_color="'. $data['hover_title_color'] .'" hover_desc_position="'. $data['hover_desc_position'] .'" hover_desc_size="'. $data['hover_desc_size'] .'" hover_desc_color="'. $data['hover_desc_color'] .'"]');
        wp_send_json(array('status' => true, 'html' => $html));
    }

    /**
     * Get term to display folder tree
     *
     * @return void
     */
    public function getGalleries()
    {
        $dirs = array();
        $id   = 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- get gallery on frontend
        if (!empty($_POST['id'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- get gallery on frontend
            $id = (int) $_POST['id'];
        }

        // Retrieve the terms in a given taxonomy or list of taxonomies.
        $categorys = get_categories(
            array(
                'taxonomy'   => WPMF_GALLERY_ADDON_TAXO,
                'orderby'    => 'name',
                'order'      => 'ASC',
                'parent'     => $id,
                'hide_empty' => false
            )
        );

        foreach ($categorys as $category) {
            $child      = get_term_children((int) $category->term_id, WPMF_GALLERY_ADDON_TAXO);
            $countchild = count($child);
            $dirs[]     = array(
                'type'        => 'dir',
                'file'        => $category->name,
                'id'          => $category->term_id,
                'parent_id'   => $category->parent,
                'count_child' => $countchild,
                'term_group'  => $category->term_group
            );
        }

        if (count($dirs) === 0) {
            wp_send_json(array('status' => false));
        } else {
            wp_send_json(array('status' => true, 'dirs' => $dirs));
        }
    }

    /**
     * Loop get parent list gallery
     *
     * @param integer $id     Id of current gallery
     * @param array   $result Result
     *
     * @return array
     */
    public function loopGetParentsCats($id, $result)
    {
        $term = get_term($id, WPMF_GALLERY_ADDON_TAXO);
        if ((int) $term->parent !== 0) {
            $result = $this->loopGetParentsCats($term->parent, $result);
        }
        $result[] = $term->term_id;
        return $result;
    }

    /**
     * Get parents categories
     *
     * @return void
     */
    public function getParentsCats()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            die();
        }

        $result = array();
        if (isset($_POST['id'])) {
            $id       = (int) $_POST['id'];
            $term     = get_term($id, WPMF_GALLERY_ADDON_TAXO);
            $result   = $this->loopGetParentsCats($term->parent, $result);
            $result[] = $id;
        }
        wp_send_json($result);
    }

    /**
     * Get social html
     *
     * @return string
     */
    public function getSocialHtml()
    {
        $social_sharing      = wpmfGetOption('social_sharing');
        $social_sharing_link = wpmfGetOption('social_sharing_link');

        foreach ($social_sharing_link as $attr_key => $attr_value) {
            ${$attr_key} = $attr_value;
        }

        $social       = '';
        if ((int) $social_sharing === 1) {
            if (!empty($facebook) || !empty($twitter) || !empty($instagram) || !empty($pinterest)) {
                $social .= '<div class="wpmfglr_social">';
                if (!empty($facebook)) {
                    $social .= '<a href="' . $facebook . '"  target="_blank">
<span class="dashicons dashicons-facebook-alt"></span></a>';
                }

                if (!empty($twitter)) {
                    $social .= '<a href="' . $twitter . '"  target="_blank">
<span class="dashicons dashicons-twitter"></span></a>';
                }

                if (!empty($instagram)) {
                    $social .= '<a href="' . $instagram . '"  target="_blank">
<span class="dashicons dashicons-instagram"></span></a>';
                }

                if (!empty($pinterest)) {
                    $social .= '<a href="' . $pinterest . '"  target="_blank">
<img class="glrsocial_image" src="' . WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/images/pinterest.png" /></a>';
                }

                $social .= '</div>';
            }
        }

        return $social;
    }

    /**
     * Run shortcode gallery
     *
     * @param array $attr Params of gallery
     *
     * @return string
     */
    public function galleryShortcode($attr)
    {
        if (empty($attr['gallery_id'])) {
            return esc_html__('Please choose a gallery!', 'wp-media-folder-gallery-addon');
        }

        wp_enqueue_style('wpmf-material-icon');
        wp_enqueue_style(
            'wpmf-material-design-iconic-font.min',
            WPMF_PLUGIN_URL . '/assets/css/material-design-iconic-font.min.css',
            array(),
            WPMF_VERSION
        );
        /* Get all params */
        $post   = get_post();
        static $instance = 0;
        $instance++;
        $selector = 'wpmf-gallery-' . $instance;
        $gallery_configs      = get_option('wpmf_gallery_settings');
        // verify orderby option
        $galleries = get_option('wpmf_galleries');
        $default_params = array(
            'display'      => '',
            'layout' => 'vertical',
            'row_height' => 200,
            'aspect_ratio' => 'default',
            'columns'      => 3,
            'gutterwidth'  => 5,
            'link'         => 'post',
            'size'         => 'thumbnail',
            'targetsize'   => 'large',
            'wpmf_orderby'      => 'post__in',
            'wpmf_order'        => 'ASC',
            'customlink'   => 0,
            'class'        => '',
            'display_tree' => 0,
            'display_tag'  => 0,
            'tree_width' => 250,
            'img_border_radius' => 0,
            'border_width' => 0,
            'border_color' => 'transparent',
            'border_style' => 'solid',
            'img_shadow' => '',
            'auto_from_folder' => 1,
            'folder' => 0,
            'show_buttons' => $gallery_configs['theme']['flowslide_theme']['show_buttons'],
            'animation' => $gallery_configs['theme']['slider_theme']['animation'],
            'duration' => $gallery_configs['theme']['slider_theme']['duration'],
            'auto_animation' => $gallery_configs['theme']['slider_theme']['auto_animation'],
            'number_lines' => 1,
            'is_divi' => 0,
            'include_children' => 0,
            'crop_image' => 1,
            'hover_color' => '#000',
            'hover_opacity' => '0.4',
            'hover_title_position' => 'center_center',
            'hover_title_size' => 16,
            'hover_title_color' => '#fff',
            'hover_desc_position' => 'none',
            'hover_desc_size' => 14,
            'hover_desc_color' => '#fff',
            'is_lazy_load' => 1
        );

        foreach ($default_params as $param_key => $default_param) {
            if (!isset($attr[$param_key])) {
                $attr[$param_key] = $default_params[$param_key];
            }
        }

        if (isset($galleries[$attr['gallery_id']])) {
            $params = array_merge(
                $default_params,
                $galleries[$attr['gallery_id']],
                $attr
            );
        } else {
            $params = array_merge(
                $default_params,
                $attr
            );
        }

        $params = $this->modifyOrderOptions($params);
        foreach ($params as $attr_key => $attr_value) {
            ${$attr_key} = $attr_value;
        }

        if (!empty($galleries[$attr['gallery_id']])) {
            $tree_width = $galleries[$attr['gallery_id']]['tree_width'];
        }

        $id = intval($gallery_id);
        $lazy_load = ((isset($gallery_configs['progressive_loading']) && (int)$gallery_configs['progressive_loading'] === 0) || is_admin()) ? false : true;
        if (isset($is_lazy_load) && (int)$is_lazy_load === 0) {
            $lazy_load = false;
        }

        $gallery_exist = get_term($id, WPMF_GALLERY_ADDON_TAXO);
        if (is_wp_error($gallery_exist)) {
            return esc_html__('Gallery not exists!', 'wp-media-folder-gallery-addon');
        }

        $relationships = get_option('wpmfgrl_relationships');
        $social = $this->getSocialHtml();

        if ($display === '') {
            $display = 'default';
            if (!empty($galleries[$id]['theme'])) {
                $display = $galleries[$id]['theme'];
            }
        }

        // get params from options
        $allow_themes = array(
            'default',
            'masonry',
            'portfolio',
            'slider',
            'flowslide',
            'square_grid',
            'material',
            'custom_grid'
        );
        if (!in_array($display, $allow_themes)) {
            $display = 'default';
        }

        $caption_lightbox = wpmfGetOption('caption_lightbox_gallery');
        $hover_class = (isset($gallery_configs['hover_image']) && (int) $gallery_configs['hover_image'] === 0) ? ' hover_false' : ' hover_true';

        $grid_styles = get_term_meta($gallery_id, 'wpmf_grid_styles', true);

        /* Query images */
        $tax_query = wpmfGalleryAddonGetTaxQuery($id, $params);
        $args = array(
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'post_type' => 'attachment',
            'post_mime_type' => wpmfGalleryAddonGetImageType(),
            'tax_query'      => $tax_query,
            'wpmf_gallery' => 1
        );

        if ($display === 'custom_grid') {
            $wpmf_orderby = 'post__in';
            $args['orderby'] = 'post__in';
            $args['order']   = 'DESC';
        } else {
            if ($wpmf_orderby !== 'post__in') {
                $args['orderby'] = $wpmf_orderby;
                $args['order']   = $wpmf_order;
            }
        }

        $query        = new WP_Query($args);
        $_attachments = $query->get_posts();

        if ($wpmf_orderby === 'post__in') {
            $attachments  = array();
            foreach ($_attachments as &$val) {
                $order = get_post_meta((int)$val->ID, 'wpmf_gallery_'. $gallery_id .'_order', true);
                $val->order = (int) $order;
                $attachments[] = $val;
            }
            usort($attachments, 'wpmfSortByOrder');
        } else {
            $attachments = $_attachments;
        }

        if (empty($attachments) && (int) $display_tree === 0) {
            return '<div class="wpmf_gallery_wrap"><p style="margin: 0; text-align: center; padding: 5px; color: #f00">'. esc_html__('No media items found.', 'wp-media-folder-gallery-addon') .'</p></div>';
        }

        $class      = array();
        $class[] = 'wpmf-gallery-addon-wrap';
        $class[] = 'gallery-link-' . $link;

        /* Create output html */
        if (!is_admin()) {
            $this->enqueue($display, $params);
        }

        $tags  = array();
        foreach ($attachments as $value) {
            $img_tags = get_post_meta($value->ID, 'wpmf_img_tags', true);
            $img_tags = explode(',', $img_tags);
            foreach ($img_tags as $img_tag) {
                if (trim($img_tag) !== '') {
                    $tags[] = trim($img_tag);
                }
            }
        }

        natcasesort($tags);
        if ((int) $display_tag === 1 && count(array_unique($tags)) > 0) {
            $classtag = 'wpmf-tags';
        } else {
            $classtag = '';
        }

        $output = '<div class="wpmf_gallery_wrap ' . $classtag . $hover_class . '" data-selected="'. (empty($attachments) ? 'child' : 'current') .'" data-selector="'. $selector .'" data-top-gallery-settings="' . esc_attr(json_encode($params)) . '" data-id="' . $id . '">';
        if (isset($display_tree) && (int) $display_tree === 1) {
            // render tree html in divi builder
            $tree_html = $this->renderTree($id);
            $tree_width = (int)$tree_width;
            if ((int)$tree_width === 0) {
                $tree_width = 250;
            }
            $right_width = 'calc(100% - '. $tree_width .'px)';
            $output .= '<div class="wpmf_gallery_tree" data-id="' . $id . '" style="width: '. esc_attr($tree_width) .'px">'. $tree_html .'</div>';
            $output .= '<div class="wpmf_gallery_box" data-id="' . $id . '" data-theme="' . $display . '" style="width: '. esc_attr($right_width) .'">';
        } else {
            $output .= '<div class="wpmf_gallery_box fullbox" data-id="' . $id . '" data-theme="' . $display . '">';
        }

        if ($display !== 'default' && $display !== 'material' && $display !== 'square_grid') {
            $output .= "<img class='loading_gallery' src='" . WPMF_GALLERY_ADDON_PLUGIN_URL . "/assets/images/Loading_icon.gif' />";
        }

        if ((int) $display_tag === 1 && count(array_unique($tags)) > 0) {
            $output .= '<div class="wpmf-gridblock-filters">';
            $output .= '<ul class="tabs gridblock-filter-categories">';
            $output .= '<li class="tab filter-all-control selected">';
            $output .= '<a data-filter="*">' . __('All', 'wp-media-folder-gallery-addon') . '</a>';
            $output .= '</li>';
            foreach (array_unique($tags) as $tag) {
                $output .= '<li class="tab filter-all-control">';
                $output .= '<a data-filter="' . $tag . '">' . strtoupper($tag) . '</a>';
                $output .= '</li>';
            }
            $output .= '</ul></div>';
        }

        ob_start();
        if (isset($display)) {
            if (file_exists(WPMF_GALLERY_ADDON_PLUGIN_DIR . 'frontend/themes-gallery/' . $display . '.php')) {
                require(WPMF_GALLERY_ADDON_PLUGIN_DIR . 'frontend/themes-gallery/' . $display . '.php');
            } else {
                require(WPMF_GALLERY_ADDON_PLUGIN_DIR . 'frontend/themes-gallery/default.php');
            }
        } else {
            require(WPMF_GALLERY_ADDON_PLUGIN_DIR . 'frontend/themes-gallery/default.php');
        }

        $output .= ob_get_contents();
        ob_end_clean();
        $output .= '</div>';
        $output .= '</div>';
        return $output;
    }

    /**
     * Register script, style
     *
     * @return void
     */
    public function galleryScripts()
    {
        $is_builder = (function_exists('fusion_is_preview_frame') && fusion_is_preview_frame()) || (function_exists('fusion_is_builder_frame') && fusion_is_builder_frame());
        if (!$is_builder) {
            wp_enqueue_script('jquery');
            wp_register_style(
                'wpmf-material-icon',
                WPMF_PLUGIN_URL. 'assets/css/google-material-icon.css',
                array(),
                WPMF_VERSION
            );

            $lightbox = get_option('wpmf_usegellery_lightbox');
            if (!empty($lightbox)) {
                wp_dequeue_style('media_boxes-fancybox');
                wp_dequeue_script('media_boxes-fancybox-js');
                wp_register_script(
                    'wpmf-google-photo-fancybox-script',
                    WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/js/fancybox/jquery.fancybox.min.js',
                    array('jquery'),
                    WPMF_VERSION
                );

                wp_register_style(
                    'wpmf-google-photo-fancybox-style',
                    WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/js/fancybox/jquery.fancybox.min.css',
                    array(),
                    WPMF_VERSION
                );
            }

            wp_register_script(
                'wpmf-autobrower',
                WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/js/jquery.esn.autobrowse.js',
                array('jquery'),
                WPMF_GALLERY_ADDON_VERSION,
                true
            );

            wp_register_script(
                'wordpresscanvas-imagesloaded',
                WPMF_PLUGIN_URL . '/assets/js/display-gallery/imagesloaded.pkgd.min.js',
                array(),
                '3.1.5',
                true
            );

            wp_register_style(
                'wpmf-justified-style',
                WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/js/justified-gallery/justifiedGallery.min.css',
                array(),
                WPMF_GALLERY_ADDON_VERSION
            );

            wp_register_script(
                'wpmf-justified-script',
                WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/js/justified-gallery/jquery.justifiedGallery.min.js',
                array('jquery'),
                WPMF_GALLERY_ADDON_VERSION,
                true
            );

            wp_register_script('wpmfisotope', WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/js/isotope.pkgd.js', array('jquery'), WPMF_GALLERY_ADDON_VERSION, true);
            wp_register_script('wpmfpackery', WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/packery/packery.pkgd.min.js', array('jquery'), WPMF_GALLERY_ADDON_VERSION, true);
            wp_register_style(
                'wpmf-slick-style',
                WPMF_PLUGIN_URL . 'assets/js/slick/slick.css',
                array(),
                WPMF_VERSION
            );

            wp_register_style(
                'wpmf-slick-theme-style',
                WPMF_PLUGIN_URL . 'assets/js/slick/slick-theme.css',
                array(),
                WPMF_VERSION
            );

            wp_register_script(
                'wpmf-slick-script',
                WPMF_PLUGIN_URL . 'assets/js/slick/slick.min.js',
                array('jquery'),
                WPMF_VERSION,
                true
            );

            wp_register_style(
                'wpmf-gallery-style',
                WPMF_PLUGIN_URL . 'assets/css/display-gallery/style-display-gallery.css',
                array(),
                WPMF_VERSION
            );

            wp_register_script(
                'wpmf-flipster-js',
                WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/js/jquery.flipster.js',
                array('jquery'),
                WPMF_GALLERY_ADDON_VERSION,
                true
            );

            wp_register_style(
                'wpmf-flipster-css',
                WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/css/jquery.flipster.css',
                array(),
                WPMF_GALLERY_ADDON_VERSION
            );

            wp_register_script(
                'wpmf-gallery-js',
                WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/js/gallery.js',
                array('jquery'),
                WPMF_GALLERY_ADDON_VERSION,
                true
            );

            wp_register_script(
                'wpmf-gallery-tree-js',
                WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/js/gallery_navigation_front.js',
                array('jquery'),
                WPMF_GALLERY_ADDON_VERSION
            );

            wp_register_style(
                'wpmf-gallery-css',
                WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/css/gallery.css',
                array(),
                WPMF_GALLERY_ADDON_VERSION
            );

            wp_localize_script('wpmf-gallery-js', 'wpmfgallery', $this->localizeScript());
        }
    }

    /**
     * Enqueue script styles by editor
     *
     * @param string $editor Editor name
     *
     * @return void
     */
    public function enqueueScript($editor = '')
    {
        if ($editor === 'divi') {
            wp_enqueue_style('wpmf-material-icon');
            wp_enqueue_style('wpmf-slick-style');
            wp_enqueue_style('wpmf-slick-theme-style');
            wp_enqueue_style('wpmf-justified-style');
            wp_enqueue_style('wpmf-gallery-style');
            wp_enqueue_style('wpmf-flipster-css');
            wp_enqueue_style('wpmf-gallery-css');
            wp_enqueue_script('wordpresscanvas-imagesloaded');
            wp_enqueue_script('wpmf-slick-script');
            wp_enqueue_script('wpmf-justified-script');
            wp_enqueue_script('wpmf-flipster-js');
            wp_enqueue_script('wpmfisotope');
            wp_enqueue_script('wpmfpackery');
        }
    }

    /**
     * Load scripts and styles
     *
     * @param string $display Theme name
     * @param string $params  Gallery params
     *
     * @return void
     */
    public function enqueue($display, $params)
    {
        $settings = get_option('wpmf_gallery_settings');
        wp_enqueue_script('wpmf-google-photo-fancybox-script');
        wp_enqueue_style('wpmf-google-photo-fancybox-style');
        wp_enqueue_script('wordpresscanvas-imagesloaded');
        if (in_array($display, array('masonry', 'portfolio', 'square_grid')) || (int)$params['display_tree'] === 1) {
            if ($params['layout'] === 'vertical') {
                wp_enqueue_script('jquery-masonry');
            } else {
                if ($display === 'masonry' || $display === 'square_grid') {
                    wp_enqueue_style('wpmf-justified-style');
                    wp_enqueue_script('wpmf-justified-script');
                }
            }
        }

        if ($display === 'custom_grid') {
            wp_enqueue_script('wpmfisotope');
            wp_enqueue_script('wpmfpackery');
        }

        if (!isset($settings['progressive_loading']) || (int) $settings['progressive_loading'] === 1) {
            wp_enqueue_script('wpmf-autobrower');
        }

        if ($display === 'slider' || (int) $params['display_tree'] === 1) {
            wp_enqueue_style('wpmf-slick-style');
            wp_enqueue_style('wpmf-slick-theme-style');
            wp_enqueue_script('wpmf-slick-script');
        }

        wp_enqueue_style('wpmf-gallery-style');

        if ($display === 'flowslide' || (int) $params['display_tree'] === 1) {
            wp_enqueue_script('wpmf-flipster-js');
            wp_enqueue_style('wpmf-flipster-css');
        }

        wp_enqueue_script('wpmf-gallery-js');
        wp_enqueue_script('wpmf-gallery-tree-js');
        wp_enqueue_style('wpmf-gallery-css');
    }

    /**
     * Render folders tree
     *
     * @param string $folder_id Folder ID
     *
     * @return string
     */
    public function renderTree($folder_id)
    {
        $gallery = get_term((int)$folder_id, WPMF_GALLERY_ADDON_TAXO);
        $feature_image_id = get_term_meta($folder_id, 'wpmf_gallery_feature_image', true);
        $feature_image = '';
        if (!empty($feature_image_id)) {
            $feature_image = wp_get_attachment_image_url($feature_image_id, 'thumbnail');
        }
        $feature_image = ($feature_image) ? $feature_image : WPMF_GALLERY_ADDON_PLUGIN_URL .'assets/images/image-gallery-icon.png';
        $sub_html = $this->renderFoldersTree($gallery->term_id, 28);
        $html = '<ul>';
        $html .= '<li class="open selected" data-id="'. esc_attr($folder_id) .'">';
        $html .= '<div class="wpmf-gallery-tree-item" data-id="'. esc_attr($folder_id) .'">';
        $html .= '<div class="wpmf-gallery-item-inside">';
        if ($sub_html !== '') {
            $html .= '<a data-id="'. esc_attr($folder_id) .'" class="wpmf-gallery-toggle-icon"><i class="tree_arrow_right_icon wpmf-gallery-arrow"></i></a>';
        } else {
            $html .= '<a data-id="'. esc_attr($folder_id) .'" class="wpmf-gallery-toggle-icon" style="visibility: hidden"><i class="tree_arrow_right_icon wpmf-gallery-arrow"></i></a>';
        }

        $html .= '<a class="wpmf-gallery-text-item" data-id="'. esc_attr($folder_id) .'">';
        $html .= '<img class="wpmf-gallery-thumbnail-icon" src="'. esc_url($feature_image) .'">';
        $html .= '<span class="wpmf-gallery-item-title">'. esc_html($gallery->name) .'</span>';
        $html .= '</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= $sub_html;
        $html .= '</li>';
        $html .= '</ul>';
        return $html;
    }

    /**
     * Render folders tree
     *
     * @param integer $parent  Parent
     * @param integer $padding Padding
     *
     * @return string
     */
    public function renderFoldersTree($parent = 0, $padding = 28)
    {
        $args = array(
            'hide_empty'                   => false,
            'taxonomy'                     => WPMF_GALLERY_ADDON_TAXO,
            'pll_get_terms_not_translated' => 1,
            'parent' => $parent
        );

        $galleries            = get_categories($args);
        foreach ($galleries as $key => $object) {
            $order = get_term_meta($object->term_id, 'wpmf_order', true);
            if (empty($order)) {
                $order = 0;
            }
            $object->order = $order;
        }
        usort($galleries, 'wpmfGalleryReorder');
        $html = '';
        if (!empty($galleries)) {
            $html .= '<ul>';
            foreach ($galleries as $gallery) {
                $feature_image_id = get_term_meta($gallery->term_id, 'wpmf_gallery_feature_image', true);
                $feature_image = '';
                if (!empty($feature_image_id)) {
                    $feature_image = wp_get_attachment_image_url($feature_image_id, 'thumbnail');
                }
                $feature_image = ($feature_image) ? $feature_image : WPMF_GALLERY_ADDON_PLUGIN_URL .'assets/images/image-gallery-icon.png';

                $html .= '<li class="closed" data-id="'. esc_attr($gallery->term_id) .'">';
                $sub_html = $this->renderFoldersTree($gallery->term_id, (int)$padding + 15);
                $html .= '<div class="wpmf-gallery-tree-item" data-id="'. esc_attr($gallery->term_id) .'">';
                $html .= '<div class="wpmf-gallery-item-inside" style="padding-left: '. $padding .'px">';
                if ($sub_html !== '') {
                    $html .= '<a data-id="'. esc_attr($gallery->term_id) .'" class="wpmf-gallery-toggle-icon"><i class="tree_arrow_right_icon wpmf-gallery-arrow"></i></a>';
                } else {
                    $html .= '<a data-id="'. esc_attr($gallery->term_id) .'" class="wpmf-gallery-toggle-icon" style="visibility: hidden"><i class="tree_arrow_right_icon wpmf-gallery-arrow"></i></a>';
                }
                $html .= '<a class="wpmf-gallery-text-item" data-id="'. esc_attr($gallery->term_id) .'">';
                $html .= '<img class="wpmf-gallery-thumbnail-icon" src="'. esc_url($feature_image) .'">';
                $html .= '<span class="wpmf-gallery-item-title">'. esc_html($gallery->name) .'</span>';
                $html .= '</a>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= $sub_html;
                $html .= '</li>';
            }
            $html .= '</ul>';
        }

        return $html;
    }

    /**
     * Localize a script.
     * Works only if the script has already been added.
     *
     * @return array
     */
    public function localizeScript()
    {
        $option_current_theme = get_option('current_theme');
        $gallery_configs      = get_option('wpmf_gallery_settings');

        if (isset($gallery_configs['progressive_loading']) && (int) $gallery_configs['progressive_loading'] === 0) {
            $progressive_loading = 0;
        } else {
            $progressive_loading = 1;
        }

        return array(
            'wpmf_current_theme'  => $option_current_theme,
            'gallery_configs'     => $gallery_configs,
            'progressive_loading' => (int) $progressive_loading,
            'wpmf_gallery_nonce'  => wp_create_nonce('wpmf_gallery_nonce'),
            'ajaxurl'             => admin_url('admin-ajax.php')
        );
    }

    /**
     * Generate html attachment link
     *
     * @param integer $id        Id of image
     * @param string  $size      Size of image
     * @param boolean $permalink Permalink of image
     *
     * @return mixed|string|boolean
     */
    public function galleryGetAttachmentLink(
        $id = 0,
        $size = 'thumbnail',
        $permalink = false
    ) {
        $id    = intval($id);
        $_post = get_post($id);
        $url   = wp_get_attachment_url($_post->ID);
        if (empty($_post) || ('attachment' !== $_post->post_type) || !$url) {
            return false;
        }

        if ($size && 'none' !== $size) {
            $thumb_id = wpmfGalleryGetVideoThumbID($id);
            $link_text = wp_get_attachment_image($thumb_id, $size, false, array('data-type' => 'wpmfgalleryimg', 'id' => 'wpmfgalleryimg'));
        } else {
            $link_text = '';
        }

        if (trim($link_text) === '') {
            $link_text = $_post->post_title;
        }

        return apply_filters(
            'wp_get_attachment_link',
            $link_text,
            $thumb_id,
            $size,
            $permalink,
            false,
            false
        );
    }

    /**
     * Modify order options
     *
     * @param array $settings Gallery settings
     *
     * @return mixed
     */
    public function modifyOrderOptions($settings)
    {
        if (isset($settings['orderby'])) {
            $settings['wpmf_orderby'] = $settings['orderby'];
        }
        if (isset($settings['order'])) {
            $settings['wpmf_order'] = $settings['order'];
        }

        return $settings;
    }

    /**
     * Get gallery items
     *
     * @return void
     */
    public function getGalleryItem()
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
        $settings = json_decode(stripcslashes($_REQUEST['settings']), true);
        // verify orderby option
        $settings = $this->modifyOrderOptions($settings);
        $gallery_configs     = get_option('wpmf_gallery_settings');
        $lazy_load = (isset($gallery_configs['progressive_loading']) && (int) $gallery_configs['progressive_loading'] === 0) ? false : true;
        $tags = isset($_REQUEST['tags']) ? $_REQUEST['tags'] : '*';
        $id = $_REQUEST['gallery_id'];
        $social = $this->getSocialHtml();
        foreach ($settings as $key => $setting) {
            ${$key} = $setting;
        }
        $theme = $_REQUEST['theme'];
        // phpcs:enable
        $tax_query = wpmfGalleryAddonGetTaxQuery($id, $settings);
        $args = array(
            'posts_per_page' => 8,
            'post_status'    => 'any',
            'post_type' => 'attachment',
            'post_mime_type' => wpmfGalleryAddonGetImageType(),
            'tax_query'      => $tax_query
        );

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
        if (isset($_REQUEST['loaded_ids'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
            $args['post__not_in'] = explode(',', $_REQUEST['loaded_ids']);
        }
        if ($wpmf_orderby !== 'post__in') {
            $args['orderby'] = $wpmf_orderby;
            $args['order']   = $wpmf_order;
        }

        $query        = new WP_Query($args);
        $_attachments = $query->get_posts();
        $attachments  = array();
        foreach ($_attachments as &$val) {
            $order = get_post_meta((int)$val->ID, 'wpmf_gallery_order', true);
            $val->order = (int) $order;
            if ($tags !== '*') {
                $i_tags = get_post_meta($val->ID, 'wpmf_img_tags', true);
                $i_tags = explode(',', $i_tags);

                $i_trim_tags = array();
                foreach ($i_tags as $i_tag) {
                    $i_trim_tags[] = trim($i_tag);
                }

                if (in_array(esc_html($tags), $i_trim_tags)) {
                    $attachments[] = $val;
                }
            } else {
                $attachments[] = $val;
            }
        }

        if ($wpmf_orderby === 'post__in') {
            usort($attachments, 'wpmfSortByOrder');
        }

        if (empty($attachments)) {
            wp_send_json(array('status' => false));
        }
        $items = array();
        foreach ($attachments as $i => $attachment) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
            $index = $i + count(explode(',', $_REQUEST['loaded_ids']));
            $items[] = $this->getAttachmentThemeHtml($theme, $attachment, $social, $index, $settings);
        }

        wp_send_json(array('status' => true, 'items' => $items));
    }

    /**
     * Get vimeo video ID from URL
     *
     * @param string $url URl of video
     *
     * @return mixed|string
     */
    public function getVimeoVideoIdFromUrl($url = '')
    {
        $regs = array();
        $id   = '';
        $vimeo_pattern = '%^https?:\/\/(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|video\/|)(\d+)(?:$|\/|\?)(?:[?]?.*)$%im';
        if (preg_match($vimeo_pattern, $url, $regs)) {
            $id = $regs[3];
        }

        return $id;
    }

    /**
     * Get lightbox items
     *
     * @param array  $attachments All Attachments
     * @param string $targetsize  Lightbox size
     *
     * @return array
     */
    public function getLightboxItems($attachments, $targetsize)
    {
        $lightbox_items = array();
        $caption_lightbox = wpmfGetOption('caption_lightbox_gallery');
        foreach ($attachments as $attachment) {
            $post_title = (!empty($caption_lightbox) && $attachment->post_excerpt !== '') ? $attachment->post_excerpt : $attachment->post_title;
            $lightboxUrls = $this->getLightboxUrl($attachment->ID, $targetsize);
            $type = $lightboxUrls['type'];
            $url = $lightboxUrls['url'];
            $lightbox_items[] = array(
                'src' => $url,
                'type' => $type,
                'caption' => htmlentities($post_title, ENT_COMPAT, 'UTF-8')
            );
        }

        return $lightbox_items;
    }

    /**
     * Get lightbox URL and type
     *
     * @param integer $attachmentID Attachment ID
     * @param string  $targetsize   Lightbox size
     *
     * @return array
     */
    public function getLightboxUrl($attachmentID, $targetsize)
    {
        $type = 'image';
        $item_urls = wp_get_attachment_image_url($attachmentID, $targetsize);
        $remote_video = get_post_meta($attachmentID, 'wpmf_remote_video_link', true);
        $url = (!empty($remote_video)) ? $remote_video : $item_urls;
        if ((!empty($remote_video)) && strpos($url, 'vimeo') !== false) {
            $vimeo_id = $this->getVimeoVideoIdFromUrl($url);
            $url = 'https://player.vimeo.com/video/' . $vimeo_id;
            $type = 'iframe';
        }

        if ((!empty($remote_video)) && (strpos($url, 'youtube') !== false || strpos($url, 'youtu.be') !== false)) {
            $parts = parse_url($url);
            if ($parts['host'] === 'youtu.be') {
                $youtube_id = trim($parts['path'], '/');
            } else {
                parse_str($parts['query'], $query);
                $youtube_id = $query['v'];
            }
            $type = 'iframe';
            $url = 'https://www.youtube.com/embed/' . $youtube_id;
        }

        if ((!empty($remote_video)) && (strpos($url, 'dailymotion') !== false)) {
            $type = 'iframe';
            $id = strtok(basename($url), '_');
            $url = 'https://dailymotion.com/embed/video/' . $id;
        }

        if (!empty($remote_video)) {
            if (strpos($url, '.mp4') !== false || strpos($url, '.mov') !== false || strpos($url, '.flv') !== false) {
                $type = 'video';
            }
        }

        if ((!empty($remote_video)) && (strpos($url, 'wistia') !== false)) {
            $type = 'iframe';
        }

        if ((!empty($remote_video)) && (strpos($url, 'facebook') !== false)) {
            $type = 'iframe';
            $url = 'https://www.facebook.com/plugins/video.php?height=314&href='. urlencode($url) .'&show_text=false&width=560';
        }

        if ((!empty($remote_video)) && (strpos($url, 'twitch') !== false)) {
            $type = 'iframe';
            $parts = parse_url($url);
            if (strpos($parts['path'], '/video') !== false) {
                $twitch_id = str_replace('/videos/', '', $parts['path']);
                $url = 'https://player.twitch.tv/?video='. $twitch_id .'&parent=' . $_SERVER['SERVER_NAME'];
            } else {
                $twitch_id = trim($parts['path'], '/');
                $url = 'https://player.twitch.tv/?channel='. $twitch_id .'&parent=' . $_SERVER['SERVER_NAME'];
            }
        }

        return array('type' => $type, 'url' => $url);
    }

    /**
     * Get attachment html by theme
     *
     * @param string  $theme      Theme
     * @param object  $attachment Attachment details
     * @param string  $social     Social html
     * @param integer $index      Index
     * @param integer $params     Gallery settings
     *
     * @return string
     */
    public function getAttachmentThemeHtml($theme, $attachment, $social, $index = 0, $params = array())
    {
        $link = $params['link'];
        $size = $params['size'];
        $targetsize = $params['targetsize'];
        $item = '';
        $caption_lightbox = wpmfGetOption('caption_lightbox_gallery');
        $gallery_configs = get_option('wpmf_gallery_settings');
        $post_title = (!empty($caption_lightbox) && $attachment->post_excerpt !== '') ? $attachment->post_excerpt : $attachment->post_title;
        $img_tags = get_post_meta($attachment->ID, 'wpmf_img_tags', true);
        $custom_link = get_post_meta($attachment->ID, _WPMF_GALLERY_PREFIX . 'custom_image_link', true);
        $link_target = get_post_meta($attachment->ID, '_gallery_link_target', true);
        $image_output = $this->galleryGetAttachmentLink($attachment->ID, $size, false);
        if (!$image_output) {
            return '';
        }
        $remote_video = get_post_meta($attachment->ID, 'wpmf_remote_video_link', true);
        if (strpos($attachment->post_mime_type, 'video/') !== false) {
            $video = true;
        } else {
            $video = false;
        }

        $video_class = (!empty($remote_video) || $video) ? 'isvideo' : '';
        $downloads = wpmfGalleryGetDownloadLink($attachment->ID);
        $hovers = $this->renderHoverStyle($attachment, $params);
        $hover_color_style = $hovers['hover_color_style'];
        $hover_box = $hovers['hover_box'];
        switch ($theme) {
            case 'material':
                if ($custom_link !== '') {
                    $icon = '<a '. $hover_color_style .' href="' . $custom_link . '" class="wpmf_overlay '. $video_class .'" target="' . $link_target . '">'. $hover_box .'</a>';
                    $icon .= $social;
                } else {
                    switch ($link) {
                        case 'none':
                            $icon = '<div '. $hover_color_style .' class="wpmf_overlay '. $video_class .'">'. $hover_box .'</div>';
                            $icon .= $social;
                            break;

                        case 'post':
                            $url = get_attachment_link($attachment->ID);
                            $icon = '<a '. $hover_color_style .' href="' . esc_url($url) . '" title="' . esc_attr($post_title) . '" class="wpmf_overlay '. $video_class .'" target="' . $link_target . '">'. $hover_box .'</a>';
                            $icon .= $social;
                            break;

                        default:
                            $lightboxUrls = $this->getLightboxUrl($attachment->ID, $targetsize);
                            $url = $lightboxUrls['url'];
                            $icon = '<a '. $hover_color_style .' data-swipe="1" data-href="' . esc_url($url) . '" data-title="' . esc_attr($post_title) . '"
class="wpmfgalleryaddonswipe wpmf_overlay '. $video_class .'">'. $hover_box .'</a>';
                            $icon .= $social;
                    }
                }

                if (isset($gallery_configs['download_item']) && (int)$gallery_configs['download_item'] === 1) {
                    $icon .= '<a href="'.esc_url($downloads['download_link']).'" '. (($downloads['type'] === 'local') ? 'download' : '') .' class="wpmf_gallery_download_icon"><span class="material-icons-outlined"> file_download </span></a>';
                }
                $item = '<figure class="wpmf-gallery-item gallery-item" data-id="'. (int)$attachment->ID .'" data-index="'. esc_html($index) .'">';
                $item .= '<div class="wpmf-card image-over-card m-t-30">';

                $item .= '<div class="wpmf-card-image wpmf-gallery-icon">';
                $item .= wpmfRenderVideoIcon($attachment->ID);
                $item .= $icon;
                $item .= '<div class="square_thumbnail">';
                $item .= '<div class="img_centered">';
                $item .= $image_output;
                $item .= '</div>';
                $item .= '</div>';
                $item .= '</div>';


                $item .= '<div class="wpmf-card-body">';
                if (esc_html($attachment->post_excerpt) === '') {
                    $item .= '<h4 class="wpmf-card-title text-center wpmf-gallery-caption">' . wptexturize($attachment->post_title) . '</h4>';
                } else {
                    $item .= '<h4 class="wpmf-card-title text-center wpmf-gallery-caption">' . wptexturize($attachment->post_excerpt) . '</h4>';
                }
                $item .= '</div>';
                $item .= '</div>';
                $item .= '</figure>';
                break;
            case 'square_grid':
                if ($custom_link !== '') {
                    $icon = '<a '. $hover_color_style .' href="' . $custom_link . '" class="wpmf_overlay '. $video_class .'" target="' . $link_target . '">'. $hover_box .'</a>';
                    $icon .= $social;
                } else {
                    switch ($link) {
                        case 'none':
                            $icon = '<div '. $hover_color_style .' class="wpmf_overlay '. $video_class .'">'. $hover_box .'</div>';
                            $icon .= $social;
                            break;

                        case 'post':
                            $url = get_attachment_link($attachment->ID);
                            $icon = '<a '. $hover_color_style .' href="' . esc_url($url) . '" title="' . esc_attr($post_title) . '" class="wpmf_overlay '. $video_class .'" target="' . $link_target . '">'. $hover_box .'</a>';
                            $icon .= $social;
                            break;

                        default:
                            $lightboxUrls = $this->getLightboxUrl($attachment->ID, $targetsize);
                            $url = $lightboxUrls['url'];

                            $icon = '<a '. $hover_color_style .' data-swipe="1" data-href="' . esc_url($url) . '" data-title="' . esc_attr($post_title) . '"
class="wpmfgalleryaddonswipe wpmf_gallery_lightbox wpmf_overlay '. $video_class .'">'. $hover_box .'</a>';
                            $icon .= $social;
                    }
                }

                if (isset($gallery_configs['download_item']) && (int)$gallery_configs['download_item'] === 1) {
                    $icon .= '<a href="'.esc_url($downloads['download_link']).'" '. (($downloads['type'] === 'local') ? 'download' : '') .' class="wpmf_gallery_download_icon"><span class="material-icons-outlined"> file_download </span></a>';
                }

                $item = '<figure class="wpmf-gallery-item gallery-item" data-id="'. (int)$attachment->ID .'" data-index="'. esc_html($index) .'">';
                $item .= '<div class="wpmf-gallery-icon">';
                $item .= wpmfRenderVideoIcon($attachment->ID);
                $item .= $icon;
                $item .= '<div class="square_thumbnail">';
                $item .= '<div class="img_centered">';
                $item .= $image_output;
                $item .= '</div>';
                $item .= '</div>';
                $item .= '</div>';
                $item .= '</figure>';
                break;
            case 'masonry':
                if ($custom_link !== '') {
                    $icon = '<a '. $hover_color_style .' href="' . $custom_link . '" class="wpmf_overlay '. $video_class .'" target="' . $link_target . '">'. $hover_box .'</a>';
                    $icon .= $social;
                } else {
                    switch ($link) {
                        case 'none':
                            $icon = '<div '. $hover_color_style .' class="wpmf_overlay '. $video_class .'">'. $hover_box .'</div>';
                            $icon .= $social;
                            break;

                        case 'post':
                            $url = get_attachment_link($attachment->ID);
                            $icon = '<a '. $hover_color_style .' href="' . esc_url($url) . '" title="' . esc_attr($post_title) . '" class="wpmf_overlay '. $video_class .'" target="' . $link_target . '">'. $hover_box .'</a>';
                            $icon .= $social;
                            break;

                        default:
                            $lightboxUrls = $this->getLightboxUrl($attachment->ID, $targetsize);
                            $url = $lightboxUrls['url'];
                            $icon = '<a '. $hover_color_style .' data-swipe="1" data-href="' . esc_url($url) . '" data-title="' . esc_attr($post_title) . '"
class="wpmfgalleryaddonswipe wpmf_gallery_lightbox wpmf_overlay '. $video_class .'">'. $hover_box .'</a>';
                            $icon .= $social;
                    }
                }

                if (isset($gallery_configs['download_item']) && (int)$gallery_configs['download_item'] === 1) {
                    $icon .= '<a href="'.esc_url($downloads['download_link']).'" '. (($downloads['type'] === 'local') ? 'download' : '') .' class="wpmf_gallery_download_icon"><span class="material-icons-outlined"> file_download </span></a>';
                }
                $item = '<div class="wpmf-gallery-item" data-id="'. (int)$attachment->ID .'" data-tags="' . $img_tags . '" data-index="'. esc_html($index) .'">';
                $item .= '<div class="wpmf-gallery-icon">';
                $item .= wpmfRenderVideoIcon($attachment->ID);
                $item .= $icon . ' ' . $image_output;
                $item .= '</div>';
                $item .= '</div>';
                break;
            case 'custom_grid':
                if ($custom_link !== '') {
                    $icon = '<a '. $hover_color_style .' href="' . $custom_link . '" class="wpmf_overlay '. $video_class .'" target="' . $link_target . '">'. $hover_box .'</a>';
                    $icon .= $social;
                } else {
                    switch ($link) {
                        case 'none':
                            $icon = '<div '. $hover_color_style .' class="wpmf_overlay '. $video_class .'">'. $hover_box .'</div>';
                            $icon .= $social;
                            break;

                        case 'post':
                            $url = get_attachment_link($attachment->ID);
                            $icon = '<a '. $hover_color_style .' href="' . esc_url($url) . '" title="' . esc_attr($post_title) . '" class="wpmf_overlay '. $video_class .'" target="' . $link_target . '">'. $hover_box .'</a>';
                            $icon .= $social;
                            break;

                        default:
                            $lightboxUrls = $this->getLightboxUrl($attachment->ID, $targetsize);
                            $url = $lightboxUrls['url'];
                            $icon = '<a '. $hover_color_style .' data-swipe="1" data-href="' . esc_url($url) . '" data-title="' . esc_attr($post_title) . '"
class="wpmfgalleryaddonswipe wpmf_gallery_lightbox wpmf_overlay '. $video_class .'">'. $hover_box .'</a>';
                            $icon .= $social;
                    }
                }

                if (isset($gallery_configs['download_item']) && (int)$gallery_configs['download_item'] === 1) {
                    $icon .= '<a href="'.esc_url($downloads['download_link']).'" '. (($downloads['type'] === 'local') ? 'download' : '') .' class="wpmf_gallery_download_icon"><span class="material-icons-outlined"> file_download </span></a>';
                }
                $item = '<div class="wpmf-gallery-item grid-item" data-id="'. (int)$attachment->ID .'" data-tags="' . $img_tags . '" data-index="'. esc_html($index) .'">';
                $item .= '<div class="wpmf-gallery-icon">';
                $item .= wpmfRenderVideoIcon($attachment->ID);
                $item .= $icon . ' ' . $image_output;
                $item .= '</div>';
                $item .= '</div>';
                break;
            case 'default':
                if ($custom_link !== '') {
                    $icon = '<a '. $hover_color_style .' href="' . $custom_link . '" class="wpmf_overlay '. $video_class .'" target="' . $link_target . '">'. $hover_box .'</a>';
                    $icon .= $social;
                } else {
                    switch ($link) {
                        case 'none':
                            $icon = '<div '. $hover_color_style .' class="wpmf_overlay '. $video_class .'">'. $hover_box .'</div>';
                            $icon .= $social;
                            break;

                        case 'post':
                            $url = get_attachment_link($attachment->ID);
                            $icon = '<a '. $hover_color_style .' href="' . esc_url($url) . '" title="' . esc_attr($post_title) . '" class="wpmf_overlay '. $video_class .'" target="' . $link_target . '">'. $hover_box .'</a>';
                            $icon .= $social;
                            break;

                        default:
                            $lightboxUrls = $this->getLightboxUrl($attachment->ID, $targetsize);
                            $url = $lightboxUrls['url'];

                            $icon = '<a '. $hover_color_style .' data-swipe="1" data-href="' . esc_url($url) . '" data-title="' . esc_attr($post_title) . '"
class="wpmfgalleryaddonswipe wpmf_gallery_lightbox wpmf_overlay '. $video_class .'">'. $hover_box .'</a>';
                            $icon .= $social;
                    }
                }

                if (isset($gallery_configs['download_item']) && (int)$gallery_configs['download_item'] === 1) {
                    $icon .= '<a href="'.esc_url($downloads['download_link']).'" '. (($downloads['type'] === 'local') ? 'download' : '') .' class="wpmf_gallery_download_icon"><span class="material-icons-outlined"> file_download </span></a>';
                }

                $item = '<figure class="wpmf-gallery-item gallery-item" data-id="'. (int)$attachment->ID .'" data-index="'. esc_html($index) .'">';
                $item .= '<div class="wpmf-gallery-icon">';
                $item .= wpmfRenderVideoIcon($attachment->ID);
                $item .= $icon;
                $item .= '<div class="square_thumbnail">';
                $item .= '<div class="img_centered">';
                $item .= $image_output;
                $item .= '</div>';
                $item .= '</div>';
                $item .= '</div>';
                if (trim($attachment->post_excerpt) !== '') {
                    $item .= '<h4 class="wpmf-card-title text-center wpmf-gallery-caption">' . wptexturize($attachment->post_excerpt) . '</h4>';
                } else {
                    $item .= '<h4 class="wpmf-card-title text-center wpmf-gallery-caption">' . wptexturize($attachment->post_title) . '</h4>';
                }
                $item .= '</figure>';
                break;
            case 'portfolio':
                if ($custom_link !== '') {
                    $icon = '<a '. $hover_color_style .' href="' . $custom_link . '" class="wpmf_overlay '. $video_class .'" target="' . $link_target . '">'. $hover_box .'</a>';
                    $icon .= '<a class="portfolio_lightbox" href="' . $custom_link . '" title="' . esc_attr($post_title) . '" target="' . $link_target . '">+</a>';
                    $icon .= $social;
                } else {
                    switch ($link) {
                        case 'none':
                            $icon = '<div '. $hover_color_style .' class="wpmf_overlay '. $video_class .'">'. $hover_box .'</div><span class="hide_icon portfolio_lightbox" title="' . esc_attr($post_title) . '">+</span>';
                            $icon .= $social;
                            break;

                        case 'post':
                            $url = get_attachment_link($attachment->ID);
                            $icon = '<a '. $hover_color_style .' href="' . esc_url($url) . '" title="' . esc_attr($post_title) . '" class="wpmf_overlay '. $video_class .'" target="' . $link_target . '">'. $hover_box .'</a>';
                            $icon .= '<a data-swipe="0" class="portfolio_lightbox" href="' . esc_url($url) . '" title="' . esc_attr($post_title) . '"">+</a>';
                            $icon .= $social;
                            break;

                        default:
                            $lightboxUrls = $this->getLightboxUrl($attachment->ID, $targetsize);
                            $url = $lightboxUrls['url'];

                            $icon = '<a '. $hover_color_style .' data-swipe="1" data-href="' . esc_url($url) . '" data-title="' . esc_attr($post_title) . '"
class="wpmfgalleryaddonswipe wpmf_overlay '. $video_class .'">'. $hover_box .'</a>';
                            $icon .= '<a data-swipe=1 class="wpmfgalleryaddonswipe portfolio_lightbox '. ((!empty($remote_video) || $video) ? 'isvideo' : '') .'"
href="' . esc_url($url) . '" data-title="' . esc_attr($post_title) . '">+</a>';
                            $icon .= $social;
                    }
                }

                if (isset($gallery_configs['download_item']) && (int)$gallery_configs['download_item'] === 1) {
                    $icon .= '<a href="'.esc_url($downloads['download_link']).'" '. (($downloads['type'] === 'local') ? 'download' : '') .' class="wpmf_gallery_download_icon"><span class="material-icons-outlined"> file_download </span></a>';
                }
                $item = '<figure class="wpmf-gallery-item" data-id="'. (int)$attachment->ID .'" data-tags="' . $img_tags . '" data-index="'. esc_html($index) .'">';
                $item .= '<div class="wpmf-gallery-icon">';
                $item .= wpmfRenderVideoIcon($attachment->ID);
                $item .= $icon;
                $item .= '<div class="square_thumbnail">';
                $item .= '<div class="img_centered">';
                $item .= $image_output;
                $item .= '</div>';
                $item .= '</div>';
                $item .= '</div>';
                if (trim($attachment->post_excerpt) || trim($attachment->post_title)) {
                    $item .= "<div class='wpmf-caption-text wpmf-gallery-caption'>";
                    if (trim($attachment->post_title)) {
                        $item .= "<span class='title'>" . wptexturize($attachment->post_title) . ' </span><br>';
                    }

                    if (trim($attachment->post_excerpt)) {
                        $item .= "<span class='excerpt'>" . wptexturize($attachment->post_excerpt) . '</span>';
                    }
                    $item .= '</div>';
                }
                $item .= '</figure>';
                break;
        }

        return $item;
    }

    /**
     * Render hover box for image
     *
     * @param object $attachment Attachment info
     * @param array  $params     Gallery settings
     *
     * @return array
     */
    public function renderHoverStyle($attachment, $params = array())
    {
        $hover_color_style = 'style="background: ' . wpmfConvertHex2rgba($params['hover_color'], $params['hover_opacity']) . '"';
        $title_style = 'style="color: '. $params['hover_title_color'] .'; font-size:'. $params['hover_title_size'] .'px"';
        $desc_style = 'style="color: '. $params['hover_desc_color'] .'; font-size:'. $params['hover_desc_size'] .'px"';
        $title_position_class = (isset($params['hover_title_position'])) ? $params['hover_title_position'] : 'center_center';
        $desc_position_class = (isset($params['hover_desc_position'])) ? $params['hover_desc_position'] : 'none';
        $box_hover_class = '';
        if ($title_position_class === $desc_position_class) {
            $box_hover_class = 'wpmf_box_same_position wpmf_box_' . $title_position_class;
        }

        $hover_box = '';
        if ($params['hover_title_position'] !== 'none' || $params['hover_desc_position'] !== 'none') {
            $hover_box .= '<div class="wpmf_hover_box '. $box_hover_class .'">';
            if ($params['hover_title_position'] !== 'none' && $attachment->post_title !== '') {
                $hover_box .= '<h4 class="wpmf_text wpmf_text_'. $title_position_class .'" '. $title_style .'>' . wptexturize($attachment->post_title) . '</h4>';
            }

            if ($params['hover_desc_position'] !== 'none' && $attachment->post_excerpt !== '') {
                $hover_box .= '<p class="wpmf_text wpmf_text_'. $desc_position_class .'" '. $desc_style .'>' . wptexturize($attachment->post_excerpt) . '</p>';
            }
            $hover_box .= '</div>';
        }

        return array(
            'hover_color_style' => $hover_color_style,
            'hover_box' => $hover_box,
        );
    }

    /**
     * Load gallery
     *
     * @return void
     */
    public function loadGallery()
    {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- load image gallery on frontend
        if (empty($_POST['gallery_id'])) {
            wp_send_json(array('status' => false));
        }

        $gallery_id = $_POST['gallery_id'];
        $selector = $_POST['selector'];
        /* get all params */
        $galleries           = get_option('wpmf_galleries');
        $gallery_configs     = get_option('wpmf_gallery_settings');
        $lazy_load = (isset($gallery_configs['progressive_loading']) && (int) $gallery_configs['progressive_loading'] === 0) ? false : true;
        $social = $this->getSocialHtml();
        // get params
        $id = intval($gallery_id);
        if (empty($galleries[$id]['theme'])) {
            $display = 'default';
        } else {
            $display = $galleries[$id]['theme'];
        }

        $params = $this->getSettingsFront($id, $_POST['settings'], $gallery_configs, $galleries, $display);
        // verify orderby option
        $params = $this->modifyOrderOptions($params);
        foreach ($params as $attr_key => $setting) {
            ${$attr_key} = $setting;
        }
        $lazy_load = ((isset($gallery_configs['progressive_loading']) && (int)$gallery_configs['progressive_loading'] === 0) || is_admin()) ? false : true;
        if (isset($is_lazy_load) && (int)$is_lazy_load === 0) {
            $lazy_load = false;
        }
        $caption_lightbox = wpmfGetOption('caption_lightbox_gallery');
        /* Query images */
        $tax_query = wpmfGalleryAddonGetTaxQuery($id, $params);
        $args = array(
            'posts_per_page' => - 1,
            'post_status'    => 'any',
            'post_type' => 'attachment',
            'post_mime_type' => wpmfGalleryAddonGetImageType(),
            'tax_query'      => $tax_query
        );

        if (($wpmf_orderby !== 'post__in' && $wpmf_orderby !== 'rand') || ($wpmf_orderby === 'rand' && !$lazy_load)) {
            $args['orderby'] = $wpmf_orderby;
            $args['order']   = $wpmf_order;
        }

        $query        = new WP_Query($args);
        $_attachments = $query->get_posts();
        $attachments  = array();
        foreach ($_attachments as &$val) {
            $order = get_post_meta((int)$val->ID, 'wpmf_gallery_order', true);
            $val->order = (int) $order;
            if (isset($_POST['tags']) && $_POST['tags'] !== '*') {
                $i_tags = get_post_meta($val->ID, 'wpmf_img_tags', true);
                $i_tags = explode(',', $i_tags);

                $i_trim_tags = array();
                foreach ($i_tags as $i_tag) {
                    $i_trim_tags[] = trim($i_tag);
                }

                if (in_array(esc_html($_POST['tags']), $i_trim_tags)) {
                    $attachments[] = $val;
                }
            } else {
                $attachments[] = $val;
            }
        }

        if ($wpmf_orderby === 'post__in') {
            usort($attachments, 'wpmfSortByOrder');
        }

        $class      = array();
        $class[] = 'wpmf-gallery-addon-wrap';
        $class[] = 'gallery-link-' . $link;

        /* Create output html */
        $output = '';
        $output .= "<img class='loading_gallery' src='" . WPMF_GALLERY_ADDON_PLUGIN_URL . "/assets/images/Loading_icon.gif' />";

        // render Tabs
        $tag_value = (isset($_POST['tags'])) ? $_POST['tags'] : '*';
        $output .= $this->renderTabsFilter($display_tag, $_attachments, $tag_value);

        // phpcs:enable
        ob_start();
        if (isset($display)) {
            require(WPMF_GALLERY_ADDON_PLUGIN_DIR . 'frontend/themes-gallery/' . $display . '.php');
        } else {
            require(WPMF_GALLERY_ADDON_PLUGIN_DIR . 'frontend/themes-gallery/default.php');
        }

        $output .= ob_get_contents();
        ob_end_clean();
        wp_send_json(array('status' => true, 'html' => $output));
    }

    /**
     * Get settings front
     *
     * @param integer $id                      Gallery ID
     * @param array   $request_settings        Request settings
     * @param array   $gallery_default_configs Gallery default configs
     * @param array   $galleries               Gallery configs
     * @param string  $theme                   Gallery theme
     *
     * @return array
     */
    public function getSettingsFront($id, $request_settings, $gallery_default_configs, $galleries, $theme)
    {
        $default = array(
            'columns'      => 3,
            'gutterwidth'  => 5,
            'link'         => 'post',
            'size'         => 'thumbnail',
            'targetsize'   => 'large',
            'wpmf_orderby'      => 'post__in',
            'wpmf_order'        => 'ASC',
            'customlink'   => 0,
            'class'        => '',
            'include'      => '',
            'exclude'      => '',
            'display_tree' => 0,
            'display_tag'  => 0,
            'img_border_radius' => 0,
            'border_width' => 0,
            'border_color' => 'transparent',
            'border_style' => 'solid',
            'img_shadow' => '',
            'show_buttons' => 1,
            'animation' => 'slide',
            'duration' => 4000,
            'auto_animation' => 1,
            'number_lines' => 1
        );

        if (isset($request_settings)) {
            $settings = $request_settings;
        } else {
            $settings = array_merge($gallery_default_configs['theme'][$theme . '_theme'], $galleries[$id]);
        }

        $settings = array_merge($default, $settings);
        return $settings;
    }

    /**
     * Render tabs filter
     *
     * @param integer $enable       Enable or disable
     * @param array   $_attachments Attachments
     * @param string  $tag_value    Current tag value
     *
     * @return string
     */
    public function renderTabsFilter($enable, $_attachments, $tag_value)
    {
        ob_start();
        $html = '';
        if ((int) $enable === 1) {
            $tags   = array();
            foreach ($_attachments as $value) {
                $img_tags = get_post_meta($value->ID, 'wpmf_img_tags', true);
                $img_tags = explode(',', $img_tags);
                foreach ($img_tags as $img_tag) {
                    if (trim($img_tag) !== '') {
                        $tags[] = trim($img_tag);
                    }
                }
            }

            natcasesort($tags);
            if (count(array_unique($tags)) > 0) {
                echo '<div class="wpmf-gridblock-filters">';
                echo '<ul class="tabs gridblock-filter-categories">';
                if (empty($tag_value) || (!empty($tag_value) && $tag_value === '*')) {
                    echo '<li class="tab filter-all-control selected">';
                } else {
                    echo '<li class="tab filter-all-control">';
                }

                echo '<a data-filter="*">' . esc_html__('All', 'wp-media-folder-gallery-addon') . '</a>';
                echo '</li>';
                foreach (array_unique($tags) as $tag) {
                    if ($tag !== '') {
                        if ($tag === esc_html($tag_value)) {
                            echo '<li class="tab filter-all-control selected">';
                        } else {
                            echo '<li class="tab filter-all-control">';
                        }
                        // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
                        echo '<a data-filter="' . esc_attr($tag) . '" class="">' . strtoupper($tag) . '</a>';
                        echo '</li>';
                    }
                }
                echo '</ul></div>';
            }
        }

        $html .= ob_get_contents();
        ob_end_clean();

        return $html;
    }
}
