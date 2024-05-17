<?php

namespace WP_Media_Folder\Aws\S3;

use WP_Media_Folder\Aws\Api\Parser\AbstractParser;
use WP_Media_Folder\Aws\Api\StructureShape;
use WP_Media_Folder\Aws\Api\Parser\Exception\ParserException;
use WP_Media_Folder\Aws\CommandInterface;
use WP_Media_Folder\Aws\Exception\AwsException;
use WP_Media_Folder\Psr\Http\Message\ResponseInterface;
use WP_Media_Folder\Psr\Http\Message\StreamInterface;
/**
 * Converts malformed responses to a retryable error type.
 *
 * @internal
 */
class RetryableMalformedResponseParser extends \WP_Media_Folder\Aws\Api\Parser\AbstractParser
{
    /** @var string */
    private $exceptionClass;
    public function __construct(callable $parser, $exceptionClass = \WP_Media_Folder\Aws\Exception\AwsException::class)
    {
        $this->parser = $parser;
        $this->exceptionClass = $exceptionClass;
    }
    public function __invoke(\WP_Media_Folder\Aws\CommandInterface $command, \WP_Media_Folder\Psr\Http\Message\ResponseInterface $response)
    {
        $fn = $this->parser;
        try {
            return $fn($command, $response);
        } catch (ParserException $e) {
            throw new $this->exceptionClass("Error parsing response for {$command->getName()}:" . " AWS parsing error: {$e->getMessage()}", $command, ['connection_error' => true, 'exception' => $e], $e);
        }
    }
    public function parseMemberFromStream(\WP_Media_Folder\Psr\Http\Message\StreamInterface $stream, \WP_Media_Folder\Aws\Api\StructureShape $member, $response)
    {
        return $this->parser->parseMemberFromStream($stream, $member, $response);
    }
}
