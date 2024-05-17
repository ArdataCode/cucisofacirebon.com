<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
require_once(WPMFAD_PLUGIN_DIR . '/class/wpmfHelper.php');
require_once(WPMFAD_PLUGIN_DIR . '/class/wpmfOneDrive.php');

/**
 * Class WpmfAddonOneDriveAdmin
 * This class that holds most of the admin functionality for OneDrive
 */
class WpmfAddonOneDriveAdmin extends WpmfAddonOneDrive
{

    /**
     * WpmfAddonOneDriveAdmin constructor.
     */
    public function __construct()
    {
        if (version_compare(WPMFAD_VERSION, '2.4.0', '<')) {
            $onedriveconfig = get_option('_wpmfAddon_onedrive_config');
            if (!empty($onedriveconfig['connected'])) {
                if (!get_option('wpmf_onedrive_notice', false)) {
                    add_action('admin_notices', array($this, 'onedriveNotice'), 3);
                }
            }
        }

        $this->actionHooks();
        $this->filterHooks();
        $this->handleAjax();
    }

    /**
     * Ajax action
     *
     * @return void
     */
    public function handleAjax()
    {
        add_action('wp_ajax_wpmf_onedrive_logout', array($this, 'onedriveLogout'));
        add_action('wp_ajax_wpmf_onedrive_download', array($this, 'downloadFile'));
        add_action('wp_ajax_nopriv_wpmf_onedrive_download', array($this, 'downloadFile'));
        add_action('wp_ajax_wpmf_onedrive_add_queue', array($this, 'ajaxAddToQueue'));
        add_action('wp_ajax_wpmf_onedrive_sync_full', array($this, 'autoSyncWithCrontabMethod'));
        add_action('wp_ajax_nopriv_wpmf_onedrive_sync_full', array($this, 'autoSyncWithCrontabMethod'));
    }

    /**
     * Action hooks
     *
     * @return void
     */
    public function actionHooks()
    {
        add_action('admin_init', array($this, 'addRootToQueue'));
        add_action('wp_enqueue_scripts', array($this, 'frontendStyleScript'));
        add_action('enqueue_block_editor_assets', array($this, 'addEditorAssets'), 9999);
        add_action('add_attachment', array($this, 'addAttachment'), 10, 1);
        add_action('wpmf_create_folder', array($this, 'createFolderLibrary'), 10, 4);
        add_action('wpmf_before_delete_folder', array($this, 'deleteFolderLibrary'), 10, 1);
        add_action('wpmf_update_folder_name', array($this, 'updateFolderNameLibrary'), 10, 2);
        add_action('wpmf_move_folder', array($this, 'moveFolderLibrary'), 10, 3);
        add_action('wpmf_attachment_set_folder', array($this, 'moveFileLibrary'), 10, 3);
        add_action('delete_attachment', array($this, 'deleteAttachment'), 10);
        add_action('wpmfSyncOnedrive', array($this, 'autoSyncWithCrontabMethod'));
        add_filter('wpmf_sync_onedrive', array($this, 'doSync'), 10, 3);
        add_filter('wpmf_onedrive_remove', array($this, 'syncRemoveItems'), 10, 3);
        add_filter('wpmf_move_local_to_cloud', array($this, 'moveLocalToCloud'), 10, 3);
    }

    /**
     * Filter hooks
     *
     * @return void
     */
    public function filterHooks()
    {
        add_filter('wpmfaddon_onedrivesettings', array($this, 'renderSettings'), 10, 1);
        add_filter('wpmf_onedrive_import', array($this, 'importFile'), 10, 5);
        add_filter('the_content', array($this, 'theContent'));
        add_filter('wp_update_attachment_metadata', array($this, 'wpGenerateAttachmentMetadata'), 10, 2);
    }

    /**
     * Add Onedrive notice
     *
     * @return void
     */
    public function onedriveNotice()
    {
        echo '<div class="error" id="wpmf_error">'
            . '<p>'
            . esc_html__('WP Media Folder: Onedrive Personal API has been updated, you need to reconnect your account in the settings page', 'wpmfAddon')
            . '<a href="'. esc_url(admin_url('options-general.php?page=option-folder#one_drive_box')) .'" class="button button-primary"
        style="margin: 0 5px;">
        ' . esc_html__('WP Media Folder Settings', 'wpmfAddon') . '</a>'
            . '</p>'
            . '</div>';
    }

    /**
     * Add script to open video in new window
     *
     * @param string $content Content of current post/page
     *
     * @return mixed
     */
    public function theContent($content)
    {
        if (strpos($content, 'wpmf_odv_video')) {
            wp_enqueue_script('wpmf-openwindow');
        }
        return $content;
    }

