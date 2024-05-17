<?php
namespace Joomunited\Cloud\WPMF;

defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Class CloudConnector
 */
class CloudConnector
{
    /**
     * Generated instances of application
     *
     * @var array
     */
    public static $instance = array();
    /**
     * Init path parameter
     *
     * @var string
     */
    public $path = '';
    /**
     * Init prefix of plugin parameter
     *
     * @var string
     */
    public $prefix = '';
    /**
     * Init name of plugin parameter
     *
     * @var string
     */
    public $name = '';
    /**
     * Init traslation text parameter
     *
     * @var string
     */
    public $text_domain;
    /**
     * Init cloud server link parameter
     *
     * @var string
     */
    public $connector = 'https://connector.joomunited.com/cloudconnector/';

    /**
     * Init function
     *
     * @return mixed
     */
    public function init()
    {
        if (!is_admin()) {
            return false;
        }
        $this->initCloud();
        $this->initHooks();
        $this->executeAction();
    }

    /**
     * Init cloud function
     *
     * @return void
     */
    public function initCloud()
    {
        require_once 'cloud/WPMFGoogleDrive.php';
        new WPMFGoogleDrive();
        require_once 'cloud/WPMFOneDrive.php';
        new WPMFOneDrive();
        require_once 'cloud/WPMFOneDriveBusiness.php';
        new WPMFOneDriveBusiness();
        require_once 'cloud/WPMFDropbox.php';
        new WPMFDropbox();
        require_once 'cloud/WPMFGooglePhoto.php';
        new WPMFGooglePhoto();
    }

    /**
     * Initializes the hooks
     *
     * @return void
     */
    protected function initHooks()
    {
        add_action('admin_enqueue_scripts', array($this, 'loadScripts'), 15);
        register_activation_hook($this->path, array($this, 'wpmfCloudInstall'));
    }

    /**
     * Execute function
     *
     * @return mixed
     */
    public function executeAction()
    {
        if (!(defined('DOING_AJAX') && DOING_AJAX)) {
            return false;
        }
        // phpcs:disable WordPress.Security.NonceVerification.Recommended, PHPCompatibility.FunctionUse.NewFunctions.hash_equalsFound -- Hash equals function is defined, Nonce verification is made in after
        if (empty($_GET['cloudconnector']) ||
            empty($_GET['cloudconnect_nonce']) ||
            !hash_equals($_GET['cloudconnect_nonce'], hash('md5', '_cloudconnect_nonce'))) {
            return false;
        }

        if (isset($_GET['plugin_type']) && $_GET['plugin_type'] !== $this->prefix) {
            return false;
        }

        if (isset($_GET['task']) && $_GET['task'] === 'connect' && isset($_GET['network'])) {
            switch ($_GET['network']) {
                case 'google-drive':
                    WPMFGoogleDrive::connect();
                    break;
                case 'one-drive':
                    WPMFOneDrive::connect();
                    break;
                case 'one-drive-business':
                    WPMFOneDriveBusiness::connect();
                    break;
                case 'dropbox':
                    WPMFDropbox::connect();
                    break;
                case 'google-photo':
                    WPMFGooglePhoto::connect();
                    break;
            }

            $this->closeApp();
        }
        // phpcs:enable
    }

    /**
     * Load script
     *
     * @return void
     */
    public function loadScripts()
    {
        global $current_screen;
        if (!empty($current_screen->base) && $current_screen->base === 'settings_page_option-folder') {
            wp_enqueue_style('cloudconnector-wpmf-style', plugins_url('cloudconnector_style.css', (__FILE__)));
            wp_enqueue_script('cloudconnector-wpmf-js', plugins_url('cloudconnector_script.js', (__FILE__)), array(), '1.0.0', true);
            wp_localize_script('cloudconnector-wpmf-js', 'cloudconnector_var', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'connector' => $this->connector,
                'ju_token' => $this->checkJoomunitedConnected(),
                'nonce' => wp_create_nonce('_cloudconnector_nonce')
            ));
        }
    }

    /**
     * Set default mode connect for install
     *
     * @return void
     */
    public function wpmfCloudInstall()
    {
        // Set default value
        WPMFGoogleDrive::setDefaultMode();
        WPMFOneDrive::setDefaultMode();
        WPMFOneDriveBusiness::setDefaultMode();
        WPMFDropbox::setDefaultMode();
        WPMFGooglePhoto::setDefaultMode();
    }

    /**
     * Close application function
     *
     * @return void
     */
    public function closeApp()
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce verification is made in before function
        $script_reload = '';
        if (isset($_GET['current_backlink'])) {
            $script_reload = 'window.opener.location.href = "' . self::urlsafeB64Decode($_GET['current_backlink']) . '";';
        }
        // phpcs:enable
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Keep the script text
        echo "<script type='text/javascript'>" . $script_reload . 'window.opener.location.reload(true);window.close();</script>';
    }

    /**
     * Check user connected to joomunited account
     *
     * @return boolean
     */
    public function checkJoomunitedConnected()
    {
        $token = get_option('ju_user_token');
        if (empty($token)) {
            return false;
        } else {
            return $token;
        }
    }

    /**
     * Decode a string with URL-safe Base64.
     *
     * @param string $input A Base64 encoded string
     *
     * @return string A decoded string
     */
    public static function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * Encode a string with URL-safe Base64.
     *
     * @param string $input The string you want encoded
     *
     * @return string The base64 encode of what you passed in
     */
    public static function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * Get a instance of the default factory
     *
     * @param string $path        Path of plugin
     * @param string $prefix      Prefix of plugin
     * @param string $name        Name of plugin
     * @param string $text_domain Translation text
     *
     * @return array
     */
    public static function getInstance($path, $prefix, $name, $text_domain)
    {
        if (!self::$instance) {
            self::$instance = new self;
            self::$instance->path = $path;
            self::$instance->prefix = $prefix;
            self::$instance->name = $name;
            self::$instance->text_domain = $text_domain;
        }
        return self::$instance;
    }
}


if (!function_exists('hash_equals')) {
    /**
     * Hash compare function
     *
     * @param string $str1 String1
     * @param string $str2 String2
     *
     * @return mixed
     */
    function hash_equals($str1, $str2)
    {
        if (strlen($str1) !== strlen($str2)) {
            return false;
        } else {
            $res = $str1 ^ $str2;
            $ret = 0;
            for ($i = strlen($res) - 1; $i >= 0; $i--) {
                $ret |= ord($res[$i]);
            }
            return !$ret;
        }
    }
}
