<?php
$gallery_configs = get_option('wpmf_gallery_settings');
$lightbox_items = $this->getLightboxItems($attachments, $targetsize);
$class[] = 'wpmf-custom-grid';
$class[] = 'wpmf-has-border-radius-' . $img_border_radius;
$class[] = 'wpmfgutter-' . $gutterwidth;
$crop = (isset($crop_image)) ? $crop_image : 1;
if ((int)$columns === 1) {
    $crop = 0;
}

$class = implode(' ', $class);

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
if (isset($is_divi) && (int)$is_divi === 1) {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This variable is html
    echo '<style>' . $style . '</style>';
}
echo '<div class="wpmf-gallerys wpmf-gallerys-addon" data-theme="'. esc_attr($display) .'">';
echo '<div id="' . esc_attr($selector) . '" data-id="' . esc_attr($selector) . '" class="' . esc_attr($class) . '" data-border-width="' . esc_attr($border_width) . '" data-wpmfcolumns="' . esc_attr($columns) . '"
 data-lightbox-items="'. esc_attr(json_encode($lightbox_items)) .'" data-gutter="'. (int)$gutterwidth .'">';
$i = 0;
foreach ($attachments as $index => $attachment) {
    if ($display === 'custom_grid') {
        $grid_item_styles = (isset($grid_styles) && is_array($grid_styles) && isset($grid_styles['attachment-' . $attachment->ID])) ? $grid_styles['attachment-' . $attachment->ID] : '';
    } else {
        $grid_item_styles = '';
    }

    $hovers = $this->renderHoverStyle($attachment, $params);
    $hover_color_style = $hovers['hover_color_style'];
    $hover_box = $hovers['hover_box'];
    $post_title = (!empty($caption_lightbox) && $attachment->post_excerpt !== '') ? $attachment->post_excerpt : $attachment->post_title;
    $post_excerpt = esc_html($attachment->post_excerpt);
    $img_tags = get_post_meta($attachment->ID, 'wpmf_img_tags', true);
    $link_target = get_post_meta($attachment->ID, '_gallery_link_target', true);
    $custom_link = get_post_meta($attachment->ID, _WPMF_GALLERY_PREFIX . 'custom_image_link', true);
    $lightbox = 0;
    $url = '';
    if ($custom_link !== '') {
        $image_output = $this->galleryGetAttachmentLink($attachment->ID, $size, false);
        $icon = '<a '. $hover_color_style .' href="' . $custom_link . '" title="' . esc_attr($post_title) . '" class="wpmf_overlay" target="' . $link_target . '">'. $hover_box .'</a>';
        $icon .= $social;
    } else {
        switch ($link) {
            case 'none':
                $icon = '<div '. $hover_color_style .' class="wpmf_overlay">'. $hover_box .'</div>';
                $icon .= $social;
                break;

            case 'post':
                $url = get_attachment_link($attachment->ID);
                $icon = '<a '. $hover_color_style .' href="' . esc_url($url) . '" title="' . esc_attr($post_title) . '" class="wpmf_overlay" target="' . $link_target . '">'. $hover_box .'</a>';
                $icon .= $social;
                break;

            default:
                $lightbox = 1;
                $remote_video = get_post_meta($attachment->ID, 'wpmf_remote_video_link', true);
                $item_urls = wp_get_attachment_image_url($attachment->ID, $targetsize);
                $url = (!empty($remote_video)) ? $remote_video : $item_urls;
                $icon = '<a '. $hover_color_style .' data-swipe="1" data-href="' . esc_url($url) . '" title="' . esc_attr($post_title) . '"
class="wpmfgalleryaddonswipe wpmf_overlay '. (!empty($remote_video) ? 'isvideo' : '') .'">'. $hover_box .'</a>';
                $icon .= $social;
        }
    }

    $downloads = wpmfGalleryGetDownloadLink($attachment->ID);
    if (isset($gallery_configs['download_item']) && (int)$gallery_configs['download_item'] === 1) {
        $icon .= '<a href="'.esc_url($downloads['download_link']).'" '. (($downloads['type'] === 'local') ? 'download' : '') .' class="wpmf_gallery_download_icon"><span class="material-icons-outlined"> file_download </span></a>';
    }
    echo '<div data-id="'. (int)$attachment->ID .'" data-styles="'. esc_html(json_encode($grid_item_styles)) .'" class="wpmf-gallery-item grid-item" data-index="'. esc_html($index) .'" data-tags="' . esc_html($img_tags) . '" style="opacity: 0;">';
    echo '<div class="wpmf-gallery-icon">';
    echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
    echo '<a data-swipe="'. esc_attr($lightbox) .'" href="' . esc_url($url) . '">';
    $thumb_id = wpmfGalleryGetVideoThumbID($attachment->ID);
    echo '<img src="'. esc_url(wp_get_attachment_image_url($thumb_id, $size)) .'">';
    echo '</a>';
    echo '</div>';
    echo '</div>';
}


echo '</div></div>';
