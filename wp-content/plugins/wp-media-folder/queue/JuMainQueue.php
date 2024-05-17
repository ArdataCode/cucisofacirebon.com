<?php
namespace Joomunited\Queue\V1_0_0;

/**
 * Class Queue
 */
class JuMainQueue
{
    /**
     * Version
     *
     * @var string
     */
    public static $version = '1.0.0';

    /**
     * Check queue enable
     *
     * @var boolean
     */
    public static $use_queue = true;

    /**
     * Plugin prefix
     *
     * @var string
     */
    public static $plugin_prefix = 'ju';

    /**
     * Plugin domain
     *
     * @var string
     */
    public static $plugin_domain = 'joomunited';

    /**
     * Plugin assets URL
     *
     * @var string
     */
    public static $assets_url = '';

    /**
     * Default options
     *
     * @var array
     */
    public static $default_options = array();

    /**
     * Queue retries
     *
     * @var integer
     */
    public static $retries = 3;

    /**
     * If debug is enabled or not
     *
     * @var boolean
     */
    public static $debug_enabled = false;

    /**
     * Status template
     *
     * @var array
     */
    public static $status_templates = array();

    /**
     * Last error
     *
     * @var array
     */
    public static $lastError = array();

    /**
     * Last instance name
     *
     * @var string
     */
    public static $lastUse = '';

    /**
     * Queue options
     *
     * @var array
     */
    public static $options = array();

    /**
     * Store instances
     *
     * @var array
     */
    public static $instances = array();

    /**
     * Get instance
     *
     * @param string $name Instance name
     *
     * @return mixed
     */
    public static function getInstance($name)
    {
        if ($name === null) {
            $name = self::$lastUse;
        }
        if (!array_key_exists($name, self::$instances)) {
            self::$instances[$name] = new JuMainQueue;
            self::$instances[$name]->name = $name;
        }
        self::$lastUse = $name;

        return self::$instances[$name];
    }

    /**
     * Joomunited Queue constructor
     *
     * @return void
     */
    public function init($args = array())
    {
        if (isset($args['use_queue'])) {
            self::setOption('use_queue', $args['use_queue'] ? true : false);
        }

        if (isset($args['plugin_prefix']) && $args['plugin_prefix'] !== '') {
            self::setOption('plugin_prefix', $args['plugin_prefix']);
        }

        if ((isset($args['assets_url']) && $args['assets_url'] !== '')) {
            self::setOption('assets_url', $args['assets_url']);
        } else {
            self::setOption('assets_url', $this->getAssetBaseUrl() . '/queue.js');
        }

        if (isset($args['plugin_domain']) && $args['plugin_domain'] !== '') {
            self::setOption('plugin_domain', $args['plugin_domain']);
        }

        $default_options = isset($args['queue_options']) && is_array($args['queue_options']) ? $args['queue_options'] : array();

        // Enable logging if needed
        if (isset($default_options['mode_debug']) && !empty($default_options['mode_debug'])) {
            self::setOption('debug_enabled', true);
        }

        self::setOption('default_options', $default_options);

        $status_templates = isset($args['status_templates']) && is_array($args['status_templates']) ? $args['status_templates'] : array();
        $exists_status_templates = self::$status_templates;
        if (is_array($status_templates) && count($status_templates)) {
            foreach ($status_templates as $key => $status) {
                if (!key_exists($key, $exists_status_templates)) {
                    $exists_status_templates[$key] = $status;
                }
            }
        }
        self::$status_templates = $exists_status_templates;

        self::runUpgrades();
        if (self::getOption('plugin_prefix') === 'ju' && !has_action('admin_init', array(__CLASS__, 'addQueueSettings'))) {
            add_action('admin_init', array($this, 'addQueueSettings'));
        }

        if (self::getOption('plugin_prefix') === 'ju' && has_action('admin_init', array($this, 'initQueueAdmin'))) {
            return false;
        }

        add_action('admin_init', array($this, 'initQueueAdmin'));

        $this->initAjax();

        return true;
    }

    /**
     * UI: Add queue setting to Settings > General
     *
     * @return void
     */
    public function addQueueSettings()
    {
        register_setting('general', 'joomunited_show_queue_adminbar');
        register_setting('general', 'joomunited_queue_speed');
        register_setting('general', 'joomunited_queue_trigger');
        register_setting('general', 'joomunited_queue_refreshment_interval');
        add_settings_section('juqueue-settings', 'Joomunited Queue Settings', array($this, 'queueSettingsSection'), 'general');
        add_settings_field(
            'joomunited_show_queue_adminbar',
            'Show queue in admin bar',
            array($this, 'showQueueCheckbox'),
            'general',
            'juqueue-settings'
        );
        add_settings_field(
            'joomunited_queue_speed',
            'Task running speed',
            array($this, 'showQueueTaskSelect'),
            'general',
            'juqueue-settings'
        );
        add_settings_field(
            'joomunited_queue_trigger',
            'Queue trigger method',
            array($this, 'showQueueTaskTrigger'),
            'general',
            'juqueue-settings'
        );
        add_settings_field(
            'joomunited_queue_refreshment_interval',
            'AJAX refreshment interval',
            array($this, 'showQueueTaskRefreshmentSelect'),
            'general',
            'juqueue-settings'
        );

        global $pagenow;
        if ($pagenow === 'options-general.php') {

            add_thickbox();
        }
    }

