<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Class WpmfPdfEmbed
 * This class that holds most of the PDF embed functionality for Media Folder.
 */
class WpmfPdfEmbed
{

    /**
     * Wpmf_Pdf_Embed constructor.
     */
    public function __construct()
    {
        add_action('wp_enqueue_media', array($this, 'loadScript'));
        add_action('wp_enqueue_scripts', array($this, 'registerScript'));
        add_action('wp_enqueue_scripts', array($this, 'loadStyleScript'));
        add_filter('media_send_to_editor', array($this, 'addImageFiles'), 10, 3);
        add_filter('attachment_fields_to_edit', array($this, 'attachmentFieldsToEdit'), 10, 2);
        add_filter('attachment_fields_to_save', array($this, 'attachmentFieldsToSave'), 10, 2);
        add_action('enqueue_block_editor_assets', array($this, 'addEditorAssets'));
        add_shortcode('wpmfpdf', array($this, 'wpmfPdf'));
        add_filter('the_content', array($this, 'theContent'));
        if (defined('ELEMENTOR_VERSION') && version_compare(ELEMENTOR_VERSION, '3.5', '<')) {
            add_action('elementor/widgets/widgets_registered', array($this, 'loadElementorWidget'));
        } else {
            add_action('elementor/widgets/register', array($this, 'loadElementorWidget'));
        }
        add_action('wp_ajax_wpmf_divi_load_pdf_embed_html', array($this, 'loadPdfEmbedHtml'));
    }

    /**
     * Load file design html
     *
     * @return void
     */
    public function loadPdfEmbedHtml()
    {
        if (empty($_REQUEST['et_admin_load_nonce'])
            || !wp_verify_nonce($_REQUEST['et_admin_load_nonce'], 'et_admin_load_nonce')) {
            wp_send_json(array('status' => false, 'html' => '<p>'. esc_html__('Have error when load html from URL', 'wpmf') .'</p>'));
        }

        if (empty($_REQUEST['url'])) {
            wp_send_json(array('status' => false, 'html' => '<p>'. esc_html__('Have error when load html from URL', 'wpmf') .'</p>'));
        }
        $url = str_replace(array('-pdf.jpg', '-pdf.jpeg', '-pdf.png'), '.pdf', $_REQUEST['url']);
        if (strpos($url, 'admin-ajax.php?action=wpmf') !== false) {
            if (!empty($_REQUEST['id'])) {
                $url .= '&id=' . $_REQUEST['id'] . '&dl=0';
            }
        }
        $width = (!empty($_REQUEST['width'])) ? $_REQUEST['width'] : '';
        $height = (!empty($_REQUEST['height'])) ? $_REQUEST['height'] : '';
        $html = do_shortcode('[wpmfpdf is_divi="1" url="'. $url .'" width="'. $width .'" height="'. $height .'" embed="'. $_REQUEST['embed'] .'" target="'. $_REQUEST['target'] .'"]');
        wp_send_json(array('status' => true, 'html' => $html));
    }

    /**
     * Load elementor widget
     *
     * @return void
     */
    public function loadElementorWidget()
    {
        require_once(WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/elementor-widgets/class-pdf-embed-elementor-widget.php');
        if (defined('ELEMENTOR_VERSION') && version_compare(ELEMENTOR_VERSION, '3.5', '<')) {
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \WpmfPdfEmbedElementorWidget());
        } else {
            \Elementor\Plugin::instance()->widgets_manager->register(new \WpmfPdfEmbedElementorWidget());
        }
    }

    /**
     * Enqueue styles and scripts for gutenberg
     *
     * @return void
     */
    public function addEditorAssets()
    {
        wp_enqueue_script(
            'wpmf_pdf_blocks',
            WPMF_PLUGIN_URL . 'assets/js/blocks/pdfembed/block.js',
            array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-data', 'wp-block-editor'),
            WPMF_VERSION
        );

        $params = array(
            'l18n' => array(
                'block_pdf_title'   => __('WP Media Folder PDF Embed', 'wpmf'),
            )
        );

        wp_localize_script('wpmf_pdf_blocks', 'wpmf_pdf_blocks', $params);
    }

    /**
     * Load PDF script
     *
     * @param string $content Content
     *
     * @return string
     */
    public function theContent($content)
    {
        if (strpos($content, 'wpmf-pdfemb-viewer') || strpos($content, 'data-wpmf_pdf_embed="embed"') || strpos($content, 'wpmfpdf')) {
            $this->enqueue();
        }
        return $content;
    }

