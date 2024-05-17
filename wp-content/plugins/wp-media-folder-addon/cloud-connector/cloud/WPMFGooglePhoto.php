<?php
namespace Joomunited\Cloud\WPMF;

defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Google drive class
 */
class WPMFGooglePhoto extends CloudConnector
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
    private static $option_config = '_wpmfAddon_google_photo_config';
    /**
     * Init connect mode option variable
     *
     * @var string
     */
    private static $connect_mode_option = 'joom_cloudconnector_wpmf_gpt_connect_mode';
    /**
     * Init network variable
     *
     * @var string
     */
    private $network = 'google-photo';
    /**
     * Init id button variable
     *
     * @var string
     */
    private $id_button = 'ggphoto-connect';

    /**
     * Googledrive constructor.
     */
    public function __construct()
    {
        self::$params = parent::$instance;
        add_action('cloudconnector_wpmf_display_gpt_settings', array($this,'displayGPTSettings'));
        add_action('cloudconnector_wpmf_display_gpt_connect_button', array($this,'displayGPTButton'));
        add_action('wp_ajax_cloudconnector_wpmf_gpt_changemode', array($this, 'gptChangeMode'));
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
                'link_type' => 'private',
                'googleCredentials' => '',
                'connected' => 1,
                'token_created' => '',
                'token_expires' => ''
            );
        }

        $option['googleClientId'] = $bundle['client_id'];
        $option['googleClientSecret'] = $bundle['client_secret'];
        $option['googleCredentials'] = json_encode($bundle);
        $option['connected'] = 1;
        $option['token_expires'] = isset($bundle['expires_in']) ? $bundle['expires_in'] : '';
        $option['token_created'] = time();
        update_option(self::$option_config, $option);
        // phpcs:enable
    }

    /**
     * Display connect mode checkbox
     *
     * @return void
     */
    public function displayGPTSettings()
    {
        // phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralDomain -- It is string from object
        $connect_mode_list = array(
            'automatic' => esc_html__('Automatic', self::$params->text_domain),
            'manual' => esc_html__('Manual', self::$params->text_domain)
        );
        $gpt_config = get_option(self::$option_config);
        $config_mode = get_option(self::$connect_mode_option, 'manual');
        if ($config_mode && $config_mode === 'automatic') {
            echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'input[name="googlePhotoClientId"]\').parents(\'.gpt-connector-form\').hide();
                        $(\'input[name="googlePhotoClientSecret"]\').parents(\'.gpt-connector-form\').hide();
                        $(\'input[name="javaScript_origins"]\').parents(\'.gpt-connector-form\').hide();
                        $(\'input[name="redirect_uris"]\').parents(\'.gpt-connector-form\').hide();
                        $(\'.gpt-connector-button\').hide();
                        $(\'.gpt-ju-connect-message\').show();
                    });
                </script>';

            if (!$gpt_config || empty($gpt_config['googleCredentials'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.gpt-automatic-connect\').addClass(\'ju-visibled\').show();
                        $(\'.gpt-automatic-disconnect\').removeClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            if ($gpt_config && !empty($gpt_config['googleCredentials'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.gpt-automatic-connect\').removeClass(\'ju-visibled\').hide();
                        $(\'.gpt-automatic-disconnect\').addClass(\'ju-visibled\').show();
                    });
                </script>';
            }
        } else {
            if (!$gpt_config || empty($gpt_config['googleCredentials'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.gpt-automatic-connect\').addClass(\'ju-visibled\').hide();
                        $(\'.gpt-automatic-disconnect\').removeClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            if ($gpt_config && !empty($gpt_config['googleCredentials'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.gpt-automatic-connect\').removeClass(\'ju-visibled\').hide();
                        $(\'.gpt-automatic-disconnect\').addClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.gpt-connector-button\').show();
                        $(\'.gpt-ju-connect-message\').hide();
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
        echo '<div class="gpt-mode-radio-field automatic-radio-group">';
        echo '<div class="ju-radio-group">';
        foreach ($connect_mode_list as $k => $v) {
            $checked = (!empty($config_mode) && $config_mode === $k) ? 'checked' : '';
            echo '<label><input type="radio" class="ju-radiobox" name="googlePhotoConnectMethod" value="'.esc_html($k).'" '.esc_html($checked).'><span>'.esc_html($v).'</span></label>';
        }
        echo '</div>';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- String is escaped
        echo '<div class="gpt-ju-connect-message ju-connect-message">'.$message.'</div>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Display button connect
     *
     * @return void
     */
    public function displayGPTButton()
    {
        $network = $this->network;
        $id_button = $this->id_button;
        if ($this->checkJoomunitedConnected()) {
            $juChecked = true;
        } else {
            $juChecked = false;
        }
        $fragment = '#google_photo';
        $current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . $fragment;
        $link = admin_url('admin-ajax.php') . '?cloudconnector=1&task=connect';
        $link .= '&network=' . esc_html($network);
        $link .= '&plugin_type=' . self::$params->prefix;
        $link .= '&current_backlink=' . self::urlsafeB64Encode($current_url);
        $link .= '&cloudconnect_nonce=' . hash('md5', '_cloudconnect_nonce');

        echo '<a class="ju-button waves-effect waves-light '. (($juChecked) ? '' : 'wpmftippy') .' gpt-automatic-connect '.($juChecked ? 'orange-button' : 'ju-disconnected-autoconnect').'" href="#"
                name="' . esc_html(self::$params->prefix . '_' . $id_button) . '"
                data-wpmftippy="'.esc_html($juChecked ? '' : __('Please login first to your JoomUnited account to use this feature', self::$params->text_domain)).'" 
                id="' . esc_html(self::$params->prefix . '_' . $id_button) . '" 
                data-network="' . esc_html($network) . '" 
                data-link="' . esc_html(self::urlsafeB64Encode($link)) . '" >';
        echo esc_html__('Connect Google Photo', self::$params->text_domain).'</a>';

        echo '<a class="ju-button waves-effect waves-light gpt-automatic-disconnect '.($juChecked ? 'no-background orange-button' : 'ju-disconnected-autoconnect').'" 
                href="'.esc_url(admin_url('options-general.php?page=option-folder&task=wpmf&function=wpmf_google_photo_logout')).'">';
        echo esc_html__('Disconnect Google Photo', self::$params->text_domain).'</a>';
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
    public static function gptChangeMode()
    {
        check_ajax_referer('_cloudconnector_nonce', 'cloudconnect_nonce');

        if (isset($_POST['value'])) {
            update_option(self::$connect_mode_option, $_POST['value']);
        }
    }
}
