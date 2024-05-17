<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

wp_enqueue_style('wpmf-slick-style');
wp_enqueue_style('wpmf-slick-theme-style');
wp_enqueue_script('wpmf-slick-script');
wp_enqueue_script('wpmf-gallery');

$class_default = array();
$class_default[] = 'gallery gallery_life wpmfslick wpmfslick_life';
$class_default[] = 'gallery-link-' . $link;
$class_default[] = 'wpmf-has-border-radius-' . $img_border_radius;
$class_default[] = 'wpmf-gutter-' . $gutterwidth;
$class_default[] = (((int)$columns > 1) ? 'wpmfslick_multiplecolumns' : 'wpmf-gg-one-columns');
$crop = (isset($crop_image)) ? $crop_image : 1;
if ((int)$columns === 1) {
    $crop = 0;
}
$class_default[] = 'wpmf-slick-crop-' . $crop;
$shadow = 0;
$style = '';
if ($img_shadow !== '') {
    if ((int)$columns > 1) {
        $style .= '#' . $selector . ' .wpmf-gallery-item .wpmf-gallery-icon:hover {box-shadow: ' . $img_shadow . ' !important; transition: all 200ms ease;}';
        $shadow = 1;
    }
}

if ((int)$gutterwidth === 0) {
    $shadow = 0;
}
if ($border_style !== 'none') {
    if ((int)$columns === 1) {
        $style .= '#' . $selector . ' .wpmf-gallery-item img:not(.glrsocial_image) {border: ' . $border_color . ' ' . $border_width . 'px ' . $border_style . ';}';
    } else {
        $style .= '#' . $selector . ' .wpmf-gallery-item .wpmf-gallery-icon {border: ' . $border_color . ' ' . $border_width . 'px ' . $border_style . ';}';
    }
} else {
    $border_width = 0;
}

wp_add_inline_style('wpmf-gallery-style', $style);
$output = '';
if (!empty($is_divi)) {
    $output .= '<style>' . $style . '</style>';
}
$output .= '<div class="wpmf-gallerys wpmf-gallerys-life">';
$output .= '<div id="' . $selector . '" data-id="' . $selector . '" data-gutterwidth="' . $gutterwidth . '" 
 class="' . implode(' ', $class_default) . '" data-count="'. esc_attr(count($gallery_items)) .'" data-wpmfcolumns="' . $columns . '" data-auto_animation="' . esc_html($autoplay) . '" data-duration="' . (int)$duration . '" data-border-width="' . $border_width . '" data-shadow="' . $shadow . '">';

$pos = 0;
$caption_lightbox = wpmfGetOption('caption_lightbox_gallery');
foreach ($gallery_items as $item_id => $attachment) {
    $post_title = (!empty($caption_lightbox) && $attachment->post_excerpt !== '') ? $attachment->post_excerpt : $attachment->post_title;
    $post_excerpt = esc_html($attachment->post_excerpt);
    $img_tags = get_post_meta($attachment->ID, 'wpmf_img_tags', true);
    $link_target = get_post_meta($attachment->ID, '_gallery_link_target', true);
    $custom_link = get_post_meta($attachment->ID, _WPMF_GALLERY_PREFIX . 'custom_image_link', true);
    $downloads = $this->wpmfGalleryGetDownloadLink($attachment->ID);
    $lightbox = 0;
    $url = '';
    if ($custom_link !== '') {
        $image_output = $this->getAttachmentLink($attachment->ID, $size, false, $targetsize, true, $link_target);
        $icon = '<a data-href="' . $custom_link . '" title="' . esc_attr($post_title) . '" class="wpmf_overlay" target="' . $link_target . '"></a>';
    } else {
        switch ($link) {
            case 'none':
                $icon = '<span class="wpmf_overlay"></span>';
                break;

            case 'post':
                $url = get_attachment_link($attachment->ID);
                $icon = '<a data-href="' . esc_url($url) . '" title="' . esc_attr($post_title) . '" class="wpmf_overlay" target="' . $link_target . '"></a>';
                break;

            default:
                $lightbox = 1;
                $remote_video = get_post_meta($attachment->ID, 'wpmf_remote_video_link', true);
                $item_urls = wp_get_attachment_image_url($attachment->ID, $targetsize);
                $url = (!empty($remote_video)) ? $remote_video : $item_urls;
                $icon = '<a data-lightbox="1" data-href="' . esc_url($url) . '" title="' . esc_attr($post_title) . '"
class="wpmfgalleryaddonswipe wpmf_overlay '. (!empty($remote_video) ? 'isvideo' : '') .'"></a>';
        }
    }

    if ($enable_download) {
        $icon .= '<a href="'.esc_url($downloads['download_link']).'" '. (($downloads['type'] === 'local') ? 'download' : '') .' class="wpmf_gallery_download_icon"><span class="material-icons-outlined"> file_download </span></a>';
    }

    $output .= '<div class="wpmf-gallery-item item" data-index="'. esc_attr($pos) .'" data-tags="' . esc_html($img_tags) . '" style="opacity: 0; padding: '. (int)$gutterwidth / 2 .'px">';
    $output .= '<div class="wpmf-gallery-icon">';
    $output .= wpmfRenderVideoIcon($attachment->ID);

    $output .= $icon; // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
    $output .= '<a class="'. (((int)$columns === 1) ? '' : 'square_thumbnail') .'" data-lightbox="'. esc_attr($lightbox) .'" data-href="' . esc_url($url) . '" title="'. esc_attr($post_title) .'">';
    if ((int)$columns > 1) {
        $output .= '<div class="img_centered">';
    }
    $output .= '<img class="wpmf_slider_img" alt="'. esc_attr($attachment->post_title) .'" src="'. esc_url(wp_get_attachment_image_url($attachment->ID, $size)) .'">';
    if ((int)$columns > 1) {
        $output .= '</div>';
    }
    $output .= '</a>';
    if (trim($attachment->post_excerpt) || trim($attachment->post_title)) {
        $output .= '<div class="wpmf-slick-text">';
        if (trim($attachment->post_title)) {
            $output .= '<span class="title">' . esc_html($attachment->post_title) . '</span>';
        }

        if (trim($attachment->post_excerpt)) {
            $output .= '<span class="caption">' . esc_html($attachment->post_excerpt) . '</span>';
        }
        $output .= '</div>';
    }
    $output .= '</div>';
    $output .= '</div>';
    $pos++;
}
$output .= '</div></div>';
