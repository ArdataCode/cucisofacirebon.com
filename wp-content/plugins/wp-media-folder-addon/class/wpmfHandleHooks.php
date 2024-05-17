<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
require_once(WPMFAD_PLUGIN_DIR . '/class/wpmfHelper.php');
use Joomunited\Queue\V1_0_0\JuMainQueue;

/**
 * Class WpmfHandleHooks
 * This class that holds most of the admin functionality
 */
class WpmfHandleHooks extends WpmfAddonHelper
{
    /**
     * WpmfAddonOneDriveAdmin constructor.
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'wpmfAddonLoadAutoSyncCloudScript'));
        add_filter('image_send_to_editor', array($this, 'mediaSendToEditor'), 10, 8);
        add_action('wp_ajax_wpmf_update_cloud_last_sync', array($this, 'updateCloudLastSync'));
        add_action('wpmf_attachment_set_folder', array($this, 'attachmentSetFolder'), 10, 3);
        add_action('wpmf_remove_local_file', array($this, 'removeLocalFile'), 10, 3);
        add_filter('wpmf_replace_local_to_cloud', array($this, 'replaceLocalUrltoCloud'), 10, 3);
        add_filter('wpmf_replace_cloud_url_by_page', array($this, 'updateAttachmentUrlToDatabaseByPage'), 10, 3);
    }

    /**
     * Filters the image HTML markup to send to the editor when inserting an image.
     *
     * @param string       $html    The image HTML markup to send.
     * @param integer      $id      The attachment id.
     * @param string       $caption The image caption.
     * @param string       $title   The image title.
     * @param string       $align   The image alignment.
     * @param string       $url     The image source URL.
     * @param string|array $size    Size of image. Image size or array of width and height values
     * @param string       $alt     The image alternative, or alt, text.
     *
     * @return string $html
     */
    public function mediaSendToEditor($html, $id, $caption, $title, $align, $url, $size, $alt = '')
    {
        $post      = get_post($id);
        if (in_array($post->post_mime_type, array('image/jpg', 'image/png', 'image/jpeg', 'image/webp'))) {
            $drive_id = get_post_meta((int)$id, 'wpmf_drive_id', true);
            if (!empty($drive_id)) {
                $doc = new DOMDocument();
                libxml_use_internal_errors(true);
                $sousce = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
                $doc->loadHTML($sousce);
                $tags = $doc->getElementsByTagName('img');
                if ($tags->length > 0) {
                    if (!empty($tags)) {
                        $cloud_type = get_post_meta((int)$id, 'wpmf_drive_type', true);
                        $width = $tags->item(0)->getAttribute('width');
                        $height = $tags->item(0)->getAttribute('height');
                        switch ($cloud_type) {
                            case 'google_drive':
                                $thumb_url = admin_url('admin-ajax.php') . '?action=wpmf-download-file&local_id='. $id .'&id=' . urlencode($drive_id) . '&link=true&dl=0&sz=w' . $width;
                                break;
                            case 'dropbox':
                                $thumb_url = admin_url('admin-ajax.php') . '?action=wpmf-dbxdownload-file&id=' . urlencode($drive_id) . '&link=true&dl=0&size=w' . $width .'h' . $height;
                                break;
                            case 'onedrive':
                                $thumb_url = admin_url('admin-ajax.php') . '?action=wpmf_onedrive_download&local_id='. $id .'&id=' . urlencode($drive_id) . '&link=true&dl=0&size=' . $size;
                                break;
                            case 'onedrive_business':
                                $thumb_url = admin_url('admin-ajax.php') . '?action=wpmf_onedrive_business_download&local_id='. $id .'&id=' . urlencode($drive_id) . '&link=true&dl=0&size=' . $size;
                                break;
                        }

                        $tags->item(0)->setAttribute('src', $thumb_url);
                    }
                }
                $html = $doc->saveHTML();
            }
        }
        return $html;
    }


    /**
     * Remove local file
     *
     * @param boolean $result     Result
     * @param array   $datas      Data details
     * @param integer $element_id ID of queue element
     *
     * @return boolean
     */
    public static function removeLocalFile($result, $datas, $element_id)
    {
        if (isset($datas['file_path']) && file_exists($datas['file_path'])) {
            unlink($datas['file_path']);
            return true;
        }
        return false;
    }

