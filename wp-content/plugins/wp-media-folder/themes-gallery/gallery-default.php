<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
wp_enqueue_script('wpmf-gallery');

$class_default = array();
$class_default[] = 'gallery gallery_life wpmf_gallery_default gallery_default';
$class_default[] = 'gallery-columns-' . $columns;
$class_default[] = 'gallery-size-' . $size_class;
$class_default[] = 'gallery-link-' . $link;
$class_default[] = 'wpmf-has-border-radius-' . $img_border_radius;
$class_default[] = 'wpmf-gutterwidth-' . $gutterwidth;
$style = '';
if ($img_shadow !== '') {
    $style .= '#' . $selector . ' .wpmf-gallery-item img:hover {box-shadow: ' . $img_shadow . ' !important; transition: all 200ms ease;}';
}

if ($border_style !== 'none') {
    $style .= '#' . $selector . ' .wpmf-gallery-item img {border: ' . $border_color . ' '. $border_width .'px '. $border_style .'}';
}
wp_add_inline_style('wpmf-gallery-style', $style);
$output = '';
if (!empty($is_divi)) {
    $output .= '<style>' . $style . '</style>';
}
$output .= '<div class="wpmf-gallerys wpmf-gallerys-life">';
$output .= '<div id="' . $selector . '" class="' . implode(' ', $class_default) . '">';

$pos = 0;
foreach ($gallery_items as $item_id => $attachment) {
    if (strpos($attachment->post_excerpt, '<script>') !== false) {
        $post_excerpt = esc_html($attachment->post_excerpt);
    } else {
        $post_excerpt = $attachment->post_excerpt;
    }

    $link_target = get_post_meta($attachment->ID, '_gallery_link_target', true);
    $link_target = ($link_target !== '') ? $link_target : '_self';
    $downloads = $this->wpmfGalleryGetDownloadLink($attachment->ID);
    switch ($link) {
        case 'file':
            $image_output = $this->getAttachmentLink($item_id, $size, false, $targetsize, false, $link_target);
            break;
        case 'post':
            $image_output = $this->getAttachmentLink($item_id, $size, true, $targetsize, false, $link_target);
            break;
        case 'none':
            $image_output = wp_get_attachment_image($item_id, $size, false, array('data-type' => 'wpmfgalleryimg'));
            break;
        case 'custom':
            $image_output = $this->getAttachmentLink($item_id, $size, false, $targetsize, true, $link_target);
            break;
        default:
            $image_output = $this->getAttachmentLink($item_id, $size, false, $targetsize, false, $link_target);
    }

    if ($enable_download) {
        $image_output .= '<a href="'.esc_url($downloads['download_link']).'" '. (($downloads['type'] === 'local') ? 'download' : '') .' class="wpmf_gallery_download_icon"><span class="material-icons-outlined"> file_download </span></a>';
    }

    $output .= '<figure class="wpmf-gallery-item" data-index="'. esc_attr($pos) .'">';
    $output .= '<div class="wpmf-gallery-icon">';
    $output .= wpmfRenderVideoIcon($attachment->ID);
    $output .= $image_output;
    $output .= '</div>';
    if (trim($post_excerpt) !== '') {
        $output .= '<figcaption class="wp-caption-text gallery-caption">';
        $output .= wptexturize($post_excerpt);
        $output .= '</figcaption>';
    }
    $output .= '</figure>';
    $pos++;
}
$output .= '</div></div>';
