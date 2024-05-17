<?php

namespace WP_Media_Folder\Aws\Api\Parser;

use WP_Media_Folder\Aws\Api\DateTimeResult;
use WP_Media_Folder\Aws\Api\ListShape;
use WP_Media_Folder\Aws\Api\MapShape;
use WP_Media_Folder\Aws\Api\Shape;
use WP_Media_Folder\Aws\Api\StructureShape;
/**
 * @internal Implements standard XML parsing for REST-XML and Query protocols.
 */
class XmlParser
{
    public function parse(\WP_Media_Folder\Aws\Api\StructureShape $shape, \SimpleXMLElement $value)
    {
        return $this->dispatch($shape, $value);
    }
    private function dispatch($shape, \SimpleXMLElement $value)
    {
        static $methods = ['structure' => 'parse_structure', 'list' => 'parse_list', 'map' => 'parse_map', 'blob' => 'parse_blob', 'boolean' => 'parse_boolean', 'integer' => 'parse_integer', 'float' => 'parse_float', 'double' => 'parse_float', 'timestamp' => 'parse_timestamp'];
        $type = $shape['type'];
        if (isset($methods[$type])) {
            return $this->{$methods[$type]}($shape, $value);
        }
        return (string) $value;
    }
    private function parse_structure(\WP_Media_Folder\Aws\Api\StructureShape $shape, \SimpleXMLElement $value)
    {
        $target = [];
        foreach ($shape->getMembers() as $name => $member) {
            // Extract the name of the XML node
            $node = $this->memberKey($member, $name);
            if (isset($value->{$node})) {
                $target[$name] = $this->dispatch($member, $value->{$node});
            }
        }
        return $target;
    }
    private function memberKey(\WP_Media_Folder\Aws\Api\Shape $shape, $name)
    {
        if (null !== $shape['locationName']) {
            return $shape['locationName'];
        }
        if ($shape instanceof ListShape && $shape['flattened']) {
            return $shape->getMember()['locationName'] ?: $name;
        }
        return $name;
    }
    private function parse_list(\WP_Media_Folder\Aws\Api\ListShape $shape, \SimpleXMLElement $value)
    {
        $target = [];
        $member = $shape->getMember();
        if (!$shape['flattened']) {
            $value = $value->{$member['locationName'] ?: 'member'};
        }
        foreach ($value as $v) {
            $target[] = $this->dispatch($member, $v);
        }
        return $target;
    }
    private function parse_map(\WP_Media_Folder\Aws\Api\MapShape $shape, \SimpleXMLElement $value)
    {
        $target = [];
        if (!$shape['flattened']) {
            $value = $value->entry;
        }
        $mapKey = $shape->getKey();
        $mapValue = $shape->getValue();
        $keyName = $shape->getKey()['locationName'] ?: 'key';
        $valueName = $shape->getValue()['locationName'] ?: 'value';
        foreach ($value as $node) {
            $key = $this->dispatch($mapKey, $node->{$keyName});
            $value = $this->dispatch($mapValue, $node->{$valueName});
            $target[$key] = $value;
        }
        return $target;
    }
    private function parse_blob(\WP_Media_Folder\Aws\Api\Shape $shape, $value)
    {
        return base64_decode((string) $value);
    }
    private function parse_float(\WP_Media_Folder\Aws\Api\Shape $shape, $value)
    {
        return (double) (string) $value;
    }
    private function parse_integer(\WP_Media_Folder\Aws\Api\Shape $shape, $value)
    {
        return (int) (string) $value;
    }
    private function parse_boolean(\WP_Media_Folder\Aws\Api\Shape $shape, $value)
    {
        return $value == 'true';
    }
    private function parse_timestamp(\WP_Media_Folder\Aws\Api\Shape $shape, $value)
    {
        if (!empty($shape['timestampFormat']) && $shape['timestampFormat'] === 'unixTimestamp') {
            return \WP_Media_Folder\Aws\Api\DateTimeResult::fromEpoch((string) $value);
        }
        return new \WP_Media_Folder\Aws\Api\DateTimeResult($value);
    }
}
