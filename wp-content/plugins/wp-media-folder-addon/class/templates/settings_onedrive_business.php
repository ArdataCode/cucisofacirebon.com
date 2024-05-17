<div class="content-wpmf-onedrive">
    <?php
    $appInfo = $onedriveBusinessDrive->getClient();
    $hasToken = $onedriveBusinessDrive->loadToken();
    $btnconnect = '';

    if (is_wp_error($appInfo)) {
        echo '<div id="message" class="error"><p>' . esc_html($appInfo->get_error_message()) . '</p></div>';
        return false;
    }

    if ($appInfo) {
        $authUrl = $onedriveBusinessDrive->getAuthUrl();
        if (!is_wp_error($authUrl)) {
            $btnconnect = '<a class="ju-button orange-button waves-effect waves-light btndrive wpmf_onedrive_login odb-connector-button" href="#"
         onclick="window.location.assign(\'' . $authUrl . '\',\'foo\',\'width=600,height=600\');return false;">' . __('Connect OneDrive Business', 'wpmfAddon') . '</a>';
        }
    }

    ?>

    <div id="config_onedrive_business" class="div_list wpmf_width_100">
        <?php
        do_action('cloudconnector_wpmf_display_onedrive_business_connect_button');

        if (!empty($business_config['OneDriveClientId']) && !empty($business_config['OneDriveClientSecret'])) {
            if (isset($business_config['connected']) && (int)$business_config['connected'] === 1) {
                $client = $onedriveBusinessDrive->startClient();
                $btndisconnect = '<a class="ju-button no-background orange-button waves-effect waves-light btndrive wpmf_onedrive_business_logout odb-connector-button">' . __('Disconnect OneDrive Business', 'wpmfAddon') . '</a>';
                $driveInfo = $onedriveBusinessDrive->getDriveInfo();
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

        do_action('cloudconnector_wpmf_display_onedrive_business_settings');
        ?>
        <div class="wpmf_width_100 ju-settings-option box-shadow-none m-b-0">
            <div class="wpmf_width_100 wpmf_row_full">
                <div class="wpmf_width_50" style="display: flex">
                    <input type="hidden" name="onedrive_business_generate_thumbnails" value="0">
                    <h4 data-wpmftippy="<?php esc_html_e('This option will generate image thumbnails  and store them on your cloud account. Image thumbnails will be generated according to WordPress settings and used when you embed images (for performance purpose)', 'wpmfAddon'); ?>"
                        class="ju-setting-label text wpmftippy" style="padding-left: 0"><?php esc_html_e('Generate image thumbnail', 'wpmfAddon') ?></h4>
                    <div class="ju-switch-button">
                        <label class="switch">
                            <input type="checkbox" name="onedrive_business_generate_thumbnails"
                                   value="1"
                                <?php
                                if (!isset($business_config['generate_thumbnails']) || (int)$business_config['generate_thumbnails'] === 1) {
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
                    <select name="onedrive_business_link_type">
                        <option value="public" <?php selected($business_config['link_type'], 'public') ?>><?php esc_html_e('Public link', 'wpmfAddon') ?></option>
                        <option value="private" <?php selected($business_config['link_type'], 'private') ?>><?php esc_html_e('Private link', 'wpmfAddon') ?></option>
                    </select>
                </div>
            </div>

            <div class="odb-connector-form">
                <h4><?php esc_html_e('OneDrive Client ID', 'wpmfAddon') ?></h4>
                <div>
                    <input title name="OneDriveBusinessClientId" type="text"
                           class="onedrivebusinessconfig regular-text wpmf_width_100 p-lr-20"
                           value="<?php echo esc_attr($business_config['OneDriveClientId']) ?>">
                    <p class="description" id="tagline-description">
                        <?php esc_html_e('Insert your OneDrive Application Id here.
                     You can find this Id in the OneDrive dev center', 'wpmfAddon') ?>
                    </p>
                </div>
            </div>

            <div class="odb-connector-form">
                <h4><?php esc_html_e('OneDrive Client Secret', 'wpmfAddon') ?></h4>
                <div>
                    <input title name="OneDriveBusinessClientSecret" type="text"
                           class="onedrivebusinessconfig regular-text wpmf_width_100 p-lr-20"
                           value="<?php echo esc_attr($business_config['OneDriveClientSecret']) ?>">
                    <p class="description" id="tagline-description">
                        <?php esc_html_e('Insert your OneDrive Secret here.
                     You can find this secret in the OneDrive dev center', 'wpmfAddon') ?>
                    </p>
                </div>
            </div>

            <div class="odb-connector-form">
                <div class="wpmf_row_full" style="margin: 0; position: relative;">
                    <h4><?php esc_html_e('Redirect URIs', 'wpmfAddon') ?></h4>
                    <div class="wpmf_copy_shortcode" data-input="redirect_uris_onedrive_bussiness" style="margin: 5px 0">
                        <i data-wpmftippy="<?php esc_html_e('Copy shortcode', 'wpmfAddon'); ?>"
                           class="material-icons wpmftippy">content_copy</i>
                        <label><?php esc_html_e('COPY', 'wpmfAddon'); ?></label>
                    </div>
                </div>

                <div>
                    <input title name="redirect_uris" type="text" readonly
                           value="<?php echo esc_attr(admin_url('upload.php')); ?>"
                           class="regular-text wpmf_width_100 p-lr-20 code redirect_uris_onedrive_bussiness">
                </div>
            </div>

            <a target="_blank" class="m-t-30 ju-button no-background orange-button waves-effect waves-light"
               href="https://www.joomunited.com/wordpress-documentation/wp-media-folder/288-wp-media-folder-addon-onedrive-business-integration">
                <?php esc_html_e('Read the online documentation', 'wpmfAddon') ?>
            </a>
        </div>
        <div class="wpmf_width_100 wpmf_row_full" style="margin-top: 50px;background: #eee;padding: 20px;">
            <h1><?php esc_html_e('Media Access', 'wpmfAddon'); ?></h1>
            <div class="ju-settings-option">
                <div class="wpmf_row_full">
                    <input type="hidden" name="onedrive_business_media_access" value="0">
                    <label data-wpmftippy="<?php esc_html_e('Once user upload some media, he will have a
         personal folder, can be per User or per User Role', 'wpmfAddon'); ?>"
                           class="ju-setting-label text"><?php esc_html_e('Media access by User or User Role', 'wpmfAddon') ?></label>
                    <div class="ju-switch-button">
                        <label class="switch">
                            <input type="checkbox" name="onedrive_business_media_access" value="1"
                                <?php
                                if (isset($business_config['media_access']) && (int)$business_config['media_access'] === 1) {
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
                        <select name="onedrive_business_access_by">
                            <option
                                <?php selected($business_config['access_by'], 'user'); ?> value="user">
                                <?php esc_html_e('By user', 'wpmfAddon') ?>
                            </option>
                            <option
                                <?php selected($business_config['access_by'], 'role'); ?> value="role">
                                <?php esc_html_e('By role', 'wpmfAddon') ?>
                            </option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="ju-settings-option">
                <div class="wpmf_row_full">
                    <input type="hidden" name="onedrive_business_load_all_childs" value="0">
                    <label data-wpmftippy="<?php esc_html_e('If activated the user will also be able to see the media uploaded by others in his own folder (additionally to his own media). If not activated, he\'ll see only his own media', 'wpmfAddon'); ?>"
                           class="ju-setting-label text"><?php esc_html_e('Display all media in user folder', 'wpmfAddon') ?></label>
                    <div class="ju-switch-button">
                        <label class="switch">
                            <input type="checkbox" name="onedrive_business_load_all_childs" value="1"
                                <?php
                                if (isset($business_config['load_all_childs']) && (int)$business_config['load_all_childs'] === 1) {
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
        $('.wpmf_onedrive_business_logout').click(function () {
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'wpmf_onedrive_business_logout'
                },
                success: function (response) {
                    location.reload(true);
                }
            });
        });
    });
</script>