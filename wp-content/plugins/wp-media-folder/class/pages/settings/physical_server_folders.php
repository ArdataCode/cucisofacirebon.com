<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
include_once WP_MEDIA_FOLDER_PLUGIN_DIR . 'class/physical-folder' . DIRECTORY_SEPARATOR . 'helper.php';
global $wpdb;
// phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared -- No variable needs to be prepared
$folder_fields = JUQueueHelper::getDbColumns(true);
$tables = wpmfGetOption('wp-media-folder-tables');
$folder_options = get_option('wpmf_queue_options');
if (empty($folder_options)) {
    $folder_options = array(
        'enable_physical_folders' => 0,
        'auto_detect_tables' => 1,
        'replace_relative_paths' => (get_option('uploads_use_yearmonth_folders')) ? 1 : 0,
        'search_full_database' => 0,
        'mode_debug' => 0
    );
}
?>
<div id="physical_server_folders" class="tab-content">
    <div class="content-box">
        <div id="wpmf-disclaimer">
            <h2><?php esc_html_e('Please read this disclaimer first', 'wpmf') ?></h2>
            <p>
                <?php esc_html_e('This feature will move WordPress media inside real folders and edit filenames.
                In its default version it will allow you to edit the file path and name through the image edition in the
                WordPress default media manager.', 'wpmf') ?>
            </p>
            <h2><?php esc_html_e('Important restrictions', 'wpmf') ?></h2>
            <p>
                <?php esc_html_e('WordPress has not been designed to allow changing the path of files. You should use this feature only if
                you
                really need it but note that:', 'wpmf') ?>
            </p>
            <ul>
                <li>
                    <strong><?php esc_html_e('Moving images won\'t help your SEO', 'wpmf') ?></strong> <?php esc_html_e('like other would expect you to believe, file name and Alt information are far more important.', 'wpmf') ?>
                </li>
                <li>
                    <?php esc_html_e('Even if the plugin will try its best to find and replace URLs of files in your database, depending on how other plugins deals with URLs, it may fail', 'wpmf') ?>
                </li>
                <li><?php esc_html_e('The process of replacing requires strong server performances and the more content you have the more powerful your server has to be', 'wpmf') ?>
                </li>
                <li><strong><?php esc_html_e('Always make backups of your website before any modification', 'wpmf') ?></strong></li>
            </ul>
            <h2><?php esc_html_e('Important features restrictions', 'wpmf') ?></h2>
            <p><?php esc_html_e('Instead of using WordPress custom taxonomy, create physical folders for your media. This setting needs to activated only by experienced users as it breaks compatibility with other plugin features like', 'wpmf') ?></p>
            <ul>
                <li><?php esc_html_e('Keeping link to media when renaming folder and files', 'wpmf') ?></li>
                <li><?php esc_html_e('Large folder move', 'wpmf') ?></li>
                <li><?php esc_html_e('Media and folder import and synchronization', 'wpmf') ?></li>
                <li><?php esc_html_e('Media multiple folder', 'wpmf') ?></li>
                <li><?php esc_html_e('Undo modification...', 'wpmf') ?></li>
            </ul>
        </div>

        <div class="ju-settings-option">
            <div class="wpmf_row_full">
                <input type="hidden" name="wp-media-folder-options[enable_physical_folders]" value="0">
                <label data-wpmftippy="<?php esc_html_e('WARNING: Instead of using WordPress custom taxonomy, create physical folders for your media. Some features of the plugin will be disabled as they\'re not compatible with the setting! (Read more above)', 'wpmf'); ?>"
                       class="ju-setting-label text"><?php esc_html_e('Enable physical folders', 'wpmf') ?></label>
                <div class="ju-switch-button">
                    <label class="switch">
                        <input type="checkbox" name="wp-media-folder-options[enable_physical_folders]"
                               value="1"
                            <?php
                            echo (!empty($folder_options['enable_physical_folders'])) ? 'checked' : '';
                            ?>
                        >
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
        </div>


        <div class="ju-settings-option wpmf_right m-r-0">
            <div class="wpmf_row_full">
                <input type="hidden" name="wp-media-folder-options[auto_detect_tables]" value="0">
                <label data-wpmftippy="<?php esc_html_e('The plugin will auto select the tables and columns where the replacement of attachments (media) URLs should be proceeded. This is the better option if you want to make sure to not lose replacements. Disable this option if you know what you’re doing and want to select custom data set  to optimize the process.', 'wpmf'); ?>"
                       class="ju-setting-label text"><?php esc_html_e('Detect media tables to replace content', 'wpmf') ?></label>
                <div class="ju-switch-button">
                    <label class="switch">
                        <input type="checkbox" name="wp-media-folder-options[auto_detect_tables]"
                               value="1"
                            <?php
                            if (!empty($folder_options['auto_detect_tables'])) {
                                echo 'checked';
                            }
                            ?>
                        >
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
        </div>

        <div id="table_replace"
             style="<?php echo !empty($folder_options['auto_detect_tables']) ? 'display:none' : ''; ?>">
            <p>
                <strong><?php esc_html_e('Tables to replace content into', 'wpmf'); ?></strong><br/>
                <?php esc_html_e('Select the tables which you want the images url to be replaced into', 'wpmf'); ?>
            </p>
            <div class="container">
                <?php
                $last_table = '';
                foreach ($folder_fields as $field) :
                    if ($last_table !== $field->TABLE_NAME) :
                        if ($last_table !== '') {
                            echo '</div>';
                        }
                        $last_table = $field->TABLE_NAME; ?>
                <div class="database-table"><h2><?php echo esc_html($last_table); ?></h2>
                    <?php endif; ?>
                    <div class="database-field">
                        <span><?php echo esc_html($field->COLUMN_NAME); ?></span>
                        <span><input
                                    type="checkbox"
                                    name="wp-media-folder-tables[<?php echo esc_html($last_table); ?>][<?php echo esc_html($field->COLUMN_NAME); ?>]"
                                <?php echo isset($tables[$last_table][$field->COLUMN_NAME]) ? 'checked' : ''; ?>
                        /></span>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="ju-settings-option wpmf_width_100">
            <div class="wpmf_row_full">
                <label data-wpmftippy="<?php esc_html_e('Additionally to the folder creation as physical folder, all the current folder structure and media will be transformed as WordPress physical folders', 'wpmf'); ?>"
                       class="ju-setting-label text"><?php esc_html_e('Transform current media folders', 'wpmf'); ?></label>
                <button id="sync_wpmf"
                        class="ju-button no-background orange-button waves-effect waves-light" <?php echo defined('WPMF_TAXO') ? '' : ('disabled="disabled" title="' . esc_html__('This functionnality requires WP Media Folder from Joomunited plugin', 'wpmf') . '"'); ?>>
                    <?php esc_html_e('Move existing media', 'wpmf'); ?>
                </button>
                <p id="sync_wpmf_doing"
                      style="display: none"><?php esc_html_e('Media will be moved asynchronously as a background task, please activate the top status bar to see the progression', 'wpmf'); ?></p>
            </div>
        </div>

        <div class="ju-settings-option full_search"
             style="<?php echo empty($folder_options['auto_detect_tables']) ? 'display:none' : ''; ?>">
            <div class="wpmf_row_full">
                <input type="hidden" name="wp-media-folder-options[search_full_database]" value="0">
                <label data-wpmftippy="<?php esc_html_e('If checked, the plugin will not only replace content in your wordpress tables but in all the tables it will find in the database. It could be useful if you use your attachments links in another cms or custom script. If you don\'t specifically need it, leave this option unchecked.', 'wpmf'); ?>"
                       class="ju-setting-label text"><?php printf(esc_html__('Search into full database instead of only "%s" prefixed tables', 'wpmf'), esc_html($wpdb->prefix)) ?></label>
                <div class="ju-switch-button">
                    <label class="switch">
                        <input type="checkbox" name="wp-media-folder-options[search_full_database]"
                               value="1"
                            <?php
                            if (!empty($folder_options['search_full_database'])) {
                                echo 'checked';
                            }
                            ?>
                        >
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
        </div>

        <div class="ju-settings-option full_search wpmf_right m-r-0"
             style="<?php echo empty($folder_options['auto_detect_tables']) ? 'display:none' : ''; ?>">
            <div class="wpmf_row_full">
                <input type="hidden" name="wp-media-folder-options[replace_relative_paths]" value="0">
                <label data-wpmftippy="<?php esc_html_e('By default WordPress uses absolutes urls, but some plugins may use relative path. If checked, the plugin will try to replace also relative path in database, instead of only absolute urls. Replacements in database may fail in particular cases (files with the same name) if this option is used while you\'re not using the default year/month upload folders option in WordPress settings.', 'wpmf'); ?>"
                       class="ju-setting-label text"><?php esc_html_e('Replace relative paths in database', 'wpmf') ?></label>
                <div class="ju-switch-button">
                    <label class="switch">
                        <input type="checkbox" name="wp-media-folder-options[replace_relative_paths]"
                               value="1"
                            <?php
                            if (!empty($folder_options['replace_relative_paths'])) {
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
                <input type="hidden" name="wp-media-folder-options[mode_debug]" value="0">
                <label data-wpmftippy="<?php esc_html_e('When enabled, all actions made by the plugin will be stored into a log file in the plugin folder', 'wpmf'); ?>"
                       class="ju-setting-label text"><?php esc_html_e('Mode debug activated', 'wpmf') ?></label>
                <div class="ju-switch-button">
                    <label class="switch">
                        <input type="checkbox" name="wp-media-folder-options[mode_debug]"
                               value="1"
                            <?php
                            if (!empty($folder_options['mode_debug'])) {
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