<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
require_once(WPMFAD_PLUGIN_DIR . '/class/wpmfHelper.php');
require_once(WPMFAD_PLUGIN_DIR . '/class/wpmfAws3.php');
if (defined('BP_PLUGIN_DIR')) {
    if (!class_exists('BP_Media_Stream')) {
        $bb_path = trailingslashit(constant('BP_PLUGIN_DIR') . constant('BP_SOURCE_SUBDIRECTORY'));
        // Include Media Streamline.
        if (file_exists($bb_path . 'bp-media/classes/class-bp-media-stream.php')) {
            require $bb_path . 'bp-media/classes/class-bp-media-stream.php';
        }
    }
}

use WP_Media_Folder\Aws\S3\Exception\S3Exception;
use Joomunited\Queue\V1_0_0\JuMainQueue;

/**
 * Class WpmfAddonOneDriveAdmin
 * This class that holds most of the admin functionality for OneDrive
 */
class WpmfAddonAws3Admin
{

    /**
     * Amazon settings
     *
     * @var array
     */
    public $aws3_settings = array();

    /**
     * Amazon default settings
     *
     * @var array
     */
    public $aws3_config_default = array();

    /**
     * WpmfAddonOneDriveAdmin constructor.
     */
    public function __construct()
    {
        if (is_plugin_active('wp-media-folder/wp-media-folder.php')) {
            $this->runUpgrades();
            $aws3config                = getOffloadOption();
            $this->aws3_config_default = array(
                'signature_version'        => 'v4',
                'version'                  => '2006-03-01',
                'region'                   => 'us-east-1',
                'mendpoint'                 => 'amazonaws',
                'bucket'                   => 0,
                'credentials'              => array(
                    'key'    => '',
                    'secret' => ''
                ),
                'copy_files_to_bucket'     => 0,
                'remove_files_from_server' => 0,
                'attachment_label'         => 0,
                'enable_custom_domain'         => 0,
                'custom_domain'         => ''
            );

            if (is_array($aws3config)) {
                if (isset($aws3config['region'])) {
                    $aws3config['region'] = strip_tags($aws3config['region']);
                }
                $this->aws3_settings = array_merge($this->aws3_config_default, $aws3config);
            } else {
                $this->aws3_settings = $this->aws3_config_default;
            }

            $this->actionHooks();
            $this->filterHooks();
            $this->handleAjax();
        }
    }

    /**
     * Ajax action
     *
     * @return void
     */
    public function handleAjax()
    {
        add_action('wp_ajax_wpmf-get-buckets', array($this, 'getBucketsList'));
        add_action('wp_ajax_wpmf-get-buckets-by-region', array($this, 'getBucketsByRegion'));
        add_action('wp_ajax_wpmf-create-bucket', array($this, 'createBucket'));
        add_action('wp_ajax_wpmf-delete-bucket', array($this, 'deleteBucket'));
        add_action('wp_ajax_wpmf-select-bucket', array($this, 'selectBucket'));
        add_action('wp_ajax_wpmf-uploadto-s3', array($this, 'uploadToS3'));
        add_action('wp_ajax_wpmf-download-s3', array($this, 'downloadObject'));
        add_action('wp_ajax_wpmf_upload_single_file_to_s3', array($this, 'ajaxUploadSingleFileToS3'));
        add_action('wp_ajax_wpmf_remove_local_file', array($this, 'removeLocalFile'));
        add_action('wp_ajax_wpmf-list-all-objects-from-bucket', array($this, 'listAllObjectsFromBucket'));
        add_action('wp_ajax_wpmf-list-all-copy-objects-from-bucket', array($this, 'listAllCopyObjects'));
        add_action('wp_ajax_wpmf-copy-objects-from-bucket', array($this, 'ajaxCopyObject'));
        add_action('wp_ajax_wpmf_save_storage', array($this, 'saveStorage'));
        add_filter('option__wpmfAddon_aws3_config', array($this,'loadCloudOption'), 10, 2);
    }

