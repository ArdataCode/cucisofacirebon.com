<?php

namespace WP_Media_Folder\Aws\Api\Parser;

use WP_Media_Folder\Aws\Api\StructureShape;
use WP_Media_Folder\Aws\Api\Service;
use WP_Media_Folder\Psr\Http\Message\ResponseInterface;
use WP_Media_Folder\Psr\Http\Message\StreamInterface;
/**
 * @internal Implements REST-XML parsing (e.g., S3, CloudFront, etc...)
 */
class RestXmlParser extends \WP_Media_Folder\Aws\Api\Parser\AbstractRestParser
{
    use PayloadParserTrait;
    /**
     * @param Service   $api    Service description
     * @param XmlParser $parser XML body parser
     */
    public function __construct(\WP_Media_Folder\Aws\Api\Service $api, \WP_Media_Folder\Aws\Api\Parser\XmlParser $parser = null)
    {
        parent::__construct($api);
        $this->parser = $parser ?: new \WP_Media_Folder\Aws\Api\Parser\XmlParser();
    }
    protected function payload(\WP_Media_Folder\Psr\Http\Message\ResponseInterface $response, \WP_Media_Folder\Aws\Api\StructureShape $member, array &$result)
    {
        $result += $this->parseMemberFromStream($response->getBody(), $member, $response);
    }
    public function parseMemberFromStream(\WP_Media_Folder\Psr\Http\Message\StreamInterface $stream, \WP_Media_Folder\Aws\Api\StructureShape $member, $response)
    {
        $xml = $this->parseXml($stream, $response);
        return $this->parser->parse($member, $xml);
    }
}
