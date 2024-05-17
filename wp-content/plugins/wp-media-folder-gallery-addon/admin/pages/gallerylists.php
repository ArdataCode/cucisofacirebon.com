<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
$default_params = wpmfGalleryAddonGetDefaultParams();
$params = array_merge($default_params, array(
    'idblock' => '',
    'gallery_id' => 0,
    'display' => 'default',
));

$ps = get_option('wpmf_galleries');
if (isset($_GET['gallery_id'])) {
    foreach ($params as $key => &$default) {
        if (isset($_GET[$key])) {
            if (in_array($key, array('hover_color', 'hover_title_color', 'hover_desc_color'))) {
                $default = '#' . trim($_GET[$key], '#');
            } else {
                $default = $_GET[$key];
            }
        } else {
            if (isset($ps[(int)$_GET['gallery_id']][$key])) {
                $default = $ps[(int)$_GET['gallery_id']][$key];
            }
        }
    }
}
// phpcs:enable
?>
<div id="WpmfGalleryList" data-idblock="<?php echo isset($idblock) ? esc_attr($idblock) : '' ?>"
     class="<?php echo (isset($type) && $type === 'iframe') ? 'wpmfgalleryiframeview' : '' ?> <?php echo (isset($editor_type) && $editor_type === 'wpmfgutenberg') ? 'wpmfgutenberg' : '' ?> WpmfGalleryList ju-main-wrapper" style="display: none">
    <?php wp_nonce_field('wpmfgallery', '_wpnonce', true, true); ?>
    <div id="gallerylist" class="gallerylist"
         data-edited="<?php echo esc_attr(json_encode($params)) ?>"
    >
        <div class="topbtn">
            <div class="ju-dropdown-wrap">
                <button class="add-gallery-popup">
                    <i class="zmdi zmdi-plus"></i>
                    <span><?php esc_html_e('Add New Gallery', 'wp-media-folder-gallery-addon') ?></span>
                </button>
                <ul class="ju-dropdown-menu form_add_gallery_wrap">
                    <li>
                        <a href="#new-gallery-popup" class="new-gallery-popup">
                            <i class="zmdi zmdi-plus"></i>
                            <span><?php esc_html_e('Create new media gallery', 'wp-media-folder-gallery-addon') ?></span>
                        </a>
                    </li>
                    <li>
                        <button class="btn_import_fromwp">
                            <i class="zmdi zmdi-folder-outline"></i>
                            <span><?php esc_html_e('Quick gallery from folder', 'wp-media-folder-gallery-addon') ?></span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="wpmf_search_gallery_wrap">
                <input type="text" class="wpmf_search_gallery_input" placeholder="<?php esc_html_e('Filter galleries...', 'wp-media-folder-gallery-addon') ?>">
                <i class="material-icons search_gallery_btn">search</i>
            </div>
        </div>
        <div class="scrollbar-inner tree-left-wrap">
            <div class="tree_view"></div>
        </div>
    </div>

    <?php
    require_once(WPMF_GALLERY_ADDON_PLUGIN_DIR . '/admin/pages/form_gallery_edit.php');
    ?>
</div>