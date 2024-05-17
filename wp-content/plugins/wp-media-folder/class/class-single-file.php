<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
use Joomunited\WPMediaFolder\WpmfHelper;

/**
 * Class WpmfSingleFile
 * This class that holds most of the single file functionality for Media Folder.
 */
class WpmfSingleFile
{
    /**
     * Single_File constructor.
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'singleFileStyleAdmin'));
        add_filter('media_send_to_editor', array($this, 'addImageFiles'), 10, 3);
        add_action('wp_enqueue_scripts', array($this, 'loadCustomWpAdminScript'));
        add_action('admin_enqueue_scripts', array($this, 'loadCustomWpAdminScript'));
        add_filter('mce_external_plugins', array($this, 'register'));
        add_action('enqueue_block_editor_assets', array($this, 'addEditorAssets'));
        add_shortcode('wpmffiledesign', array($this, 'wpmfFileDesign'));
        if (defined('ELEMENTOR_VERSION') && version_compare(ELEMENTOR_VERSION, '3.5', '<')) {
            add_action('elementor/widgets/widgets_registered', array($this, 'loadElementorWidget'));
        } else {
            add_action('elementor/widgets/register', array($this, 'loadElementorWidget'));
        }
        add_action('wp_ajax_wpmf_divi_load_file_design_html', array($this, 'loadFileDesignHtml'));
    }

    /**
     * Load file design html
     *
     * @return void
     */
    public function loadFileDesignHtml()
    {
        if (empty($_REQUEST['et_admin_load_nonce'])
            || !wp_verify_nonce($_REQUEST['et_admin_load_nonce'], 'et_admin_load_nonce')) {
            wp_send_json(array('status' => false, 'html' => '<p>'. esc_html__('Have error when load html from URL', 'wpmf') .'</p>'));
        }

        if (empty($_REQUEST['url'])) {
            wp_send_json(array('status' => false, 'html' => '<p>'. esc_html__('Have error when load html from URL', 'wpmf') .'</p>'));
        }
        $html = do_shortcode('[wpmffiledesign url="'. $_REQUEST['url'] .'" align="'. $_REQUEST['align'] .'"]');
        wp_send_json(array('status' => true, 'html' => $html));
    }

