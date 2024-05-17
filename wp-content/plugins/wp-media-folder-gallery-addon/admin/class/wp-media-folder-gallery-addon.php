<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Class WpmfGlrAddonAdmin
 * This class that holds most of the admin functionality for WP Media Folder Gallery
 */
class WpmfGlrAddonAdmin
{
    /**
     * WpmfGlrAddonAdmin constructor.
     */
    public function __construct()
    {
        if (is_plugin_active('wp-media-folder/wp-media-folder.php')) {
            add_action('init', array($this, 'init'), 1);
            add_action('admin_init', array($this, 'setupTinyMce'));
            if (!get_option('wpmf_gallery_import_cover', false)) {
                add_action('admin_init', array($this, 'importGalleryCover'));
            }
            add_action('admin_menu', array($this, 'addMenuPage'));
            add_action('admin_enqueue_scripts', array($this, 'register'));
            add_action('enqueue_block_editor_assets', array($this, 'addEditorAssets'), 9999);
            add_action('wp_enqueue_media', array($this, 'postEnqueue'));
            add_action('media_upload_wpmfgallery', array($this, 'mediaUploadWpmfgallery'));
            add_filter('media_upload_tabs', array($this, 'addUploadTab'));
            add_filter('wpmfgallery_settings', array($this, 'gallerySettings'), 10, 1);
            add_filter('wpmfgallery_shortcode', array($this, 'renderGalleryShortcode'), 10, 1);
            add_action('wp_ajax_wpmfgallery', array($this, 'startProcess'));
            add_action('wp_ajax_wpmf_load_gallery_html', array($this, 'loadGalleryHtml'));
        }
    }

    /**
     * Import gallery cover
     *
     * @return void
     */
    public function importGalleryCover()
    {
        add_option('wpmf_gallery_import_cover', 1);
        $galleries = get_categories(
            array(
                'hide_empty' => false,
                'taxonomy' => WPMF_GALLERY_ADDON_TAXO
            )
        );

        foreach ($galleries as $gallery) {
            $feature_image_id = get_term_meta($gallery->term_id, 'wpmf_gallery_feature_image', true);
            if (empty($feature_image_id)) {
                $galleries = get_option('wpmf_galleries');
                if (empty($galleries[$gallery->term_id])) {
                    continue;
                }

                $params = $galleries[$gallery->term_id];
                $tax_query = wpmfGalleryAddonGetTaxQuery($gallery->term_id);
                $args = array(
                    'posts_per_page' => -1,
                    'post_status'    => 'any',
                    'post_type' => 'attachment',
                    'post_mime_type' => wpmfGalleryAddonGetImageType(),
                    'tax_query'      => $tax_query
                );

                if ($params['wpmf_orderby'] !== 'post__in') {
                    $args['posts_per_page'] = 1;
                    $args['orderby'] = $params['wpmf_orderby'];
                    $args['order']   = $params['wpmf_order'];
                    $query        = new WP_Query($args);
                    $_attachments = $query->get_posts();
                    if (!empty($_attachments)) {
                        $cover_id = $_attachments[0]->ID;
                        update_term_meta($gallery->term_id, 'wpmf_gallery_feature_image', $cover_id);
                    }
                } else {
                    $query        = new WP_Query($args);
                    $_attachments = $query->get_posts();
                    if (!empty($_attachments)) {
                        $attachments  = array();
                        foreach ($_attachments as &$val) {
                            $order = get_post_meta((int)$val->ID, 'wpmf_gallery_'. $gallery->term_id .'_order', true);
                            $val->order = (int) $order;
                            $attachments[] = $val;
                        }

                        if ($params['wpmf_orderby'] === 'post__in') {
                            usort($attachments, 'wpmfSortByOrder');
                        }

                        $cover_id = $attachments[0]->ID;
                        update_term_meta($gallery->term_id, 'wpmf_gallery_feature_image', $cover_id);
                    }
                }
            }
        }
    }

    /**
     * Customize Tiny MCE Editor
     *
     * @return void
     */
    public function setupTinyMce()
    {
        /**
         * Filter check capability of current user to edit posts
         *
         * @param boolean The current user has the given capability
         * @param string  Action name
         *
         * @return boolean
         *
         * @ignore Hook already documented
         */
        $wpmf_capability = apply_filters('wpmf_user_can', current_user_can('edit_posts'), 'edit_posts');

        /**
         * Filter check capability of current user to edit pages
         *
         * @param boolean The current user has the given capability
         * @param string  Action name
         *
         * @return boolean
         *
         * @ignore Hook already documented
         */
        $wpmf_capability_1 = apply_filters('wpmf_user_can', current_user_can('edit_pages'), 'edit_pages');

        if ($wpmf_capability && $wpmf_capability_1) {
            add_filter('mce_external_plugins', array($this, 'filterMcePlugin'));
            add_filter('mce_css', array($this, 'pluginMceCss'));
        }
    }

    /**
     * Load plugin text domain
     *
     * @return void
     */
    public function init()
    {
        load_plugin_textdomain(
            'wp-media-folder-gallery-addon',
            false,
            dirname(plugin_basename(WPMF_GALLERY_ADDON_FILE)) . '/languages/'
        );
    }

