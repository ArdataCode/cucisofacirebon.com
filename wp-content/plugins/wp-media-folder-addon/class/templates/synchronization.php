<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
?>
<div>
    <div class="ju-settings-option">
        <div class="wpmf_row_full">
            <label class="ju-setting-label wpmf_width_100"><?php esc_html_e('Sync Method', 'wpmfAddon') ?></label>
            <div class="wpmfcard wpmf_width_100 p-lr-10">
                <label class="radio">
                    <input type="radio" name="sync_method"
                           value="ajax" <?php checked($sync_method, 'ajax') ?>>
                    <span class="outer"><span class="inner"></span></span><?php esc_html_e('Use Ajax', 'wpmfAddon'); ?></label>
                <label class="radio">
                    <input type="radio" name="sync_method"
                           value="crontab" <?php checked($sync_method, 'crontab') ?>>
                    <span class="outer"><span class="inner"></span></span><?php esc_html_e('Crontab url', 'wpmfAddon'); ?></label>
            </div>
            <p class="description p-lr-20"><?php esc_html_e('The Cloud synchronization method. Default is AJAX, advanced user only.', 'wpmfAddon'); ?></p>
            <div class="wpmf-crontab-url-help-wrap <?php echo ($sync_method === 'crontab') ? 'show' : 'hide' ?>">
                <?php if ((!empty($odv_settings['connected']) && !empty($odv_settings['onedriveBaseFolder']['id']))
                    || (!empty($odvbn_settings['connected']) && !empty($odvbn_settings['onedriveBaseFolder']['id']))
                    || (!empty($google_settings['connected']) && !empty($google_settings['googleBaseFolder']))
                    || (!empty($dropbox_settings['dropboxToken']))) : ?>
                <div class="wpmf-crontab-url-help">/usr/bin/php <?php echo esc_html(ABSPATH . 'wp-cron.php') ?> >/dev/null 2>&1</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="ju-settings-option wpmf_right m-r-0">
        <div class="wpmf_row_full">
            <label class="ju-setting-label wpmf_width_100"><?php esc_html_e('Sync periodicity', 'wpmfAddon') ?></label>
            <label class="wpmf_width_100 p-lr-20 line-height-40">
                <select name="sync_periodicity" class="wpmf_width_100">
                    <option value="0" <?php selected($sync_periodicity, 0) ?>><?php esc_html_e('Never', 'wpmfAddon') ?></option>
                    <option value="300" <?php selected($sync_periodicity, '300') ?>><?php esc_html_e('5 Mins', 'wpmfAddon') ?></option>
                    <option value="900" <?php selected($sync_periodicity, '900') ?>><?php esc_html_e('15 Mins', 'wpmfAddon') ?></option>
                    <option value="1800" <?php selected($sync_periodicity, '1800') ?>><?php esc_html_e('30 Mins', 'wpmfAddon') ?></option>
                    <option value="3600" <?php selected($sync_periodicity, '3600') ?>><?php esc_html_e('1 Hour', 'wpmfAddon') ?></option>
                    <option value="7200" <?php selected($sync_periodicity, '7200') ?>><?php esc_html_e('2 Hours', 'wpmfAddon') ?></option>
                    <option value="18000" <?php selected($sync_periodicity, '18000') ?>><?php esc_html_e('5 Hours', 'wpmfAddon') ?></option>
                    <option value="43200" <?php selected($sync_periodicity, '43200') ?>><?php esc_html_e('12 Hours', 'wpmfAddon') ?></option>
                    <option value="86400" <?php selected($sync_periodicity, '86400') ?>><?php esc_html_e('24 Hours', 'wpmfAddon') ?></option>
                    <option value="172800" <?php selected($sync_periodicity, '172800') ?>><?php esc_html_e('48 Hours', 'wpmfAddon') ?></option>
                </select>
            </label>
            <p class="description p-lr-20"><?php esc_html_e('Automatic Cloud content synchronization delay. Default is 15 minutes.', 'wpmfAddon'); ?></p>
        </div>
    </div>
    <button type="submit" name="btn_wpmf_save"
            class="btn_wpmf_save ju-button orange-button waves-effect waves-light"><?php esc_html_e('Save Changes', 'wpmfAddon'); ?></button>
</div>