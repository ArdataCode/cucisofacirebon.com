<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

use Joomunited\Queue\V1_0_0\JuMainQueue;

/**
 * Class WpmfAddonHelper
 */
class WpmfAddonHelper
{

    /**
     * Get cloud configs
     *
     * @param string $type Google photo or google drive
     *
     * @return mixed
     */
    public static function getAllCloudConfigs($type = 'google-drive')
    {
        $default = array(
            'googleClientId'     => '',
            'googleClientSecret' => ''
        );

        if ($type === 'google-drive') {
            return get_option('_wpmfAddon_cloud_config', $default);
        } else {
            return get_option('_wpmfAddon_google_photo_config', $default);
        }
    }

    /**
     * Save cloud configs
     *
     * @param array  $data Data config
     * @param string $type Google photo or google drive
     *
     * @return boolean
     */
    public static function saveCloudConfigs($data, $type = 'google-drive')
    {
        if ($type === 'google-drive') {
            $result = update_option('_wpmfAddon_cloud_config', $data);
        } else {
            $result = update_option('_wpmfAddon_google_photo_config', $data);
        }

        return $result;
    }

    /**
     * Get all cloud configs
     *
     * @return mixed
     */
    public static function getAllCloudParams()
    {
        return get_option('_wpmfAddon_cloud_category_params');
    }

    /**
     * Set cloud configs
     *
     * @param array $cloudParams Cloud params
     *
     * @return boolean
     */
    public static function setCloudConfigsParams($cloudParams)
    {
        $result = update_option('_wpmfAddon_cloud_category_params', $cloudParams);
        return $result;
    }

    /**
     * Get google drive params
     *
     * @return mixed
     */
    public static function getGoogleDriveParams()
    {
        $params = self::getAllCloudParams();
        return isset($params['googledrive']) ? $params['googledrive'] : false;
    }

    /**
     * Save Cloud configs
     *
     * @param string       $key Key
     * @param string|array $val Value
     *
     * @return void
     */
    public static function setCloudParam($key, $val)
    {
        $params       = self::getAllCloudConfigs();
        $params[$key] = $val;
        self::saveCloudConfigs($params);
    }


    /**
     * Get termID
     *
     * @param string $googleDriveId Id of folder
     *
     * @return boolean
     */
    public static function getTermIdGoogleDriveByGoogleId($googleDriveId)
    {
        $returnData   = false;
        $googleParams = self::getGoogleDriveParams();
        if ($googleParams) {
            foreach ($googleParams as $key => $val) {
                if ($val['idCloud'] === $googleDriveId) {
                    $returnData = $val['termId'];
                }
            }
        }
        return $returnData;
    }

    /**
     * Get google drive data by term id
     *
     * @param integer $termId Term id
     *
     * @return boolean
     */
    public static function getGoogleDriveIdByTermId($termId)
    {
        $returnData   = false;
        $googleParams = self::getGoogleDriveParams();
        if ($googleParams) {
            foreach ($googleParams as $key => $val) {
                if ((int) $val['termId'] === (int) $termId) {
                    $returnData = $val['idCloud'];
                }
            }
        }
        return $returnData;
    }

    /**
     * Get category id by cloud ID
     *
     * @param string $cloud_id Cloud id
     *
     * @return boolean
     */
    public static function getCatIdByCloudId($cloud_id)
    {
        $returnData   = false;
        $googleParams = self::getGoogleDriveParams();
        if ($googleParams) {
            foreach ($googleParams as $key => $val) {
                if ($val['idCloud'] === $cloud_id) {
                    $returnData = $val['termId'];
                }
            }
        }
        return $returnData;
    }

    /**
     * Get all google drive id
     *
     * @return array
     */
    public static function getAllGoogleDriveId()
    {
        $returnData   = array();
        $googleParams = self::getGoogleDriveParams();
        if ($googleParams) {
            foreach ($googleParams as $key => $val) {
                $returnData[] = $val['idCloud'];
            }
        }
        return $returnData;
    }

    /**
     * Sync interval
     *
     * @return float
     */
    public static function curSyncInterval()
    {
        //get last_log param
        $config = self::getAllCloudConfigs();
        if (isset($config['last_log']) && !empty($config['last_log'])) {
            $last_log  = $config['last_log'];
            $last_sync = (int) strtotime($last_log);
        } else {
            $last_sync = 0;
        }

        $time_new     = (int) strtotime(date('Y-m-d H:i:s'));
        $timeInterval = $time_new - $last_sync;
        $curtime      = $timeInterval / 60;

        return $curtime;
    }

