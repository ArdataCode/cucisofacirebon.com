<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
if (!class_exists('ET_Builder_Element')) {
    return;
}

$enable_singlefile = get_option('wpmf_option_singlefile');
if (isset($enable_singlefile) && (int)$enable_singlefile === 1) {
    require_once WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/divi-widgets/includes/modules/FileDesign/FileDesign.php';
}

$enable_gallery = get_option('wpmf_usegellery');
if (isset($enable_gallery) && (int)$enable_gallery === 1) {
    require_once WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/divi-widgets/includes/modules/Gallery/Gallery.php';
}

if (is_plugin_active('wp-media-folder-gallery-addon/wp-media-folder-gallery-addon.php')) {
    require_once WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/divi-widgets/includes/modules/GalleryAddon/GalleryAddon.php';
}

require_once WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/divi-widgets/includes/modules/PdfEmbed/PdfEmbed.php';
