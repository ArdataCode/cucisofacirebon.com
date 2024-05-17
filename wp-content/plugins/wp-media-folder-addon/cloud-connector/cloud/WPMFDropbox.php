<?php
namespace Joomunited\Cloud\WPMF;

defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Dropbox connector class
 */
class WPMFDropbox extends CloudConnector
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
     * @var string
     */
    private static $option_config = '_wpmfAddon_dropbox_config';
    /**
     * Init connect mode option variable
     *
     * @var string
     */
    private static $connect_mode_option = 'joom_cloudconnector_wpmf_dropbox_connect_mode';
    /**
     * Init network variable
     *
     * @var string
     */
    private $network = 'dropbox';
    /**
     * Init id button variable
     *
     * @var string
     */
    private $id_button = 'dropbox-connect';

    /**
     * Dropbox constructor.
     */
    public function __construct()
    {
        self::$params = parent::$instance;
        add_action('cloudconnector_wpmf_display_dropbox_settings', array($this,'displayDropboxSettings'));
        add_action('cloudconnector_wpmf_display_dropbox_connect_button', array($this,'displayDropboxButton'));
        add_action('wp_ajax_cloudconnector_wpmf_dropbox_changemode', array($this, 'dropboxChangeMode'));
    }

    /**
     * Connect function
     *
     * @return mixed
     */
    public static function connect()
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce verification is made in before function
        $bundle = isset($_GET['bundle']) ? json_decode(self::urlsafeB64Decode($_GET['bundle'])) : array();

        if (empty($bundle->app_key) || empty($bundle->app_secret)) {
            return false;
        }
        $option = get_option(self::$option_config);
        if (!$option) {
            $option = array(
                'link_type' => 'public',
                'dropboxKey' => '',
                'dropboxSecret' => '',
                'first_connected' => '1',
                'dropboxToken' => '',
                'dropboxSyncMethod' => 'sync_page_curl',
                'dropboxAuthor' => ''
            );
        }

        $option['dropboxKey'] = $bundle->app_key;
        $option['dropboxSecret'] = $bundle->app_secret;
        $option['dropboxAuthor'] = get_current_user_id();
        $option['dropboxToken'] = (!empty($bundle->accessToken) ? $bundle->accessToken : '');
        $option['first_connected'] = 1;
        update_option(self::$option_config, $option);
        // phpcs:enable
    }

    /**
     * Display connect mode checkbox
     *
     * @return void
     */
    public function displayDropboxSettings()
    {
        // phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralDomain -- It is string from object
        $connect_mode_list = array(
            'automatic' => esc_html__('Automatic', self::$params->text_domain),
            'manual' => esc_html__('Manual', self::$params->text_domain)
        );
        $dropbox_config = get_option(self::$option_config);
        $config_mode = get_option(self::$connect_mode_option, 'manual');

        if ($config_mode && $config_mode === 'automatic') {
            echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'input[name="dropboxKey"]\').parents(\'.dropbox-connector-form\').hide();
                        $(\'input[name="dropboxSecret"]\').parents(\'.dropbox-connector-form\').hide();
                        $(\'input[name="dropboxAuthor"]\').parents(\'.dropbox-connector-form\').hide();
                        $(\'.dropbox-connector-button\').hide();
                        $(\'.dropbox-ju-connect-message\').show();
                    });
                </script>';

            if (!$dropbox_config || empty($dropbox_config['dropboxToken'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.dropbox-automatic-connect\').addClass(\'ju-visibled\').show();
                        $(\'.dropbox-automatic-disconnect\').removeClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            if ($dropbox_config && !empty($dropbox_config['dropboxToken'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.dropbox-automatic-connect\').removeClass(\'ju-visibled\').hide();
                        $(\'.dropbox-automatic-disconnect\').addClass(\'ju-visibled\').show();
                    });
                </script>';
            }
        } else {
            if (!$dropbox_config || empty($dropbox_config['dropboxToken'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.dropbox-automatic-connect\').addClass(\'ju-visibled\').hide();
                        $(\'.dropbox-automatic-disconnect\').removeClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            if ($dropbox_config && !empty($dropbox_config['dropboxToken'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.dropbox-automatic-connect\').removeClass(\'ju-visibled\').hide();
                        $(\'.dropbox-automatic-disconnect\').addClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.dropbox-connector-button\').show();
                        $(\'.dropbox-ju-connect-message\').hide();
                    });
                </script>';
        }

        if ($this->checkJoomunitedConnected()) {
            $message = '<p>'.esc_html__('The automatic connection mode to Dropbox uses a validated Dropbox app, meaning that you just need a single login to connect your drive.', self::$params->text_domain).'</p>';
            $message .= '<p>'.esc_html__('On the other hand, the manual connection requires that you create your own app on the Dropbox Developer Console.', self::$params->text_domain).'</p>';
        } else {
            $message = '<p>'.esc_html__('The automatic connection mode to Dropbox uses a validated Dropbox app, meaning that you just need a single login to connect your dropbox.', self::$params->text_domain);
            $message .= '<strong>'.esc_html__(' However, please login first to your JoomUnited account to use this feature.', self::$params->text_domain).'</strong>';
            $message .= esc_html(' You can do that from', self::$params->text_domain).' <a href="'.esc_url(admin_url('options-general.php')).'"> the WordPress settings</a> '.esc_html__('using the same username and password as on the JoomUnited website.', self::$params->text_domain).'</p>';
            $message .= '<p>'.esc_html__('On the other hand, the manual connection requires that you create your own app on the Dropbox Developer Console.', self::$params->text_domain).'</p>';
        }

        echo '<div class="wpmf_width_100 ju-settings-option box-shadow-none m-b-0">';
        echo '<h4>'.esc_html__('Connecting mode', self::$params->text_domain).'</h4>';
        echo '<div class="dropbox-mode-radio-field automatic-radio-group">';
        echo '<div class="ju-radio-group">';
        foreach ($connect_mode_list as $k => $v) {
            $checked = (!empty($config_mode) && $config_mode === $k) ? 'checked' : '';
            echo '<label><input type="radio" class="ju-radiobox" name="dropboxConnectMethod" value="'.esc_html($k).'" '.esc_attr($checked).'><span>'.esc_html($v).'</span></label>';
        }
        echo '</div>';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- String is escaped
        echo '<div class="dropbox-ju-connect-message ju-connect-message">'.$message.'</div>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Display button connect
     *
     * @return void
     */
    public function displayDropboxButton()
    {
        $network = $this->network;
        $id_button = $this->id_button;
        if ($this->checkJoomunitedConnected()) {
            $juChecked = true;
        } else {
            $juChecked = false;
        }
        $fragment = '#dropbox_box';
        $current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . $fragment;
        $link = admin_url('admin-ajax.php') . '?cloudconnector=1&task=connect';
        $link .= '&network=' . esc_html($network);
        $link .= '&plugin_type=' . self::$params->prefix;
        $link .= '&current_backlink=' . self::urlsafeB64Encode($current_url);
        $link .= '&cloudconnect_nonce=' . hash('md5', '_cloudconnect_nonce');

        echo '<a class="ju-button waves-effect waves-light '. (($juChecked) ? '' : 'wpmftippy') .' dropbox-automatic-connect '.($juChecked ? 'orange-button' : 'ju-disconnected-autoconnect').'" href="#"
                name="' . esc_html(self::$params->prefix . '_' . $id_button) . '" 
                id="' . esc_html(self::$params->prefix . '_' . $id_button) . '" 
                data-network="' . esc_html($network) . '" 
                data-wpmftippy="'.esc_html($juChecked ? '' : __('Please login first to your JoomUnited account to use this feature', self::$params->text_domain)).'"
                data-link="' . esc_html(self::urlsafeB64Encode($link)) . '" >';
        echo esc_html__('Connect Dropbox', self::$params->text_domain).'</a>';

        echo '<a class="ju-button waves-effect waves-light dropbox-automatic-disconnect '.($juChecked ? 'no-background orange-button' : 'ju-disconnected-autoconnect').'" 
                href="'.esc_url(admin_url('options-general.php?page=option-folder&task=wpmf&function=wpmf_dropboxlogout')).'" data-network="' . esc_html($network) . '">';
        echo esc_html__('Disconnect Dropbox', self::$params->text_domain).'</a>';

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
    public static function dropboxChangeMode()
    {
        check_ajax_referer('_cloudconnector_nonce', 'cloudconnect_nonce');

        if (isset($_POST['value'])) {
            update_option(self::$connect_mode_option, $_POST['value']);
        }
    }
}
