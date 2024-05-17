<?php
wp_enqueue_script('wpmf-popup');
wp_enqueue_style('wpmf-css-popup');
$parse_url = parse_url(home_url());
if (isset($parse_url['path'])) {
    $javaScript_origins = str_replace($parse_url['path'], '', home_url());
} else {
    $javaScript_origins = home_url();
}
?>
<style>
    #manage-bucket {
        position: relative;
    }

    #manage-bucket.loading:before {
        content: '';
        width: calc(100% - 40px);
        height: calc(100% - 40px);
        position: absolute;
        background-color: rgba(255, 255, 255, 0.6);
        z-index: 9;
    }

    .cloud_provider_wrap.change_provider > h2,
    .cloud_provider_wrap.change_provider > div {
        display: none;
    }

    .cloud_provider_wrap.change_provider .enpoint_wrap {
        display: block;
    }

    .enpoint_list {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        column-gap: 20px;
        width: 100%;
    }

    .enpoint_item {
        min-width: 140px;
        margin-bottom: 20px;
        padding: 20px;
        background: #fff;
        position: relative;
        font-weight: bold;
        font-size: 14px;
        box-shadow: 0px 3px 6px #d9d9d999;
        border-radius: 4px;
        width: calc((100% - 60px) / 4);
        display: none;
    }

    .change_provider .enpoint_item {
        display: block;
    }

    .enpoint_selected {
        display: block;
        border: 3px solid #ff8726;
        box-shadow: 1px 1px 12px #ccc;
    }

    .enpoint_item a {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        width: 100%;
        justify-content: center;
        position: relative;
        font-weight: bold;
        font-size: 14px;
        flex-direction: column;
        text-decoration: none;
        color: #404852;
        outline: 0 !important;
        box-shadow: none;
        border: 0 !important;
    }

    .enpoint_selected a {
        color: #ff8726;
    }

    .enpoint_wrap h3 {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }

    .provider_icon {
        position: absolute !important;
        right: 10px;
        top: 10px;
        width: auto !important;
        z-index: 99;
    }

    .provider_change_icon {
        position: relative !important;
        color: #ff8726;
        margin-left: 5px;
        top: auto;
        right: auto;
        outline: 0 !important;
        box-shadow: none !important;
    }

    .change_provider .provider_icon {
        display: none;
    }

    .provider_settings_icon {
        display: none;
    }

    .change_provider .provider_settings_icon {
        display: none;
    }

    .change_provider .enpoint_selected .provider_settings_icon {
        display: block;
    }

    .enpoint_item input {
        position: absolute;
        left: 0;
        top: 0;
    }

    .enpoint_item.enpoint_selected input {
        display: none;
    }

    .change_provider .enpoint_item.enpoint_selected input {
        display: block;
    }

    .enpoint_item img {
        height: 100px;
        width: auto;
        padding: 0;
        margin-bottom: 15px;
        border-radius: 4px;
    }

    .wpmf_hide_settings {
        display: none;
    }

    .aws3-connect-wrap {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
    }

    .aws3-connect-wrap .aws3-client-wrap {
        width: calc(50% - 12px);
        margin-bottom: 12px;
    }

    .ju-settings-option {
        position: relative;
    }

    .ju-settings-option.loading:before {
        background: rgba(255, 255, 255, 0.4);
        position: absolute;
        width: 100%;
        height: 100%;
        z-index: 9;
        content: '';
    }
    .bucket_error_msg {
        font-size: 12px;
        color: #f00;
    }

    .copy_section_wrap {
        margin: 10px 0 20px 0; width: 100%; display: inline-block
    }

    .copy_section_wrap > select, .copy_section_wrap > button {
        margin-bottom: 10px;
    }
