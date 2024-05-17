<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

require_once(WPMFAD_PLUGIN_DIR . '/class/wpmfGooglePhoto.php');
/**
 * Class WpmfAddonGooglePhotoAdmin
 */
class WpmfAddonGooglePhotoAdmin extends WpmfAddonGooglePhoto
{
    /**
     * WpmfAddonGooglePhotoAdmin constructor.
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
        add_action('wp_ajax_wpmf-get-google-photos', array($this, 'getGooglePhotos'));
        add_action('wp_ajax_wpmf_google_photo_gallery_import', array($this, 'googlePhotoGalleryImport'));
        add_action('wp_ajax_wpmf_google_photo_import', array($this, 'photosImportToFolder'));
        add_action('wp_ajax_wpmf_google_photo_album_import', array($this, 'albumImportToLibrary'));
        add_action('wp_ajax_wpmf_import_all_photos_in_album_to_gallery', array($this, 'importAllPhotosInAlbumToGallery'));
    }

    /**
     * Action hooks
     *
     * @return void
     */
    public function actionHooks()
    {
        if (!empty($this->params->connected)) {
            add_action('admin_menu', array($this, 'addMenuPage'));
            add_action('admin_enqueue_scripts', array($this, 'register'));
        }
    }

    /**
     * Filter hooks
     *
     * @return void
     */
    public function filterHooks()
    {
        add_filter('wpmfaddon_google_photo_settings', array($this, 'renderSettings'), 10, 3);
    }

    /**
     * Load scripts and style
     *
     * @return void
     */
    public function register()
    {
        wp_register_style(
            'wpmf-settings-google-icon',
            plugins_url('/assets/css/google-material-icon.css', dirname(__FILE__)),
            array(),
            WPMF_VERSION
        );

        wp_register_script(
            'wpmf-google-photo-fancybox-script',
            WPMFAD_PLUGIN_URL . '/assets/js/fancybox/jquery.fancybox.min.js',
            array('jquery'),
            WPMFAD_VERSION
        );

        wp_register_style(
            'wpmf-google-photo-fancybox-style',
            WPMFAD_PLUGIN_URL . '/assets/js/fancybox/jquery.fancybox.min.css',
            array(),
            WPMFAD_VERSION
        );

        wp_register_script(
            'wpmf-import-cloud-tree',
            WPMF_PLUGIN_URL . '/assets/js/imports/import_cloud_tree.js',
            array('jquery'),
            WPMF_VERSION
        );

        wp_register_script(
            'wpmf-google-photo-script',
            WPMFAD_PLUGIN_URL . '/assets/js/google-photo-admin.js',
            array('jquery'),
            WPMFAD_VERSION
        );

        wp_localize_script('wpmf-google-photo-script', 'wpmfgooglephoto', array(
            'l18n' => array(
                'selected_photos' => esc_html__('Selected Photos', 'wpmfAddon'),
                'all_photos' => esc_html__('All photos', 'wpmfAddon'),
                'import_source' => esc_html__('Import Source', 'wpmfAddon'),
                'importing_goolge_photo_album'  => esc_html__('Photo album importing...', 'wpmfAddon'),
                'importing_goolge_photo'  => esc_html__('Google photos importing...', 'wpmfAddon'),
                'cancel'                => esc_html__('Cancel', 'wpmfAddon'),
                'import'                => esc_html__('Import', 'wpmfAddon')
            ),
            'vars' => array(
                'wpmf_images_path' => plugins_url('assets/images', dirname(__FILE__)),
                'ajaxurl'          => admin_url('admin-ajax.php'),
                'admin_url' => admin_url(),
                'wpmf_nonce' => wp_create_nonce('wpmf_nonce'),
            )
        ));

        wp_register_style(
            'wpmf-google-photo-style',
            WPMFAD_PLUGIN_URL . '/assets/css/google-photo-admin.css',
            array(),
            WPMFAD_VERSION
        );
    }

    /**
     * Add menu media page
     *
     * @return void
     */
    public function addMenuPage()
    {
        add_media_page(
            'Google Photos',
            'Google Photos',
            'upload_files',
            'wpmf-google-photos',
            array($this, 'showGooglePhotoPage')
        );
    }

