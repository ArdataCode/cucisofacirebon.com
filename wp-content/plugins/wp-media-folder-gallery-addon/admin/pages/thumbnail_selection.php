<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
$thumb_id = wpmfGalleryGetVideoThumbID($image->ID);
$thumnail = wp_get_attachment_image_url($thumb_id, 'thumbnail');
$thumnailUrl = wp_get_attachment_image_url($thumb_id, 'large');
$video_url = get_post_meta($image->ID, 'wpmf_remote_video_link', true);
$styles = (isset($grid_styles) && is_array($grid_styles) && isset($grid_styles['attachment-' . $image->ID])) ? $grid_styles['attachment-' . $image->ID] : array('width' => 2, 'height' => 2);


if ($thumnailUrl) : ?>
<div data-styles="<?php echo esc_html(json_encode($styles)) ?>" data-id="<?php echo esc_html($image->ID) ?>" class="gallery-attachment <?php echo ((int)$image->item_in_folder === 1) ? 'is_item_folder' : '' ?> <?php echo ((int)$feature_image_id === (int)$image->ID) ? 'is_feature_gallery' : '' ?>" data-thumbnail="<?php echo esc_url($thumnail) ?>">
    <div class="wpmfglr-attachment-preview">
        <?php if (!empty($video_url)) : ?>
        <i class="material-icons wpmf_gallery_video_icon">play_circle_filled</i>
        <?php endif; ?>
        <img src="<?php echo esc_html($thumnailUrl) ?>">
        <div class="hover_img">
            <div class="action_images">
                <a class="set_feature_image" title="<?php esc_html_e('Use as gallery cover', 'wp-media-folder-gallery-addon') ?>">
                    <i class="material-icons"> wallpaper </i>
                </a>
                <a class="edit_gallery_item">
                    <i class="material-icons"> tune </i>
                </a>
                <a class="delete_gallery_item">
                    <i class="material-icons"> delete_outline </i>
                </a>
            </div>
        </div>
        <i class="material-icons img-checked"> check_circle_outline </i>
    </div>
    <div class="wpmfsegrip"></div>
</div>
<?php endif; ?>