    /**
     * Load scripts
     *
     * @return void
     */
    public function frontendStyleScript()
    {
        wp_register_script(
            'wpmf-openwindow',
            plugins_url('/assets/js/frontend_openwindow.js', dirname(__FILE__)),
            array(),
            WPMFAD_VERSION,
            true
        );
        wp_localize_script('wpmf-openwindow', 'wpmfaddonlang', array(
            'wpmf_images_path' => plugins_url('assets/images', dirname(__FILE__)),
            'ajaxurl'          => admin_url('admin-ajax.php')
        ));
    }

    /**
     * Enqueue styles and scripts for gutenberg
     *
     * @return void
     */
    public function addEditorAssets()
    {
        wp_enqueue_script(
            'wpmfonedrive_blocks',
            plugins_url('assets/blocks/wpmfonedrive/block.js', dirname(__FILE__)),
            array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-data', 'wp-editor'),
            WPMFAD_VERSION
        );

        wp_enqueue_style(
            'wpmfonedrive_blocks',
            plugins_url('assets/blocks/wpmfonedrive/style.css', dirname(__FILE__)),
            array(),
            WPMFAD_VERSION
        );

        $params = array(
            'l18n' => array(
                'btnopen'        => __('OneDrive Media', 'wpmfAddon'),
                'onedrive_drive' => __('OneDrive', 'wpmfAddon'),
                'edit'           => __('Edit', 'wpmfAddon'),
                'remove'         => __('Remove', 'wpmfAddon'),
                'loading'         => __('Loading...', 'wpmfAddon')
            ),
            'vars' => array(
                'block_cover' => WPMFAD_URL .'assets/blocks/wpmfonedrive/preview.png'
            )
        );

        wp_localize_script('wpmfonedrive_blocks', 'wpmfodvblocks', $params);
    }

    /**
     * Onedrive settings html
     *
     * @param string $html HTML
     *
     * @return string
     */
    public function renderSettings($html)
    {
        $onedriveDrive  = new WpmfAddonOneDrive();
        $onedrive_config = get_option('_wpmfAddon_onedrive_config');
        if (empty($onedrive_config['OneDriveClientId'])) {
            $onedrive_config['OneDriveClientId'] = '';
        }

        if (empty($onedrive_config['OneDriveClientSecret'])) {
            $onedrive_config['OneDriveClientSecret'] = '';
        }

        if (empty($onedrive_config['link_type'])) {
            $onedrive_config['link_type'] = 'private';
        }

        if (!isset($onedrive_config['generate_thumbnails'])) {
            $onedrive_config['generate_thumbnails'] = 1;
        }

        if (!isset($onedrive_config['media_access'])) {
            $onedrive_config['media_access'] = 0;
        }

        if (!isset($onedrive_config['access_by'])) {
            $onedrive_config['access_by'] = 'user';
        }

        if (!isset($onedrive_config['load_all_childs'])) {
            $onedrive_config['load_all_childs'] = 0;
        }

        if (isset($_POST['OneDriveClientId']) && isset($_POST['OneDriveClientSecret'])) {
            if (empty($_POST['wpmf_nonce'])
                || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
                die();
            }

            $onedrive_config['OneDriveClientId']     = trim($_POST['OneDriveClientId']);
            $onedrive_config['OneDriveClientSecret'] = trim($_POST['OneDriveClientSecret']);
            if (isset($_POST['onedrive_link_type'])) {
                $onedrive_config['link_type'] = $_POST['onedrive_link_type'];
            }

            if (isset($_POST['onedrive_generate_thumbnails'])) {
                $onedrive_config['generate_thumbnails'] = $_POST['onedrive_generate_thumbnails'];
            }

            if (isset($_POST['onedrive_media_access'])) {
                $onedrive_config['media_access'] = $_POST['onedrive_media_access'];
            }

            if (isset($_POST['onedrive_access_by'])) {
                $onedrive_config['access_by'] = $_POST['onedrive_access_by'];
            }

            if (isset($_POST['onedrive_load_all_childs'])) {
                $onedrive_config['load_all_childs'] = $_POST['onedrive_load_all_childs'];
            }

            update_option('_wpmfAddon_onedrive_config', $onedrive_config);
            $onedrive_config = get_option('_wpmfAddon_onedrive_config');
            $onedriveDrive  = new WpmfAddonOneDrive();
        }

        ob_start();
        require_once 'templates/settings_onedrive.php';
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * Logout Onedrive app
     *
     * @return void
     */
    public function onedriveLogout()
    {
        /**
         * Filter check capability of current user to logout Onedrive
         *
         * @param boolean The current user has the given capability
         * @param string  Action name
         *
         * @return boolean
         *
         * @ignore Hook already documented
         */
        $wpmf_capability = apply_filters('wpmf_user_can', current_user_can('upload_files'), 'onedrive_logout');
        if (!$wpmf_capability) {
            wp_send_json(false);
        }
        $params              = get_option('_wpmfAddon_onedrive_config');
        unset($params['connected']);
        unset($params['state']);
        unset($params['onedriveBaseFolder']);
        update_option('_wpmfAddon_onedrive_config', $params);
        delete_option('wpmf_onedrive_create_root');
        wp_send_json(array('status' => true));
    }
}
