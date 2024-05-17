<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
wp_enqueue_script('jquery-masonry');
wp_enqueue_script('wpmf-gallery');
$class[] = 'gallery_life gallery-masonry gallery-portfolio';
$class[] = 'gallery-columns-' . $columns;
$class[] = 'gallery-size-' . $size_class;
$class[] = 'wpmf-gallery-bottomspace-' . $bottomspace;
$class[] = 'wpmf-gallery-clear';
$class[] = 'wpmf-has-border-radius-' . $img_border_radius;
$class[] = 'wpmf-gutterwidth-' . $gutterwidth;
$class = implode(' ', $class);

$padding_portfolio = get_option('wpmf_padding_portfolio');
if (!isset($padding_portfolio) && $padding_portfolio === '') {
    $padding_portfolio = 10;
}

$gutterwidth = isset($gutterwidth) ? $gutterwidth : $padding_portfolio;
$style = '';
if ($img_shadow !== '') {
    $style .= '#' . $selector . ' .wpmf-gallery-item .wpmf_overlay:hover {box-shadow: ' . $img_shadow . ' !important; transition: all 200ms ease;}';
}

if ($border_style !== 'none') {
    $style .= '#' . $selector . ' .wpmf-gallery-item img {border: ' . $border_color . ' '. $border_width .'px '. $border_style .'}';
}
wp_add_inline_style('wpmf-gallery-style', $style);
$output = '';
if (!empty($is_divi)) {
    $output .= '<style>' . $style . '</style>';
}
$output .= "<div class='wpmf-gallerys wpmf-gallerys-life'>";
$output .= '<div id="' . $selector . '"
 data-gutter-width="' . $gutterwidth . '"
  data-wpmfcolumns="' . $columns . '" class="' . $class . '">';

$pos = 0;
$current_theme = get_option('current_theme');
if (isset($current_theme) && $current_theme === 'Gleam') {
    $tclass = 'fancybox';
} else {
    $tclass = '';
}

foreach ($gallery_items as $item_id => $attachment) {
    $post_excerpt = $attachment->post_excerpt;
    $post_title = $attachment->post_title;
    $lb_title = (!empty($caption_lightbox) && $post_excerpt !=='') ? $post_excerpt : $post_title;
    $link_target = get_post_meta($attachment->ID, '_gallery_link_target', true);
    $link_target = ($link_target !== '') ? $link_target : '_self';
    $downloads = $this->wpmfGalleryGetDownloadLink($attachment->ID);
    switch ($link) {
        case 'file':
            $image_output = $this->getAttachmentLink($item_id, $size, false, $targetsize, false, $link_target);
            $remote_video = get_post_meta($item_id, 'wpmf_remote_video_link', true);
            $imgs_urls = wp_get_attachment_image_src($item_id, $targetsize);
            $lb = 1;

            $url = get_post_meta($attachment->ID, _WPMF_GALLERY_PREFIX . 'custom_image_link', true);
            if ($url !== '') {
                $lb = 0;
                $url_image = $url;
            } else {
                if ($targetsize) {
                    if ($attachment->post_mime_type === 'application/pdf') {
                        $url_image = wp_get_attachment_url($attachment->ID);
                        $link_target = '_blank';
                        $lb = 0;
                    } else {
                        $lb = 1;
                        $img = wp_get_attachment_image_src($item_id, $targetsize);
                        $url_image = $img[0];
                    }
                } else {
                    $url_image = $imgs_urls[0];
                }
            }

            if (!empty($remote_video)) {
                $icon = '<a data-lightbox="' . $lb . '" href="' . $remote_video . '"
 title="' . esc_attr($post_title) . '" class="wpmf_overlay ' . $tclass . ' isvideo" target="' . $link_target . '"></a>
 <a data-lightbox="' . $lb . '" class="portfolio_lightbox ' . $tclass . ' isvideo" href="' . $remote_video . '" target="' . $link_target . '" 
  title="' . esc_attr($post_title) . '">+</a>';
            } else {
                $icon = '<a data-lightbox="' . $lb . '" href="' . $url_image . '" title="' . esc_attr($lb_title) . '" 
 class="wpmf_overlay ' . $tclass . ' not_video" target="' . $link_target . '"></a>
 <a data-lightbox="' . $lb . '" class="portfolio_lightbox ' . $tclass . ' not_video" href="' . $url_image . '" title="' . esc_attr($lb_title) . '" 
   target="' . $link_target . '">+</a>';
            }
            break;
        case 'post':
            $image_output = $this->getAttachmentLink($item_id, $size, true, $targetsize, false, $link_target);
            $url_image = get_attachment_link($item_id);
            if ($attachment->post_mime_type === 'application/pdf') {
                $url_image = wp_get_attachment_url($attachment->ID);
                $link_target = '_blank';
            } else {
                $url_image = get_attachment_link($item_id);
            }

            $icon = '<a href="' . $url_image . '" title="' . esc_attr($post_title) . '" class="wpmf_overlay ' . $tclass . '" target="' . $link_target . '"></a>';
            $icon .= '<a class="portfolio_lightbox ' . $tclass . '" href="' . $url_image . '" target="' . $link_target . '">+</a>';
            break;
        case 'none':
            $image_output = wp_get_attachment_image($item_id, $size, false, array('data-type' => 'wpmfgalleryimg'));
            $icon = '<span class="wpmf_overlay"></span><span class="hide_icon portfolio_lightbox">+</span>';
            break;
        case 'custom':
            $image_output = $this->getAttachmentLink($item_id, $size, false, $targetsize, true, $link_target);
            $url_image = get_post_meta($item_id, _WPMF_GALLERY_PREFIX . 'custom_image_link', true);
            if ($url_image === '') {
                $url_image = get_attachment_link($item_id);
            }

            $icon = '<a href="' . $url_image . '" title="' . esc_attr($post_title) . '" class="wpmf_overlay ' . $tclass . '" target="' . $link_target . '"></a>';
            $icon .= '<a class="portfolio_lightbox ' . $tclass . '" href="' . $url_image . '" target="' . $link_target . '">+</a>';
            break;
        default:
            $image_output = $this->getAttachmentLink($item_id, $size, true, $targetsize, false, $link_target);
    }

    if ($enable_download) {
        $image_output .= '<a href="'.esc_url($downloads['download_link']).'" '. (($downloads['type'] === 'local') ? 'download' : '') .' class="wpmf_gallery_download_icon"><span class="material-icons-outlined"> file_download </span></a>';
    }

    $output .= '<div class="wpmf-gallery-item
     wpmf-gallery-item-position-'. $pos .' wpmf-gallery-item-attachment-' . $item_id . '" data-index="'. esc_attr($pos) .'">';
    $output .= '<div class="wpmf-gallery-icon">';
    $output .= wpmfRenderVideoIcon($attachment->ID);
    $output .= $icon;
    $output .= $image_output;
    $output .= '</div>';

    if (trim($attachment->post_excerpt) || trim($attachment->post_title)) {
        $output .= '<div class="wpmf-caption-text wpmf-gallery-caption">';
        if ($attachment->post_title !== '') {
            $output .= '<span class="title">' . wptexturize(esc_html($attachment->post_title)) . '</span>';
        }

        if ($attachment->post_excerpt !== '') {
            $output .= '<span class="excerpt">' . wptexturize($attachment->post_excerpt) . '</span>';
        }
        $output .= '</div>';
    }
    $output .= '</div>';

    $pos++;
}
$output .= "</div></div>\n";
