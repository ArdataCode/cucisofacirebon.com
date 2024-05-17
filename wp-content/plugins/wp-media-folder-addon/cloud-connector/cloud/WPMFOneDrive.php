<?php
namespace Joomunited\Cloud\WPMF;

defined('ABSPATH') || die('No direct script access allowed!');

use Joomunited\WPFramework\v1_0_5\Utilities;
use GuzzleHttp\Client as GuzzleHttpClient;
use Krizalys\Onedrive\Client as Client;
use Krizalys\Onedrive\Exception\ConflictException;
use Microsoft\Graph\Exception\GraphException;
use Microsoft\Graph\Graph;
use Krizalys\Onedrive\Proxy\FileProxy;
use Microsoft\Graph\Model\DriveItem;
use Microsoft\Graph\Model;
use Microsoft\Graph\Model\UploadSession;
use Krizalys\Onedrive\Constant\ConflictBehavior;
use Krizalys\Onedrive\Constant\AccessTokenStatus;

/**
 * Onedrive connector class
 */
class WPMFOneDrive extends CloudConnector
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
    private static $option_config = '_wpmfAddon_onedrive_config';
    /**
     * Init connect mode option variable
     *
     * @var string
     */
    private static $connect_mode_option = 'joom_cloudconnector_wpmf_onedrive_connect_mode';
    /**
     * Init network variable
     *
     * @var string
     */
    private $network = 'one-drive';
    /**
     * Init id button variable
     *
     * @var string
     */
    private $id_button = 'onedrive-connect';

    /**
     * Onedrive constructor.
     */
    public function __construct()
    {
        self::$params = parent::$instance;
        add_action('cloudconnector_wpmf_display_onedrive_settings', array($this,'displayODSettings'));
        add_action('cloudconnector_wpmf_display_onedrive_connect_button', array($this,'displayODButton'));
        add_action('wp_ajax_cloudconnector_wpmf_onedrive_changemode', array($this, 'onedriveChangeMode'));
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

        if (empty($bundle->onedriveKey) || empty($bundle->onedriveSecret)) {
            return false;
        }
        $option = get_option(self::$option_config);
        if (!$option) {
            $option = array(
                'link_type' => 'public',
                'OneDriveClientId' => '',
                'OneDriveClientSecret' => '',
                'state' => array(),
                'onedriveBaseFolder' => array(),
                'connected' => 1,
            );
        }

        $option['OneDriveClientId'] = $bundle->onedriveKey;
        $option['OneDriveClientSecret'] = $bundle->onedriveSecret;
        $option['connected'] = 1;
        $option['state'] = (!empty($bundle->onedriveState) ? $bundle->onedriveState : array());
        $option['onedriveBaseFolder'] = self::getBasefolder($option);
        update_option(self::$option_config, $option);
        // phpcs:enable
    }

    /**
     * Display connect mode checkbox
     *
     * @return void
     */
    public function displayODSettings()
    {
        // phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralDomain -- It is string from object
        $connect_mode_list = array(
            'automatic' => esc_html__('Automatic', self::$params->text_domain),
            'manual' => esc_html__('Manual', self::$params->text_domain)
        );
        $onedrive_config = get_option(self::$option_config);
        $config_mode = get_option(self::$connect_mode_option, 'manual');

        if ($config_mode && $config_mode === 'automatic') {
            echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'input[name="OneDriveClientId"]\').parents(\'.od-connector-form\').hide();
                        $(\'input[name="OneDriveClientSecret"]\').parents(\'.od-connector-form\').hide();
                        $(\'input[name="redirect_uris"]\').parents(\'.od-connector-form\').hide();
                        $(\'.od-connector-button\').hide();
                        $(\'.od-ju-connect-message\').show();
                    });
                </script>';

            if (!$onedrive_config || empty($onedrive_config['connected'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.onedrive-automatic-connect\').addClass(\'ju-visibled\').show();
                        $(\'.od-audisconnect.wpmf_onedrive_logout\').removeClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            if ($onedrive_config && !empty($onedrive_config['connected'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.onedrive-automatic-connect\').removeClass(\'ju-visibled\').hide();
                        $(\'.od-audisconnect.wpmf_onedrive_logout\').addClass(\'ju-visibled\').show();
                    });
                </script>';
            }
        } else {
            if (!$onedrive_config || empty($onedrive_config['connected'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.onedrive-automatic-connect\').addClass(\'ju-visibled\').hide();
                        $(\'.od-audisconnect.wpmf_onedrive_logout\').removeClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            if ($onedrive_config && !empty($onedrive_config['connected'])) {
                echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.onedrive-automatic-connect\').removeClass(\'ju-visibled\').hide();
                        $(\'.od-audisconnect.wpmf_onedrive_logout\').addClass(\'ju-visibled\').hide();
                    });
                </script>';
            }

            echo '<script async type="text/javascript">
                    jQuery(document).ready(function($) {
                        $(\'.od-connector-button\').show();
                        $(\'.od-ju-connect-message\').hide();
                    });
                </script>';
        }

        if ($this->checkJoomunitedConnected()) {
            $message = '<p>'.esc_html__('The automatic connection mode to OneDrive uses a validated Microsoft app, meaning that you just need a single login to connect your drive.', self::$params->text_domain).'</p>';
            $message .= '<p>'.esc_html__('On the other hand, the manual connection requires that you create your own app on the OneDrive Developer Console.', self::$params->text_domain).'</p>';
        } else {
            $message = '<p>'.esc_html__('The automatic connection mode to OneDrive uses a validated Microsoft app, meaning that you just need a single login to connect your drive.', self::$params->text_domain);
            $message .= '<strong>'.esc_html__(' However, please login first to your JoomUnited account to use this feature.', self::$params->text_domain).'</strong>';
            $message .= esc_html(' You can do that from', self::$params->text_domain).' <a href="'.esc_url(admin_url('options-general.php')).'"> the WordPress settings</a> '.esc_html__('using the same username and password as on the JoomUnited website.', self::$params->text_domain).'</p>';
            $message .= '<p>'.esc_html__('On the other hand, the manual connection requires that you create your own app on the OneDrive Developer Console.', self::$params->text_domain).'</p>';
        }

        echo '<div class="wpmf_width_100 ju-settings-option box-shadow-none m-b-0">';
        echo '<h4>'.esc_html__('Connecting mode', self::$params->text_domain).'</h4>';
        echo '<div class="od-mode-radio-field automatic-radio-group">';
        echo '<div class="ju-radio-group">';
        foreach ($connect_mode_list as $k => $v) {
            $checked = (!empty($config_mode) && $config_mode === $k) ? 'checked' : '';
            echo '<label><input type="radio" class="ju-radiobox" name="onedriveConnectMethod" value="'.esc_html($k).'" '.esc_attr($checked).'><span>'.esc_html($v).'</span></label>';
        }
        echo '</div>';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- String is escaped
        echo '<div class="od-ju-connect-message ju-connect-message">'.$message.'</div>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Display button connect
     *
     * @return void
     */
    public function displayODButton()
    {
        $network = $this->network;
        $id_button = $this->id_button;
        if ($this->checkJoomunitedConnected()) {
            $juChecked = true;
        } else {
            $juChecked = false;
        }
        $fragment = '#one_drive_box';
        $current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . $fragment;
        $link = admin_url('admin-ajax.php') . '?cloudconnector=1&task=connect';
        $link .= '&network=' . esc_html($network);
        $link .= '&plugin_type=' . self::$params->prefix;
        $link .= '&current_backlink=' . self::urlsafeB64Encode($current_url);
        $link .= '&cloudconnect_nonce=' . hash('md5', '_cloudconnect_nonce');

        echo '<a class="ju-button waves-effect waves-light '. (($juChecked) ? '' : 'wpmftippy') .' onedrive-automatic-connect '.($juChecked ? 'orange-button' : 'ju-disconnected-autoconnect').'" href="#"
                name="' . esc_html(self::$params->prefix . '_' . $id_button) . '" 
                id="' . esc_html(self::$params->prefix . '_' . $id_button) . '" 
                data-network="' . esc_html($network) . '" 
                data-wpmftippy="'.esc_html($juChecked ? '' : __('Please login first to your JoomUnited account to use this feature', self::$params->text_domain)).'"
                data-link="' . esc_html(self::urlsafeB64Encode($link)) . '" >';
        echo esc_html__('Connect OneDrive', self::$params->text_domain).'</a>';

        echo '<a class="ju-button waves-effect waves-light od-audisconnect wpmf_onedrive_logout '.($juChecked ? 'no-background orange-button' : 'ju-disconnected-autoconnect').'" 
                href="" data-network="' . esc_html($network) . '">';
        echo esc_html__('Disconnect OneDrive', self::$params->text_domain).'</a>';
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
    public static function onedriveChangeMode()
    {
        check_ajax_referer('_cloudconnector_nonce', 'cloudconnect_nonce');

        if (isset($_POST['value'])) {
            update_option(self::$connect_mode_option, $_POST['value']);
        }
    }

    /**
     * Get base folder id
     *
     * @param array $option Option config
     *
     * @return array
     */
    public static function getBasefolder($option)
    {
        try {
            require_once plugin_dir_path(self::$params->path)  . 'class/sdk/vendor/autoload.php';
            $client = new Client(
                $option['OneDriveClientId'],
                new Graph(),
                new GuzzleHttpClient(),
                \Krizalys\Onedrive\Onedrive::buildServiceDefinition(),
                array(
                    'state' => isset($option['state']) && !empty($option['state']) ? $option['state'] : array()
                )
            );

            $blogname = trim(str_replace(array(':', '~', '"', '%', '&', '*', '<', '>', '?', '/', '\\', '{', '|', '}'), '', get_bloginfo('name')));

            // Fix onedrive bug, last folder name can not be a dot
            if (substr($blogname, -1) === '.') {
                $blogname = substr($blogname, strlen($blogname) - 1);
            }

            if ($blogname === '') {
                $folderName = 'WP Media Folder Automatic';
            } else {
                $folderName = 'WP Media Folder Automatic - ' . $blogname;
            }
            $folderName = preg_replace('@["*:<>?/\\|]@', '', $folderName);
            /**
             * Filter to set root cloud folder name for automatic method
             *
             * @param string Folder name
             *
             * @return string
             *
             * @ignore Hook already documented
             */
            $folderName = apply_filters('wpmf_cloud_auto_folder_name', $folderName);
            $graph = new Graph();
            $graph->setAccessToken($client->getState()->token->data->access_token);
            $basefolder = array();

            if (empty($option['onedriveBaseFolder'])) {
                try {
                    $root = $client->getRoot()->createFolder($folderName);
                    $basefolder = array(
                        'id' => $root->id,
                        'name' => $root->name
                    );
                } catch (ConflictException $e) {
                    $root = $client->getDriveItemByPath('/' . $folderName);
                    $basefolder = array(
                        'id' => $root->id,
                        'name' => $root->name
                    );
                }
            } else {
                try {
                    $root = $graph
                        ->createRequest('GET', '/me/drive/items/' . $option['onedriveBaseFolder']['id'])
                        ->setReturnType(Model\DriveItem::class) // phpcs:ignore PHPCompatibility.Constants.NewMagicClassConstant.Found -- Use to sets the return type of the response object
                        ->execute();
                } catch (\Exception $ex) {
                    $root = $client->getRoot()->createFolder($folderName);
                    $basefolder = array(
                        'id' => $root->id,
                        'name' => $root->name
                    );
                }

                if (!is_wp_error($root)) {
                    $basefolder = array(
                        'id' => $root->id,
                        'name' => $root->name
                    );
                }
            }

            return $basefolder;
        } catch (\Exception $ex) {
            return array();
        }
    }
}
