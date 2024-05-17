<?php
namespace Joomunited\WPMediaFolder;

/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
/**
 * Class WpmfHelper
 * This class that holds most of the main functionality for Media Folder.
 */
class WpmfHelper
{
    /**
     * User full access ID
     *
     * @var array
     */
    public static $user_full_access_id = array();

    /**
     * Vimeo pattern
     *
     * @var string
     */
    public static $vimeo_pattern = '%^https?:\/\/(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|video\/|)(\d+)(?:$|\/|\?)(?:[?]?.*)$%im';

    /**
     * Load import Enhanced Media Library categories script
     *
     * @param array  $categories    External categories list
     * @param string $category_name Category name
     *
     * @return array
     */
    public static function loadImportExternalCatsScript($categories, $category_name = '')
    {
        $attachment_terms_order = array();
        $attachment_terms[]       = array(
            'id'        => 0,
            'label'     => esc_html__('Media Library', 'wpmf'),
            'parent_id' => 0
        );
        $attachment_terms_order[] = '0';
        foreach ($categories as $category) {
            if ((int)$category->parent === -1) {
                $parent = 0;
            } else {
                $parent = $category->parent;
            }
            $attachment_terms[$category->term_id] = array(
                'id'            => $category->term_id,
                'label'         => $category->name,
                'parent_id'     => $parent,
                'depth'         => $category->depth
            );
            $attachment_terms_order[] = $category->term_id;
        }

        if ($category_name === 'filebird') {
            $vars['filebird_categories'] = $attachment_terms;
            $vars['filebird_categories_order'] = $attachment_terms_order;
        }

        if ($category_name === 'real_media_library') {
            $vars['rml_categories'] = $attachment_terms;
            $vars['rml_categories_order'] = $attachment_terms_order;
        }

        if ($category_name === 'media_category') {
            $vars['media_category_categories'] = $attachment_terms;
            $vars['media_category_categories_order'] = $attachment_terms_order;
        }

        if ($category_name === 'media_folder') {
            $vars['mf_categories'] = $attachment_terms;
            $vars['mf_categories_order'] = $attachment_terms_order;
        }

        return $vars;
    }

    /**
     * Move file compatiple with WPML plugin
     *
     * @param integer $id               Id of attachment
     * @param integer $current_category Id of current folder
     * @param integer $id_category      Id of new folder
     *
     * @return void
     */
    public static function moveFileWpml($id, $current_category, $id_category)
    {
        if (is_plugin_active('polylang/polylang.php') || is_plugin_active('polylang-pro/polylang.php')) {
            global $polylang;
            $polylang_current = $polylang->curlang;
            foreach ($polylang->model->get_languages_list() as $language) {
                if (!empty($polylang_current) && (int) $language->term_id === (int) $polylang_current->term_id) {
                    continue;
                }
                $translation_id = $polylang->model->post->get_translation($id, $language);
                if (($translation_id) && (int) $translation_id !== (int) $id) {
                    if ($current_category !== 'no') {
                        wp_remove_object_terms(
                            (int) $translation_id,
                            (int) $current_category,
                            WPMF_TAXO
                        );
                    } else {
                        wp_set_object_terms(
                            (int) $translation_id,
                            (int) $id_category,
                            WPMF_TAXO,
                            true
                        );
                    }

                    if ($id_category !== 'no') {
                        wp_set_object_terms(
                            (int) $translation_id,
                            (int) $id_category,
                            WPMF_TAXO,
                            true
                        );

                        /**
                         * Set attachmnent folder after moving file with WPML plugin
                         *
                         * @param integer Attachment ID
                         * @param integer Target folder
                         * @param array   Extra informations
                         *
                         * @ignore Hook already documented
                         */
                        do_action('wpmf_attachment_set_folder', $translation_id, $id_category, array('trigger' => 'move_attachment'));
                    } else {
                        wp_remove_object_terms(
                            (int) $translation_id,
                            (int) $current_category,
                            WPMF_TAXO
                        );
                    }

                    // reset order of file
                    update_post_meta(
                        (int) $translation_id,
                        'wpmf_order',
                        0
                    );
                }
            }
        } elseif (defined('ICL_SITEPRESS_VERSION') && ICL_SITEPRESS_VERSION) {
            global $sitepress;
            $trid = $sitepress->get_element_trid($id, 'post_attachment');
            if ($trid) {
                $translations = $sitepress->get_element_translations($trid, 'post_attachment', true, true, true);
                foreach ($translations as $translation) {
                    if ((int) $translation->element_id !== (int) $id) {
                        if ($current_category !== 'no') {
                            wp_remove_object_terms(
                                (int) $translation->element_id,
                                (int) $current_category,
                                WPMF_TAXO
                            );
                        } else {
                            wp_set_object_terms(
                                (int) $translation->element_id,
                                (int) $id_category,
                                WPMF_TAXO,
                                true
                            );
                        }

                        if ($id_category !== 'no') {
                            wp_set_object_terms(
                                (int) $translation->element_id,
                                (int) $id_category,
                                WPMF_TAXO,
                                true
                            );

                            /**
                             * Set attachmnent folder after moving file with WPML plugin
                             *
                             * @param integer Attachment ID
                             * @param integer Target folder
                             * @param array   Extra informations
                             *
                             * @ignore Hook already documented
                             */
                            do_action('wpmf_attachment_set_folder', $translation->element_id, $id_category, array('trigger' => 'move_attachment'));
                        } else {
                            wp_remove_object_terms(
                                (int) $translation->element_id,
                                (int) $current_category,
                                WPMF_TAXO
                            );
                        }

                        // reset order of file
                        update_post_meta(
                            (int) $translation->element_id,
                            'wpmf_order',
                            0
                        );
                    }
                }
            }
        }
    }

