<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

require_once(WPMFAD_PLUGIN_DIR . '/class/wpmfHelper.php');
require_once(WPMFAD_PLUGIN_DIR . '/class/wpmfGoogle.php');
require_once(WPMFAD_PLUGIN_DIR . '/class/Google/autoload.php');

/**
 * Class WpmfAddonGoogle
 * This class that holds most of the admin functionality for Google drive
 */
class WpmfAddonGoogle extends WpmfAddonGoogleDrive
{

    /**
     * WpmfAddonGoogle constructor.
     */
    public function __construct()
    {
        parent::__construct();
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
        add_action('wp_ajax_wpmf-download-file', array($this, 'downloadFile'));
        add_action('wp_ajax_nopriv_wpmf-download-file', array($this, 'downloadFile'));
        add_action('wp_ajax_wpmf-preview-file', array($this, 'previewFile'));
        add_action('wp_ajax_nopriv_wpmf-preview-file', array($this, 'previewFile'));
        add_action('wp_ajax_wpmf_google_add_queue', array($this, 'ajaxAddToQueue'));
        add_action('wp_ajax_wpmf_google_sync_full', array($this, 'autoSyncWithCrontabMethod'));
        add_action('wp_ajax_nopriv_wpmf_google_sync_full', array($this, 'autoSyncWithCrontabMethod'));
    }

    /**
     * Action hooks
     *
     * @return void
     */
    public function actionHooks()
    {
        add_action('admin_init', array($this, 'addRootToQueue'));
        add_action('enqueue_block_editor_assets', array($this, 'addEditorAssets'), 9999);
        add_action('add_attachment', array($this, 'addAttachment'), 10, 1);
        add_action('wpmf_create_folder', array($this, 'createFolderLibrary'), 10, 4);
        add_action('wpmf_before_delete_folder', array($this, 'deleteFolderLibrary'), 10, 1);
        add_action('wpmf_update_folder_name', array($this, 'updateFolderNameLibrary'), 10, 2);
        add_action('wpmf_move_folder', array($this, 'moveFolderLibrary'), 10, 3);
        add_action('wpmf_attachment_set_folder', array($this, 'moveFileLibrary'), 10, 3);
        add_action('delete_attachment', array($this, 'deleteAttachment'), 10);
        add_action('wpmfSyncGoogle', array($this, 'autoSyncWithCrontabMethod'));
        add_filter('wpmf_sync_google_drive', array($this, 'doSync'), 10, 3);
        add_filter('wpmf_google_drive_remove', array($this, 'syncRemoveItems'), 10, 3);
        add_filter('wpmf_move_local_to_cloud', array($this, 'moveLocalToCloud'), 10, 3);
    }

    /**
     * Filter hooks
     *
     * @return void
     */
    public function filterHooks()
    {
        add_filter('wpmf_google_import', array($this, 'importFile'), 10, 5);
        add_filter('wpmfaddon_ggsettings', array($this, 'renderSettings'), 10, 3);
        add_filter('wpmfaddon_synchronization_settings', array($this, 'renderSynchronizationSettings'), 10, 1);
        add_filter('wp_update_attachment_metadata', array($this, 'wpGenerateAttachmentMetadata'), 10, 2);
    }

