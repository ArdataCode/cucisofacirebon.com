<?php

namespace WP_Media_Folder\Aws\Api\Serializer;

use WP_Media_Folder\Aws\Api\StructureShape;
use WP_Media_Folder\Aws\Api\Service;
/**
 * @internal
 */
class RestXmlSerializer extends \WP_Media_Folder\Aws\Api\Serializer\RestSerializer
{
    /** @var XmlBody */
    private $xmlBody;
    /**
     * @param Service $api      Service API description
     * @param string  $endpoint Endpoint to connect to
     * @param XmlBody $xmlBody  Optional XML formatter to use
     */
    public function __construct(\WP_Media_Folder\Aws\Api\Service $api, $endpoint, \WP_Media_Folder\Aws\Api\Serializer\XmlBody $xmlBody = null)
    {
        parent::__construct($api, $endpoint);
        $this->xmlBody = $xmlBody ?: new \WP_Media_Folder\Aws\Api\Serializer\XmlBody($api);
    }
    protected function payload(\WP_Media_Folder\Aws\Api\StructureShape $member, array $value, array &$opts)
    {
        $opts['headers']['Content-Type'] = 'application/xml';
        $opts['body'] = (string) $this->xmlBody->build($member, $value);
    }
}