    /**
     * Check user full access
     *
     * @return boolean
     */
    public static function checkUserFullAccess()
    {
        global $current_user;
        $wpmf_active_media = get_option('wpmf_active_media');
        $user_roles        = $current_user->roles;
        $role              = array_shift($user_roles);
        if (isset($wpmf_active_media) && (int) $wpmf_active_media === 1
            && $role !== 'administrator' && !current_user_can('administrator') && (!in_array($current_user->ID, self::$user_full_access_id) || self::$user_full_access_id === 0) && !current_user_can('wpmf_full_access')) {
            $user_full_access = false;
        } else {
            $user_full_access = true;
        }

        $user_full_access = apply_filters('wpmf_user_full_access', $user_full_access, $role);
        return $user_full_access;
    }

    /**
     * Create Pdf Thumbnail
     *
     * @param string $filepath File path
     *
     * @return void
     */
    public static function createPdfThumbnail($filepath)
    {
        $metadata       = array();
        $fallback_sizes = array(
            'thumbnail',
            'medium',
            'large',
        );

        /**
         * Filters the image sizes generated for non-image mime types.
         *
         * @param array $fallback_sizes An array of image size names.
         * @param array $metadata       Current attachment metadata.
         */
        $fallback_sizes = apply_filters('fallback_intermediate_image_sizes', $fallback_sizes, $metadata);

        $sizes                      = array();
        $_wp_additional_image_sizes = wp_get_additional_image_sizes();

        foreach ($fallback_sizes as $s) {
            if (isset($_wp_additional_image_sizes[$s]['width'])) {
                $sizes[$s]['width'] = intval($_wp_additional_image_sizes[$s]['width']);
            } else {
                $sizes[$s]['width'] = get_option($s . '_size_w');
            }

            if (isset($_wp_additional_image_sizes[$s]['height'])) {
                $sizes[$s]['height'] = intval($_wp_additional_image_sizes[$s]['height']);
            } else {
                $sizes[$s]['height'] = get_option($s . '_size_h');
            }

            if (isset($_wp_additional_image_sizes[$s]['crop'])) {
                $sizes[$s]['crop'] = $_wp_additional_image_sizes[$s]['crop'];
            } else {
                // Force thumbnails to be soft crops.
                if ('thumbnail' !== $s) {
                    $sizes[$s]['crop'] = get_option($s . '_crop');
                }
            }
        }

        // Only load PDFs in an image editor if we're processing sizes.
        if (!empty($sizes)) {
            $editor = wp_get_image_editor($filepath);

            if (!is_wp_error($editor)) { // No support for this type of file
                /*
                 * PDFs may have the same file filename as JPEGs.
                 * Ensure the PDF preview image does not overwrite any JPEG images that already exist.
                 */
                $dirname      = dirname($filepath) . '/';
                $ext          = '.' . pathinfo($filepath, PATHINFO_EXTENSION);
                $preview_file = $dirname . wp_unique_filename($dirname, wp_basename($filepath, $ext) . '-pdf.jpg');

                $uploaded = $editor->save($preview_file, 'image/jpeg');
                unset($editor);

                // Resize based on the full size image, rather than the source.
                if (!is_wp_error($uploaded)) {
                    $editor = wp_get_image_editor($uploaded['path']);
                    unset($uploaded['path']);

                    if (!is_wp_error($editor)) {
                        $metadata['sizes']         = $editor->multi_resize($sizes);
                        $metadata['sizes']['full'] = $uploaded;
                    }
                }
            }
        }
    }