    /**
     * Get extension
     *
     * @param string $file File name
     *
     * @return string
     */
    public static function getExt($file)
    {
        $dot = strrpos($file, '.') + 1;

        return substr($file, $dot);
    }

    /**
     * Strips the last extension off of a file name
     *
     * @param string $file The file name
     *
     * @return string  The file name without the extension
     */
    public static function stripExt($file)
    {
        return preg_replace('#\.[^.]*$#', '', $file);
    }

    /*----------- Dropbox -----------------*/
    /**
     * Get all dropbox configs
     *
     * @return mixed
     */
    public static function getAllDropboxConfigs()
    {
        $default = array(
            'dropboxKey'        => '',
            'dropboxSecret'     => '',
            'dropboxSyncTime'   => '5',
            'dropboxSyncMethod' => 'sync_page_curl'
        );
        return get_option('_wpmfAddon_dropbox_config', $default);
    }

    /**
     * Save dropbox config
     *
     * @param array $data Data config
     *
     * @return boolean
     */
    public static function saveDropboxConfigs($data)
    {

        $result = update_option('_wpmfAddon_dropbox_config', $data);
        return $result;
    }

    /**
     * Get dropbox config
     *
     * @param string $name Dropbox name
     *
     * @return array|null
     */
    public static function getDataConfigByDropbox($name)
    {
        $DropboxParams = array();

        if (self::getAllDropboxConfigs()) {
            foreach (self::getAllDropboxConfigs() as $key => $val) {
                if (strpos($key, 'dropbox') !== false) {
                    $DropboxParams[$key] = $val;
                }
            }
            $result = null;
            switch ($name) {
                case 'dropbox':
                    $result = $DropboxParams;
                    break;
            }
            return $result;
        }
        return null;
    }

    /**
     * Set dropbox config
     *
     * @param array $dropboxParams Params of dropbox
     *
     * @return boolean
     */
    public static function setDropboxConfigsParams($dropboxParams)
    {
        $result = update_option('_wpmfAddon_dropbox_category_params', $dropboxParams);
        return $result;
    }

    /**
     * Get dropbox params
     *
     * @return mixed
     */
    public static function getDropboxParams()
    {
        return get_option('_wpmfAddon_dropbox_category_params', array());
    }

    /**
     * Get id by termID
     *
     * @param integer $termId Folder id
     *
     * @return boolean
     */
    public static function getDropboxIdByTermId($termId)
    {
        $returnData = false;
        $dropParams = self::getDropboxParams();
        if ($dropParams && isset($dropParams[$termId])) {
            $returnData = $dropParams[$termId]['idDropbox'];
        }
        return $returnData;
    }

    /**
     * Get dropbox folder id
     *
     * @param integer $termId Folder id
     *
     * @return boolean
     */
    public static function getIdFolderByTermId($termId)
    {
        $returnData = false;
        $dropParams = self::getDropboxParams();
        if ($dropParams && isset($dropParams[$termId])) {
            $returnData = $dropParams[$termId]['id'];
        }
        return $returnData;
    }

    /**
     * Get term id by Path
     *
     * @param string $path Path
     *
     * @return boolean|integer|string
     */
    public static function getTermIdByDropboxPath($path)
    {
        $dropbox_list = self::getDropboxParams();
        $result       = false;
        $path         = strtolower($path);
        if (!empty($dropbox_list)) {
            foreach ($dropbox_list as $k => $v) {
                if (strtolower($v['idDropbox']) === $path) {
                    $result = $k;
                }
            }
        }
        return $result;
    }

    /**
     * Get path by id
     *
     * @param string $id Dropbox file id
     *
     * @return boolean
     */
    public static function getPathByDropboxId($id)
    {
        $dropbox_list = self::getDropboxParams();
        $result       = false;
        if (!empty($dropbox_list)) {
            foreach ($dropbox_list as $k => $v) {
                if ($v['id'] === $id) {
                    $result = $v['idDropbox'];
                }
            }
        }

        return $result;
    }

    /**
     * Set dropbox file infos
     *
     * @param array $params Params
     *
     * @return boolean
     */
    public static function setDropboxFileInfos($params)
    {
        $result = update_option('_wpmfAddon_dropbox_fileInfo', $params);
        return $result;
    }

