<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
?>
<div id="google_drive_box" class="tab-content">
    <div class="wpmf_width_100 p-tb-20 wpmf_left top_bar">
        <h1 class="wpmf_left"><?php esc_html_e('Google Drive', 'wpmf') ?></h1>
        <?php
        do_action('cloudconnector_wpmf_display_ggd_connect_button');
        if (isset($googleconfig['googleClientId']) && $googleconfig['googleClientId'] !== ''
            && isset($googleconfig['googleClientSecret']) && $googleconfig['googleClientSecret'] !== '') {
            if (empty($googleconfig['connected'])) {
                $urlGoogle = $googleDrive->getAuthorisationUrl();
                ?>
                <div class="btn_wpmf_saves">
                    <a class="ju-button orange-button waves-effect waves-light btndrive ggd-connector-button" href="#"
                       onclick="window.location.assign('<?php echo esc_html($urlGoogle); ?>','foo','width=600,height=600');return false;">
                        <?php esc_html_e('Connect Google Drive', 'wpmf') ?></a>
                </div>

                <?php
            } else {
                $url_logout = admin_url('options-general.php?page=option-folder&task=wpmf&function=wpmf_gglogout');
                ?>
                <div class="btn_wpmf_saves">
                    <a class="ju-button no-background orange-button waves-effect waves-light btndrive ggd-connector-button"
                       href="<?php echo esc_html($url_logout) ?>">
                        <?php esc_html_e('Disconnect Google Drive', 'wpmf') ?></a>
                </div>
                <?php
            }
        } else {
            $config_mode = get_option('joom_cloudconnector_wpmf_ggd_connect_mode', 'manual');
            if ($config_mode && $config_mode === 'automatic') {
                echo '<div class="btn_wpmf_saves"><div class="ggd-connector-button"></div></div>';
            } else {
                echo '<div class="btn_wpmf_saves"><div class="ggd-connector-button"></div></div>';
                require WP_MEDIA_FOLDER_PLUGIN_DIR . '/class/pages/settings/submit_button.php';
            }
        }
        ?>
    </div>
    <div class="content-box">
        <?php
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- View request, no action
        if (isset($_POST['btn_wpmf_save'])) {
            ?>
            <div class="wpmf_width_100 top_bar saved_infos" style="padding: 20px 0">
                <?php
                require WP_MEDIA_FOLDER_PLUGIN_DIR . '/class/pages/settings/saved_info.php';
                ?>
            </div>
            <?php
        }
        ?>

        <div class="wpmf_width_100 ju-settings-option">
            <div class="p-d-20">
                <?php
                if (is_plugin_active('wp-media-folder-addon/wp-media-folder-addon.php')) {
                    // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
                    echo $html_tabgoogle;
                }
                ?>
            </div>
        </div>
    </div>
</div>

<div id="google_photo" class="tab-content">
    <div class="wpmf_width_100 p-tb-20 wpmf_left top_bar">
        <h1 class="wpmf_left"><?php esc_html_e('Google Photo', 'wpmf') ?></h1>
        <?php
        do_action('cloudconnector_wpmf_display_gpt_connect_button');
        if (isset($google_photo_config['googleClientId']) && $google_photo_config['googleClientId'] !== ''
            && isset($google_photo_config['googleClientSecret']) && $google_photo_config['googleClientSecret'] !== '') {
            if (empty($google_photo_config['connected'])) {
                $urlGooglePhoto = $googlePhoto->getAuthorisationUrl();
                ?>
                <div class="btn_wpmf_saves">
                    <a class="ju-button orange-button waves-effect waves-light btndrive gpt-connector-button" href="#"
                       onclick="window.location.assign('<?php echo esc_html($urlGooglePhoto); ?>','foo','width=600,height=600');return false;">
                        <?php esc_html_e('Connect Google Photo', 'wpmf') ?></a>
                </div>

                <?php
            } else {
                ?>
                <div class="btn_wpmf_saves">
                    <a class="ju-button no-background orange-button waves-effect waves-light btndrive gpt-connector-button"
                       href="<?php echo esc_html(admin_url('options-general.php?page=option-folder&task=wpmf&function=wpmf_google_photo_logout')) ?>">
                        <?php esc_html_e('Disconnect Google Photo', 'wpmf') ?></a>
                </div>
                <?php
            }
        } else {
            $config_mode = get_option('joom_cloudconnector_wpmf_gpt_connect_mode', 'manual');
            if ($config_mode && $config_mode === 'automatic') {
                echo '<div class="btn_wpmf_saves"><div class="gpt-connector-button"></div></div>';
            } else {
                echo '<div class="btn_wpmf_saves"><div class="gpt-connector-button"></div></div>';
                require WP_MEDIA_FOLDER_PLUGIN_DIR . '/class/pages/settings/submit_button.php';
            }
        }
        ?>
    </div>
    <div class="content-box">
        <?php
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- View request, no action
        if (isset($_POST['btn_wpmf_save'])) {
            ?>
            <div class="wpmf_width_100 top_bar saved_infos" style="padding: 20px 0">
                <?php
                require WP_MEDIA_FOLDER_PLUGIN_DIR . '/class/pages/settings/saved_info.php';
                ?>
            </div>
            <?php
        }
        ?>

        <div class="wpmf_width_100 ju-settings-option">
            <div class="p-d-20">
                <?php
                if (is_plugin_active('wp-media-folder-addon/wp-media-folder-addon.php')) {
                    // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
                    echo $html_google_photo;
                }
                ?>
            </div>
        </div>
    </div>