    /**
     * UI: Queue Settions section title
     *
     * @return void
     */
    public function queueSettingsSection()
    {
        ?>
        <p class="description">
            <?php esc_html_e('Some of JoomUnited\'s plugins require to process some task in background (cloud synchronization, file processing, ...).', self::getOption('plugin_prefix')); ?>
            <br />
            <?php esc_html_e('To prevent PHP timeout errors during the process, it\'s done asynchronously in the background.', self::getOption('plugin_prefix')); ?>
            <br />
            <?php esc_html_e('These settings let you optimize the process depending on your server resources.', self::getOption('plugin_prefix')); ?>
        </p>
        <?php
    }
    /**
     * UI: Show queue in admin bar
     *
     * @return void
     */
    public function showQueueCheckbox()
    {
        $show_in_adminbar = get_option('joomunited_show_queue_adminbar', 0);
        ?>
        <fieldset>
            <legend class="screen-reader-text"><span>Show queue status in admin bar</span></legend>
            <label for="joomunited_show_queue_adminbar" title="<?php esc_html_e('Show the number of items waiting to be processed in the admin menu bar.', self::getOption('plugin_domain')); ?>">
                <input name="joomunited_show_queue_adminbar" type="checkbox" id="joomunited_show_queue_adminbar" value="1" <?php checked($show_in_adminbar); ?>>
                <?php esc_html_e('Enable', 'wpfd'); ?>
            </label>
            <p class="description">
                <?php esc_html_e('Show the number of items waiting to be processed in the admin menu bar.', self::getOption('plugin_domain')); ?>
            </p>
        </fieldset>
        <?php
    }

    /**
     * UI: Task speed option
     *
     * @return void
     */
    public function showQueueTaskSelect()
    {
        $queue_task_speed = get_option('joomunited_queue_speed', 75);
        ?>
        <fieldset>
            <legend class="screen-reader-text"><span>Queue task speed</span></legend>
            <label for="joomunited_queue_speed" title="<?php esc_html_e('You can reduce the background task processing by changing this parameter. It could be necessary when the plugin is installed on small servers instances but requires consequent task processing. Default 75%.', self::getOption('plugin_domain')); ?>">
                <select name="joomunited_queue_speed" id="joomunited_queue_speed">
                    <option value="100" <?php selected($queue_task_speed, 100); ?>>100%</option>
                    <option value="75" <?php selected($queue_task_speed, 75); ?>>75%</option>
                    <option value="50" <?php selected($queue_task_speed, 50); ?>>50%</option>
                    <option value="25" <?php selected($queue_task_speed, 25); ?>>25%</option>
                </select>
            </label>
            <p class="description">
                <?php esc_html_e('You can reduce the background task processing by changing this parameter. It could be necessary when the plugin is installed on small servers instances but requires consequent task processing. Default 75%.', self::getOption('plugin_domain')); ?>
            </p>
        </fieldset>
        <?php
    }

    /**
     * UI: Task ajax refreshment interval
     *
     * @return void
     */
    public function showQueueTaskRefreshmentSelect()
    {
        $queue_refreshment_interval = get_option('joomunited_queue_refreshment_interval', 15);
        ?>
        <fieldset>
            <legend class="screen-reader-text"><span>AJAX queue refreshment interval</span></legend>
            <label for="joomunited_queue_refreshment_interval" title="<?php esc_html_e('You can reduce the background task ajax calling by changing this parameter. It could be necessary when the plugin is installed on small servers instances or shared hosting. Default 15s.', self::getOption('plugin_domain')); ?>">
                <select name="joomunited_queue_refreshment_interval" id="joomunited_queue_refreshment_interval">
                    <option value="10" <?php selected($queue_refreshment_interval, 10); ?>>10s</option>
                    <option value="15" <?php selected($queue_refreshment_interval, 15); ?>>15s</option>
                    <option value="20" <?php selected($queue_refreshment_interval, 20); ?>>20s</option>
                    <option value="25" <?php selected($queue_refreshment_interval, 25); ?>>25s</option>
                    <option value="30" <?php selected($queue_refreshment_interval, 30); ?>>30s</option>
                    <option value="35" <?php selected($queue_refreshment_interval, 35); ?>>35s</option>
                    <option value="40" <?php selected($queue_refreshment_interval, 40); ?>>40s</option>
                    <option value="45" <?php selected($queue_refreshment_interval, 45); ?>>45s</option>
                    <option value="50" <?php selected($queue_refreshment_interval, 50); ?>>50s</option>
                    <option value="55" <?php selected($queue_refreshment_interval, 55); ?>>55s</option>
                    <option value="60" <?php selected($queue_refreshment_interval, 60); ?>>1m</option>
                </select>
            </label>
            <p class="description"><?php esc_html_e('You can reduce the background task ajax calling by changing this parameter. It could be necessary when the plugin is installed on small servers instances or shared hosting. Default 15s.', self::getOption('plugin_domain')); ?></p>
        </fieldset>
        <?php
    }

