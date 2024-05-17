<?php

namespace WP_Media_Folder\Aws\Handler\GuzzleV6;

use Exception;
use WP_Media_Folder\GuzzleHttp\Exception\ConnectException;
use WP_Media_Folder\GuzzleHttp\Exception\RequestException;
use WP_Media_Folder\GuzzleHttp\Promise;
use WP_Media_Folder\GuzzleHttp\Client;
use WP_Media_Folder\GuzzleHttp\ClientInterface;
use WP_Media_Folder\GuzzleHttp\TransferStats;
use WP_Media_Folder\Psr\Http\Message\RequestInterface as Psr7Request;
/**
 * A request handler that sends PSR-7-compatible requests with Guzzle 6.
 */
class GuzzleHandler
{
    /** @var ClientInterface */
    private $client;
    /**
     * @param ClientInterface $client
     */
    public function __construct(\WP_Media_Folder\GuzzleHttp\ClientInterface $client = null)
    {
        $this->client = $client ?: new \WP_Media_Folder\GuzzleHttp\Client();
    }
    /**
     * @param Psr7Request $request
     * @param array       $options
     *
     * @return Promise\Promise
     */
    public function __invoke(\WP_Media_Folder\Psr\Http\Message\RequestInterface $request, array $options = [])
    {
        $request = $request->withHeader('User-Agent', $request->getHeaderLine('User-Agent') . ' ' . \WP_Media_Folder\GuzzleHttp\default_user_agent());
        return $this->client->sendAsync($request, $this->parseOptions($options))->otherwise(static function (\Exception $e) {
            $error = ['exception' => $e, 'connection_error' => $e instanceof ConnectException, 'response' => null];
            if ($e instanceof RequestException && $e->getResponse()) {
                $error['response'] = $e->getResponse();
            }
            return new \WP_Media_Folder\GuzzleHttp\Promise\RejectedPromise($error);
        });
    }
    private function parseOptions(array $options)
    {
        if (isset($options['http_stats_receiver'])) {
            $fn = $options['http_stats_receiver'];
            unset($options['http_stats_receiver']);
            $prev = isset($options['on_stats']) ? $options['on_stats'] : null;
            $options['on_stats'] = static function (\WP_Media_Folder\GuzzleHttp\TransferStats $stats) use($fn, $prev) {
                if (is_callable($prev)) {
                    $prev($stats);
                }
                $transferStats = ['total_time' => $stats->getTransferTime()];
                $transferStats += $stats->getHandlerStats();
                $fn($transferStats);
            };
        }
        return $options;
    }
}