    /**
     * Render PDF embed
     *
     * @param array $attrs Shortcode params
     *
     * @return string
     */
    public function wpmfPdf($attrs)
    {
        if (!empty($attrs['url'])) {
            $url = $attrs['url'];
            $title = basename($attrs['url']);
        } else {
            if (empty($attrs['id'])) {
                return '';
            }
            $url = wp_get_attachment_url($attrs['id']);
            $pdf = get_post($attrs['id']);
            if (empty($pdf)) {
                return '';
            }

            $cloud_type = get_post_meta((int)$attrs['id'], 'wpmf_drive_type', true);
            if ($cloud_type === 'dropbox') {
                $drive_id = get_post_meta((int)$attrs['id'], 'wpmf_drive_id', true);
                $url = admin_url('admin-ajax.php') . '?action=wpmf-dbxdownload-file&id=' . urlencode($drive_id) . '&link=true&dl=0';
            }

            if ($cloud_type === 'onedrive') {
                $drive_id = get_post_meta((int)$attrs['id'], 'wpmf_drive_id', true);
                $url = admin_url('admin-ajax.php') . '?action=wpmf_onedrive_download&id=' . urlencode($drive_id) . '&link=true&dl=0';
            }

            if ($cloud_type === 'onedrive_business') {
                $drive_id = get_post_meta((int)$attrs['id'], 'wpmf_drive_id', true);
                $url = admin_url('admin-ajax.php') . '?action=wpmf_onedrive_business_download&id=' . urlencode($drive_id) . '&link=true&dl=0';
            }

            if ($cloud_type === 'google_drive') {
                $drive_id = get_post_meta((int)$attrs['id'], 'wpmf_drive_id', true);
                $url = admin_url('admin-ajax.php') . '?action=wpmf-download-file&id=' . urlencode($drive_id) . '&link=true&dl=0';
            }

            $title = $pdf->post_title;
        }

        if (!empty($attrs['embed']) && $attrs['embed'] !== 'off') {
            if (empty($attrs['is_divi'])) {
                $this->enqueue();
            }
            $width = (!empty($attrs['width']) && strpos($attrs['width'], '%') === false) ? $attrs['width'] : '';
            $height = (!empty($attrs['height']) && strpos($attrs['height'], '%') === false) ? $attrs['height'] : '';
            $return = '<a class="wpmf-pdfemb-viewer" data-width="'. (int)$width .'" data-height="'. ((int)$height - 31) .'" href="'.esc_url($url).'">'.esc_html($title).'</a>';
        } else {
            $return = '<a href="'.esc_url($url).'" '. (($attrs['target'] !== '') ? 'target="'.esc_attr($attrs['target']).'"' : '') .'>'.esc_html($title).'</a>';
        }

        return $return;
    }

    /**
     * Load styles and scripts
     *
     * @return void
     */
    public function loadStyleScript()
    {
        global $post;
        if (!empty($post)) {
            $is_elementor = get_post_meta($post->ID, '_elementor_edit_mode', true);
            if ($is_elementor === 'builder') {
                $_elementor_data = get_post_meta($post->ID, '_elementor_data', true);
                if (!is_string($_elementor_data)) {
                    $_elementor_data = json_encode($_elementor_data);
                }
                if (strpos($_elementor_data, 'wpmf-pdfemb-viewer') || strpos($_elementor_data, 'data-wpmf_pdf_embed="embed"') || strpos($_elementor_data, 'wpmfpdf')) {
                    $this->enqueue();
                }
            } else {
                if (strpos($post->post_content, 'wpmf-pdfemb-viewer') || strpos($post->post_content, 'data-wpmf_pdf_embed="embed"') || strpos($post->post_content, 'wpmfpdf')) {
                    $this->enqueue();
                }
            }
        }
    }

    /**
     * Load script and style
     *
     * @return void
     */
    public function enqueue()
    {
        wp_enqueue_script('wpmf_embed_pdf_js');
        wp_localize_script(
            'wpmf_embed_pdf_js',
            'wpmf_pdfemb_trans',
            $this->getTranslation()
        );
        wp_enqueue_script('wpmf_compat_js');
        wp_enqueue_script('wpmf_pdf_js');
        wp_enqueue_style('pdfemb_embed_pdf_css');
    }