    /**
     * Get dropbox infos
     *
     * @return mixed
     */
    public static function getDropboxFileInfos()
    {
        return get_option('_wpmfAddon_dropbox_fileInfo');
    }

    /**
     * Sync interval dropbox
     *
     * @return float
     */
    public static function curSyncIntervalDropbox()
    {
        //get last_log param
        $config = self::getAllDropboxConfigs();
        if (isset($config['last_log']) && !empty($config['last_log'])) {
            $last_log  = $config['last_log'];
            $last_sync = (int) strtotime($last_log);
        } else {
            $last_sync = 0;
        }

        $time_new     = (int) strtotime(date('Y-m-d H:i:s'));
        $timeInterval = $time_new - $last_sync;
        $curtime      = $timeInterval / 60;
        return $curtime;
    }

    /**
     * Transfer iptc exif to image
     *
     * @param array   $image_info           Image info
     * @param string  $destination_image    Destination image
     * @param integer $original_orientation Original orientation
     *
     * @return boolean|integer
     */
    public static function transferIptcExifToImage($image_info, $destination_image, $original_orientation)
    {
        // Check destination exists
        if (!file_exists($destination_image)) {
            return false;
        }

        // Get EXIF data from the image info, and create the IPTC segment
        $exif_data = ((is_array($image_info) && key_exists('APP1', $image_info)) ? $image_info['APP1'] : null);
        if ($exif_data) {
            // Find the image's original orientation flag, and change it to 1
            // This prevents applications and browsers re-rotating the image, when we've already performed that function
            // @TODO I'm not sure this is the best way of changing the EXIF orientation flag, and could potentially affect
            // other EXIF data
            $exif_data = str_replace(chr(dechex($original_orientation)), chr(0x1), $exif_data);

            $exif_length = strlen($exif_data) + 2;
            if ($exif_length > 0xFFFF) {
                return false;
            }

            // Construct EXIF segment
            $exif_data = chr(0xFF) . chr(0xE1) . chr(($exif_length >> 8) & 0xFF) . chr($exif_length & 0xFF) . $exif_data;
        }

        // Get IPTC data from the source image, and create the IPTC segment
        $iptc_data = ((is_array($image_info) && key_exists('APP13', $image_info)) ? $image_info['APP13'] : null);
        if ($iptc_data) {
            $iptc_length = strlen($iptc_data) + 2;
            if ($iptc_length > 0xFFFF) {
                return false;
            }

            // Construct IPTC segment
            $iptc_data = chr(0xFF) . chr(0xED) . chr(($iptc_length >> 8) & 0xFF) . chr($iptc_length & 0xFF) . $iptc_data;
        }

        // Get the contents of the destination image
        $destination_image_contents = file_get_contents($destination_image);
        if (!$destination_image_contents) {
            return false;
        }
        if (strlen($destination_image_contents) === 0) {
            return false;
        }

        // Build the EXIF and IPTC data headers
        $destination_image_contents = substr($destination_image_contents, 2);
        $portion_to_add = chr(0xFF) . chr(0xD8); // Variable accumulates new & original IPTC application segments
        $exif_added = !$exif_data;
        $iptc_added = !$iptc_data;

        while ((substr($destination_image_contents, 0, 2) & 0xFFF0) === 0xFFE0) {
            $segment_length = (substr($destination_image_contents, 2, 2) & 0xFFFF);
            $iptc_segment_number = (substr($destination_image_contents, 1, 1) & 0x0F);   // Last 4 bits of second byte is IPTC segment #
            if ($segment_length <= 2) {
                return false;
            }

            $thisexistingsegment = substr($destination_image_contents, 0, $segment_length + 2);
            if ((1 <= $iptc_segment_number) && (!$exif_added)) {
                $portion_to_add .= $exif_data;
                $exif_added = true;
                if (1 === $iptc_segment_number) {
                    $thisexistingsegment = '';
                }
            }

            if ((13 <= $iptc_segment_number) && (!$iptc_added)) {
                $portion_to_add .= $iptc_data;
                $iptc_added = true;
                if (13 === $iptc_segment_number) {
                    $thisexistingsegment = '';
                }
            }

            $portion_to_add .= $thisexistingsegment;
            $destination_image_contents = substr($destination_image_contents, $segment_length + 2);
        }

        // Write the EXIF and IPTC data to the new file
        if (!$exif_added) {
            $portion_to_add .= $exif_data;
        }
        if (!$iptc_added) {
            $portion_to_add .= $iptc_data;
        }

        $output_file = fopen($destination_image, 'w');
        if ($output_file) {
            return fwrite($output_file, $portion_to_add . $destination_image_contents);
        }

        return false;
    }

