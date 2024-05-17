<?php

namespace WP_Media_Folder\Aws\Api\Parser;

use WP_Media_Folder\Aws\Api\StructureShape;
use WP_Media_Folder\Aws\Api\Service;
use WP_Media_Folder\Aws\Result;
use WP_Media_Folder\Aws\CommandInterface;
use WP_Media_Folder\Psr\Http\Message\ResponseInterface;
use WP_Media_Folder\Psr\Http\Message\StreamInterface;
/**
 * @internal Implements JSON-RPC parsing (e.g., DynamoDB)
 */
class JsonRpcParser extends \WP_Media_Folder\Aws\Api\Parser\AbstractParser
{
    use PayloadParserTrait;
    /**
     * @param Service    $api    Service description
     * @param JsonParser $parser JSON body builder
     */
    public function __construct(\WP_Media_Folder\Aws\Api\Service $api, \WP_Media_Folder\Aws\Api\Parser\JsonParser $parser = null)
    {
        parent::__construct($api);
        $this->parser = $parser ?: new \WP_Media_Folder\Aws\Api\Parser\JsonParser();
    }
    public function __invoke(\WP_Media_Folder\Aws\CommandInterface $command, \WP_Media_Folder\Psr\Http\Message\ResponseInterface $response)
    {
        $operation = $this->api->getOperation($command->getName());
        $result = null === $operation['output'] ? null : $this->parseMemberFromStream($response->getBody(), $operation->getOutput(), $response);
        return new \WP_Media_Folder\Aws\Result($result ?: []);
    }
    public function parseMemberFromStream(\WP_Media_Folder\Psr\Http\Message\StreamInterface $stream, \WP_Media_Folder\Aws\Api\StructureShape $member, $response)
    {
        return $this->parser->parse($member, $this->parseJson($stream, $response));
    }
}
