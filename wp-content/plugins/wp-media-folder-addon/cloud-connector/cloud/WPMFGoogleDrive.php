<?php
namespace Joomunited\Cloud\WPMF;

defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Google drive class
 */
class WPMFGoogleDrive extends CloudConnector
{
    /**
     * Init params variable
     *
     * @var array
     */
    private static $params = null;
    /**
     * Init option configuration variable
     *
     * @var string  var_dump(self::$params->text_domain);
     */
    private static $option_config = '_wpmfAddon_cloud_config';
    /**
     * Init connect mode option variable
     *
     * @var string
     */
    private static $connect_mode_option = 'joom_cloudconnector_wpmf_ggd_connect_mode';
    /**
     * Init network variable
     *
     * @var string
     */
    private $network = 'google-drive';
    /**
     * Init id button variable
     *
     * @var string
     */
    private $id_button = 'ggdrive-connect';

    /**
     * Googledrive constructor.
     */
    public function __construct()
    {
        self::$params = parent::$instance;
        add_action('cloudconnector_wpmf_display_ggd_settings', array($this,'displayGGDSettings'));
        add_action('cloudconnector_wpmf_display_ggd_connect_button', array($this,'displayGGDButton'));
        add_action('wp_ajax_cloudconnector_wpmf_ggd_changemode', array($this, 'ggdChangeMode'));
    }

    /**
     * Connect function
     *
     * @return mixed
     */
    public static function connect()
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce verification is made in before function
        $bundle = isset($_GET['bundle']) ? json_decode(self::urlsafeB64Decode($_GET['bundle']), true) : array();

        if (!$bundle || empty($bundle['client_id']) || empty($bundle['client_secret'])) {
            return false;
        }

        $option = get_option(self::$option_config);
        if (!$option) {
            $option = array(
                'googleClientId' => '',
                'googleClientSecret' => '',
                'link_type' => 'public',
                'drive_type' => 'my_drive',
                'googleBaseFolder' => '',
                'googleCredentials' => '',
                'connected' => 1
            );
        }

        $option['googleClientId'] = $bundle['client_id'];
        $option['googleClientSecret'] = $bundle['client_secret'];
        $option['googleCredentials'] = json_encode($bundle);
        $option['connected'] = 1;
        $option['googleBaseFolder'] = self::getBasefolder($bundle, $option);
        update_option(self::$option_config, $option);