    /**
     * Localize a script.
     * Works only if the script has already been added.
     *
     * @return array
     */
    public function getTranslation()
    {
        $array = array(
            'worker_src' => plugins_url('assets/js/pdf-embed/pdf.worker.min.js', dirname(__FILE__)),
            'cmap_url'   => plugins_url('assets/js/pdf-embed/cmaps/', dirname(__FILE__)),
            'objectL10n' =>
                array(
                    'loading'            => __('Loading...', 'wpmf'),
                    'page'               => __('Page', 'wpmf'),
                    'zoom'               => __('Zoom', 'wpmf'),
                    'prev'               => __('Previous page', 'wpmf'),
                    'next'               => __('Next page', 'wpmf'),
                    'zoomin'             => __('Zoom In', 'wpmf'),
                    'zoomout'            => __('Zoom Out', 'wpmf'),
                    'secure'             => __('Secure', 'wpmf'),
                    'download'           => __('Download PDF', 'wpmf'),
                    'fullscreen'         => __('Full Screen', 'wpmf'),
                    'domainerror'        => __('Error: URL to the PDF file must be on exactly the same domain as the current web page.', 'wpmf'),
                    'clickhereinfo'      => __('Click here for more info', 'wpmf'),
                    'widthheightinvalid' => __('PDF page width or height are invalid', 'wpmf'),
                    'viewinfullscreen'   => __('View in Full Screen', 'wpmf'),
                    'poweredby'          => 1
                )
        );
        return $array;
    }

    /**
     * Add pdf embed html to editor
     *
     * @param string  $html       HTML markup for a media item sent to the editor.
     * @param integer $id         The first key from the $_POST['send'] data.
     * @param array   $attachment Array of attachment metadata.
     *
     * @return string $html
     */
    public function addImageFiles($html, $id, $attachment)
    {
        $post      = get_post($id);
        $mimetype  = explode('/', $post->post_mime_type);
        $pdf_embed = get_post_meta($id, 'wpmf_pdf_embed', true);
        $target    = get_post_meta($id, '_gallery_link_target', true);
        if ($mimetype[1] === 'pdf') {
            if (isset($pdf_embed) && $pdf_embed === 'embed') {
                $doc = new DOMDocument();
                libxml_use_internal_errors(true);
                $sousce = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
                $doc->loadHTML($sousce);
                $tags = $doc->getElementsByTagName('a');
                if ($tags->length > 0) {
                    if (!empty($tags)) {
                        $cloud_type = get_post_meta((int)$id, 'wpmf_drive_type', true);
                        if ($cloud_type === 'dropbox') {
                            $drive_id = get_post_meta((int)$id, 'wpmf_drive_id', true);
                            $url = admin_url('admin-ajax.php') . '?action=wpmf-dbxdownload-file&id=' . urlencode($drive_id) . '&link=true&dl=0';
                            $tags->item(0)->setAttribute('href', $url);
                        }

                        $class = $tags->item(0)->getAttribute('class');
                        if (!empty($class)) {
                            $newclass = $class . ' wpmf-pdfemb-viewer';
                        } else {
                            $newclass = 'wpmf-pdfemb-viewer';
                        }
                        $tags->item(0)->setAttribute('target', $target);
                        $tags->item(0)->setAttribute('class', $newclass);
                        $html = $doc->saveHTML();
                    }
                }
            } else {
                $singlefile = get_option('wpmf_option_singlefile');
                if (isset($singlefile) && (int) $singlefile === 1) {
                    $meta           = get_post_meta($id, '_wp_attached_file');
                    $upload_dir     = wp_upload_dir();
                    $url_attachment = $upload_dir['basedir'] . '/' . $meta[0];
                    if (file_exists($url_attachment)) {
                        $size = filesize($url_attachment);
                        if ($size < 1024 * 1024) {
                            $size = round($size / 1024, 1) . ' kB';
                        } elseif ($size > 1024 * 1024) {
                            $size = round($size / (1024 * 1024), 1) . ' MB';
                        }
                    } else {
                        $size = 0;
                    }

                    $type = wp_check_filetype($post->guid);
                    $ext  = $type['ext'];
                    $html = '<span class="wpmf_mce-wrap" data-file="' . $id . '" style="overflow: hidden;">';
                    $html .= '<a class="wpmf-defile wpmf_mce-single-child"
                     href="' . $post->guid . '" data-id="' . $id . '" target="' . $target . '">';
                    $html .= '<span class="wpmf_mce-single-child" style="font-weight: bold;">';
                    $html .= $post->post_title;
                    $html .= '</span><br>';
                    $html .= '<span class="wpmf_mce-single-child" style="font-weight: normal;font-size: 0.8em;">';
                    $html .= '<b class="wpmf_mce-single-child">' . esc_html__('Size', 'wpmf') . ' : </b>' . $size;
                    $html .= '<b class="wpmf_mce-single-child"> ' . esc_html__('Format', 'wpmf') . ' : </b>' . strtoupper($ext) . '</span>';
                    $html .= '</a>';
                    $html .= '</span>';
                } else {
                    $html = preg_replace('/(<a\b[^><]*)>/i', '$1 target="' . $target . '">', $html);
                }
            }
        }
        return $html;
    }