    /**
     * UI: Task ajax refreshment interval
     *
     * @return void
     */
    public function showQueueTaskTrigger()
    {
        $queue_task_trigger = get_option('joomunited_queue_trigger', 'heartbeat');
        ?>
        <fieldset>
            <legend class="screen-reader-text"><span>Queue trigger method</span></legend>
            <label for="joomunited_queue_trigger" title="<?php esc_html_e('Choose method to trigger the queue. Default is WP Heartbeat.', self::getOption('plugin_domain')); ?>">
                <select name="joomunited_queue_trigger" id="joomunited_queue_trigger">
                    <option value="heartbeat" <?php selected($queue_task_trigger, 'heartbeat'); ?>>WP Heartbeat</option>
                    <option value="ajax" <?php selected($queue_task_trigger, 'ajax'); ?>>Ajax</option>
                </select>
            </label>
            <p class="description">
                <?php esc_html_e('Choose method to trigger the queue. Default is WP Heartbeat.', self::getOption('plugin_domain')); ?>
            </p>
        </fieldset>
        <?php
    }

    /**
     * Get task speed
     *
     * @return mixed|void
     *
     * @return integer
     */
    public static function getTasksSpeed()
    {
        return get_option('joomunited_queue_speed', 75);
    }

    /**
     * Get queue option
     *
     * @param string $key Option key
     *
     * @return boolean|mixed|null
     */
    public static function getOption($key)
    {
        // Return option in lastest instance
        if (!empty(self::$lastUse)) {
            // Get options of instance
            $options = self::$options;
            $optionValue = isset($options[self::$lastUse][$key]) ? $options[self::$lastUse][$key] : null;
            if (!is_null($optionValue)) {
                return $optionValue;
            }
        }
        try {
            // Return default option
            $class = new \ReflectionClass(__CLASS__);
            if ($class->hasProperty($key)) {
                return $class->getStaticPropertyValue($key);
            }
        } catch (\ReflectionException $e) {
            self::$lastError[] = $e->getMessage();
            return null;
        }

        return false;
    }

    /**
     * Set queue option
     *
     * @param string $key   Option key
     * @param mixed  $value Option value
     *
     * @return void
     */
    public static function setOption($key, $value)
    {
        if (!empty(self::$lastUse)) {
            // Set options for instance
            $options = self::$options;
            $options[self::$lastUse][$key] = $value;
            self::$options = $options;
        } else {
            try {
                $class = new \ReflectionClass(__CLASS__);
                if ($class->hasProperty($key)) {
                    $class->setStaticPropertyValue($key, $value);
                }
            } catch (\ReflectionException $e) {
                self::$lastError[] = $e->getMessage();
            }
        }
    }

