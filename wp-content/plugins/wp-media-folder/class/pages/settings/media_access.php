<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
?>
    <div id="user_media_access" class="tab-content">
        <div class="content-box content-wpmf-media-access">
            <div class="ju-settings-option">
                <div class="wpmf_row_full">
                    <input type="hidden" name="wpmf_active_media" value="0">
                    <label data-wpmftippy="<?php esc_html_e('Once user upload some media, he will have a
             personal folder, can be per User or per User Role', 'wpmf'); ?>"
                           class="ju-setting-label text"><?php esc_html_e('Media access by User or User Role', 'wpmf') ?></label>
                    <div class="ju-switch-button">
                        <label class="switch">
                            <input type="checkbox" name="wpmf_active_media" value="1"
                                <?php
                                if (isset($active_media) && (int)$active_media === 1) {
                                    echo 'checked';
                                }
                                ?>
                            >
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="ju-settings-option wpmf_right m-r-0">
                <div class="wpmf_row_full">
                    <label data-wpmftippy="<?php esc_html_e('Automatically create a
             folder per User or per WordPress User Role', 'wpmf'); ?>"
                           class="ju-setting-label text"><?php esc_html_e('Folder automatic creation', 'wpmf') ?></label>
                    <label class="line-height-50 wpmf_right p-r-20">
                        <select name="wpmf_create_folder">
                            <option
                                <?php selected($create_folder, 'user'); ?> value="user">
                                <?php esc_html_e('By user', 'wpmf') ?>
                            </option>
                            <option
                                <?php selected($create_folder, 'role'); ?> value="role">
                                <?php esc_html_e('By role', 'wpmf') ?>
                            </option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="ju-settings-option">
                <h4 data-wpmftippy="<?php esc_html_e('Select the root folder to store all user media and
             folders (only if Media by User or User Role is activated above)', 'wpmf'); ?>"
                    class="ju-setting-label text"><?php esc_html_e('User media folder root', 'wpmf') ?></h4>
                <div class="wpmf_row_full">
                    <span id="wpmfjaouser"></span>
                </div>
            </div>

            <div class="ju-settings-option wpmf_right m-r-0">
                <div class="wpmf_row_full">
                    <input type="hidden" name="all_media_in_user_root" value="0">
                    <label data-wpmftippy="<?php esc_html_e('If activated the user will also be able to see the media uploaded by others in his own folder (additionally to his own media). If not activated, he\'ll see only his own media', 'wpmf'); ?>"
                           class="ju-setting-label text"><?php esc_html_e('Display all media in user folder', 'wpmf') ?></label>
                    <div class="ju-switch-button">
                        <label class="switch">
                            <input type="checkbox" name="all_media_in_user_root" value="1"
                                <?php
                                if (isset($all_media_in_user_root) && (int)$all_media_in_user_root === 1) {
                                    echo 'checked';
                                }
                                ?>
                            >
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="file_design" class="tab-content">
        <div class="content-box content-wpmf-media-access">
            <div class="ju-settings-option">
                <div class="wpmf_row_full">
                    <input type="hidden" name="wpmf_option_singlefile" value="0">
                    <label data-wpmftippy="<?php esc_html_e('When enabling this option you will have the possibility to transform media file links (like .pdf, .doc... into a nice download button. Setup the button design below', 'wpmf'); ?>"
                           class="ju-setting-label text">
                        <?php esc_html_e('Single media download', 'wpmf') ?></label>
                    <div class="ju-switch-button">
                        <label class="switch">
                            <input type="checkbox" name="wpmf_option_singlefile"
                                   value="1"
                                <?php
                                if (isset($option_singlefile) && (int)$option_singlefile === 1) {
                                    echo 'checked';
                                }
                                ?>
                            >
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="p-d-20 ju-settings-option wpmf_width_100">
                <h4 style="font-size: 20px"><?php esc_html_e('Color Theme', 'wpmf') ?></h4>
                <div class="wpmf_width_100">
                    <div class="ju-settings-option wpmf-no-shadow wpmf_width_20">
                        <label class="wpmf_width_100 p-b-20 wpmf_left text label_text"
                               for="singlebg"><?php esc_html_e('Background color', 'wpmf') ?></label>
                        <label>
                            <input name="wpmf_color_singlefile[bgdownloadlink]" type="text"
                                   value="<?php echo esc_attr($media_download['bgdownloadlink']) ?>"
                                   class="inputbox input-block-level wp-color-field-bg wp-color-picker">
                        </label>
                    </div>

                    <div class="ju-settings-option wpmf-no-shadow wpmf_width_20">
                        <label class="wpmf_width_100 p-b-20 wpmf_left text label_text"
                               for="singlebg"><?php esc_html_e('Hover color', 'wpmf') ?></label>
                        <label>
                            <input name="wpmf_color_singlefile[hvdownloadlink]" type="text"
                                   value="<?php echo esc_attr($media_download['hvdownloadlink']) ?>"
                                   class="inputbox input-block-level wp-color-field-hv wp-color-picker">
                        </label>
                    </div>

                    <div class="ju-settings-option wpmf_width_20 wpmf-no-shadow">
                        <label class="wpmf_width_100 p-b-20 wpmf_left text label_text"
                               for="singlebg"><?php esc_html_e('Font color', 'wpmf') ?></label>
                        <label>
                            <input name="wpmf_color_singlefile[fontdownloadlink]" type="text"
                                   value="<?php echo esc_attr($media_download['fontdownloadlink']) ?>"
                                   class="inputbox input-block-level wp-color-field-font wp-color-picker">
                        </label>
                    </div>

                    <div class="ju-settings-option wpmf_width_20 wpmf-no-shadow">
                        <label class="wpmf_width_100 p-b-20 wpmf_left text label_text"
                               for="singlebg"><?php esc_html_e('Hover font color', 'wpmf') ?></label>
                        <label>
                            <input name="wpmf_color_singlefile[hoverfontcolor]" type="text"
                                   value="<?php echo esc_attr($media_download['hoverfontcolor']) ?>"
                                   class="inputbox input-block-level wp-color-field-hvfont wp-color-picker">
                        </label>
                    </div>
                </div>
            </div>

            <div class="p-d-20 ju-settings-option wpmf_width_100">
                <h4 style="font-size: 20px"><?php esc_html_e('Icon', 'wpmf') ?></h4>
                <div class="wpmf_width_100 m-b-20">
                    <?php
                    $media_icons = array(
                        'download_style_0' => array('label' => esc_html__('Default', 'wpmf'), 'url' => WPMF_PLUGIN_URL . 'assets/images/setting_download_style_0.svg'),
                        'download_style_1' => array('label' => esc_html__('Style 1', 'wpmf'), 'url' => WPMF_PLUGIN_URL . 'assets/images/setting_download_style_1.svg'),
                        'download_style_2' => array('label' => esc_html__('Style 2', 'wpmf'), 'url' => WPMF_PLUGIN_URL . 'assets/images/setting_download_style_2.svg'),
                        'download_style_3' => array('label' => esc_html__('Style 3', 'wpmf'), 'url' => WPMF_PLUGIN_URL . 'assets/images/setting_download_style_3.svg'),
                        'download_style_4' => array('label' => esc_html__('Style 4', 'wpmf'), 'url' => WPMF_PLUGIN_URL . 'assets/images/setting_download_style_4.svg')
                    );
                    foreach ($media_icons as $media_icon => $media_icon_url) :
                        ?>
                        <div class="ju-settings-option wpmf-no-shadow wpmf_width_20 wpmf-media-icons">
                            <div class="wpmf-media-icon">
                                <img src="<?php echo esc_url($media_icon_url['url']) ?>">
                                <div class="wpmf-media-icon-radio">
                                    <label class="radio">
                                        <input name="wpmf_color_singlefile[icon_image]" type="radio"
                                               value="<?php echo esc_attr($media_icon) ?>"
                                               class="inputbox input-block-level" <?php checked($media_download['icon_image'], $media_icon) ?>>
                                        <span class="outer"><span
                                                    class="inner"></span></span><?php echo esc_html($media_icon_url['label']) ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="wpmf_width_100">
                    <div class="ju-settings-option wpmf-no-shadow wpmf_width_20">
                        <label class="wpmf_width_100 p-b-20 wpmf_left text label_text"
                               for="singlebg"><?php esc_html_e('Icon color', 'wpmf') ?></label>
                        <label>
                            <input name="wpmf_color_singlefile[icon_color]" type="text"
                                   value="<?php echo esc_attr($media_download['icon_color']) ?>"
                                   class="inputbox input-block-level wp-color-field-icon-color wp-color-picker">
                        </label>
                    </div>
                </div>
            </div>

            <div class="p-d-20 ju-settings-option wpmf_width_100">
                <h4 style="font-size: 20px"><?php esc_html_e('Border', 'wpmf') ?></h4>
                <div class="wpmf_width_100">
                    <div class="ju-settings-option wpmf-no-shadow wpmf_width_20">
                        <label class="wpmf_width_100 p-b-20 wpmf_left text label_text"
                               for="singlebg"><?php esc_html_e('Border radius', 'wpmf') ?></label>
                        <label>
                            <input name="wpmf_color_singlefile[border_radius]" type="number"
                                   value="<?php echo esc_attr($media_download['border_radius']) ?>"
                                   class="inputbox input-block-level">
                        </label>
                    </div>

                    <div class="ju-settings-option wpmf-no-shadow wpmf_width_20">
                        <label class="wpmf_width_100 p-b-20 wpmf_left text label_text"
                               for="singlebg"><?php esc_html_e('Border width', 'wpmf') ?></label>
                        <label>
                            <input name="wpmf_color_singlefile[border_width]" type="number"
                                   value="<?php echo esc_attr($media_download['border_width']) ?>"
                                   class="inputbox input-block-level">
                        </label>
                    </div>

                    <div class="ju-settings-option wpmf-no-shadow wpmf_width_20">
                        <label class="wpmf_width_100 p-b-20 wpmf_left text label_text"
                               for="singlebg"><?php esc_html_e('Border type', 'wpmf') ?></label>
                        <label>
                            <select name="wpmf_color_singlefile[border_type]">
                                <option value="solid" <?php selected($media_download['border_type'], 'solid') ?>><?php esc_html_e('Solid', 'wpmf') ?></option>
                                <option value="double" <?php selected($media_download['border_type'], 'double') ?>><?php esc_html_e('Double', 'wpmf') ?></option>
                                <option value="dotted" <?php selected($media_download['border_type'], 'dotted') ?>><?php esc_html_e('Dotted', 'wpmf') ?></option>
                                <option value="dashed" <?php selected($media_download['border_type'], 'dashed') ?>><?php esc_html_e('Dashed', 'wpmf') ?></option>
                                <option value="groove" <?php selected($media_download['border_type'], 'groove') ?>><?php esc_html_e('Groove', 'wpmf') ?></option>
                            </select>
                        </label>
                    </div>

                    <div class="ju-settings-option wpmf-no-shadow wpmf_width_20">
                        <label class="wpmf_width_100 p-b-20 wpmf_left text label_text"
                               for="singlebg"><?php esc_html_e('Border color', 'wpmf') ?></label>
                        <label>
                            <input name="wpmf_color_singlefile[border_color]" type="text"
                                   value="<?php echo esc_attr($media_download['border_color']) ?>"
                                   class="inputbox input-block-level wp-color-field-border-color wp-color-picker">
                        </label>
                    </div>
                </div>
            </div>

            <div class="p-d-20 ju-settings-option wpmf_width_100">
                <h4 style="font-size: 20px"><?php esc_html_e('Margin', 'wpmf') ?></h4>
                <div class="wpmf_width_100">
                    <div class="ju-settings-option wpmf-no-shadow wpmf_width_20">
                        <label class="wpmf_width_100 p-b-20 wpmf_left text label_text"
                               for="singlebg"><?php esc_html_e('Margin top', 'wpmf') ?></label>
                        <label>
                            <input name="wpmf_color_singlefile[margin_top]" type="number"
                                   value="<?php echo esc_attr($media_download['margin_top']) ?>"
                                   class="inputbox input-block-level">
                        </label>
                    </div>

                    <div class="ju-settings-option wpmf-no-shadow wpmf_width_20">
                        <label class="wpmf_width_100 p-b-20 wpmf_left text label_text"
                               for="singlebg"><?php esc_html_e('Margin right', 'wpmf') ?></label>
                        <label>
                            <input name="wpmf_color_singlefile[margin_right]" type="number"
                                   value="<?php echo esc_attr($media_download['margin_right']) ?>"
                                   class="inputbox input-block-level">
                        </label>
                    </div>

                    <div class="ju-settings-option wpmf-no-shadow wpmf_width_20">
                        <label class="wpmf_width_100 p-b-20 wpmf_left text label_text"
                               for="singlebg"><?php esc_html_e('Margin bottom', 'wpmf') ?></label>
                        <label>
                            <input name="wpmf_color_singlefile[margin_bottom]" type="number"
                                   value="<?php echo esc_attr($media_download['margin_bottom']) ?>"
                                   class="inputbox input-block-level">
                        </label>
                    </div>

                    <div class="ju-settings-option wpmf-no-shadow wpmf_width_20">
                        <label class="wpmf_width_100 p-b-20 wpmf_left text label_text"
                               for="singlebg"><?php esc_html_e('Margin left', 'wpmf') ?></label>
                        <label>
                            <input name="wpmf_color_singlefile[margin_left]" type="number"
                                   value="<?php echo esc_attr($media_download['margin_left']) ?>"
                                   class="inputbox input-block-level">
                        </label>
                    </div>
                </div>
            </div>

            <div class="p-d-20 ju-settings-option wpmf_width_100">
                <h4 style="font-size: 20px"><?php esc_html_e('Padding', 'wpmf') ?></h4>
                <div class="wpmf_width_100">
                    <div class="ju-settings-option wpmf-no-shadow wpmf_width_20">
                        <label class="wpmf_width_100 p-b-20 wpmf_left text label_text"
                               for="singlebg"><?php esc_html_e('Padding top', 'wpmf') ?></label>
                        <label>
                            <input name="wpmf_color_singlefile[padding_top]" type="number"
                                   value="<?php echo esc_attr($media_download['padding_top']) ?>"
                                   class="inputbox input-block-level">
                        </label>
                    </div>

                    <div class="ju-settings-option wpmf-no-shadow wpmf_width_20">
                        <label class="wpmf_width_100 p-b-20 wpmf_left text label_text"
                               for="singlebg"><?php esc_html_e('Padding right', 'wpmf') ?></label>
                        <label>
                            <input name="wpmf_color_singlefile[padding_right]" type="number"
                                   value="<?php echo esc_attr($media_download['padding_right']) ?>"
                                   class="inputbox input-block-level">
                        </label>
                    </div>

                    <div class="ju-settings-option wpmf-no-shadow wpmf_width_20">
                        <label class="wpmf_width_100 p-b-20 wpmf_left text label_text"
                               for="singlebg"><?php esc_html_e('Padding bottom', 'wpmf') ?></label>
                        <label>
                            <input name="wpmf_color_singlefile[padding_bottom]" type="number"
                                   value="<?php echo esc_attr($media_download['padding_bottom']) ?>"
                                   class="inputbox input-block-level">
                        </label>
                    </div>

                    <div class="ju-settings-option wpmf-no-shadow wpmf_width_20">
                        <label class="wpmf_width_100 p-b-20 wpmf_left text label_text"
                               for="singlebg"><?php esc_html_e('Padding left', 'wpmf') ?></label>
                        <label>
                            <input name="wpmf_color_singlefile[padding_left]" type="number"
                                   value="<?php echo esc_attr($media_download['padding_left']) ?>"
                                   class="inputbox input-block-level">
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
wp_enqueue_style('wp-color-picker');
wp_enqueue_script('wp-color-picker');
?>