    /**
     * Add footer
     *
     * @return void
     */
    public function adminFooterPdfEmbed()
    {
        ?>
        <script type="text/javascript">
            if (typeof jQuery !== "undefined") {
                jQuery(function () {
                    if (typeof wp !== "undefined" && typeof wp.media !== "undefined" && typeof wp.media.events !== "undefined") {
                        wp.media.events.on('editor:image-edit', function (data) {
                            data.metadata.wpmf_pdf_embed = data.editor.dom.getAttrib(data.image, 'data-wpmf_pdf_embed');
                        });
                    }
                });
            }
        </script>
        <?php
    }

    /**
     * Add script
     *
     * @return void
     */
    public function registerScript()
    {
        wp_register_script(
            'wpmf_embed_pdf_js',
            plugins_url('assets/js/pdf-embed/all-pdfemb-basic.min.js', dirname(__FILE__)),
            array('jquery')
        );
        wp_localize_script(
            'wpmf_embed_pdf_js',
            'wpmf_pdfemb_trans',
            $this->getTranslation()
        );
        wp_register_script(
            'wpmf_compat_js',
            plugins_url('assets/js/pdf-embed/compatibility.js', dirname(__FILE__)),
            array('jquery')
        );
        wp_register_script(
            'wpmf_pdf_js',
            plugins_url('assets/js/pdf-embed/pdf.js', dirname(__FILE__)),
            array()
        );
        wp_register_style(
            'pdfemb_embed_pdf_css',
            plugins_url('assets/css/pdfemb-embed-pdf.css', dirname(__FILE__))
        );
    }

    /**
     * Add footer script
     *
     * @return void
     */
    public function loadScript()
    {
        add_action('admin_footer', array($this, 'adminFooterPdfEmbed'), 11);
        add_action('wp_footer', array($this, 'adminFooterPdfEmbed'), 11);
    }

    /**
     * Create enable PDF embed field
     * Based on /wp-admin/includes/media.php
     *
     * @param array   $form_fields An array of attachment form fields.
     * @param WP_Post $post        The WP_Post attachment object.
     *
     * @return mixed
     */
    public function attachmentFieldsToEdit($form_fields, $post)
    {
        if ($post->post_mime_type === 'application/pdf') {
            $value = get_post_meta($post->ID, 'wpmf_pdf_embed', true);
            if (empty($value)) {
                $value = 'large';
            }
            $embed  = array(
                'link'  => __('Off', 'wpmf'),
                'embed' => __('On', 'wpmf'),
            );
            $option = '';
            foreach ($embed as $k => $v) {
                if ($value === $k) {
                    $option .= '<option selected value="' . $k . '">' . $v . '</option>';
                } else {
                    $option .= '<option value="' . $k . '">' . $v . '</option>';
                }
            }
            $form_fields['wpmf_pdf_embed'] = array(
                'label' => __('PDF Embed', 'wpmf'),
                'input' => 'html',
                'html'  => '
                            <select name="attachments[' . $post->ID . '][wpmf_pdf_embed]"
                             id="attachments[' . $post->ID . '][wpmf_pdf_embed]">
                                    ' . $option . '
                            </select>'
            );
        }

        return $form_fields;
    }

    /**
     * Save enable PDF embed option
     * Based on /wp-admin/includes/media.php
     *
     * @param array $post       An array of post data.
     * @param array $attachment An array of attachment metadata.
     *
     * @return mixed $post
     */
    public function attachmentFieldsToSave($post, $attachment)
    {
        if (isset($attachment['wpmf_pdf_embed'])) {
            update_post_meta($post['ID'], 'wpmf_pdf_embed', $attachment['wpmf_pdf_embed']);
        }
        return $post;
    }
}
