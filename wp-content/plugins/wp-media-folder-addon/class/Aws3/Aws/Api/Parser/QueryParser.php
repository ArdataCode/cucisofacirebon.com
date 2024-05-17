<?php

namespace WP_Media_Folder\Aws\Api\Parser;

use WP_Media_Folder\Aws\Api\Service;
use WP_Media_Folder\Aws\Api\StructureShape;
use WP_Media_Folder\Aws\Result;
use WP_Media_Folder\Aws\CommandInterface;
use WP_Media_Folder\Psr\Http\Message\ResponseInterface;
use WP_Media_Folder\Psr\Http\Message\StreamInterface;
/**
 * @internal Parses query (XML) responses (e.g., EC2, SQS, and many others)
 */
class QueryParser extends \WP_Media_Folder\Aws\Api\Parser\AbstractParser
{
    use PayloadParserTrait;
    /** @var bool */
    private $honorResultWrapper;
    /**
     * @param Service   $api                Service description
     * @param XmlParser $xmlParser          Optional XML parser
     * @param bool      $honorResultWrapper Set to false to disable the peeling
     *                                      back of result wrappers from the
     *                                      output structure.
     */
    public function __construct(\WP_Media_Folder\Aws\Api\Service $api, \WP_Media_Folder\Aws\Api\Parser\XmlParser $xmlParser = null, $honorResultWrapper = true)
    {
        parent::__construct($api);
        $this->parser = $xmlParser ?: new \WP_Media_Folder\Aws\Api\Parser\XmlParser();
        $this->honorResultWrapper = $honorResultWrapper;
    }
    public function __invoke(\WP_Media_Folder\Aws\CommandInterface $command, \WP_Media_Folder\Psr\Http\Message\ResponseInterface $response)
    {
        $output = $this->api->getOperation($command->getName())->getOutput();
        $xml = $this->parseXml($response->getBody(), $response);
        if ($this->honorResultWrapper && $output['resultWrapper']) {
            $xml = $xml->{$output['resultWrapper']};
        }
        return new \WP_Media_Folder\Aws\Result($this->parser->parse($output, $xml));
    }
    public function parseMemberFromStream(\WP_Media_Folder\Psr\Http\Message\StreamInterface $stream, \WP_Media_Folder\Aws\Api\StructureShape $member, $response)
    {
        $xml = $this->parseXml($stream, $response);
        return $this->parser->parse($member, $xml);
    }
}