    /**
     * Move file from server to cloud
     *
     * @param integer $attachment_id Attachment ID
     * @param integer $folder_id     Folder ID
     * @param array   $params        Params
     *
     * @return void
     */
    public function attachmentSetFolder($attachment_id, $folder_id, $params)
    {
        if (!isset($params['trigger'])) {
            return;
        }

        if ($params['trigger'] === 'move_attachment' && !empty($params['local_to_cloud'])) {
            $folder_drive_type = get_term_meta($folder_id, 'wpmf_drive_type', true);
            if (!empty($folder_drive_type)) {
                $drive_id = get_term_meta($folder_id, 'wpmf_drive_root_id', true);
                if (empty($drive_id)) {
                    $drive_id = get_term_meta($folder_id, 'wpmf_drive_id', true);
                }

                if (empty($drive_id) && $folder_drive_type !== 'dropbox') {
                    return;
                }

                $datas = array(
                    'attachment_id' => $attachment_id,
                    'action' => 'wpmf_move_local_to_cloud',
                    'type' => $folder_drive_type,
                    'local_folder_id' => $folder_id,
                    'cloud_folder_id' => ($folder_drive_type === 'dropbox') ? $folder_id : $drive_id
                );

                $wpmfQueue = JuMainQueue::getInstance('wpmf');
                $wpmfQueue->addToQueue($datas);
            }
        }
    }

    /**
     * Load auto sync cloud script
     *
     * @return void
     */
    public function wpmfAddonLoadAutoSyncCloudScript()
    {
        // check cloud connected
        $dropbox_config = get_option('_wpmfAddon_dropbox_config');
        $google_config = get_option('_wpmfAddon_cloud_config');
        $onedrive_config = get_option('_wpmfAddon_onedrive_config');
        $onedrive_business_config = get_option('_wpmfAddon_onedrive_business_config');
        if (!empty($dropbox_config['dropboxToken'])
            || (!empty($google_config['googleCredentials']) && !empty($google_config['googleBaseFolder']))
            || (!empty($onedrive_config['connected']) && !empty($onedrive_config['onedriveBaseFolder']['id']))
            || (!empty($onedrive_business_config['connected']) && !empty($onedrive_business_config['onedriveBaseFolder']['id']))) {
            // check sync method to run ajax
            $sync_method = wpmfGetOption('sync_method');
            // remove curl mothod, so we use ajax method
            if ($sync_method === 'curl') {
                $sync_method = 'ajax';
            }
            $sync_periodicity = wpmfGetOption('sync_periodicity');
            $last_sync = get_option('wpmf_cloud_time_last_sync');
            if (empty($last_sync)) {
                add_option('wpmf_cloud_time_last_sync', time());
                $last_sync = get_option('wpmf_cloud_time_last_sync');
            }

            wp_enqueue_script(
                'wpmfAutoSyncClouds',
                WPMFAD_PLUGIN_URL . 'assets/js/sync_clouds.js',
                array('jquery'),
                WPMFAD_VERSION
            );

            wp_localize_script('wpmfAutoSyncClouds', 'wpmfAutoSyncClouds', array(
                'vars' => array(
                    'last_sync' => (int) $last_sync,
                    'sync_method' => $sync_method,
                    'sync_periodicity' => (int) $sync_periodicity,
                    'wpmf_nonce' => wp_create_nonce('wpmf_nonce')
                ),
                'l18n' => array(
                    'hover_cloud_syncing' => esc_html__('Cloud syncing on the way', 'wpmfAddon'),
                    'sync_all_clouds_notice'  => __('Please wait while WP Media Folder is syncing the cloud files', 'wpmfAddon')
                )
            ));
        }
    }

    /**
     * Update cloud last sync
     *
     * @return void
     */
    public function updateCloudLastSync()
    {
        if (empty($_POST['wpmf_nonce'])
            || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
            die();
        }

        $time = time();
        if (!get_option('wpmf_cloud_time_last_sync', false)) {
            add_option('wpmf_cloud_time_last_sync', $time);
        } else {
            update_option('wpmf_cloud_time_last_sync', $time);
        }

        if (!get_option('wpmf_cloud_connection_notice', false)) {
            add_option('wpmf_cloud_connection_notice', 1);
        }

        delete_option('wpmf_cloud_sync_token');
        delete_option('wpmf_cloud_sync_time');
        wp_send_json(array('status' => true, 'time' => $time));
    }
}