    /**
     * Create thumbnail after replace
     *
     * @param string  $filepath Physical path of file
     * @param string  $extimage Extension of file
     * @param array   $metadata Meta data of file
     * @param integer $post_id  ID of file
     *
     * @return void
     */
    public static function createThumbs($filepath, $extimage, $metadata, $post_id)
    {
        if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            $uploadpath = wp_upload_dir();
            foreach ($metadata['sizes'] as $size => $sizeinfo) {
                $intermediate_file = str_replace(basename($filepath), $sizeinfo['file'], $filepath);

                // load image and get image size
                list($width, $height) = getimagesize($filepath);
                $new_width = $sizeinfo['width'];
                $new_height = floor($height * ($sizeinfo['width'] / $width));
                $tmp_img = imagecreatetruecolor($new_width, $new_height);

                imagealphablending($tmp_img, false);
                imagesavealpha($tmp_img, true);

                switch ($extimage) {
                    case 'jpeg':
                    case 'jpg':
                        $source = imagecreatefromjpeg($filepath);
                        break;

                    case 'png':
                        $source = imagecreatefrompng($filepath);
                        break;

                    case 'gif':
                        $source = imagecreatefromgif($filepath);
                        break;

                    case 'bmp':
                        $source = imagecreatefromwbmp($filepath);
                        break;
                    default:
                        $source = imagecreatefromjpeg($filepath);
                }

                imagealphablending($source, true);
                imagecopyresampled($tmp_img, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                switch ($extimage) {
                    case 'jpeg':
                    case 'jpg':
                        imagejpeg($tmp_img, path_join($uploadpath['basedir'], $intermediate_file), 100);
                        break;

                    case 'png':
                        imagepng($tmp_img, path_join($uploadpath['basedir'], $intermediate_file), 9);
                        break;

                    case 'gif':
                        imagegif($tmp_img, path_join($uploadpath['basedir'], $intermediate_file));
                        break;

                    case 'bmp':
                        imagewbmp($tmp_img, path_join($uploadpath['basedir'], $intermediate_file));
                        break;
                }

                $metadata[$size]['width'] = $new_width;
                $metadata[$size]['width'] = $new_height;
                wp_update_attachment_metadata($post_id, $metadata);
            }
        } else {
            wp_update_attachment_metadata($post_id, $metadata);
        }
    }

    /**
     * Save pptc metadata
     *
     * @param integer $enable       Enable or disable option
     * @param integer $image_id     ID of image
     * @param string  $path         Path of image
     * @param array   $allow_fields Include fields
     * @param string  $title        Title of image
     * @param string  $mime_type    Mime type
     *
     * @return void
     */
    public static function saveIptcMetadata($enable, $image_id, $path, $allow_fields, $title, $mime_type)
    {
        $iptcMeta = array();
        // update alt
        if ((int) $enable === 1 && strpos($mime_type, 'image') !== false && $title !== '' && !empty($allow_fields['alt'])) {
            update_post_meta($image_id, '_wp_attachment_image_alt', $title);
        }

        if ((int)$enable === 1 && strpos($mime_type, 'image') !== false) {
            $size = getimagesize($path, $info);
            if (!empty($allow_fields['2#105']) && $title !== '') {
                $iptcMeta['2#105'] = array($title);
            }

            if (isset($info['APP13'])) {
                $iptc = iptcparse($info['APP13']);
                if (!empty($iptc)) {
                    foreach ($iptc as $code => $iptcValue) {
                        if (!empty($allow_fields[$code])) {
                            $iptcMeta[$code] = $iptcValue;
                        }
                    }

                    update_post_meta($image_id, 'wpmf_iptc', $iptcMeta);
                }
            }
        }
    }

    /**
     * Sort parents before children
     * http://stackoverflow.com/questions/6377147/sort-an-array-placing-children-beneath-parents
     *
     * @param array   $objects      List folder
     * @param integer $enable_count Enable count
     * @param array   $result       Result
     * @param integer $parent       Parent of folder
     * @param integer $depth        Depth of folder
     *
     * @return array           output
     */
    public static function parentSort(array $objects, $enable_count = false, array &$result = array(), $parent = 0, $depth = 0)
    {
        foreach ($objects as $key => $object) {
            if ((int)$object->parent === -1) {
                $pr = 0;
            } else {
                $pr = $object->parent;
            }

            if ((int) $pr === (int) $parent) {
                if ($enable_count) {
                    $object->files_count = self::getCountFiles($object->term_id);
                    $object->count_all = 0;
                }
                $object->depth = $depth;
                array_push($result, $object);
                unset($objects[$key]);
                self::parentSort($objects, $enable_count, $result, $object->term_id, $depth + 1);
            }
        }
        return $result;
    }