    /**
     * Fix image orientation
     *
     * @param array $file File info
     *
     * @return mixed
     */
    public static function fixImageOrientation($file)
    {
        // Check we have a file
        if (!file_exists($file['file'])) {
            return $file;
        }

        // Attempt to read EXIF data from the image
        $exif_data = wp_read_image_metadata($file['file']);
        if (!$exif_data) {
            return $file;
        }

        // Check if an orientation flag exists
        if (!isset($exif_data['orientation'])) {
            return $file;
        }

        // Check if the orientation flag matches one we're looking for
        $required_orientations = array(8, 3, 6);
        if (!in_array($exif_data['orientation'], $required_orientations)) {
            return $file;
        }

        // If here, the orientation flag matches one we're looking for
        // Load the WordPress Image Editor class
        $image = wp_get_image_editor($file['file']);
        if (is_wp_error($image)) {
            // Something went wrong - abort
            return $file;
        }

        // Store the source image EXIF and IPTC data in a variable, which we'll write
        // back to the image once its orientation has changed
        // This is required because when we save an image, it'll lose its metadata.
        $source_size = getimagesize($file['file'], $image_info);
        // Depending on the orientation flag, rotate the image
        switch ($exif_data['orientation']) {

            /**
             * Rotate 90 degrees counter-clockwise
             */
            case 8:
                $image->rotate(90);
                break;

            /**
             * Rotate 180 degrees
             */
            case 3:
                $image->rotate(180);
                break;

            /**
             * Rotate 270 degrees counter-clockwise ($image->rotate always works counter-clockwise)
             */
            case 6:
                $image->rotate(270);
                break;
        }

        // Save the image, overwriting the existing image
        // This will discard the EXIF and IPTC data
        $image->save($file['file']);

        // Finally, return the data that's expected
        return $file;
    }

    /**
     * Get file paths for all attachment versions.
     *
     * @param integer       $attachment_id Attachment ID
     * @param array|boolean $meta          Meta data
     *
     * @return array
     */
    public static function getAttachmentFilePaths($attachment_id, $meta = false)
    {
        $file_path = get_attached_file($attachment_id, true);
        $paths     = array(
            'original' => $file_path,
        );

        if (empty($meta)) {
            $meta = get_post_meta($attachment_id, '_wp_attachment_metadata', true);
        }

        if (is_wp_error($meta)) {
            return $paths;
        }

        // Get file name of original path
        $file_name = wp_basename($file_path);
        $full_urls = wp_get_attachment_image_src($attachment_id, 'full');

        if (!$full_urls) {
            return $paths;
        }

        $full_url = $full_urls[0];
        $file_name_of_full = wp_basename($full_url);

        $paths['scaled_full'] = str_replace('-scaled', '', $file_path);
        $file_name_of_full = str_replace('-scaled', '', $file_name_of_full);
        if ($file_name !== $file_name_of_full) {
            $paths['full'] = $file_path;
        }

        // If file edited, current file name might be different.
        if (isset($meta['file'])) {
            $paths['file'] = str_replace($file_name, wp_basename($meta['file']), $file_path);
        }

        // Sizes
        if (isset($meta['sizes'])) {
            foreach ($meta['sizes'] as $size => $file) {
                if (isset($file['file'])) {
                    $paths[$size] = str_replace($file_name, $file['file'], $file_path);
                }
            }
        }

        // Get backup size
        $backups = get_post_meta($attachment_id, '_wp_attachment_backup_sizes', true);
        if (is_array($backups)) {
            foreach ($backups as $size => $file) {
                if (isset($file['file'])) {
                    $paths[$size] = str_replace($file_name, $file['file'], $file_path);
                }
            }
        }

        // Remove duplicates
        $paths = array_unique($paths);
        return $paths;
    }

