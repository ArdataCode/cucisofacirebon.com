<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
?>
<div class="block-item ju-settings-option cboption">
    <div class="wpmf_row_full">
        <input type="hidden" name="wpmf_gallery_settings[progressive_loading]" value="0">
        <label data-wpmftippy="<?php esc_html_e('Load your images on page scroll, recommended for better
         performance to a large amount of images on the same page', 'wp-media-folder-gallery-addon'); ?>" class="ju-setting-label text">
            <?php esc_html_e('Progressive loading', 'wp-media-folder-gallery-addon') ?>
        </label>
        <div class="ju-switch-button">
            <label class="switch">
                <?php
                if (isset($gallery_configs['progressive_loading']) && (int) $gallery_configs['progressive_loading'] === 0) :
                    ?>
                    <input type="checkbox" name="wpmf_gallery_settings[progressive_loading]" value="1">
                <?php else : ?>
                    <input type="checkbox" name="wpmf_gallery_settings[progressive_loading]" checked value="1">
                <?php endif; ?>
                <span class="slider round"></span>
            </label>
        </div>
    </div>
</div>

<div class="block-item ju-settings-option cboption wpmf_right m-r-0">
    <div class="wpmf_row_full">
        <input type="hidden" name="wpmf_gallery_settings[hover_image]" value="0">
        <label data-wpmftippy="<?php esc_html_e('Mouse hover background color on image thumbnail', 'wp-media-folder-gallery-addon'); ?>" class="ju-setting-label text">
            <?php esc_html_e('Hover image', 'wp-media-folder-gallery-addon') ?>
        </label>
        <div class="ju-switch-button">
            <label class="switch">
                <?php
                if (isset($gallery_configs['hover_image']) && (int) $gallery_configs['hover_image'] === 0) :
                    ?>
                    <input type="checkbox" name="wpmf_gallery_settings[hover_image]" value="1">
                <?php else : ?>
                    <input type="checkbox" name="wpmf_gallery_settings[hover_image]" checked value="1">
                <?php endif; ?>
                <span class="slider round"></span>
            </label>
        </div>
    </div>
</div>

<div class="block-item ju-settings-option cboption">
    <div class="wpmf_row_full">
        <input type="hidden" name="wpmf_gallery_settings[download_item]" value="0">
        <label data-wpmftippy="<?php esc_html_e('Download image from gallery', 'wp-media-folder-gallery-addon'); ?>" class="ju-setting-label text">
            <?php esc_html_e('Download icon', 'wp-media-folder-gallery-addon') ?>
        </label>
        <div class="ju-switch-button">
            <label class="switch">
                <?php
                if (isset($gallery_configs['download_item']) && (int)$gallery_configs['download_item'] === 1) :
                    ?>
                    <input type="checkbox" name="wpmf_gallery_settings[download_item]" checked value="1">
                <?php else : ?>
                    <input type="checkbox" name="wpmf_gallery_settings[download_item]" value="1">
                <?php endif; ?>
                <span class="slider round"></span>
            </label>
        </div>
    </div>
</div>

<div class="block-item ju-settings-option wpmf_width_100 p-lr-20 cboption">
    <?php
    // For setting default theme
    // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
    echo $default_theme;
    ?>
</div>

<div class="block-item ju-settings-option wpmf_width_100 p-lr-20 cboption">
    <?php
    // For setting portfolio theme
    // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
    echo $portfolio_theme;
    ?>
</div>

<div class="block-item ju-settings-option wpmf_width_100 p-lr-20 cboption">
    <?php
    // For setting masonry theme
    // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
    echo $masonry_theme;
    ?>
</div>

<div class="block-item ju-settings-option wpmf_width_100 p-lr-20 cboption">
    <?php
    // For setting slider theme
    // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
    echo $slider_theme;
    ?>
</div>
<div class="block-item ju-settings-option wpmf_width_100 p-lr-20 cboption">
    <?php
    // For setting flow slide theme
    // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
    echo $flowslide_theme;
    ?>
</div>

<div class="block-item ju-settings-option wpmf_width_100 p-lr-20 cboption">
    <?php
    // For setting square grid theme
    // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
    echo $square_grid_theme;
    ?>
</div>

<div class="block-item ju-settings-option wpmf_width_100 p-lr-20 cboption">
    <?php
    // For setting material theme
    // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
    echo $material_theme;
    ?>
</div>
