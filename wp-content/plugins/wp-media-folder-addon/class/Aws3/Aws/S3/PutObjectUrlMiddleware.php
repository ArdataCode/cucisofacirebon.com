<?php

namespace WP_Media_Folder\Aws\S3;

use WP_Media_Folder\Aws\CommandInterface;
use WP_Media_Folder\Aws\ResultInterface;
use WP_Media_Folder\Psr\Http\Message\RequestInterface;
/**
 * Injects ObjectURL into the result of the PutObject operation.
 *
 * @internal
 */
class PutObjectUrlMiddleware
{
    /** @var callable  */
    private $nextHandler;
    /**
     * Create a middleware wrapper function.
     *
     * @return callable
     */
    public static function wrap()
    {
        return function (callable $handler) {
            return new self($handler);
        };
    }
    /**
     * @param callable $nextHandler Next handler to invoke.
     */
    public function __construct(callable $nextHandler)
    {
        $this->nextHandler = $nextHandler;
    }
    public function __invoke(\WP_Media_Folder\Aws\CommandInterface $command, \WP_Media_Folder\Psr\Http\Message\RequestInterface $request = null)
    {
        $next = $this->nextHandler;
        return $next($command, $request)->then(function (\WP_Media_Folder\Aws\ResultInterface $result) use($command) {
            $name = $command->getName();
            switch ($name) {
                case 'PutObject':
                case 'CopyObject':
                    $result['ObjectURL'] = $result['@metadata']['effectiveUri'];
                    break;
                case 'CompleteMultipartUpload':
                    $result['ObjectURL'] = $result['Location'];
                    break;
            }
            return $result;
        });
    }
}
