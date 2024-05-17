<?php

namespace WP_Media_Folder\Aws\ClientSideMonitoring;

use WP_Media_Folder\Aws\CommandInterface;
use WP_Media_Folder\Aws\Exception\AwsException;
use WP_Media_Folder\Aws\ResultInterface;
use WP_Media_Folder\GuzzleHttp\Psr7\Request;
use WP_Media_Folder\Psr\Http\Message\RequestInterface;
/**
 * @internal
 */
interface MonitoringMiddlewareInterface
{
    /**
     * Data for event properties to be sent to the monitoring agent.
     *
     * @param RequestInterface $request
     * @return array
     */
    public static function getRequestData(\WP_Media_Folder\Psr\Http\Message\RequestInterface $request);
    /**
     * Data for event properties to be sent to the monitoring agent.
     *
     * @param ResultInterface|AwsException|\Exception $klass
     * @return array
     */
    public static function getResponseData($klass);
    public function __invoke(\WP_Media_Folder\Aws\CommandInterface $cmd, \WP_Media_Folder\Psr\Http\Message\RequestInterface $request);
}
