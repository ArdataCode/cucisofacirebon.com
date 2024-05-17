<?php
/*
  Plugin Name: WP Media folder
  Plugin URI: http://www.joomunited.com
  Description: WP media Folder is a WordPress plugin that enhance the WordPress media manager by adding a folder manager inside.
  Author: Joomunited
  Version: 5.5.4
  Update URI: https://www.joomunited.com/juupdater_files/wp-media-folder.json
  Author URI: http://www.joomunited.com
  Text Domain: wpmf
  Domain Path: /languages
  Licence : GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
  Copyright : Copyright (C) 2014 JoomUnited (http://www.joomunited.com). All rights reserved.
 */
// Prohibit direct script loading
defined('ABSPATH') || die('No direct script access allowed!');

//Check plugin requirements
if (version_compare(PHP_VERSION, '5.6', '<')) {
    if (!function_exists('wpmfDisablePlugin')) {
        /**
         * Deactivate plugin
         *
         * @return void
         */
        function wpmfDisablePlugin()
        {
            /**
             * Filter check user capability to do an action
             *
             * @param boolean The current user has the given capability
             * @param string  Action name
             *
             * @return boolean
             */
            $wpmf_capability = apply_filters('wpmf_user_can', current_user_can('activate_plugins'), 'activate_plugins');
            if ($wpmf_capability && is_plugin_active(plugin_basename(__FILE__))) {
                deactivate_plugins(__FILE__);
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
                unset($_GET['activate']);
            }
        }
    }

    if (!function_exists('wpmfShowError')) {
        /**
         * Show notice
         *
         * @return void
         */
        function wpmfShowError()
        {
            echo '<div class="error"><p>';
            echo '<strong>WP Media Folder</strong>';
            echo ' need at least PHP 5.6 version, please update php before installing the plugin.</p></div>';
        }
    }

    //Add actions
    add_action('admin_init', 'wpmfDisablePlugin');
    add_action('admin_notices', 'wpmfShowError');

    //Do not load anything more
    return;
}

