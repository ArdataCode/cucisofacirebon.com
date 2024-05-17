<?php

namespace WP_Media_Folder\Aws\Api\Serializer;

use WP_Media_Folder\Aws\Api\Service;
use WP_Media_Folder\Aws\Api\StructureShape;
/**
 * Serializes requests for the REST-JSON protocol.
 * @internal
 */
class RestJsonSerializer extends \WP_Media_Folder\Aws\Api\Serializer\RestSerializer
{
    /** @var JsonBody */
    private $jsonFormatter;
    /** @var string */
    private $contentType;
    /**
     * @param Service  $api           Service API description
     * @param string   $endpoint      Endpoint to connect to
     * @param JsonBody $jsonFormatter Optional JSON formatter to use
     */
    public function __construct(\WP_Media_Folder\Aws\Api\Service $api, $endpoint, \WP_Media_Folder\Aws\Api\Serializer\JsonBody $jsonFormatter = null)
    {
        parent::__construct($api, $endpoint);
        $this->contentType = \WP_Media_Folder\Aws\Api\Serializer\JsonBody::getContentType($api);
        $this->jsonFormatter = $jsonFormatter ?: new \WP_Media_Folder\Aws\Api\Serializer\JsonBody($api);
    }
    protected function payload(\WP_Media_Folder\Aws\Api\StructureShape $member, array $value, array &$opts)
    {
        $opts['headers']['Content-Type'] = $this->contentType;
        $opts['body'] = (string) $this->jsonFormatter->build($member, $value);
    }
}