</style>
<?php
$cloud_lists = array(
    'aws3' => array(
        'key' => 'aws3',
        'name' => 'Amazon S3',
        'img' => WPMFAD_PLUGIN_URL . 'assets/images/AWS-cloud-storage.png',
        'img_class' => 'endpoint_aws3_img',
        'console_link' => 'https://console.aws.amazon.com/s3/buckets',
        'bucket' => esc_html__('Bucket', 'wpmfAddon'),
        'document_link' => 'https://www.joomunited.com/wordpress-documentation/wp-media-folder/289-wp-media-folder-addon-amazon-s3-integration'
    ),
    'digitalocean' => array(
        'key' => 'digitalocean',
        'name' => 'DigitalOcean',
        'img' => WPMFAD_PLUGIN_URL . 'assets/images/digitalocean-cloud-storage.png',
        'img_class' => 'endpoint_digitalocean_img',
        'console_link' => 'https://cloud.digitalocean.com/spaces',
        'bucket' => esc_html__('Space', 'wpmfAddon'),
        'document_link' => 'https://www.joomunited.com/wordpress-documentation/wp-media-folder/647-wp-media-folder-addon-digitalocean-integration'
    ),
    'wasabi' => array(
        'key' => 'wasabi',
        'name' => 'Wasabi',
        'img' => WPMFAD_PLUGIN_URL . 'assets/images/wasabi-cloud-storage.png',
        'img_class' => 'wasabi_img',
        'console_link' => 'https://console.wasabisys.com/#/file_manager',
        'bucket' => esc_html__('Bucket', 'wpmfAddon'),
        'document_link' => 'https://www.joomunited.com/wordpress-documentation/wp-media-folder/646-wp-media-folder-addon-wasabi-integration'
    ),
    'linode' => array(
        'key' => 'linode',
        'name' => 'Linode',
        'img' => WPMFAD_PLUGIN_URL . 'assets/images/linode-cloud-storage.png',
        'img_class' => 'linode_img',
        'console_link' => 'https://cloud.linode.com/object-storage',
        'bucket' => esc_html__('Bucket', 'wpmfAddon'),
        'document_link' => 'https://www.joomunited.com/wordpress-documentation/wp-media-folder/652-wp-media-folder-addon-linode-integration'
    ),
    'google_cloud_storage' => array(
        'key' => 'google_cloud_storage',
        'name' => 'Google Cloud Storage',
        'img' => WPMFAD_PLUGIN_URL . 'assets/images/google-cloud-storage.png',
        'img_class' => 'gcs_img',
        'console_link' => 'https://console.cloud.google.com/storage/',
        'bucket' => esc_html__('Bucket', 'wpmfAddon'),
        'document_link' => 'https://www.joomunited.com/wordpress-documentation/wp-media-folder/654-wp-media-folder-addon-google-cloud-storage-integration'
    )
    //'google_cloud_storage' => array('key' => 'google_cloud_storage', 'name' => 'Google Cloud Storage', 'img' => WPMFAD_PLUGIN_URL . 'assets/images/google-cloud-storage.png', 'img_class' => 'endpoint_gcs_img'),
    //'bunny_cdn_edge' => array('key' => 'bunny_cdn_edge', 'name' => 'Bunny CDN Edge Storage', 'img' => WPMFAD_PLUGIN_URL . 'assets/images/bunnynet-logo.svg', 'img_class' => 'bunnynet_img')
);
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
$class_wrap = (isset($_GET['end_action']) && $_GET['end_action'] === 'change_provider') ? 'change_provider' : '';
?>
<div class="cloud_provider_wrap <?php echo esc_attr($class_wrap); ?>">
    <?php
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
    if (isset($_GET['end_action']) && $_GET['end_action'] === 'change_provider') :
        ?>
        <div class="enpoint_wrap">
            <h3><?php esc_html_e('STORAGE PROVIDER', 'wpmfAddon'); ?></h3>
            <div class="enpoint_list">
                <input type="hidden" name="wpmf_redirect"
                       value="<?php echo esc_url(admin_url('options-general.php?page=option-folder#storage_provider')) ?>">
                <?php
                foreach ($cloud_lists as $cloud_item) :
                    ?>
                    <div class="enpoint_item <?php echo ($cloud_endpoint === $cloud_item['key']) ? 'enpoint_selected' : '' ?>">
                        <a class="provider_icon"
                           href="<?php echo esc_url(admin_url('options-general.php?page=option-folder&end_action=change_provider#storage_provider')) ?>"><span
                                    class="material-icons-outlined">drive_file_rename_outline</span></a>
                        <a class="provider_icon provider_settings_icon"
                           href="<?php echo esc_url(admin_url('options-general.php?page=option-folder&cloud=' . $cloud_item['key'] . '#storage_provider')) ?>"><span
                                    class="material-icons-outlined">settings</span></a>
                        <a>
                            <input type="radio" name="wpmf_cloud_endpoint" class="wpmf_cloud_endpoint"
                                   value="<?php echo esc_attr($cloud_item['key']) ?>" <?php checked($cloud_endpoint, $cloud_item['key']) ?>>
                            <img class="<?php echo esc_attr($cloud_item['img_class']) ?>"
                                 src="<?php echo esc_url($cloud_item['img']) ?>">
                            <?php echo esc_html($cloud_item['name']) ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="btn_wpmf_saves"
                 style="padding: 0;display: flex; align-items: center; justify-content: flex-start;">
                <button type="button"
                        data-target_url="<?php echo esc_url(admin_url('options-general.php?page=option-folder#storage_provider')) ?>"
                        class="btn_wpmf_save wpmf_save_storage ju-button orange-button waves-effect waves-light"><?php esc_html_e('Save Changes', 'wpmfAddon'); ?></button>
                <span class="spinner" style="margin: 0 0 0 10px"></span>
            </div>
        </div>
    <?php else : ?>
        <div class="enpoint_wrap">
            <div style="display: flex; align-items: center; flex-wrap: wrap; justify-content: space-between;">
                <h3>
                    <?php esc_html_e('SELECT CLOUD PROVIDER', 'wpmfAddon'); ?>
                    <a class="provider_icon provider_change_icon"
                       href="<?php echo esc_url(admin_url('options-general.php?page=option-folder&end_action=change_provider#storage_provider')) ?>"><span
                                class="material-icons-outlined">drive_file_rename_outline</span></a>
                </h3>

                <?php
                if ($cloud_endpoint === 'google_cloud_storage') {
                    if (isset($aws3config['credentials']['key']) && $aws3config['credentials']['secret'] !== ''
                        && isset($aws3config['credentials']['secret']) && $aws3config['credentials']['secret'] !== '') {
                        if (empty($aws3config['connected'])) {
                            $googleDrive = new WpmfAddonGoogleDrive('google_cloud');
                            $google_cloud_auth_url = $googleDrive->getAuthorisationUrl(admin_url('options-general.php?page=option-folder&task=wpmf&function=wpmf_google_cloud_auth'), 'google_cloud_storage');
                            ?>
                            <a class="ju-button orange-button waves-effect waves-light btndrive"
                               href="#"
                               onclick="window.location.assign('<?php echo esc_html($google_cloud_auth_url); ?>','foo','width=600,height=600');return false;">
                                <?php esc_html_e('Connect', 'wpmfAddon') ?></a>

                            <?php
                        } else {
                            $url_logout = admin_url('options-general.php?page=option-folder&task=wpmf&function=wpmf_google_cloud_logout');
                            ?>
                            <a class="ju-button no-background orange-button waves-effect waves-light btndrive"
                               href="<?php echo esc_html($url_logout) ?>">
                                <?php esc_html_e('Disconnect', 'wpmfAddon') ?></a>
                            <?php
                        }
                    }
                }
                ?>
            </div>

            <div class="enpoint_list">
                <?php
                foreach ($cloud_lists as $cloud_item) :
                    if ($cloud_endpoint !== $cloud_item['key']) {
                        continue;
                    }
                    ?>
                    <div class="enpoint_item enpoint_selected" style="display: none">
                        <a class="provider_icon"
                           href="<?php echo esc_url(admin_url('options-general.php?page=option-folder&end_action=change_provider#storage_provider')) ?>"><span
                                    class="material-icons-outlined">drive_file_rename_outline</span></a>
                        <a href="<?php echo esc_url(admin_url('options-general.php?page=option-folder&cloud=' . $cloud_item['key'] . '#storage_provider')) ?>">
                            <input type="radio" name="wpmf_cloud_endpoint" class="wpmf_cloud_endpoint"
                                   value="<?php echo esc_attr($cloud_item['key']) ?>" checked>
                            <img class="<?php echo esc_attr($cloud_item['img_class']) ?>"
                                 src="<?php echo esc_url($cloud_item['img']) ?>">
                            <?php echo esc_html($cloud_item['name']) ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="download-s3-popup" class="white-popup mfp-hide">
            <h3><?php printf(esc_html__('Retrieve %s media', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['name'])) ?></h3>
            <p class="description"><?php printf(esc_html__('This action will retrieve all your media from your %1$s %2$s and copy it back to your server, links to media will be reverted back to the original local image. This is useful when you want to remove the %3$s integration only.', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['name']), esc_html($cloud_lists[$cloud_endpoint]['bucket']), esc_html($cloud_lists[$cloud_endpoint]['name'])) ?></p>
            <div class="wpmf-process-bar-full wpmf-process-bar-download-s3-full s3_process_download_wrap">
                <div class="wpmf-process-bar wpmf-process-bar-download-s3" data-w="0"></div>
                <span>0%</span>
            </div>
            <div class="action_download_s3">
                <a class="ju-button wpmf-small-btn btn-cancel-popup-download-s3"><?php esc_html_e('Cancel', 'wpmfAddon') ?></a>
                <a class="ju-button orange-button wpmf-small-btn btn-download-s3"
                   data-cloud="<?php echo esc_html($cloud_endpoint) ?>"
                   data-msg="<?php printf(esc_html__('Downloading the files from %s...', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['name'])) ?>"><?php esc_html_e('Retrieve media', 'wpmfAddon') ?></a>
            </div>
        </div>
        <div id="manage-bucket" class="white-popup mfp-hide">
            <div class="table-list-buckets m-b-40">
                <h3><?php printf(esc_html__('Select an existing %s', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['bucket'])) ?></h3>
                <?php if ($cloud_endpoint === 'digitalocean' || $cloud_endpoint === 'linode') : ?>
                    <div>
                        <h3><?php esc_html_e('Region', 'wpmfAddon') ?></h3>
                        <div>
                            <label>
                                <select class="select-bucket-region" data-endpoint="<?php echo esc_attr($cloud_endpoint) ?>">
                                    <?php
                                    if (!empty($aws3->regions)) :
                                        foreach ($aws3->regions as $k_regions => $v_region) :
                                            ?>
                                            <option value="<?php echo esc_attr($k_regions); ?>" <?php selected($aws3config['region'], $k_regions) ?> ><?php echo esc_html($v_region); ?></option>
                                        <?php endforeach;
                                    endif;
                                    ?>
                                </select>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                <table class="wpmf_width_100">
                    <thead>
                    <tr>
                        <th style="width: 30%"><?php printf(esc_html__('%s name', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['bucket'])) ?></th>
                        <th style="width: 30%"><?php esc_html_e('Date created', 'wpmfAddon') ?></th>
                        <th style="width: 30%"></th>
                        <th style="width: 10%"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($list_buckets['Buckets'])) {
                        foreach ($list_buckets['Buckets'] as $bucket) {
                            if ($cloud_endpoint === 'google_cloud_storage') {
                                $region = $bucket['region'];
                            } else {
                                if ($cloud_endpoint !== 'digitalocean' && $cloud_endpoint !== 'linode') {
                                    $region = $aws3->getBucketLocation(
                                        array('Bucket' => $bucket['Name'])
                                    );
                                }
                            }
                            ?>
                            <tr class="row_bucket <?php echo (isset($aws3config['bucket']) && $aws3config['bucket'] === $bucket['Name']) ? 'bucket-selected' : 'aws3-select-bucket' ?>"
                                data-region="<?php echo esc_attr($region) ?>"
                                data-bucket="<?php echo esc_attr($bucket['Name']) ?>">
                                <td style="width: 30%"><?php echo esc_html($bucket['Name']) ?></td>
                                <td style="width: 30%"><?php echo esc_html($bucket['CreationDate']) ?></td>
                                <td style="width: 30%">
                                    <?php if (isset($aws3config['bucket']) && $aws3config['bucket'] === $bucket['Name']) : ?>
                                        <label class="btn-select-bucket">
                                            <?php printf(esc_html__('Selected %s', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['bucket'])) ?>
                                        </label>
                                    <?php else : ?>
                                        <label class="btn-select-bucket">
                                            <?php printf(esc_html__('Select %s', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['bucket'])) ?>
                                        </label>
                                    <?php endif; ?>
                                </td>
                                <td style="width: 10%">
                                    <a class="delete-bucket wpmftippy"
                                       data-wpmftippy="<?php printf(esc_html__('Delete %s', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['bucket'])) ?>"
                                       data-bucket="<?php echo esc_attr($bucket['Name']) ?>"><i class="material-icons">delete_outline</i></a>
                                    <img src="<?php echo esc_url(WPMFAD_PLUGIN_URL . 'assets/images/spinner.gif') ?>"
                                         class="spinner-delete-bucket">
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                    </tbody>
                </table>
            </div>

            <div class="wpmf-create-bucket-wrap">
                <div>
                    <h3><?php printf(esc_html__('Create a new %s', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['bucket'])) ?></h3>
                    <div>
                        <label>
                            <input type="text" class="wpmf_width_100 new-bucket-name"
                                   placeholder="<?php printf(esc_html__('New %s name', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['bucket'])) ?>">
                            <p class="bucket_error_msg"></p>
                        </label>
                    </div>
                </div>

                <div>
                    <h3><?php esc_html_e('Region', 'wpmfAddon') ?></h3>
                    <div>
                        <label>
                            <select class="new-bucket-region">
                                <?php
                                if (!empty($aws3->regions)) :
                                    foreach ($aws3->regions as $k_regions => $v_region) :
                                        ?>
                                        <option value="<?php echo esc_attr($k_regions); ?>"><?php echo esc_html($v_region); ?></option>
                                    <?php endforeach;
                                endif;
                                ?>
                            </select>
                        </label>
                    </div>
                </div>

                <div class="wpmf_width_100 m-t-20 action-aws-btn">
                    <button type="button"
                            class="ju-button wpmf-small-btn cancel-bucket-btn"><?php esc_html_e('Cancel', 'wpmfAddon') ?></button>
                    <button type="button"
                            class="ju-button orange-button wpmf-small-btn create-bucket-btn" data-endpoint="<?php echo esc_attr($cloud_endpoint) ?>"><?php esc_html_e('Create', 'wpmfAddon') ?></button>
                    <span class="spinner create-bucket-spinner"></span>
                </div>
            </div>
        </div>

        <?php
        $defined_configs = array();
        if (defined('WPMF_AWS3_SETTINGS')) {
            $defined_configs = unserialize(WPMF_AWS3_SETTINGS);
        }
        ?>
        <div class="ju-settings-option wpmf_width_100 p-d-20 aws3-connect-wrap">
            <div class="aws3-client-wrap">
                <h4><?php echo (($cloud_endpoint === 'google_cloud_storage') ? esc_html__('Client ID', 'wpmfAddon') : esc_html__('Access Key ID', 'wpmfAddon')) ?></h4>
                <input title="<?php esc_attr_e('Access Key ID', 'wpmfAddon') ?>" autocomplete="off"
                       name="aws3_config[credentials][key]" type="text" class="regular-text wpmf_width_100 p-lr-20"
                       value="<?php echo esc_attr(trim($aws3config['credentials']['key'])) ?>" <?php echo (!empty($defined_configs)) ? 'readonly' : '' ?>>
            </div>

            <div class="aws3-client-wrap">
                <h4><?php echo (($cloud_endpoint === 'google_cloud_storage') ? esc_html__('Client Secret', 'wpmfAddon') : esc_html__('Secret Access Key', 'wpmfAddon')) ?></h4>
                <input title="<?php esc_attr_e('Secret Access Key', 'wpmfAddon') ?>" autocomplete="off"
                       name="aws3_config[credentials][secret]" type="password"
                       class="regular-text wpmf_width_100 p-lr-20"
                       value="<?php echo esc_attr(trim($aws3config['credentials']['secret'])) ?>" <?php echo (!empty($defined_configs)) ? 'readonly' : '' ?>>
            </div>

            <?php if ($cloud_endpoint === 'google_cloud_storage') : ?>
            <div class="aws3-client-wrap">
                <h4><?php esc_html_e('Project ID', 'wpmfAddon') ?></h4>
                <input autocomplete="off"
                       name="aws3_config[credentials][project_id]" type="text"
                       class="regular-text wpmf_width_100 p-lr-20"
                       value="<?php echo (!empty($aws3config['credentials']['project_id'])) ? esc_attr(trim($aws3config['credentials']['project_id'])) : '' ?>">
            </div>

            <div class="aws3-client-wrap">
                <h4><?php esc_html_e('JavaScript origins', 'wpmfAddon') ?></h4>
                <input autocomplete="off"
                       type="text"
                       class="regular-text wpmf_width_100 p-lr-20"
                       value="<?php echo esc_attr($javaScript_origins); ?>" readonly>
            </div>

            <div class="aws3-client-wrap">
                <h4><?php esc_html_e('Redirect URIs', 'wpmfAddon') ?></h4>
                <input  autocomplete="off"
                       type="text"
                       class="regular-text wpmf_width_100 p-lr-20"
                       value="<?php echo esc_attr(admin_url('options-general.php?page=option-folder&task=wpmf&function=wpmf_google_cloud_auth')) ?>" readonly>
            </div>
            <?php endif; ?>
            <?php
            if ($cloud_endpoint !== 'google_cloud_storage') {
                if (!$connect && !empty($aws3config['credentials']['key']) && !empty($aws3config['credentials']['secret'])) {
                    echo '<p class="wpmf-warning"><b>' . esc_html__('Connection failed: ', 'wpmfAddon') . '</b>' . esc_html($msg) . '</p>';
                }
            }
            ?>
            <?php if (!defined('WPMF_AWS3_SETTINGS')) : ?>
                <?php if ($connect) : ?>
                    <div class="wpmf_width_100">
                        <h4><?php echo esc_html($cloud_lists[$cloud_endpoint]['bucket']) ?></h4>
                        <div class="buckets_wrap">
                            <?php if (!empty($list_buckets['Buckets'])) : ?>
                                <?php if (!empty($aws3config['bucket'])) : ?>
                                    <b class="current_bucket"><?php echo esc_html($aws3config['bucket']); ?></b>
                                <?php else : ?>
                                    <b class="current_bucket"><?php printf(esc_html__('Please select an %1$s %2$s to start using %3$s server', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['name']), esc_html($cloud_lists[$cloud_endpoint]['bucket']), esc_html($cloud_lists[$cloud_endpoint]['name'])) ?></b>
                                <?php endif; ?>
                            <?php else : ?>
                                <b class="current_bucket"></b>
                            <?php endif; ?>
                            <?php
                            if (!empty($location_name) && !empty($list_buckets['Buckets'])) {
                                echo '<span class="lb-current-region">' . esc_html($location_name) . '</span>';
                            } else {
                                echo '<span class="lb-current-region"></span>';
                            }
                            ?>
                            <?php if (empty($list_buckets['Buckets'])) : ?>
                                <div class="msg-no-bucket show">
                                    <label><?php esc_html_e('No bucket found, please add a bucket to be able to use this feature', 'wpmfAddon') ?></label>
                                    <a class="ju-button orange-button wpmf-small-btn aws3-manage-bucket"
                                       href="#manage-bucket">
                                        <?php esc_html_e('Add bucket', 'wpmfAddon') ?>
                                    </a>
                                </div>
                            <?php else : ?>
                                <div class="msg-no-bucket">
                                    <label><?php esc_html_e('No bucket found, please add a bucket to be able to use this feature', 'wpmfAddon') ?></label>
                                    <a class="ju-button orange-button wpmf-small-btn aws3-manage-bucket"
                                       href="#manage-bucket">
                                        <?php esc_html_e('Add bucket', 'wpmfAddon') ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <a class="ju-button orange-button wpmf-small-btn aws3-manage-bucket"
                               href="#manage-bucket">
                                <?php printf(esc_html__('%s settings and selection', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['bucket'])) ?>
                            </a>
                            <?php if (!empty($aws3config['bucket']) && !empty($list_buckets['Buckets'])) : ?>
                                <a class="ju-button wpmf-small-btn aws3-view-console"
                                   href="<?php echo esc_url($cloud_lists[$cloud_endpoint]['console_link']) ?>"
                                   target="_blank"><?php esc_html_e('View console', 'wpmfAddon') ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="wpmf_width_100 wpmf-inline">
            <div class="ju-settings-option">
                <div class="wpmf_row_full">
                    <input type="hidden" name="aws3_config[copy_files_to_bucket]" value="0">
                    <label data-wpmftippy="<?php printf(esc_html__('When a file is uploaded to your media library, a copy will be sent to %1$s %2$s. On frontend the media will be loaded from the %3$s server', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['name']), esc_html($cloud_lists[$cloud_endpoint]['bucket']), esc_html($cloud_lists[$cloud_endpoint]['name'])); ?>"
                           class="ju-setting-label text"><?php echo esc_html__('Copy to', 'wpmfAddon') . ' ' . esc_html($cloud_lists[$cloud_endpoint]['name']) ?></label>
                    <div class="ju-switch-button">
                        <label class="switch">
                            <input type="checkbox" class="copy_files_to_bucket" name="aws3_config[copy_files_to_bucket]"
                                   value="1"
                                <?php
                                if (isset($aws3config['copy_files_to_bucket']) && (int)$aws3config['copy_files_to_bucket'] === 1) {
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
                    <input type="hidden" name="aws3_config[remove_files_from_server]" value="0">
                    <label data-wpmftippy="<?php printf(esc_html__('When a file has been uploaded to %s, the local copy will be deleted', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['name'])); ?>"
                           class="ju-setting-label text"><?php esc_html_e('Remove after upload', 'wpmfAddon') ?></label>
                    <div class="ju-switch-button">
                        <label class="switch">
                            <input type="checkbox" name="aws3_config[remove_files_from_server]"
                                   value="1"
                                <?php
                                if (isset($aws3config['remove_files_from_server']) && (int)$aws3config['remove_files_from_server'] === 1) {
                                    echo 'checked';
                                }
                                ?>
                            >
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="ju-settings-option">
                <div class="wpmf_row_full">
                    <input type="hidden" name="aws3_config[attachment_label]" value="0">
                    <label data-wpmftippy="<?php printf(esc_html__('Apply a label on each media to visually see that the media is on %s', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['name'])); ?>"
                           class="ju-setting-label text"><?php esc_html_e('Attachment label', 'wpmfAddon') ?></label>
                    <div class="ju-switch-button">
                        <label class="switch">
                            <input type="checkbox" name="aws3_config[attachment_label]"
                                   value="1"
                                <?php
                                if (isset($attachment_label) && (int)$attachment_label === 1) {
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

        <div class="sync-aws3-wrap wpmf-option-storage-wrap <?php echo (!empty($aws3config['copy_files_to_bucket']) && $connect) ? 'wpmf_show_settings' : 'wpmf_hide_settings' ?>">
            <div class="s3-process-wrap">
                <div class="s3-process-left">
                    <label class="status-text-s3-sync"><span><?php echo esc_html($s3_percent['s3_percent']); ?></span>% <?php printf(esc_html__('of your Media Library has been uploaded to %s', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['name'])) ?>
                    </label>
                    <div class="s3-button-sync-wrap">
                        <div class="wpmf_row_full">
                            <div
                                    data-enable="<?php echo !empty($aws3config['copy_files_to_bucket']) ? 1 : 0 ?>"
                                    data-cloud="<?php echo esc_html($cloud_endpoint) ?>"
                                    data-text="<?php esc_html_e('Synchronize Media', 'wpmfAddon') ?>"
                                    data-msg="<?php printf(esc_html__('Uploading the files to %s...', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['name'])) ?>"
                                    data-wpmftippy="<?php printf(esc_attr__('Synchronize the whole media library with %s. Note that it applies the options if checked above like removing media from local server.', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['name'])) ?>"
                                    class="wpmftippy ju-button wpmf-small-btn <?php echo ($connect) ? 'btn-dosync-s3 btn-sync-s3' : 'btn-dosync-s3-disabled' ?>">
                                <labeL><?php esc_html_e('Synchronize Media', 'wpmfAddon') ?></labeL><span
                                        class="spinner spinner-syncS3" style="visibility: visible"></span></div>
                        </div>
                    </div>
                </div>
                <div class="s3-process-right">
                    <div class="syncs3-circle-bar"><strong></strong></div>
                    <input type="hidden" id="progressController"
                           value="<?php echo esc_attr($s3_percent['s3_percent']) ?>"/>
                    <input type="hidden" id="s3sync_ok" value="<?php echo esc_attr($s3_percent['s3_percent']) ?>"/>
                </div>

                <div class="wpmf-process-bar-full wpmf-process-bar-syncs3-full s3_process_sync_wrap"
                     data-local-files-count="<?php echo esc_attr($s3_percent['local_files_count']) ?>">
                    <div class="wpmf-process-bar wpmf-process-bar-syncs3" data-w="0"></div>
                    <span>0%</span>
                </div>

                <div style="margin-top: 20px; width: 100%; display: inline-block">
                    <h4>
                        <?php esc_html_e('File type to include in synchronization', 'wpmfAddon') ?></h4>
                    <label class="wpmf_width_100">
                    <textarea name="allow_syncs3_extensions"
                              class="wpmf_width_100 allow_syncs3_extensions"><?php echo esc_html($allow_syncs3_extensions) ?></textarea>
                    </label>
                </div>
            </div>
        </div>


        <div class="ju-settings-option p-lr-20 wpmf_width_100">
            <div style="margin: 10px 0 20px 0; width: 100%; display: inline-block">
                <h4 style="padding: 0; float: left"><?php esc_html_e('Cloudfront Integration', 'wpmfAddon') ?></h4>
                <div class="ju-switch-button" style="float: left">
                    <label class="switch">
                        <input type="hidden" name="aws3_config[enable_custom_domain]" value="0">
                        <input type="checkbox" name="aws3_config[enable_custom_domain]"
                               value="1"
                            <?php
                            if (isset($aws3config['enable_custom_domain']) && (int)$aws3config['enable_custom_domain'] === 1) {
                                echo 'checked';
                            }
                            ?>
                        >
                        <span class="slider round"></span>
                    </label>
                </div>
                <label class="ju-setting-label text"
                       style="padding: 0; width: 100%"><?php esc_html_e('Custom Domain (CNAME)', 'wpmfAddon') ?></label>
                <div class="wpmf_width_100">
                    <input autocomplete="off"
                           name="aws3_config[custom_domain]" type="text" class="regular-text p-lr-20"
                           value="<?php echo esc_attr($aws3config['custom_domain']) ?>">
                </div>
            </div>
        </div>

        <?php if ($connect) : ?>
            <div>
                <h2 style="padding: 0; float: left; font-size: 24px;"><?php esc_html_e('Advanced settings and actions', 'wpmfAddon') ?></h2>
                <div class="ju-settings-option p-lr-20 wpmf_width_100">
                    <div style="margin: 10px 0 20px 0; width: 100%; display: inline-block">
                        <label class="wpmf_width_100 ju-setting-label text"
                               style="padding: 0; margin-bottom: 5px"><?php printf(esc_html__('Import all the folders and files from %s server to Media library', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['name'])) ?></label>
                        <?php
                        if ($cloud_endpoint === 'digitalocean' || $cloud_endpoint === 'linode') :
                            ?>
                            <select class="select-bucket-region1"
                                    data-default_option="<option value=''><?php printf(esc_html__('Choose a %s', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['bucket'])) ?></option>"
                                    data-target=".wpmf_bucket_import" data-endpoint="<?php echo esc_attr($cloud_endpoint) ?>"
                                    style="margin-right: 5px">
                                <option value=""><?php esc_html_e('Select a region', 'wpmfAddon') ?></option>
                                <?php
                                if (!empty($aws3->regions)) {
                                    foreach ($aws3->regions as $k_regions => $v_region) {
                                        echo '<option value="' . esc_attr($k_regions) . '">' . esc_html($v_region) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        <?php endif; ?>

                        <select class="wpmf_bucket_import" style="margin-right: 5px">
                            <?php
                            if (!empty($list_buckets['Buckets'])) {
                                echo '<option value="">' . esc_html__('Choose a', 'wpmfAddon') . ' ' . esc_html($cloud_lists[$cloud_endpoint]['bucket']) . '</option>';
                                if ($cloud_endpoint !== 'digitalocean' && $cloud_endpoint !== 'linode') {
                                    foreach ($list_buckets['Buckets'] as $from_bucket) {
                                        echo '<option value="' . esc_attr($from_bucket['Name']) . '">' . esc_html($from_bucket['Name']) . '</option>';
                                    }
                                }
                            }
                            ?>
                        </select>
                        <button type="button"
                                class="ju-button wpmf-import-s3"
                                data-cloud="<?php echo esc_html($cloud_endpoint) ?>"><?php esc_html_e('Import', 'wpmfAddon') ?>
                            <span
                                    class="import-objects-bucket-spinner spinner"></span></button>
                    </div>
                </div>

                <div class="ju-settings-option p-lr-20 wpmf_width_100">
                    <div class="copy_section_wrap">
                        <label class="wpmf_width_100 ju-setting-label text"
                               style="padding: 0; margin-bottom: 5px"><?php printf(esc_html__('Copy all the files from a %1$s to other %2$s', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['bucket']), esc_html($cloud_lists[$cloud_endpoint]['bucket'])) ?></label>
                        <?php
                        if ($cloud_endpoint === 'digitalocean' || $cloud_endpoint === 'linode') :
                            ?>
                            <select class="select-bucket-region1"
                                    data-default_option="<option value=''><?php printf(esc_html__('From %s', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['bucket'])) ?></option>"
                                    data-target=".wpmf_from_bucket" data-endpoint="<?php echo esc_attr($cloud_endpoint) ?>"
                                    style="margin-right: 5px">
                                <option value=""><?php esc_html_e('Select a region', 'wpmfAddon') ?></option>
                                <?php
                                if (!empty($aws3->regions)) {
                                    foreach ($aws3->regions as $k_regions => $v_region) {
                                        echo '<option value="' . esc_attr($k_regions) . '">' . esc_html($v_region) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        <?php endif; ?>
                        <select class="wpmf_from_bucket">
                            <?php
                            if (!empty($list_buckets['Buckets'])) {
                                echo '<option value="">' . esc_html__('From', 'wpmfAddon') . ' ' . esc_html($cloud_lists[$cloud_endpoint]['bucket']) . '</option>';
                                foreach ($list_buckets['Buckets'] as $from_bucket) {
                                    echo '<option value="' . esc_attr($from_bucket['Name']) . '">' . esc_html($from_bucket['Name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                        <button type="button"
                                class="ju-button wpmf-copy-s3"
                                style="margin-right: 5px; margin-left: 5px"><?php esc_html_e('Copy the files', 'wpmfAddon') ?>
                            <span class="copy-objects-bucket-spinner spinner"></span></button>
                        <?php
                        if ($cloud_endpoint === 'digitalocean' || $cloud_endpoint === 'linode') :
                            ?>
                            <select class="select-bucket-region1"
                                    data-default_option="<option value=''><?php printf(esc_html__('To %s', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['bucket'])) ?></option>"
                                    data-target=".wpmf_to_bucket" data-endpoint="<?php echo esc_attr($cloud_endpoint) ?>"
                                    style="margin-right: 5px">
                                <option value=""><?php esc_html_e('Select a region', 'wpmfAddon') ?></option>
                                <?php
                                if (!empty($aws3->regions)) {
                                    foreach ($aws3->regions as $k_regions => $v_region) {
                                        echo '<option value="' . esc_attr($k_regions) . '">' . esc_html($v_region) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        <?php endif; ?>

                        <select class="wpmf_to_bucket" style="margin-right: 5px">
                            <?php
                            if (!empty($list_buckets['Buckets'])) :
                                echo '<option value="">' . esc_html__('To', 'wpmfAddon') . ' ' . esc_html($cloud_lists[$cloud_endpoint]['bucket']) . '</option>';
                                foreach ($list_buckets['Buckets'] as $to_bucket) {
                                    echo '<option value="' . esc_attr($to_bucket['Name']) . '">' . esc_html($to_bucket['Name']) . '</option>';
                                }
                            endif; ?>
                        </select>
                    </div>
                </div>

                <div class="ju-settings-option p-lr-20 wpmf_width_100">
                    <div style="margin: 10px 0 20px 0; width: 100%; display: inline-block">
                        <label class="wpmf_width_100 ju-setting-label text"
                               style="padding: 0; margin-bottom: 5px"><?php printf(esc_html__('Retrieve back all my %s media to the media library', 'wpmfAddon'), esc_html($cloud_lists[$cloud_endpoint]['name'])) ?></label>
                        <a class="ju-button no-background waves-effect waves-light btn-open-popup-download"
                           href="#download-s3-popup"
                        >
                            <?php esc_html_e('Retrieve media', 'wpmfAddon') ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="ju-settings-option p-lr-20 wpmf_width_100">
            <a target="_blank" class="ju-button no-background orange-button waves-effect waves-light"
               style="margin: 15px 0;"
               href="<?php echo esc_url($cloud_lists[$cloud_endpoint]['document_link']) ?>">
                <?php esc_html_e('Read the online documentation', 'wpmfAddon') ?>
            </a>
        </div>
        <div class="btn_wpmf_saves" style="padding: 0">
            <button type="submit" name="btn_wpmf_save"
                    class="btn_wpmf_save ju-button orange-button waves-effect waves-light"><?php esc_html_e('Save Changes', 'wpmfAddon'); ?></button>
        </div>
    <?php endif; ?>
</div>