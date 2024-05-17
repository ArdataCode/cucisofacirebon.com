<?php

namespace WP_Media_Folder\Aws\ClientSideMonitoring\Exception;

use WP_Media_Folder\Aws\HasMonitoringEventsTrait;
use WP_Media_Folder\Aws\MonitoringEventsInterface;
/**
 * Represents an error interacting with configuration for client-side monitoring.
 */
class ConfigurationException extends \RuntimeException implements \WP_Media_Folder\Aws\MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
