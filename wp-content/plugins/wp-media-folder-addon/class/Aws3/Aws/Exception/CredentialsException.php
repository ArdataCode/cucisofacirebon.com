<?php

namespace WP_Media_Folder\Aws\Exception;

use WP_Media_Folder\Aws\HasMonitoringEventsTrait;
use WP_Media_Folder\Aws\MonitoringEventsInterface;
class CredentialsException extends \RuntimeException implements \WP_Media_Folder\Aws\MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