        $folder = get_term_by('name', 'Google Drive', WPMF_TAXO);
        if (!empty($folder)) {
            update_term_meta($folder->term_id, 'wpmf_drive_type', 'google_drive');
            update_term_meta($folder->term_id, 'wpmf_drive_root_id', $option['googleBaseFolder']);
        }
        // phpcs:enable
    }

    /**
     * Display connect mode checkbox
     *
     * @return void
     */
    public function displayGGDSettings()
    {
        // phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralDomain -- It is string from object
        $connect_mode_list = array(
            'automatic' => esc_html__('Automatic', self::$params->text_domain),
            'manual' => esc_html__('Manual', self::$params->text_domain)
        );
        $ggd_config = get_option(self::$option_config);
        $config_mode = get_option(self::$connect_mode_option, 'manual');
        if ($config_mode && $config_mode === 'automatic') {
            echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'input[name="googleClientId"]\').parents(\'.ggd-connector-form\').hide();
                        $(\'input[name="googleClientSecret"]\').parents(\'.ggd-connector-form\').hide();
                        $(\'input[name="javaScript_origins"]\').parents(\'.ggd-connector-form\').hide();
                        $(\'input[name="redirect_uris"]\').parents(\'.ggd-connector-form\').hide();
                        $(\'.ggd-connector-button\').hide();
                        $(\'.ggd-ju-connect-message\').show();
                    });
                </script>';

            if (!$ggd_config || empty($ggd_config['googleCredentials'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.ggd-automatic-connect\').addClass(\'ju-visibled\').show();
                        $(\'.ggd-automatic-disconnect\').removeClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            if ($ggd_config && !empty($ggd_config['googleCredentials'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.ggd-automatic-connect\').removeClass(\'ju-visibled\').hide();
                        $(\'.ggd-automatic-disconnect\').addClass(\'ju-visibled\').show();
                    });
                </script>';
            }
        } else {
            if (!$ggd_config || empty($ggd_config['googleCredentials'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.ggd-automatic-connect\').addClass(\'ju-visibled\').hide();
                        $(\'.ggd-automatic-disconnect\').removeClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            if ($ggd_config && !empty($ggd_config['googleCredentials'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.ggd-automatic-connect\').removeClass(\'ju-visibled\').hide();
                        $(\'.ggd-automatic-disconnect\').addClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.ggd-connector-button\').show();
                        $(\'.ggd-ju-connect-message\').hide();
                    });
                </script>';
        }

        if ($this->checkJoomunitedConnected()) {
            $message = '<p>'.esc_html__('The automatic connection mode to Google Drive uses a validated Google app, meaning that you just need a single login to connect your drive.', self::$params->text_domain).'</p>';
            $message .= '<p>'.esc_html__('On the other hand, the manual connection requires that you create your own app on the Google Developer Console.', self::$params->text_domain).'</p>';
        } else {
            $message = '<p>'.esc_html__('The automatic connection mode to Google Drive uses a validated Google app, meaning that you just need a single login to connect your drive.', self::$params->text_domain);
            $message .= '<strong>'.esc_html__(' However, please login first to your JoomUnited account to use this feature.', self::$params->text_domain).'</strong>';
            $message .= esc_html(' You can do that from', self::$params->text_domain).' <a href="'.esc_url(admin_url('options-general.php')).'"> the WordPress settings</a> '.esc_html__('using the same username and password as on the JoomUnited website.', self::$params->text_domain).'</p>';
            $message .= '<p>'.esc_html__('On the other hand, the manual connection requires that you create your own app on the Google Developer Console.', self::$params->text_domain).'</p>';
        }

        echo '<div class="wpmf_width_100 ju-settings-option box-shadow-none m-b-0">';
        echo '<h4>'.esc_html__('Connecting mode', self::$params->text_domain).'</h4>';
        echo '<div class="ggd-mode-radio-field automatic-radio-group">';
        echo '<div class="ju-radio-group">';
        foreach ($connect_mode_list as $k => $v) {
            $checked = (!empty($config_mode) && $config_mode === $k) ? 'checked' : '';
            echo '<label><input type="radio" class="ju-radiobox" name="googleConnectMethod" value="'.esc_html($k).'" '.esc_html($checked).'><span>'.esc_html($v).'</span></label>';
        }
        echo '</div>';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- String is escaped
        echo '<div class="ggd-ju-connect-message ju-connect-message">'.$message.'</div>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Display button connect
     *
     * @return void
     */
    public function displayGGDButton()
    {
        $network = $this->network;
        $id_button = $this->id_button;
        if ($this->checkJoomunitedConnected()) {
            $juChecked = true;
        } else {
            $juChecked = false;
        }
        $fragment = '#google_drive_box';
        $current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . $fragment;
        $link = admin_url('admin-ajax.php') . '?cloudconnector=1&task=connect';
        $link .= '&network=' . esc_html($network);
        $link .= '&plugin_type=' . self::$params->prefix;
        $link .= '&current_backlink=' . self::urlsafeB64Encode($current_url);
        $link .= '&cloudconnect_nonce=' . hash('md5', '_cloudconnect_nonce');

        echo '<a class="ju-button waves-effect waves-light '. (($juChecked) ? '' : 'wpmftippy') .' ggd-automatic-connect '.($juChecked ? 'orange-button' : 'ju-disconnected-autoconnect').'" href="#"
                name="' . esc_html(self::$params->prefix . '_' . $id_button) . '"
                data-wpmftippy="'.esc_html($juChecked ? '' : __('Please login first to your JoomUnited account to use this feature', self::$params->text_domain)).'" 
                id="' . esc_html(self::$params->prefix . '_' . $id_button) . '" 
                data-network="' . esc_html($network) . '" 
                data-link="' . esc_html(self::urlsafeB64Encode($link)) . '" >';
        echo esc_html__('Connect Google Drive', self::$params->text_domain).'</a>';

        echo '<a class="ju-button waves-effect waves-light ggd-automatic-disconnect '.($juChecked ? 'no-background orange-button' : 'ju-disconnected-autoconnect').'" 
                href="'.esc_url(admin_url('options-general.php?page=option-folder&task=wpmf&function=wpmf_gglogout')).'">';
        echo esc_html__('Disconnect Google Drive', self::$params->text_domain).'</a>';
        // phpcs:enable
    }

    /**
     * Set default connect mode when installing
     *
     * @return void
     */
    public static function setDefaultMode()
    {
        if (!get_option(self::$connect_mode_option)) {
            update_option(self::$connect_mode_option, 'automatic');
        }
    }

    /**
     * Change connect mode
     *
     * @return void
     */
    public static function ggdChangeMode()
    {
        check_ajax_referer('_cloudconnector_nonce', 'cloudconnect_nonce');

        if (isset($_POST['value'])) {
            update_option(self::$connect_mode_option, $_POST['value']);
        }
    }

    /**
     * Get base folder id
     *
     * @param array $authenticate Author
     * @param array $option       Option of google drive
     *
     * @return string
     */
    public static function getBasefolder($authenticate, $option)
    {
        require_once plugin_dir_path(self::$params->path)  . 'class/Google/autoload.php';
        $google_client = new \WpmfGoogle_Client();
        $google_client->setClientId($authenticate['client_id']);
        $google_client->setClientSecret($authenticate['client_secret']);
        $google_client->setAccessToken(json_encode($authenticate));
        $google_client->setScopes(array(
            'https://www.googleapis.com/auth/drive',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
            'https://www.googleapis.com/auth/drive.appdata',
            'https://www.googleapis.com/auth/drive.apps.readonly',
            'https://www.googleapis.com/auth/drive.file',
            'https://www.googleapis.com/auth/devstorage.full_control'
        ));

        $check_root_folder = false;
        if (!empty($option['googleBaseFolder'])) {
            $check_root_folder = self::folderExists($google_client, $option['googleBaseFolder']);
        }

        if ($check_root_folder && !empty($option['googleClientId']) && $authenticate['client_id'] === $option['googleClientId']) {
            $googleBaseFolder = $option['googleBaseFolder'];
        } else {
            $googleBaseFolder = self::createFolder($google_client);
        }

        return $googleBaseFolder;
    }

    /**
     * Folder exists
     *
     * @param object $client Googledrive client
     * @param string $id     Folder id
     *
     * @return boolean
     */
    public static function folderExists($client, $id)
    {
        try {
            $service = new \WpmfGoogle_Service_Drive($client);
            $service->files->get($id);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create new folder google drive
     *
     * @param object $client Service
     *
     * @return mixed
     */
    public static function createFolder($client)
    {
        $config = get_option('_wpmfAddon_cloud_config');
        $title = self::$params->name . ' - ' . get_bloginfo('name') . ' - Automatic connect';
        /**
         * Filter to set root cloud folder name for automatic method
         *
         * @param string Folder name
         *
         * @return string
         */
        $title = apply_filters('wpmf_cloud_auto_folder_name', $title);
        try {
            $service = new \WpmfGoogle_Service_Drive($client);
            if (self::isTeamDrives($config)) {
                $drive = new \WpmfGoogle_Service_Drive_Drive();
                $drive->name = $title;
                $fileId = $service->drives->create(time(), $drive);
            } else {
                $file           = new \WpmfGoogle_Service_Drive_DriveFile();
                $file->name    = $title;
                $file->mimeType = 'application/vnd.google-apps.folder';
                $fileId = $service->files->create($file, array('supportsAllDrives' => self::isTeamDrives($config)));
            }
            return $fileId->getId();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Is Shared Drive
     *
     * @param array $configs Configs
     *
     * @return boolean
     */
    public static function isTeamDrives($configs)
    {
        if (!empty($configs['drive_type']) && $configs['drive_type'] === 'team_drive') {
            return true;
        }

        return false;
    }
}