    /**
     * Run ajax
     *
     * @return void
     */
    public function startProcess()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            die();
        }

        if (isset($_REQUEST['task'])) {
            switch ($_REQUEST['task']) {
                case 'change_gallery':
                    $this->changeGallery();
                    break;
                case 'get_library_tree':
                    $this->getLibraryTree();
                    break;
                case 'import_images_from_wp':
                    $this->importImagesFromWp();
                    break;
                case 'create_gallery':
                    $this->createGallery();
                    break;
                case 'delete_gallery':
                    $this->deleteGallery();
                    break;
                case 'edit_gallery':
                    $this->editGallery();
                    break;
                case 'save_custom_grid_styles':
                    $this->saveCustomGridStyles();
                    break;
                case 'delete_imgs_selected':
                    $this->deleteImgsSelected();
                    break;
                case 'item_details':
                    $this->itemDetails();
                    break;
                case 'image_selection_delete':
                    $this->imageSelectionDelete();
                    break;
                case 'update_gallery_item':
                    $this->updateGalleryItem();
                    break;
                case 'gallery_uploadfile':
                    $this->galleryUploadFile();
                    break;
                case 'get_imgselection':
                    $this->getImgSelectionNav();
                    break;
                case 'update_img_per_page':
                    $this->updateImgPerpage();
                    break;
                case 'update_parent_gallery':
                    $this->updateParentGallery();
                    break;
                case 'reorder_image_gallery':
                    $this->reorderFile();
                    break;
                case 'reorder_gallery':
                    $this->reorderGallery();
                    break;
                case 'move_gallery':
                    $this->moveGallery();
                    break;
                case 'wpmf_get_gallery_folders':
                    $this->getGalleryFolders();
                    break;
                case 'wpmf_import_gallery_folders':
                    $this->importGalleryFolders();
                    break;
                case 'wpmf_gallery_set_feature_image':
                    $this->gallerySetFeatureImage();
                    break;
                case 'load_gallery_preview':
                    $this->loadGalleryPreview();
                    break;
                case 'add_video':
                    $this->addVideoToGallery();
                    break;

                case 'load_video_thumbnail':
                    $this->loadVideoThumbnail();
                    break;
                case 'auto_load_video_thumbnail':
                    $this->autoLoadVideoThumbnail();
                    break;
            }
        }
    }

    /**
     * Import WPMF categories to Gallery
     *
     * @return void
     */
    public function getGalleryFolders()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            die();
        }

        global $wpdb;
        if (!get_option('wpmf_categories_list', false)) {
            add_option('wpmf_categories_list', array('0' => 0));
        }
        if (!empty($_POST['first'])) {
            $termsRel = array('0' => 0);
        } else {
            $termsRel = get_option('wpmf_categories_list', true);
        }
        $paged = (isset($_POST['paged'])) ? (int) $_POST['paged'] : 1;
        $limit = 30;
        $offset = ($paged - 1) * $limit;
        $ids = (isset($_POST['ids'])) ? $_POST['ids'] : '';
        $theme = $this->getTheme($_POST['theme']);
        // if not selected then stop
        if (empty($ids)) {
            wp_send_json(array('status' => true, 'continue' => false));
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Variable has been prepare
        $wpmf_categories = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $wpdb->terms . ' as t INNER JOIN ' . $wpdb->term_taxonomy . ' AS tt ON tt.term_id = t.term_id WHERE taxonomy = %s AND t.term_id IN ('. $ids .') LIMIT %d OFFSET %d', array(WPMF_TAXO, (int) $limit, (int) $offset)));
        if (empty($wpmf_categories)) {
            wp_send_json(array('status' => true, 'continue' => false));
        }

        $galleries = get_option('wpmf_galleries');
        foreach ($wpmf_categories as $wpmf_category) {
            $inserted = wp_insert_term(
                $wpmf_category->name,
                WPMF_GALLERY_ADDON_TAXO,
                array('slug' => wp_unique_term_slug($wpmf_category->slug, $wpmf_category))
            );
            if (!is_wp_error($inserted)) {
                $termsRel[$wpmf_category->term_id] = array('id' => $inserted['term_id'], 'name' => $wpmf_category->name, 'term_parent' => $wpmf_category->parent);
                if (empty($galleries) && !is_array($galleries)) {
                    $galleries = array();
                    $galleries[$inserted['term_id']] = array(
                        'gallery_id' => $inserted['term_id'],
                        'theme' => $theme,
                        'auto_from_folder' => 1,
                        'folder' => $wpmf_category->term_id
                    );
                } else {
                    $galleries[$inserted['term_id']] = array(
                        'gallery_id' => $inserted['term_id'],
                        'theme' => $theme,
                        'auto_from_folder' => 1,
                        'folder' => $wpmf_category->term_id
                    );
                }
                update_term_meta((int) $inserted['term_id'], 'wpmf_theme', $theme);
                /* set option wpmf_galleries to relative gallery id with theme */
                update_option('wpmf_galleries', $galleries);
            }
        }
        update_option('wpmf_categories_list', $termsRel);
        wp_send_json(array('status' => true, 'continue' => true));
    }

    /**
     * Update parent for new imported folder from WPMF category
     *
     * @return void
     */
    public function importGalleryFolders()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            die();
        }

        $termsRel = get_option('wpmf_categories_list', true);
        $paged = (isset($_POST['paged'])) ? (int) $_POST['paged'] : 1;
        $limit = 5;
        $offset = ($paged - 1) * $limit;
        $categories = array_slice($termsRel, $offset, $limit, true);
        if (empty($categories)) {
            update_option('wpmf_categories_list', array('0' => 0));
            wp_send_json(array('status' => true, 'continue' => false));
        }

        global $wpdb;
        foreach ($categories as $term_id => $category) {
            wp_update_term($termsRel[$term_id]['id'], WPMF_GALLERY_ADDON_TAXO, array('parent' => (int) $termsRel[$category['term_parent']]['id']));
            $attachment = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . $wpdb->term_relationships . ' WHERE term_taxonomy_id = %d LIMIT 1', array((int) $term_id)));
            update_term_meta($termsRel[$term_id]['id'], 'wpmf_gallery_feature_image', $attachment->object_id);
        }
        wp_send_json(array('status' => true, 'continue' => true));
    }

    /**
     * Set gallery feature image
     *
     * @return void
     */
    public function gallerySetFeatureImage()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            die();
        }

        $image_id = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 0;
        $gallery_id = isset($_POST['gallery_id']) ? (int)$_POST['gallery_id'] : 0;

        if ((int)$image_id === 0 || (int)$gallery_id === 0) {
            wp_send_json(array('status' => false));
        }

        update_term_meta($gallery_id, 'wpmf_gallery_feature_image', $image_id);
        wp_send_json(array('status' => true));
    }

    /**
     * Ajax load gallery preview
     *
     * @return void
     */
    public function loadGalleryPreview()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            die();
        }
        $shortcode = (isset($_POST['shortcode'])) ? $_POST['shortcode'] : '';
        if ($shortcode === '') {
            wp_send_json(array('status' => false));
        }

        $html = do_shortcode(stripslashes($shortcode), true);
        wp_send_json(array('status' => true, 'html' => $html));
    }

    /**
     * Add video to Gallery
     *
     * @return void
     */
    public function addVideoToGallery()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            die();
        }

        $video_url = (isset($_POST['video_url'])) ? $_POST['video_url'] : '';
        $id_gallery = (isset($_POST['id_gallery'])) ? (int)$_POST['id_gallery'] : 0;
        $thumbnail_id = (isset($_POST['thumbnail_id'])) ? (int)$_POST['thumbnail_id'] : 0;
        if (empty($video_url) || empty($id_gallery)) {
            wp_send_json(array('status' => false));
        }
        $mainClass = wpmfGetMainClass();
        $video_library_id = $mainClass->doCreateVideo($video_url, $thumbnail_id, 'video_to_gallery');
        if ($video_library_id) {
            wp_set_object_terms((int)$video_library_id, (int)$id_gallery, WPMF_GALLERY_ADDON_TAXO, true);
            wp_send_json(array('status' => true));
        }
        wp_send_json(array('status' => false));
    }

    /**
     * Load video to thumbnail
     *
     * @return void
     */
    public function loadVideoThumbnail()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            die();
        }

        $video_url = (isset($_POST['video_url'])) ? $_POST['video_url'] : '';
        if (empty($video_url)) {
            wp_send_json(array('status' => false));
        }
        $mainClass = wpmfGetMainClass();
        $video_library_id = $mainClass->getVideoThumbnail($video_url);
        if ($video_library_id) {
            wp_send_json(array('status' => true));
        }
        wp_send_json(array('status' => false));
    }

    /**
     * Auto load video thumbnail
     *
     * @return void
     */
    public function autoLoadVideoThumbnail()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            die();
        }

        $thumb_url = '';
        $video_url = (isset($_POST['video_url'])) ? $_POST['video_url'] : '';
        $mainClass = wpmfGetMainClass();
        if (!preg_match($mainClass->vimeo_pattern, $video_url, $output_array)
            && !preg_match('/(youtube.com|youtu.be)\/(watch)?(\?v=)?(\S+)?/', $video_url, $match)
            && !preg_match('/\b(?:dailymotion)\.com\b/i', $video_url, $vresult)) {
            wp_send_json(array('status' => false));
        } elseif (preg_match($mainClass->vimeo_pattern, $video_url, $output_array)) {
            // for vimeo
            $id = $mainClass->getVimeoVideoIdFromUrl($video_url);
            $videos = wp_remote_get('https://player.vimeo.com/video/' . $id . '/config');
            $body = json_decode($videos['body']);
            if (!empty($body->video->thumbs->base)) {
                $thumb_url = $body->video->thumbs->base;
            } else {
                $videos = wp_remote_get('https://vimeo.com/api/v2/video/' . $id . '.json');
                $body = json_decode($videos['body']);
                $body = $body[0];
                $thumb_url = '';
                if (isset($body->thumbnail_large)) {
                    $thumb_url = $body->thumbnail_large;
                } elseif (isset($body->thumbnail_medium)) {
                    $thumb_url = $body->thumbnail_large;
                } elseif (isset($body->thumbnail_small)) {
                    $thumb_url = $body->thumbnail_small;
                }
            }
        } elseif (preg_match('/(youtube.com|youtu.be)\/(watch)?(\?v=)?(\S+)?/', $video_url, $match)) {
            // for youtube
            $parts = parse_url($video_url);
            if ($parts['host'] === 'youtu.be') {
                $id = trim($parts['path'], '/');
            } else {
                parse_str($parts['query'], $query);
                $id = $query['v'];
            }

            $thumb_url = 'http://img.youtube.com/vi/' . $id . '/maxresdefault.jpg';
            $gets = wp_remote_get($thumb_url);
            if (!empty($gets) && $gets['response']['code'] !== 200) {
                $thumb_url = 'http://img.youtube.com/vi/' . $id . '/sddefault.jpg';
                $gets = wp_remote_get($thumb_url);
            }

            if (!empty($gets) && $gets['response']['code'] !== 200) {
                $thumb_url = 'http://img.youtube.com/vi/' . $id . '/hqdefault.jpg';
                $gets = wp_remote_get($thumb_url);
            }

            if (!empty($gets) && $gets['response']['code'] !== 200) {
                $thumb_url = 'http://img.youtube.com/vi/' . $id . '/mqdefault.jpg';
                $gets = wp_remote_get($thumb_url);
            }

            if (!empty($gets) && $gets['response']['code'] !== 200) {
                $thumb_url = 'http://img.youtube.com/vi/' . $id . '/default.jpg';
            }
        } elseif (preg_match('/\b(?:dailymotion)\.com\b/i', $video_url, $vresult)) {
            // for dailymotion
            $id   = $mainClass->getDailymotionVideoIdFromUrl($video_url);
            $gets = wp_remote_get('http://www.dailymotion.com/services/oembed?format=json&url=http://www.dailymotion.com/embed/video/' . $id);
            $info = json_decode($gets['body'], true);
            if (empty($info)) {
                wp_send_json(array('status' => false));
            }

            if (!empty($info['thumbnail_url'])) {
                $thumb_url = $info['thumbnail_url'];
            }
        }

        if (!empty($thumb_url)) {
            wp_send_json(array('status' => true, 'thumb_url' => $thumb_url));
        }
        wp_send_json(array('status' => false));
    }

    /**
     * Load external TinyMCE plugins.
     *
     * @param array $plugins List TinyMCE plugins
     *
     * @return mixed
     */
    public function filterMcePlugin($plugins)
    {
        $plugins['wpmfglr'] = plugins_url('assets/js/tmce_plugin.js', WPMF_GALLERY_ADDON_FILE);
        return $plugins;
    }

    /**
     * Load tinyMCE plugin css
     *
     * @param string $mce_css Css
     *
     * @return string
     */
    public function pluginMceCss($mce_css)
    {
        if (!empty($mce_css)) {
            $mce_css .= ',';
        }
        $mce_css .= plugins_url('assets/css/tmce_plugin.css', WPMF_GALLERY_ADDON_FILE);
        return $mce_css;
    }

    /**
     * Add a tab to media menu in iframe
     *
     * @param array $tabs An array of media tabs
     *
     * @return array
     */
    public function addUploadTab($tabs)
    {
        global $current_screen;
        if (!empty($current_screen)) {
            if (!method_exists($current_screen, 'is_block_editor') || !$current_screen->is_block_editor()) {
                $newtab = array('wpmfgallery' => __('WP Media Folder Gallery', 'wp-media-folder-gallery-addon'));
                return array_merge($tabs, $newtab);
            }
        }

        return $tabs;
    }

    /**
     * Create iframe
     *
     * @return void
     */
    public function mediaUploadWpmfgallery()
    {
        $errors = false;
        wp_iframe(array($this, 'mediaUploadWpmfgalleryForm'), $errors);
    }

    /**
     * Load html iframe
     *
     * @return void
     */
    public function mediaUploadWpmfgalleryForm()
    {
        $this->enqueue();
        $type = 'iframe';
        require_once(WPMF_GALLERY_ADDON_PLUGIN_DIR . '/admin/pages/gallerylists.php');
        if (!class_exists('_WP_Editors', false)) {
            require_once ABSPATH . 'wp-includes/class-wp-editor.php';
            _WP_Editors::wp_link_dialog();
        }
    }

    /**
     * Load scripts
     *
     * @return void
     */
    public function postEnqueue()
    {
        wp_enqueue_script(
            'wpmf_btn_asgallery',
            WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/js/btn_save_asgallery.js',
            array('jquery'),
            WPMF_GALLERY_ADDON_VERSION
        );
        wp_localize_script('wpmf_btn_asgallery', 'wpmf_btn_asgallery', array(
            'btn_save_as_gallery' => __('Save as WPMF gallery', 'wp-media-folder-gallery-addon'),
            'new_gallery' => __('New gallery', 'wp-media-folder-gallery-addon'),
            'wpmf_gallery_nonce' => wp_create_nonce('wpmf_gallery_nonce'),
        ));
    }

    /**
     * Load script for gallery preview
     *
     * @return void
     */
    public function loadScriptPreview()
    {
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
            WPMF_PLUGIN_URL . '/assets/css/display-gallery/style-display-gallery.css',
            array(),
            WPMF_VERSION
        );

        wp_register_script(
            'wpmf-flipster-js',
            WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/js/jquery.flipster.js',
            array('jquery'),
            WPMF_GALLERY_ADDON_VERSION,
            true
        );

        wp_register_style(
            'wpmf-flipster-css',
            WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/css/jquery.flipster.css',
            array(),
            WPMF_GALLERY_ADDON_VERSION
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

        wp_register_script('wpmfisotope', WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/js/isotope.pkgd.js', array('jquery'), WPMF_GALLERY_ADDON_VERSION, true);
        wp_register_script('wpmfpackery', WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/packery/packery.pkgd.min.js', array('jquery'), WPMF_GALLERY_ADDON_VERSION, true);
        wp_register_script(
            'wpmf-gallery-tree-js',
            WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/js/gallery_navigation_front.js',
            array('jquery'),
            WPMF_GALLERY_ADDON_VERSION
        );

        wp_register_style(
            'wpmf-gallery-css',
            WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/css/gallery.css',
            array(),
            WPMF_GALLERY_ADDON_VERSION
        );
    }

    /**
     * Load scripts and style
     *
     * @return void
     */
    public function register()
    {
        global $pagenow, $current_screen;
        $this->loadScriptPreview();
        wp_register_script(
            'wordpresscanvas-imagesloaded',
            WPMF_PLUGIN_URL . '/assets/js/display-gallery/imagesloaded.pkgd.min.js',
            array(),
            '3.1.5',
            true
        );

        wp_register_script(
            'wpmf-galleryaddon-jquery-form',
            WPMF_PLUGIN_URL . 'assets/js/jquery.form.js',
            array('jquery'),
            WPMF_VERSION
        );

        wp_register_script(
            'wpmf-glraddon-popup',
            WPMF_PLUGIN_URL . '/assets/js/display-gallery/jquery.magnific-popup.min.js',
            array('jquery'),
            '0.9.9',
            true
        );

        wp_register_style(
            'wpmf-glraddon-popup-style',
            WPMF_PLUGIN_URL . '/assets/css/display-gallery/magnific-popup.css',
            array(),
            '0.9.9'
        );

        wp_register_style(
            'wpmf-import-gallery-style',
            WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/css/import-gallery.css',
            array(),
            WPMF_GALLERY_ADDON_VERSION
        );

        wp_register_script(
            'wpmf-glraddon-library_tree',
            WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/js/import-gallery.js',
            array('jquery'),
            WPMF_GALLERY_ADDON_VERSION
        );

        wp_register_script(
            'wpmf-glraddon-script',
            WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/js/script.js',
            array('jquery', 'plupload'),
            WPMF_GALLERY_ADDON_VERSION
        );

        wp_register_script(
            'wpmfgallery-scrollbar',
            WPMF_PLUGIN_URL . '/assets/js/scrollbar/jquery.scrollbar.min.js',
            array('jquery'),
            WPMF_GALLERY_ADDON_VERSION
        );

        wp_register_style(
            'wpmfgallery-scrollbar',
            WPMF_PLUGIN_URL . '/assets/js/scrollbar/jquery.scrollbar.css',
            array(),
            WPMF_GALLERY_ADDON_VERSION
        );

        wp_register_script(
            'wpmf-gallery-tree',
            WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/js/gallery_tree.js',
            array('jquery', 'wpmf-glraddon-script'),
            WPMF_GALLERY_ADDON_VERSION
        );

        wp_register_style(
            'wpmf-glraddon-style',
            WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/css/style.css',
            array(),
            WPMF_GALLERY_ADDON_VERSION
        );

        wp_register_style(
            'wpmf-glraddon-justyle',
            WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/css/justyle.css',
            array(),
            WPMF_GALLERY_ADDON_VERSION
        );

        wp_register_style(
            'wpmf-style-tippy',
            WPMF_PLUGIN_URL . 'assets/js/tippy/tippy.css',
            array(),
            WPMF_VERSION
        );

        wp_register_script(
            'wpmf-tippy-core',
            WPMF_PLUGIN_URL . 'assets/js/tippy/tippy-core.js',
            array('jquery'),
            WPMF_VERSION
        );

        wp_register_script(
            'wpmf-tippy',
            WPMF_PLUGIN_URL . 'assets/js/tippy/tippy.js',
            array('jquery'),
            WPMF_VERSION
        );
    }

    /**
     * Load scripts and style.
     *
     * @return void
     */
    public function enqueue()
    {
        wp_enqueue_media();
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-resizable');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-droppable');
        wp_enqueue_script('wpmf-galleryaddon-jquery-form');
        wp_enqueue_script('wordpresscanvas-imagesloaded');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style(
            'wpmf-material-icon',
            plugins_url('/assets/css/google-material-icon.css', dirname(__FILE__)),
            array(),
            WPMF_VERSION
        );

        wp_enqueue_script(
            'jQuery.fileupload',
            WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/fileupload/jquery.fileupload.js',
            array('jquery'),
            false,
            true
        );

        wp_enqueue_script(
            'jQuery.fileupload-process',
            WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/fileupload/jquery.fileupload-process.js',
            array('jquery'),
            false,
            true
        );

        // load preview assets
        wp_enqueue_script('jquery-masonry');
        wp_enqueue_style('wpmf-slick-style');
        wp_enqueue_style('wpmf-slick-theme-style');
        wp_enqueue_script('wpmf-slick-script');
        wp_enqueue_style('wpmf-gallery-style');
        wp_enqueue_script('wpmf-flipster-js');
        wp_enqueue_style('wpmf-flipster-css');
        wp_enqueue_style('wpmf-justified-style');
        wp_enqueue_script('wpmf-justified-script');
        wp_enqueue_script('wpmfisotope');
        wp_enqueue_script('wpmfpackery');
        wp_enqueue_script('wpmf-gallery-tree-js');
        wp_enqueue_style('wpmf-gallery-css');
        // end load preview assets

        wp_enqueue_script('wpmf-tippy-core');
        wp_enqueue_script('wpmf-tippy');
        wp_enqueue_style('wpmf-style-tippy');
        wp_enqueue_script('wpmfgallery-scrollbar');
        wp_enqueue_style('wpmfgallery-scrollbar');
        wp_enqueue_style('wpmf-glraddon-justyle');
        wp_enqueue_style('wpmf-import-gallery-style');
        wp_enqueue_script('wpmf-glraddon-library_tree');
        wp_enqueue_script('wpmf-glraddon-script');
        wp_enqueue_script('wpmf-gallery-tree');
        wp_enqueue_style('wpmf-glraddon-style');
        wp_enqueue_script('wpmf-glraddon-popup');
        wp_enqueue_style('wpmf-glraddon-popup-style');
        wp_localize_script(
            'wpmf-glraddon-script',
            'wpmf_glraddon',
            $this->localizeScript()
        );

        if (isset($_GET['noheader'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
            wp_enqueue_style(
                'wpmf-glraddon-form',
                WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/css/form.css',
                array(),
                WPMF_VERSION
            );

            wp_enqueue_style(
                'wpmf-glraddon-common',
                WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/css/common.css',
                array(),
                WPMF_VERSION
            );
        }
    }

    /**
     * Enqueue styles and scripts for gutenberg
     *
     * @return void
     */
    public function addEditorAssets()
    {
        wp_enqueue_script('jquery-masonry');

        wp_enqueue_style(
            'wpmf-justified-style',
            WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/js/justified-gallery/justifiedGallery.min.css',
            array(),
            WPMF_GALLERY_ADDON_VERSION
        );

        wp_enqueue_script(
            'wpmf-justified-script',
            WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/js/justified-gallery/jquery.justifiedGallery.min.js',
            array('jquery'),
            WPMF_GALLERY_ADDON_VERSION,
            true
        );

        wp_enqueue_script('wpmfisotope', WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/js/isotope.pkgd.js', array('jquery'), WPMF_GALLERY_ADDON_VERSION, true);
        wp_enqueue_script('wpmfpackery', WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/packery/packery.pkgd.min.js', array('jquery'), WPMF_GALLERY_ADDON_VERSION, true);

        wp_enqueue_style(
            'wpmf-slick-style',
            WPMF_PLUGIN_URL . 'assets/js/slick/slick.css',
            array(),
            WPMF_VERSION
        );

        wp_enqueue_style(
            'wpmf-slick-theme-style',
            WPMF_PLUGIN_URL . 'assets/js/slick/slick-theme.css',
            array(),
            WPMF_VERSION
        );

        wp_enqueue_script(
            'wpmf-slick-script',
            WPMF_PLUGIN_URL . 'assets/js/slick/slick.min.js',
            array('jquery'),
            WPMF_VERSION,
            true
        );

        wp_enqueue_style(
            'wpmf-gallery-style',
            WPMF_PLUGIN_URL . '/assets/css/display-gallery/style-display-gallery.css',
            array(),
            WPMF_VERSION
        );

        wp_enqueue_script(
            'wpmf-flipster-js',
            WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/js/jquery.flipster.js',
            array('jquery'),
            WPMF_GALLERY_ADDON_VERSION,
            true
        );

        wp_enqueue_style(
            'wpmf-flipster-css',
            WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/css/jquery.flipster.css',
            array(),
            WPMF_GALLERY_ADDON_VERSION
        );

        wp_enqueue_style(
            'wpmf-gallery-css',
            WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/css/gallery.css',
            array(),
            WPMF_GALLERY_ADDON_VERSION
        );

        wp_enqueue_style(
            'wpmf-jaofiletree',
            WPMF_PLUGIN_URL . '/assets/css/jaofiletree.css',
            array(),
            WPMF_VERSION
        );

        wp_enqueue_script(
            'wpmfgallery_blocks',
            WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/blocks/gallery/block.js',
            array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-data', 'wp-editor'),
            WPMF_GALLERY_ADDON_VERSION
        );

        wp_enqueue_style(
            'wpmfgallery_blocks',
            WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/blocks/gallery/style.css',
            array(),
            WPMF_GALLERY_ADDON_VERSION
        );

        $gallery_configs = get_option('wpmf_gallery_settings');
        $themes_setting = get_option('wpmf_galleries');
        $sizes = apply_filters('image_size_names_choose', array(
            'thumbnail' => __('Thumbnail', 'wp-media-folder-gallery-addon'),
            'medium' => __('Medium', 'wp-media-folder-gallery-addon'),
            'large' => __('Large', 'wp-media-folder-gallery-addon'),
            'full' => __('Full Size', 'wp-media-folder-gallery-addon'),
        ));

        $sizes_value = json_decode(get_option('wpmf_gallery_image_size_value'));
        if (!empty($sizes_value)) {
            foreach ($sizes as $k => $size) {
                if (!in_array($k, $sizes_value)) {
                    unset($sizes[$k]);
                }
            }
        }

        $galleries = get_categories(
            array(
                'hide_empty' => false,
                'taxonomy' => WPMF_GALLERY_ADDON_TAXO,
                'pll_get_terms_not_translated' => 1
            )
        );

        $galleries = wpmfParentSort($galleries);
        $params = array(
            'l18n' => array(
                'btnopen' => __('Load WP Media Folder Gallery', 'wp-media-folder-gallery-addon'),
                'gallery_title' => __('WPMF Gallery Addon', 'wp-media-folder-gallery-addon'),
                'select_gallery_title' => __('Select or Create gallery', 'wp-media-folder-gallery-addon'),
                'edit' => __('Edit', 'wp-media-folder-gallery-addon'),
                'remove' => __('Remove', 'wp-media-folder-gallery-addon'),
                'loading' => __('Loading...', 'wp-media-folder-gallery-addon')
            ),
            'vars' => array(
                'admin_gallery_page' => admin_url('upload.php?page=media-folder-galleries&noheader=1&editor=gutenberg'),
                'gallery_configs' => $gallery_configs,
                'themes_setting' => $themes_setting,
                'sizes' => $sizes,
                'galleries' => $galleries,
                'wpmf_gallery_nonce' => wp_create_nonce('wpmf_gallery_nonce'),
                'block_cover' => WPMF_GALLERY_ADDON_PLUGIN_URL .'assets/blocks/gallery/preview.png',
                'ajaxurl' => admin_url('admin-ajax.php')
            )
        );

        wp_localize_script('wpmfgallery_blocks', 'wpmfgalleryblocks', $params);
    }

    /**
     * Get all gallery
     *
     * @return array
     */
    public function getAllGalleries()
    {
        $terms = get_categories(
            array(
                'hide_empty' => false,
                'taxonomy' => WPMF_GALLERY_ADDON_TAXO
            )
        );
        $terms = $this->parentSort($terms);
        $terms_order = array();
        $attachment_terms[] = array(
            'id' => 0,
            'label' => __('WP MEDIA FOLDER GALLERIES', 'wp-media-folder-gallery-addon'),
            'slug' => '',
            'parent_id' => 0
        );
        $terms_order[] = 0;

        foreach ($terms as $term) {
            $order = $this->getOrderGallery($term->term_id);
            $feature_image_id = get_term_meta($term->term_id, 'wpmf_gallery_feature_image', true);
            $feature_image = '';
            if (!empty($feature_image_id)) {
                $feature_image = wp_get_attachment_image_url($feature_image_id, 'thumbnail');
            }
            $attachment_terms[$term->term_id] = array(
                'id' => $term->term_id,
                'label' => $term->name,
                'slug' => $term->slug,
                'parent_id' => $term->category_parent,
                'depth' => $term->depth,
                'order' => $order,
                'feature_image' => ($feature_image) ? $feature_image : ''
            );
            $terms_order[] = $term->term_id;
        }

        return array(
            'terms_order' => $terms_order,
            'attachment_terms' => $attachment_terms
        );
    }

    /**
     * Localize a script
     *
     * @return array
     */
    public function localizeScript()
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
        if (isset($_GET['gallery_id'])) {
            $gallery_id = (int)$_GET['gallery_id'];
        } else {
            $gallery_id = 0;
        }
        // phpcs:enable
        // get all gallery
        $terms = $this->getAllGalleries();
        $attachment_terms = $terms['attachment_terms'];
        $terms_order = $terms['terms_order'];

        $themes = array(
            'default' => __('Default', 'wp-media-folder-gallery-addon'),
            'masonry' => __('Masonry', 'wp-media-folder-gallery-addon'),
            'portfolio' => __('Portfolio', 'wp-media-folder-gallery-addon'),
            'slider' => __('Slider', 'wp-media-folder-gallery-addon'),
            'flowslide' => __('Flow slide', 'wp-media-folder-gallery-addon'),
            'square_grid' => __('Square grid', 'wp-media-folder-gallery-addon'),
            'material' => __('Material', 'wp-media-folder-gallery-addon')
        );

        $l18n = array(
            'root_title' => __('Galleries', 'wp-media-folder-gallery-addon'),
            'create_gallery_desc' => __('Select a folder to create gallery
             and sub-galleries', 'wp-media-folder-gallery-addon'),
            'cancel' => __('Cancel', 'wp-media-folder-gallery-addon'),
            'discard' => __('Discard', 'wp-media-folder-gallery-addon'),
            'edit' => __('Edit thumbnail', 'wp-media-folder-gallery-addon'),
            'delete' => __('Delete', 'wp-media-folder-gallery-addon'),
            'rename' => __('Edit', 'wp-media-folder-gallery-addon'),
            'remove' => __('remove', 'wp-media-folder-gallery-addon'),
            'create' => __('Create', 'wp-media-folder-gallery-addon'),
            'add_video' => __('Add a video', 'wp-media-folder-gallery-addon'),
            'select_from_library' => __('Select from Library', 'wp-media-folder-gallery-addon'),
            'empty_video_thumbnail' => __('Please add a thumbnail for video', 'wp-media-folder-gallery-addon'),
            'question_quit_video_edit' => __('Are you sure you want to quit the video edition?', 'wp-media-folder-gallery-addon'),
            'notification' => __('Notification', 'wp-media-folder-gallery-addon'),
            'item_folder_msg' => sprintf(__('You cannot remove an image when it\'s an automatic gallery based on a folder content. Edit the folder from the %s or change the gallery image source.', 'wp-media-folder-gallery-addon'), '<a target="_blank" href="'. admin_url('upload.php') .'">'. __('media library', 'wp-media-folder-gallery-addon') .'</a>'),
            'or' => __('or', 'wp-media-folder-gallery-addon'),
            'gallery_moving_text'    => __('Moving gallery', 'wp-media-folder-gallery-addon'),
            'moved_gallery' => __('Moved a gallery', 'wp-media-folder-gallery-addon'),
            'empty_url' => __('Please add a video URL', 'wp-media-folder-gallery-addon'),
            'empty_thumbnail' => __('Please add a thumbnail', 'wp-media-folder-gallery-addon'),
            'add_image' => __('Add thumbnail', 'wp-media-folder-gallery-addon'),
            'edit_image' => __('Edit image', 'wp-media-folder-gallery-addon'),
            'video_url' => __('Paste video URL: https://www.youtube.com/watch...', 'wp-media-folder-gallery-addon'),
            'theme_label' => __('Gallery Theme', 'wp-media-folder-gallery-addon'),
            'select_theme_label' => __('Apply theme:', 'wp-media-folder-gallery-addon'),
            'iframe_import_label' => __('Select or upload image to import
             them to image gallery selection', 'wp-media-folder-gallery-addon'),
            'import' => __('Import images', 'wp-media-folder-gallery-addon'),
            'edit_gallery' => __('Edit gallery', 'wp-media-folder-gallery-addon'),
            'error' => __('Error', 'wp-media-folder-gallery-addon'),
            'save' => __('Save', 'wp-media-folder-gallery-addon'),
            'save_settings' => __('Save Settings', 'wp-media-folder-gallery-addon'),
            'save_changes' => __('Save Changes', 'wp-media-folder-gallery-addon'),
            'leave_site_msg_1' => __('Your unsaved changes will be lost.', 'wp-media-folder-gallery-addon'),
            'leave_site_msg_2' => __('Save changes before closing?', 'wp-media-folder-gallery-addon'),
            'delete_image_gallery' => __('Are you sure to want to delete this image?', 'wp-media-folder-gallery-addon'),
            'delete_selected_image' => __('Are you sure to want
             to delete these images?', 'wp-media-folder-gallery-addon'),
            'delete_gallery' => __('Are you sure you want to remove this gallery?', 'wp-media-folder-gallery-addon'),
            'image_details' => __('Image Details', 'wp-media-folder-gallery-addon'),
            'add_gallery' => __('Gallery added', 'wp-media-folder-gallery-addon'),
            'save_img' => __('Images saved', 'wp-media-folder-gallery-addon'),
            'delete_img' => __('Images removed', 'wp-media-folder-gallery-addon'),
            'upload_img' => __('Images uploaded', 'wp-media-folder-gallery-addon'),
            'save_glr' => __('Gallery saved', 'wp-media-folder-gallery-addon'),
            'save_glr_modal' => __('Gallery saved: Insert to apply', 'wp-media-folder-gallery-addon'),
            'delete_glr' => __('Gallery removed', 'wp-media-folder-gallery-addon'),
            'new_gallery' => __('New gallery', 'wp-media-folder-gallery-addon'),
            'import_gallery' => __('Gallery import on the way...', 'wp-media-folder-gallery-addon'),
            'gallery_imported' => __('New gallery imported', 'wp-media-folder-gallery-addon'),
            'reordergallery' => __('New gallery order saved!', 'wp-media-folder-gallery-addon'),
            'gallery_saving' => __('Gallery saving', 'wp-media-folder-gallery-addon'),
            'maxNumberOfFiles' => __('Maximum number of files exceeded', 'wp-media-folder-gallery-addon'),
            'acceptFileTypes' => __('File type not allowed', 'wp-media-folder-gallery-addon'),
            'maxFileSize' => __('File is too large', 'wp-media-folder-gallery-addon'),
            'minFileSize' => __('File is too small', 'wp-media-folder-gallery-addon'),
            'uploading' => __('Uploading', 'wp-media-folder-gallery-addon'),
            'gallery_importing' => __('Gallery importing...', 'wp-media-folder-gallery-addon'),
            'folder_listing' => __('Folders listing...', 'wp-media-folder-gallery-addon'),
            'upload_error' => __('Post-processing of the image failed likely because the server is busy or does not have enough resources. Uploading a smaller image may help. Suggested maximum size is 2500 pixels.', 'wp-media-folder-gallery-addon'),
            'moving_text'    => __('Moving gallery', 'wp-media-folder-gallery-addon'),
            'success_copy_shortcode' => __('Gallery shortcode copied!', 'wp-media-folder-gallery-addon'),
        );

        $vars = array(
            'themes' => $themes,
            'gallery_id' => $gallery_id,
            'wpmf_gallery_nonce' => wp_create_nonce('wpmf_gallery_nonce'),
            'categories' => $attachment_terms,
            'categories_order' => $terms_order,
            'plugin_url_image' => WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/images/',
            'admin_url' => admin_url(),
            'site_url' => site_url()
        );

        return array(
            'l18n' => $l18n,
            'vars' => $vars
        );
    }

    /**
     * Sort parents before children
     * http://stackoverflow.com/questions/6377147/sort-an-array-placing-children-beneath-parents
     *
     * @param array   $objects Input objects with attributes 'id' and 'parent'
     * @param array   $result  Optional, reference) internal
     * @param integer $parent  Parent of gallery
     * @param integer $depth   Depth of gallery
     *
     * @return array           output
     */
    public function parentSort(array $objects, array &$result = array(), $parent = 0, $depth = 0)
    {
        foreach ($objects as $key => $object) {
            if ((int)$object->parent === (int)$parent) {
                $object->depth = $depth;
                array_push($result, $object);
                unset($objects[$key]);
                $this->parentSort($objects, $result, $object->term_id, $depth + 1);
            }
        }
        return $result;
    }

    /**
     * Add menu media page
     *
     * @return void
     */
    public function addMenuPage()
    {
        add_media_page(
            'Media Folder Galleries',
            'Media Folder Galleries',
            'upload_files',
            'media-folder-galleries',
            array($this, 'showGalleryList')
        );
    }

    /**
     * Galleries list page
     *
     * @return void
     */
    public function showGalleryList()
    {
        if (version_compare(WPMF_VERSION, '4.4.2', '<') && WPMF_VERSION !== '2.4.7') {
            echo '<div class="error" id="wpmf_error">';
            echo '<p>';
            esc_html_e('Please update WP Media Folder to 4.4.2+ version
             to use WP Media Folder gallery addon', 'wp-media-folder-gallery-addon');
            echo '</p>';
            echo '</div>';
        } else {
            if (isset($_GET['noheader'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
                global $hook_suffix;
                _wp_admin_html_begin();
                do_action('admin_enqueue_scripts', $hook_suffix);
                do_action('admin_print_scripts-' . $hook_suffix);
                do_action('admin_print_scripts');
                $style = '
                    html.wp-toolbar {
                        padding: 0 !important;
                    }
                ';
                wp_add_inline_style('wpmf-glraddon-style', $style);
            }
            if (isset($_GET['view']) && $_GET['view'] === 'framemedia') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
                if (isset($_GET['gallery_id'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
                    $gallery_id = $_GET['gallery_id']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
                } else {
                    $gallery_id = 0;
                }

                $this->enqueue();
                wp_localize_script(
                    'wpmf-modal-import-js',
                    'wpmfmd_import',
                    array(
                        'current_site' => admin_url(),
                        'gallery_id' => $gallery_id
                    )
                );

                ?>
                <input type="button" class="ju-button btn_modal_import_image_fromwp wpmfstrtoupper"
                       value="<?php esc_html_e('Import from wordpress', 'wp-media-folder-gallery-addon') ?>">
                <?php
            } else {
                $this->enqueue();
                // phpcs:disable WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
                if (isset($_GET['noheader'])) {
                    $type = 'iframe';
                    if (isset($_GET['editor']) && $_GET['editor'] === 'gutenberg') {
                        $editor_type = 'wpmfgutenberg';
                    }
                } else {
                    $type = 'notiframe';
                }
                // phpcs:enable
                echo '<div class="first_bg_load" style="width: 100%;height: 100%;background: #fff;float: left;position: absolute;"></div>';
                require_once(WPMF_GALLERY_ADDON_PLUGIN_DIR . '/admin/pages/gallerylists.php');
            }
        }
    }

    /**
     * Render gallery shortcode settings
     *
     * @param string $html Current html
     *
     * @return string
     */
    public function renderGalleryShortcode($html)
    {
        wp_enqueue_script(
            'wpmf-glraddon-settings',
            WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/js/gallery_settings.js',
            array('jquery'),
            WPMF_GALLERY_ADDON_VERSION,
            true
        );

        wp_localize_script(
            'wpmf-glraddon-settings',
            'glraddon_settings',
            array(
                'l18n' => array(
                    'success_copy_shortcode' => __('Gallery shortcode copied!', 'wp-media-folder-gallery-addon'),
                ),
                'vars' => array()
            )
        );

        $shortcode_configs = wpmfGetOption('gallery_shortcode');
        ob_start();
        $lists_themes = array(
            'default_theme',
            'portfolio_theme',
            'masonry_theme',
            'slider_theme',
            'flowslide_theme',
            'square_grid_theme',
            'material_theme'
        );

        $params = array();
        foreach ($lists_themes as $key_theme) {
            $params[$key_theme] = $this->shortcodeSettings(
                $key_theme,
                $shortcode_configs
            );
        }

        foreach ($params as $attr_key => $attr_value) {
            ${$attr_key} = $attr_value;
        }
        require_once(WPMF_GALLERY_ADDON_PLUGIN_DIR . '/admin/pages/gallery_shortcode/render_gallery_shortcode.php');
        $html .= ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * Gallery shortcode settings
     *
     * @param string $theme_name        Theme name
     * @param array  $shortcode_configs Shortcode settings
     *
     * @return string
     */
    public function shortcodeSettings($theme_name, $shortcode_configs)
    {
        ob_start();
        $settings = $shortcode_configs['theme'][$theme_name];
        require(WPMF_GALLERY_ADDON_PLUGIN_DIR . '/admin/pages/gallery_shortcode/shortcode_settings.php');
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * Gallery settings
     *
     * @param string $html Gallery html
     *
     * @return string
     */
    public function gallerySettings($html)
    {
        $gallery_configs = get_option('wpmf_gallery_settings');
        ob_start();
        $default_label = __('Default gallery theme', 'wp-media-folder-gallery-addon');
        $portfolio_label = __('Portfolio gallery theme', 'wp-media-folder-gallery-addon');
        $masonry_label = __('Masonry gallery theme', 'wp-media-folder-gallery-addon');
        $slider_label = __('Slider gallery theme', 'wp-media-folder-gallery-addon');
        $flowslide_label = __('Flow slide theme', 'wp-media-folder-gallery-addon');
        $square_grid_label = __('Square grid theme', 'wp-media-folder-gallery-addon');
        $material_label = __('Material theme', 'wp-media-folder-gallery-addon');

        $default_theme = $this->themeSettings(
            'default_theme',
            $gallery_configs,
            $default_label
        );
        $portfolio_theme = $this->themeSettings(
            'portfolio_theme',
            $gallery_configs,
            $portfolio_label
        );
        $masonry_theme = $this->themeSettings(
            'masonry_theme',
            $gallery_configs,
            $masonry_label
        );
        $slider_theme = $this->themeSettings(
            'slider_theme',
            $gallery_configs,
            $slider_label
        );
        $flowslide_theme = $this->themeSettings(
            'flowslide_theme',
            $gallery_configs,
            $flowslide_label
        );
        $square_grid_theme = $this->themeSettings(
            'square_grid_theme',
            $gallery_configs,
            $square_grid_label
        );
        $material_theme = $this->themeSettings(
            'material_theme',
            $gallery_configs,
            $material_label
        );
        require_once(WPMF_GALLERY_ADDON_PLUGIN_DIR . '/admin/pages/gallery_settings.php');
        $html .= ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * Gallery settings
     *
     * @param string $theme_name      Theme name
     * @param array  $gallery_configs Gallery config
     * @param string $theme_label     Theme label
     *
     * @return string
     */
    public function themeSettings($theme_name, $gallery_configs, $theme_label)
    {
        ob_start();
        $settings = $gallery_configs['theme'][$theme_name];
        require(WPMF_GALLERY_ADDON_PLUGIN_DIR . '/admin/pages/theme_settings.php');
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * Get count image selection
     *
     * @param integer $gallery_id Id of gallery
     *
     * @return integer
     */
    public function getCountImageSelection($gallery_id)
    {
        $tax_query = wpmfGalleryAddonGetTaxQuery($gallery_id);
        $args = array(
            'posts_per_page' => -1,
            'post_status' => 'any',
            'post_type' => 'attachment',
            'post_mime_type' => wpmfGalleryAddonGetImageType(),
            'tax_query' => $tax_query
        );
        $querycount = new WP_Query($args);
        $post_count = $querycount->post_count;
        return $post_count;
    }

    /**
     * Update gallery item
     *
     * @return void
     */
    public function updateGalleryItem()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            wp_send_json(
                array(
                    'status' => false
                )
            );
        }

        /**
         * Filter check capability of current user to update image in gallery
         *
         * @param boolean The current user has the given capability
         * @param string  Action name
         *
         * @return boolean
         *
         * @ignore Hook already documented
         */
        $wpmf_capability = apply_filters('wpmf_user_can', current_user_can('upload_files'), 'gallery_update_image');
        if (!$wpmf_capability) {
            wp_send_json(
                array(
                    'status' => false
                )
            );
        }

        if (isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            // Update post
            $params = array(
                'ID' => $id,
                'post_title' => sanitize_text_field($_POST['title']),
                'post_excerpt' => sanitize_text_field($_POST['excerpt'])
            );

            // Update the post into the database
            wp_update_post($params);
            update_post_meta(
                $id,
                '_wp_attachment_image_alt',
                sanitize_text_field($_POST['alt'])
            );
            update_post_meta(
                $id,
                '_wpmf_gallery_custom_image_link',
                sanitize_text_field($_POST['link_to'])
            );
            update_post_meta(
                $id,
                '_gallery_link_target',
                sanitize_text_field($_POST['link_target'])
            );
            update_post_meta(
                $id,
                'wpmf_img_tags',
                trim(sanitize_text_field($_POST['img_tags']))
            );
            update_post_meta(
                $id,
                'wpmf_remote_video_link',
                trim(sanitize_text_field($_POST['video_url']))
            );

            if (!empty($_POST['video_thumb_id'])) {
                update_post_meta(
                    $id,
                    'wpmf_video_thumbnail_id',
                    (int)$_POST['video_thumb_id']
                );
            }
            wp_send_json(array('status' => true));
        }
        wp_send_json(array('status' => false));
    }

    /**
     * Get image selection details
     *
     * @return void
     */
    public function itemDetails()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            die();
        }

        if (isset($_POST['id'])) {
            ob_start();
            $id = (int)$_POST['id'];
            $details = get_post($id);
            if (empty($details)) {
                wp_send_json(array('status' => false, 'html' => ''));
            }

            $alt = get_post_meta($id, '_wp_attachment_image_alt', true);
            $link_to = get_post_meta($id, '_wpmf_gallery_custom_image_link', true);
            $link_target = get_post_meta($id, '_gallery_link_target', true);
            $img_tags = get_post_meta($id, 'wpmf_img_tags', true);
            $video_url = get_post_meta($id, 'wpmf_remote_video_link', true);
            $thumb_id = wpmfGalleryGetVideoThumbID($id);
            $thumb_url = wp_get_attachment_image_url($thumb_id, 'thumbnail');
            /* set default meta */
            if (empty($alt)) {
                $alt = '';
            }
            if (empty($link_to)) {
                $link_to = '';
            }
            if (empty($link_target)) {
                $link_target = '';
            }
            if (empty($img_tags)) {
                $img_tags = '';
            }
            if (empty($video_url)) {
                $video_url = '';
            }

            require_once(WPMF_GALLERY_ADDON_PLUGIN_DIR . '/admin/pages/gallery_item_details.php');

            $images_html = ob_get_contents();
            ob_end_clean();
            wp_send_json(array('status' => true, 'html' => $images_html));
        }
        wp_send_json(array('status' => false, 'html' => ''));
    }

    /**
     * Delete image selection from gallery
     *
     * @return void
     */
    public function imageSelectionDelete()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            wp_send_json(
                array(
                    'status' => false
                )
            );
        }

        /**
         * Filter check capability of current user to delete image in gallery
         *
         * @param boolean The current user has the given capability
         * @param string  Action name
         *
         * @return boolean
         *
         * @ignore Hook already documented
         */
        $wpmf_capability = apply_filters('wpmf_user_can', current_user_can('upload_files'), 'gallery_delete_gallery_item');
        if (!$wpmf_capability) {
            wp_send_json(
                array(
                    'status' => false
                )
            );
        }

        if (isset($_POST['id'])) {
            // delete gallery to media library
            $id = (int)$_POST['id'];
            $metatype = get_post_meta((int)$id, 'wpmfglr_type', true);
            if (isset($metatype) && $metatype === 'upload') {
                wp_delete_attachment($id);
            } else {
                /* Remove in gallery */
                wp_remove_object_terms($id, (int)$_POST['id_gallery'], WPMF_GALLERY_ADDON_TAXO);
            }

            /* get count image selection */
            $count = $this->getCountImageSelection($_POST['id_gallery']);
            $nav = $this->regenerationNav($count);
            wp_send_json(array('status' => true, 'nav' => $nav));
        }
        wp_send_json(array('status' => false));
    }

    /**
     * Get nav of image selection
     *
     * @param integer $post_count       Count image in gallery
     * @param integer $current_page_nav Current page
     *
     * @return string
     */
    public function regenerationNav($post_count, $current_page_nav = 1)
    {
        $limit = get_option('wpmf_gallery_img_per_page');
        $page_count = ceil($post_count / $limit);
        $nav = '';
        ob_start();
        if ($page_count > 1) {
            require_once(WPMF_GALLERY_ADDON_PLUGIN_DIR . '/admin/pages/nav.php');
        }
        $nav = ob_get_contents();
        ob_end_clean();
        return $nav;
    }

    /**
     * Change gallery
     *
     * @return void
     */
    public function changeGallery()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            die();
        }

        /**
         * Filter check capability of current user to get gallery
         *
         * @param boolean The current user has the given capability
         * @param string  Action name
         *
         * @return boolean
         *
         * @ignore Hook already documented
         */
        $wpmf_capability = apply_filters('wpmf_user_can', current_user_can('upload_files'), 'get_gallery');
        if (!$wpmf_capability) {
            wp_send_json(false);
        }

        $id = 0;
        $limit = get_option('wpmf_gallery_img_per_page');
        if (!empty($_POST['id'])) {
            $id = (int)$_POST['id'];
        }

        $current_gallery = get_term($id, WPMF_GALLERY_ADDON_TAXO);
        $child = get_term_children((int)$current_gallery->term_id, WPMF_GALLERY_ADDON_TAXO);
        $countchild = count($child);

        // get params
        $galleries = get_option('wpmf_galleries');
        $theme = $galleries[$id]['theme'];
        $params = wpmfGalleryAddonGetParams($id);

        $from_folder = 0;
        if (!empty($params) && (int)$params['auto_from_folder'] === 1 && !empty($params['folder'])) {
            $from_folder = $params['folder'];
        }
        // get images html
        $tax_query = wpmfGalleryAddonGetTaxQuery($id, array(), 'change_gallery');
        $args = array(
            'posts_per_page' => $limit,
            'post_status' => 'any',
            'post_type' => 'attachment',
            'post_mime_type' => wpmfGalleryAddonGetImageType(),
            'tax_query' => $tax_query,
            'orderby' => $params['wpmf_orderby'],
            'order' => $params['wpmf_order']
        );

        $query = new WP_Query($args);
        $imageIDs = $query->get_posts();

        $items_in_folder = array();
        if ((int)$params['auto_from_folder'] === 1 && !empty($params['folder'])) {
            $items_in_folder = get_objects_in_term((int)$params['folder'], WPMF_TAXO);
        }

        if ($params['orderby'] === 'post__in' || $params['wpmf_orderby'] === 'post__in' || $theme === 'custom_grid') {
            foreach ($imageIDs as &$val) {
                $order = get_post_meta((int)$val->ID, 'wpmf_gallery_'. $id .'_order', true);
                $val->order = (int) $order;
                $val->item_in_folder = (in_array($val->ID, $items_in_folder)) ? 1 : 0;
            }

            usort($imageIDs, 'wpmfSortByOrder');
        }

        $glr = array(
            'name' => $current_gallery->name,
            'id' => $current_gallery->term_id,
            'parent' => $current_gallery->parent,
            'count_child' => $countchild,
            'term_group' => $current_gallery->term_group,
            'theme' => $theme,
            'params' => $params,
            'images' => $imageIDs
        );

        $upload_form_html = '';
        ob_start();
        require(WPMF_GALLERY_ADDON_PLUGIN_DIR . '/admin/pages/dragdrop.php');
        $upload_form_html .= ob_get_contents();
        ob_end_clean();

        if (count($imageIDs) === 0 || (count($imageIDs) === 1 && (int)$imageIDs[0] === 0)) {
            ob_start();
            require(WPMF_GALLERY_ADDON_PLUGIN_DIR . '/admin/pages/dragdrop.php');
            $images_html = ob_get_contents();
            ob_end_clean();
            wp_send_json(array('status' => false, 'nav' => '', 'upload_form_html' => $upload_form_html, 'theme' => $theme, 'glr' => $glr));
        } else {
            $feature_image_id = get_term_meta($id, 'wpmf_gallery_feature_image', true);
            if (empty($feature_image_id)) {
                update_term_meta($id, 'wpmf_gallery_feature_image', $imageIDs[0]->ID);
                $glr['feature_image_id'] = $imageIDs[0]->ID;
                $feature_image_id = $imageIDs[0]->ID;
            } else {
                $glr['feature_image_id'] = $feature_image_id;
            }

            $images = array();
            $images_html = '';
            ob_start();
            $grid_styles = get_term_meta($current_gallery->term_id, 'wpmf_grid_styles', true);
            foreach ($imageIDs as $image) {
                $folders = get_the_terms($image, WPMF_TAXO);
                $is_from_folder = false;
                foreach ($folders as $folder) {
                    if ((int)$folder->term_id === (int)$from_folder) {
                        $is_from_folder = true;
                        break;
                    }
                }
                if ($is_from_folder) {
                    require(WPMF_GALLERY_ADDON_PLUGIN_DIR . '/admin/pages/thumbnail_folder_selection.php');
                } else {
                    require(WPMF_GALLERY_ADDON_PLUGIN_DIR . '/admin/pages/thumbnail_selection.php');
                }
            }
            $images_html = ob_get_contents();
            ob_end_clean();
            $post_count = $this->getCountImageSelection($id);
            $nav = $this->regenerationNav($post_count);
            wp_send_json(
                array(
                    'status' => true,
                    'nav' => $nav,
                    'images_html' => $images_html,
                    'upload_form_html' => $upload_form_html,
                    'theme' => $theme,
                    'glr' => $glr
                )
            );
        }
    }

    /**
     * Get library folder tree
     *
     * @return void
     */
    public function getLibraryTree()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            die();
        }

        /**
         * Filter check capability of current user to get wpmf folders list
         *
         * @param boolean The current user has the given capability
         * @param string  Action name
         *
         * @return boolean
         *
         * @ignore Hook already documented
         */
        $wpmf_capability = apply_filters('wpmf_user_can', current_user_can('upload_files'), 'get_wpmf_category');
        if (!$wpmf_capability) {
            wp_send_json(false);
        }
        global $current_user;
        $dir = '/';
        if (!empty($_POST['dir'])) {
            $dir = $_POST['dir'];
            if ($dir[0] === '/') {
                $dir = '.' . $dir . '/';
            }
        }
        $dir = str_replace('..', '', $dir);
        $dirs = array();
        $id = 0;
        if (!empty($_POST['id'])) {
            $id = (int)$_POST['id'];
        }

        // Retrieve the terms in a given taxonomy or list of taxonomies.
        $categories = get_categories(
            array(
                'taxonomy' => WPMF_TAXO,
                'orderby' => 'name',
                'order' => 'ASC',
                'parent' => $id,
                'hide_empty' => false
            )
        );
        $wpmf_active_media = get_option('wpmf_active_media');
        $wpmf_create_folder = get_option('wpmf_create_folder');
        $user_roles = $current_user->roles;
        $role = array_shift($user_roles);
        $current_role = $this->getRoles(get_current_user_id());
        foreach ($categories as $category) {
            if ((int)$category->parent === 0 && $category->name === 'Gallery Upload') {
                continue;
            }

            $child = get_term_children((int)$category->term_id, WPMF_TAXO);
            $countchild = count($child);
            if (($role !== 'administrator' && isset($wpmf_active_media) && (int)$wpmf_active_media === 1)
                || ($role === 'administrator' && isset($_SESSION['wpmf_display_media'])
                    && $_SESSION['wpmf_display_media'] === 'yes')) {
                if ($wpmf_create_folder === 'user') {
                    if ((int)$category->term_group === (int)get_current_user_id()) {
                        $dirs[] = array(
                            'type' => 'dir',
                            'dir' => $dir,
                            'file' => $category->name,
                            'id' => $category->term_id,
                            'parent_id' => $category->parent,
                            'count_child' => $countchild,
                            'term_group' => $category->term_group
                        );
                    }
                } else {
                    $role = $this->getRoles($category->term_group);
                    if ($current_role === $role) {
                        $dirs[] = array(
                            'type' => 'dir',
                            'dir' => $dir,
                            'file' => $category->name,
                            'id' => $category->term_id,
                            'parent_id' => $category->parent,
                            'count_child' => $countchild,
                            'term_group' => $category->term_group
                        );
                    }
                }
            } else {
                $dirs[] = array(
                    'type' => 'dir',
                    'dir' => $dir,
                    'file' => $category->name,
                    'id' => $category->term_id,
                    'parent_id' => $category->parent,
                    'count_child' => $countchild,
                    'term_group' => $category->term_group
                );
            }
        }

        if (count($dirs) < 0) {
            wp_send_json(array('status' => false));
        } else {
            wp_send_json(array('dirs' => $dirs, 'status' => true));
        }
    }

    /**
     * Get all terms need import
     *
     * @param integer $parent  ID of term parent
     * @param array   $results Results
     *
     * @return array
     */
    public function getTermChild($parent, $results)
    {
        if (empty($results)) {
            $results = array();
        }

        $terms = get_terms(WPMF_TAXO, array(
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => false,
            'child_of' => 1,
            'parent' => $parent
        ));


        if (!empty($terms)) {
            foreach ($terms as $term) {
                $results[] = $term;
                $results = $this->getTermChild($term->term_id, $results);
            }
        }

        return $results;
    }

    /**
     * Ajax import images from wordpress
     *
     * @return void
     */
    public function importImagesFromWp()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            wp_send_json(
                array(
                    'status' => false
                )
            );
        }

        /**
         * Filter check capability of current user to import images from wordpress
         *
         * @param boolean The current user has the given capability
         * @param string  Action name
         *
         * @return boolean
         *
         * @ignore Hook already documented
         */
        $wpmf_capability = apply_filters('wpmf_user_can', current_user_can('upload_files'), 'import_image_from_wp');
        if (!$wpmf_capability) {
            wp_send_json(array('status' => false));
        }

        if (empty($_POST['ids'])) {
            wp_send_json(array('status' => false));
        }

        /* set images to gallery */
        $ids = explode(',', $_POST['ids']);
        foreach ($ids as $id) {
            // set to gallery
            wp_set_object_terms((int)$id, (int)$_POST['gallery_id'], WPMF_GALLERY_ADDON_TAXO, true);
            // set to root folder
            $root_folder = get_the_terms((int)$id, WPMF_TAXO);
            if (empty($root_folder)) {
                $root_id = get_option('wpmf_folder_root_id');
                wp_set_object_terms(
                    (int)$id,
                    (int)$root_id,
                    WPMF_TAXO,
                    true
                );
            }

            // set default order images
            update_post_meta((int)$id, 'wpmf_gallery_'. $_POST['gallery_id'] .'_order', 0);
        }

        wp_send_json(array('status' => true));
    }

    /**
     * Generate attachment html
     *
     * @param string  $title Title of image
     * @param integer $id    Id of image
     *
     * @return void
     */
    public function generateAttachmentHtml($title, $id)
    {
        ?>
        <li aria-label="<?php echo esc_html($title) ?>" aria-checked="false" data-id="<?php echo esc_html($id) ?>"
            class="attachment">
            <div class="wpmfglr-attachment-preview">
                <?php
                $thumnailUrl = wp_get_attachment_image_src($id, 'medium');
                ?>
                <img src="<?php echo esc_html($thumnailUrl[0]) ?>" draggable="false" alt="">
                <div class="action_images">
                    <span data-id="<?php echo esc_html($id) ?>"
                          class="edit_gallery_item dashicons dashicons-edit"></span>
                    <span data-id="<?php echo esc_html($id) ?>"
                          class="delete_gallery_item dashicons dashicons-trash"></span>
                </div>
            </div>
            <button type="button" class="check" tabindex="-1"><span class="media-modal-icon"></span><span
                        class="screen-reader-text">Deselect</span></button>
        </li>
        <?php
    }

    /**
     * Get theme, if not exist return default theme
     *
     * @param string $theme Theme name
     *
     * @return string
     */
    public function getTheme($theme)
    {
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
        if (in_array($theme, $allow_themes)) {
            return $theme;
        }
        return 'default';
    }

    /**
     * Ajax create gallery
     *
     * @return void
     */
    public function createGallery()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            wp_send_json(
                array(
                    'status' => false
                )
            );
        }

        /**
         * Filter check capability of current user to create a gallery
         *
         * @param boolean The current user has the given capability
         * @param string  Action name
         *
         * @return boolean
         *
         * @ignore Hook already documented
         */
        $wpmf_capability = apply_filters('wpmf_user_can', current_user_can('upload_files'), 'create_gallery');
        if (!$wpmf_capability) {
            wp_send_json(array('status' => false));
        }

        if (isset($_POST['type']) && $_POST['type'] === 'save_as_gallery') {
            $title = time();
        } else {
            if (isset($_POST['title'])) {
                $title = $_POST['title'];
            } else {
                $title = __('New gallery', 'wp-media-folder-gallery-addon');
            }
        }

        // get theme
        $theme = $this->getTheme($_POST['theme']);
        /* add new gallery and params to array */
        $galleries = get_option('wpmf_galleries');
        $inserted = wp_insert_term(
            $title,
            WPMF_GALLERY_ADDON_TAXO,
            array('parent' => (int)$_POST['parent'])
        );

        if (is_wp_error($inserted)) {
            wp_send_json(array('status' => false, 'msg' => $inserted->get_error_message()));
        }

        // get last order
        $args = array(
            'taxonomy' => WPMF_GALLERY_ADDON_TAXO,
            'hide_empty' => false,
            'parent' => (int)$_POST['parent']
        );
        $lastCats = get_terms($args);
        foreach ($lastCats as $key => $object) {
            $order = get_term_meta($object->term_id, 'wpmf_order', true);
            if (empty($order)) {
                $order = 0;
            }
            $object->order = $order;
        }
        usort($lastCats, 'wpmfGalleryReorder');

        if (is_array($lastCats) && count($lastCats)) {
            $last_index = count($lastCats) - 1;
            $order = get_term_meta($lastCats[$last_index]->term_id, 'wpmf_order', true);
            update_term_meta((int) $inserted['term_id'], 'wpmf_order', (int)$order + 1);
        }

        update_term_meta((int) $inserted['term_id'], 'wpmf_theme', $theme);
        $termInfos = get_term($inserted['term_id'], WPMF_GALLERY_ADDON_TAXO);

        /* create wpmf_galleries option */
        if (empty($galleries) && !is_array($galleries)) {
            $galleries = array();
            $galleries[$inserted['term_id']] = array(
                'gallery_id' => $inserted['term_id'],
                'theme' => $theme
            );
        } else {
            $galleries[$inserted['term_id']] = array(
                'gallery_id' => $inserted['term_id'],
                'theme' => $theme
            );
        }

        $termInfos->theme = $theme;
        /* set option wpmf_galleries to relative gallery id with theme */
        update_option('wpmf_galleries', $galleries);
        /* get dropdown gallery */
        $dropdown_gallery = $this->dropdownGallery();

        // get all gallery
        $terms = $this->getAllGalleries();
        $attachment_terms = $terms['attachment_terms'];
        $terms_order = $terms['terms_order'];

        wp_send_json(
            array(
                'items' => $termInfos,
                'dropdown_gallery' => $dropdown_gallery,
                'status' => true,
                'categories' => $attachment_terms,
                'categories_order' => $terms_order
            )
        );
    }

    /**
     * Generation dropdown gallery
     *
     * @param integer $selected_gallery Gallery ID
     *
     * @return string
     */
    public function dropdownGallery($selected_gallery = 0)
    {
        if (!empty($selected_gallery)) {
            $selected = get_term((int)$selected_gallery, WPMF_GALLERY_ADDON_TAXO);
        } else {
            $selected = 0;
        }

        ob_start();
        $html = '';
        $dropdown_options = array(
            'show_option_none' => __('Parent gallery', 'wp-media-folder-gallery-addon'),
            'option_none_value' => 0,
            'hide_empty' => false,
            'hierarchical' => true,
            'orderby' => 'name',
            'taxonomy' => WPMF_GALLERY_ADDON_TAXO,
            'class' => 'wpmf-gallery-categories ju-select',
            'name' => 'wpmf-gallery-categories',
            'selected' => $selected->parent
        );
        wp_dropdown_categories($dropdown_options);
        $html .= ob_get_contents();
        ob_end_clean();

        return $html;
    }

    /**
     * Ajax delete gallery
     *
     * @return void
     */
    public function deleteGallery()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            wp_send_json(
                array(
                    'status' => false
                )
            );
        }

        /**
         * Filter check capability of current user to delete a gallery
         *
         * @param boolean The current user has the given capability
         * @param string  Action name
         *
         * @return boolean
         *
         * @ignore Hook already documented
         */
        $wpmf_capability = apply_filters('wpmf_user_can', current_user_can('upload_files'), 'delete_gallery');
        if (!$wpmf_capability) {
            wp_send_json(array('status' => false));
        }

        if (wp_delete_term((int)$_POST['id'], WPMF_GALLERY_ADDON_TAXO)) {
            /* update setting gallery */
            $galleries = get_option('wpmf_galleries');
            if (isset($galleries[$_POST['id']])) {
                unset($galleries[$_POST['id']]);
                update_option('wpmf_galleries', $galleries);
            }

            wp_send_json(array('status' => true));
        } else {
            wp_send_json(array('status' => false));
        }
    }

    /**
     * Ajax edit gallery
     *
     * @return void
     */
    public function saveCustomGridStyles()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            wp_send_json(
                array(
                    'status' => false,
                    'msg' => __('Edit failed. Please try again.', 'wp-media-folder-gallery-addon')
                )
            );
        }

        /**
         * Filter check capability of current user to edit a gallery
         *
         * @param boolean The current user has the given capability
         * @param string  Action name
         *
         * @return boolean
         *
         * @ignore Hook already documented
         */
        $wpmf_capability = apply_filters('wpmf_user_can', current_user_can('upload_files'), 'edit_gallery');
        if (!$wpmf_capability) {
            wp_send_json(
                array(
                    'status' => false,
                    'msg' => __('Edit failed. Please try again.', 'wp-media-folder-gallery-addon')
                )
            );
        }

        if (isset($_POST['id'])) {
            // get theme
            $theme = $this->getTheme($_POST['theme']);
            if ($theme === 'custom_grid') {
                $grid_styles = json_decode(stripslashes($_POST['grid_styles']), true);
                $order = 0;
                foreach ($grid_styles as $image => $grid_style) {
                    $images = explode('-', $image);
                    if (!empty($images[1])) {
                        update_post_meta((int)$images[1], 'wpmf_gallery_'. $_POST['id'] .'_order', $order);
                    }
                    $order++;
                }
                update_term_meta((int)$_POST['id'], 'wpmf_grid_styles', $grid_styles);
                wp_send_json(array('status' => true));
            }
        }

        wp_send_json(array('status' => false));
    }

    /**
     * Ajax edit gallery
     *
     * @return void
     */
    public function editGallery()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            wp_send_json(
                array(
                    'status' => false,
                    'msg' => __('Edit failed. Please try again.', 'wp-media-folder-gallery-addon')
                )
            );
        }

        /**
         * Filter check capability of current user to edit a gallery
         *
         * @param boolean The current user has the given capability
         * @param string  Action name
         *
         * @return boolean
         *
         * @ignore Hook already documented
         */
        $wpmf_capability = apply_filters('wpmf_user_can', current_user_can('upload_files'), 'edit_gallery');
        if (!$wpmf_capability) {
            wp_send_json(
                array(
                    'status' => false,
                    'msg' => __('Edit failed. Please try again.', 'wp-media-folder-gallery-addon')
                )
            );
        }

        if (isset($_POST['id'])) {
            // get theme
            $theme = $this->getTheme($_POST['theme']);
            $gallery_params = wpmfGalleryAddonGetDefaultParams();
            $oldterm = get_term((int)$_POST['id'], WPMF_GALLERY_ADDON_TAXO);
            $params = array(
                'name' => $_POST['title'],
                'parent' => (int)$_POST['parent']
            );

            $termInfos = wp_update_term((int)$_POST['id'], WPMF_GALLERY_ADDON_TAXO, $params);
            if ($termInfos instanceof WP_Error) {
                wp_send_json(array('status' => false, 'msg' => $termInfos->get_error_messages()));
            } else {
                /* update theme for this gallery */
                $galleries = get_option('wpmf_galleries');
                $galleries[$_POST['id']]['theme'] = $theme;
                foreach ($gallery_params as $param => $value) {
                    if (isset($_POST[$param])) {
                        $galleries[$_POST['id']][$param] = $_POST[$param];
                    }
                }
                update_option('wpmf_galleries', $galleries);

                /* set images to gallery */
                $images = get_objects_in_term($_POST['id'], WPMF_GALLERY_ADDON_TAXO);
                $termInfos = get_term((int)$_POST['id'], WPMF_GALLERY_ADDON_TAXO);
                $termInfos->theme = $_POST['theme'];
                $termInfos->images = $images;

                // get all gallery
                $terms = $this->getAllGalleries();
                $attachment_terms = $terms['attachment_terms'];
                $terms_order = $terms['terms_order'];

                // get dropdown lists gallery html
                $dropdown_gallery = $this->dropdownGallery();

                $json = array(
                    'status' => true,
                    'dropdown_gallery' => $dropdown_gallery,
                    'items' => $termInfos,
                    'categories' => $attachment_terms,
                    'categories_order' => $terms_order
                );

                /* If update parent */
                if ((int)$oldterm->parent !== (int)$_POST['parent']) {
                    $child_id = get_term_children((int)$_POST['id'], WPMF_GALLERY_ADDON_TAXO);
                    $child_id_category = get_term_children((int)$_POST['parent'], WPMF_GALLERY_ADDON_TAXO);
                    $json['count_id'] = count($child_id);
                    $json['count_to_id'] = count($child_id_category);
                    $json['update_parent'] = 1;
                }
                wp_send_json(
                    $json
                );
            }
        }
        wp_send_json(
            array(
                'status' => false,
                'msg' => __('This gallery does not exist!', 'wp-media-folder-gallery-addon')
            )
        );
    }

    /**
     * Remove selected images from media selection
     *
     * @return void
     */
    public function deleteImgsSelected()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            wp_send_json(
                array(
                    'status' => false
                )
            );
        }

        if (isset($_POST['ids'])) {
            $ids = explode(',', $_POST['ids']);
            if (!empty($ids)) {
                foreach ($ids as $id) {
                    $metatype = get_post_meta((int)$id, 'wpmfglr_type', true);
                    if (isset($metatype) && $metatype === 'upload') {
                        wp_delete_attachment((int)$id);
                    } else {
                        /* Remove in gallery */
                        wp_remove_object_terms((int)$id, (int)$_POST['id_gallery'], WPMF_GALLERY_ADDON_TAXO);
                    }
                }

                /* get count image selection */
                $count = $this->getCountImageSelection($_POST['id_gallery']);
                $nav = $this->regenerationNav($count);
                wp_send_json(array('status' => true, 'nav' => $nav));
            }
        }
        wp_send_json(array('status' => false));
    }

    /**
     * Get current user role
     *
     * @param integer $userId User id
     *
     * @return mixed|string
     */
    public function getRoles($userId)
    {
        if (!function_exists('get_userdata')) {
            require_once(ABSPATH . 'wp-includes/pluggable.php');
        }
        $userdata = get_userdata($userId);
        if (!empty($userdata->roles)) {
            $role = array_shift($userdata->roles);
        } else {
            $role = '';
        }
        return $role;
    }

    /**
     * Ajax upload file
     *
     * @return void
     */
    public function galleryUploadFile()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            die();
        }

        /**
         * Filter check capability of current user to upload images to gallery
         *
         * @param boolean The current user has the given capability
         * @param string  Action name
         *
         * @return boolean
         *
         * @ignore Hook already documented
         */
        $wpmf_capability = apply_filters('wpmf_user_can', current_user_can('upload_files'), 'gallery_upload_images');
        if (!$wpmf_capability) {
            wp_send_json(false);
        }

        if (!empty($_FILES['wpmf_gallery_file'])) {
            $lists = array();
            foreach ($_FILES['wpmf_gallery_file']['name'] as $i => $file) {
                $lists[] = array(
                    'name' => $file,
                    'type' => $_FILES['wpmf_gallery_file']['type'][$i],
                    'tmp_name' => $_FILES['wpmf_gallery_file']['tmp_name'][$i],
                    'error' => $_FILES['wpmf_gallery_file']['error'][$i],
                    'size' => $_FILES['wpmf_gallery_file']['size'][$i]
                );
            }

            $allowedTypes = array('gif', 'jpg', 'JPG', 'png', 'bmp', 'jpeg', 'JPEG', 'svg', 'webp');
            $upload_dir = wp_upload_dir();
            $images_html = '';
            $idsImport = array();
            ob_start();
            foreach ($lists as $list) {
                $infopath = pathinfo($list['name']);
                if (!in_array($infopath['extension'], $allowedTypes)) {
                    wp_send_json(
                        array(
                            'status' => false,
                            'msg' => __('Please upload the media with format
                             (jpg, png, gif, jpeg, bmp, svg)', 'wp-media-folder-gallery-addon')
                        )
                    );
                }

                if ($list['error'] > 0) {
                    continue;
                }

                $file = sanitize_file_name($list['name']);
                $content = file_get_contents($list['tmp_name']);
                $title = str_replace('.' . $infopath['extension'], '', $list['name']);

                $attach_id = $this->insertAttachmentMetadata(
                    $upload_dir['path'],
                    $upload_dir['url'],
                    $list['name'],
                    $file,
                    $content,
                    $list['type'],
                    $infopath['extension']
                );
                update_post_meta($attach_id, 'wpmfglr_type', 'upload');
                $idsImport[] = $attach_id;
                /* set images to gallery */
                wp_set_object_terms((int)$attach_id, (int)$_POST['up_gallery_id'], WPMF_GALLERY_ADDON_TAXO, true);
                $glr_selection = get_term_by('name', 'Gallery Upload', 'wpmf-category');
                if ($glr_selection) {
                    if ($glr_selection->taxonomy === 'wpmf-category') {
                        wp_set_object_terms((int)$attach_id, (int)$glr_selection->term_id, WPMF_TAXO, true);
                    }
                }
                // set default order images
                update_post_meta((int)$attach_id, 'wpmf_gallery_'. $_POST['up_gallery_id'] .'_order', 0);

                if ($attach_id) {
                    $this->generateAttachmentHtml($title, (int)$attach_id);
                }
            }
            $images_html .= ob_get_contents();
            ob_end_clean();
            /* get count image selection */
            $post_count = $this->getCountImageSelection($_POST['up_gallery_id']);
            $nav = $this->regenerationNav($post_count);
            wp_send_json(array('status' => true, 'ids' => $idsImport, 'html' => $images_html, 'nav' => $nav));
        } else {
            wp_send_json(array('status' => false, 'msg' => __('File not exist', 'wp-media-folder-gallery-addon')));
        }
    }

    /**
     * Update img per page
     *
     * @return void
     */
    public function updateImgPerpage()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            wp_send_json(
                array(
                    'status' => false
                )
            );
        }

        if (isset($_POST['img_per_page']) && is_numeric($_POST['img_per_page'])) {
            update_option('wpmf_gallery_img_per_page', $_POST['img_per_page']);
        }
        wp_send_json(array('status' => true));
    }

    /**
     * Update gallery parent when draggable gallery on folder tree
     *
     * @return void
     */
    public function updateParentGallery()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            wp_send_json(
                array(
                    'status' => false
                )
            );
        }

        /**
         * Filter check capability of current user to update parent of gallery
         *
         * @param boolean The current user has the given capability
         * @param string  Action name
         *
         * @return boolean
         *
         * @ignore Hook already documented
         */
        $wpmf_capability = apply_filters('wpmf_user_can', current_user_can('upload_files'), 'update_parent_gallery');
        if (!$wpmf_capability) {
            wp_send_json(
                array(
                    'status' => false
                )
            );
        }

        if (isset($_POST['id_gallery']) && isset($_POST['parent'])) {
            $r = wp_update_term(
                (int)$_POST['id_gallery'],
                WPMF_GALLERY_ADDON_TAXO,
                array('parent' => (int)$_POST['parent'])
            );
            if ($r instanceof WP_Error) {
                wp_send_json(array('status' => false));
            } else {
                // get all gallery
                $terms = $this->getAllGalleries();
                $attachment_terms = $terms['attachment_terms'];
                $terms_order = $terms['terms_order'];

                // get dropdown lists gallery html
                $dropdown_gallery = $this->dropdownGallery();
                wp_send_json(
                    array(
                        'status' => true,
                        'dropdown_gallery' => $dropdown_gallery,
                        'categories' => $attachment_terms,
                        'categories_order' => $terms_order
                    )
                );
            }
        }
    }

    /**
     * Get attachment from folder Image gallery selection by nav
     *
     * @return void
     */
    public function getImgSelectionNav()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            die();
        }

        /**
         * Filter check capability of current user to get gallery images list
         *
         * @param boolean The current user has the given capability
         * @param string  Action name
         *
         * @return boolean
         *
         * @ignore Hook already documented
         */
        $wpmf_capability = apply_filters('wpmf_user_can', current_user_can('upload_files'), 'get_gallery_images_list');
        if (!$wpmf_capability) {
            wp_send_json(false);
        }

        if (isset($_POST['id_gallery']) && isset($_POST['current_page_nav'])) {
            $id = $_POST['id_gallery'];
            /* get count page */
            $limit = get_option('wpmf_gallery_img_per_page');
            $params = wpmfGalleryAddonGetParams($id);
            $tax_query = wpmfGalleryAddonGetTaxQuery($id);
            $feature_image_id = get_term_meta($id, 'wpmf_gallery_feature_image', true);
            $args = array(
                'posts_per_page' => -1,
                'post_status' => 'any',
                'post_type' => 'attachment',
                'post_mime_type' => wpmfGalleryAddonGetImageType(),
                'tax_query' => $tax_query,
            );
            $querycount = new WP_Query($args);
            $post_count = $querycount->post_count;
            $page_count = ceil($post_count / $limit);
            $current_page_nav = $_POST['current_page_nav'];
            if ($current_page_nav <= 0) {
                $current_page_nav = 1;
            }
            if ($current_page_nav > $page_count) {
                $current_page_nav = $page_count;
            }
            $offset = ((int)$current_page_nav - 1) * $limit;
            $args = array(
                'offset' => $offset,
                'posts_per_page' => $limit,
                'post_status' => 'any',
                'post_type' => 'attachment',
                'post_mime_type' => wpmfGalleryAddonGetImageType(),
                'tax_query' => $tax_query,
                'orderby' => $params['wpmf_orderby'],
                'order' => $params['wpmf_order']
            );

            $query = new WP_Query($args);
            $iSelections = $query->get_posts();
            if ($params['orderby'] === 'post__in') {
                foreach ($iSelections as &$val) {
                    $order = get_post_meta((int)$val->ID, 'wpmf_gallery_'. $id .'_order', true);
                    $val->order = (int) $order;
                }

                usort($iSelections, 'wpmfSortByOrder');
            }

            ob_start();
            $html = '';
            $grid_styles = get_term_meta($id, 'wpmf_grid_styles', true);
            foreach ($iSelections as $image) {
                require(WPMF_GALLERY_ADDON_PLUGIN_DIR . '/admin/pages/thumbnail_selection.php');
            }
            $html .= ob_get_contents();

            $nav = $this->regenerationNav($post_count, $current_page_nav);
            ob_end_clean();
            wp_send_json(array('status' => true, 'html' => $html, 'nav' => $nav));
        }
    }

    /**
     * Insert a attachment to database
     *
     * @param string $upload_path Path of file
     * @param string $upload_url  URL of file
     * @param string $file_title  Title of tile
     * @param string $file        File name
     * @param string $content     Content of file
     * @param string $mime_type   Mime type of file
     * @param string $ext         Extension of file
     *
     * @return boolean|integer|WP_Error
     */
    public function insertAttachmentMetadata($upload_path, $upload_url, $file_title, $file, $content, $mime_type, $ext)
    {
        remove_filter('add_attachment', array($GLOBALS['wp_media_folder'], 'wpmf_after_upload'));
        $file = wp_unique_filename($upload_path, $file);
        $upload = file_put_contents($upload_path . '/' . $file, $content);
        if ($upload) {
            // Get WP upload dir
            $uploadDir = wp_upload_dir();
            $destination_file_path = $uploadDir['path'] . '/' . $file;
            $destination_url       = $uploadDir['url'] . '/' . $file;

            // Set file permissions
            $stat = stat($destination_file_path);
            $perms = $stat['mode'] & 0000666;
            chmod($destination_file_path, $perms);

            // Apply upload filters
            $return = apply_filters('wp_handle_upload', array(
                'file' => $destination_file_path,
                'url' => $destination_url,
                'type' => $mime_type,
            ));

            $attachment = array(
                'guid' => $upload_url . '/' . $file,
                'post_mime_type' => $mime_type,
                'post_title' => str_replace('.' . $ext, '', $file_title),
                'post_status' => 'inherit'
            );

            $image_path = $upload_path . '/' . $file;
            // Insert attachment
            $attach_id = wp_insert_attachment($attachment, $image_path);
            $attach_data = wp_generate_attachment_metadata($attach_id, $image_path);
            wp_update_attachment_metadata($attach_id, $attach_data);
            return $attach_id;
        }
        return false;
    }

    /**
     * Ajax custom order for file
     *
     * @return void
     */
    public function reorderFile()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            die();
        }

        if (isset($_POST['order'])) {
            $gallery_id = (isset($_POST['gallery_id'])) ? $_POST['gallery_id'] : 0;
            $orders = (array)json_decode(stripslashes_deep($_POST['order']));
            if (is_array($orders) && !empty($orders)) {
                foreach ($orders as $position => $id) {
                    update_post_meta(
                        (int)$id,
                        'wpmf_gallery_'. $gallery_id .'_order',
                        (int)$position
                    );
                }
            }
        }
    }

    /**
     * Get custom order gallery
     *
     * @param integer $term_id Id of gallery
     *
     * @return integer|mixed
     */
    public function getOrderGallery($term_id)
    {
        $order = get_term_meta($term_id, 'wpmf_order', true);
        if (empty($order)) {
            $order = 0;
        }
        return $order;
    }

    /**
     * Update order gallery
     *
     * @param array $lists List gallery with order
     *
     * @return void
     */
    public function updateOrderGallery($lists)
    {
        if (empty($lists)) {
            return;
        }

        foreach ($lists as $index => $id) {
            if ((int)$id === 0) {
                continue;
            }
            update_term_meta((int)$id, 'wpmf_order', $index);
        }
    }

    /**
     * Ajax custom order for gallery
     *
     * @return void
     */
    public function reorderGallery()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            wp_send_json(
                array(
                    'status' => false
                )
            );
        }

        /**
         * Filter check capability of current user to reorder gallery
         *
         * @param boolean The current user has the given capability
         * @param string  Action name
         *
         * @return boolean
         *
         * @ignore Hook already documented
         */
        $wpmf_capability = apply_filters('wpmf_user_can', current_user_can('upload_files'), 'reorder_gallery');
        if (!$wpmf_capability) {
            wp_send_json(array('status' => false));
        }

        $orders = json_decode(stripslashes($_POST['order']), true);
        $this->updateOrderGallery($orders);
        $dropdown_gallery = $this->dropdownGallery();
        wp_send_json(
            array(
                'dropdown_gallery' => $dropdown_gallery,
                'status' => true
            )
        );
    }

    /**
     * Move a gallery via ajax
     *
     * @return void
     */
    public function moveGallery()
    {
        if (empty($_POST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_POST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            die();
        }

        /**
         * Filter check capability of current user to move a folder
         *
         * @param boolean The current user has the given capability
         * @param string  Action name
         *
         * @return boolean
         *
         * @ignore Hook already documented
         */
        $wpmf_capability = apply_filters('wpmf_user_can', current_user_can('upload_files'), 'move_gallery');
        if (!$wpmf_capability) {
            wp_send_json(array('status' => false, 'msg' => esc_html__('You not have permission!', 'wp-media-folder-gallery-addon')));
        }

        /*
         * Check if there is another gallery with the same slug
         * in the folder we moving into
         */
        $term     = get_term($_POST['id_category']);
        $siblings = get_categories(
            array(
                'taxonomy' => WPMF_GALLERY_ADDON_TAXO,
                'fields'   => 'names',
                'get'      => 'all',
                'parent'   => (int) $_POST['id_category']
            )
        );
        if (in_array($term->slug, $siblings)) {
            wp_send_json(array('status' => false));
        }

        $r = wp_update_term((int) $_POST['id'], WPMF_GALLERY_ADDON_TAXO, array('parent' => (int) $_POST['id_category']));
        if ($r instanceof WP_Error) {
            wp_send_json(array('status' => false));
        } else {
            // get all gallery
            $terms = $this->getAllGalleries();
            $attachment_terms = $terms['attachment_terms'];
            $terms_order = $terms['terms_order'];
            $selected_gallery = (isset($_POST['selected_gallery'])) ? $_POST['selected_gallery'] : 0;

            // get dropdown lists gallery html
            $dropdown_gallery = $this->dropdownGallery($selected_gallery);
            wp_send_json(
                array(
                    'status' => true,
                    'dropdown_gallery' => $dropdown_gallery,
                    'categories' => $attachment_terms,
                    'categories_order' => $terms_order
                )
            );
        }
    }

    /**
     * Load gallery html with ajax method
     *
     * @return void
     */
    public function loadGalleryHtml()
    {
        if (empty($_REQUEST['wpmf_gallery_nonce'])
            || !wp_verify_nonce($_REQUEST['wpmf_gallery_nonce'], 'wpmf_gallery_nonce')) {
            wp_send_json(array('status' => false));
        }

        if (!empty($_REQUEST['datas'])) {
            $request_params = (array)json_decode(stripslashes($_REQUEST['datas']));
            $galleries = get_option('wpmf_galleries');
            $default_params = array(
                'gallery_id' => 0,
                'display' => '',
                'layout' => 'vertical',
                'row_height' => 200,
                'aspect_ratio' => 'default',
                'columns' => 3,
                'gutterwidth' => 5,
                'link' => 'post',
                'size' => 'thumbnail',
                'crop_image' => 1,
                'targetsize' => 'large',
                'wpmf_orderby' => 'post__in',
                'wpmf_order' => 'ASC',
                'customlink' => 0,
                'class' => '',
                'include' => '',
                'exclude' => '',
                'display_tree' => 0,
                'display_tag' => 0,
                'img_border_radius' => 0,
                'border_width' => 0,
                'border_color' => 'transparent',
                'border_style' => 'solid',
                'hoverShadowH' => 0,
                'hoverShadowV' => 0,
                'hoverShadowBlur' => 0,
                'hoverShadowSpread' => 0,
                'hoverShadowColor' => 'ccc',
                'show_buttons' => 1,
                'animation' => 'slide',
                'duration' => 4000,
                'auto_animation' => 1,
                'number_lines' => 1,
                'hover_color' => '#000',
                'hover_opacity' => '0.4',
                'hover_title_position' => 'center_center',
                'hover_title_size' => 16,
                'hover_title_color' => '#fff',
                'hover_desc_position' => 'none',
                'hover_desc_size' => 14,
                'hover_desc_color' => '#fff'
            );

            if (isset($galleries[$request_params['gallery_id']])) {
                $params = array_merge(
                    $default_params,
                    $galleries[$request_params['gallery_id']],
                    $request_params
                );
            } else {
                $params = array_merge(
                    $default_params,
                    $request_params
                );
            }

            foreach ($params as $attr_key => $attr_value) {
                ${$attr_key} = $attr_value;
            }

            $gallery = get_term($gallery_id, WPMF_GALLERY_ADDON_TAXO);
            if (empty($gallery)) {
                wp_send_json(array('status' => false));
            }

            if ($display === '') {
                $display = 'default';
                if (!empty($galleries[$gallery_id]['theme'])) {
                    $display = $galleries[$gallery_id]['theme'];
                }
            }

            if (isset($hoverShadowH, $hoverShadowV, $hoverShadowBlur, $hoverShadowSpread) && ((int)$hoverShadowH !== 0 || (int)$hoverShadowV !== 0 || (int)$hoverShadowBlur !== 0 || (int)$hoverShadowSpread !== 0)) {
                if ($hoverShadowColor !== 'transparent') {
                    $hoverShadowColor = '#' . $hoverShadowColor;
                }
                $img_shadow = $hoverShadowH . 'px ' . $hoverShadowV . 'px ' . $hoverShadowBlur . 'px ' . $hoverShadowSpread . 'px ' . $hoverShadowColor;
            } else {
                $img_shadow = '';
            }

            if ($border_color !== 'transparent') {
                $border_color = '#' . $border_color;
            }

            if (strpos($hover_color, '#') === false) {
                $hover_color = '#' . $hover_color;
            }

            if (strpos($hover_title_color, '#') === false) {
                $hover_title_color = '#' . $hover_title_color;
            }

            if (strpos($hover_desc_color, '#') === false) {
                $hover_desc_color = '#' . $hover_desc_color;
            }

            $shortcode = '[wpmfgallery';
            $shortcode .= ' gallery_id="' . $gallery_id . '"';
            $shortcode .= ' display="' . $display . '"';
            $shortcode .= ' layout="' . $layout . '"';
            $shortcode .= ' row_height="' . $row_height . '"';
            $shortcode .= ' aspect_ratio="' . $aspect_ratio . '"';
            $shortcode .= ' size="' . $size . '"';
            $shortcode .= ' crop_image="' . $crop_image . '"';
            $shortcode .= ' columns="' . $columns . '"';
            $shortcode .= ' targetsize="' . $targetsize . '"';
            $shortcode .= ' link="none"';
            $shortcode .= ' wpmf_orderby="' . $orderby . '"';
            $shortcode .= ' wpmf_order="' . $order . '"';
            $shortcode .= ' display_tree="' . $display_tree . '"';
            $shortcode .= ' display_tag="' . $display_tag . '"';
            $shortcode .= ' notlazyload="1"';
            $shortcode .= ' gutterwidth="' . $gutterwidth . '"';
            $shortcode .= ' img_border_radius="' . $img_border_radius . '"';
            $shortcode .= ' border_width="' . $border_width . '"';
            $shortcode .= ' border_color="' . $border_color . '"';
            $shortcode .= ' border_style="' . $border_style . '"';
            $shortcode .= ' img_shadow="' . $img_shadow . '"';
            $shortcode .= ' show_buttons="' . $show_buttons . '"';
            $shortcode .= ' animation="' . $animation . '"';
            $shortcode .= ' duration="' . $duration . '"';
            $shortcode .= ' auto_animation="' . $auto_animation . '"';
            $shortcode .= ' number_lines="' . $number_lines . '"';
            $shortcode .= ' hover_color="' . $hover_color . '"';
            $shortcode .= ' hover_opacity="' . $hover_opacity . '"';
            $shortcode .= ' hover_title_position="' . $hover_title_position . '"';
            $shortcode .= ' hover_title_size="' . $hover_title_size . '"';
            $shortcode .= ' hover_title_color="' . $hover_title_color . '"';
            $shortcode .= ' hover_desc_position="' . $hover_desc_position . '"';
            $shortcode .= ' hover_desc_size="' . $hover_desc_size . '"';
            $shortcode .= ' hover_desc_color="' . $hover_desc_color . '"';
            $shortcode .= ' is_divi="1"';

            $shortcode .= ']';
            $html = do_shortcode($shortcode, true);
            wp_send_json(array('status' => true, 'html' => $html, 'theme' => $display, 'title' => $gallery->name));
        }

        wp_send_json(array('status' => false));
    }
}