    /**
     * Load elementor widget
     *
     * @return void
     */
    public function loadElementorWidget()
    {
        require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/elementor-widgets/class-file-design-elementor-widget.php');
        if (defined('ELEMENTOR_VERSION') && version_compare(ELEMENTOR_VERSION, '3.5', '<')) {
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \WpmfFileDesignElementorWidget());
        } else {
            \Elementor\Plugin::instance()->widgets_manager->register(new \WpmfFileDesignElementorWidget());
        }
    }

    /**
     * Render File Design by shortcode
     *
     * @param array $attrs Shortcode params
     *
     * @return string
     */
    public function wpmfFileDesign($attrs)
    {
        if (empty($attrs['url']) && empty($attrs['id'])) {
            return '';
        }

        if (!empty($attrs['url'])) {
            $file_id = WpmfHelper::attachmentUrlToPostid($attrs['url'], 'pdf');
        } else {
            $file_id = (int)$attrs['id'];
        }

        $file           = get_post($file_id);
        $mimetype       = explode('/', $file->post_mime_type);
        $target         = get_post_meta($file_id, '_gallery_link_target', true);
        $fileUrl  = wp_get_attachment_url($file_id);
        $filePath = get_attached_file($file_id);
        $meta = wp_get_attachment_metadata($file_id);
        if (isset($meta['filesize'])) {
            $size = $meta['filesize'];
        } elseif (file_exists($filePath)) {
            $size = filesize($filePath);
        } else {
            $size = 0;
        }

        if ($size < 1024 * 1024) {
            $size = round($size / 1024, 1) . ' kB';
        } elseif ($size > 1024 * 1024) {
            $size = round($size / (1024 * 1024), 1) . ' MB';
        }

        $style = array();
        $style[] = 'overflow: hidden';
        if (isset($attrs['align'])) {
            $style[] = 'text-align: ' . $attrs['align'];
        }
        $style = implode(';', $style);
        $html = '';
        if ($mimetype[0] !== 'image') {
            $type = pathinfo($fileUrl);
            if (isset($type['extension']) && $type['extension']) {
                $html .= '<div class="wpmf_mce-wrap" data-file="' . $file_id . '" style="'. $style .'">';
                $html .= '<a class="wpmf-defile wpmf_mce-single-child"
             href="' . $fileUrl . '" data-id="' . $file_id . '" target="' . $target . '" download>';
                $html .= '<span class="wpmf_mce-single-child" style="font-weight: bold; font-family: \'Arial\', sans-serif;line-height: 20px;">';
                $html .= $file->post_title;
                $html .= '</span><br>';
                $html .= '<span class="wpmf_mce-single-child" style="font-weight: normal;font-size: 0.8em; font-family: \'Arial\', sans-serif;line-height: 20px;">';
                $html .= '<b class="wpmf_mce-single-child">Size : </b>' . $size;
                $html .= '<b class="wpmf_mce-single-child"> Format : </b>' . strtoupper($type['extension']);
                $html .= '</span>';
                $html .= '</a>';
                $html .= '</div>';
            }
        }
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
            'wpmf_filedesign_blocks',
            WPMF_PLUGIN_URL . 'assets/js/blocks/file_design/block.js',
            array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-data', 'wp-block-editor'),
            WPMF_VERSION
        );

        $params = array(
            'l18n' => array(),
            'vars' => array(
                'block_cover' => WPMF_PLUGIN_URL .'assets/js/blocks/file_design/preview.png'
            )
        );

        wp_localize_script('wpmf_filedesign_blocks', 'wpmf_filedesign_blocks', $params);
    }


    /**
     * Includes script to editor
     *
     * @param array $plugin_array List editor plugin
     *
     * @return mixed
     */
    public function register($plugin_array)
    {
        $url                      = WPMF_PLUGIN_URL . '/assets/js/single-file.js';
        $plugin_array['wpmf_mce'] = $url;
        return $plugin_array;
    }

    /**
     * Includes styles to editor
     *
     * @return void
     */
    public function singleFileStyleAdmin()
    {
        $upload_dir = wp_upload_dir();
        if (file_exists($upload_dir['basedir'] . '/wpmf/css/wpmf_single_file.css')) {
            add_editor_style($upload_dir['baseurl'] . '/wpmf/css/wpmf_single_file.css');
        }
    }

    /**
     * Includes styles
     *
     * @return void
     */
    public function loadCustomWpAdminScript()
    {
        $upload_dir = wp_upload_dir();
        if (file_exists($upload_dir['basedir'] . '/wpmf/css/wpmf_single_file.css')) {
            wp_enqueue_style(
                'wpmf-single-file',
                $upload_dir['baseurl'] . '/wpmf/css/wpmf_single_file.css',
                array(),
                WPMF_VERSION
            );
            wp_add_inline_style('wpmf-single-file', '.content-align-center.elementor-widget-wpmf_file_design .wpmf_mce-wrap {text-align: center;}.content-align-left.elementor-widget-wpmf_file_design .wpmf_mce-wrap {text-align: left;}.content-align-right.elementor-widget-wpmf_file_design .wpmf_mce-wrap {text-align: right;}.wpmf-defile{display: inline-block; float:none !important}.wpmf-defile span.wpmf_mce-single-child{float:left}');
        }
    }

    /**
     * Add single file to editor
     *
     * @param string  $html       HTML markup for a media item sent to the editor.
     * @param integer $id         The first key from the $_POST['send'] data.
     * @param array   $attachment Array of attachment metadata.
     *
     * @return string $html
     */
    public function addImageFiles($html, $id, $attachment)
    {
        $post           = get_post($id);
        $mimetype       = explode('/', $post->post_mime_type);
        $target         = get_post_meta($id, '_gallery_link_target', true);
        $fileUrl  = wp_get_attachment_url($id);
        $filePath = get_attached_file($id);
        $meta = wp_get_attachment_metadata($id);
        if (isset($meta['filesize'])) {
            $size = $meta['filesize'];
        } elseif (file_exists($filePath)) {
            $size = filesize($filePath);
        } else {
            $size = 0;
        }

        if ($size < 1024 * 1024) {
            $size = round($size / 1024, 1) . ' kB';
        } elseif ($size > 1024 * 1024) {
            $size = round($size / (1024 * 1024), 1) . ' MB';
        }

        if ($mimetype[0] === 'application' && $mimetype[1] !== 'pdf') {
            $type = wp_check_filetype($fileUrl);
            $ext  = $type['ext'];
            $html = '<div class="wpmf_mce-wrap" data-file="' . $id . '" style="overflow: hidden;">';
            $html .= '<a class="wpmf-defile wpmf_mce-single-child"
             href="' . $fileUrl . '" data-id="' . $id . '" target="' . $target . '" download>';
            $html .= '<span class="wpmf_mce-single-child" style="font-weight: bold;">';
            $html .= $post->post_title;
            $html .= '</span><br>';
            $html .= '<span class="wpmf_mce-single-child" style="font-weight: normal;font-size: 0.8em;">';
            $html .= '<b class="wpmf_mce-single-child">Size : </b>' . $size;
            $html .= '<b class="wpmf_mce-single-child"> Format : </b>' . strtoupper($ext);
            $html .= '</span>';
            $html .= '</a>';
            $html .= '</div>';
        }
        return $html;
    }
}
