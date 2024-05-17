<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
$galleries = get_categories(
    array(
        'hide_empty' => false,
        'taxonomy'   => WPMF_GALLERY_ADDON_TAXO
    )
);

$themes = array(
    'default'     => __('Default', 'wp-media-folder-gallery-addon'),
    'masonry'     => __('Masonry', 'wp-media-folder-gallery-addon'),
    'portfolio'   => __('Portfolio', 'wp-media-folder-gallery-addon'),
    'slider'      => __('Slider', 'wp-media-folder-gallery-addon'),
    'flowslide'   => __('Flow slide', 'wp-media-folder-gallery-addon'),
    'square_grid' => __('Square grid', 'wp-media-folder-gallery-addon'),
    'material'    => __('Material', 'wp-media-folder-gallery-addon')
);
?>
<div class="block-item cboption wpmf_width_100 wpmf-no-padding wpmf-no-shadow">
    <h3 class="p-d-10 gallery-shortcode-title"><?php esc_html_e('WP Media Folder Galleries Shortcode', 'wp-media-folder-gallery-addon'); ?></h3>
    <div class="block-item ju-settings-option cboption wpmf_width_40 wpmf-no-shadow p-d-10">
        <div class="wpmf_row_full">
            <label class="ju-setting-label text wpmf-no-padding wpmf-bold"
                   data-wpmftippy="<?php esc_html_e('Load gallery tree navigation', 'wp-media-folder-gallery-addon'); ?>">
                <?php esc_html_e('Display gallery navigation', 'wp-media-folder-gallery-addon'); ?>
            </label>

            <input type="hidden" name="gallery_shortcode[display_tree]"
                   value="0" data-param="display_tree">
            <span class="ju-switch-button">
                    <label class="switch">
                        <?php if (isset($shortcode_configs['display_tree'])
                                  && (int) $shortcode_configs['display_tree'] === 1) : ?>
                            <input type="checkbox" class="shortcode_param" data-param="display_tree"
                                   name="gallery_shortcode[display_tree]"
                                   value="1" checked>
                        <?php else : ?>
                            <input type="checkbox" class="shortcode_param" data-param="display_tree"
                                   name="gallery_shortcode[display_tree]"
                                   value="1">
                        <?php endif; ?>
                        <span class="slider round"></span>
                    </label>
                </span>
        </div>

        <div class="wpmf_row_full">
            <label class="ju-setting-label text wpmf-no-padding wpmf-bold" data-wpmftippy="<?php esc_html_e('Display image tag as display
         filter', 'wp-media-folder-gallery-addon'); ?>">
                <?php esc_html_e('Display images tags', 'wp-media-folder-gallery-addon'); ?>
            </label>

            <input type="hidden" name="gallery_shortcode[display_tag]"
                   value="0" data-param="display_tag">
            <span class="ju-switch-button">
                <label class="switch">
                    <?php if (isset($shortcode_configs['display_tag'])
                              && (int) $shortcode_configs['display_tag'] === 1) : ?>
                        <input type="checkbox" class="shortcode_param" data-param="display_tag"
                               name="gallery_shortcode[display_tag]"
                               value="1" checked>
                    <?php else : ?>
                        <input type="checkbox" class="shortcode_param" data-param="display_tag"
                               name="gallery_shortcode[display_tag]"
                               value="1">
                    <?php endif; ?>
                    <span class="slider round"></span>
                </label>
            </span>
        </div>
    </div>

    <div class="block-item ju-settings-option wpmf_width_100 wpmf-no-padding wpmf-no-shadow">
        <div class="block-item ju-settings-option wpmf_width_20 p-d-10 wpmf-no-shadow">
            <div class="wpmf_row_full">
                <div class="gallery_shortcode_settings">
                    <label class="wpmf_width_100 p-b-20 wpmf_left text label_text">
                        <?php esc_html_e('Choose a gallery', 'wp-media-folder-gallery-addon'); ?>
                    </label>

                    <label>
                        <select name="gallery_shortcode[choose_gallery_id]" class="choose_gallery_id shortcode_param">
                            <option value="0"><?php esc_html_e('Choose a gallery', 'wp-media-folder-gallery-addon') ?></option>
                            <?php foreach ($galleries as $gallery) : ?>
                                <?php if (isset($shortcode_configs['choose_gallery_id'])
                                          && (int) $shortcode_configs['choose_gallery_id'] === (int) $gallery->term_id) : ?>
                                    <option value="<?php echo esc_html($gallery->term_id) ?>" selected>
                                        <?php echo esc_html($gallery->name) ?>
                                    </option>
                                <?php else : ?>
                                    <option value="<?php echo esc_html($gallery->term_id) ?>">
                                        <?php echo esc_html($gallery->name) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
            </div>
        </div>

        <div class="block-item ju-settings-option wpmf_width_20 p-d-10 wpmf-no-shadow">
            <div class="wpmf_row_full">
                <div class="gallery_shortcode_settings">
                    <label class="wpmf_width_100 p-b-20 wpmf_left text label_text">
                        <?php esc_html_e('Choose a theme', 'wp-media-folder-gallery-addon'); ?>
                    </label>

                    <label>
                        <select name="gallery_shortcode[choose_gallery_theme]" class="choose_gallery_theme">
                            <option value="default"><?php esc_html_e('Choose a theme', 'wp-media-folder-gallery-addon') ?></option>
                            <?php foreach ($themes as $theme_key => $theme_label) { ?>
                                <?php if (isset($shortcode_configs['choose_gallery_theme'])
                                          && $shortcode_configs['choose_gallery_theme'] === $theme_key) : ?>
                                    <option value="<?php echo esc_html($theme_key) ?>" selected>
                                        <?php echo esc_html($theme_label) ?>
                                    </option>
                                <?php else : ?>
                                    <option value="<?php echo esc_html($theme_key) ?>">
                                        <?php echo esc_html($theme_label) ?>
                                    </option>
                                <?php endif; ?>


                            <?php } ?>
                        </select>
                    </label>
                </div>
            </div>
        </div>

        <?php
        // phpcs:disable WordPress.Security.EscapeOutput -- Content already escaped in the method
        // For setting default theme
        echo $default_theme;
        // For setting portfolio theme
        echo $portfolio_theme;
        // For setting masonry theme
        echo $masonry_theme;
        // For setting slider theme
        echo $slider_theme;
        // For setting flow slide theme
        echo $flowslide_theme;
        // For setting square grid theme
        echo $square_grid_theme;
        // For setting material theme
        echo $material_theme;
        // phpcs:enable
        ?>
        <div class="block-item ju-settings-option wpmf_width_100 p-d-10 wpmf-no-margin wpmf-no-shadow">
            <div class="wpmf_row_full" style="margin: 0 0 10px 0;">
                <label class="wpmf_width_100 p-b-20 wpmf_left text label_text" style="width: 100%">
                    <?php esc_html_e('Shortcode', 'wp-media-folder-gallery-addon') ?>
                </label>
                <input title type="text" name="gallery_shortcode[gallery_shortcode_input]"
                       class="gallery_shortcode_input regular-text"
                       value="<?php echo esc_attr(stripslashes($shortcode_configs['gallery_shortcode_input'])) ?>">
                <i data-wpmftippy="<?php esc_html_e('Copy shortcode', 'wp-media-folder-gallery-addon'); ?>"
                   class="material-icons copy_shortcode_gallery wpmftippy">content_copy</i>
            </div>
        </div>
    </div>
</div>