    /**
     * Get count files in folder
     *
     * @param integer $term_id Id of folder
     *
     * @return integer
     */
    public static function getCountFiles($term_id)
    {
        global $wpdb;
        if (defined('ICL_SITEPRESS_VERSION') && ICL_SITEPRESS_VERSION) {
            global $sitepress;
            $settings = $sitepress->get_settings();
            if (isset($settings['custom_posts_sync_option']['attachment']) && (int) $settings['custom_posts_sync_option']['attachment'] === 1) {
                $language = $sitepress->get_current_language();
                $count = (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'icl_translations AS wpml 
            INNER JOIN ' . $wpdb->term_relationships . ' AS term_rela ON term_rela.object_id = wpml.element_id 
            WHERE wpml.element_type = "post_attachment" AND term_rela.term_taxonomy_id = %d 
            AND wpml.language_code = %s', array($term_id, $language)));
            } else {
                $folder = get_term($term_id, WPMF_TAXO);
                return $folder->count;
            }
        } elseif (is_plugin_active('polylang/polylang.php') || is_plugin_active('polylang-pro/polylang.php')) {
            global $polylang;
            $all_objects = get_objects_in_term($term_id, WPMF_TAXO);
            if ($polylang->curlang && $polylang->model->is_translated_post_type('attachment')) {
                $my_current_lang = $polylang->curlang->slug;
                $lang_term = get_term_by('slug', $my_current_lang, 'language');
                $lang_object = get_objects_in_term($lang_term->term_id, 'language', array('post_type' => 'attachment'));
                $count = array_intersect($all_objects, $lang_object);
                return count($count);
            } else {
                return count($all_objects);
            }
        } else {
            $count = $wpdb->get_var($wpdb->prepare('SELECT COUNT(ID) FROM ' . $wpdb->posts . ' as p, ' . $wpdb->term_relationships . ' as tr, ' . $wpdb->term_taxonomy . ' as tt, ' . $wpdb->terms . ' as t WHERE p.ID = tr.object_id AND post_type = %s AND (post_status = %s OR post_status = %s) AND tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.term_id=t.term_id AND t.term_id = %d', array('attachment', 'publish', 'inherit', (int)$term_id)));
        }

        return (int) $count;
    }

    /**
     * Get root folder count
     *
     * @param integer $folderRootId Root folder ID
     *
     * @return integer
     */
    public static function getRootFolderCount($folderRootId)
    {
        // if disable root media count
        $root_media_count = wpmfGetOption('root_media_count');
        if ((int)$root_media_count === 0) {
            return 0;
        }

        global $wpdb;

        // Retrieve the overall count of attachements
        $query = 'SELECT COUNT(DISTINCT(p.ID)) AS count FROM ' . $wpdb->posts . ' AS p
                        WHERE p.post_type = "attachment" 
                            AND (p.post_status = "publish" OR p.post_status = "inherit")';
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL not contain variable
        $total_count = (int)$wpdb->get_var($query);

        // Retrieve the number of attachments which are at least in one folder (except the root folder)
        $attachments_in_folders_count = (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(DISTINCT(p.ID)) AS count FROM ' . $wpdb->posts . ' AS p 
                        LEFT JOIN ' . $wpdb->term_relationships . ' AS tr 
                            ON p.ID = tr.object_id
                        LEFT JOIN ' . $wpdb->term_taxonomy . ' AS tt 
                            ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.taxonomy = "wpmf-category"
                        WHERE p.post_type = "attachment" 
                            AND (p.post_status = "publish" OR p.post_status = "inherit")
                            AND tt.term_id IS NOT NULL
                            AND tt.term_id <> %d', (int) $folderRootId));

        // Retrieve the number of attachments which are simultaneously in the root folder and in another folder
        $attachments_in_root_folder_count = (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(DISTINCT(p.ID)) AS count FROM ' . $wpdb->posts . ' AS p 
                        LEFT JOIN ' . $wpdb->term_relationships . ' AS tr 
                            ON p.ID = tr.object_id
                        LEFT JOIN ' . $wpdb->term_taxonomy . ' AS tt 
                            ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.taxonomy = "wpmf-category"
                        WHERE p.post_type = "attachment" 
                            AND (p.post_status = "publish" OR p.post_status = "inherit")
                            AND tt.term_id = %d', (int) $folderRootId));

        return  $total_count - $attachments_in_folders_count + $attachments_in_root_folder_count;
    }

    /**
     * Tries to convert an attachment URL into a post ID.
     *
     * @param string $url       The URL to resolve.
     * @param string $ext       Extension of file
     * @param string $file_hash File hash
     * @param string $action    Action
     *
     * @return integer The found post ID, or 0 on failure.
     */
    public static function attachmentUrlToPostid($url, $ext = '', $file_hash = '', $action = '')
    {
        global $wpdb;
        $dir = wp_get_upload_dir();
        $path = $url;

        $site_url = parse_url($dir['url']);
        $image_path = parse_url($path);

        // Force the protocols to match if needed.
        if (isset($image_path['scheme']) && ($image_path['scheme'] !== $site_url['scheme'])) {
            $path = str_replace($image_path['scheme'], $site_url['scheme'], $path);
        }

        if (0 === strpos($path, $dir['baseurl'] . '/')) {
            $path = substr($path, strlen($dir['baseurl'] . '/'));
        }

        if ($ext === 'pdf') {
            $path = str_replace(array('-pdf.jpg', '-pdf.jpeg', '-pdf.png'), '.pdf', $path);
        }

        if ($action === 'import') {
            $sql = $wpdb->prepare(
                'SELECT post_id, meta_value FROM '. $wpdb->postmeta .' WHERE meta_key = "wpmf_sync_file_hash" AND meta_value = %s',
                $file_hash
            );
        } else {
            $sql = $wpdb->prepare(
                'SELECT post_id, meta_value FROM '. $wpdb->postmeta .' WHERE meta_key = "_wp_attached_file" AND meta_value = %s',
                $path
            );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Variable has been prepare
        $results = $wpdb->get_results($sql);
        $post_id = null;

        if ($results) {
            // Use the first available result, but prefer a case-sensitive match, if exists.
            $post_id = reset($results)->post_id;

            if (count($results) > 1) {
                foreach ($results as $result) {
                    $drive_id = get_post_meta($result->post_id, 'wpmf_drive_id', true);
                    if ($path === $result->meta_value && empty($drive_id)) {
                        $post_id = $result->post_id;
                        break;
                    }
                }
            }
        }

        return (int)$post_id;
    }

    /**
     * Get current user role
     *
     * @param integer $userId Id of user
     *
     * @return mixed|string
     */
    public static function getRoles($userId)
    {
        if (!function_exists('get_userdata')) {
            require_once(ABSPATH . 'wp-includes/pluggable.php');
        }

        if ((int)$userId === 0) {
            return 'administrator';
        }

        $userdata = get_userdata($userId);
        if (!empty($userdata->roles)) {
            if (in_array('administrator', $userdata->roles)) {
                return 'administrator';
            }
            $role = array_slice($userdata->roles, 0, 1);
            $role = $role[0];
        } else {
            $role = '';
        }

        return $role;
    }

    /**
     * Get cloud root folder ID
     *
     * @param string $cloud_type Cloud type
     *
     * @return boolean|integer
     */
    public static function getCloudRootFolderID($cloud_type)
    {
        $folder = false;
        switch ($cloud_type) {
            case 'google_drive':
                $folder = get_term_by('name', 'Google Drive', WPMF_TAXO);
                break;
            case 'dropbox':
                $folder = get_term_by('name', 'Dropbox', WPMF_TAXO);
                break;
            case 'onedrive':
                $folder = get_term_by('name', 'Onedrive', WPMF_TAXO);
                break;
            case 'onedrive_business':
                $folder = get_term_by('name', 'Onedrive Business', WPMF_TAXO);
                break;
        }

        if (!empty($folder)) {
            return $folder->term_id;
        }

        return false;
    }

    /**
     * Check cloud connected
     *
     * @param string $cloud_type Cloud type
     *
     * @return boolean
     */
    public static function isConnected($cloud_type)
    {
        $connected = false;
        switch ($cloud_type) {
            case 'google_drive':
                $options = get_option('_wpmfAddon_cloud_config');
                if (!empty($options['connected']) && !empty($options['media_access'])) {
                    $connected = true;
                }
                break;
            case 'dropbox':
                $options = get_option('_wpmfAddon_dropbox_config');
                if (!empty($options['dropboxToken']) && !empty($options['media_access'])) {
                    $connected = true;
                }
                break;
            case 'onedrive':
                $options = get_option('_wpmfAddon_onedrive_config');
                if (!empty($options['connected']) && !empty($options['media_access'])) {
                    $connected = true;
                }
                break;
            case 'onedrive_business':
                $options = get_option('_wpmfAddon_onedrive_business_config');
                if (!empty($options['connected']) && !empty($options['media_access'])) {
                    $connected = true;
                }
                break;
        }

        return $connected;
    }

    /**
     * Check enable load all media in cloud user folder
     *
     * @param string $cloud_type Cloud type
     *
     * @return boolean
     */
    public static function isLoadAllChildsCloud($cloud_type)
    {
        $connected = false;
        switch ($cloud_type) {
            case 'google_drive':
                $options = get_option('_wpmfAddon_cloud_config');
                if (!empty($options['connected']) && !empty($options['media_access']) && !empty($options['load_all_childs'])) {
                    $connected = true;
                }
                break;
            case 'dropbox':
                $options = get_option('_wpmfAddon_dropbox_config');
                if (!empty($options['dropboxToken']) && !empty($options['media_access']) && !empty($options['load_all_childs'])) {
                    $connected = true;
                }
                break;
            case 'onedrive':
                $options = get_option('_wpmfAddon_onedrive_config');
                if (!empty($options['connected']) && !empty($options['media_access']) && !empty($options['load_all_childs'])) {
                    $connected = true;
                }
                break;
            case 'onedrive_business':
                $options = get_option('_wpmfAddon_onedrive_business_config');
                if (!empty($options['connected']) && !empty($options['media_access']) && !empty($options['load_all_childs'])) {
                    $connected = true;
                }
                break;
            default:
                $connected = false;
        }

        return $connected;
    }

    /**
     * Get access
     *
     * @param integer $term_id            Folder ID
     * @param integer $user_id            User ID
     * @param string  $capability         Capability
     * @param string  $cloud_user_folders Cloud user folders list
     *
     * @return boolean
     */
    public static function getAccess($term_id, $user_id, $capability = '', $cloud_user_folders = array())
    {
        $active_media = get_option('wpmf_active_media');
        if (empty($active_media)) {
            return true;
        }

        $is_access = false;
        $role = self::getRoles($user_id);
        if ($role === 'administrator') {
            return true;
        }

        if (empty($term_id)) {
            return false;
        }

        global $current_user;
        $term = get_term($term_id, WPMF_TAXO);

        // inherit folder permissions
        $role_permissions = get_term_meta((int)$term_id, 'wpmf_folder_role_permissions', true);
        $user_permissions = get_term_meta((int)$term_id, 'wpmf_folder_user_permissions', true);
        $inherit_folder = get_term_meta((int)$term_id, 'inherit_folder', true);
        if ((($inherit_folder === '' && $role_permissions === '' && $user_permissions === '') || !empty($inherit_folder)) && $term->parent !== 0) {
            $ancestors = get_ancestors($term_id, WPMF_TAXO, 'taxonomy');
            if (!empty($ancestors)) {
                $t = false;
                foreach ($ancestors as $ancestor) {
                    $inherit_folder = get_term_meta((int)$ancestor, 'inherit_folder', true);
                    if ((int)$inherit_folder === 0) {
                        $t = true;
                        $term_id = $ancestor;
                        break;
                    }
                }

                if (!$t) {
                    $term_id = $ancestors[count($ancestors) - 1];
                }
            }
        }

        // check is root cloud folder
        if ($term->name === 'Google Drive' && (int)$term->parent === 0 && $capability === 'view_folder') {
            if (self::isConnected('google_drive')) {
                return true;
            } else {
                return false;
            }
        } elseif ($term->name === 'Dropbox' && (int)$term->parent === 0 && $capability === 'view_folder') {
            if (self::isConnected('dropbox')) {
                return true;
            } else {
                return false;
            }
        } elseif ($term->name === 'Onedrive' && (int)$term->parent === 0 && $capability === 'view_folder') {
            if (self::isConnected('onedrive')) {
                return true;
            } else {
                return false;
            }
        } elseif ($term->name === 'Onedrive Business' && (int)$term->parent === 0 && $capability === 'view_folder') {
            if (self::isConnected('onedrive_business')) {
                return true;
            } else {
                return false;
            }
        }

        if ($capability !== 'view_folder' && !$term_id) {
            return false;
        }

        // only show role folder when access type is 'role'
        $access_type     = get_option('wpmf_create_folder');
        if ($access_type === 'role') {
            if ($term->name === $role && strpos($term->slug, '-wpmf-role') !== false) {
                return true;
            }
        }

        $type = get_term_meta($term_id, 'wpmf_drive_type', true);
        // if is cloud folder
        if (!empty($type)) {
            if (in_array($term_id, $cloud_user_folders)) {
                return true;
            }
        }

        // get access by role
        $permissions = get_term_meta((int)$term_id, 'wpmf_folder_role_permissions');
        if (!empty($permissions)) {
            foreach ($permissions as $permission) {
                if ($permission[0] === $role && in_array($capability, $permission)) {
                    $is_access = true;
                    break;
                }
            }
        }

        if ($is_access) {
            return true;
        } else {
            // get access by user
            $permissions = get_term_meta((int)$term_id, 'wpmf_folder_user_permissions');
            if ($term->name === $current_user->user_login && (int) $term->term_group === (int) get_current_user_id() && $capability === 'view_folder') {
                return true;
            }

            if (!empty($permissions)) {
                foreach ($permissions as $permission) {
                    if ((int)$permission[0] === get_current_user_id() && in_array($capability, $permission)) {
                        $is_access = true;
                        break;
                    }
                }
            }
        }

        return $is_access;
    }

    /**
     * Get dailymotion video ID from URL
     *
     * @param string $url URL of video
     *
     * @return mixed|string
     */
    public static function getDailymotionVideoIdFromUrl($url = '')
    {
        $id = strtok(basename($url), '_');
        return $id;
    }

    /**
     * Get vimeo video ID from URL
     *
     * @param string $url URl of video
     *
     * @return mixed|string
     */
    public static function getVimeoVideoIdFromUrl($url = '')
    {
        $regs = array();
        $id   = '';
        if (preg_match(self::$vimeo_pattern, $url, $regs)) {
            $id = $regs[3];
        }

        return $id;
    }

    /**
     * Create video in media library
     *
     * @param string  $video_url Video URL
     * @param integer $thumbnail Video thumbnail
     * @param string  $action    Action
     *
     * @return boolean|integer|WP_Error
     */
    public static function doCreateVideo($video_url = '', $thumbnail = 0, $action = 'remote_video')
    {
        $title   = '';
        $ext     = '';
        $content = '';
        if ($action === 'video_to_gallery' && (int)$thumbnail !== 0) {
            update_post_meta($thumbnail, 'wpmf_remote_video_link', $video_url);
            return $thumbnail;
        }

        $video_url = str_replace('manage/videos/', '', $video_url);
        if (!preg_match(self::$vimeo_pattern, $video_url, $output_array)
            && !preg_match('/(youtube.com|youtu.be)\/(watch)?(\?v=)?(\S+)?/', $video_url, $match)
            && !preg_match('/\b(?:dailymotion)\.com\b/i', $video_url, $vresult)) {
            return false;
        } elseif (preg_match(self::$vimeo_pattern, $video_url, $output_array)) {
            // for vimeo
            $id = self::getVimeoVideoIdFromUrl($video_url);
            $videos = wp_remote_get('https://player.vimeo.com/video/' . $id . '/config');
            $body = json_decode($videos['body']);
            if (!empty($body->video->thumbs->base)) {
                $thumb = $body->video->thumbs->base;
            } else {
                $videos = wp_remote_get('https://vimeo.com/api/v2/video/' . $id . '.json');
                $body = json_decode($videos['body']);
                $body = $body[0];
                $thumb = '';
                if (isset($body->thumbnail_large)) {
                    $thumb = $body->thumbnail_large;
                } elseif (isset($body->thumbnail_medium)) {
                    $thumb = $body->thumbnail_large;
                } elseif (isset($body->thumbnail_small)) {
                    $thumb = $body->thumbnail_small;
                }
            }

            if ($thumb !== '') {
                $thumb_remote = wp_remote_get($thumb);
                $content = $thumb_remote['body'];
                $title = $body->video->title;
                $ext = 'jpg';
            } else {
                return false;
            }
        } elseif (preg_match('/(youtube.com|youtu.be)\/(watch)?(\?v=)?(\S+)?/', $video_url, $match)) {
            // for youtube
            // get thumbnail of video
            $parts = parse_url($video_url);
            if ($parts['host'] === 'youtu.be') {
                $id = trim($parts['path'], '/');
            } else {
                parse_str($parts['query'], $query);
                $id = $query['v'];
            }

            $thumb = 'http://img.youtube.com/vi/' . $id . '/maxresdefault.jpg';
            $gets = wp_remote_get($thumb);
            if (!empty($gets) && $gets['response']['code'] !== 200) {
                $thumb = 'http://img.youtube.com/vi/' . $id . '/sddefault.jpg';
                $gets = wp_remote_get($thumb);
            }

            if (!empty($gets) && $gets['response']['code'] !== 200) {
                $thumb = 'http://img.youtube.com/vi/' . $id . '/hqdefault.jpg';
                $gets = wp_remote_get($thumb);
            }

            if (!empty($gets) && $gets['response']['code'] !== 200) {
                $thumb = 'http://img.youtube.com/vi/' . $id . '/mqdefault.jpg';
                $gets = wp_remote_get($thumb);
            }

            if (!empty($gets) && $gets['response']['code'] !== 200) {
                $thumb = 'http://img.youtube.com/vi/' . $id . '/default.jpg';
                $gets = wp_remote_get($thumb);
            }

            if (empty($gets)) {
                return false;
            }

            $content = $gets['body'];
            $json_datas = wp_remote_get('https://www.youtube.com/oembed?url=' . $video_url . '&format=json');
            if (!is_array($json_datas)) {
                return false;
            }

            $infos = json_decode($json_datas['body'], true);
            if (isset($infos['status']) && $infos['status'] === 'fail') {
                return false;
            }

            if (empty($infos['title'])) {
                $title = $id;
            } else {
                $title = $infos['title'];
            }

            $info_thumbnail = pathinfo($thumb); // get info thumbnail
            $ext            = $info_thumbnail['extension'];
        } elseif (preg_match('/\b(?:dailymotion)\.com\b/i', $video_url, $vresult)) {
            // for dailymotion
            $id   = self::getDailymotionVideoIdFromUrl($video_url);
            $gets = wp_remote_get('http://www.dailymotion.com/services/oembed?format=json&url=http://www.dailymotion.com/embed/video/' . $id);
            $info = json_decode($gets['body'], true);
            if (empty($info)) {
                return false;
            }

            // get thumbnail content of video
            $thumb = $info['thumbnail_url'];
            $thumb_gets        = wp_remote_get($thumb);
            if (empty($thumb_gets)) {
                return false;
            }
            $content = $thumb_gets['body'];
            $info_thumbnail = pathinfo($info['thumbnail_url']); // get info thumbnail
            $ext            = (!empty($info_thumbnail['extension'])) ? $info_thumbnail['extension'] : 'jpg';
        }

        $upload_dir = wp_upload_dir();
        // create wpmf_remote_video folder
        if (!file_exists($upload_dir['basedir'] . '/wpmf_remote_video')) {
            if (!mkdir($upload_dir['basedir'] . '/wpmf_remote_video')) {
                return false;
            }
        }

        if ((int)$thumbnail === 0) {
            // upload  thumbnail to wpmf_remote_video folder
            $upload_folder = $upload_dir['basedir'] . '/wpmf_remote_video';
            $title = sanitize_text_field($title);
            if (file_exists($upload_folder . '/' . $title . '.' . $ext)) {
                $fname = wp_unique_filename($upload_folder, $title . '.' . $ext);
                $upload        = file_put_contents($upload_folder . '/' . $fname, $content);
            } else {
                $fname = $title . '.' . $ext;
                $upload        = file_put_contents($upload_folder . '/' . $fname, $content);
            }

            // upload images
            if ($upload) {
                if (($ext === 'jpg')) {
                    $mimetype = 'image/jpeg';
                } else {
                    $mimetype = 'image/' . $ext;
                }
                $attachment = array(
                    'guid'           => $upload_dir['baseurl'] . '/' . $fname,
                    'post_mime_type' => $mimetype,
                    'post_title'     => $title,
                    'post_excerpt'   => $title
                );

                $image_path = $upload_folder . '/' . $fname;
                $attach_id  = wp_insert_attachment($attachment, $image_path);
                if (!is_wp_error($attach_id)) {
                    // create image in folder
                    $current_folder_id = $_POST['folder_id']; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No action, nonce is not required
                    wp_set_object_terms((int) $attach_id, (int) $current_folder_id, WPMF_TAXO, false);

                    $attach_data = wp_generate_attachment_metadata($attach_id, $image_path);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    update_post_meta($attach_id, 'wpmf_remote_video_link', $video_url);
                    return $attach_id;
                }
            }

            return false;
        }

        update_post_meta($thumbnail, 'wpmf_remote_video_link', $video_url);
        return $thumbnail;
    }
}