</div>

<div id="dropbox_box" class="tab-content">
    <div class="wpmf_width_100 p-tb-20 wpmf_left top_bar">
        <h1 class="wpmf_left"><?php esc_html_e('Dropbox', 'wpmf') ?></h1>
        <?php
        do_action('cloudconnector_wpmf_display_dropbox_connect_button');
        if (isset($dropboxconfig['dropboxKey']) && $dropboxconfig['dropboxKey'] !== ''
            && isset($dropboxconfig['dropboxSecret']) && $dropboxconfig['dropboxSecret'] !== '') {
            if ($Dropbox->checkAuth()) {
                try {
                    $urlDropbox = $Dropbox->getAuthorizeDropboxUrl();
                } catch (Exception $e) {
                    $urlDropbox = '';
                }
            }
            if ($Dropbox->checkAuth()) {
                ?>
                <div class="btn_wpmf_saves">
                    <a class="ju-button orange-button waves-effect waves-light btndrive dropbox-connector-button" href="#"
                       onclick="window.open('<?php echo esc_html($urlDropbox); ?>','foo','width=600,height=600');return false;">
                        <?php esc_html_e('Connect Dropbox', 'wpmf') ?></a>
                </div>

                <?php
            } else { ?>
                <div class="btn_wpmf_saves">
                    <a class="ju-button no-background orange-button waves-effect waves-light btndrive dropbox-connector-button"
                       href="<?php echo esc_html(admin_url('options-general.php?page=option-folder&task=wpmf&function=wpmf_dropboxlogout')) ?>">
                        <?php esc_html_e('Disconnect Dropbox', 'wpmf') ?></a>
                </div>
                <?php
            }
        } else {
            $config_mode = get_option('joom_cloudconnector_wpmf_dropbox_connect_mode', 'manual');
            if ($config_mode && $config_mode === 'automatic') {
                echo '<div class="btn_wpmf_saves"><div class="dropbox-connector-button"></div></div>';
            } else {
                echo '<div class="btn_wpmf_saves"><div class="dropbox-connector-button"></div></div>';
                require WP_MEDIA_FOLDER_PLUGIN_DIR . '/class/pages/settings/submit_button.php';
            }
        }
        ?>
    </div>
    <div class="content-box">
        <?php
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- View request, no action
        if (isset($_POST['btn_wpmf_save'])) {
            ?>
            <div class="wpmf_width_100 top_bar saved_infos" style="padding: 20px 0">
                <?php
                require WP_MEDIA_FOLDER_PLUGIN_DIR . '/class/pages/settings/saved_info.php';
                ?>
            </div>
            <?php
        }
        ?>

        <div class="wpmf_width_100  ju-settings-option">
            <div class="p-d-20">
                <?php
                if (is_plugin_active('wp-media-folder-addon/wp-media-folder-addon.php')) {
                    // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
                    echo $html_tabdropbox;
                }
                ?>
            </div>
        </div>
    </div>
</div>

