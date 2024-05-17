<?php
if (empty($aspect_ratio)) {
    $aspect_ratio = '1_1';
}
$class[] = 'wpmf_gallery_default gallery_default glrdefault gallery-portfolio';
$class[] = 'galleryid-' . $id;
$class[] = 'gallery-columns-' . $columns;
$class[] = 'gallery-size-' . $size;
$class[] = 'wpmf-has-border-radius-' . $img_border_radius;
$class[] = 'wpmf-gutterwidth-' . $gutterwidth;
$class[] = 'ratio_' . $aspect_ratio;
$class[] = 'wpmf-gallery-clear';
$class = implode(' ', $class);

$padding_masonry = get_option('wpmf_padding_portfolio');
if (!isset($padding_masonry) && $padding_masonry === '') {
    $padding_masonry = 10;
}
$gutterwidth = isset($gutterwidth) ? $gutterwidth : $padding_masonry;
$lists = array();
$lists_full = array();
$j = 0;
$lightbox_items = $this->getLightboxItems($attachments, $targetsize);
foreach ($attachments as $index => $attachment) {
    $item = $this->getAttachmentThemeHtml('portfolio', $attachment, $social, $index, $params);
    if (!$lazy_load) {
        $lists[] = $item;
    } else {
        if ($j >= 8) {
            $lists[] = $item;
        }
        $j++;
    }
    $lists_full[] = $item;
}

$style = '';
if ($img_shadow !== '') {
    $style .= '#' . $selector . ' .wpmf-gallery-item img:not(.glrsocial_image):hover, #' . $selector . ' .wpmf-gallery-item .wpmf_overlay {box-shadow: ' . $img_shadow . ' !important; transition: all 200ms ease;}';
}

if ($border_style !== 'none') {
    $style .= '#' . $selector . ' .wpmf-gallery-item img:not(.glrsocial_image) {border: ' . $border_color . ' ' . $border_width . 'px ' . $border_style . '}';
}

wp_add_inline_style('wpmf-gallery-style', $style);
if (isset($is_divi) && (int)$is_divi === 1) {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This variable is html
    echo '<style>' . $style . '</style>';
}
echo '<div class="wpmf-gallerys wpmf-gallerys-addon" data-theme="'. esc_attr($display) .'" data-id="' . esc_html($id) . '">';
echo '<div id="' . esc_attr($selector) . '" data-count="' . count($lists_full) . '" class="' . esc_attr($class) . '" data-gutter-width="' . esc_attr($gutterwidth) . '"
data-wpmfcolumns="' . esc_attr($columns) . '" data-lightbox-items="'. esc_attr(json_encode($lightbox_items)) .'">';
foreach ($lists_full as $i => $list) {
    if (!$lazy_load) {
        echo $list; // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
    } else {
        if ($i < 8) {
            echo $list; // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
        }
    }
}

echo "</div></div>\n";
