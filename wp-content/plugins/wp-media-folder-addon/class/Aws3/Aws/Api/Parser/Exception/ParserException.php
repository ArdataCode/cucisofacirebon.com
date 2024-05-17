<?php

namespace WP_Media_Folder\Aws\Api\Parser\Exception;

use WP_Media_Folder\Aws\HasMonitoringEventsTrait;
use WP_Media_Folder\Aws\MonitoringEventsInterface;
use WP_Media_Folder\Aws\ResponseContainerInterface;
class ParserException extends \RuntimeException implements \WP_Media_Folder\Aws\MonitoringEventsInterface, \WP_Media_Folder\Aws\ResponseContainerInterface
{
    use HasMonitoringEventsTrait;
    private $response;
    public function __construct($message = '', $code = 0, $previous = null, array $context = [])
    {
        $this->response = isset($context['response']) ? $context['response'] : null;
        parent::__construct($message, $code, $previous);
    }
    /**
     * Get the received HTTP response if any.
     *
     * @return ResponseInterface|null
     */
    public function getResponse()
    {
        return $this->response;
    }
}