if (!defined('WP_MEDIA_FOLDER_PLUGIN_DIR')) {
    define('WP_MEDIA_FOLDER_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('WPMF_FILE')) {
    define('WPMF_FILE', __FILE__);
}

if (!defined('WPMF_TAXO')) {
    define('WPMF_TAXO', 'wpmf-category');
}

define('_WPMF_GALLERY_PREFIX', '_wpmf_gallery_');
define('WPMF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPMF_DOMAIN', 'wpmf');
define('WPMF_VERSION', '5.5.4');

include_once(ABSPATH . 'wp-admin/includes/plugin.php');
//Include the jutranslation helpers
include_once('jutranslation' . DIRECTORY_SEPARATOR . 'jutranslation.php');
call_user_func(
    '\Joomunited\WPMediaFolder\Jutranslation\Jutranslation::init',
    __FILE__,
    'wpmf',
    'WP Media Folder',
    'wpmf',
    'languages' . DIRECTORY_SEPARATOR . 'wpmf-en_US.mo'
);

// Reintegrate WP Media Folders
if (is_admin()) {
    if (!class_exists('\Joomunited\Queue\V1_0_0\JuMainQueue')) {
        require_once WP_MEDIA_FOLDER_PLUGIN_DIR . 'queue/JuMainQueue.php';
    }

    /**
     * Translate for queue class.
     * ***** DO NOT REMOVE *****
     * Translate strings in JuMainQueue.php file
     * esc_html__('Some of JoomUnited\'s plugins require to process some task in background (cloud synchronization, file processing, ...).', 'wpmf');
     * esc_html__('To prevent PHP timeout errors during the process, it\'s done asynchronously in the background.', 'wpmf');
     * esc_html__('These settings let you optimize the process depending on your server resources.', 'wpmf'); ?>
     * esc_html__('Show the number of items waiting to be processed in the admin menu bar.', 'wpmf');
     * esc_html__('You can reduce the background task processing by changing this parameter. It could be necessary when the plugin is installed on small servers instances but requires consequent task processing. Default 75%.', 'wpmf');
     * esc_html__('You can reduce the background task ajax calling by changing this parameter. It could be necessary when the plugin is installed on small servers instances or shared hosting. Default 15s.', 'wpmf');
     * esc_html__('Pause queue', 'wpmf');
     * esc_html__('Pause queue', 'wpmf');
     * esc_html__('Start queue', 'wpmf');
     * esc_html__('Enable', 'wpmf');
     *
     * ***** DO NOT REMOVE *****
     * End translate for queue class
     */
    $args = wpmfGetQueueOptions(false);
    $wpmfQueue = call_user_func('\Joomunited\Queue\V1_0_0\JuMainQueue::getInstance', 'wpmf');
    $wpmfQueue->init($args);
    $folder_options = get_option('wpmf_queue_options');
    if (!empty($folder_options['enable_physical_folders'])) {
        require_once WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/physical-folder' . DIRECTORY_SEPARATOR . 'wpmf.php';
        new JUQueueActions();
        require_once WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/physical-folder' . DIRECTORY_SEPARATOR . 'helper.php';
    }

    add_action(
        'wpmf_before_delete_folder',
        function ($folder_term) {
            $wpmfQueue = \Joomunited\Queue\V1_0_0\JuMainQueue::getInstance('wpmf');
            $queue_id = get_term_meta($folder_term->term_id, 'wpmf_sync_queue', true);
            if (!empty($queue_id)) {
                if (is_array($queue_id)) {
                    foreach ($queue_id as $queueID) {
                        $wpmfQueue->deleteQueue($queueID);
                    }
                } else {
                    $wpmfQueue->deleteQueue($queue_id);
                }
            }
        },
        2,
        2
    );
    add_action('delete_attachment', function ($id) {
        $queue_id = get_post_meta($id, 'wpmf_sync_queue', true);
        $wpmfQueue = \Joomunited\Queue\V1_0_0\JuMainQueue::getInstance('wpmf');
        if (!empty($queue_id)) {
            if (is_array($queue_id)) {
                foreach ($queue_id as $queueID) {
                    $wpmfQueue->deleteQueue($queueID);
                }
            } else {
                $wpmfQueue->deleteQueue($queue_id);
            }
        }
    }, 10);
}

if (!class_exists('\Joomunited\WPMF\JUCheckRequirements')) {
    require_once(trailingslashit(dirname(__FILE__)) . 'requirements.php');
}

if (class_exists('\Joomunited\WPMF\JUCheckRequirements')) {
    // Plugins name for translate
    $args = array(
        'plugin_name' => esc_html__('WP Media Folder', 'wpmf'),
        'plugin_path' => wpmfGetPath(),
        'plugin_textdomain' => 'wpmf',
        'requirements' => array(
            'php_version' => '5.6',
            'php_modules' => array(
                'curl' => 'warning'
            ),
            'functions' => array(
                'gd_info' => 'warning'
            ),
            // Minimum addons version
            'addons_version' => array(
                'wpmfAddons' => '3.4.14',
                'wpmfGalleryAddons' => '2.0.5'
            )
        ),
    );
    $wpmfCheck = call_user_func('\Joomunited\WPMF\JUCheckRequirements::init', $args);

    if (!$wpmfCheck['success']) {
        // Do not load anything more
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
        unset($_GET['activate']);
        return;
    }

    if (isset($wpmfCheck) && !empty($wpmfCheck['load'])) {
        foreach ($wpmfCheck['load'] as $addonName) {
            if (function_exists($addonName . 'Init')) {
                call_user_func($addonName . 'Init');
            }
        }
    }
}

/**
 * Get queue options
 *
 * @param boolean $cron Is cron
 *
 * @return array
 */
function wpmfGetQueueOptions($cron = false)
{
    $args = array(
        'use_queue' => true, // required
        'assets_url' => WPMF_PLUGIN_URL . 'queue/assets/queue.js',
        'plugin_prefix' => 'ju',
        'status_templates' => array(
            'wpmf_sync_google_drive' => esc_html__('Syncing %d Google Drive files', 'wpmf'),
            'wpmf_sync_onedrive' => esc_html__('Syncing %d OneDrive files', 'wpmf'),
            'wpmf_sync_onedrive_business' => esc_html__('Syncing %d OneDrive Business files', 'wpmf'),
            'wpmf_sync_dropbox' => esc_html__('Syncing %d Dropbox files', 'wpmf'),
            'wpmf_google_drive_remove' => esc_html__('Comparing %d Google Drive folders', 'wpmf'),
            'wpmf_dropbox_remove' => esc_html__('Comparing %d Dropbox folders', 'wpmf'),
            'wpmf_onedrive_remove' => esc_html__('Comparing %d OneDrive folders', 'wpmf'),
            'wpmf_onedrive_business_remove' => esc_html__('Comparing %d OneDrive Business folders', 'wpmf'),
            'wpmf_s3_import' => esc_html__('Importing %d files from Amazon S3', 'wpmf'),
            'wpmf_digitalocean_import' => esc_html__('Importing %d files from DigitalOcean', 'wpmf'),
            'wpmf_wasabi_import' => esc_html__('Importing %d files from Wasabi', 'wpmf'),
            'wpmf_linode_import' => esc_html__('Importing %d files from Linode', 'wpmf'),
            'wpmf_google_cloud_storage_import' => esc_html__('Importing %d files from Google Cloud', 'wpmf'),
            'wpmf_replace_s3_url_by_page' => esc_html__('%d actions in queue to updating Amazon S3 URL', 'wpmf'),
            'wpmf_replace_digitalocean_url_by_page' => esc_html__('%d actions in queue to updating DigitalOcean URL', 'wpmf'),
            'wpmf_replace_wasabi_url_by_page' => esc_html__('%d actions in queue to updating Wasabi URL', 'wpmf'),
            'wpmf_replace_linode_url_by_page' => esc_html__('%d actions in queue to updating Linode URL', 'wpmf'),
            'wpmf_replace_google_cloud_storage_url_by_page' => esc_html__('%d actions in queue to updating Google Cloud URL', 'wpmf'),
            'wpmf_physical_folders' => esc_html__('Moving %d real files', 'wpmf'),
            'wpmf_replace_physical_url' => esc_html__('Updating URL of %d files', 'wpmf'),
            'wpmf_sync_ftp_to_library' => esc_html__('Syncing %d files from FTP', 'wpmf'),
            'wpmf_sync_library_to_ftp' => esc_html__('Syncing %d files from Media to FTP', 'wpmf'),
            'wpmf_import_ftp_to_library' => esc_html__('Importing %d files from FTP', 'wpmf'),
            'wpmf_s3_remove_local_file' => esc_html__('Removing %d files after offload', 'wpmf'),
            'wpmf_move_local_to_cloud' => esc_html__('Moving %d files from server to cloud', 'wpmf'),
            'wpmf_replace_cloud_url_by_page' => esc_html__('%d actions in queue to updating file URL', 'wpmf'),
            'wpmf_remove_local_file' => esc_html__('Removing %d files after upload to cloud', 'wpmf'),
            'wpmf_import_nextgen_gallery' => esc_html__('Importing %d galleries from NextGen', 'wpmf')
        ), // required
        'queue_options' => array(
            'mode_debug' => 0, // required
            'enable_physical_folders' => 0,
            'auto_detect_tables' => 1,
            'replace_relative_paths' => (get_option('uploads_use_yearmonth_folders')) ? 1 : 0,
            'search_full_database' => 0,
        ) // required
    );

    return $args;
}

/**
 * Get plugin path
 *
 * @return string
 */
function wpmfGetPath()
{
    if (!function_exists('plugin_basename')) {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }

    return plugin_basename(__FILE__);
}

/**
 * Load term
 *
 * @param string $taxonomy Taxonomy name
 *
 * @return array|object|null
 */
function wpmfLoadTerms($taxonomy)
{
    global $wpdb;
    $results = $wpdb->get_results($wpdb->prepare('SELECT DISTINCT t.term_id FROM '.$wpdb->terms.' t INNER JOIN '.$wpdb->term_taxonomy.' tax ON tax.term_id = t.term_id WHERE tax.taxonomy = %s', array($taxonomy)), ARRAY_A);
    return $results;
}

register_uninstall_hook(__FILE__, 'wpmfUnInstall');
/**
 * UnInstall plugin
 *
 * @return void
 */
function wpmfUnInstall()
{
    $delete_all_datas = wpmfGetOption('delete_all_datas');
    if (!empty($delete_all_datas)) {
        // delete folder
        $folders = wpmfLoadTerms('wpmf-category');
        foreach ($folders as $folder) {
            wp_delete_term((int) $folder['term_id'], 'wpmf-category');
        }

        $folders = wpmfLoadTerms('wpmf-gallery-category');
        foreach ($folders as $folder) {
            wp_delete_term((int) $folder['term_id'], 'wpmf-gallery-category');
        }

        // delete cloud media
        global $wpdb;
        $limit = 100;
        $total         = $wpdb->get_var($wpdb->prepare('SELECT COUNT(posts.ID) as total FROM ' . $wpdb->prefix . 'posts as posts
               WHERE   posts.post_type = %s', array('attachment')));

        $j = ceil((int) $total / $limit);
        for ($i = 1; $i <= $j; $i ++) {
            $offset      = ($i - 1) * $limit;
            $args = array(
                'post_type' => 'attachment',
                'posts_per_page' => $limit,
                'offset' => $offset,
                'post_status' => 'any'
            );

            $files = get_posts($args);
            foreach ($files as $file) {
                $wpmf_drive_id = get_post_meta($file->ID, 'wpmf_drive_type', true);
                if (!empty($wpmf_drive_id)) {
                    wp_delete_attachment($file->ID);
                } else {
                    delete_post_meta($file->ID, 'wpmf_size');
                    delete_post_meta($file->ID, 'wpmf_filetype');
                    delete_post_meta($file->ID, 'wpmf_order');
                    delete_post_meta($file->ID, 'wpmf_awsS3_info');
                }
            }
        }

        // delete table
        global $wpdb;
        $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'wpmf_s3_queue');

        // delete option
        $options_list = array(
            'wpmf_addon_version',
            'wpmf_folder_root_id',
            'wpmf_update_count',
            'wpmf_version',
            'wpmf_gallery_image_size_value',
            'wpmf_padding_masonry',
            'wpmf_padding_portfolio',
            'wpmf_usegellery',
            'wpmf_useorder',
            'wpmf_create_folder',
            'wpmf_option_override',
            'wpmf_option_duplicate',
            'wpmf_active_media',
            'wpmf_folder_option2',
            'wpmf_usegellery_lightbox',
            'wpmf_media_rename',
            'wpmf_patern_rename',
            'wpmf_rename_number',
            'wpmf_option_media_remove',
            'wpmf_default_dimension',
            'wpmf_selected_dimension',
            'wpmf_weight_default',
            'wpmf_weight_selected',
            'wpmf_color_singlefile',
            'wpmf_option_singlefile',
            'wpmf_option_sync_media',
            'wpmf_option_sync_media_external',
            'wpmf_list_sync_media',
            'wpmf_time_sync',
            'wpmf_lastRun_sync',
            'wpmf_slider_animation',
            'wpmf_option_mediafolder',
            'wpmf_option_countfiles',
            'wpmf_option_lightboximage',
            'wpmf_option_hoverimg',
            'wpmf_options_format_title',
            'wpmf_image_watermark_apply',
            'wpmf_option_image_watermark',
            'wpmf_watermark_position',
            'wpmf_watermark_image',
            'wpmf_watermark_image_id',
            'wpmf_gallery_settings',
            '_wpmf_import_order_notice_flag',
            '_wpmfAddon_cloud_config',
            '_wpmfAddon_dropbox_config',
            'wpmf_onedrive_business',
            '_wpmfAddon_aws3_config',
            'wpmf_gallery_img_per_page',
            'wpmfgrl_relationships_media',
            'wpmfgrl_relationships',
            'wpmf_galleries',
            'wpmf_import_nextgen_gallery',
            'wpmf_onedrive_business_files',
            'wpmf_odv_business_files',
            'wpmf_odv_allfiles',
            'wpmf_google_folders',
            'wpmf_google_allfiles',
            'wpmf_dropbox_allfiles',
            'wpmf_dropbox_folders',
            'wpmf_odv_folders',
            'wpmf_odv_business_folders',
            'wpmf_odv_business_allfiles',
            '_wpmfAddon_onedrive_business_config',
            'wpmf_onedrive_notice',
            '_wpmfAddon_onedrive_config',
            'wpmf_google_folder_id',
            'wpmf_dropbox_folder_id',
            'wpmf_odv_business_folder_id',
            'wpmf_odv_folder_id',
            'wpmf_cloud_connection_notice',
            'wp-media-folder-addon-tables',
            '_wpmf_activation_redirect',
            'wpmf_use_taxonomy',
            'wpmf_cloud_time_last_sync',
            'wpmf_dropbox_attachments',
            'wpmf_dropbox_folders',
            'wpmf_dropbox_allfiles',
            'wpmf_google_attachments',
            'wpmf_google_folders',
            'wpmf_google_allfiles',
            'wpmf_odv_attachments',
            'wpmf_odv_folders',
            'wpmf_odv_allfiles',
            'wpmf_odv_business_attachments',
            'wpmf_odv_business_folders',
            'wpmf_odv_business_allfiles',
            'wpmf_cloud_name_syncing',
            'wpmf_ftp_sync_time',
            'wpmf_ftp_sync_token',
            'wpmf_settings'
        );

        foreach ($options_list as $option) {
            delete_option($option);
        }
    }
}

register_activation_hook(__FILE__, 'wpmfInstall');
/**
 * Install plugin
 *
 * @return void
 */
function wpmfInstall()
{
    set_time_limit(0);
    global $wpdb;
    $limit         = 100;
    $values        = array();
    $place_holders = array();
    $total         = $wpdb->get_var($wpdb->prepare('SELECT COUNT(posts.ID) as total FROM ' . $wpdb->prefix . 'posts as posts
               WHERE   posts.post_type = %s', array('attachment')));

    if ($total <= 5000) {
        $j = ceil((int) $total / $limit);
        for ($i = 1; $i <= $j; $i ++) {
            $offset      = ($i - 1) * $limit;
            $attachments = $wpdb->get_results($wpdb->prepare('SELECT ID FROM ' . $wpdb->prefix . 'posts as posts
               WHERE   posts.post_type     = %s LIMIT %d OFFSET %d', array('attachment', $limit, $offset)));
            foreach ($attachments as $attachment) {
                $wpmf_size_filetype = wpmfGetSizeFiletype($attachment->ID);
                $size               = $wpmf_size_filetype['size'];
                $ext                = $wpmf_size_filetype['ext'];
                if (!get_post_meta($attachment->ID, 'wpmf_size')) {
                    array_push($values, $attachment->ID, 'wpmf_size', $size);
                    $place_holders[] = "('%d', '%s', '%s')";
                }

                if (!get_post_meta($attachment->ID, 'wpmf_filetype')) {
                    array_push($values, $attachment->ID, 'wpmf_filetype', $ext);
                    $place_holders[] = "('%d', '%s', '%s')";
                }

                if (!get_post_meta($attachment->ID, 'wpmf_order')) {
                    array_push($values, $attachment->ID, 'wpmf_order', 0);
                    $place_holders[] = "('%d', '%s', '%d')";
                }
            }

            if (count($place_holders) > 0) {
                $query = 'INSERT INTO ' . $wpdb->prefix . 'postmeta (post_id, meta_key, meta_value) VALUES ';
                $query .= implode(', ', $place_holders);
                $wpdb->query($wpdb->prepare($query, $values)); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Insert multiple row, can't write sql in prepare
                $place_holders = array();
                $values        = array();
            }
        }
    }
}

/**
 * Get size and file type for attachment
 *
 * @param integer $pid ID of attachment
 *
 * @return array
 */
function wpmfGetSizeFiletype($pid)
{
    $wpmf_size_filetype = array();
    $meta               = get_post_meta($pid, '_wp_attached_file');
    $upload_dir         = wp_upload_dir();
    if (empty($meta)) {
        return array('size' => 0, 'ext' => '');
    }
    $url_attachment     = $upload_dir['basedir'] . '/' . $meta[0];
    if (file_exists($url_attachment)) {
        $size     = filesize($url_attachment);
        $filetype = wp_check_filetype($url_attachment);
        $ext      = $filetype['ext'];
    } else {
        $size = 0;
        $ext  = '';
    }
    $wpmf_size_filetype['size'] = $size;
    $wpmf_size_filetype['ext']  = $ext;

    return $wpmf_size_filetype;
}

/**
 * Set a option
 *
 * @param string            $option_name Option name
 * @param string|array|void $value       Value of option
 *
 * @return void
 */
function wpmfSetOption($option_name, $value)
{
    $settings = get_option('wpmf_settings');
    if (empty($settings)) {
        $settings               = array();
        $settings[$option_name] = $value;
    } else {
        $settings[$option_name] = $value;
    }

    update_option('wpmf_settings', $settings);
}

/**
 * Get a option
 *
 * @param string $option_name Option name
 *
 * @return mixed
 */
function wpmfGetOption($option_name)
{
    $formats_title       = get_option('wpmf_options_format_title');
    if (empty($formats_title)) {
        $formats_title = array();
    }

    $media_download       = json_decode(get_option('wpmf_color_singlefile'), true);
    if (empty($media_download)) {
        $media_download = array();
    }

    $params_theme     = array(
        'default_theme'     => array(
            'columns'    => 3,
            'size'       => 'medium',
            'targetsize' => 'large',
            'link'       => 'file',
            'orderby'    => 'post__in',
            'order'      => 'ASC'
        ),
        'portfolio_theme'   => array(
            'columns'    => 3,
            'size'       => 'medium',
            'targetsize' => 'large',
            'link'       => 'file',
            'orderby'    => 'post__in',
            'order'      => 'ASC'
        ),
        'masonry_theme'     => array(
            'columns'    => 3,
            'size'       => 'medium',
            'targetsize' => 'large',
            'link'       => 'file',
            'orderby'    => 'post__in',
            'order'      => 'ASC'
        ),
        'slider_theme'      => array(
            'columns'        => 3,
            'size'           => 'medium',
            'targetsize'     => 'large',
            'link'           => 'file',
            'orderby'        => 'post__in',
            'order'          => 'ASC',
            'animation'      => 'slide',
            'duration'       => 4000,
            'auto_animation' => 1
        ),
        'flowslide_theme'   => array(
            'columns'      => 3,
            'size'         => 'medium',
            'targetsize'   => 'large',
            'link'         => 'file',
            'orderby'      => 'post__in',
            'order'        => 'ASC',
            'show_buttons' => 1
        ),
        'square_grid_theme' => array(
            'columns'    => 3,
            'size'       => 'medium',
            'targetsize' => 'large',
            'link'       => 'file',
            'orderby'    => 'post__in',
            'order'      => 'ASC'
        ),
        'material_theme'    => array(
            'columns'    => 3,
            'size'       => 'medium',
            'targetsize' => 'large',
            'link'       => 'file',
            'orderby'    => 'post__in',
            'order'      => 'ASC'
        ),
    );
    $gallery_settings = array(
        'theme' => $params_theme
    );

    $gallery_shortcode_settings = array(
        'choose_gallery_id'       => 0,
        'choose_gallery_theme'    => 'default',
        'display_tree'            => 0,
        'display_tag'             => 0,
        'theme'                   => $params_theme,
        'gallery_shortcode_input' => ''
    );

    $default_settings = array(
        'root_media_count' => 0,
        'delete_all_datas' => 0,
        'all_media_in_user_root' => 0,
        'load_gif' => 1,
        'hide_tree' => 1,
        'enable_folders' => 1,
        'caption_lightbox_gallery' => 0,
        'hide_remote_video' => 1,
        'enable_download_media' => 0,
        'folder_color' => array(),
        'watermark_image_scaling' => 100,
        'social_sharing' => 0,
        'search_file_include_childrent' => 0,
        'social_sharing_link' => array(
            'facebook' => '',
            'twitter' => '',
            'google' => '',
            'instagram' => '',
            'pinterest' => ''
        ),
        'watermark_margin' => array(
            'top' => 0,
            'right' => 0,
            'bottom' => 0,
            'left' => 0
        ),
        'format_mediatitle' => 1,
        'gallery_settings' => $gallery_settings,
        'gallery_shortcode' => $gallery_shortcode_settings,
        'gallery_shortcode_cf' => array(
            'wpmf_folder_id' => 0,
            'display' => 'default',
            'columns' => 3,
            'size' => 'medium',
            'targetsize' => 'large',
            'link' => 'file',
            'wpmf_orderby' => 'post__in',
            'wpmf_order' => 'ASC',
            'autoplay' => 1,
            'include_children' => 0,
            'gutterwidth' => 10,
            'img_border_radius' => 0,
            'border_style' => 'none',
            'border_width' => 0,
            'border_color' => 'transparent',
            'img_shadow' => '0 0 0 0 transparent',
            'value' => ''
        ),
        'watermark_exclude_folders' => array(),
        'sync_method' => 'ajax',
        'sync_periodicity' => '900',
        'show_folder_id' => 0,
        'watermark_opacity' => 100,
        'watermark_margin_unit' => 'px',
        'allow_sync_extensions' => 'jpg,jpeg,jpe,gif,png,svg,bmp,tiff,tif,ico,7z,bz2,gz,rar,tgz,zip,csv,doc,docx,ods,odt,pdf,pps,ppt,pptx,ppsx,rtf,txt,xls,xlsx,psd,tif,tiff,mid,mp3,mp4,ogg,wma,3gp,avi,flv,m4v,mkv,mov,mpeg,mpg,swf,vob,wmv,webm',
        'allow_syncs3_extensions' => 'jpg,jpeg,jpe,gif,png,svg,bmp,tiff,tif,ico,7z,bz2,gz,rar,tgz,zip,csv,doc,docx,ods,odt,pdf,pps,ppt,pptx,ppsx,rtf,txt,xls,xlsx,psd,tif,tiff,mid,mp3,mp4,ogg,wma,3gp,avi,flv,m4v,mkv,mov,mpeg,mpg,swf,vob,wmv,webm',
        'import_iptc_meta' => 0,
        'iptc_fields' => array(
            'title' => 1,
            'alt' => 1,
            'description' => 0,
            'caption' => 0,
            '2#025' => 0,
            'credit' => 0,
            '2#005' => 0,
            '2#010' => 0,
            '2#015' => 0,
            '2#020' => 0,
            '2#040' => 0,
            '2#055' => 0,
            '2#080' => 0,
            '2#085' => 0,
            '2#090' => 0,
            '2#095' => 0,
            '2#100' => 0,
            '2#101' => 0,
            '2#103' => 0,
            '2#105' => 1,
            '2#110' => 0,
            '2#115' => 0,
            '2#116' => 0
        ),
        'export_folder_type' => 'only_folder',
        'tasks_speed' => 100,
        'status_menu_bar' => 0,
        'wpmf_export_folders' => array(),
        'wp-media-folder-tables' => array(
            'wp_posts' => array(
                'post_content' => 1,
                'post_excerpt' => 1
            )
        ),
        'wpmf_options_format_title' => array_merge(array(
            'hyphen'          => 1,
            'underscore'      => 1,
            'period'          => 0,
            'tilde'           => 0,
            'plus'            => 0,
            'capita'          => 'cap_all',
            'alt'             => 0,
            'caption'         => 0,
            'description'     => 0,
            'hash'            => 0,
            'ampersand'       => 0,
            'copyright'       => 0,
            'number'          => 0,
            'square_brackets' => 0,
            'round_brackets'  => 0,
            'curly_brackets'  => 0
        ), $formats_title),
        'media_download' => array_merge(array(
            'bgdownloadlink'   => '#202231',
            'hvdownloadlink'   => '#1c1e2a',
            'fontdownloadlink' => '#f4f6ff',
            'hoverfontcolor'   => '#ffffff',
            'margin_top' => 30,
            'margin_right' => 30,
            'margin_bottom' => 30,
            'margin_left' => 30,
            'padding_top' => 20,
            'padding_right' => 30,
            'padding_bottom' => 20,
            'padding_left' => 70,
            'border_radius' => 15,
            'border_width' => 0,
            'border_type' => 'solid',
            'border_color' => '#f4f6ff',
            'icon_image' => 'download_style_0',
            'icon_color' => '#f4f6ff'
        ), $media_download)
    );
    $settings         = get_option('wpmf_settings');
    if (isset($settings) && isset($settings[$option_name])) {
        if (is_array($settings[$option_name]) && !empty($default_settings[$option_name])) {
            return array_merge($default_settings[$option_name], $settings[$option_name]);
        } else {
            return $settings[$option_name];
        }
    }

    return $default_settings[$option_name];
}

require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-helper.php');
$frontend = get_option('wpmf_option_mediafolder');
if (!empty($frontend) || is_admin()) {
    global $wpmfwatermark;
    require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-main.php');
    $GLOBALS['wp_media_folder'] = new WpMediaFolder;
    $useorder                   = get_option('wpmf_useorder');
    // todo : should this really be always loaded on each wp request?
    // todo : should we not loaded
    if (isset($useorder) && (int) $useorder === 1) {
        require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-orderby-media.php');
        new WpmfOrderbyMedia;
        require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-filter-size.php');
        new WpmfFilterSize;
    }

    $option_duplicate = get_option('wpmf_option_duplicate');
    if (isset($option_duplicate) && (int) $option_duplicate === 1) {
        require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-duplicate-file.php');
        new WpmfDuplicateFile;
    }

    $wpmf_media_rename = get_option('wpmf_media_rename');
    if (isset($wpmf_media_rename) && (int) $wpmf_media_rename === 1) {
        require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-media-rename.php');
        new WpmfMediaRename;
    }

    require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-image-watermark.php');
    $wpmfwatermark = new WpmfWatermark();

    $option_override = get_option('wpmf_option_override');
    if (isset($option_override) && (int) $option_override === 1) {
        require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-replace-file.php');
        new WpmfReplaceFile;
    }
}

/**
 * Load script for elementor
 *
 * @return void
 */
function wpmfLoadElementorWidgetStyle()
{
    wp_enqueue_style(
        'wpmf-widgets',
        WPMF_PLUGIN_URL . 'assets/css/elementor-widgets/widgets.css',
        array(),
        WPMF_VERSION,
        'all'
    );
    $ui_theme = \Elementor\Core\Settings\Manager::get_settings_managers('editorPreferences')->get_model()->get_settings('ui_theme');
    wp_enqueue_style(
        'wpmf-widgets-light',
        WPMF_PLUGIN_URL . 'assets/css/elementor-widgets/widgets-light.css',
        array('elementor-editor'),
        WPMF_VERSION,
        'all'
    );

    if ('light' !== $ui_theme) {
        $ui_theme_media_queries = 'all';
        if ('auto' === $ui_theme) {
            $ui_theme_media_queries = '(prefers-color-scheme: dark)';
        }

        wp_enqueue_style(
            'wpmf-widgets-dark',
            WPMF_PLUGIN_URL . 'assets/css/elementor-widgets/widgets-dark.css',
            array('elementor-editor-dark-mode'),
            WPMF_VERSION,
            $ui_theme_media_queries
        );
    }
}
add_action('elementor/editor/after_enqueue_styles', 'wpmfLoadElementorWidgetStyle');

/**
 * Load script for elementor
 *
 * @return void
 */
function wpmfLoadElementorWidgetScript()
{
    wp_enqueue_media();
    wp_enqueue_script(
        'wpmf-widgets',
        WPMF_PLUGIN_URL . 'class/elementor-widgets/widgets.js',
        array('jquery'),
        WPMF_VERSION
    );
}
add_action('elementor/editor/after_enqueue_styles', 'wpmfLoadElementorWidgetScript');

/**
 * Add elementor widget categories
 *
 * @param object $elements_manager Elements manager
 *
 * @return void
 */
function wpmfAddElementorWidgetCategories($elements_manager)
{
    $elements_manager->add_category(
        'wpmf',
        array(
            'title' => __('WP Media Folder', 'wpmf'),
            'icon' => 'fa fa-plug'
        )
    );
}

add_action('elementor/elements/categories_registered', 'wpmfAddElementorWidgetCategories');

// Init Divi module
if (!function_exists('wpmfInitializeDiviExtension')) :
    /**
     * Creates the extension's main class instance.
     *
     * @return void
     */
    function wpmfInitializeDiviExtension()
    {
        require_once WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/divi-widgets/includes/WpmfDivi.php';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
        if (isset($_REQUEST['et_fb']) && (int)$_REQUEST['et_fb'] === 1) {
            require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-pdf-embed.php');
            $pdf = new WpmfPdfEmbed;
            $pdf->registerScript();
            $pdf->enqueue();

            $enable_gallery = get_option('wpmf_usegellery');
            if (isset($enable_gallery) && (int) $enable_gallery === 1) {
                require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-display-gallery.php');
                $gallery = new WpmfDisplayGallery;
                $gallery->galleryScripts();
                $gallery->enqueueScript('divi');
            }

            do_action('wpmf_init_gallery_addon_divi');
        }
        wp_enqueue_style(
            'wpmf_divi_css',
            WPMF_PLUGIN_URL . 'assets/css/divi-widgets.css',
            array(),
            WPMF_VERSION,
            'all'
        );
    }

    add_action('divi_extensions_init', 'wpmfInitializeDiviExtension');
endif;

add_action('vc_frontend_editor_enqueue_js_css', 'wpmfVcEnqueueJsCss');

/**
 * This action registers all styles(fonts) to be enqueue later
 *
 * @return void
 */
function wpmfVcEnqueueJsCss()
{
    // load jquery library
    require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-pdf-embed.php');
    $pdf = new WpmfPdfEmbed;
    $pdf->registerScript();
    $pdf->enqueue();
}

/**
 * Get main class
 *
 * @return mixed|WpMediaFolder
 */
function wpmfGetMainClass()
{
    if (!empty($GLOBALS['wp_media_folder'])) {
        $main_class = $GLOBALS['wp_media_folder'];
    } else {
        require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-helper.php');
        require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-main.php');
        $main_class = new WpMediaFolder;
    }

    return $main_class;
}

/**
 * Register media frame field
 *
 * @param array  $settings Setting details
 * @param string $value    Default value
 *
 * @return string
 */
function wpmfMediaSettingsField($settings, $value)
{
    return '<div class="' . esc_attr($settings['block_name'] . '_block') . '">'
        . '<input name="' . esc_attr($settings['param_name']) . '" class="wpb_vc_param_value wpb-textinput ' .
        esc_attr($settings['param_name']) . ' ' .
        esc_attr($settings['block_name']) . '_field" type="text" value="' . esc_attr($value) . '" /><button class="' . esc_attr($settings['class']) . '" type="button">' . $settings['button_label'] . '</button>' .
        '</div>';
}

/**
 * Register number field
 *
 * @param array  $settings Setting details
 * @param string $value    Default value
 *
 * @return string
 */
function wpmfNumberSettingsField($settings, $value)
{
    return '<input name="' . esc_attr($settings['param_name']) . '" min="' . esc_attr($settings['min']) . '" max="' . esc_attr($settings['max']) . '" step="' . esc_attr($settings['step']) . '" class="wpb_vc_param_value wpb-textinput ' .
        esc_attr($settings['param_name']) . '_field" type="number" value="' . esc_attr($value) . '" />';
}

/**
 * Add bakery widgets
 *
 * @return void
 */
function wpmfVcBeforeInit()
{
    vc_add_shortcode_param('wpmf_media', 'wpmfMediaSettingsField');
    vc_add_shortcode_param('wpmf_number', 'wpmfNumberSettingsField');
    wp_enqueue_style(
        'wpmf-bakery-style',
        WPMF_PLUGIN_URL . 'assets/css/vc_style.css',
        array(),
        WPMF_VERSION
    );

    require_once WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/bakery-widgets/PdfEmbed.php';
    $enable_singlefile = get_option('wpmf_option_singlefile');
    if (isset($enable_singlefile) && (int)$enable_singlefile === 1) {
        require_once WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/bakery-widgets/FileDesign.php';
    }

    $enable_gallery = get_option('wpmf_usegellery');
    if (isset($enable_gallery) && (int)$enable_gallery === 1) {
        require_once WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/bakery-widgets/Gallery.php';
    }

    do_action('wpmf_vc_init_gallery_addon');
}

add_action('vc_before_init', 'wpmfVcBeforeInit');

if (!function_exists('wpmfTnitAvada')) {
    /**
     * Create custom field for avada
     *
     * @param array $field_types File types
     *
     * @return mixed
     */
    function wpmfAvadaFields($field_types)
    {
        $field_types['wpmf_gallery_select'] = array(
            'wpmf_gallery_select',
            WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/avada-widgets/fields/select_images.php'
        );

        $field_types['wpmf_single_file'] = array(
            'wpmf_single_file',
            WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/avada-widgets/fields/single_file.php'
        );

        $field_types['wpmf_pdf_embed'] = array(
            'wpmf_pdf_embed',
            WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/avada-widgets/fields/pdf_embed.php'
        );

        return $field_types;
    }

    /**
     * Init Avada module
     *
     * @return void
     */
    function wpmfTnitAvada()
    {
        if (!defined('AVADA_VERSION') || !defined('FUSION_BUILDER_VERSION')) {
            return;
        }

        add_filter('fusion_builder_fields', 'wpmfAvadaFields', 10, 1);
        require_once WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/avada-widgets/PdfEmbed.php';
        $enable_singlefile = get_option('wpmf_option_singlefile');
        if (isset($enable_singlefile) && (int)$enable_singlefile === 1) {
            require_once WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/avada-widgets/FileDesign.php';
        }

        $enable_gallery = get_option('wpmf_usegellery');
        if (isset($enable_gallery) && (int)$enable_gallery === 1) {
            require_once WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/avada-widgets/Gallery.php';
        }

        if (fusion_is_builder_frame()) {
            add_action('fusion_builder_enqueue_live_scripts', 'wpmfAvadaEnqueueSeparateLiveScripts');
        }
    }

    add_action('init', 'wpmfTnitAvada');
}

/**
 * Avada enqueue live scripts
 *
 * @return void
 */
function wpmfAvadaEnqueueSeparateLiveScripts()
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
    // load jquery library
    require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-pdf-embed.php');
    $pdf = new WpmfPdfEmbed;
    $pdf->registerScript();
    $pdf->enqueue();
    wp_enqueue_script('wpmf_fusion_view_element', WPMF_PLUGIN_URL . '/class/avada-widgets/js/avada.js', array(), WPMF_VERSION, true);
}

$active_media = get_option('wpmf_active_media');
if (isset($active_media) && (int) $active_media === 1) {
    require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-folder-access.php');
    new WpmfFolderAccess;
}

$enable_gallery = get_option('wpmf_usegellery');
if (isset($enable_gallery) && (int) $enable_gallery === 1) {
    require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-display-gallery.php');
    new WpmfDisplayGallery;
}

if (is_admin()) {
    require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-wp-folder-option.php');
    new WpmfMediaFolderOption;
}

$wpmf_option_singlefile = get_option('wpmf_option_singlefile');
if (isset($wpmf_option_singlefile) && (int) $wpmf_option_singlefile === 1) {
    require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-single-file.php');
    new WpmfSingleFile();
}

$wpmf_option_lightboximage = get_option('wpmf_option_lightboximage');
if (isset($wpmf_option_lightboximage) && (int) $wpmf_option_lightboximage === 1) {
    require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-single-lightbox.php');
    new WpmfSingleLightbox;
}

require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-pdf-embed.php');
new WpmfPdfEmbed();

//  load gif file on page load or not
$load_gif = wpmfGetOption('load_gif');
if (isset($load_gif) && (int) $load_gif === 0) {
    require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/class-load-gif.php');
    new WpmfLoadGif();
}

/**
 * Get cloud folder ID
 *
 * @param string $folder_id Folder ID
 *
 * @return boolean|mixed
 */
function wpmfGetCloudFolderID($folder_id)
{
    $cloud_id = get_term_meta($folder_id, 'wpmf_drive_root_id', true);
    if (empty($cloud_id)) {
        $cloud_id = get_term_meta($folder_id, 'wpmf_drive_id', true);
    }

    $cloud_type = get_term_meta($folder_id, 'wpmf_drive_type', true);
    if (empty($cloud_id)) {
        if (isset($cloud_type) && $cloud_type !== 'dropbox') {
            return false;
        } else {
            if ($cloud_id === '') {
                return 'root';
            }
            return $cloud_id;
        }
    } else {
        return $cloud_id;
    }
}

/**
 * Get cloud folder type
 *
 * @param string $folder_id Folder ID
 *
 * @return boolean|mixed
 */
function wpmfGetCloudFolderType($folder_id)
{
    $type = get_term_meta($folder_id, 'wpmf_drive_root_type', true);
    if (empty($type)) {
        $type = get_term_meta($folder_id, 'wpmf_drive_type', true);
    }

    if (empty($type)) {
        return 'local';
    } else {
        return $type;
    }
}

/**
 * Get cloud file ID
 *
 * @param string $file_id File ID
 *
 * @return boolean|mixed
 */
function wpmfGetCloudFileID($file_id)
{
    $cloud_id = get_post_meta($file_id, 'wpmf_drive_id', true);
    if (empty($cloud_id)) {
        return false;
    } else {
        return $cloud_id;
    }
}

/**
 * Get cloud file type
 *
 * @param string $file_id File ID
 *
 * @return boolean|mixed
 */
function wpmfGetCloudFileType($file_id)
{
    $type = get_post_meta($file_id, 'wpmf_drive_type', true);
    if (empty($type)) {
        return 'local';
    } else {
        return $type;
    }
}

/**
 * Get IPTC header default
 *
 * @return array
 */
function getIptcHeader()
{
    $iptcHeaderArray = array
    (
        '2#005'=>'DocumentTitle',
        '2#025'=>'Keywords',
        '2#010'=>'Urgency',
        '2#015'=>'Category',
        '2#020'=>'Subcategories',
        '2#040'=>'SpecialInstructions',
        '2#055'=>'CreationDate',
        '2#080'=>'AuthorByline',
        '2#085'=>'AuthorTitle',
        '2#090'=>'City',
        '2#095'=>'State',
        '2#100'=>'Location',
        '2#101'=>'Country',
        '2#103'=>'OTR',
        '2#105'=>'Headline',
        '2#110'=>'Credit',
        '2#115'=>'PhotoSource',
        '2#116'=>'Copyright'
    );

    return $iptcHeaderArray;
}

add_action('admin_enqueue_scripts', 'wpmfAddStyle');
add_action('wp_enqueue_media', 'wpmfAddStyle');
/**
 * Add style and script
 *
 * @return void
 */
function wpmfAddStyle()
{
    wp_enqueue_style(
        'wpmf-material-design-iconic-font.min',
        plugins_url('/assets/css/material-design-iconic-font.min.css', __FILE__),
        array(),
        WPMF_VERSION
    );

    wp_enqueue_script(
        'wpmf-link-dialog',
        plugins_url('/assets/js/open_link_dialog.js', __FILE__),
        array('jquery'),
        WPMF_VERSION
    );
}

add_action('init', 'wpmfRegisterTaxonomyForImages', 0);
/**
 * Register 'wpmf-category' taxonomy
 *
 * @return void
 */
function wpmfRegisterTaxonomyForImages()
{
    /**
     * Filter to change public param wpmf-category taxonomy
     *
     * @param boolean Toxonomy public status
     *
     * @return boolean
     */
    $public = apply_filters('wpmf_taxonomy_public', false);
    register_taxonomy(
        WPMF_TAXO,
        'attachment',
        array(
            'hierarchical'          => true,
            'show_in_nav_menus'     => false,
            'show_ui'               => false,
            'public'                => $public,
            'update_count_callback' => '_update_generic_term_count',
            'labels'                => array(
                'name'              => __('WPMF Categories', 'wpmf'),
                'singular_name'     => __('WPMF Category', 'wpmf'),
                'menu_name'         => __('WPMF Categories', 'wpmf'),
                'all_items'         => __('All WPMF Categories', 'wpmf'),
                'edit_item'         => __('Edit WPMF Category', 'wpmf'),
                'view_item'         => __('View WPMF Category', 'wpmf'),
                'update_item'       => __('Update WPMF Category', 'wpmf'),
                'add_new_item'      => __('Add New WPMF Category', 'wpmf'),
                'new_item_name'     => __('New WPMF Category Name', 'wpmf'),
                'parent_item'       => __('Parent WPMF Category', 'wpmf'),
                'parent_item_colon' => __('Parent WPMF Category:', 'wpmf'),
                'search_items'      => __('Search WPMF Categories', 'wpmf'),
            )
        )
    );

    $root_id = get_option('wpmf_folder_root_id', false);
    if (!$root_id) {
        $tag = get_term_by('name', 'WP Media Folder Root', WPMF_TAXO);
        if (empty($tag)) {
            $inserted = wp_insert_term('WP Media Folder Root', WPMF_TAXO, array('parent' => 0));
            if (!get_option('wpmf_folder_root_id', false)) {
                add_option('wpmf_folder_root_id', $inserted['term_id'], '', 'yes');
            }
        } else {
            if (!get_option('wpmf_folder_root_id', false)) {
                add_option('wpmf_folder_root_id', $tag->term_id, '', 'yes');
            }
        }
    } else {
        $root = get_term_by('id', (int) $root_id, WPMF_TAXO);
        if (!$root) {
            $inserted = wp_insert_term('WP Media Folder Root', WPMF_TAXO, array('parent' => 0));
            if (!is_wp_error($inserted)) {
                update_option('wpmf_folder_root_id', (int) $inserted['term_id']);
            } else {
                if (is_numeric($inserted->error_data['term_exists'])) {
                    update_option('wpmf_folder_root_id', $inserted->error_data['term_exists']);
                }
            }
        }
    }
}

add_filter('wp_get_attachment_url', 'wpmfGetAttachmentImportUrl', 99, 2);
add_filter('wp_prepare_attachment_for_js', 'wpmfGetAttachmentImportData', 10, 3);
/**
 * Filters the attachment URL.
 *
 * @param string  $url           URL for the given attachment.
 * @param integer $attachment_id Attachment post ID.
 *
 * @return mixed
 */
function wpmfGetAttachmentImportUrl($url, $attachment_id)
{
    $site_path = apply_filters('wpmf_site_path', ABSPATH);
    $path = get_post_meta($attachment_id, 'wpmf_import_path', true);
    if (!empty($path) && file_exists($path)) {
        $url = str_replace($site_path, site_url('/'), $path);
    }

    return $url;
}

/**
 * Filters the attachment data prepared for JavaScript.
 *
 * @param array       $response   Array of prepared attachment data.
 * @param WP_Post     $attachment Attachment object.
 * @param array|false $meta       Array of attachment meta data, or false if there is none.
 *
 * @return mixed
 */
function wpmfGetAttachmentImportData($response, $attachment, $meta)
{
    $site_path = apply_filters('wpmf_site_path', ABSPATH);
    $path = get_post_meta($attachment->ID, 'wpmf_import_path', true);
    if (!empty($path) && file_exists($path)) {
        $url = str_replace($site_path, site_url('/'), $path);
        $response['url'] = $url;
    }

    return $response;
}

if (is_admin()) {
    //config section
    if (!defined('JU_BASE')) {
        define('JU_BASE', 'https://www.joomunited.com/');
    }

    $remote_updateinfo = JU_BASE . 'juupdater_files/wp-media-folder.json';
    //end config
    require 'juupdater/juupdater.php';
    $UpdateChecker = Jufactory::buildUpdateChecker(
        $remote_updateinfo,
        __FILE__
    );
}

if (!function_exists('wpmfPluginCheckForUpdates')) {
    /**
     * Plugin check for updates
     *
     * @param object $update      Update
     * @param array  $plugin_data Plugin data
     * @param string $plugin_file Plugin file
     *
     * @return array|boolean|object
     */
    function wpmfPluginCheckForUpdates($update, $plugin_data, $plugin_file)
    {
        if ($plugin_file !== 'wp-media-folder/wp-media-folder.php') {
            return $update;
        }

        if (empty($plugin_data['UpdateURI']) || !empty($update)) {
            return $update;
        }

        $response = wp_remote_get($plugin_data['UpdateURI']);

        if (is_wp_error($response) || empty($response['body'])) {
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
    add_filter('update_plugins_www.joomunited.com', 'wpmfPluginCheckForUpdates', 10, 3);
}

add_filter('mailpoet_conflict_resolver_whitelist_script', 'wpmf_mailpoet_conflict_resolver_whitelist_script', 10, 1);
/**
 * Mailpoet conflict resolver whitelist script
 *
 * @param array $scripts Scripts list
 *
 * @return array
 */
function wpmf_mailpoet_conflict_resolver_whitelist_script($scripts)
{
    $scripts[] = 'wp-media-folder';
    return $scripts;
}

add_filter('mailpoet_conflict_resolver_whitelist_style', 'wpmf_mailpoet_conflict_resolver_whitelist_style', 10, 1);
/**
 * Mailpoet conflict resolver whitelist stype
 *
 * @param array $tyles Style list
 *
 * @return array
 */
function wpmf_mailpoet_conflict_resolver_whitelist_style($tyles)
{
    $tyles[] = 'wp-media-folder';
    return $tyles;
}


/**
 * Render Video Icon
 *
 * @param integer $attachment_id Attachment ID
 *
 * @return string
 */
function wpmfRenderVideoIcon($attachment_id)
{
    $remote_url = get_post_meta($attachment_id, 'wpmf_remote_video_link', true);
    if (!empty($remote_url)) {
        return '<i class="material-icons wpmf_remote_video_fe">play_circle_filled</i>';
    }

    return '';
}