<div id="one_drive_box" class="tab-content">
    <div class="wpmf_width_100 p-tb-20 wpmf_left top_bar">
        <h1 class="wpmf_left"><?php esc_html_e('OneDrive Personal', 'wpmf') ?></h1>
        <?php
        require WP_MEDIA_FOLDER_PLUGIN_DIR . '/class/pages/settings/submit_button.php';
        ?>
    </div>

    <div class="content-box">
        <?php
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- View request, no action
        if (isset($_POST['btn_wpmf_save'])) {
            ?>
            <div class="wpmf_width_100 top_bar saved_infos" style="padding: 20px 0">
                <?php
                require WP_MEDIA_FOLDER_PLUGIN_DIR . '/class/pages/settings/saved_info.php';
                ?>
            </div>
            <?php
        }
        ?>

        <div class="wpmf_width_100 ju-settings-option">
            <div class="p-d-20">
                <?php
                if (is_plugin_active('wp-media-folder-addon/wp-media-folder-addon.php')) {
                    // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
                    echo $html_onedrive_settings;
                }
                ?>
            </div>
        </div>

        <?php
        if (is_plugin_active('wp-media-folder-addon/wp-media-folder-addon.php')) :
            ?>
            <h1 class="wpmf_left"><?php esc_html_e('OneDrive Business', 'wpmf') ?></h1>
            <div class="wpmf_width_100 ju-settings-option">
                <div class="p-d-20">
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
                    echo $html_onedrive_business_settings;
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="storage_provider" class="tab-content">
    <div class="wpmf_width_100 p-tb-20 wpmf_left top_bar">
        <h1 class="wpmf_left">
            <?php
            $clouds = array(
                'aws3' => array('key' => 'aws3', 'name' => 'Amazon S3'),
                'digitalocean' => array('key' => 'digitalocean', 'name' => 'DigitalOcean'),
                'wasabi' => array('key' => 'wasabi', 'name' => 'Wasabi'),
                'google_cloud_storage' => array('key' => 'google_cloud_storage', 'name' => 'Google Cloud Storage'),
                'linode' => array('key' => 'linode', 'name' => 'Linode'),
                'bunny_cdn_edge' => array('key' => 'bunny_cdn_edge', 'name' => 'Bunny CDN Edge Storage')
            );

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
            if (isset($_GET['cloud'])) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
                $storage = $_GET['cloud'];
            } else {
                $storage = get_option('wpmf_cloud_endpoint');
                if (empty($storage)) {
                    $storage = 'aws3';
                }
            }

            echo esc_html($clouds[$storage]['name'])
            ?>
        </h1>
        <?php
        require WP_MEDIA_FOLDER_PLUGIN_DIR . '/class/pages/settings/submit_button.php';
        ?>
    </div>
    <div class="content-box content-wpmf-general">
        <?php
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- View request, no action
        if (isset($_POST['btn_wpmf_save'])) {
            ?>
            <div class="wpmf_width_100 top_bar saved_infos" style="padding: 20px 0">
                <?php
                require WP_MEDIA_FOLDER_PLUGIN_DIR . '/class/pages/settings/saved_info.php';
                ?>
            </div>
            <?php
        }
        ?>

        <div>
            <div class="wpmf_row_full">
                <?php
                if (is_plugin_active('wp-media-folder-addon/wp-media-folder-addon.php')) {
                    // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
                    echo $html_tabaws3;
                }
                ?>
            </div>
        </div>
    </div>
</div>

<div id="synchronization" class="tab-content">
    <div class="wpmf_width_100 p-tb-20 wpmf_left top_bar">
        <h1 class="wpmf_left"><?php esc_html_e('Synchronization', 'wpmf') ?></h1>
        <?php
        require WP_MEDIA_FOLDER_PLUGIN_DIR . '/class/pages/settings/submit_button.php';
        ?>
    </div>
    <div class="content-box content-wpmf-general">
        <?php
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- View request, no action
        if (isset($_POST['btn_wpmf_save'])) {
            ?>
            <div class="wpmf_width_100 top_bar saved_infos" style="padding: 20px 0">
                <?php
                require WP_MEDIA_FOLDER_PLUGIN_DIR . '/class/pages/settings/saved_info.php';
                ?>
            </div>
            <?php
        }
        ?>

        <div>
            <div class="wpmf_row_full">
                <?php
                if (is_plugin_active('wp-media-folder-addon/wp-media-folder-addon.php')) {
                    // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
                    echo $synchronization;
                }
                ?>
            </div>
        </div>
    </div>
</div>