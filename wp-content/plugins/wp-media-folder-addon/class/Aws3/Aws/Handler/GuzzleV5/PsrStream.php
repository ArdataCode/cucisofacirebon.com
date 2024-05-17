<?php

namespace WP_Media_Folder\Aws\Handler\GuzzleV5;

use WP_Media_Folder\GuzzleHttp\Stream\StreamDecoratorTrait;
use WP_Media_Folder\GuzzleHttp\Stream\StreamInterface as GuzzleStreamInterface;
use WP_Media_Folder\Psr\Http\Message\StreamInterface as Psr7StreamInterface;
/**
 * Adapts a Guzzle 5 Stream to a PSR-7 Stream.
 *
 * @codeCoverageIgnore
 */
class PsrStream implements \WP_Media_Folder\Psr\Http\Message\StreamInterface
{
    use StreamDecoratorTrait;
    /** @var GuzzleStreamInterface */
    private $stream;
    public function __construct(\WP_Media_Folder\GuzzleHttp\Stream\StreamInterface $stream)
    {
        $this->stream = $stream;
    }
    public function rewind()
    {
        $this->stream->seek(0);
    }
    public function getContents()
    {
        return $this->stream->getContents();
    }
}
