<div class="content-wpmf-onedrive">
    <?php
    $appInfo = $onedriveDrive->getClient();
    $hasToken = $onedriveDrive->loadToken();
    $btnconnect = '';

    if (is_wp_error($appInfo)) {
        echo '<div id="message" class="error"><p>' . esc_html($appInfo->get_error_message()) . '</p></div>';
        return false;
    }

    if ($appInfo) {
        $authUrl = $onedriveDrive->getAuthUrl();
        if (!is_wp_error($authUrl)) {
            $btnconnect = '<a class="ju-button orange-button waves-effect waves-light btndrive wpmf_onedrive_login od-connector-button" href="#"
         onclick="window.location.assign(\'' . $authUrl . '\',\'foo\',\'width=600,height=600\');return false;">' . __('Connect OneDrive', 'wpmfAddon') . '</a>';
        }
    }

    ?>

    <div id="config_onedrive" class="div_list wpmf_width_100">
        <?php
        do_action('cloudconnector_wpmf_display_onedrive_connect_button');

        if (!empty($onedrive_config['OneDriveClientId']) && !empty($onedrive_config['OneDriveClientSecret'])) {
            if (isset($onedrive_config['connected']) && (int)$onedrive_config['connected'] === 1) {
                $client = $onedriveDrive->startClient();
                $btndisconnect = '<a class="ju-button no-background orange-button waves-effect waves-light btndrive wpmf_onedrive_logout od-connector-button">' . __('Disconnect OneDrive', 'wpmfAddon') . '</a>';
                $driveInfo = $onedriveDrive->getDriveInfo();
                // phpcs:disable WordPress.Security.EscapeOutput -- Content already escaped in the method
                if (!$driveInfo || is_wp_error($driveInfo)) {
                    echo $btnconnect;
                } else {
                    echo $btndisconnect;
                }
                // phpcs:enable
            } else {
                echo $btnconnect; // phpcs:disable WordPress.Security.EscapeOutput -- Content already escaped in the method
            }
        }

        do_action('cloudconnector_wpmf_display_onedrive_settings');
        ?>

        <div class="wpmf_width_100 ju-settings-option box-shadow-none m-b-0">
            <div class="wpmf_width_100 wpmf_row_full">
                <div class="wpmf_width_50" style="display: flex">
                    <input type="hidden" name="onedrive_generate_thumbnails" value="0">
                    <h4 data-wpmftippy="<?php esc_html_e('This option will generate image thumbnails  and store them on your cloud account. Image thumbnails will be generated according to WordPress settings and used when you embed images (for performance purpose)', 'wpmfAddon'); ?>"
                           class="ju-setting-label text wpmftippy" style="padding-left: 0"><?php esc_html_e('Generate image thumbnail', 'wpmfAddon') ?></h4>
                    <div class="ju-switch-button">
                        <label class="switch">
                            <input type="checkbox" name="onedrive_generate_thumbnails"
                                   value="1"
                                <?php
                                if (!isset($onedrive_config['generate_thumbnails']) || (int)$onedrive_config['generate_thumbnails'] === 1) {
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
                <h4 data-wpmftippy="<?php esc_attr_e('Define the type of link use by default when you insert a cloud media in a page or post. Public link will generate a public accessible link for your file and affect the appropriate rights on the cloud file. Private link will hide the cloud link to keep the original access right of your file', 'wpmfAddon') ?>" class="wpmftippy"><?php esc_html_e('Media link type', 'wpmfAddon') ?></h4>
                <div>
                    <select name="onedrive_link_type">
                        <option value="public" <?php selected($onedrive_config['link_type'], 'public') ?>><?php esc_html_e('Public link', 'wpmfAddon') ?></option>
                        <option value="private" <?php selected($onedrive_config['link_type'], 'private') ?>><?php esc_html_e('Private link', 'wpmfAddon') ?></option>
                    </select>
                </div>
            </div>

            <div class="od-connector-form">
                <h4><?php esc_html_e('OneDrive Client ID', 'wpmfAddon') ?></h4>
                <div>
                    <input title name="OneDriveClientId" type="text"
                           class="onedriveconfig regular-text wpmf_width_100 p-lr-20"
                           value="<?php echo esc_attr($onedrive_config['OneDriveClientId']) ?>">

                    <p class="description" id="tagline-description">
                        <?php esc_html_e('Insert your OneDrive Application Id here.
                     You can find this Id in the OneDrive dev center', 'wpmfAddon') ?>
                    </p>
                </div>
            </div>

            <div class="od-connector-form">
                <h4><?php esc_html_e('OneDrive Client Secret', 'wpmfAddon') ?></h4>
                <div>
                    <input title name="OneDriveClientSecret" type="text"
                           class="onedriveconfig regular-text wpmf_width_100 p-lr-20"
                           value="<?php echo esc_attr($onedrive_config['OneDriveClientSecret']) ?>">

                    <p class="description" id="tagline-description">
                        <?php esc_html_e('Insert your OneDrive Secret here.
                     You can find this secret in the OneDrive dev center', 'wpmfAddon') ?>
                    </p>
                </div>
            </div>

            <div class="od-connector-form">
                <div class="wpmf_row_full" style="margin: 0; position: relative;">
                    <h4><?php esc_html_e('Redirect URIs', 'wpmfAddon') ?></h4>
                    <div class="wpmf_copy_shortcode" data-input="redirect_uris_onedrive" style="margin: 5px 0">
                        <i data-wpmftippy="<?php esc_html_e('Copy shortcode', 'wpmfAddon'); ?>"
                           class="material-icons wpmftippy">content_copy</i>
                        <label><?php esc_html_e('COPY', 'wpmfAddon'); ?></label>
                    </div>
                </div>

                <div>
                    <input title name="redirect_uris" type="text" readonly
                           value="<?php echo esc_attr(admin_url()); ?>"
                           class="regular-text wpmf_width_100 p-lr-20 code redirect_uris_onedrive">
                </div>
            </div>

            <a target="_blank" class="m-t-30 ju-button no-background orange-button waves-effect waves-light"
               href="https://www.joomunited.com/wordpress-documentation/wp-media-folder/287-wp-media-folder-addon-onedrive-personal-integration">
                <?php esc_html_e('Read the online documentation', 'wpmfAddon') ?>
            </a>
        </div>

        <div class="wpmf_width_100 wpmf_row_full" style="margin-top: 50px;background: #eee;padding: 20px;">
            <h1><?php esc_html_e('Media Access', 'wpmfAddon'); ?></h1>
            <div class="ju-settings-option">
                <div class="wpmf_row_full">
                    <input type="hidden" name="onedrive_media_access" value="0">
                    <label data-wpmftippy="<?php esc_html_e('Once user upload some media, he will have a
         personal folder, can be per User or per User Role', 'wpmfAddon'); ?>"
                           class="ju-setting-label text"><?php esc_html_e('Media access by User or User Role', 'wpmfAddon') ?></label>
                    <div class="ju-switch-button">
                        <label class="switch">
                            <input type="checkbox" name="onedrive_media_access" value="1"
                                <?php
                                if (isset($onedrive_config['media_access']) && (int)$onedrive_config['media_access'] === 1) {
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
                        <select name="onedrive_access_by">
                            <option
                                <?php selected($onedrive_config['access_by'], 'user'); ?> value="user">
                                <?php esc_html_e('By user', 'wpmfAddon') ?>
                            </option>
                            <option
                                <?php selected($onedrive_config['access_by'], 'role'); ?> value="role">
                                <?php esc_html_e('By role', 'wpmfAddon') ?>
                            </option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="ju-settings-option">
                <div class="wpmf_row_full">
                    <input type="hidden" name="onedrive_load_all_childs" value="0">
                    <label data-wpmftippy="<?php esc_html_e('If activated the user will also be able to see the media uploaded by others in his own folder (additionally to his own media). If not activated, he\'ll see only his own media', 'wpmfAddon'); ?>"
                           class="ju-setting-label text"><?php esc_html_e('Display all media in user folder', 'wpmfAddon') ?></label>
                    <div class="ju-switch-button">
                        <label class="switch">
                            <input type="checkbox" name="onedrive_load_all_childs" value="1"
                                <?php
                                if (isset($onedrive_config['load_all_childs']) && (int)$onedrive_config['load_all_childs'] === 1) {
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
    <button type="submit" name="btn_wpmf_save"
            class="btn_wpmf_save ju-button orange-button waves-effect waves-light"><?php esc_html_e('Save Changes', 'wpmfAddon'); ?></button>
</div>

<script type="text/javascript">
    jQuery(document).ready(function ($) {
        $('.wpmf_onedrive_logout').click(function () {
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'wpmf_onedrive_logout'
                },
                success: function (response) {
                    location.reload(true);
                }
            });
        });
    });
</script>