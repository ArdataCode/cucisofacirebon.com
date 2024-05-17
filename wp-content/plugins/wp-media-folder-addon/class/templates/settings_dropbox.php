<div class="content-wpmf-dropbox">
    <?php
    if (isset($dropbox_error) && $dropbox_error !== '') {
        echo '<p style="color: #f00">' . esc_html($dropbox_error) . '</p>';
    }

    do_action('cloudconnector_wpmf_display_dropbox_settings');
    ?>

    <div class="wpmf_width_100 wpmf_row_full">
        <div class="wpmf_width_50" style="display: flex">
            <input type="hidden" name="dropbox_generate_thumbnails" value="0">
            <h4 data-wpmftippy="<?php esc_html_e('This option will generate image thumbnails  and store them on your cloud account. Image thumbnails will be generated according to WordPress settings and used when you embed images (for performance purpose)', 'wpmfAddon'); ?>"
                class="ju-setting-label text wpmftippy"
                style="padding-left: 0"><?php esc_html_e('Generate image thumbnail', 'wpmfAddon') ?></h4>
            <div class="ju-switch-button">
                <label class="switch">
                    <input type="checkbox" name="dropbox_generate_thumbnails"
                           value="1"
                        <?php
                        if (!isset($dropboxconfig['generate_thumbnails']) || (int)$dropboxconfig['generate_thumbnails'] === 1) {
                            echo 'checked';
                        }
                        ?>
                    >
                    <span class="slider round"></span>
                </label>
            </div>
        </div>
    </div>

    <div>
        <h4 data-wpmftippy="<?php esc_attr_e('Define the type of link use by default when you insert a cloud media in a page or post. Public link will generate a public accessible link for your file and affect the appropriate rights on the cloud file. Private link will hide the cloud link to keep the original access right of your file', 'wpmfAddon') ?>"
            class="wpmftippy"><?php esc_html_e('Media link type', 'wpmfAddon') ?></h4>
        <div>
            <select name="dropbox_link_type">
                <option value="public" <?php selected($dropboxconfig['link_type'], 'public') ?>><?php esc_html_e('Public link', 'wpmfAddon') ?></option>
                <option value="private" <?php selected($dropboxconfig['link_type'], 'private') ?>><?php esc_html_e('Private link', 'wpmfAddon') ?></option>
            </select>
        </div>
    </div>

    <div class="dropbox-connector-form">
        <h4><?php esc_html_e('App Key', 'wpmfAddon') ?></h4>
        <div>
            <input title name="dropboxKey" type="text" class="regular-text wpmf_width_100 p-lr-20"
                   value="<?php echo esc_attr($dropboxconfig['dropboxKey']) ?>">
        </div>
    </div>

    <div class="dropbox-connector-form">
        <h4><?php esc_html_e('App Secret', 'wpmfAddon') ?></h4>
        <div>
            <input title name="dropboxSecret" type="text" class="regular-text wpmf_width_100 p-lr-20"
                   value="<?php echo esc_attr($dropboxconfig['dropboxSecret']) ?>">
        </div>
    </div>

    <div class="dropbox-connector-form">
        <h4><?php esc_html_e('Redirect URIs', 'wpmfAddon') ?></h4>
        <div>
            <input readonly type="text" class="regular-text wpmf_width_100 p-lr-20"
                   value="<?php echo esc_url(admin_url('/options-general.php?page=option-folder&task=wpmf&function=dropbox_authenticate')) ?>">
        </div>
    </div>

    <a target="_blank" class="m-t-30 ju-button no-background orange-button waves-effect waves-light"
       href="https://www.joomunited.com/wordpress-documentation/wp-media-folder/521-wp-media-folder-addon-dropbox-integration">
        <?php esc_html_e('Read the online documentation', 'wpmfAddon') ?>
    </a>

    <div class="wpmf_width_100 wpmf_row_full" style="margin-top: 50px;background: #eee;padding: 20px;">
        <h1><?php esc_html_e('Media Access', 'wpmfAddon'); ?></h1>
        <div class="ju-settings-option">
            <div class="wpmf_row_full">
                <input type="hidden" name="dropbox_media_access" value="0">
                <label data-wpmftippy="<?php esc_html_e('Once user upload some media, he will have a
         personal folder, can be per User or per User Role', 'wpmfAddon'); ?>"
                       class="ju-setting-label text"><?php esc_html_e('Media access by User or User Role', 'wpmfAddon') ?></label>
                <div class="ju-switch-button">
                    <label class="switch">
                        <input type="checkbox" name="dropbox_media_access" value="1"
                            <?php
                            if (isset($dropboxconfig['media_access']) && (int)$dropboxconfig['media_access'] === 1) {
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
         folder per User or per WordPress User Role', 'wpmfAddon'); ?>"
                       class="ju-setting-label text"><?php esc_html_e('Folder automatic creation', 'wpmfAddon') ?></label>
                <label class="line-height-50 wpmf_right p-r-20">
                    <select name="dropbox_access_by">
                        <option
                            <?php selected($dropboxconfig['access_by'], 'user'); ?> value="user">
                            <?php esc_html_e('By user', 'wpmfAddon') ?>
                        </option>
                        <option
                            <?php selected($dropboxconfig['access_by'], 'role'); ?> value="role">
                            <?php esc_html_e('By role', 'wpmfAddon') ?>
                        </option>
                    </select>
                </label>
            </div>
        </div>

        <div class="ju-settings-option">
            <div class="wpmf_row_full">
                <input type="hidden" name="dropbox_load_all_childs" value="0">
                <label data-wpmftippy="<?php esc_html_e('If activated the user will also be able to see the media uploaded by others in his own folder (additionally to his own media). If not activated, he\'ll see only his own media', 'wpmfAddon'); ?>"
                       class="ju-setting-label text"><?php esc_html_e('Display all media in user folder', 'wpmfAddon') ?></label>
                <div class="ju-switch-button">
                    <label class="switch">
                        <input type="checkbox" name="dropbox_load_all_childs" value="1"
                            <?php
                            if (isset($dropboxconfig['load_all_childs']) && (int)$dropboxconfig['load_all_childs'] === 1) {
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
    <button type="submit" name="btn_wpmf_save"
            class="btn_wpmf_save ju-button orange-button waves-effect waves-light"><?php esc_html_e('Save Changes', 'wpmfAddon'); ?></button>
</div>