    /**
     * Render google drive settings
     *
     * @param string $html         HTML
     * @param object $googleDrive  WpmfAddonGoogleDrive class
     * @param array  $googleconfig Google drive config
     *
     * @return string
     */
    public function renderSettings($html, $googleDrive, $googleconfig)
    {
        if (empty($googleconfig['googleClientId'])) {
            $googleconfig['googleClientId'] = '';
        }

        if (empty($googleconfig['googleClientSecret'])) {
            $googleconfig['googleClientSecret'] = '';
        }

        if (!isset($googleconfig['generate_thumbnails'])) {
            $googleconfig['generate_thumbnails'] = 1;
        }

        if (!isset($googleconfig['media_access'])) {
            $googleconfig['media_access'] = 0;
        }

        if (!isset($googleconfig['access_by'])) {
            $googleconfig['access_by'] = 'user';
        }

        if (!isset($googleconfig['load_all_childs'])) {
            $googleconfig['load_all_childs'] = 0;
        }

        ob_start();
        require_once 'templates/settings_google_drive.php';
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * Render synchronization settings
     *
     * @param string $html HTML
     *
     * @return string
     */
    public function renderSynchronizationSettings($html)
    {
        $odv_settings = get_option('_wpmfAddon_onedrive_config');
        $odvbn_settings = get_option('_wpmfAddon_onedrive_business_config');
        $dropbox_settings = get_option('_wpmfAddon_dropbox_config');
        $google_settings = get_option('_wpmfAddon_cloud_config');

        $sync_method = wpmfGetOption('sync_method');
        // remove curl mothod, so we use ajax method
        if ($sync_method === 'curl') {
            $sync_method = 'ajax';
        }
        $sync_periodicity = wpmfGetOption('sync_periodicity');
        ob_start();
        require_once 'templates/synchronization.php';
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * Enqueue styles and scripts for gutenberg
     *
     * @return void
     */
    public function addEditorAssets()
    {
        wp_enqueue_script(
            'wpmfgoogle_blocks',
            plugins_url('assets/blocks/wpmfgoogle/block.js', dirname(__FILE__)),
            array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-data', 'wp-editor' ),
            WPMFAD_VERSION
        );

        wp_enqueue_style(
            'wpmfgoogle_blocks',
            plugins_url('assets/blocks/wpmfgoogle/style.css', dirname(__FILE__)),
            array(),
            WPMFAD_VERSION
        );

        $params = array(
            'l18n' => array(
                'btnopen' => __('Google Drive Media', 'wpmfAddon'),
                'google_drive' => __('Google Drive', 'wpmfAddon'),
                'edit' => __('Edit', 'wpmfAddon'),
                'remove' => __('Remove', 'wpmfAddon')
            ),
            'vars' => array(
                'block_cover' => WPMFAD_URL .'assets/blocks/wpmfgoogle/preview.png'
            )
        );

        wp_localize_script('wpmfgoogle_blocks', 'wpmfblocks', $params);
    }

    /**
     * Access google drive
     *
     * @param string $type Google photo or google drive
     *
     * @return void
     */
    public function ggAuthenticated($type = 'google-drive')
    {
        $google      = new WpmfAddonGoogleDrive($type);
        $credentials = $google->authenticate($type);
        if ($type === 'google-drive') {
            $google->storeCredentials($credentials);
            $data                     = $this->getParams();
            //Check if WPMF folder exists and create if not
            $folderName = 'WP Media Folder - ' . get_bloginfo('name');
            /**
             * Filter to set root cloud folder name for automatic method
             *
             * @param string Folder name
             *
             * @return string
             */
            $folderName = apply_filters('wpmf_cloud_folder_name', $folderName);
            if (empty($data['googleBaseFolder'])) {
                $folder                   = $google->createFolder($folderName);
                $data['googleBaseFolder'] = $folder->id;
            } else {
                $client = $this->getClient($data);
                $service     = new WpmfGoogle_Service_Drive($client);
                try {
                    if (!empty($data['drive_type']) && $data['drive_type'] === 'team_drive') {
                        $folder     = $service->drives->get($data['googleBaseFolder']);
                    } else {
                        $folder     = $service->files->get($data['googleBaseFolder']);
                    }
                } catch (Exception $e) {
                    $folder                   = $google->createFolder($folderName);
                }

                $data['googleBaseFolder'] = $folder->id;
            }

            if (!empty($data['googleBaseFolder'])) {
                $data['connected']  = 1;
                $this->setParams($data);
            }
            $this->redirect(admin_url('options-general.php?page=option-folder#google_drive_box'));
        } else {
            $data = get_option('_wpmfAddon_google_photo_config', true);
            $data['googleCredentials']  = $credentials;
            $data['connected']  = 1;
            update_option('_wpmfAddon_google_photo_config', $data);
            $this->redirect(admin_url('options-general.php?page=option-folder#google_photo'));
        }
    }

    /**
     * Access google drive
     *
     * @param string $type Google photo or google drive
     *
     * @return void
     */
    public function ggCloudAuthenticated($type = 'google-drive')
    {
        $google      = new WpmfAddonGoogleDrive($type);
        $credentials = $google->authenticate($type, admin_url('options-general.php?page=option-folder&task=wpmf&function=wpmf_google_cloud_auth'));
        $data = get_option('_wpmfAddon_google_cloud_storage_config', true);
        $data['googleCredentials']  = $credentials;
        $data['connected']  = 1;
        update_option('_wpmfAddon_google_cloud_storage_config', $data);
        $this->redirect(admin_url('options-general.php?page=option-folder#storage_provider'));
    }

    /**
     * Get google config
     *
     * @param string $type Google photo or google drive
     *
     * @return mixed
     */
    public function getParams($type = 'google-drive')
    {
        return WpmfAddonHelper::getAllCloudConfigs($type);
    }

    /**
     * Set google config
     *
     * @param array  $data Data to set config
     * @param string $type Google photo or google drive
     *
     * @return void
     */
    public function setParams($data, $type = 'google-drive')
    {
        WpmfAddonHelper::saveCloudConfigs($data, $type);
    }

    /**
     * Redirect url
     *
     * @param string $location URL
     *
     * @return void
     */
    public function redirect($location)
    {
        if (!headers_sent()) {
            header('Location: ' . $location, true, 303);
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
            echo "<script>document.location.href='" . str_replace("'", '&apos;', $location) . "';</script>\n";
        }
    }

    /**
     * Logout google drive app
     *
     * @param string $type Google photo or google drive
     *
     * @return void
     */
    public function ggLogout($type = 'google-drive')
    {
        if ($type === 'google-drive') {
            $data                      = $this->getParams();
            unset($data['connected']);
            unset($data['googleCredentials']);
            $this->setParams($data);
            delete_option('wpmf_google_drive_create_root');
            $this->redirect(admin_url('options-general.php?page=option-folder#google_drive_box'));
        } else {
            $data                      = $this->getParams('google-photo');
            unset($data['googleCredentials']);
            unset($data['token_expires']);
            unset($data['token_created']);
            unset($data['connected']);
            $this->setParams($data, 'google-photo');
            $this->redirect(admin_url('options-general.php?page=option-folder#google_photo'));
        }
    }

    /**
     * Logout google cloud storage
     *
     * @return void
     */
    public function googleCloudLogout()
    {
        $data = get_option('_wpmfAddon_google_cloud_storage_config', true);
        unset($data['googleCredentials']);
        unset($data['token_expires']);
        unset($data['token_created']);
        unset($data['connected']);
        update_option('_wpmfAddon_google_cloud_storage_config', $data);
        $this->redirect(admin_url('options-general.php?page=option-folder#storage_provider'));
    }
}
