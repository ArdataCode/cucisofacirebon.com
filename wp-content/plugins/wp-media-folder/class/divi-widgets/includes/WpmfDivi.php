<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Class WpmfDivi
 */
class WpmfDivi extends DiviExtension
{

    /**
     * The gettext domain for the extension's translations.
     *
     * @var string
     */
    public $gettext_domain = 'wpmf';

    /**
     * The extension's WP Plugin name.
     *
     * @var string
     */
    public $name = 'wpmf';

    /**
     * The extension's version
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * WpmfDivi constructor.
     *
     * @param string $name Name of extension
     * @param array  $args Params
     *
     * @return void
     */
    public function __construct($name = 'wpmf', $args = array())
    {
        $this->plugin_dir = plugin_dir_path(__FILE__);
        $this->plugin_dir_url = plugin_dir_url($this->plugin_dir);

        parent::__construct($name, $args);
    }
}

new WpmfDivi;
