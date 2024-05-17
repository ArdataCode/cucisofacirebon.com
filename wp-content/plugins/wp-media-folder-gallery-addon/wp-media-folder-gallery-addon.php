<?php
/*
  Plugin Name: WP Media folder Gallery Addon
  Plugin URI: http://www.joomunited.com
  Description: WP Media Folder Gallery Addon enhances WPMF plugin by adding a full image gallery management
  Author: Joomunited
  Version: 2.4.7
  Update URI: https://www.joomunited.com/juupdater_files/wp-media-folder-gallery-addon.json
  Author URI: http://www.joomunited.com
  Text Domain: wp-media-folder-gallery-addon
  Domain Path: /languages
  Licence : GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
  Copyright : Copyright (C) 2014 JoomUnited (http://www.joomunited.com). All rights reserved.
 */
// Prohibit direct script loading
defined('ABSPATH') || die('No direct script access allowed!');

//Check plugin requirements
if (version_compare(PHP_VERSION, '5.6', '<')) {
    if (!function_exists('wpmfGalleryShowError')) {
        /**
         * Show notice
         *
         * @return void
         */
        function wpmfGalleryShowError()
        {
            echo '<div class="error"><p>';
            echo '<strong>WP Media Folder Gallery Addon</strong>';
            echo ' need at least PHP 5.6 version, please update php before installing the plugin.</p></div>';
        }
    }

    //Add actions
    add_action('admin_notices', 'wpmfGalleryShowError');
    //Do not load anything more
    return;
}
if (!defined('WPMF_GALLERY_ADDON_PLUGIN_DIR')) {
    /**
     * Path to WP Media Folder Gallery addon plugin
     */
    define('WPMF_GALLERY_ADDON_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('WPMF_GALLERY_ADDON_PLUGIN_URL')) {
    /**
     * Url to WP Media Folder Gallery addon plugin
     */
    define('WPMF_GALLERY_ADDON_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('WPMF_GALLERY_ADDON_FILE')) {
    /**
     * Path to this file
     */
    define('WPMF_GALLERY_ADDON_FILE', __FILE__);
}

if (!defined('WPMF_GALLERY_ADDON_DOMAIN')) {
    /**
     * Text domain
     */
    define('WPMF_GALLERY_ADDON_DOMAIN', 'wp-media-folder-gallery-addon');
}

if (!defined('WPMF_GALLERY_ADDON_VERSION')) {
    /**
     * Plugin version
     */
    define('WPMF_GALLERY_ADDON_VERSION', '2.4.7');
}

if (!defined('WPMF_GALLERY_ADDON_TAXO')) {
    /**
     * Gallery taxonomy name
     */
    define('WPMF_GALLERY_ADDON_TAXO', 'wpmf-gallery-category');
}

//JUtranslation
add_filter('wpmf_get_addons', function ($addons) {
    $addon                          = new stdClass();
    $addon->main_plugin_file        = __FILE__;
    $addon->extension_name          = 'WP Media Folder Gallery Addon';
    $addon->extension_slug          = 'wpmf-gallery-addon';
    $addon->text_domain             = 'wp-media-folder-gallery-addon';
    $addon->language_file           = plugin_dir_path(__FILE__) . 'languages' . DIRECTORY_SEPARATOR . 'wp-media-folder-gallery-addon-en_US.mo';
    $addons[$addon->extension_slug] = $addon;
    return $addons;
});

/**
 * Load Jutranslation
 *
 * @return void
 */
function wpmfGalleryAddonsInit()
{
    if (!class_exists('\Joomunited\WPMFGALLERYADDON\JUCheckRequirements')) {
        require_once(trailingslashit(dirname(__FILE__)) . 'requirements.php');
    }

    if (class_exists('\Joomunited\WPMFGALLERYADDON\JUCheckRequirements')) {
        // Plugins name for translate
        $args = array(
            'plugin_name' => esc_html__('WP Media Folder Gallery Addon', 'wp-media-folder-gallery-addon'),
            'plugin_path' => 'wp-media-folder-gallery-addon/wp-media-folder-gallery-addon.php',
            'plugin_textdomain' => 'wp-media-folder-gallery-addon',
            'requirements' => array(
                'plugins'     => array(
                    array(
                        'name' => 'WP Media Folder',
                        'path' => 'wp-media-folder/wp-media-folder.php',
                        'requireVersion' => '4.7.2'
                    )
                ),
                'php_version' => '5.6'
            )
        );
        $wpmfCheck = call_user_func('\Joomunited\WPMFGALLERYADDON\JUCheckRequirements::init', $args);

        if (!$wpmfCheck['success']) {
            // Do not load anything more
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
            unset($_GET['activate']);
            return;
        }
    }
}

/**
 * Get plugin path
 *
 * @return string
 */
function wpmfGalleryAddons_getPath()
{
    return 'wp-media-folder-gallery-addon/wp-media-folder-gallery-addon.php';
}

include_once(ABSPATH . 'wp-admin/includes/plugin.php');

register_activation_hook(__FILE__, 'wpmfGalleryInstall');

/**
 * Add some options
 *
 * @return void
 */
function wpmfGalleryInstall()
{
    /* create number of items per page for image selection */
    if (!get_option('wpmf_gallery_img_per_page', false)) {
        update_option('wpmf_gallery_img_per_page', 20);
    }

    if (!get_option('wpmfgrl_relationships_media', false)) {
        add_option('wpmfgrl_relationships_media', array(), '', 'yes');
    }
}

/**
 * Sort parents before children
 * http://stackoverflow.com/questions/6377147/sort-an-array-placing-children-beneath-parents
 *
 * @param array   $objects List folder
 * @param array   $result  Result
 * @param integer $parent  Parent of folder
 * @param integer $depth   Depth of folder
 *
 * @return array           output
 */
function wpmfParentSort(array $objects, array &$result = array(), $parent = 0, $depth = 0)
{
    foreach ($objects as $key => $object) {
        $order = get_term_meta($object->term_id, 'wpmf_order', true);
        if (empty($order)) {
            $order = 0;
        }
        $object->order = $order;

        if ((int) $object->parent === (int) $parent) {
            $object->depth = $depth;
            array_push($result, $object);
            unset($objects[$key]);
            wpmfParentSort($objects, $result, $object->term_id, $depth + 1);
        }
    }
    return $result;
}

/**
 * Order attachment by order
 *
 * @param integer $a Item details
 * @param integer $b Item details
 *
 * @return mixed
 */
function wpmfSortByOrder($a, $b)
{
    return $a->order - $b->order;
}

/* Register WPMF_GALLERY_ADDON_TAXO taxonomy */
add_action('init', 'wpmfGalleryRegisterTaxonomy', 0);
/**
 * Register gallery taxonomy
 *
 * @return void
 */
function wpmfGalleryRegisterTaxonomy()
{
    if (!taxonomy_exists('wpmf-category')) {
        register_taxonomy(
            'wpmf-category',
            'attachment',
            array(
                'hierarchical' => true,
                'show_in_nav_menus' => false,
                'show_ui' => false,
                'public' => false,
                'labels' => array(
                    'name' => __('WPMF Categories', 'wp-media-folder-gallery-addon'),
                    'singular_name' => __('WPMF Category', 'wp-media-folder-gallery-addon'),
                    'menu_name' => __('WPMF Categories', 'wp-media-folder-gallery-addon'),
                    'all_items' => __('All WPMF Categories', 'wp-media-folder-gallery-addon'),
                    'edit_item' => __('Edit WPMF Category', 'wp-media-folder-gallery-addon'),
                    'view_item' => __('View WPMF Category', 'wp-media-folder-gallery-addon'),
                    'update_item' => __('Update WPMF Category', 'wp-media-folder-gallery-addon'),
                    'add_new_item' => __('Add New WPMF Category', 'wp-media-folder-gallery-addon'),
                    'new_item_name' => __('New WPMF Category Name', 'wp-media-folder-gallery-addon'),
                    'parent_item' => __('Parent WPMF Category', 'wp-media-folder-gallery-addon'),
                    'parent_item_colon' => __('Parent WPMF Category:', 'wp-media-folder-gallery-addon'),
                    'search_items' => __('Search WPMF Categories', 'wp-media-folder-gallery-addon'),
                )
            )
        );
    }

    /* get image term selection */
    $glr_selection = get_term_by('name', 'Gallery Upload', 'wpmf-category');
    if (!$glr_selection) {
        $inserted = wp_insert_term('Gallery Upload', 'wpmf-category', array());
        if (!is_wp_error($inserted)) {
            $relationships = array($inserted['term_id']);
            update_option('wpmfgrl_relationships', $relationships);
        }
    }

    register_taxonomy(WPMF_GALLERY_ADDON_TAXO, 'attachment', array(
        'hierarchical' => true,
        'show_in_nav_menus' => false,
        'show_ui' => false,
        'public' => false,
        'labels' => array(
            'name' => __('WPMF Gallery Categories', 'wp-media-folder-gallery-addon'),
            'singular_name' => __('WPMF Gallery Category', 'wp-media-folder-gallery-addon'),
            'menu_name' => __('WPMF Gallery Categories', 'wp-media-folder-gallery-addon'),
            'all_items' => __('All WPMF Gallery Categories', 'wp-media-folder-gallery-addon'),
            'edit_item' => __('Edit WPMF Gallery Category', 'wp-media-folder-gallery-addon'),
            'view_item' => __('View WPMF Gallery Category', 'wp-media-folder-gallery-addon'),
            'update_item' => __('Update WPMF Gallery Category', 'wp-media-folder-gallery-addon'),
            'add_new_item' => __('Add New WPMF Gallery Category', 'wp-media-folder-gallery-addon'),
            'new_item_name' => __('New WPMF Gallery Category Name', 'wp-media-folder-gallery-addon'),
            'parent_item' => __('Parent WPMF Gallery Category', 'wp-media-folder-gallery-addon'),
            'parent_item_colon' => __('Parent WPMF Gallery Category:', 'wp-media-folder-gallery-addon'),
            'search_items' => __('Search WPMF Gallery Categories', 'wp-media-folder-gallery-addon'),
        ),
    ));
}

require_once(WPMF_GALLERY_ADDON_PLUGIN_DIR . 'admin/class/wp-media-folder-gallery-addon.php');
new WpmfGlrAddonAdmin;

global $wpmfGalleryFront;
require_once(WPMF_GALLERY_ADDON_PLUGIN_DIR . 'frontend/class/wp-media-folder-gallery-addon.php');
$wpmfGalleryFront = new WpmfGlrAddonFrontEnd;


/**
 * Load elementor widget
 *
 * @return void
 */
function wpmfGalleryAddonLoadElementorWidget()
{
    require_once(WPMF_GALLERY_ADDON_PLUGIN_DIR . 'elementor-widgets/class-gallery-elementor-widget.php');
    \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \WpmfGalleryAddonElementorWidget());
}

add_action('elementor/widgets/widgets_registered', 'wpmfGalleryAddonLoadElementorWidget');

add_action('elementor/frontend/before_register_scripts', function () {
    wp_enqueue_script(
        'wordpresscanvas-imagesloaded',
        WPMF_PLUGIN_URL . '/assets/js/display-gallery/imagesloaded.pkgd.min.js',
        array(),
        '3.1.5',
        true
    );
    wp_enqueue_script('wpmfisotope', WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/js/isotope.pkgd.js', array('jquery'), WPMF_GALLERY_ADDON_VERSION, true);
    wp_enqueue_script('wpmfpackery', WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/packery/packery.pkgd.min.js', array('jquery'), WPMF_GALLERY_ADDON_VERSION, true);
    wp_enqueue_script(
        'wpmf-justified-script',
        WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/js/justified-gallery/jquery.justifiedGallery.min.js',
        array('jquery'),
        WPMF_GALLERY_ADDON_VERSION,
        true
    );
    wp_enqueue_script(
        'wpmf-slick-script',
        WPMF_PLUGIN_URL . 'assets/js/slick/slick.min.js',
        array('jquery'),
        WPMF_VERSION,
        true
    );
    wp_enqueue_script(
        'wpmf-flipster-js',
        WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/js/jquery.flipster.js',
        array('jquery'),
        WPMF_GALLERY_ADDON_VERSION,
        true
    );
});

add_action('elementor/editor/before_enqueue_scripts', function () {
    wp_enqueue_script(
        'wordpresscanvas-imagesloaded',
        WPMF_PLUGIN_URL . '/assets/js/display-gallery/imagesloaded.pkgd.min.js',
        array(),
        '3.1.5',
        true
    );
    wp_enqueue_script('wpmfisotope', WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/js/isotope.pkgd.js', array('jquery'), WPMF_GALLERY_ADDON_VERSION, true);
    wp_enqueue_script('wpmfpackery', WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/packery/packery.pkgd.min.js', array('jquery'), WPMF_GALLERY_ADDON_VERSION, true);
    wp_enqueue_script(
        'wpmf-justified-script',
        WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/js/justified-gallery/jquery.justifiedGallery.min.js',
        array('jquery'),
        WPMF_GALLERY_ADDON_VERSION,
        true
    );
    wp_enqueue_script(
        'wpmf-slick-script',
        WPMF_PLUGIN_URL . 'assets/js/slick/slick.min.js',
        array('jquery'),
        WPMF_VERSION,
        true
    );

    wp_enqueue_script(
        'wpmf-flipster-js',
        WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/js/jquery.flipster.js',
        array('jquery'),
        WPMF_GALLERY_ADDON_VERSION,
        true
    );
    wp_enqueue_script(
        'wpmf-gallery-js',
        WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/js/gallery.js',
        array('jquery', 'wpmfisotope', 'wpmf-justified-script'),
        WPMF_GALLERY_ADDON_VERSION,
        true
    );

    $option_current_theme = get_option('current_theme');
    $gallery_configs = get_option('wpmf_gallery_settings');

    if (isset($gallery_configs['progressive_loading']) && (int)$gallery_configs['progressive_loading'] === 0) {
        $progressive_loading = 0;
    } else {
        $progressive_loading = 1;
    }

    wp_localize_script('wpmf-gallery-js', 'wpmfgallery', array(
        'wpmf_current_theme' => $option_current_theme,
        'gallery_configs' => $gallery_configs,
        'progressive_loading' => (int)$progressive_loading,
        'wpmf_gallery_nonce' => wp_create_nonce('wpmf_gallery_nonce'),
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
});

/**
 * Enqueue script in divi gallery addon module
 *
 * @return void
 */
function wpmfInitGalleryAddonDivi()
{
    require_once(WPMF_GALLERY_ADDON_PLUGIN_DIR . 'frontend/class/wp-media-folder-gallery-addon.php');
    $gallery_addon = new WpmfGlrAddonFrontEnd;
    $gallery_addon->galleryScripts();
    $gallery_addon->enqueueScript('divi');
}

add_action('wpmf_init_gallery_addon_divi', 'wpmfInitGalleryAddonDivi');

/**
 * Enqueue script in bakery gallery addon module
 *
 * @return void
 */
function wpmfVcInitGalleryAddon()
{
    require_once WPMF_GALLERY_ADDON_PLUGIN_DIR . '/bakery-widgets/Gallery.php';
}

add_action('wpmf_vc_init_gallery_addon', 'wpmfVcInitGalleryAddon');

/**
 * This action registers all styles(fonts) to be enqueue later
 *
 * @return void
 */
function wpmfAddonVcEnqueueJsCss()
{
    wp_enqueue_script('jquery-masonry');
}

add_action('vc_frontend_editor_enqueue_js_css', 'wpmfAddonVcEnqueueJsCss');

if (is_plugin_active('wp-media-folder/wp-media-folder.php')) {
    if (!function_exists('wpmfGalleryAddonTnitAvada')) {
        /**
         * Init Avada module
         *
         * @return void
         */
        function wpmfGalleryAddonTnitAvada()
        {
            if (!defined('AVADA_VERSION') || !defined('FUSION_BUILDER_VERSION')) {
                return;
            }

            require_once WPMF_GALLERY_ADDON_PLUGIN_DIR . 'avada-widgets/Gallery.php';
            if (fusion_is_builder_frame()) {
                add_action('fusion_builder_enqueue_live_scripts', 'wpmfAddonAvadaEnqueueSeparateLiveScripts');
            }
            add_action('fusion_builder_admin_scripts_hook', 'fusion_builder_admin_scripts_hook');
        }

        add_action('init', 'wpmfGalleryAddonTnitAvada');
    }
}

/**
 * Avada enqueue admin scripts
 *
 * @return void
 */
function fusion_builder_admin_scripts_hook()
{
    wp_enqueue_script('wpmf_fusion_admin_gallery_addon_element', WPMF_GALLERY_ADDON_PLUGIN_URL . '/avada-widgets/js/avada_backend.js', array(), WPMF_GALLERY_ADDON_VERSION, true);
}

/**
 * Avada enqueue live scripts
 *
 * @return void
 */
function wpmfAddonAvadaEnqueueSeparateLiveScripts()
{
    wp_enqueue_script('jquery-masonry');
    $js_folder_url = FUSION_LIBRARY_URL . '/assets' . ((true === FUSION_LIBRARY_DEV_MODE) ? '' : '/min') . '/js';
    wp_enqueue_script('isotope', $js_folder_url . '/library/isotope.js', array(), FUSION_BUILDER_VERSION, true);
    wp_enqueue_script('packery', $js_folder_url . '/library/packery.js', array(), FUSION_BUILDER_VERSION, true);
    wp_enqueue_script('images-loaded', $js_folder_url . '/library/imagesLoaded.js', array(), FUSION_BUILDER_VERSION, true);
    wp_enqueue_script(
        'wpmf-fusion-slick-script',
        WPMF_PLUGIN_URL . 'assets/js/slick/slick.min.js',
        array('jquery'),
        WPMF_VERSION,
        true
    );

    wp_enqueue_script(
        'wpmf-justified-script',
        WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/js/justified-gallery/jquery.justifiedGallery.min.js',
        array('jquery'),
        WPMF_GALLERY_ADDON_VERSION,
        true
    );

    wp_enqueue_script(
        'wpmf-fusion-flipster-js',
        WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/js/jquery.flipster.js',
        array('jquery'),
        WPMF_GALLERY_ADDON_VERSION,
        true
    );

    wp_enqueue_script('wpmf_fusion_view_gallery_addon_element', WPMF_GALLERY_ADDON_PLUGIN_URL . '/avada-widgets/js/avada.js', array(), WPMF_GALLERY_ADDON_VERSION, true);
}

/**
 * Get gallery default params
 *
 * @return array
 */
function wpmfGalleryAddonGetDefaultParams()
{
    return array(
        'layout' => 'vertical',
        'row_height' => 200,
        'aspect_ratio' => 'default',
        'columns' => 3,
        'size' => 'medium',
        'targetsize' => 'large',
        'link' => 'file',
        'wpmf_orderby' => 'date',
        'wpmf_order' => 'DESC',
        'display_tree' => 0,
        'display_tag' => 0,
        'animation' => 'slide',
        'duration' => 4000,
        'auto_animation' => 1,
        'number_lines' => 1,
        'show_buttons' => 1,
        'auto_from_folder' => 1,
        'tree_width' => 250,
        'folder' => 0,
        'include_children' => 0,
        'hover_color' => '#000',
        'hover_opacity' => '0.4',
        'hover_title_position' => 'center_center',
        'hover_title_size' => 16,
        'hover_title_color' => '#fff',
        'hover_desc_position' => 'none',
        'hover_desc_size' => 14,
        'hover_desc_color' => '#fff',
        'gutterwidth' => 5
    );
}

/**
 * Convert hex to rgba
 *
 * @param string          $color   Color code
 * @param boolean|integer $opacity Opacity
 *
 * @return string
 */
function wpmfConvertHex2rgba($color, $opacity = false)
{
    $default = 'rgb(0,0,0)';
    if (empty($color)) {
        return $default;
    }

    if ($color[0] === '#') {
        $color = substr($color, 1);
    }

    if (strlen($color) === 6) {
        $hex = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
    } elseif (strlen($color) === 3) {
        $hex = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
    } else {
        return $default;
    }

    $rgb = array_map('hexdec', $hex);
    if ($opacity) {
        if (abs($opacity) > 1) {
            $opacity = 1.0;
        }

        $output = 'rgba(' . implode(',', $rgb) . ',' . $opacity . ')';
    } else {
        $output = 'rgb(' . implode(',', $rgb) . ')';
    }

    return $output;
}

/**
 * Get gallery params
 *
 * @param integer $gallery_id Gallery ID
 *
 * @return array
 */
function wpmfGalleryAddonGetParams($gallery_id)
{
    $default_params = wpmfGalleryAddonGetDefaultParams();
    $gallery_configs = get_option('wpmf_gallery_settings');
    $galleries = get_option('wpmf_galleries');
    if (!empty($galleries[$gallery_id])) {
        $theme = $galleries[$gallery_id]['theme'];
        if (empty($gallery_configs) || empty($gallery_configs['theme'][$theme . '_theme'])) {
            $theme_configs = array();
        } else {
            $theme_configs = $gallery_configs['theme'][$theme . '_theme'];
            if (empty($theme_configs['wpmf_orderby']) && !empty($theme_configs['orderby'])) {
                $theme_configs['wpmf_orderby'] = $theme_configs['orderby'];
            }
            if (empty($theme_configs['wpmf_order']) && !empty($theme_configs['order'])) {
                $theme_configs['wpmf_order'] = $theme_configs['order'];
            }
        }

        if (empty($galleries[$gallery_id])) {
            $current_gallery_configs = array();
        } else {
            $current_gallery_configs = $galleries[$gallery_id];
            if (empty($current_gallery_configs['wpmf_orderby']) && !empty($current_gallery_configs['orderby'])) {
                $current_gallery_configs['wpmf_orderby'] = $current_gallery_configs['orderby'];
            }
            if (empty($current_gallery_configs['wpmf_order']) && !empty($current_gallery_configs['order'])) {
                $current_gallery_configs['wpmf_order'] = $current_gallery_configs['order'];
            }
        }

        $params = array_merge($default_params, $theme_configs, $current_gallery_configs);
    } else {
        $params = array();
    }

    return $params;
}

/**
 * Get image type
 *
 * @return array
 */
function wpmfGalleryAddonGetImageType()
{
    return array('image/jpeg', 'image/gif', 'image/png', 'image/bmp', 'image/tiff', 'image/x-icon', 'image/webp');
}

/**
 * Get tax query
 *
 * @param integer $gallery_id Gallery ID
 * @param array   $attrs      Attributes
 *
 * @return array
 */
function wpmfGalleryAddonGetTaxQuery($gallery_id, $attrs = array())
{
    $relationships = get_option('wpmfgrl_relationships');
    $params = wpmfGalleryAddonGetParams($gallery_id);
    $tax_query = array();
    $tax_query[] = array(
        'taxonomy'         => WPMF_GALLERY_ADDON_TAXO,
        'field'            => 'term_id',
        'terms'            => $gallery_id,
        'include_children' => (!empty($attrs['include_children'])) ? true : false
    );

    if (isset($relationships[$gallery_id])) {
        $tax_query['relation'] = 'OR';
        $tax_query[] = array(
            'taxonomy'         => WPMF_TAXO,
            'field'            => 'term_id',
            'terms'            => (int) $relationships[$gallery_id],
            'include_children' => false
        );
    }

    // get gallery from folder
    if (!empty($params) && (int)$params['auto_from_folder'] === 1 && !empty($params['folder'])) {
        $tax_query['relation'] = 'OR';
        $tax_query[] = array(
            'taxonomy' => WPMF_TAXO,
            'field' => 'term_id',
            'terms' => (int)$params['folder'],
            'include_children' => (!empty($attrs['include_children'])) ? true : false
        );
    }

    return $tax_query;
}

/**
 * Get video thumbnail ID
 *
 * @param integer $attachmentID Attachment ID
 *
 * @return integer
 */
function wpmfGalleryGetVideoThumbID($attachmentID)
{
    $video_thumbnail_id = get_post_meta($attachmentID, 'wpmf_video_thumbnail_id', true);
    $thumb_id = (!empty($video_thumbnail_id)) ? (int)$video_thumbnail_id : $attachmentID;
    return $thumb_id;
}

/**
 * Resort gallery
 *
 * @param array $a Gallery list
 * @param array $b Gallery list
 *
 * @return mixed
 */
function wpmfGalleryReorder($a, $b)
{
    return $a->order - $b->order;
}

/**
 * Get attachment download link
 *
 * @param integer $attachment_id Attachment ID
 *
 * @return false|string|string[]
 */
function wpmfGalleryGetDownloadLink($attachment_id)
{
    $drive_type = get_post_meta($attachment_id, 'wpmf_drive_type', true);
    if (empty($drive_type)) {
        $download_link = wp_get_attachment_image_url($attachment_id, 'full');
        $type = 'local';
    } else {
        $drive_id = get_post_meta($attachment_id, 'wpmf_drive_id', true);
        switch ($drive_type) {
            case 'onedrive':
                $download_link = admin_url('admin-ajax.php') . '?action=wpmf_onedrive_download&id=' . urlencode($drive_id) . '&link=true&dl=1';
                break;

            case 'onedrive_business':
                $download_link = admin_url('admin-ajax.php') . '?action=wpmf_onedrive_business_download&id=' . urlencode($drive_id) . '&link=true&dl=1';
                break;

            case 'google_drive':
                $download_link = admin_url('admin-ajax.php') . '?action=wpmf-download-file&id=' . urlencode($drive_id) . '&dl=1';
                break;

            case 'dropbox':
                $download_link = admin_url('admin-ajax.php') . '?action=wpmf-dbxdownload-file&id=' . urlencode($drive_id) . '&link=true&dl=1';
                break;
            default:
                $download_link = wp_get_attachment_image_url($attachment_id, 'full');
        }

        $download_link = str_replace('&amp;', '&', $download_link);
        $download_link = str_replace('&#038;', '&', $download_link);
        $type = 'cloud';
    }

    return array('download_link' => $download_link, 'type' => $type);
}

if (is_admin()) {
    if (!defined('JU_BASE')) {
        /**
         * Joomunited site url
         */
        define('JU_BASE', 'https://www.joomunited.com/');
    }

    $remote_updateinfo = JU_BASE . 'juupdater_files/wp-media-folder-gallery-addon.json';
    //end config

    require 'juupdater/juupdater.php';
    $UpdateChecker = Jufactory::buildUpdateChecker(
        $remote_updateinfo,
        __FILE__
    );
}

if (!function_exists('wpmfGalleryAddonPluginCheckForUpdates')) {
    /**
     * Plugin check for updates
     *
     * @param object $update      Update
     * @param array  $plugin_data Plugin data
     * @param string $plugin_file Plugin file
     *
     * @return array|boolean|object
     */
    function wpmfGalleryAddonPluginCheckForUpdates($update, $plugin_data, $plugin_file)
    {
        if ($plugin_file !== 'wp-media-folder-gallery-addon/wp-media-folder-gallery-addon.php') {
            return $update;
        }

        if (empty($plugin_data['UpdateURI']) || !empty($update)) {
            return $update;
        }

        $response = wp_remote_get($plugin_data['UpdateURI']);

        if (empty($response['body'])) {
            return $update;
        }

        $custom_plugins_data = json_decode($response['body'], true);

        $package = null;
        $token = get_option('ju_user_token');
        if (!empty($token)) {
            $package = $custom_plugins_data['download_url'] . '&token=' . $token . '&siteurl=' . get_option('siteurl');
        }

        return array(
            'version' => $custom_plugins_data['version'],
            'package' => $package
        );
    }
    add_filter('update_plugins_www.joomunited.com', 'wpmfGalleryAddonPluginCheckForUpdates', 10, 3);
}
