<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
?>
<div class="wpmf_row_full">
    <h4 class="settings_theme_name"><?php echo esc_html($theme_label); ?></h4>
    <div class="wpmf_gallery_settings">
        <?php if ($theme_name !== 'flowslide_theme') : ?>
            <div class="block-item ju-settings-option wpmf-no-shadow wpmf_width_20 p-d-10">
                <label class="wpmf_width_100 p-b-20 wpmf_left text label_text" data-wpmftippy="<?php esc_html_e('Number of columns
                 by default in the gallery theme', 'wp-media-folder-gallery-addon'); ?>">
                    <?php esc_html_e('Columns', 'wp-media-folder-gallery-addon'); ?>
                </label>

                <label>
                    <select class="columns"
                            name="wpmf_gallery_settings[theme][<?php echo esc_html($theme_name) ?>][columns]">
                        <?php for ($i = 1; $i <= 8; $i ++) { ?>
                            <option value="<?php echo esc_html($i) ?>" <?php selected((int) $settings['columns'], (int) $i) ?> >
                                <?php echo esc_html($i) ?>
                            </option>
                        <?php } ?>
                    </select>
                </label>
            </div>

            <div class="block-item ju-settings-option wpmf-no-shadow wpmf_width_20 p-d-10">
                <label class="wpmf_width_100 p-b-20 wpmf_left text label_text" data-wpmftippy="<?php esc_html_e('Image size to load
                 by default as thumbnail', 'wp-media-folder-gallery-addon'); ?>">
                    <?php esc_html_e('Gallery image size', 'wp-media-folder-gallery-addon'); ?>
                </label>
                <label class="size">
                    <select class="size" name="wpmf_gallery_settings[theme][<?php echo esc_html($theme_name) ?>][size]">
                        <?php
                        $sizes_value = json_decode(get_option('wpmf_gallery_image_size_value'));
                        $sizes       = apply_filters('image_size_names_choose', array(
                            'thumbnail' => __('Thumbnail', 'wp-media-folder-gallery-addon'),
                            'medium'    => __('Medium', 'wp-media-folder-gallery-addon'),
                            'large'     => __('Large', 'wp-media-folder-gallery-addon'),
                            'full'      => __('Full Size', 'wp-media-folder-gallery-addon'),
                        ));
                        ?>

                        <?php foreach ($sizes_value as $key) : ?>
                            <?php if (!empty($sizes[$key])) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($settings['size'], $key); ?>>
                                    <?php echo esc_html($sizes[$key]); ?>
                                </option>
                            <?php endif; ?>

                        <?php endforeach; ?>

                    </select>
                </label>
            </div>

            <div class="block-item ju-settings-option wpmf-no-shadow wpmf_width_20 p-d-10">
                <label class="wpmf_width_100 p-b-20 wpmf_left text label_text" data-wpmftippy="<?php esc_html_e('Image size to load by default as full
                 size (opened in the lightbox)', 'wp-media-folder-gallery-addon'); ?>">
                    <?php esc_html_e('Lightbox size', 'wp-media-folder-gallery-addon'); ?>
                </label>

                <label>
                    <select class="targetsize"
                            name="wpmf_gallery_settings[theme][<?php echo esc_html($theme_name) ?>][targetsize]">
                        <?php
                        $sizes = array(
                            'thumbnail' => __('Thumbnail', 'wp-media-folder-gallery-addon'),
                            'medium'    => __('Medium', 'wp-media-folder-gallery-addon'),
                            'large'     => __('Large', 'wp-media-folder-gallery-addon'),
                            'full'      => __('Full Size', 'wp-media-folder-gallery-addon'),
                        );
                        ?>

                        <?php foreach ($sizes as $key => $name) : ?>
                            <option value="<?php echo esc_attr($key); ?>"
                                <?php selected($settings['targetsize'], $key); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <div class="block-item ju-settings-option wpmf-no-shadow wpmf_width_20 p-d-10">
                <label class="wpmf_width_100 p-b-20 wpmf_left text label_text" data-wpmftippy="<?php esc_html_e('Action when the user
                 click on the image thumbnail', 'wp-media-folder-gallery-addon'); ?>">
                    <?php esc_html_e('Action on click', 'wp-media-folder-gallery-addon'); ?>
                </label>

                <label>
                    <select class="link-to"
                            name="wpmf_gallery_settings[theme][<?php echo esc_html($theme_name) ?>][link]">
                        <option value="file" <?php selected($settings['link'], 'file'); ?>>
                            <?php esc_html_e('Lightbox', 'wp-media-folder-gallery-addon'); ?>
                        </option>
                        <option value="post" <?php selected($settings['link'], 'post'); ?>>
                            <?php esc_html_e('Attachment Page', 'wp-media-folder-gallery-addon'); ?>
                        </option>
                        <option value="none" <?php selected($settings['link'], 'none'); ?>>
                            <?php esc_html_e('None', 'wp-media-folder-gallery-addon'); ?>
                        </option>
                    </select>
                </label>
            </div>

            <div class="block-item ju-settings-option wpmf-no-shadow wpmf_width_20 p-d-10">
                <label class="wpmf_width_100 p-b-20 wpmf_left text label_text" data-wpmftippy="<?php esc_html_e('Image gallery
                 default ordering', 'wp-media-folder-gallery-addon'); ?>">
                    <?php esc_html_e('Order by', 'wp-media-folder-gallery-addon'); ?>
                </label>

                <label>
                    <select class="wpmf_orderby"
                            name="wpmf_gallery_settings[theme][<?php echo esc_html($theme_name) ?>][orderby]">
                        <option value="post__in" <?php selected($settings['orderby'], 'post__in'); ?>>
                            <?php esc_html_e('Custom', 'wp-media-folder-gallery-addon'); ?>
                        </option>
                        <option value="rand" <?php selected($settings['orderby'], 'rand'); ?>>
                            <?php esc_html_e('Random', 'wp-media-folder-gallery-addon'); ?>
                        </option>
                        <option value="title" <?php selected($settings['orderby'], 'title'); ?>>
                            <?php esc_html_e('Title', 'wp-media-folder-gallery-addon'); ?>
                        </option>
                        <option value="date" <?php selected($settings['orderby'], 'date'); ?>>
                            <?php esc_html_e('Date', 'wp-media-folder-gallery-addon'); ?>
                        </option>
                    </select>
                </label>
            </div>

            <div class="block-item ju-settings-option wpmf-no-shadow wpmf_width_20 p-d-10">
                <label class="wpmf_width_100 p-b-20 wpmf_left text label_text" data-wpmftippy="<?php esc_html_e('By default, use ascending
                 or descending order', 'wp-media-folder-gallery-addon'); ?>">
                    <?php esc_html_e('Order', 'wp-media-folder-gallery-addon'); ?>
                </label>

                <label>
                    <select class="wpmf_order"
                            name="wpmf_gallery_settings[theme][<?php echo esc_html($theme_name) ?>][order]">
                        <option value="ASC" <?php selected($settings['order'], 'ASC'); ?>>
                            <?php esc_html_e('Ascending', 'wp-media-folder-gallery-addon'); ?>
                        </option>
                        <option value="DESC" <?php selected($settings['order'], 'DESC'); ?>>
                            <?php esc_html_e('Descending', 'wp-media-folder-gallery-addon'); ?>
                        </option>
                    </select>
                </label>
            </div>

            <?php if ($theme_name === 'slider_theme') : ?>
                <div class="block-item ju-settings-option wpmf-no-shadow wpmf_width_20 p-d-10">
                    <label class="wpmf_width_100 p-b-20 wpmf_left text label_text">
                        <?php esc_html_e('Transition type', 'wp-media-folder-gallery-addon'); ?>
                    </label>

                    <label>
                        <select class="wpmf_animation"
                                name="wpmf_gallery_settings[theme][<?php echo esc_html($theme_name) ?>][animation]">
                            <option value="slide" <?php selected($settings['animation'], 'slide'); ?>>
                                <?php esc_html_e('Slide', 'wp-media-folder-gallery-addon'); ?>
                            </option>
                            <option value="fade" <?php selected($settings['animation'], 'fade'); ?>>
                                <?php esc_html_e('Fade', 'wp-media-folder-gallery-addon'); ?>
                            </option>
                        </select>
                    </label>
                </div>
                <div class="block-item ju-settings-option wpmf-no-shadow wpmf_width_30 p-d-10">
                    <label class="wpmf_width_100 p-b-20 wpmf_left text label_text">
                        <?php esc_html_e('Transition duration', 'wp-media-folder-gallery-addon'); ?>
                    </label>

                    <label>
                        <input type="number"
                               name="wpmf_gallery_settings[theme][<?php echo esc_html($theme_name) ?>][duration]"
                               value="<?php echo esc_attr($settings['duration']) ?>">
                    </label>
                    <label> ms</label>
                </div>
                <div class="block-item ju-settings-option wpmf-no-shadow wpmf_width_100 p-d-10">
                    <label class="ju-setting-label text wpmf-no-padding wpmf-bold wpmf_left">
                        <?php esc_html_e('Automatic animation', 'wp-media-folder-gallery-addon'); ?>
                    </label>

                    <div class="ju-switch-button wpmf_left">
                        <input type="hidden"
                               name="wpmf_gallery_settings[theme][<?php echo esc_html($theme_name) ?>][auto_animation]"
                               value="0">
                        <span class="ju-switch-button">
                <label class="switch">
                            <?php if (isset($settings['auto_animation']) && (int) $settings['auto_animation'] === 1) : ?>
                                <input type="checkbox"
                                       name="wpmf_gallery_settings[theme][<?php echo esc_html($theme_name) ?>][auto_animation]"
                                       value="1" checked>
                            <?php else : ?>
                                <input type="checkbox"
                                       name="wpmf_gallery_settings[theme][<?php echo esc_html($theme_name) ?>][auto_animation]"
                                       value="1">
                            <?php endif; ?>

                    <span class="slider round"></span>
            </label>
            </span>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($theme_name === 'flowslide_theme') : ?>
            <div class="block-item ju-settings-option wpmf-no-shadow wpmf_width_20 p-d-10">
                <label class="wpmf_width_100 p-b-20 wpmf_left text label_text size" data-wpmftippy="<?php esc_html_e('Image size to load
                 by default as thumbnail', 'wp-media-folder-gallery-addon'); ?>">
                    <?php esc_html_e('Gallery image size', 'wp-media-folder-gallery-addon'); ?>
                </label>

                <label class="size">
                    <select class="size" name="wpmf_gallery_settings[theme][<?php echo esc_html($theme_name) ?>][size]">
                        <?php
                        $sizes_value = json_decode(get_option('wpmf_gallery_image_size_value'));
                        $sizes       = apply_filters('image_size_names_choose', array(
                            'thumbnail' => __('Thumbnail', 'wp-media-folder-gallery-addon'),
                            'medium'    => __('Medium', 'wp-media-folder-gallery-addon'),
                            'large'     => __('Large', 'wp-media-folder-gallery-addon'),
                            'full'      => __('Full Size', 'wp-media-folder-gallery-addon'),
                        ));
                        ?>

                        <?php foreach ($sizes_value as $key) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($settings['size'], $key); ?>>
                                <?php echo esc_html($sizes[$key]); ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </label>
            </div>

            <div class="block-item ju-settings-option wpmf-no-shadow wpmf_width_20 p-d-10">
                <label class="wpmf_width_100 p-b-20 wpmf_left text label_text" data-wpmftippy="<?php esc_html_e('Image size to load by default
                 as full size (opened in the lightbox)', 'wp-media-folder-gallery-addon'); ?>">
                    <?php esc_html_e('Lightbox size', 'wp-media-folder-gallery-addon'); ?>
                </label>

                <label>
                    <select class="targetsize"
                            name="wpmf_gallery_settings[theme][<?php echo esc_html($theme_name) ?>][targetsize]">
                        <?php
                        $sizes = array(
                            'thumbnail' => __('Thumbnail', 'wp-media-folder-gallery-addon'),
                            'medium'    => __('Medium', 'wp-media-folder-gallery-addon'),
                            'large'     => __('Large', 'wp-media-folder-gallery-addon'),
                            'full'      => __('Full Size', 'wp-media-folder-gallery-addon'),
                        );
                        ?>

                        <?php foreach ($sizes as $key => $name) : ?>
                            <option value="<?php echo esc_attr($key); ?>"
                                <?php selected($settings['targetsize'], $key); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <div class="block-item ju-settings-option wpmf-no-shadow wpmf_width_20 p-d-10">
                <label class="wpmf_width_100 p-b-20 wpmf_left text label_text" data-wpmftippy="<?php esc_html_e('Action when the user click
                 on the image thumbnail', 'wp-media-folder-gallery-addon'); ?>">
                    <?php esc_html_e('Action on click', 'wp-media-folder-gallery-addon'); ?>
                </label>

                <label>
                    <select class="link-to"
                            name="wpmf_gallery_settings[theme][<?php echo esc_html($theme_name) ?>][link]">
                        <option value="file" <?php selected($settings['link'], 'file'); ?>>
                            <?php esc_html_e('Lightbox', 'wp-media-folder-gallery-addon'); ?>
                        </option>
                        <option value="post" <?php selected($settings['link'], 'post'); ?>>
                            <?php esc_html_e('Attachment Page', 'wp-media-folder-gallery-addon'); ?>
                        </option>
                        <option value="none" <?php selected($settings['link'], 'none'); ?>>
                            <?php esc_html_e('None', 'wp-media-folder-gallery-addon'); ?>
                        </option>
                    </select>
                </label>
            </div>

            <div class="block-item ju-settings-option wpmf-no-shadow wpmf_width_20 p-d-10">
                <label class="wpmf_width_100 p-b-20 wpmf_left text label_text" data-wpmftippy="<?php esc_html_e('Image gallery
                 default ordering', 'wp-media-folder-gallery-addon'); ?>">
                    <?php esc_html_e('Order by', 'wp-media-folder-gallery-addon'); ?>
                </label>

                <label>
                    <select class="wpmf_orderby"
                            name="wpmf_gallery_settings[theme][<?php echo esc_html($theme_name) ?>][orderby]">
                        <option value="post__in" <?php selected($settings['orderby'], 'post__in'); ?>>
                            <?php esc_html_e('Custom', 'wp-media-folder-gallery-addon'); ?>
                        </option>
                        <option value="rand" <?php selected($settings['orderby'], 'rand'); ?>>
                            <?php esc_html_e('Random', 'wp-media-folder-gallery-addon'); ?>
                        </option>
                        <option value="title" <?php selected($settings['orderby'], 'title'); ?>>
                            <?php esc_html_e('Title', 'wp-media-folder-gallery-addon'); ?>
                        </option>
                        <option value="date" <?php selected($settings['orderby'], 'date'); ?>>
                            <?php esc_html_e('Date', 'wp-media-folder-gallery-addon'); ?>
                        </option>
                    </select>
                </label>
            </div>

            <div class="block-item ju-settings-option wpmf-no-shadow wpmf_width_100">
                <label class="wpmf_width_100 p-b-20 wpmf_left text label_text p-d-10" data-wpmftippy="<?php esc_html_e('By default, use ascending
                 or descending order', 'wp-media-folder-gallery-addon'); ?>">
                    <?php esc_html_e('Order', 'wp-media-folder-gallery-addon'); ?>
                </label>

                <div class="block-item ju-settings-option wpmf-no-shadow wpmf_width_100">
                    <div class="block-item ju-settings-option wpmf-no-shadow wpmf_width_20 p-d-10">
                        <label>
                            <select class="wpmf_order"
                                    name="wpmf_gallery_settings[theme][<?php echo esc_html($theme_name) ?>][order]">
                                <option value="ASC" <?php selected($settings['order'], 'ASC'); ?>>
                                    <?php esc_html_e('Ascending', 'wp-media-folder-gallery-addon'); ?>
                                </option>
                                <option value="DESC" <?php selected($settings['order'], 'DESC'); ?>>
                                    <?php esc_html_e('Descending', 'wp-media-folder-gallery-addon'); ?>
                                </option>
                            </select>
                        </label>
                    </div>

                    <div class="block-item ju-settings-option wpmf-no-shadow wpmf_width_50 p-d-10">
                        <label class="ju-setting-label setting wpmf-no-padding text wpmf-bold wpmf_left" data-wpmftippy="<?php esc_html_e('Display navigation
                 arrows', 'wp-media-folder-gallery-addon'); ?>">
                            <?php esc_html_e('Show buttons', 'wp-media-folder-gallery-addon'); ?>
                        </label>

                        <label class="wpmf_left">
                            <input type="hidden"
                                   name="wpmf_gallery_settings[theme][<?php echo esc_html($theme_name) ?>][show_buttons]"
                                   value="0">
                            <span class="ju-switch-button">
                        <label class="switch">
                            <?php if (isset($settings['show_buttons']) && (int) $settings['show_buttons'] === 1) : ?>
                                <input type="checkbox"
                                       name="wpmf_gallery_settings[theme][<?php echo esc_html($theme_name) ?>][show_buttons]"
                                       value="1" checked>
                            <?php else : ?>
                                <input type="checkbox"
                                       name="wpmf_gallery_settings[theme][<?php echo esc_html($theme_name) ?>][show_buttons]"
                                       value="1">
                            <?php endif; ?>
                            <span class="slider round"></span>
                        </label>
                    </span>
                        </label>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>