<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
?>
<div id="form_item_details" data-id="<?php echo esc_html($id) ?>"
     class="form_item_details form_item_details_popup full">
    <div class="wpmf-media-sidebar" data-id="<?php echo esc_html($id) ?>">
        <div>
            <div class="head_image_details">
                <div class="thumbnail-image">
                    <img src="<?php echo esc_html($thumb_url) ?>" draggable="false"
                         alt="<?php echo esc_html($alt) ?>">
                    <?php if ($video_url !== '') :?>
                    <input type="hidden" class="edit-video-thumbnail-id" value="<?php echo esc_attr($thumb_id) ?>">
                    <div class="video-thumbnail-action"><span class="material-icons-outlined edit-video-thumbnail"> mode_edit </span></div>
                    <?php endif; ?>
                </div>
            </div>

            <label class="setting">
                <span class="name"><?php esc_html_e('Title', 'wp-media-folder-gallery-addon') ?></span>
                <input type="text" class="img_title ju-input" value="<?php echo esc_html($details->post_title) ?>">
            </label>

            <label class="setting">
                <span class="name"><?php esc_html_e('Caption', 'wp-media-folder-gallery-addon') ?></span>
                <textarea class="img_excerpt ju-input"><?php echo esc_html($details->post_excerpt) ?></textarea>
            </label>

            <label class="setting">
                <span class="name"><?php esc_html_e('Alt Text', 'wp-media-folder-gallery-addon') ?></span>
                <input type="text" class="img_alt ju-input" value="<?php echo esc_html($alt) ?>">
            </label>

            <label class="setting">
                <span class="name"><?php esc_html_e('Link to', 'wp-media-folder-gallery-addon') ?></span>
                <input type="text" class="text custom_image_link ju-input" value="<?php echo esc_html($link_to) ?>">
                <button type="button" id="link-btn" class="link-btn"><i
                            class="zmdi zmdi-link wpmf-zmdi-link"></i></button>
            </label>

            <label class="setting">
                <span class="name"><?php esc_html_e('Link target', 'wp-media-folder-gallery-addon') ?></span>
                <select class="image_link_target ju-select">
                    <option value="" <?php selected($link_target, '') ?>>
                        <?php esc_html_e('Same Window', 'wp-media-folder-gallery-addon') ?>
                    </option>
                    <option value="_blank" <?php selected($link_target, '_blank') ?>>
                        <?php esc_html_e('New Window', 'wp-media-folder-gallery-addon') ?>
                    </option>
                </select>
            </label>

            <label class="setting">
                <span class="name"><?php esc_html_e('Image tags', 'wp-media-folder-gallery-addon') ?></span>
                <input type="text" class="img_tags ju-input" value="<?php echo esc_html($img_tags) ?>">
            </label>

            <label class="setting">
                <span class="name"><?php esc_html_e('Video URL', 'wp-media-folder-gallery-addon') ?></span>
                <input type="text" class="edit_video_url ju-input" value="<?php echo esc_html($video_url) ?>">
            </label>
        </div>
    </div>
</div>