    /**
     * Get all text assimilated columns from database
     *
     * @param boolean $all Retrive only prefix tables or not
     *
     * @return array|null|object
     */
    public static function getDbColumns($all)
    {
        global $wpdb;
        $extra_query = '';

        // Not forced to retrieve all tables
        if (!$all) {
            $extra_query = ' AND TABLE_NAME LIKE "' . $wpdb->prefix . '%" ';
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Nothing to prepare
        return $wpdb->get_results('SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE DATA_TYPE IN ("varchar", "text", "tinytext", "mediumtext", "longtext") AND TABLE_SCHEMA = "' . DB_NAME . '" ' . $extra_query . ' ORDER BY TABLE_NAME', OBJECT);
    }

    /**
     * Get the columns that can contain images
     *
     * @return array
     */
    public static function getDefaultDbColumns()
    {
        global $wpdb;
        $columns = self::getDbColumns(false);
        $final_columns = array();

        $exclude_tables = array(
            $wpdb->prefix . 'users',
            $wpdb->prefix . 'term_taxonomy',
            $wpdb->prefix . 'term_relationships',
            $wpdb->prefix . 'terms',
            $wpdb->prefix . 'wpmf_s3_queue',
            $wpdb->prefix . 'cmplz_cookiebanners',
            $wpdb->prefix . 'cmplz_cookies',
            $wpdb->prefix . 'cmplz_services',
            $wpdb->prefix . 'cmplz_statistics',
            $wpdb->prefix . 'easy_pie_contacts',
            $wpdb->prefix . 'easy_pie_cs_subscribers',
            $wpdb->prefix . 'easy_pie_emails',
            $wpdb->prefix . 'newsletter',
            $wpdb->prefix . 'newsletter_sent',
            $wpdb->prefix . 'newsletter_stats',
            $wpdb->prefix . 'newsletter_user_logs',
            $wpdb->prefix . 'duplicator_pro_entities',
            $wpdb->prefix . 'duplicator_pro_packages',
            $wpdb->prefix . 'icl_content_status',
            $wpdb->prefix . 'icl_core_status',
            $wpdb->prefix . 'icl_flags',
            $wpdb->prefix . 'icl_languages',
            $wpdb->prefix . 'icl_languages_translations',
            $wpdb->prefix . 'icl_locale_map',
            $wpdb->prefix . 'icl_message_status',
            $wpdb->prefix . 'icl_node',
            $wpdb->prefix . 'icl_reminders',
            $wpdb->prefix . 'icl_string_positions',
            $wpdb->prefix . 'icl_string_status',
            $wpdb->prefix . 'icl_string_translations',
            $wpdb->prefix . 'icl_translate',
            $wpdb->prefix . 'icl_translate_job',
            $wpdb->prefix . 'icl_translate',
            $wpdb->prefix . 'icl_translation_status',
            $wpdb->prefix . 'icl_translate_job',
            $wpdb->prefix . 'yoast_seo_meta',
            $wpdb->prefix . 'yoast_migrations',
            $wpdb->prefix . 'yoast_primary_term',
            $wpdb->prefix . 'wpmf_queue',
            $wpdb->prefix . 'wpfd_queue',
            $wpdb->prefix . 'ju_queue',
            $wpdb->prefix . 'as3cf_items',
            $wpdb->prefix . 'actionscheduler_logs',
            $wpdb->prefix . 'actionscheduler_groups',
            $wpdb->prefix . 'actionscheduler_claims',
            $wpdb->prefix . 'actionscheduler_actions',
            $wpdb->prefix . 'popularpostssummary',
            $wpdb->prefix . 'linguise_urls',
            $wpdb->prefix . 'realmedialibrary',
            $wpdb->prefix . 'realmedialibrary_meta',
            $wpdb->prefix . 'realmedialibrary_posts',
            $wpdb->prefix . 'realmedialibrary_tmp',
            $wpdb->prefix . 'wc_webhooks',
        );
        foreach ($columns as $column) {
            if (in_array($column->TABLE_NAME, $exclude_tables)) {
                continue;
            }

            if (strpos($column->TABLE_NAME, 'woocommerce') !== false || strpos($column->TABLE_NAME, 'wptm') !== false || strpos($column->TABLE_NAME, 'wpio') !== false || strpos($column->TABLE_NAME, 'icl_') !== false) {
                continue;
            }
            $matches = array();
            preg_match('/varchar\(([0-9]+)\)/', $column->COLUMN_TYPE, $matches);

            if (count($matches) && (int) $matches[1] < 40) {
                continue;
            }

            if (!isset($final_columns[$column->TABLE_NAME])) {
                $final_columns[$column->TABLE_NAME] = array();
            }

            if ($column->TABLE_NAME === $wpdb->posts) {
                if (in_array($column->COLUMN_NAME, array('post_type', 'post_mime_type', 'pinged', 'to_ping', 'post_password', 'post_title', 'post_name', 'ping_status', 'comment_status', 'post_status', 'post_title'))) {
                    continue;
                }
            }

            if ($column->TABLE_NAME === $wpdb->postmeta) {
                if (in_array($column->COLUMN_NAME, array('meta_key'))) {
                    continue;
                }
            }

            if ($column->TABLE_NAME === $wpdb->termmeta) {
                if (in_array($column->COLUMN_NAME, array('meta_key'))) {
                    continue;
                }
            }

            if ($column->TABLE_NAME === $wpdb->options) {
                if (in_array($column->COLUMN_NAME, array('option_name'))) {
                    continue;
                }
            }

            if ($column->TABLE_NAME === $wpdb->usermeta) {
                if (in_array($column->COLUMN_NAME, array('meta_key'))) {
                    continue;
                }
            }

            if ($column->TABLE_NAME === $wpdb->commentmeta) {
                if (in_array($column->COLUMN_NAME, array('meta_key'))) {
                    continue;
                }
            }

            if ($column->TABLE_NAME === $wpdb->comments) {
                if (in_array($column->COLUMN_NAME, array('comment_author', 'comment_author_email', 'comment_author_url', 'comment_author_IP', 'comment_agent'))) {
                    continue;
                }
            }

            if ($column->TABLE_NAME === $wpdb->links) {
                if (in_array($column->COLUMN_NAME, array('link_rel', 'link_rss', 'link_name'))) {
                    continue;
                }
            }

            $final_columns[$column->TABLE_NAME][$column->COLUMN_NAME] = 1;
        }

        return $final_columns;
    }

    /**
     * Encode file names according to RFC 3986 when generating urls
     *
     * @param string $file File name
     *
     * @return string Encoded filename
     */
    public static function encodeFilename($file)
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
     * Replace cloud URL in database by page
     *
     * @param boolean $result     Result
     * @param array   $datas      Data details
     * @param integer $element_id ID of queue element
     *
     * @return boolean
     */
    public static function updateAttachmentUrlToDatabaseByPage($result, $datas, $element_id)
    {
        global $wpdb;
        $table = $datas['table'];
        $columns = $datas['columns'];
        $key = $datas['key'];
        $search_url = $datas['search_url'];
        $replace_url = $datas['replace_url'];
        $offset = ((int)$datas['page'] - 1) * (int)$datas['limit'];

        // Search for serialized strings
        $query = 'SELECT * FROM ' . esc_sql($table) . ' LIMIT ' . esc_sql($datas['limit']) . ' OFFSET ' . esc_sql($offset);

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query escaped previously
        $results = $wpdb->get_results($query, ARRAY_N);

        if (count($results)) {
            foreach ($results as $result) {
                $unserialized_var = unserialize($result[1]);
                if ($unserialized_var !== false) {
                    // Actually replace string in all available strin array and properties
                    $unserialized_var = self::replaceStringRecursive($unserialized_var, $search_url, $replace_url);
                    // Serialize it back
                    $serialized_var = serialize($unserialized_var);
                    foreach ($columns as $column => $column_value) {
                        if ($column === 'key') {
                            continue;
                        }

                        // We're sure this is a serialized value, proceed it here
                        unset($columns[$column]);
                        // Update the database with new serialized value
                        $nb_rows = $wpdb->query($wpdb->prepare(
                            'UPDATE ' . esc_sql($table) . ' SET ' . esc_sql($column) . '=%s WHERE ' . esc_sql($key) . '=%s AND meta_key NOT IN("_wp_attached_file", "_wp_attachment_metadata")',
                            array($serialized_var, $result[0])
                        ));
                    }
                }
            }
        }

        if (count($columns)) {
            $columns_query = array();

            foreach ($columns as $column => $column_value) {
                // Relative urls
                $columns_query[] = '`' . $column . '` = replace(`' . esc_sql($column) . '`, "' . esc_sql($search_url) . '", "' . esc_sql($replace_url) . '")';
            }

            $query = 'UPDATE `' . esc_sql($table) . '` SET ' . implode(',', $columns_query);

            // Ignore attachments meta column
            if ($table === $wpdb->prefix . 'postmeta') {
                $query .= ' WHERE meta_key NOT IN("_wp_attached_file", "_wp_attachment_metadata")';
            }

            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query escaped previously
            $wpdb->query($query);
        }
        return true;
    }

    /**
     * Recursively parse a variable to replace a string
     *
     * @param mixed  $var     Variable to replace string into
     * @param string $search  String to search
     * @param string $replace String to replace with
     *
     * @return mixed
     */
    public static function replaceStringRecursive($var, $search, $replace)
    {
        switch (gettype($var)) {
            case 'string':
                return str_replace($search, $replace, $var);

            case 'array':
                foreach ($var as &$property) {
                    $property = self::replaceStringRecursive($property, $search, $replace);
                }
                return $var;

            case 'object':
                foreach (get_object_vars($var) as $property_name => $property_value) {
                    $var->{$property_name} = self::replaceStringRecursive($property_value, $search, $replace);
                }
                return $var;
        }
        return '';
    }

    /**
     * Replace local url
     *
     * @param boolean $result     Result
     * @param array   $datas      Data details
     * @param integer $element_id ID of queue element
     *
     * @return boolean|integer
     */
    public function replaceLocalUrltoCloud($result, $datas, $element_id)
    {
        if (isset($datas['attachment_id'])) {
            try {
                $file_paths = get_post_meta($datas['attachment_id'], 'wpmf_origin_file_paths', true);
                // get tables
                $tables = self::getDefaultDbColumns();
                foreach ($file_paths as $size => $file_path) {
                    self::updateAttachmentUrlToDatabase((int)$datas['attachment_id'], $size, $file_path, $tables);
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
     * Update new URL attachment in database
     *
     * @param integer $post_id   Attachment ID
     * @param string  $size      Size of file
     * @param string  $file_path Files path
     * @param array   $tables    All tables in database
     *
     * @return void
     */
    public function updateAttachmentUrlToDatabase($post_id, $size, $file_path, $tables)
    {
        global $wpdb;
        $meta   = get_post_meta($post_id, '_wp_attachment_metadata', true);
        // get attachted file
        if (!empty($meta) && !empty($meta['file'])) {
            $attached_file = $meta['file'];
        } else {
            $attached_file = get_post_meta($post_id, '_wp_attached_file', true);
        }

        $search_url = str_replace(
            str_replace('\\', '/', get_home_path()),
            str_replace('\\', '/', home_url()) . '/',
            str_replace('\\', '/', $file_path)
        );

        $replace_url = '';
        if ($size === 'original') {
            $saved_link = get_post_meta($post_id, 'wpmf_drive_link', true);
            $replace_url = urldecode(self::encodeFilename($saved_link));
        } else {
            $file_drive_type = get_post_meta($post_id, 'wpmf_drive_type', true);
            $file_drive_id = get_post_meta($post_id, 'wpmf_drive_id', true);
            switch ($file_drive_type) {
                case 'onedrive':
                    list($width, $height) = getimagesize($file_path);
                    $cropsize = $width . 'x' . $height;
                    $replace_url = admin_url('admin-ajax.php') . '?action=wpmf_onedrive_download&local_id='. $post_id .'&id=' . urlencode($file_drive_id) . '&link=true&dl=0&size=' . $cropsize;
                    $replace_url = urldecode(self::encodeFilename($replace_url));
                    break;
                case 'onedrive_business':
                    list($width, $height) = getimagesize($file_path);
                    $cropsize = $width . 'x' . $height;
                    $replace_url = admin_url('admin-ajax.php') . '?action=wpmf_onedrive_business_download&local_id='. $post_id .'&id=' . urlencode($file_drive_id) . '&link=true&dl=0&size=' . $cropsize;
                    $replace_url = urldecode(self::encodeFilename($replace_url));
                    break;
                default:
                    if (isset($meta['sizes']) && isset($meta['sizes'][$size])) {
                        $replace_url = $meta['sizes'][$size]['file'];
                        $replace_url = urldecode(self::encodeFilename($replace_url));
                    }
            }
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
            $wpmfQueue = JuMainQueue::getInstance('wpmf');
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
                    'action' => 'wpmf_replace_cloud_url_by_page'
                );
                $wpmfQueue->addToQueue($datas);
            }
        }

        // remove after upload
        $datas = array(
            'file_path' => $file_path,
            'action' => 'wpmf_remove_local_file'
        );
        $wpmfQueue->addToQueue($datas);
    }
}