    /**
     * Show google photo page
     *
     * @return void
     */
    public function showGooglePhotoPage()
    {
        if (isset($_GET['noheader'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
            global $hook_suffix;
            _wp_admin_html_begin();
            do_action('admin_enqueue_scripts', $hook_suffix);
            do_action('admin_print_scripts-' . $hook_suffix);
            do_action('admin_print_scripts');
            $header = 'no-header';
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
            $gallery_id = (isset($_GET['gallery_id'])) ? (int) $_GET['gallery_id'] : 0;
        }

        wp_enqueue_media();
        wp_enqueue_style('wpmf-settings-google-icon');
        wp_enqueue_script('wpmf-google-photo-fancybox-script');
        wp_enqueue_style('wpmf-google-photo-fancybox-style');

        wp_enqueue_script('wpmf-import-cloud-tree');
        wp_enqueue_script('wpmf-google-photo-script');
        wp_enqueue_style('wpmf-google-photo-style');
        wp_enqueue_script('wpmf-google-photo-script');
        wp_enqueue_style('wpmf-google-photo-style');
        $albums = $this->getListAlbums();
        $upload_dir = wp_upload_dir();
        require_once(WPMFAD_PLUGIN_DIR . 'class/templates/google-photo-library.php');
    }

    /**
     * Render google drive settings
     *
     * @param string $html                HTML
     * @param object $googlePhoto         WpmfAddonGooglePhoto class
     * @param array  $google_photo_config Google photo config
     *
     * @return string
     */
    public function renderSettings($html, $googlePhoto, $google_photo_config)
    {
        if (empty($google_photo_config['googleClientId'])) {
            $google_photo_config['googleClientId'] = '';
        }

        if (empty($google_photo_config['googleClientSecret'])) {
            $google_photo_config['googleClientSecret'] = '';
        }

        ob_start();
        require_once 'templates/settings_google_photo.php';
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
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
     * Get google photos
     *
     * @return void
     */
    public function getGooglePhotos()
    {
        if (empty($_POST['wpmf_nonce'])
            || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
            die();
        }

        $album = (isset($_POST['album'])) ? $_POST['album'] : '';
        $pageToken = (isset($_POST['pageToken'])) ? $_POST['pageToken'] : '';
        if ($album === '') {
            wp_send_json(array('status' => false));
        }

        if ($album === 'photos') {
            $return = $this->getAllMediaItems($pageToken);
            $photos = $return['photos'];
        } else {
            $return = $this->getMediaItemsByAlbumId($album, 30, $pageToken);
            $photos = $return['photos'];
        }

        $html = '';
        if (!empty($photos)) {
            foreach ($photos as $photo) {
                if (strpos($photo->mimeType, 'image') === false) {
                    continue;
                }

                $full_link = $photo->baseUrl . '=w' . $photo->mediaMetadata->width . '-h' . $photo->mediaMetadata->height;
                $html .= '<div class="photo-item" data-mimetype="'. esc_attr($photo->mimeType) .'" data-filename="'. esc_attr($photo->filename) .'" data-id="' . esc_attr($photo->id) . '" data-full="' . esc_url($full_link) . '">';
                $html .= '<svg width="24px" height="24px" class="photo-item-check-bg" viewBox="0 0 24 24"><circle opacity=".26" fill="url(#checkboxShadowCircle)" cx="12" cy="13.512" r="10.488"></circle><circle fill="#FFF" cx="12" cy="12.2" r="8.292"></circle></svg><svg width="24px" height="24px" class="photo-item-check v1262d JUQOtc orgUxc" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"></path></svg>';
                $html .= '<a data-fancybox="gallery" class="google-photo-full" href="' . esc_url($full_link) . '">'. esc_html__('Preview', 'wpmfAddon') .'</a>';
                $html .= '<img src="' . esc_attr($photo->baseUrl) . '">';
                $html .= '</div>';
            }
        } else {
            $html .= '<p class="no-photos">'. esc_html__('No photos found.', 'wpmfAddon') .'</p>';
        }

        if (!empty($return['pageToken'])) {
            wp_send_json(array('status' => true, 'html' => $html, 'pageToken' => $return['pageToken']));
        } else {
            wp_send_json(array('status' => true, 'html' => $html));
        }
    }

    /**
     * Import photos to a folder library
     *
     * @return void
     */
    public function photosImportToFolder()
    {
        if (empty($_POST['wpmf_nonce'])
            || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
            die();
        }

        set_time_limit(0);
        $files = (isset($_POST['files'])) ? explode(',', $_POST['files']) : array();
        $mimeTypes = (isset($_POST['mimeTypes'])) ? explode(',', $_POST['mimeTypes']) : array();
        $filenames = (isset($_POST['filenames'])) ? explode(',', $_POST['filenames']) : array();

        $page = (isset($_POST['page'])) ? (int) $_POST['page'] : 0;
        $limit = 2;
        $offset = $page * $limit;
        $files = array_slice($files, $offset, $limit);
        // stop if empty files
        if (empty($files)) {
            wp_send_json(array('status' => true, 'continue' => false, 'msg' => __('Import success!', 'wpmfAddon')));
        }

        $folder = (isset($_POST['folder'])) ? (int) $_POST['folder'] : 0;
        $upload_dir = wp_upload_dir();
        $upload_folder = $upload_dir['path'];
        foreach ($files as $index => $file) {
            $gets = wp_remote_get($file);
            if (!empty($gets)) {
                $content = $gets['body'];
                $pathinfo = pathinfo($filenames[$index]); // get info thumbnail
                $title          = sanitize_title($pathinfo['filename']); // get title video
                $ext            = $pathinfo['extension'];

                if (file_exists($upload_folder . '/' . $title . '.' . $ext)) {
                    $fname = wp_unique_filename($upload_folder, $title . '.' . $ext);
                    $upload        = file_put_contents($upload_folder . '/' . $fname, $content);
                } else {
                    $fname = $title . '.' . $ext;
                    $upload        = file_put_contents($upload_folder . '/' . $fname, $content);
                }

                // upload images
                if ($upload) {
                    $attachment = array(
                        'guid'           => $upload_dir['url'] . '/' . $fname,
                        'post_mime_type' => $mimeTypes[$index],
                        'post_title'     => $title
                    );

                    $image_path = $upload_folder . '/' . $fname;
                    $attach_id  = wp_insert_attachment($attachment, $image_path);
                    $attach_data = wp_generate_attachment_metadata($attach_id, $image_path);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    // update order meta
                    update_post_meta(
                        (int) $attach_id,
                        'wpmf_order',
                        0
                    );

                    // set file to folder
                    if (!empty($folder)) {
                        wp_set_object_terms((int) $attach_id, $folder, WPMF_TAXO, false);
                    }

                    /**
                     * Create remote video file
                     *
                     * @param integer       Created attachment ID
                     * @param integer|array Target        folder
                     * @param array         Extra informations
                     *
                     * @ignore Hook already documented
                     */
                    do_action('wpmf_add_attachment', $attach_id, $folder, array('type' => 'import_google_photo'));
                }
            }
        }

        wp_send_json(array('status' => true, 'continue' => true, 'msg' => __('Import success!', 'wpmfAddon')));
    }

    /**
     * Import all photos to gallery
     *
     * @return void
     */
    public function importAllPhotosInAlbumToGallery()
    {
        if (empty($_POST['wpmf_nonce'])
            || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
            die();
        }

        set_time_limit(0);
        $albumId = (isset($_POST['albumId'])) ? $_POST['albumId'] : '';
        $pageToken = (isset($_POST['pageToken'])) ? $_POST['pageToken'] : '';
        if ($albumId === '') {
            wp_send_json(array('status' => false));
        }

        if ($albumId === 'photos') {
            $return = $this->getAllMediaItems($pageToken, 5);
            $files = $return['photos'];
        } else {
            $return = $this->getMediaItemsByAlbumId($albumId, 5, $pageToken);
            $files = $return['photos'];
        }

        // stop if empty files
        if (empty($files)) {
            wp_send_json(array('status' => true, 'continue' => false, 'msg' => esc_html__('Import success!', 'wpmfAddon')));
        }

        // get parent for case import album
        $folder = (isset($_POST['folder'])) ? (int) $_POST['folder'] : 0;
        $upload_dir = wp_upload_dir();
        $upload_folder = $upload_dir['path'];
        foreach ($files as $file) {
            $full_link = $file->baseUrl . '=w' . $file->mediaMetadata->width . '-h' . $file->mediaMetadata->height;
            $gets = wp_remote_get($full_link);
            if (!empty($gets)) {
                $content = $gets['body'];
                $pathinfo = pathinfo($file->filename); // get info thumbnail
                $title          = sanitize_title($pathinfo['filename']); // get title video
                $ext            = $pathinfo['extension'];

                if (file_exists($upload_folder . '/' . $title . '.' . $ext)) {
                    $fname = wp_unique_filename($upload_folder, $title . '.' . $ext);
                    $upload        = file_put_contents($upload_folder . '/' . $fname, $content);
                } else {
                    $fname = $title . '.' . $ext;
                    $upload        = file_put_contents($upload_folder . '/' . $fname, $content);
                }

                // upload images
                if ($upload) {
                    $attachment = array(
                        'guid'           => $upload_dir['url'] . '/' . $fname,
                        'post_mime_type' => $file->mimeType,
                        'post_title'     => $title
                    );

                    $image_path = $upload_folder . '/' . $fname;
                    $attach_id  = wp_insert_attachment($attachment, $image_path);
                    $attach_data = wp_generate_attachment_metadata($attach_id, $image_path);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    // update order meta
                    update_post_meta(
                        (int) $attach_id,
                        'wpmf_order',
                        0
                    );
                    // set file to folder
                    if (!empty($folder)) {
                        wp_set_object_terms((int) $attach_id, $folder, WPMF_GALLERY_ADDON_TAXO, true);
                        update_post_meta((int)$attach_id, 'wpmf_gallery_order', 0);
                        $relationships = get_option('wpmfgrl_relationships');
                        wp_set_object_terms(
                            (int)$attach_id,
                            (int)$relationships[(int)$folder],
                            WPMF_TAXO,
                            true
                        );
                    }


                    /**
                     * Create remote video file
                     *
                     * @param integer       Created attachment ID
                     * @param integer|array Target        folder
                     * @param array         Extra informations
                     *
                     * @ignore Hook already documented
                     */
                    do_action('wpmf_add_attachment', $attach_id, $folder, array('type' => 'import_google_photo'));
                }
            }
        }

        if (empty($return['pageToken'])) {
            wp_send_json(array('status' => true, 'continue' => false, 'msg' => __('Import success!', 'wpmfAddon')));
        } else {
            wp_send_json(array('status' => true, 'continue' => true, 'pageToken' => $return['pageToken']));
        }
    }

    /**
     * Import album to media library
     *
     * @return void
     */
    public function albumImportToLibrary()
    {
        if (empty($_POST['wpmf_nonce'])
            || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
            die();
        }

        set_time_limit(0);
        $albumId = (isset($_POST['albumId'])) ? $_POST['albumId'] : '';
        $pageToken = (isset($_POST['pageToken'])) ? $_POST['pageToken'] : '';
        if ($albumId === '') {
            wp_send_json(array('status' => false));
        }

        if ($albumId === 'photos') {
            $return = $this->getAllMediaItems($pageToken, 2);
            $files = $return['photos'];
        } else {
            $return = $this->getMediaItemsByAlbumId($albumId, 2, $pageToken);
            $files = $return['photos'];
        }

        // stop if empty files
        if (empty($files)) {
            wp_send_json(array('status' => true, 'continue' => false, 'msg' => esc_html__('Import success!', 'wpmfAddon')));
        }

        // get parent for case import album
        $created_album = (!empty($_POST['created_album'])) ? true : false;
        if ($created_album) {
            $albumCreatedId = (int) $_POST['folder'];
        } else {
            $album_title = (isset($_POST['album_title'])) ? $_POST['album_title'] : esc_html__('New album', 'wpmfAddon');
            $folder = (isset($_POST['folder'])) ? $_POST['folder'] : 0;
            $inserted = wp_insert_term($album_title, WPMF_TAXO, array('parent' => $folder));
            if (is_wp_error($inserted)) {
                wp_send_json(array('status' => false, 'msg' => $inserted->get_error_message()));
            } else {
                $albumCreatedId = (int) $inserted['term_id'];
            }
        }

        $upload_dir = wp_upload_dir();
        $upload_folder = $upload_dir['path'];
        foreach ($files as $file) {
            $full_link = $file->baseUrl . '=w' . $file->mediaMetadata->width . '-h' . $file->mediaMetadata->height;
            $gets = wp_remote_get($full_link);
            if (!empty($gets)) {
                $content = $gets['body'];
                $pathinfo = pathinfo($file->filename); // get info thumbnail
                $title          = sanitize_title($pathinfo['filename']); // get title video
                $ext            = $pathinfo['extension'];

                if (file_exists($upload_folder . '/' . $title . '.' . $ext)) {
                    $fname = wp_unique_filename($upload_folder, $title . '.' . $ext);
                    $upload        = file_put_contents($upload_folder . '/' . $fname, $content);
                } else {
                    $fname = $title . '.' . $ext;
                    $upload        = file_put_contents($upload_folder . '/' . $fname, $content);
                }

                // upload images
                if ($upload) {
                    $attachment = array(
                        'guid'           => $upload_dir['url'] . '/' . $fname,
                        'post_mime_type' => $file->mimeType,
                        'post_title'     => $title
                    );

                    $image_path = $upload_folder . '/' . $fname;
                    $attach_id  = wp_insert_attachment($attachment, $image_path);
                    $attach_data = wp_generate_attachment_metadata($attach_id, $image_path);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    // update order meta
                    update_post_meta(
                        (int) $attach_id,
                        'wpmf_order',
                        0
                    );

                    // set file to folder
                    if (!empty($albumCreatedId)) {
                        wp_set_object_terms((int) $attach_id, $albumCreatedId, WPMF_TAXO);
                    }

                    /**
                     * Create remote video file
                     *
                     * @param integer       Created attachment ID
                     * @param integer|array Target        folder
                     * @param array         Extra informations
                     *
                     * @ignore Hook already documented
                     */
                    do_action('wpmf_add_attachment', $attach_id, $albumCreatedId, array('type' => 'import_google_photo'));
                }
            }
        }

        if (empty($return['pageToken'])) {
            wp_send_json(array('status' => true, 'continue' => false, 'msg' => __('Import success!', 'wpmfAddon')));
        } else {
            wp_send_json(array('status' => true, 'continue' => true, 'albumCreatedId' => $albumCreatedId, 'pageToken' => $return['pageToken']));
        }
    }

    /**
     * Import selected photos to gallery
     *
     * @return void
     */
    public function googlePhotoGalleryImport()
    {
        if (empty($_POST['wpmf_nonce'])
            || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
            die();
        }

        set_time_limit(0);
        $files = (isset($_POST['files'])) ? explode(',', $_POST['files']) : array();
        $mimeTypes = (isset($_POST['mimeTypes'])) ? explode(',', $_POST['mimeTypes']) : array();
        $filenames = (isset($_POST['filenames'])) ? explode(',', $_POST['filenames']) : array();

        $page = (isset($_POST['page'])) ? (int) $_POST['page'] : 0;
        $limit = 5;
        $offset = $page * $limit;
        $files = array_slice($files, $offset, $limit);
        // stop if empty files
        if (empty($files)) {
            wp_send_json(array('status' => true, 'continue' => false, 'msg' => __('Import success!', 'wpmfAddon')));
        }

        $folder = (isset($_POST['folder'])) ? (int) $_POST['folder'] : 0;
        $upload_dir = wp_upload_dir();
        $upload_folder = $upload_dir['path'];
        foreach ($files as $index => $file) {
            $gets = wp_remote_get($file);
            if (!empty($gets)) {
                $content = $gets['body'];
                $pathinfo = pathinfo($filenames[$index]); // get info thumbnail
                $title          = sanitize_title($pathinfo['filename']); // get title video
                $ext            = $pathinfo['extension'];

                if (file_exists($upload_folder . '/' . $title . '.' . $ext)) {
                    $fname = wp_unique_filename($upload_folder, $title . '.' . $ext);
                    $upload        = file_put_contents($upload_folder . '/' . $fname, $content);
                } else {
                    $fname = $title . '.' . $ext;
                    $upload        = file_put_contents($upload_folder . '/' . $fname, $content);
                }

                // upload images
                if ($upload) {
                    $attachment = array(
                        'guid'           => $upload_dir['url'] . '/' . $fname,
                        'post_mime_type' => $mimeTypes[$index],
                        'post_title'     => $title
                    );

                    $image_path = $upload_folder . '/' . $fname;
                    $attach_id  = wp_insert_attachment($attachment, $image_path);
                    $attach_data = wp_generate_attachment_metadata($attach_id, $image_path);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    // update order meta
                    update_post_meta(
                        (int) $attach_id,
                        'wpmf_order',
                        0
                    );
                    // set file to folder
                    if (!empty($folder)) {
                        wp_set_object_terms((int) $attach_id, $folder, WPMF_GALLERY_ADDON_TAXO, true);
                        update_post_meta((int)$attach_id, 'wpmf_gallery_order', 0);
                        $relationships = get_option('wpmfgrl_relationships');
                        wp_set_object_terms(
                            (int)$attach_id,
                            (int)$relationships[(int)$folder],
                            WPMF_TAXO,
                            true
                        );
                    }

                    /**
                     * Create remote video file
                     *
                     * @param integer       Created attachment ID
                     * @param integer|array Target        folder
                     * @param array         Extra informations
                     *
                     * @ignore Hook already documented
                     */
                    do_action('wpmf_add_attachment', $attach_id, $folder, array('type' => 'import_google_photo'));
                }
            }
        }

        wp_send_json(array('status' => true, 'continue' => true, 'msg' => __('Import success!', 'wpmfAddon')));
    }
}