    /**
     * Init queue for admin bar
     *
     * @return void
     */
    public function initQueueAdmin() {
        add_filter('heartbeat_received', array($this, 'heartbeat_received'), 10, 2);
        add_action('admin_footer', array($this, 'enqueueScript'), 10);

        // Add menu bar
        $show_in_adminbar = get_option('joomunited_show_queue_adminbar', 0);
        if ($show_in_adminbar) {
            add_action('admin_bar_menu', array($this, 'queueAdminBar'), 999);

            wp_register_style(self::getOption('plugin_prefix') . '-dummy-handle', false);
            wp_enqueue_style(self::getOption('plugin_prefix') . '-dummy-handle');
            wp_add_inline_style(
                self::getOption('plugin_prefix') . '-dummy-handle',
                '#wp-admin-bar-'. self::getOption('plugin_prefix') .'-topbar a {
                            color: #FFF !important;                    
                          }
                          #wp-admin-bar-'. self::getOption('plugin_prefix') .'-topbar span.'. self::getOption('plugin_prefix') .' {
                            width: 10px;
                            height: 10px;
                            border-radius: 5px;
                            background-color: #969696;
                            display: inline-block;
                            vertical-align: baseline;
                            margin-right: 6px;
                          }
                          #wp-admin-bar-'. self::getOption('plugin_prefix') .'-topbar span.'. self::getOption('plugin_prefix') .'-querying {
                            opacity: 0.6;
                          }
                          #wp-admin-bar-'. self::getOption('plugin_prefix') .'-topbar span.'. self::getOption('plugin_prefix') .'-green {
                            background-color: #4caf50;
                          }
                          #wp-admin-bar-'. self::getOption('plugin_prefix') .'-topbar span.'. self::getOption('plugin_prefix') .'-orange {
                            background-color: #ff9800;
                          }
                          #wp-admin-bar-'. self::getOption('plugin_prefix') .'-topbar span.'. self::getOption('plugin_prefix') .'-gray {
                            background-color: #969696 !important;
                          }
                          .ju-status-wrap {
                            position: relative;
                          }
                          .ju_queue_status {
                            position: absolute !important;
                            top: 100%;
                            left: -10px;
                            background: #32373c !important;
                            display: none;
                          }
                          .ju_queue_status li {
                            color: color: rgba(240, 245, 250, 0.7) !important;
                            width: 300px !important;
                            text-overflow: ellipsis !important;
                            overflow: hidden;
                            display: inline-block;
                            padding: 2px 10px !important;
                            box-sizing: border-box !important;
                            border-bottom: #474747 1px solid;
                          }
                          .ju-status-wrap:hover > .ju_queue_status{
                            display: block;
                          }
                          .'. self::getOption('plugin_prefix') .'_clear_queue .dashicons, .'. self::getOption('plugin_prefix') .'_stop_queue .dashicons {
                            font-family: dashicons !important;
                            vertical-align: middle;
                            font-size: 16px !important;
                            line-height: 18px !important;
                            margin-right: 5px !important;
                          }
                          .'. self::getOption('plugin_prefix') .'_clear_queue *, .'. self::getOption('plugin_prefix') .'_stop_queue * {
                            vertical-align: middle;
                            display: inline-block;
                          }
                          @-webkit-keyframes rotating /* Safari and Chrome */ {
                              from {
                                -webkit-transform: rotate(0deg);
                                -o-transform: rotate(0deg);
                                transform: rotate(0deg);
                              }
                              to {
                                -webkit-transform: rotate(360deg);
                                -o-transform: rotate(360deg);
                                transform: rotate(360deg);
                              }
                            }
                            @keyframes rotating {
                              from {
                                -ms-transform: rotate(0deg);
                                -moz-transform: rotate(0deg);
                                -webkit-transform: rotate(0deg);
                                -o-transform: rotate(0deg);
                                transform: rotate(0deg);
                              }
                              to {
                                -ms-transform: rotate(360deg);
                                -moz-transform: rotate(360deg);
                                -webkit-transform: rotate(360deg);
                                -o-transform: rotate(360deg);
                                transform: rotate(360deg);
                              }
                            }
                            .'. self::getOption('plugin_prefix') .'_clear_queue.queue_running .dashicons-remove {
                              -webkit-animation: rotating 0.2s linear infinite;
                              -moz-animation: rotating 0.2s linear infinite;
                              -ms-animation: rotating 0.2s linear infinite;
                              -o-animation: rotating 0.2s linear infinite;
                              animation: rotating 0.2s linear infinite;
                            }
                          '
            );
        }
    }

    /**
     * Add queue status to admin bar
     *
     * @param WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance, passed by reference
     *
     * @return void
     */
    public function queueAdminBar($wp_admin_bar)
    {
        $stop = self::getStopStatus();
        // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain,WordPress.WP.I18n.NonSingularStringLiteralText
        $stop_button = (!empty($stop)) ? '<span class="dashicons dashicons-controls-play"></span><label>' . __('Start queue', self::getOption('plugin_domain')) . '</label>' : '<span class="dashicons dashicons-controls-pause"></span><label>' . esc_html__('Pause queue', self::getOption('plugin_domain')) . '</label>';
        $args = array(
            'id' => self::getOption('plugin_prefix') . '-topbar',
            'title' => '<a href="#" class="ju-status-wrap"><span class="' . self::getOption('plugin_prefix') . '"></span><span class="' . self::getOption('plugin_prefix') . '-queue">0</span><div class="ju_queue_status"><ul><li class="' . self::getOption('plugin_prefix') . '_clear_queue"><span class="dashicons dashicons-remove"></span><label>' . __('Clear queue', self::getOption('plugin_domain')) . '</label></li><li class="' . self::getOption('plugin_prefix') . '_stop_queue">' . $stop_button . '</li></ul></div></a>',// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain,WordPress.WP.I18n.NonSingularStringLiteralText
            'meta' => array(
                'classname' => 'ju-queue',
            ),
        );
        $wp_admin_bar->add_node($args);
    }

    /**
     * Get stop status
     *
     * @return integer
     */
    public function getStopStatus()
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare('SELECT option_value FROM '. $wpdb->options .' WHERE option_name = %s LIMIT 1', self::getOption('plugin_prefix') . '_stop_queue'));
        if (is_object($row)) {
            $stop = (int)$row->option_value;
        } else {
            $stop = 0;
        }

        return $stop;
    }

    /**
     * Check queue exist
     *
     * @param string $value   Value
     * @param string $compare Compare
     *
     * @return array|object|void|null
     */
    public function checkQueueExist($value = '', $compare = '=')
    {
        global $wpdb;
        $table = $wpdb->prefix . self::getOption('plugin_prefix') . '_queue';
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Params has prepared
        switch ($compare) {
            case '=':
                $row = $wpdb->get_row($wpdb->prepare('SELECT id, status, responses FROM ' . $table . ' WHERE datas = BINARY %s ORDER BY date_added DESC', array($value)));
                break;
            case 'LIKE':
                $row = $wpdb->get_row($wpdb->prepare('SELECT id, status, responses FROM ' . $table . ' WHERE datas LIKE BINARY %s ORDER BY date_added DESC', array('%' . $value . '%')));
                break;
            default:
                $row = $wpdb->get_row($wpdb->prepare('SELECT id, status, responses FROM ' . $table . ' WHERE datas = BINARY %s ORDER BY date_added DESC', array($value)));
        }
        // phpcs:enable
        return $row;
    }

    /**
     * Add to the queue
     *
     * @param array $datas     Datas details
     * @param array $responses Responses details
     *
     * @return void
     */
    public function addToQueue($datas = array(), $responses = array())
    {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . self::getOption('plugin_prefix') . '_queue',
            array(
                'action' => $datas['action'],
                'datas' => json_encode($datas),
                'responses' => stripslashes(json_encode($responses)),
                'date_added' => round(microtime(true) * 1000),
                'date_done' => null,
                'status' => 0
            ),
            array(
                '%s',
                '%s',
                '%s',
                '%d',
                '%d',
                '%d'
            )
        );
    }

    /**
     * Proceed queue asynchronously
     *
     * @return void
     */
    public function proceedQueueAsync()
    {
        global $wpdb;
        $stop = $this->getStopStatus();
        // run if no stop
        if (empty($stop)) {
            $row = $wpdb->get_row($wpdb->prepare('SELECT option_value FROM '. $wpdb->options .' WHERE option_name = %s LIMIT 1', self::getOption('plugin_prefix') . '_queue_running'));
            if (is_object($row)) {
                $queue_running = (int)$row->option_value;
            } else {
                $queue_running = 0;
            }

            //$queue_running = get_option(self::getOption('plugin_prefix' . '_queue_running');
            $queue_length = $this->getQueueLength();
            // Check if queue is currently running for less than 30 seconds
            if (($queue_running + 300) < time()) {
                delete_option(self::getOption('plugin_prefix') . '_queue_id_running');
            }

            if ($queue_length && $queue_running + 30 < time()) {
                update_option(self::getOption('plugin_prefix') . '_queue_running', time());
                switch ((int)self::getTasksSpeed()) {
                    case 75:
                        sleep(4);
                        break;
                    case 25:
                        sleep(10);
                        break;
                }
                $remoteUrl = admin_url('admin-ajax.php').'?action='. self::getOption('plugin_prefix') .'_proceed&'. self::getOption('plugin_prefix') .'_token='.get_option(self::getOption('plugin_prefix') . '_token') . '&speed=' . self::getTasksSpeed();
                /**
                 * Filter to add Basic Authentication for queue can work on protected site.
                 *
                 * @param array
                 */
                $basicAuthentication = apply_filters('ju_queue_basic_authentication_info', array('username' => '', 'password' => ''));
                $protocol = 'http';
                if (strpos($remoteUrl, 'https') === 0) {
                    $protocol = 'https';
                }

                if ($basicAuthentication['username'] !== '' && $basicAuthentication['password'] !== '') {
                    $basicAuthentication['password'] = urlencode($basicAuthentication['password']);
                    $remoteUrl = str_replace($protocol . '://', $protocol . '://' . $basicAuthentication['username'] . ':' . $basicAuthentication['password'] . '@', $remoteUrl);
                }

                $result = wp_remote_head($remoteUrl, array('sslverify' => false));
                self::log('Info : Proceed queue asynchronously ' . (is_wp_error($result)?$result->get_error_message():'success'));
            } elseif ($queue_length) {
                self::log('Info : Queue already running (queue_running: ' . $queue_running .', time: ' . time());
            }
        }
    }

    /**
     * Update responses
     *
     * @param integer $id        Item ID
     * @param array   $responses Responses
     *
     * @return void
     */
    public function updateResponses($id, $responses = array())
    {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . self::getOption('plugin_prefix') . '_queue',
            array(
                'responses' => json_encode($responses)
            ),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Update datas
     *
     * @param integer $id    Item ID
     * @param array   $datas Datas
     *
     * @return void
     */
    public function updateDatas($id, $datas = array())
    {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . self::getOption('plugin_prefix') . '_queue',
            array(
                'datas' => json_encode($datas)
            ),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Roll back queue
     *
     * @param integer $id    Queue ID
     * @param array   $datas Queue datas
     *
     * @return void
     */
    public function rollBackQueue($id, $datas = array())
    {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . self::getOption('plugin_prefix') . '_queue',
            array(
                'datas' => json_encode($datas),
                'retries' => 0,
                'status' => 0
            ),
            array('id' => $id),
            array('%s', '%d', '%d'),
            array('%d')
        );
    }

    /**
     * Delete a queue by id
     *
     * @param integer $id ID of queue
     *
     * @return void
     */
    public function deleteQueue($id)
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Params has prepared
        $wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->prefix . self::getOption('plugin_prefix') . '_queue WHERE id = %d', (int)$id));
    }

    /**
     * Proceed elements in the queue
     *
     * @return integer
     */
    private function proceedQueue()
    {
        self::log('Info : Proceed queue synchronously');
        global $wpdb;
        $done = 0;
        $max_execution_time = $this->getMaximumExecutionTime();
        self::log('Info : Max execution time is ' . $max_execution_time);
        // Update last queue time value
        update_option(self::getOption('plugin_prefix') . '_queue_running', time());

        // Remove last week elements
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Params has prepared
        $wpdb->query('DELETE FROM ' . $wpdb->prefix . self::getOption('plugin_prefix') . '_queue WHERE date_done < (UNIX_TIMESTAMP()*1000 - 1 * 24 * 60 * 60 * 1000)');
        // Retrieve all elements in the queue
        do {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Params has prepared
            $elements = $wpdb->get_results('SELECT id, datas, retries, date_added FROM ' . $wpdb->prefix . self::getOption('plugin_prefix') . '_queue WHERE status=0 ORDER BY date_added ASC LIMIT 50');
            foreach ($elements as $element) {
                // check if a queue is running
                $row = $wpdb->get_row($wpdb->prepare('SELECT option_value FROM '. $wpdb->options .' WHERE option_name = %s LIMIT 1', self::getOption('plugin_prefix') . '_queue_id_running'));
                if (is_object($row)) {
                    $queue_id_running = (int)$row->option_value;
                } else {
                    $queue_id_running = 0;
                }

                if (!empty($queue_id_running) && (int)$queue_id_running === $element->id) {
                    return $done;
                }

                update_option(self::getOption('plugin_prefix') . '_queue_id_running', $element->id);
                set_time_limit(0);
                // Actually move the file
                $datas = json_decode($element->datas, true);
                $retries = (int) $element->retries + 1;
                if ($retries > self::getOption('retries')) {
                    $result = true;
                    $wpdb->update(
                        $wpdb->prefix . self::getOption('plugin_prefix') . '_queue',
                        array(
                            'date_done' => round(microtime(true) * 1000),
                            'retries' => (int)$retries,
                            'status' => 2
                        ),
                        array('id' => $element->id),
                        array('%d', '%d', '%d'),
                        array('%d')
                    );
                } else {
                    $result = apply_filters($datas['action'], -1, $datas, $element->id);
                    if ($result) {
                        $wpdb->update(
                            $wpdb->prefix . self::$plugin_prefix . '_queue',
                            array(
                                'date_done' => round(microtime(true) * 1000),
                                'retries' => (int)$retries,
                                'status' => 1
                            ),
                            array('id' => $element->id),
                            array('%d', '%d', '%d'),
                            array('%d')
                        );
                    } else {
                        $wpdb->update(
                            $wpdb->prefix . self::$plugin_prefix . '_queue',
                            array(
                                'retries' => (int)$retries
                            ),
                            array('id' => $element->id),
                            array('%d'),
                            array('%d')
                        );
                    }
                }

                // Update last queue time value
                update_option(self::getOption('plugin_prefix') . '_queue_running', time());
                delete_option(self::getOption('plugin_prefix') . '_queue_id_running');
                if ($result) {
                    $done++;
                }
            }
            $current_time = microtime(true);
        } while ($elements && $current_time < $max_execution_time);

        self::log('Info : Synchronous queue finished');

        return $done;
    }

    /**
     * Retrieve microtime at which the script should stop
     *
     * @return float
     */
    private function getMaximumExecutionTime()
    {
        $max_execution_time = (int)ini_get('max_execution_time');

        if (!$max_execution_time) {
            $max_execution_time = 30;
        } elseif ($max_execution_time > 60) {
            $max_execution_time = 60;
        }

        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            $time = $_SERVER['REQUEST_TIME_FLOAT'];
        } else {
            // Consider script started 3 seconds ago
            $time = microtime(true) - 3 * 1000 * 1000;
        }

        // We should stop the script 3 seconds before it reach max execution limit
        return $time + $max_execution_time * 1000 * 1000 - 3 * 1000 * 1000;
    }

    /**
     * Get number of items in the queue waiting
     *
     * @return integer
     */
    public function getQueueLength()
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Params has prepared
        return (int)$wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . self::getOption('plugin_prefix') . '_queue WHERE status=0');
    }

    /**
     * Get status list
     *
     * @return array|object|null
     */
    public function getStatus()
    {
        global $wpdb;
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Params has prepared
        $results = $wpdb->get_results('SELECT COUNT(action) as count, action FROM ' . $wpdb->prefix . self::getOption('plugin_prefix') . '_queue WHERE status=0 GROUP BY action');
        return $results;
    }

    /**
     * Get remain count by actions
     *
     * @param array $actions Actions
     *
     * @return integer
     */
    public function getRemainCountByActions($actions = array())
    {
        global $wpdb;

        if (count($actions) === 0) {
            return -1;
        }

        $inActionArr = array_map(function($action) {
            return '"' . $action . '"';
        }, $actions);

        return (int)$wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . self::getOption('plugin_prefix') . '_queue WHERE status=0 AND action in ('.implode(',', $inActionArr).')');
    }

    /**
     * Enqueue background task script
     *
     * @return void
     */
    public function enqueueScript()
    {
        global $wpdb;
        $queue_length = (int)$wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . self::getOption('plugin_prefix') . '_queue');
        if ($queue_length > 0 || self::getOption('use_queue')) {
            $queue_trigger = get_option('joomunited_queue_trigger', 'heartbeat');
            $joomunited_queue_refreshment_interval = get_option('joomunited_queue_refreshment_interval', 15);
            wp_enqueue_script(self::getOption('plugin_prefix') . '_queue', self::getOption('assets_url'), array('jquery'), null, true);
            wp_localize_script(self::getOption('plugin_prefix') . '_queue', self::getOption('plugin_prefix') . '_object_queue', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'prefix' => self::getOption('plugin_prefix'),
                'trigger' => $queue_trigger,
                'queue_ajax_interval' => $joomunited_queue_refreshment_interval,
                'stop_label' => esc_html__('Pause queue', self::getOption('plugin_domain')),// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain,WordPress.WP.I18n.NonSingularStringLiteralText
                'start_label' => esc_html__('Start queue', self::getOption('plugin_domain')),// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain,WordPress.WP.I18n.NonSingularStringLiteralText
            ));
        }
    }

    /**
     * Heartbeat received
     *
     * @return array
     */
    public function heartbeat_received($response, $data)
    {
        // Make sure we only run our query if the edd_heartbeat key is present
        if (isset($data['ju_queue_heartbeat']) && $data['ju_queue_heartbeat'] === 'run_queue') {
            /**
             * Action fire to sync
             *
             * @internal
             */
            $this->proceedQueueAsync();
            // Send back the number of timestamp
            $response['ju_queue_result'] = $this->ajaxQueue();
        }
        return $response;
    }

    /**
     * Ajax request
     *
     * @return void
     */
    public function initAjax()
    {
        add_action('wp_ajax_'. self::getOption('plugin_prefix') .'_clear_queue', function () {
            global $wpdb;
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Params has prepared
            $wpdb->query('DELETE FROM ' . $wpdb->prefix . self::getOption('plugin_prefix') . '_queue');
            wp_send_json(array('status' => true));
        });

        add_action('wp_ajax_'. self::getOption('plugin_prefix') .'_stop_queue', function () {
            global $wpdb;
            $row = $wpdb->get_row($wpdb->prepare('SELECT option_value FROM '. $wpdb->options .' WHERE option_name = %s LIMIT 1', self::getOption('plugin_prefix') . '_stop_queue'));
            if (is_object($row)) {
                $stop = ((int)$row->option_value === 0) ? 1 : 0;
            } else {
                $stop = 1;
            }

            update_option(self::getOption('plugin_prefix') . '_stop_queue', $stop);
        });

        add_action('wp_ajax_'. self::getOption('plugin_prefix') .'_queue', function () {
            $result = $this->ajaxQueue();
            if ($result) {
                header('Content-Type: application/json');
                echo $result;
            }
            self::proceedQueueAsync();
            exit(0);
        });

        add_action('wp_ajax_nopriv_'. self::getOption('plugin_prefix') .'_proceed', function () {
            error_reporting(0);
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action and a custom token is used
            if (!isset($_REQUEST[self::getOption('plugin_prefix') . '_token']) || $_REQUEST[self::getOption('plugin_prefix') . '_token'] !== get_option(self::getOption('plugin_prefix') . '_token')) {
                self::log('Info : Proceed queue ajax stopped, wrong token');
                exit(0);
            }

            self::log('Info : Proceed queue ajax');

            if (ob_get_length()) {
                ob_end_clean();
            }
            header('Connection: close');
            header('Content-Encoding: none');
            ignore_user_abort(true);
            header('Content-Length: 0');
            ob_end_flush();
            flush();
            if (ob_get_length()) {
                ob_end_clean();
            }

            $this->proceedQueue();
            switch ((int)self::getTasksSpeed()) {
                case 100:
                case 75:
                    if ($this->getQueueLength()) {
                        $this->proceedQueueAsync();
                    }
                    break;
            }
        });
    }

    /**
     * Get queue status on ajax call
     *
     * @return false|string
     */
    public function ajaxQueue()
    {
        $stop = $this->getStopStatus();
        $queue_length = $this->getQueueLength();
        $statuss = $this->getStatus();
        $status_html = '<ul class="ju_queue_status_res">';
        $status_templates = self::$status_templates;

        foreach ($statuss as $status) {
            if (isset($status_templates[$status->action])) {
                $status_html .= '<li>'. str_replace('%d', $status->count, $status_templates[$status->action]) .'</li>';
            }
        }
        $status_html .= '</ul>';

        return json_encode(array(
            'queue_length' => $queue_length,
            'status_html' => $status_html,
            'stop' => $stop,
            'title' => sprintf(__('%s actions queued', self::getOption('plugin_domain')), $queue_length) // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain,WordPress.WP.I18n.NonSingularStringLiteralText
        ));
    }

    /**
     * Get table charset and collation.
     *
     * @return string
     */
    public function getWpCharsetCollate() {

        global $wpdb;
        $charset_collate = '';

        if ( ! empty ( $wpdb->charset ) )
            $charset_collate = 'CHARACTER SET ' . $wpdb->charset;

        if (!empty ($wpdb->collate)) {
            $charset_collate .= ' COLLATE ' . $wpdb->collate;
        }

        return $charset_collate;
    }

    /**
     * Check if the plugin need to run an update of db or options
     *
     * @return void
     */
    public function runUpgrades()
    {
        global $wpdb;
        $charset_collate = $this->getWpCharsetCollate();
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Params has prepared
        $createTable = 'CREATE TABLE `' . $wpdb->prefix . self::getOption('plugin_prefix') . '_queue` (
                      `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                      `datas` LONGTEXT NOT NULL,
                      `action` LONGTEXT NOT NULL,
                      `responses` LONGTEXT DEFAULT NULL,
                      `date_added` VARCHAR(14) NOT NULL,
                      `date_done` VARCHAR(14) DEFAULT NULL,
                      `retries` int(11) UNSIGNED NOT NULL DEFAULT 0,
                      `status` tinyint(1) UNSIGNED NOT NULL,
                      PRIMARY KEY (`id`),
                      KEY idx_datas (datas(191)),
                      KEY idx_status (status)
                    ) ' . $charset_collate . ' ENGINE=InnoDB';
        // phpcs:enable
        // Create table if not exists. Return true on table exists or success.
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $result = \maybe_create_table($wpdb->prefix . self::getOption('plugin_prefix') . '_queue', $createTable);

        $checkDb = get_option(self::getOption('plugin_prefix') . '_queue', false);
        // Up to date, nothing to do
        if ($checkDb) {
            return;
        }

        add_option(self::getOption('plugin_prefix') . '_token', self::getRandomString());
        update_option(self::getOption('plugin_prefix') . '_queue', self::$version);
    }

    /**
     * Generate a random string
     *
     * @param integer $length Length of the returned string
     *
     * @author https://stackoverflow.com/questions/4356289/php-random-string-generator#answer-4356295
     *
     * @return string
     */
    private function getRandomString($length = 20)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * Log into a debug file
     *
     * @param string $msg Message
     *
     * @return void
     */
    public static function log($msg = '')
    {
        // Do nothing if not enabled
        if (!self::getOption('debug_enabled')) {
            return;
        }

        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Log if enable debug
        error_log($msg);
    }

    /**
     * Get asset URL
     *
     * @return string
     */
    private function getAssetBaseUrl()
    {
        $currentPath = plugin_dir_url(__FILE__) . DIRECTORY_SEPARATOR . 'assets';
        $url = str_replace(
            wp_normalize_path(untrailingslashit(ABSPATH)),
            site_url(),
            wp_normalize_path($currentPath)
        );

        return esc_url_raw($url);
    }

    /**
     * Update queue meta
     *
     * @param integer $attachment_id Attachment ID
     * @param integer $queue_id      Queue ID
     *
     * @return void
     */
    public function updateQueuePostMeta($attachment_id, $queue_id)
    {
        $queue_meta = get_post_meta($attachment_id, 'wpmf_sync_queue', true);
        if (!empty($queue_meta) && is_array($queue_meta)) {
            $queue_ids = array_merge($queue_meta, array($queue_id));
        } else {
            $queue_ids = array((int)$queue_id);
        }
        update_post_meta($attachment_id, 'wpmf_sync_queue', array_unique($queue_ids));
    }

    /**
     * Update queue meta
     *
     * @param integer $term_id  Term ID
     * @param integer $queue_id Queue ID
     *
     * @return void
     */
    public function updateQueueTermMeta($term_id, $queue_id)
    {
        $queue_meta = get_term_meta($term_id, 'wpmf_sync_queue', true);
        if (!empty($queue_meta) && is_array($queue_meta)) {
            $queue_ids = array_merge($queue_meta, array($queue_id));
        } else {
            $queue_ids = array((int)$queue_id);
        }
        update_term_meta($term_id, 'wpmf_sync_queue', array_unique($queue_ids));
    }
}
