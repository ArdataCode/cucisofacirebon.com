<?php

namespace WP_Media_Folder\Aws\Api\Parser;

use WP_Media_Folder\Aws\Api\Service;
use WP_Media_Folder\Aws\Api\StructureShape;
use WP_Media_Folder\Psr\Http\Message\ResponseInterface;
use WP_Media_Folder\Psr\Http\Message\StreamInterface;
/**
 * @internal Implements REST-JSON parsing (e.g., Glacier, Elastic Transcoder)
 */
class RestJsonParser extends \WP_Media_Folder\Aws\Api\Parser\AbstractRestParser
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
    protected function payload(\WP_Media_Folder\Psr\Http\Message\ResponseInterface $response, \WP_Media_Folder\Aws\Api\StructureShape $member, array &$result)
    {
        $jsonBody = $this->parseJson($response->getBody(), $response);
        if ($jsonBody) {
            $result += $this->parser->parse($member, $jsonBody);
        }
    }
    public function parseMemberFromStream(\WP_Media_Folder\Psr\Http\Message\StreamInterface $stream, \WP_Media_Folder\Aws\Api\StructureShape $member, $response)
    {
        $jsonBody = $this->parseJson($stream, $response);
        if ($jsonBody) {
            return $this->parser->parse($member, $jsonBody);
        }
        return [];
    }
}