    /**
     * Filters the value of an existing option.
     *
     * @param mixed  $value  Value of the option. If stored serialized, it will be
     *                       unserialized prior to being returned.
     * @param string $option Option name.
     *
     * @return mixed|void
     */
    public function loadCloudOption($value, $option)
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
        if (isset($_GET['cloud'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
            $cloud_endpoint = $_GET['cloud'];
        } else {
            $cloud_endpoint = get_option('wpmf_cloud_endpoint');
            if (empty($cloud_endpoint)) {
                $cloud_endpoint = 'aws3';
            }
        }

        if ($cloud_endpoint !== 'aws3') {
            $aws3config = get_option('_wpmfAddon_'. $cloud_endpoint .'_config');
            return $aws3config;
        }

        return $value;
    }

    /**
     * Action hooks
     *
     * @return void
     */
    public function actionHooks()
    {
        if (!empty($this->aws3_settings['copy_files_to_bucket'])) {
            add_action('add_attachment', array($this, 'addAttachment'), 10, 1);
        }

        add_action('admin_enqueue_scripts', array($this, 'loadAdminScripts'));
        add_action('add_meta_boxes', array($this, 'attachmentMetaBox'));
        add_action('shortpixel/image/optimised', array($this, 'imageUpload'), 10);
    }

    /**
     * Filter hooks
     *
     * @return void
     */
    public function filterHooks()
    {
        add_filter('option__wpmfAddon_aws3_config', array($this, 'getAws3Configs'), 10, 2);
        add_filter('option__wpmfAddon_wasabi_config', array($this, 'getAws3Configs'), 10, 2);
        add_filter('option__wpmfAddon_digitalocean_config', array($this, 'getAws3Configs'), 10, 2);
        add_filter('option__wpmfAddon_linode_config', array($this, 'getAws3Configs'), 10, 2);
        add_filter('option__wpmfAddon_google_cloud_storage_config', array($this, 'getAws3Configs'), 10, 2);

        add_filter('wpmfaddon_aws3settings', array($this, 'renderSettings'), 10, 1);
        add_filter('delete_attachment', array($this, 'deleteAttachment'), 20);
        add_filter('wp_get_attachment_url', array($this, 'wpGetAttachmentUrl'), 99, 2);
        add_filter('wp_calculate_image_srcset', array($this, 'wpCalculateImageSrcset'), 10, 5);
        add_filter('wp_calculate_image_srcset_meta', array($this, 'wpCalculateImageSrcsetMeta'), 10, 4);
        add_filter('wp_prepare_attachment_for_js', array($this, 'wpPrepareAttachmentForJs'), 99, 3);
        add_filter('wp_generate_attachment_metadata', array($this, 'wpUpdateAttachmentMetadata'), 110, 2);
        add_filter('bp_media_get_preview_image_url', array($this, 'bpMediaOffloadGetPreviewUrl'), PHP_INT_MAX, 5);
        add_filter('wpmf_s3_replace_local', array($this, 'replaceLocalUrl'), 10, 3);
        add_filter('wpmf_s3_replace_urls3', array($this, 'replaceLocalUrlS3'), 10, 3);
        add_filter('wpmf_replace_s3_url_by_page', array($this, 'updateAttachmentUrlToDatabaseByPage'), 10, 3);
        add_filter('wpmf_replace_digitalocean_url_by_page', array($this, 'updateAttachmentUrlToDatabaseByPage'), 10, 3);
        add_filter('wpmf_replace_linode_url_by_page', array($this, 'updateAttachmentUrlToDatabaseByPage'), 10, 3);
        add_filter('wpmf_replace_wasabi_url_by_page', array($this, 'updateAttachmentUrlToDatabaseByPage'), 10, 3);
        add_filter('wpmf_replace_google_cloud_storage_url_by_page', array($this, 'updateAttachmentUrlToDatabaseByPage'), 10, 3);
        add_filter('wpmf_s3_import', array($this, 'importObjectsFromBucket'), 10, 3);
        add_filter('wpmf_digitalocean_import', array($this, 'importObjectsFromBucket'), 10, 3);
        add_filter('wpmf_wasabi_import', array($this, 'importObjectsFromBucket'), 10, 3);
        add_filter('wpmf_linode_import', array($this, 'importObjectsFromBucket'), 10, 3);
        add_filter('wpmf_google_cloud_storage_import', array($this, 'importObjectsFromBucket'), 10, 3);
        add_filter('wpmf_s3_remove_local_file', array($this, 's3RemoveLocalFile'), 10, 3);
        add_filter('bb_media_do_symlink', array($this, 'bbDoSymlink'), PHP_INT_MAX, 4);
        add_filter('bb_document_do_symlink', array($this, 'bbDoSymlink'), PHP_INT_MAX, 4);
        add_filter('bb_video_do_symlink', array($this, 'bbDoSymlink'), PHP_INT_MAX, 4);
        add_filter('bb_video_create_thumb_symlinks', array($this, 'bbDoSymlink'), PHP_INT_MAX, 4);
        add_filter('default_post_metadata', array($this, 'defaultPostMetadata'), 10, 5);
    }

    /**
     * Upload image webp
     *
     * @param object $imageItem Image item
     *
     * @return array|boolean
     */
    public function imageUpload($imageItem)
    {
        if (empty($this->aws3_settings['copy_files_to_bucket'])) {
            return false;
        }

        // Only medialibrary offloading supported.
        if ('media' !== $imageItem->get('type')) {
            return false;
        }

        $id = $imageItem->get('id');
        $meta = get_post_meta($id, '_wp_attachment_metadata', true);
        $parent_path = $this->getFolderS3Path($id);
        $file_paths = WpmfAddonHelper::getAttachmentFilePaths($id, $meta);

        $aws3 = new WpmfAddonAWS3();
        $cloud_endpoint = get_option('wpmf_cloud_endpoint');
        include_once 'includes/mime-types.php';
        foreach ($file_paths as $size => $file_path) {
            if (!file_exists($file_path)) {
                continue;
            }

            $infofile = pathinfo($file_path);
            $webp_path = str_replace($infofile['extension'], 'webp', $file_path);
            if (!file_exists($webp_path)) {
                continue;
            }

            try {
                if ($cloud_endpoint === 'google_cloud_storage') {
                    $config = get_option('_wpmfAddon_google_cloud_storage_config', true);
                    $client = $this->getGoogleClient($config);
                    $service = new WpmfGoogle_Service_Storage($client);
                    $obj = new WpmfGoogle_Service_Storage_StorageObject();
                    $obj->setName($parent_path . basename($webp_path));
                    $obj->setAcl('public-read');
                    $contenType = 'image/webp';
                    $obj->setContentType($contenType);
                    $service->objects->insert(
                        $config['bucket'],
                        $obj,
                        array('name' => $parent_path . basename($webp_path), 'data' => file_get_contents($webp_path), 'uploadType' => 'media', 'mimeType' => $contenType)
                    );

                    // public object
                    $acl = new WpmfGoogle_Service_Storage_ObjectAccessControl();
                    $acl->setEntity('allUsers');
                    $acl->setRole('READER');
                    $acl->setBucket($config['bucket']);
                    $acl->setObject($parent_path . basename($webp_path));
                    $response = $service->objectAccessControls->insert($config['bucket'], $parent_path . basename($webp_path), $acl);
                } else {
                    $aws3->uploadObject(
                        array(
                            'ACL' => 'public-read',
                            'Bucket' => $this->aws3_settings['bucket'],
                            'Key' => $parent_path . basename($webp_path),
                            'SourceFile' => $webp_path,
                            'ContentType' => get_post_mime_type($id),
                            'CacheControl' => 'max-age=31536000',
                            'Expires' => date('D, d M Y H:i:s O', time() + 31536000),
                            'Metadata' => array(
                                'attachment_id' => $id,
                                'size' => $size
                            )
                        )
                    );
                }
            } catch (Exception $e) {
                $res = array('status' => false, 'msg' => esc_html($e->getMessage()));
                return $res;
            }
        }

        // remove after upload to cloud
        $this->doRemoveLocalFile($id);
        return true;
    }

    /**
     * Filters the value of an existing option.
     *
     * @param mixed  $value  Value of the option. If stored serialized, it will be
     *                       unserialized prior to being returned.
     * @param string $option Option name.
     *
     * @return mixed
     */
    public function getAws3Configs($value, $option)
    {
        if (defined('WPMF_AWS3_SETTINGS')) {
            $aws3_configs = unserialize(WPMF_AWS3_SETTINGS);
            if (!empty($aws3_configs['access-key-id']) && !empty($aws3_configs['secret-access-key'])) {
                $value['credentials']['key'] = $aws3_configs['access-key-id'];
                $value['credentials']['secret'] = $aws3_configs['secret-access-key'];
            }

            if (!empty($aws3_configs['bucket'])) {
                $value['bucket'] = $aws3_configs['bucket'];
            }

            if (!empty($aws3_configs['region'])) {
                $value['region'] = $aws3_configs['region'];
            }

            if (!empty($aws3_configs['root_folder_name'])) {
                $value['root_folder_name'] = $aws3_configs['root_folder_name'];
            }
        }

        return $value;
    }

    /**
     * Compatible with elementor svg
     *
     * @param mixed   $value     The value to return, either a single metadata value or an array of values depending on the value of `$single`.
     * @param integer $object_id ID of the object metadata is for.
     * @param string  $meta_key  Metadata key.
     * @param boolean $single    Whether to return only the first value of the specified `$meta_key`.
     * @param string  $meta_type Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user', or any other object type with an associated meta table.
     *
     * @return mixed
     */
    public function defaultPostMetadata($value, $object_id, $meta_key, $single, $meta_type)
    {
        if ($meta_key === '_elementor_inline_svg') {
            $infos = get_post_meta((int)$object_id, 'wpmf_awsS3_info', true);
            if (!empty($infos)) {
                $svg = get_post_meta((int)$object_id, '_wpmf_elementor_inline_svg', true);
                return $svg;
            }
        }

        return $value;
    }

    /**
     * Function to set the false to use the default media symlink instead use the offload media URL of media.
     *
     * @param boolean $can           Default true.
     * @param integer $id            Media/document/video id.
     * @param integer $attachment_id Attachment id.
     * @param string  $size          Preview size.
     *
     * @return boolean true if the offload media used.
     */
    public function bbDoSymlink($can, $id, $attachment_id, $size)
    {
        $aws3config = getOffloadOption();
        if (is_array($aws3config) && !empty($aws3config['copy_files_to_bucket'])) {
            $can = false;
        }

        return $can;
    }

    /**
     * Return the offload media plugin attachment url.
     *
     * @param string  $attachment_url Attachment url.
     * @param integer $media_id       Media id.
     * @param integer $attachment_id  Attachment id.
     * @param string  $size           Size of the media.
     * @param boolean $symlink        Display symlink or not.
     *
     * @return false|mixed|string return the original media URL.
     *
     * @since BuddyBoss 1.7.0
     */
    public function bpMediaOffloadGetPreviewUrl($attachment_url, $media_id, $attachment_id, $size, $symlink)
    {
        $media = new BP_Media($media_id);
        $infos = get_post_meta((int)$media->attachment_id, 'wpmf_awsS3_info', true);
        if (!empty($infos)) {
            $attachment_url = wp_get_attachment_url($media->attachment_id);
        }

        return $attachment_url;
    }

    /**
     * Remove local file on queue
     *
     * @param integer|boolean $result     Result
     * @param array           $datas      QUeue datas
     * @param integer         $element_id Queue ID
     *
     * @return boolean
     */
    public function s3RemoveLocalFile($result, $datas, $element_id)
    {
        if (!empty($datas['attachment_id'])) {
            $attachment_file = get_attached_file($datas['attachment_id']);
            if ($attachment_file) {
                $info = pathinfo($attachment_file);
                if (!in_array($info['extension'], array('jpg', 'jpg', 'webp', 'png', 'JPG', 'WEBP', 'PNG', 'JPEG'))) {
                    // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- fix warning when not have permission unlink
                    @unlink($attachment_file);
                    return true;
                }

                // update file size
                $this->updateFileSize($datas['attachment_id']);
                $meta       = get_post_meta($datas['attachment_id'], '_wp_attachment_metadata', true);
                $file_paths = WpmfAddonHelper::getAttachmentFilePaths($datas['attachment_id'], $meta);
                if ($info['extension'] === 'svg') {
                    $svg = get_post_meta($datas['attachment_id'], '_wpmf_elementor_inline_svg', true);
                    if (empty($svg)) {
                        $svg = file_get_contents($attachment_file);
                        if (!empty($svg)) {
                            add_post_meta($datas['attachment_id'], '_wpmf_elementor_inline_svg', $svg);
                        }
                    }
                }

                foreach ($file_paths as $file_path) {
                    if (!file_exists($file_path)) {
                        continue;
                    }

                    if (!is_writable($file_path)) {
                        continue;
                    }

                    $infofile = pathinfo($file_path);

                    // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- fix warning when not have permission unlink
                    @unlink($file_path);

                    if (file_exists($file_path . '.webp')) {
                        // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- fix warning when not have permission unlink
                        @unlink($file_path . '.webp');
                    }

                    $webp_path = str_replace($infofile['extension'], 'webp', $file_path);
                    if (file_exists($webp_path)) {
                        // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- fix warning when not have permission unlink
                        @unlink($webp_path);
                    }
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Import objects from Bucket
     *
     * @param integer|boolean $result     Result
     * @param array           $datas      QUeue datas
     * @param integer         $element_id Queue ID
     *
     * @return boolean
     */
    public function importObjectsFromBucket($result, $datas, $element_id)
    {
        set_time_limit(0);
        // insert folder parent
        if (dirname($datas['key']) === '.') {
            $root_folder = wp_insert_term($datas['bucket'], WPMF_TAXO, array('parent' => 0));
            if (is_wp_error($root_folder)) {
                if (isset($root_folder->error_data) && isset($root_folder->error_data['term_exists'])) {
                    $parent = $root_folder->error_data['term_exists'];
                }
            } else {
                $parent = $root_folder['term_id'];
            }
        } else {
            global $wpdb;
            $queue = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'ju_queue WHERE id = %d', array($element_id)));
            $responses = json_decode($queue->responses, true);
            $parent = $responses['parent'];
        }

        $import_key = sanitize_title($datas['bucket'] . '/' . $datas['key']);
        // insert child folder
        if ($datas['type'] === 'folder') {
            // check folder imported
            $args = array(
                'hide_empty' => false,
                'meta_query' => array(
                    array(
                        'key'       => 'wpmf_s3_import_key',
                        'value'     => $import_key,
                        'compare'   => '='
                    )
                ),
                'taxonomy'  => WPMF_TAXO
            );
            $folders = get_terms($args);
            // if folder not exists
            if (empty($folders)) {
                $inserted = wp_insert_term(
                    basename($datas['key']),
                    WPMF_TAXO,
                    array(
                        'parent' => $parent,
                        'slug' => sanitize_title($datas['key']) . WPMF_TAXO
                    )
                );
                $parent = $inserted['term_id'];
            } else {
                $parent = (int)$folders[0]->term_id;
            }

            update_term_meta($parent, 'wpmf_s3_import_key', $import_key);
            $wpmfQueue = JuMainQueue::getInstance('wpmf');
            $wpmfQueue->updateQueueTermMeta((int)$parent, (int)$element_id);
        } else {
            global $wpdb;
            // check attachment imported
            $exist = $wpdb->get_var($wpdb->prepare('SELECT COUNT(meta_id) FROM ' . $wpdb->postmeta . ' WHERE meta_key = "wpmf_s3_import_key" AND meta_value = %s', array($import_key)));
            if (empty($exist)) {
                // don't upload to S3
                remove_action('add_attachment', array($this, 'addAttachment'));
                $upload_dir = wp_upload_dir();
                $aws3 = new WpmfAddonAWS3($datas['region']);
                $upload_dir = wp_upload_dir();
                if (file_exists($upload_dir['path'] . '/' . basename($datas['key']))) {
                    $file   = wp_unique_filename($upload_dir['path'], basename($datas['key']));
                } else {
                    $file = basename($datas['key']);
                }

                $file_path = $upload_dir['path'] . '/' . $file;
                if (file_exists($file_path)) {
                    return true;
                }
                $file_url = $upload_dir['url'] . '/' . $file;
                $path_parts = pathinfo($file_path);
                $info_file  = wp_check_filetype($file_path);
                // download attachment
                try {
                    if (isset($datas['action']) && $datas['action'] === 'wpmf_google_cloud_storage_import') {
                        $config = get_option('_wpmfAddon_google_cloud_storage_config', true);
                        $client = $this->getGoogleClient($config);
                        $service = new WpmfGoogle_Service_Storage($client);
                        $file = $service->objects->get($datas['bucket'], $datas['key']);
                        $content = file_get_contents($file->getMediaLink());
                        file_put_contents($file_path, $content);
                    } else {
                        $aws3->getObject(array(
                            'Bucket' => $datas['bucket'],
                            'Key'    => $datas['key'],
                            'SaveAs' => $file_path
                        ));
                    }

                    // insert attachment to media library
                    $attachment = array(
                        'guid' => $file_url,
                        'post_mime_type' => $info_file['type'],
                        'post_title'     => $path_parts['filename'],
                        'post_status'    => 'inherit'
                    );
                    // Insert attachment
                    $attach_id   = wp_insert_attachment($attachment, $file_path);
                    // set attachment to term
                    wp_set_object_terms((int) $attach_id, (int) $parent, WPMF_TAXO, false);
                    $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    update_post_meta($attach_id, 'wpmf_s3_import_key', $import_key);
                    $wpmfQueue = JuMainQueue::getInstance('wpmf');
                    $wpmfQueue->updateQueuePostMeta((int)$attach_id, (int)$element_id);
                    return true;
                } catch (Exception $e) {
                    return false;
                }
            }
        }

        if ($datas['type'] === 'folder') {
            // update parent in array
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'ju_queue',
                array(
                    'responses' => stripslashes(json_encode(array('parent' => $parent)))
                ),
                array('responses' => stripslashes('{"parent":"' . $datas['bucket'] . '-' . $datas['key'] . '"}')),
                array('%s'),
                array('%s')
            );
        }
        return true;
    }

    /**
     * List all objects from Bucket
     *
     * @return void
     */
    public function listAllObjectsFromBucket()
    {
        if (empty($_POST['wpmf_nonce'])
            || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
            die();
        }

        set_time_limit(0);
        if (isset($_POST['region'])) {
            $region = $_POST['region'];
        } else {
            $aws3 = new WpmfAddonAWS3();
            $region = $aws3->getBucketLocation(
                array('Bucket' => $_POST['bucket'])
            );
        }

        $aws3config = getOffloadOption();
        $aws3config['bucket'] = $_POST['bucket'];
        $aws3config['region'] = $region;
        if (isset($aws3config['bucket'])) {
            $arrs = $this->getAllImportObjects($aws3config);
            $list = $arrs['list'];
            $term_root_id = $arrs['term_root_id'];
            $objests_list = $this->extractListObjectsFromPath($list, $term_root_id);
            $cloud_name = (isset($_POST['cloud']) && $_POST['cloud'] !== 'aws3') ? $_POST['cloud'] : 's3';
            ksort($objests_list);
            foreach ($objests_list as $objest) {
                $datas = array(
                    'key' => $objest['key'],
                    'type' => $objest['type'],
                    'parent' => $objest['parent'],
                    'bucket' => $_POST['bucket'],
                    'region' => $region,
                    'action' => 'wpmf_'. $cloud_name .'_import',
                );

                $responses = array(
                    'parent' => $_POST['bucket'] . '-' . $objest['parent']
                );
                $wpmfQueue = JuMainQueue::getInstance('wpmf');
                $row = $wpmfQueue->checkQueueExist(json_encode($datas));
                if (!$row) {
                    $wpmfQueue->addToQueue($datas, $responses);
                }
            }
            wp_send_json(array('status' => true, 'region' => $region));
        }

        wp_send_json(array('status' => false));
    }

    /**
     * Extract list objects from path list
     *
     * @param array   $arrs         Origin list objects
     * @param integer $term_root_id ID of bucket folder on media library
     * @param array   $new_list     New list objects
     *
     * @return array
     */
    public function extractListObjectsFromPath($arrs, $term_root_id = 0, $new_list = array())
    {
        foreach ($arrs as $k => $arr) {
            $parent = dirname($arr['key']);
            if (!isset($new_list[$arr['key']])) {
                $new_list[$arr['key']] = $arr;
            }
            unset($arrs[$k]);
            if ($parent !== '.') {
                if (!isset($arrs[$parent . '/'])) {
                    $arrs[$parent . '/'] = array(
                        'key' => $parent . '/',
                        'type' => 'folder',
                        'parent' => (dirname($parent) !== '.') ? dirname($parent) . '/' : (int) $term_root_id,
                    );
                    if (strpos($k, '***' . $parent) !== false) {
                        unset($arrs[$k]);
                    }
                }
            }
        }
        if (!empty($arrs)) {
            $new_list = $this->extractListObjectsFromPath($arrs, $term_root_id, $new_list);
        }
        return $new_list;
    }

    /**
     * Get all objects
     *
     * @param string $endpoint Cloud type
     * @param array  $config   Config
     *
     * @return array|mixed
     */
    public function doGetAllObjects($endpoint, $config)
    {
        if ($endpoint === 'google_cloud_storage') {
            $client = $this->getGoogleClient($config);
            $service = new WpmfGoogle_Service_Storage($client);
            $allObjects = $service->objects->listObjects($config['bucket']);
            $objects = $allObjects->getItems();
        } else {
            $aws3 = new WpmfAddonAWS3($config['region']);
            $objects = $aws3->getFoldersFilesFromBucket(array('Bucket' => $config['bucket']));
        }

        return $objects;
    }

    /**
     * List all objects from Bucket
     *
     * @param array $config Options
     *
     * @return array
     */
    public function getAllImportObjects($config)
    {
        $cloud_endpoint = get_option('wpmf_cloud_endpoint');
        $objects = $this->doGetAllObjects($cloud_endpoint, $config);

        if (empty($objects)) {
            wp_send_json(array('status' => false));
        }

        $arrs = array();
        $term_root_id = 0;
        foreach ($objects as $object) {
            if ($cloud_endpoint === 'google_cloud_storage') {
                $key = $object->getName();
            } else {
                $key = $object['Key'];
            }
            $info = pathinfo($key);
            $parent = dirname($key) . '/';
            if ($parent === './') {
                $inserted = wp_insert_term($config['bucket'], WPMF_TAXO, array('parent' => 0));
                if (is_wp_error($inserted)) {
                    if (isset($inserted->error_data) && isset($inserted->error_data['term_exists'])) {
                        $parent = $inserted->error_data['term_exists'];
                        $term_root_id = $inserted->error_data['term_exists'];
                    }
                } else {
                    $parent = $inserted['term_id'];
                    $term_root_id = $inserted['term_id'];
                }
            }

            if (empty($info['extension'])) {
                $arrs[$key . '***' . $parent] = array(
                    'key' => $key,
                    'type' => 'folder',
                    'parent' => $parent
                );
            } else {
                $arrs[$key . '***' . $parent] = array(
                    'key' => $key,
                    'type' => 'file',
                    'parent' => $parent
                );
            }
        }
        ksort($arrs);
        return array('list' => $arrs, 'term_root_id' => $term_root_id);
    }

    /**
     * Remove local file
     *
     * @return void
     */
    public function removeLocalFile()
    {
        if (empty($_POST['wpmf_nonce'])
            || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
            die();
        }

        $configs = getOffloadOption();
        if (empty($configs['remove_files_from_server'])) {
            wp_send_json(array('status' => false));
        }

        if (empty($_POST['ids'])) {
            wp_send_json(array('status' => false));
        }

        $ids = explode(',', $_POST['ids']);
        foreach ($ids as $id) {
            $this->doRemoveLocalFile($id);
        }

        wp_send_json(array('status' => true));
    }

    /**
     * Ajax upload single file to S3
     *
     * @return void
     */
    public function ajaxUploadSingleFileToS3()
    {
        if (empty($_POST['wpmf_nonce'])
            || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
            die();
        }

        if (empty($_POST['ids'])) {
            wp_send_json(array('status' => false));
        }
        $cloud_endpoint = get_option('wpmf_cloud_endpoint');
        if (empty($cloud_endpoint)) {
            $cloud_endpoint = 's3';
        }
        $configs = getOffloadOption();
        $remove = (!empty($configs['remove_files_from_server'])) ? 1: 0;
        $ids = explode(',', $_POST['ids']);
        $success_ids = array();
        foreach ($ids as $id) {
            $infos = get_post_meta((int) $id, 'wpmf_awsS3_info', true);
            if (!empty($infos)) {
                continue;
            }

            $aws3       = new WpmfAddonAWS3();
            $return = $this->uploadSingleFileToS3((int) $id, $aws3, $cloud_endpoint);
            if ($return['status']) {
                $success_ids[] = (int) $id;
            }
        }

        if (empty($success_ids)) {
            wp_send_json(array('status' => false));
        }

        wp_send_json(array('status' => true, 'remove' => $remove, 'ids' => implode(',', $success_ids)));
    }

    /**
     * List all objects from Bucket
     *
     * @param array $config Options
     *
     * @return array
     */
    public function getAllCopyObjects($config)
    {
        $cloud_endpoint = get_option('wpmf_cloud_endpoint');
        $objects = $this->doGetAllObjects($cloud_endpoint, $config);
        if (empty($objects)) {
            wp_send_json(array('status' => false));
        }

        $arrs = array();
        foreach ($objects as $object) {
            if ($cloud_endpoint === 'google_cloud_storage') {
                $key = $object->getName();
            } else {
                $key = $object['Key'];
            }

            $info = pathinfo($key);
            if (empty($info['extension'])) {
                $arrs[$key] = array(
                    'key' => $key,
                    'type' => 'folder'
                );
            } else {
                $arrs[$key] = array(
                    'key' => $key,
                    'type' => 'file'
                );
            }
        }
        ksort($arrs);
        return $arrs;
    }

    /**
     * List all copy objects
     *
     * @return void
     */
    public function listAllCopyObjects()
    {
        if (empty($_POST['wpmf_nonce'])
            || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
            die();
        }

        set_time_limit(0);
        $aws3config = getOffloadOption();
        $cloud_endpoint = get_option('wpmf_cloud_endpoint');
        if ($cloud_endpoint === 'google_cloud_storage') {
            $from_region = '';
            $to_region = '';
        } else {
            $aws3config['bucket'] = $_POST['from_bucket'];
            $aws3 = new WpmfAddonAWS3();
            if (isset($_POST['from_region'])) {
                $from_region = $_POST['from_region'];
            } else {
                $from_region = $aws3->getBucketLocation(
                    array('Bucket' => $_POST['from_bucket'])
                );
            }

            if (isset($_POST['to_region'])) {
                $to_region = $_POST['to_region'];
            } else {
                $to_region = $aws3->getBucketLocation(
                    array('Bucket' => $_POST['to_bucket'])
                );
            }

            $aws3config['region'] = $from_region;
        }

        $arrs = $this->getAllCopyObjects($aws3config);
        update_option('wpmf_'. $cloud_endpoint .'_copy_list', $arrs);
        wp_send_json(array('status' => true, 'to_region' => $to_region));
    }

    /**
     * Ajax copy object
     *
     * @return void
     */
    public function ajaxCopyObject()
    {
        if (empty($_POST['wpmf_nonce'])
            || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
            die();
        }

        set_time_limit(0);
        $from_bucket = $_POST['from_bucket'];
        $to_bucket = $_POST['to_bucket'];

        $cloud_endpoint = get_option('wpmf_cloud_endpoint');
        if ($cloud_endpoint !== 'google_cloud_storage') {
            $aws3 = new WpmfAddonAWS3($_POST['region']);
        }

        $arrs = get_option('wpmf_'. $cloud_endpoint .'_copy_list', true);
        if (empty($arrs)) {
            wp_send_json(array('status' => true, 'continue' => false));
        }

        // get first element
        $copys = array_slice($arrs, 0, 5);
        foreach ($copys as $key => $copy) {
            try {
                if ($cloud_endpoint !== 'google_cloud_storage') {
                    $result = $aws3->copyObject(array(
                        'ACL'          => 'public-read',
                        'Bucket' => $to_bucket,
                        'CopySource' => $from_bucket . '/' . urlencode($copy['key']),
                        'Key' => $copy['key'],
                        'MetadataDirective' => 'COPY'
                    ));
                } else {
                    $config = get_option('_wpmfAddon_google_cloud_storage_config', true);
                    $client = $this->getGoogleClient($config);
                    $service = new WpmfGoogle_Service_Storage($client);
                    $obj = new WpmfGoogle_Service_Storage_StorageObject();
                    $obj->setBucket($to_bucket);
                    $obj->setName($copy['key']);
                    $obj->setAcl('public-read');
                    $file = $service->objects->copy($from_bucket, $copy['key'], $to_bucket, $copy['key'], $obj);

                    // public object
                    $acl = new WpmfGoogle_Service_Storage_ObjectAccessControl();
                    $acl->setEntity('allUsers');
                    $acl->setRole('READER');
                    $acl->setBucket($to_bucket);
                    $acl->setObject($copy['key']);
                    $response = $service->objectAccessControls->insert($to_bucket, $copy['key'], $acl);
                }

                unset($arrs[$key]);
            } catch (Exception $e) {
                unset($arrs[$key]);
            }
        }

        update_option('wpmf_'. $cloud_endpoint .'_copy_list', $arrs);
        wp_send_json(array('status' => true, 'continue' => true));
    }

    /**
     * Save storage
     *
     * @return void
     */
    public function saveStorage()
    {
        if (empty($_POST['wpmf_nonce'])
            || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
            wp_send_json(array('status' => false));
        }

        $storage = (isset($_POST['storage'])) ? $_POST['storage'] : '';
        if ($storage !== '') {
            update_option('wpmf_cloud_endpoint', $storage);
            wp_send_json(array('status' => true));
        }

        wp_send_json(array('status' => false));
    }

    /**
     * Filters the attachment data prepared for JavaScript.
     * Base on /wp-includes/media.php
     *
     * @param array          $response   Array of prepared attachment data.
     * @param integer|object $attachment Attachment ID or object.
     * @param array          $meta       Array of attachment meta data.
     *
     * @return mixed $response
     */
    public function wpPrepareAttachmentForJs($response, $attachment, $meta)
    {
        $infos = get_post_meta($attachment->ID, 'wpmf_awsS3_info', true);
        if (empty($infos)) {
            return $response;
        }

        $response['aws3_infos'] = $infos;
        return $response;
    }

    /**
     * Alter the image meta data to add srcset support for object versioned S3 URLs
     *
     * @param array   $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
     * @param array   $size_array    Array of width and height values in pixels (in that order).
     * @param string  $image_src     The 'src' of the image.
     * @param integer $attachment_id The image attachment ID to pass to the filter
     *
     * @return array
     */
    public function wpCalculateImageSrcsetMeta($image_meta, $size_array, $image_src, $attachment_id)
    {
        if (empty($image_meta['file'])) {
            return $image_meta;
        }

        if (false !== strpos($image_src, $image_meta['file'])) {
            return $image_meta;
        }

        //  return if not on s3
        $infos = get_post_meta($attachment_id, 'wpmf_awsS3_info', true);
        if (empty($infos)) {
            return $image_meta;
        }

        $image_meta['file'] = rawurlencode(wp_basename($image_meta['file']));
        if (!empty($image_meta['sizes'])) {
            $image_meta['sizes'] = array_map(function ($size) {
                $size['file'] = rawurlencode($size['file']);
                return $size;
            }, $image_meta['sizes']);
        }

        return $image_meta;
    }

    /**
     * Replace local URLs with S3 ones for srcset image sources
     *
     * @param array   $srcs          Source
     * @param array   $size_array    Array of width and height values in pixels (in that order).
     * @param string  $image_src     The 'src' of the image.
     * @param array   $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
     * @param integer $attachment_id The image attachment ID to pass to the filter
     *
     * @return array
     */
    public function wpCalculateImageSrcset($srcs, $size_array, $image_src, $image_meta, $attachment_id)
    {
        if (!is_array($srcs)) {
            return $srcs;
        }

        //  return if not on s3
        $infos = get_post_meta($attachment_id, 'wpmf_awsS3_info', true);
        if (empty($infos)) {
            return $srcs;
        }

        foreach ($srcs as $width => $source) {
            $size = $this->getImageSizeByWidth($image_meta['sizes'], $width, wp_basename($source['url']));
            if (!empty($size)) {
                $url                 = wp_get_attachment_image_src($attachment_id, $size);
                $srcs[$width]['url'] = $url[0];
            } else {
                $url                 = wp_get_attachment_url($attachment_id);
                $srcs[$width]['url'] = $url;
            }
        }

        return $srcs;
    }

    /**
     * Helper function to find size name from width and filename
     *
     * @param array  $sizes    List sizes
     * @param string $width    Width
     * @param string $filename File name
     *
     * @return null|string
     */
    public function getImageSizeByWidth($sizes, $width, $filename)
    {
        foreach ($sizes as $size_name => $size) {
            if ($width === (int) $size['width'] && $filename === $size['file']) {
                return $size_name;
            }
        }

        return null;
    }

    /**
     * Check if the plugin need to run an update of db or options
     *
     * @return void
     */
    public function runUpgrades()
    {
        $version = get_option('wpmf_addon_version', '1.0.0');
        // Up to date, nothing to do
        if ($version === WPMFAD_VERSION) {
            return;
        }

        if (version_compare($version, '2.2.0', '<')) {
            global $wpdb;
            $wpdb->query('CREATE TABLE `' . $wpdb->prefix . 'wpmf_s3_queue` (
                      `id` int(11) NOT NULL,
                      `post_id` int(11) NOT NULL,
                      `destination` text NOT NULL,
                      `date_added` varchar(14) NOT NULL,
                      `date_done` varchar(14) DEFAULT NULL,
                      `status` tinyint(1) NOT NULL
                    ) ENGINE=InnoDB');

            $wpdb->query('ALTER TABLE `' . $wpdb->prefix . 'wpmf_s3_queue`
                          ADD UNIQUE KEY `id` (`id`),
                          ADD KEY `date_added` (`date_added`,`status`);');

            $wpdb->query('ALTER TABLE `' . $wpdb->prefix . 'wpmf_s3_queue`
                          MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');
        }

        // Set default options values
        $options = get_option('wp-media-folder-addon-tables');
        if (!$options) {
            add_option(
                'wp-media-folder-addon-tables',
                array(
                    'wp_posts' => array(
                        'post_content' => 1,
                        'post_excerpt' => 1
                    )
                )
            );
        }
        update_option('wpmf_addon_version', WPMFAD_VERSION);
    }

    /**
     * Includes styles and some scripts
     *
     * @return void
     */
    public function loadAdminScripts()
    {
        global $current_screen;
        if (!empty($current_screen->base) && $current_screen->base === 'settings_page_option-folder') {
            wp_enqueue_style(
                'wpmf-magnific-popup',
                WPMF_PLUGIN_URL . '/assets/css/display-gallery/magnific-popup.css',
                array(),
                '0.9.9'
            );

            wp_enqueue_script(
                'wpmf-magnific-popup',
                WPMF_PLUGIN_URL. '/assets/js/display-gallery/jquery.magnific-popup.min.js',
                array('jquery'),
                '0.9.9',
                true
            );

            wp_enqueue_script(
                'wpmf-circle-progress',
                plugins_url('assets/js/circle-progress.js', dirname(__FILE__)),
                array('jquery'),
                WPMFAD_VERSION
            );

            wp_enqueue_script(
                'wpmf-aws3-option',
                plugins_url('/assets/js/aws3-option.js', dirname(__FILE__)),
                array('jquery', 'wpmf-script-option', 'wpmf-magnific-popup', 'wpmf-circle-progress'),
                WPMFAD_VERSION
            );

            wp_localize_script('wpmf-aws3-option', 'wpmfS3', array(
                'l18n' => array(
                    'select_region_alert'  => esc_html__('Please select a region.', 'wpmfAddon'),
                    'copy_bucket_success'  => esc_html__('The file has been copied.', 'wpmfAddon'),
                    'import_s3_to_library'  => esc_html__('Importing Amazon S3 files to media', 'wpmfAddon'),
                    'confirm_delete_bucket'   => __('Do you really want to delete?', 'wpmfAddon'),
                    'bucket_selected'  => esc_html__('Selected bucket', 'wpmfAddon'),
                    'choose_bucket'  => esc_html__('Choose a bucket', 'wpmfAddon'),
                    'sync_process_text' => esc_html__('Syncronization on the way, please wait', 'wpmfAddon'),
                    'bucket_select'    => esc_html__('Select bucket', 'wpmfAddon'),
                    'no_upload_s3_msg' => esc_html__('Please enable (Copy to Amazon S3) option', 'wpmfAddon'),
                    'sync_btn_text' => esc_html__('Synchronize with Amazon S3', 'wpmfAddon'),
                    'upload_to_s3' => esc_html__('Uploading the files to S3...', 'wpmfAddon'),
                    'download_from_s3' => esc_html__('Downloading the files from S3...', 'wpmfAddon'),
                    'update_local_url' => esc_html__('Updating content...', 'wpmfAddon'),
                    'delete_local_files' => esc_html__('Deleting the files on server...', 'wpmfAddon'),
                    'dialog_label' => esc_html__('Infomation', 'wpmfAddon'),
                    'choose_bucket_copy' => esc_html__('You need choose copy bucket and destination bucket', 'wpmfAddon'),
                    'queue_import_alert' => __('Media will be imported asynchronously in backgound', 'wpmfAddon'),
                    'bucket_error_msg' => __('Names must be lowercase and start with a letter or number. They can be between 3 and 63 characters long and may contain dashes', 'wpmfAddon'),
                    'bucket_error_msg1' => __('Names must not contain spaces.', 'wpmfAddon')
                ),
                'vars' => array(
                    'wpmf_nonce' => wp_create_nonce('wpmf_nonce'),
                )
            ));
        }
    }

    /**
     * Get S3 complete percent
     *
     * @return array
     */
    public function getS3CompletePercent()
    {
        global $wpdb;
        $all_attachments    = $wpdb->get_var('SELECT COUNT(ID) FROM ' . $wpdb->posts . ' WHERE post_type = "attachment" AND post_status != "trash"');
        $all_cloud_attachments = $wpdb->get_var('SELECT COUNT(ID) FROM ' . $wpdb->posts . ' as p INNER JOIN ' . $wpdb->postmeta . ' as pm ON p.ID = pm.post_id WHERE post_type = "attachment" AND pm.meta_key = "wpmf_drive_id" AND pm.meta_value != ""');
        $count_attachment    = $all_attachments - $all_cloud_attachments;
        $count_attachment_s3 = $wpdb->get_var('SELECT COUNT(ID) FROM ' . $wpdb->posts . ' as p INNER JOIN ' . $wpdb->postmeta . ' as pm ON p.ID = pm.post_id WHERE p.post_type = "attachment" AND pm.meta_key = "wpmf_awsS3_info" AND pm.meta_value !=""');
        if ($count_attachment_s3 >= $count_attachment) {
            $s3_percent = 100;
        } else {
            if ((int) $count_attachment === 0) {
                $s3_percent = 0;
            } else {
                $s3_percent = ceil($count_attachment_s3 / $count_attachment * 100);
            }
        }

        $local_files_count = $all_attachments - $all_cloud_attachments - $count_attachment_s3;
        return array('local_files_count' => $local_files_count, 's3_percent' => (int) $s3_percent);
    }

    /**
     * Update new URL attachment in database
     *
     * @param integer $post_id     Attachment ID
     * @param string  $file_path   Files path
     * @param string  $destination Destination
     * @param boolean $retrieve    Retrieve
     * @param array   $tables      All tables in database
     * @param string  $cloud_name  Cloud name
     *
     * @return void
     */
    public function updateAttachmentUrlToDatabase($post_id, $file_path, $destination, $retrieve, $tables, $cloud_name = 's3')
    {
        global $wpdb;
        $infos = get_post_meta($post_id, 'wpmf_awsS3_info', true);
        if (empty($infos)) {
            return;
        }

        $meta   = get_post_meta($post_id, '_wp_attachment_metadata', true);
        // get attachted file
        if (!empty($meta) && !empty($meta['file'])) {
            $attached_file = $meta['file'];
        } else {
            $attached_file = get_post_meta($post_id, '_wp_attached_file', true);
        }

        $old_url = str_replace(
            str_replace('\\', '/', get_home_path()),
            str_replace('\\', '/', home_url()) . '/',
            str_replace('\\', '/', $file_path)
        );

        $new_url = str_replace(rtrim(home_url(), '/'), $destination, $old_url);
        $new_url = urldecode(WpmfAddonHelper::encodeFilename($new_url));

        if ($retrieve) {
            $search_url = $new_url;
            $replace_url = $old_url;
        } else {
            $search_url = $old_url;
            $replace_url = $new_url;
        }

        if ($search_url === '' || $replace_url === '') {
            return;
        }

        // ===========================
        foreach ($tables as $table => &$columns) {
            if (!count($columns)) {
                continue;
            }

            // Get the primary key of the table
            $key = $wpdb->get_row('SHOW KEYS FROM  ' . esc_sql($table) . ' WHERE Key_name = "PRIMARY"');

            // No primary key, we can't do anything in this table
            if ($key === null) {
                continue;
            }

            $key = $key->Column_name;

            $count_records = $wpdb->get_var('SELECT COUNT(' . esc_sql($key) . ') FROM ' . esc_sql($table));
            $limit = 5000;
            $total_pages = ceil($count_records/$limit);
            for ($i = 1; $i <= $total_pages; $i++) {
                $datas = array(
                    'table' => $table,
                    'columns' => $columns,
                    'page' => (int)$i,
                    'limit' => (int)$limit,
                    'key' => $key,
                    'search_url' => $search_url,
                    'replace_url' => $replace_url,
                    'attached_file' => $attached_file,
                    'action' => 'wpmf_replace_'. $cloud_name .'_url_by_page'
                );
                $wpmfQueue = JuMainQueue::getInstance('wpmf');
                $row = $wpmfQueue->checkQueueExist(json_encode($datas));
                if (!$row) {
                    $wpmfQueue->addToQueue($datas);
                }
            }
        }
    }

    /**
     * Replace S3 URL in database by page
     *
     * @param boolean $result     Result
     * @param array   $datas      Data details
     * @param integer $element_id ID of queue element
     *
     * @return boolean
     */
    public function updateAttachmentUrlToDatabaseByPage($result, $datas, $element_id)
    {
        $return = WpmfAddonHelper::updateAttachmentUrlToDatabaseByPage($result, $datas, $element_id);
        return $return;
    }

    /**
     * Get Google Client
     *
     * @param array $config Google Client config
     *
     * @return Google_Client
     */
    public function getGoogleClient($config)
    {
        $config = get_option('_wpmfAddon_google_cloud_storage_config', true);
        $client                 = new WpmfGoogle_Client();
        $client->setClientId($config['credentials']['key']);
        $client->setClientSecret($config['credentials']['secret']);
        $client->setAccessType('offline');
        if (!empty($config['googleCredentials'])) {
            $client->setAccessToken($config['googleCredentials']);
            if ($client->isAccessTokenExpired()) {
                $token = json_decode($config['googleCredentials'], true);
                $client->refreshToken($token['refresh_token']);
                $token = $client->getAccessToken();
                $client->setAccessToken($token);
                $new_config = get_option('_wpmfAddon_google_cloud_storage_config');
                $new_config['googleCredentials'] = $token;
                update_option('_wpmfAddon_google_cloud_storage_config', $new_config);
            }
        }
        return $client;
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
        $connect    = false;
        $s3_percent = $this->getS3CompletePercent();
        $allow_syncs3_extensions = wpmfGetOption('allow_syncs3_extensions');
        try {
            // get selected cloud endpoint
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
            if (isset($_GET['cloud'])) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
                $cloud_endpoint = $_GET['cloud'];
            } else {
                $cloud_endpoint = get_option('wpmf_cloud_endpoint');
                if (empty($cloud_endpoint)) {
                    $cloud_endpoint = 'aws3';
                }
            }

            $aws3config = getOffloadOption($cloud_endpoint);
            if (is_array($aws3config)) {
                $aws3config = array_merge($this->aws3_config_default, $aws3config);
            } else {
                $aws3config = $this->aws3_config_default;
            }

            $region = isset($aws3config['region']) ? $aws3config['region'] : '';
            $aws3 = new WpmfAddonAWS3($region);
            if (isset($_POST['btn_wpmf_save'])) {
                if (empty($_POST['wpmf_nonce'])
                    || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
                    die();
                }
                if (!empty($_POST['aws3_config'])) {
                    if (defined('WPMF_AWS3_SETTINGS')) {
                        unset($_POST['aws3_config']['credentials']);
                    }
                    
                    if (isset($_POST['wpmf_cloud_endpoint'])) {
                        $cloud_endpoint = $_POST['wpmf_cloud_endpoint'];
                    } else {
                        $cloud_endpoint = get_option('wpmf_cloud_endpoint');
                        if (empty($cloud_endpoint)) {
                            $cloud_endpoint = 'aws3';
                        }
                    }

                    $oldConfigs = getOffloadOption($cloud_endpoint);
                    if (empty($oldConfigs)) {
                        $oldConfigs = array();
                    }

                    $requestConfigs = $_POST['aws3_config'];
                    $newConfigs = array_merge($oldConfigs, $requestConfigs);
                    update_option('_wpmfAddon_'. $cloud_endpoint .'_config', $newConfigs);
                    $aws3config = getOffloadOption($cloud_endpoint);
                }

                if (isset($_POST['wpmf_cloud_endpoint'])) {
                    update_option('wpmf_cloud_endpoint', $_POST['wpmf_cloud_endpoint']);
                }

                $aws3 = new WpmfAddonAWS3($region);
            }

            // get all buckets
            $location_name = '';
            if (!empty($aws3config['credentials']['key']) && !empty($aws3config['credentials']['secret'])) {
                if ($cloud_endpoint === 'google_cloud_storage') {
                    $config = get_option('_wpmfAddon_google_cloud_storage_config', true);
                    $client = $this->getGoogleClient($config);
                    $service     = new WpmfGoogle_Service_Storage($client);
                    $list_buckets = array('Buckets' => array());
                    if (!empty($config['credentials']['project_id'])) {
                        try {
                            $listbuckets = $service->buckets->listBuckets($config['credentials']['project_id']);
                            $allbuckets = $listbuckets->getItems();

                            if (!empty($allbuckets)) {
                                foreach ($allbuckets as $bk) {
                                    $list_buckets['Buckets'][] = array(
                                        'Name' => $bk->id,
                                        'CreationDate' => $bk->timeCreated,
                                        'region' => strtolower($bk->getLocation())
                                    );
                                }
                            }
                        } catch (Exception $e) {
                            $msg = $e->getMessage();
                        }
                    }
                } else {
                    $list_buckets = $aws3->listBuckets();
                }

                if (!empty($aws3config['bucket'])) {
                    if (isset($aws3->regions[strtolower($aws3config['region'])])) {
                        $location_name = $aws3->regions[strtolower($aws3config['region'])];
                    }
                }

                $connect = true;
            }

            if (empty($aws3config['region'])) {
                $firstValue = reset($aws3->regions);
                $firstKey = key($aws3->regions);
                $aws3config['region'] = $firstKey;
            }

            $copy_files_to_bucket     = $aws3config['copy_files_to_bucket'];
            $remove_files_from_server = $aws3config['remove_files_from_server'];
            $attachment_label         = $aws3config['attachment_label'];
        } catch (Exception $e) {
            $connect = false;
            $msg     = $e->getMessage();
        }

        ob_start();
        require_once 'templates/settings_aws3.php';
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * Add the S3 meta box to the attachment screen
     *
     * @return void
     */
    public function attachmentMetaBox()
    {
        add_meta_box(
            's3-actions',
            __('Amazon Infos', 'wpmfAddon'),
            array($this, 'metaBox'),
            'attachment',
            'side',
            'core'
        );
    }

    /**
     * Render the S3 attachment meta box
     *
     * @return void
     */
    public function metaBox()
    {
        require_once 'templates/attachment-metabox.php';
    }

    /**
     * Upload attachment to s3
     *
     * @param object|boolean $aws3    S3 class object
     * @param integer        $post_id Attachment ID
     * @param array          $data    Attachment meta data
     *
     * @return array
     */
    public function doUploadToS3($aws3, $post_id, $data)
    {
        $parent_path = $this->getFolderS3Path($post_id);
        $file_paths = WpmfAddonHelper::getAttachmentFilePaths($post_id, $data);
        $infos = get_post_meta($post_id, 'wpmf_awsS3_info', true);
        if (!empty($infos)) {
            include_once 'includes/mime-types.php';
            foreach ($file_paths as $size => $file_path) {
                if (!file_exists($file_path)) {
                    continue;
                }

                try {
                    if ($aws3) {
                        $aws3->uploadObject(
                            array(
                                'ACL'          => 'public-read',
                                'Bucket'       => $this->aws3_settings['bucket'],
                                'Key'          => $parent_path . basename($file_path),
                                'SourceFile'   => $file_path,
                                'ContentType'  => get_post_mime_type($post_id),
                                'CacheControl' => 'max-age=31536000',
                                'Expires'      => date('D, d M Y H:i:s O', time() + 31536000),
                                'Metadata'     => array(
                                    'attachment_id' => $post_id,
                                    'size'          => $size
                                )
                            )
                        );
                    } else {
                        $config = get_option('_wpmfAddon_google_cloud_storage_config', true);
                        $client = $this->getGoogleClient($config);
                        $service     = new WpmfGoogle_Service_Storage($client);
                        $obj = new WpmfGoogle_Service_Storage_StorageObject();
                        $obj->setName($parent_path . basename($file_path));

                        $infofile = pathinfo($file_path);
                        $contenType = 'application/octet-stream';
                        if (isset($infofile['extension'])) {
                            $contenType = getMimeType($infofile['extension']);
                        }

                        $obj->setContentType($contenType);
                        $obj->setAcl('public-read');
                        $service->objects->insert(
                            $config['bucket'],
                            $obj,
                            array('name' => $parent_path . basename($file_path), 'data' => file_get_contents($file_path),'uploadType' => 'media', 'mimeType' => $contenType)
                        );

                        // public object
                        $acl = new WpmfGoogle_Service_Storage_ObjectAccessControl();
                        $acl->setEntity('allUsers');
                        $acl->setRole('READER');
                        $acl->setBucket($config['bucket']);
                        $acl->setObject($parent_path . basename($file_path));
                        $response = $service->objectAccessControls->insert($config['bucket'], $parent_path . basename($file_path), $acl);
                    }
                } catch (Exception $e) {
                    $res = array('status' => false, 'msg' => esc_html($e->getMessage()));
                    return $res;
                }
            }
        }

        $res = array('status' => true);
        return $res;
    }

    /**
     * Add a file to the queue
     *
     * @param integer $post_id     Attachment id
     * @param string  $destination Destination
     * @param string  $status      Status
     *
     * @return void
     */
    public function addToQueue($post_id, $destination, $status = 0)
    {
        global $wpdb;
        $check = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'wpmf_s3_queue WHERE post_id=%d', array($post_id)));
        if (empty($check)) {
            $wpdb->insert(
                $wpdb->prefix . 'wpmf_s3_queue',
                array(
                    'post_id'     => $post_id,
                    'date_added'  => round(microtime(true) * 1000),
                    'destination' => WpmfAddonHelper::encodeFilename($destination),
                    'date_done'   => null,
                    'status'      => $status
                ),
                array(
                    '%d',
                    '%d',
                    '%s',
                    '%d',
                    '%d'
                )
            );
        }
    }

    /**
     * Update attachment metadata
     *
     * @param array   $data    Meta data
     * @param integer $post_id Attachment ID
     *
     * @return array
     */
    public function wpUpdateAttachmentMetadata($data, $post_id)
    {
        if (is_null($data)) {
            $data = wp_get_attachment_metadata($post_id, true);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No action, nonce is not required
        if (!empty($_POST['wpmf_folder'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No action, nonce is not required
            $folder_id = (int)$_POST['wpmf_folder'];
            $cloud_id = wpmfGetCloudFolderID($folder_id);
            if ($cloud_id) {
                return $data;
            }
        }

        $infos      = get_post_meta($post_id, 'wpmf_awsS3_info', true);
        if (empty($infos)) {
            return $data;
        }

        $cloud_endpoint = get_option('wpmf_cloud_endpoint');
        if ($cloud_endpoint === 'google_cloud_storage') {
            $return = $this->doUploadToS3(false, $post_id, $data);
        } else {
            $aws3 = new WpmfAddonAWS3();
            $return = $this->doUploadToS3($aws3, $post_id, $data);
        }

        if ($return['status']) {
            global $wpdb;
            $link = $this->wpGetAttachmentUrl('', $post_id);
            $wpdb->update(
                $wpdb->posts,
                array(
                    'guid' => $link
                ),
                array('ID' => $post_id),
                array(
                    '%s'
                ),
                array('%d')
            );
        }

        $configs = getOffloadOption();
        if (!empty($configs['remove_files_from_server'])) {
            // check plugin shortpixel active
            if (is_plugin_active('shortpixel-image-optimiser/wp-shortpixel.php')) {
                $op = get_option('wp-short-create-webp');
                $op1 = get_option('wp-short-pixel-auto-media-library');
                if (!empty($op) && !empty($op1)) {
                    return $data;
                }
            }
            $this->doRemoveLocalFile($post_id);
        }
        return $data;
    }

    /**
     * Add attachment to cloud
     *
     * @param integer $attachment_id Attachment ID
     *
     * @return void
     */
    public function addAttachment($attachment_id)
    {
        $path = get_attached_file($attachment_id);
        if (file_exists($path)) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No action, nonce is not required
            if (!empty($_POST['wpmf_folder'])) {
                // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No action, nonce is not required
                $folder_id = (int)$_POST['wpmf_folder'];
                $cloud_id = wpmfGetCloudFolderID($folder_id);
                if (!$cloud_id) {
                    if (!empty($this->aws3_settings['bucket'])) {
                        $this->addMetaInfo($attachment_id, 1);
                    }
                }
            } else {
                if (!empty($this->aws3_settings['bucket'])) {
                    $this->addMetaInfo($attachment_id, 1);
                }
            }
        }
    }

    /**
     * Add meta info
     *
     * @param integer $attachment_id Attachment ID
     * @param integer $status        Status
     *
     * @return void
     */
    public function addMetaInfo($attachment_id, $status = 0)
    {
        $parent_path = $this->getFolderS3Path($attachment_id);
        $file_path = get_attached_file($attachment_id);
        $cloud_endpoint = get_option('wpmf_cloud_endpoint');
        if (empty($cloud_endpoint)) {
            $cloud_endpoint = 'aws3';
        }

        update_post_meta($attachment_id, 'wpmf_awsS3_info', array(
            'Acl'    => 'public-read',
            'Region' => $this->aws3_settings['region'],
            'Bucket' => $this->aws3_settings['bucket'],
            'Key'    => $parent_path . basename($file_path),
            'endpoint' => $cloud_endpoint
        ));

        $destination = $this->getDestination($attachment_id);
        if ($destination) {
            $this->addToQueue($attachment_id, $destination, $status);
        }
    }

    /**
     * Update File Size
     *
     * @param integer $post_id Attachment ID
     *
     * @return void
     */
    public function updateFileSize($post_id)
    {
        $meta      = get_post_meta($post_id, '_wp_attachment_metadata', true);
        $file_path = get_attached_file($post_id, true);
        if (file_exists($file_path)) {
            $filesize  = filesize($file_path);
            if ($filesize > 0) {
                $meta['filesize'] = $filesize;
                update_post_meta($post_id, '_wp_attachment_metadata', $meta);
            }
        }
    }

    /**
     * Remove local file by ID
     *
     * @param integer $id ID of file
     *
     * @return void
     */
    public function doRemoveLocalFile($id)
    {
        $configs = getOffloadOption();
        if (empty($configs['remove_files_from_server'])) {
            return;
        }

        $datas = array(
            'attachment_id' => $id,
            'action' => 'wpmf_s3_remove_local_file'
        );

        $wpmfQueue = JuMainQueue::getInstance('wpmf');
        $wpmfQueue->addToQueue($datas);
    }

    /**
     * Delete Attachment
     *
     * @param integer $post_id Attachment ID
     *
     * @return void
     */
    public function deleteAttachment($post_id)
    {
        $infos = get_post_meta($post_id, 'wpmf_awsS3_info', true);
        global $wpdb;
        // delete in wpmf_s3_queue table
        $wpdb->delete($wpdb->prefix . 'wpmf_s3_queue', array('post_id' => $post_id), array('%d'));
        if (!empty($infos)) {
            try {
                set_time_limit(0);
                // delete on s3 server
                if (isset($infos['endpoint']) && $infos['endpoint'] !== 'google_cloud_storage') {
                    $aws3 = new WpmfAddonAWS3();
                }

                $file_paths = WpmfAddonHelper::getAttachmentFilePaths($post_id);
                /**
                 * Delete Attachment from Amazon S3
                 *
                 * @param integer Attachment ID
                 * @param string  Bucket
                 * @param string  Key
                 */
                do_action('wpmf_s3_delete_attachment', $post_id, $infos['Bucket'], $infos['Key']);

                if (isset($infos['endpoint']) && $infos['endpoint'] === 'google_cloud_storage') {
                    $config = get_option('_wpmfAddon_google_cloud_storage_config', true);
                    $client = $this->getGoogleClient($config);
                    $service = new WpmfGoogle_Service_Storage($client);
                }
                foreach ($file_paths as $size => $file_path) {
                    $infofile = pathinfo($file_path);
                    $webp_path = str_replace($infofile['extension'], 'webp', $file_path);
                    if (isset($infos['endpoint']) && $infos['endpoint'] === 'google_cloud_storage') {
                        $service->objects->delete(
                            $infos['Bucket'],
                            dirname($infos['Key']) . '/' . basename($file_path)
                        );

                        $service->objects->delete(
                            $infos['Bucket'],
                            dirname($infos['Key']) . '/' . basename($webp_path)
                        );
                    } else {
                        $aws3->deleteObject(
                            array(
                                'Bucket' => $infos['Bucket'],
                                'Key'    => dirname($infos['Key']) . '/' . basename($file_path)
                            )
                        );

                        $aws3->deleteObject(
                            array(
                                'Bucket' => $infos['Bucket'],
                                'Key'    => dirname($infos['Key']) . '/' . basename($webp_path)
                            )
                        );
                    }
                }
            } catch (Exception $e) {
                echo esc_html($e->getMessage());
            }
        }
    }

    /**
     * Get folder breadcrumb
     *
     * @param integer $post_id Attachment ID
     *
     * @return string
     */
    public function getFolderS3Path($post_id)
    {
        $attached  = get_attached_file($post_id);
        $attached  = str_replace('\\', '/', $attached);
        $attached  = str_replace(basename($attached), '', $attached);
        $home_path = str_replace('\\', '/', ABSPATH);
        $path      = str_replace($home_path, '', $attached);
        $path      = str_replace('//', '', $path);
        $configs = getOffloadOption();
        $root_folder_name = (isset($configs['root_folder_name'])) ? $configs['root_folder_name'] : 'wp-media-folder-' . sanitize_title(get_bloginfo('name'));
        /**
         * Filter change root folder name for Amazon S3 when upload or sync from Media Library to Amazon S3
         *
         * @param string  $root_folder_name
         *
         * @return string
         */
        $root_folder_name = apply_filters('wpmf_amazons3_root_foldername', $root_folder_name);
        return $root_folder_name . '/' . trim($path, '/') . '/';
    }

    /**
     * Get folder breadcrumb
     *
     * @param integer $id     Folder id
     * @param integer $parent Folder parent
     * @param string  $string Current breadcrumb
     *
     * @return string
     */
    public function getCategoryDir($id, $parent, $string)
    {
        if (!empty($parent)) {
            $term   = get_term($parent, WPMF_TAXO);
            $string = $this->getCategoryDir($id, $term->parent, $term->name . '/' . $string);
        }

        return $string;
    }

    /**
     * Create a bucket
     *
     * @return void
     */
    public function createBucket()
    {
        if (empty($_POST['wpmf_nonce'])
            || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
            die();
        }

        if (isset($_POST['name']) && $_POST['name'] !== '') {
            $name = trim($_POST['name']);
            if (strlen($name) < 3 || strlen($name) > 63) {
                wp_send_json(array(
                    'status' => false,
                    'msg'    => esc_attr__('Names must be between 3 and 63 characters long', 'wpmfAddon')
                ));
            }
            $args = array('Bucket' => $name);
            if (isset($_POST['region'])) {
                if (isset($_POST['endpoint']) && $_POST['endpoint'] === 'linode') {
                    $args['CreateBucketConfiguration'] = array('LocationConstraint' => 'default');
                } else {
                    $args['CreateBucketConfiguration'] = array('LocationConstraint' => $_POST['region']);
                }
            }

            try {
                $aws3 = new WpmfAddonAWS3($_POST['region']);
                if (isset($_POST['endpoint']) && $_POST['endpoint'] === 'google_cloud_storage') {
                    try {
                        $config = get_option('_wpmfAddon_google_cloud_storage_config', true);
                        $client = $this->getGoogleClient($config);
                        $service     = new WpmfGoogle_Service_Storage($client);
                        $storage_bucket_service = new WpmfGoogle_Service_Storage_Bucket();
                        $storage_bucket_service->setName($name);
                        $storage_bucket_service->setLocation($_POST['region']);
                        $service->buckets->insert($config['credentials']['project_id'], $storage_bucket_service);
                    } catch (Exception $e) {
                        $errors = $e->getErrors();
                        wp_send_json(array(
                            'status' => false,
                            'msg'    => esc_html($errors[0]['message'])
                        ));
                    }
                } else {
                    $aws3->createBucket($args);
                }

                // select bucket after create
                $aws3config = getOffloadOption();
                if (is_array($aws3config)) {
                    $aws3config['bucket'] = $name;
                    $aws3config['region'] = $_POST['region'];
                    $cloud_endpoint = get_option('wpmf_cloud_endpoint');
                    if (empty($cloud_endpoint)) {
                        $cloud_endpoint = 'aws3';
                    }
                    update_option('_wpmfAddon_'. $cloud_endpoint .'_config', $aws3config);
                }

                $location_name = $aws3->regions[$_POST['region']];
                wp_send_json(array('status' => true, 'msg' => esc_html__('Created bucket success!', 'wpmfAddon'), 'location_name' => $location_name));
            } catch (S3Exception $e) {
                wp_send_json(array(
                    'status' => false,
                    'msg'    => esc_html($e->getAwsErrorMessage())
                ));
            }
        }
    }

    /**
     * Delete a bucket
     *
     * @return void
     */
    public function deleteBucket()
    {
        if (empty($_POST['wpmf_nonce'])
            || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
            die();
        }

        if (isset($_POST['name']) && $_POST['name'] !== '') {
            $name = $_POST['name'];
            try {
                $aws3   = new WpmfAddonAWS3();
                $region = (isset($_POST['region'])) ? $_POST['region'] : '';
                $cloud_endpoint = get_option('wpmf_cloud_endpoint');
                if (empty($cloud_endpoint)) {
                    $cloud_endpoint = 'aws3';
                }

                if ($cloud_endpoint === 'google_cloud_storage') {
                    $config = get_option('_wpmfAddon_google_cloud_storage_config', true);
                    $client = $this->getGoogleClient($config);
                    $service     = new WpmfGoogle_Service_Storage($client);
                    $service->buckets->delete($name);
                } else {
                    if ($region === '' && $cloud_endpoint !== 'digitalocean' && $cloud_endpoint !== 'linode') {
                        $region = $aws3->getBucketLocation(
                            array('Bucket' => $name)
                        );
                    }


                    $args   = getOffloadOption();
                    if ($region !== $args['region']) {
                        $aws3 = new WpmfAddonAWS3($region);
                    }

                    $list_objects = $aws3->listObjects(array('Bucket' => $name));
                    if (!empty($list_objects['Contents'])) {
                        foreach ($list_objects['Contents'] as $list_object) {
                            $aws3->deleteObject(array(
                                'Bucket' => $name,
                                'Key'    => $list_object['Key']
                            ));
                        }
                    }

                    $result = $aws3->deleteBucket(array(
                        'Bucket' => $name
                    ));
                }

                wp_send_json(array('status' => true));
            } catch (Exception $e) {
                wp_send_json(array('status' => false, 'msg' => esc_html($e->getMessage())));
            }
        }
        wp_send_json(array('status' => false, 'msg' => esc_html__('Delete failed!', 'wpmfAddon')));
    }

    /**
     * Select a bucket
     *
     * @return void
     */
    public function selectBucket()
    {
        if (empty($_POST['wpmf_nonce'])
            || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
            die();
        }

        $region = isset($_POST['region']) ? $_POST['region'] : '';
        $cloud_endpoint = get_option('wpmf_cloud_endpoint');
        if (empty($cloud_endpoint)) {
            $cloud_endpoint = 'aws3';
        }

        $endpoint = isset($_POST['endpoint']) ? $_POST['endpoint'] : '';
        $aws3config = get_option('_wpmfAddon_'. $endpoint .'_config');
        $aws3       = new WpmfAddonAWS3($region);
        if ($cloud_endpoint !== 'google_cloud_storage') {
            if ($region === '' && $cloud_endpoint !== 'digitalocean' && $cloud_endpoint !== 'linode') {
                $region = $aws3->getBucketLocation(
                    array('Bucket' => $_POST['bucket'])
                );
                $region_lb = $aws3->regions[$aws3config['region']];
            }
        } else {
            $config = get_option('_wpmfAddon_google_cloud_storage_config', true);
            $client = $this->getGoogleClient($config);
            $service     = new WpmfGoogle_Service_Storage($client);
            //$storage_bucket_service = new WpmfGoogle_Service_Storage_Bucket();
            $bucket_detail = $service->buckets->get($_POST['bucket']);
            $region = $bucket_detail->getLocation();
            $region_lb = $aws3->regions[strtolower($region)];
        }

        if (is_array($aws3config)) {
            $aws3config['bucket'] = $_POST['bucket'];
            $aws3config['region'] = $region;
            if (!empty($endpoint)) {
                update_option('_wpmfAddon_'. $endpoint .'_config', $aws3config);
                wp_send_json(array(
                    'status' => true,
                    'bucket' => $aws3config['bucket'],
                    'region' => $region_lb
                ));
            }
        }

        wp_send_json(array('status' => false, 'msg' => esc_html__('Select bucket failed!', 'wpmfAddon')));
    }

    /**
     * Get buckets list
     *
     * @return void
     */
    public function getBucketsByRegion()
    {
        if (empty($_POST['wpmf_nonce'])
            || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
            die();
        }

        $region = isset($_POST['region']) ? $_POST['region'] : '';
        $aws3         = new WpmfAddonAWS3($region);
        $list_buckets = $aws3->listBuckets();
        wp_send_json(array('status' => true, 'buckets' => $list_buckets['Buckets']));
    }

    /**
     * Get buckets list
     *
     * @return void
     */
    public function getBucketsList()
    {
        if (empty($_POST['wpmf_nonce'])
            || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
            die();
        }

        $region = isset($_POST['region']) ? $_POST['region'] : '';
        $cloud_endpoint = get_option('wpmf_cloud_endpoint');
        if ($cloud_endpoint === 'google_cloud_storage') {
            $config = get_option('_wpmfAddon_google_cloud_storage_config', true);
            $client = $this->getGoogleClient($config);
            $service     = new WpmfGoogle_Service_Storage($client);
            $list_buckets = array('Buckets' => array());
            if (!empty($config['credentials']['project_id'])) {
                try {
                    $listbuckets = $service->buckets->listBuckets($config['credentials']['project_id']);
                    $allbuckets = $listbuckets->getItems();

                    if (!empty($allbuckets)) {
                        foreach ($allbuckets as $bk) {
                            $list_buckets['Buckets'][] = array(
                                'Name' => $bk->id,
                                'CreationDate' => $bk->timeCreated,
                                'region' => strtolower($bk->getLocation())
                            );
                        }
                    }
                } catch (Exception $e) {
                    $msg = $e->getMessage();
                }
            }
        } else {
            $aws3         = new WpmfAddonAWS3($region);
            $list_buckets = $aws3->listBuckets();
        }

        $aws3config   = getOffloadOption();
        $html         = '';
        if (!empty($list_buckets['Buckets'])) {
            foreach ($list_buckets['Buckets'] as $bucket) {
                if (isset($aws3config['bucket']) && $aws3config['bucket'] === $bucket['Name']) {
                    $html .= '<tr class="row_bucket bucket-selected" data-region="'. esc_attr($region) .'" data-bucket="' . esc_attr($bucket['Name']) . '">';
                } else {
                    $html .= '<tr class="row_bucket aws3-select-bucket" data-region="'. esc_attr($region) .'" data-bucket="' . esc_attr($bucket['Name']) . '">';
                }

                $html .= '<td style="width: 30%">' . esc_html($bucket['Name']) . '</td>';
                $html .= '<td style="width: 30%">' . esc_html($bucket['CreationDate']) . '</td>';
                if (isset($aws3config['bucket']) && $aws3config['bucket'] === $bucket['Name']) {
                    $html .= '<td style="width: 30%"><label class="btn-select-bucket">' . esc_html__('Selected bucket', 'wpmfAddon') . '</label></td>';
                } else {
                    $html .= '<td style="width: 30%"><label class="btn-select-bucket">' . esc_html__('Select bucket', 'wpmfAddon') . '</label></td>';
                }
                $html .= '<td style="width: 10%"><a class="delete-bucket wpmfqtip" data-alt="' . esc_html__('Delete bucket', 'wpmfAddon') . '" data-bucket="' . esc_attr($bucket['Name']) . '"><i class="material-icons"> delete_outline </i></a></td>';
                $html .= '</tr>';
            }
        }

        wp_send_json(array('status' => true, 'html' => $html, 'buckets' => $list_buckets['Buckets']));
    }

    /**
     * Update S3 URL to local URL
     *
     * @param integer|boolean $result     Result
     * @param array           $datas      QUeue datas
     * @param integer         $element_id Queue ID
     *
     * @return boolean
     */
    public function replaceLocalUrlS3($result, $datas, $element_id)
    {
        if (isset($datas['attachment_id'])) {
            try {
                $file_paths = get_post_meta($datas['attachment_id'], 'wpmf_origin_file_paths', true);
                // get tables
                $tables = WpmfAddonHelper::getDefaultDbColumns();
                foreach ($file_paths as $size => $file_path) {
                    $this->updateAttachmentUrlToDatabase((int)$datas['attachment_id'], $file_path, $datas['destination'], true, $tables, $datas['cloud_name']);
                }

                // Update queue meta
                $wpmfQueue = JuMainQueue::getInstance('wpmf');
                $wpmfQueue->updateQueuePostMeta((int)$datas['attachment_id'], (int)$element_id);
                return true;
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * Download attachment from s3 s3
     *
     * @return void
     */
    public function downloadObject()
    {
        if (empty($_POST['wpmf_nonce'])
            || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
            die();
        }

        /**
         * Filter check capability of current user to regenerate image thumbnail
         *
         * @param boolean The current user has the given capability
         * @param string  Action name
         *
         * @return boolean
         *
         * @ignore Hook already documented
         */
        $wpmf_capability = apply_filters('wpmf_user_can', current_user_can('manage_options'), 'download_object');
        if (!$wpmf_capability) {
            wp_send_json(array('status' => false, 'msg' => 'You not have permission!', 'wpmfAddon'));
        }

        set_time_limit(0);
        global $wpdb;
        $cloud_endpoint = get_option('wpmf_cloud_endpoint');
        $query = new WP_Query(array(
            'posts_per_page' => 1,
            'post_type' => 'attachment',
            'post_status' => 'any',
            'orderby' => 'ID',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'wpmf_drive_id',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => 'wpmf_awsS3_info',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => 'wpmf_awsS3_info',
                    'value' => $cloud_endpoint,
                    'compare' => 'LIKE'
                )
            )
        ));

        $attachments = $query->get_posts();
        $count = count($attachments);
        // return if empty local file
        if ($count === 0) {
            wp_send_json(array('status' => true, 'continue' => false));
        }

        $attachment_id = $attachments[0]->ID;
        try {
            $aws3 = new WpmfAddonAWS3();
            $infos = get_post_meta($attachment_id, 'wpmf_awsS3_info', true);
            $file_paths = WpmfAddonHelper::getAttachmentFilePaths($attachment_id);
            $cloud_name = (isset($_POST['cloud']) && $_POST['cloud'] !== 'aws3') ? $_POST['cloud'] : 's3';
            foreach ($file_paths as $file_path) {
                if (file_exists($file_path)) {
                    continue;
                }

                if (!file_exists(dirname($file_path))) {
                    mkdir(dirname($file_path), 0777, true);
                }

                if (isset($infos['endpoint']) && $infos['endpoint'] === 'google_cloud_storage') {
                    $config = get_option('_wpmfAddon_google_cloud_storage_config', true);
                    $client = $this->getGoogleClient($config);
                    $service = new WpmfGoogle_Service_Storage($client);
                    $file = $service->objects->get($infos['Bucket'], $infos['Key']);
                    $content = file_get_contents($file->getMediaLink());
                    file_put_contents($file_path, $content);
                } else {
                    $aws3->getObject(array(
                        'Bucket' => $infos['Bucket'],
                        'Key' => dirname($infos['Key']) . '/' . basename($file_path),
                        'SaveAs' => $file_path
                    ));
                }
            }

            $destination = $this->getDestination($attachment_id);
            // add to queue, use to replace URL in database
            $datas = array(
                'attachment_id' => $attachment_id,
                'action' => 'wpmf_s3_replace_urls3',
                'cloud_name' => $cloud_name,
                'destination' => $destination
            );

            $wpmfQueue = JuMainQueue::getInstance('wpmf');
            $wpmfQueue->addToQueue($datas);
            // delete meta info
            delete_post_meta($attachment_id, 'wpmf_awsS3_info');
            $count = $wpdb->get_var('SELECT COUNT(id) FROM ' . $wpdb->prefix . 'wpmf_s3_queue WHERE status = 1 OR status = 0');
            $count1 = $wpdb->get_var('SELECT COUNT(id) FROM ' . $wpdb->prefix . 'wpmf_s3_queue WHERE status = 0');
            $percent = ($count1 / $count) * 100;
            sleep(0.5);
            wp_send_json(array('status' => true, 'continue' => true, 'percent' => $percent));
        } catch (Exception $e) {
            delete_post_meta($attachment_id, 'wpmf_awsS3_info');
            $count = $wpdb->get_var('SELECT COUNT(id) FROM ' . $wpdb->prefix . 'wpmf_s3_queue WHERE status = 1 OR status = 0');
            $count1 = $wpdb->get_var('SELECT COUNT(id) FROM ' . $wpdb->prefix . 'wpmf_s3_queue WHERE status = 0');
            $percent = ($count1 / $count) * 100;
            sleep(0.5);
            wp_send_json(array('status' => true, 'continue' => true, 'percent' => $percent));
        }
    }

    /**
     * Update status for file
     *
     * @param integer $fileID File ID
     * @param integer $status Status
     *
     * @return void
     */
    public function updateStatusS3($fileID, $status)
    {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'wpmf_s3_queue',
            array(
                'status'    => $status
            ),
            array('id' => $fileID),
            array(
                '%d',
            ),
            array('%d')
        );
    }

    /**
     * Upload single file to S3
     *
     * @param integer $attachment_id Attachment ID
     * @param object  $aws3          WpmfAddonAWS3 class
     * @param string  $cloud_name    Cloud name
     *
     * @return array
     */
    public function uploadSingleFileToS3($attachment_id, $aws3, $cloud_name = 's3')
    {
        $data = wp_get_attachment_metadata($attachment_id, true);
        // do upload to s3
        $this->addMetaInfo($attachment_id);
        $cloud_endpoint = get_option('wpmf_cloud_endpoint');
        if ($cloud_endpoint === 'google_cloud_storage') {
            $return = $this->doUploadToS3(false, $attachment_id, $data);
        } else {
            $return = $this->doUploadToS3($aws3, $attachment_id, $data);
        }
        if (isset($return['status']) && $return['status']) {
            global $wpdb;
            // update status s3 queue
            $wpdb->update(
                $wpdb->prefix . 'wpmf_s3_queue',
                array(
                    'status'    => 1,
                    'date_done' => round(microtime(true) * 1000)
                ),
                array('post_id' => $attachment_id),
                array(
                    '%d',
                    '%d'
                ),
                array('%d')
            );

            // store origin file paths to meta
            $meta       = get_post_meta($attachment_id, '_wp_attachment_metadata', true);
            $file_paths = WpmfAddonHelper::getAttachmentFilePaths($attachment_id, $meta);
            update_post_meta($attachment_id, 'wpmf_origin_file_paths', $file_paths);

            // remove local file
            $configs = getOffloadOption();
            if (!empty($configs['remove_files_from_server'])) {
                $this->s3RemoveLocalFile(
                    true,
                    array(
                        'attachment_id' => $attachment_id,
                        'action' => 'wpmf_s3_remove_local_file'
                    ),
                    ''
                );
            }

            // get destination
            $destination = $this->getDestination($attachment_id);
            // add to queue, use to replace URL in database
            $datas = array(
                'attachment_id' => $attachment_id,
                'action' => 'wpmf_s3_replace_local',
                'cloud_name' => $cloud_name,
                'destination' => $destination
            );
            $wpmfQueue = JuMainQueue::getInstance('wpmf');
            $row = $wpmfQueue->checkQueueExist(json_encode($datas));
            if (!$row) {
                $wpmfQueue->addToQueue($datas);
            }
        }
        return $return;
    }

    /**
     * Get destination
     *
     * @param integer $attachment_id Attachment ID
     *
     * @return boolean|string
     */
    public function getDestination($attachment_id)
    {
        $destination = false;
        $infos = get_post_meta($attachment_id, 'wpmf_awsS3_info', true);
        $configs = getOffloadOption();
        if (isset($infos['endpoint'])) {
            $cloud_endpoint = $infos['endpoint'];
        } else {
            $cloud_endpoint = 'aws3';
        }
        $root_folder_name = (isset($configs['root_folder_name'])) ? $configs['root_folder_name'] : 'wp-media-folder-' . sanitize_title(get_bloginfo('name'));
        /**
         * Filter change root folder name for Amazon S3 when upload or sync from Media Library to Amazon S3
         *
         * @param string  $root_folder_name
         *
         * @return string
         *
         * @ignore Hook already documented
         */
        $root_folder_name = apply_filters('wpmf_amazons3_root_foldername', $root_folder_name);
        if (!empty($infos)) {
            switch ($cloud_endpoint) {
                case 'amazonaws':
                    if (isset($infos['Region']) && $infos['Region'] !== 'us-east-1') {
                        $destination = 'https://s3-' . $infos['Region'] . '.amazonaws.com/' . $infos['Bucket'] . '/' . $root_folder_name;
                    } else {
                        $destination = 'https://s3.amazonaws.com/' . $infos['Bucket'] . '/'. $root_folder_name;
                    }
                    break;
                case 'wasabi':
                    if (isset($infos['Region']) && $infos['Region'] !== 'us-east-1') {
                        $destination = 'https://s3.' . $infos['Region'] . '.wasabisys.com/' . $infos['Bucket'] . '/' . $root_folder_name;
                    } else {
                        $destination = 'https://s3.wasabisys.com/' . $infos['Bucket'] . '/'. $root_folder_name;
                    }
                    break;
                case 'digitalocean':
                    $destination = 'https://' . $infos['Bucket'] . '.' . $infos['Region'] . '.digitaloceanspaces.com/' . $root_folder_name;
                    break;
                case 'google_cloud_storage':
                    $destination = 'https://storage.googleapis.com/'. $infos['Bucket'] . '/' . $root_folder_name;
                    break;
                case 'linode':
                    $destination = 'https://' . $infos['Bucket'] . '.' . $infos['Region'] . '.linodeobjects.com/' . $root_folder_name;
                    break;
            }
        }

        return $destination;
    }

    /**
     * Sync media library with s3
     *
     * @return void
     */
    public function uploadToS3()
    {
        if (empty($_POST['wpmf_nonce'])
            || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
            die();
        }

        /**
         * Filter check capability of current user to regenerate image thumbnail
         *
         * @param boolean The current user has the given capability
         * @param string  Action name
         *
         * @return boolean
         *
         * @ignore Hook already documented
         */
        $wpmf_capability = apply_filters('wpmf_user_can', current_user_can('manage_options'), 'upload_to_s3');
        if (!$wpmf_capability) {
            wp_send_json(
                array(
                    'status' => false,
                    'msg'    => esc_html__('Permission defined!', 'wpmfAddon')
                )
            );
        }

        $aws3config = getOffloadOption();
        if (empty($aws3config['copy_files_to_bucket'])) {
            wp_send_json(
                array(
                    'status' => false,
                    'msg'    => esc_html__('Please enable (Copy to Amazon S3) option', 'wpmfAddon')
                )
            );
        }

        if (empty($aws3config['bucket'])) {
            wp_send_json(
                array(
                    'status' => false,
                    'msg'    => esc_html__('Please select an Amazon bucket to start using S3 server', 'wpmfAddon')
                )
            );
        }

        set_time_limit(0);
        $allow_exts = wpmfGetOption('allow_syncs3_extensions');
        $allow_exts_array = explode(',', trim($allow_exts));
        $allow_exts = array();
        include_once 'includes/mime-types.php';
        foreach ($allow_exts_array as $allow_ext) {
            if ($allow_ext === '') {
                continue;
            }
            $allow_exts[] = getMimeType(strtolower($allow_ext));
        }

        if (empty($allow_exts)) {
            wp_send_json(
                array(
                    'status' => false,
                    'msg'    => esc_html__('File type to include in synchronization is empty.', 'wpmfAddon')
                )
            );
        }

        $query = new WP_Query(array(
            'posts_per_page' => 1,
            'post_type' => 'attachment',
            'post_status' => 'any',
            'orderby' => 'ID',
            'order' => 'DESC',
            'post_mime_type' => $allow_exts,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key'     => 'wpmf_drive_id',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key'     => 'wpmf_awsS3_info',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key'     => 'wpmf_s3_ignore',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));

        $attachments = $query->get_posts();
        $count = count($attachments);
        // return if empty local file
        if ($count === 0) {
            wp_send_json(array(
                'status'                   => true,
                'continue' => false,
                's3_percent'               => 100
            ));
        }

        try {
            $aws3       = new WpmfAddonAWS3();
            $s3_percent = $this->getS3CompletePercent();
            $return_filetype = '';
            $cloud_name = (isset($_POST['cloud']) && $_POST['cloud'] !== 'aws3') ? $_POST['cloud'] : 's3';
            foreach ($attachments as $attachment) {
                $file_url = wp_get_attachment_url($attachment->ID);
                $filetype = pathinfo($file_url);
                $return_filetype = $filetype['extension'];
                if (isset($filetype['extension']) && $filetype['extension']) {
                    $return = $this->uploadSingleFileToS3($attachment->ID, $aws3, $cloud_name);
                } else {
                    update_post_meta($attachment->ID, 'wpmf_s3_ignore', 1);
                }
            }

            $process_percent = 0;
            if (isset($_POST['local_files_count'])) {
                $process_percent = (1 / (int) $_POST['local_files_count']) * 100;
            }

            wp_send_json(array(
                'status'                   => true,
                'continue' => true,
                'percent'               => $process_percent,
                's3_percent' => $s3_percent['s3_percent'],
                'filetype' => $return_filetype
            ));
        } catch (Exception $e) {
            wp_send_json(
                array(
                    'status' => false,
                    'msg'    => esc_html($e->getMessage())
                )
            );
        }
    }

    /**
     * Update local URL to S3 URL
     *
     * @param integer|boolean $result     Result
     * @param array           $datas      QUeue datas
     * @param integer         $element_id Queue ID
     *
     * @return boolean
     */
    public function replaceLocalUrl($result, $datas, $element_id)
    {
        // update database
        if (isset($datas['attachment_id'])) {
            try {
                $file_paths = get_post_meta($datas['attachment_id'], 'wpmf_origin_file_paths', true);
                // get tables
                $tables = WpmfAddonHelper::getDefaultDbColumns();
                foreach ($file_paths as $size => $file_path) {
                    $this->updateAttachmentUrlToDatabase($datas['attachment_id'], $file_path, $datas['destination'], false, $tables, $datas['cloud_name']);
                }
                // Update queue meta
                $wpmfQueue = JuMainQueue::getInstance('wpmf');
                $wpmfQueue->updateQueuePostMeta((int)$datas['attachment_id'], (int)$element_id);
                return true;
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * Encode file names according to RFC 3986 when generating urls
     *
     * @param string $file File name
     *
     * @return string Encoded filename
     */
    public function encodeFilename($file)
    {
        if (!is_admin()) {
            return $file;
        }

        $url = parse_url($file);

        if (!isset($url['path'])) {
            // Can't determine path, return original
            return $file;
        }

        $file = str_replace(' ', '+', $file);
        if (isset($url['query'])) {
            // Manually strip query string, as passing $url['path'] to basename results in corrupt characters
            $file_name = wp_basename(str_replace('?' . $url['query'], '', $file));
        } else {
            $file_name = wp_basename($file);
        }

        if (false !== strpos($file_name, '%')) {
            // File name already encoded, return original
            return $file;
        }

        $encoded_file_name = rawurlencode($file_name);
        if ($file_name === $encoded_file_name) {
            // File name doesn't need encoding, return original
            return $file;
        }

        return str_replace($file_name, $encoded_file_name, $file);
    }

    /**
     * Get attachment URL
     *
     * @param string  $url     Old URL
     * @param integer $post_id Attachment ID
     *
     * @return string
     */
    public function wpGetAttachmentUrl($url, $post_id)
    {
        $infos = get_post_meta($post_id, 'wpmf_awsS3_info', true);
        $infos = apply_filters('wpmf_cloud_infos', $infos, $post_id);
        if (!empty($infos)) {
            $aws3config = getOffloadOption();
            if (isset($infos['endpoint'])) {
                $cloud_endpoint = $infos['endpoint'];
            } else {
                $cloud_endpoint = 'aws3';
            }

            $saved_cloud_endpoint = get_option('wpmf_cloud_endpoint');
            if (empty($saved_cloud_endpoint)) {
                $saved_cloud_endpoint = 'aws3';
            }

            $bucket = $infos['Bucket'];
            /**
             * Filter change the bucket for all media
             *
             * @param string  $bucket
             *
             * @return string
             */
            $bucket = apply_filters('wpmf_amazons3_bucket', $bucket);
            if (!empty($aws3config['enable_custom_domain']) && !empty($aws3config['custom_domain']) && $saved_cloud_endpoint === $cloud_endpoint) {
                $url = 'https://' . str_replace(array('http://', 'https://'), '', trim($aws3config['custom_domain'], '/') . '/' . $infos['Key']);
            } else {
                switch ($cloud_endpoint) {
                    case 'wasabi':
                        if (isset($infos['Region']) && $infos['Region'] !== 'us-east-1') {
                            $url = 'https://s3.' . $infos['Region'] . '.wasabisys.com/' . $bucket . '/' . str_replace(' ', '%20', $infos['Key']);
                        } else {
                            $url = 'https://s3.wasabisys.com/' . $bucket . '/' . str_replace(' ', '%20', $infos['Key']);
                        }
                        break;
                    case 'digitalocean':
                        $url = 'https://' . $bucket . '.' . $infos['Region'] . '.digitaloceanspaces.com/' . str_replace(' ', '%20', $infos['Key']);
                        break;
                    case 'linode':
                        $url = 'https://' . $bucket . '.' . $infos['Region'] . '.linodeobjects.com/' . str_replace(' ', '%20', $infos['Key']);
                        break;
                    case 'google_cloud_storage':
                        $url = 'https://storage.googleapis.com/'. $bucket . '/' . str_replace(' ', '%20', $infos['Key']);
                        break;
                    default:
                        if (isset($infos['Region']) && $infos['Region'] !== 'us-east-1') {
                            $url = 'https://s3-' . $infos['Region'] . '.amazonaws.com/' . $bucket . '/' . str_replace(' ', '%20', $infos['Key']);
                        } else {
                            $url = 'https://s3.amazonaws.com/' . $bucket . '/' . str_replace(' ', '%20', $infos['Key']);
                        }
                }
            }
        }

        return $url;
    }

    /**
     * Gets a specific external variable by name and optionally filters it
     *
     * @param string  $var     Variable Name
     * @param integer $type    Variable type
     * @param integer $filter  Filter
     * @param mixed   $options Options
     *
     * @return mixed
     */
    public function filterInput($var, $type = INPUT_GET, $filter = FILTER_DEFAULT, $options = array())
    {
        return filter_input($type, $var, $filter, $options);
    }

    /**
     * Is this an AJAX
     *
     * @return boolean
     */
    public function isAjax()
    {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return true;
        }

        return false;
    }
}
