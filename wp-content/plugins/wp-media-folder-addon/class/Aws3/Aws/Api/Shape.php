<?php

namespace WP_Media_Folder\Aws\Api;

/**
 * Base class representing a modeled shape.
 */
class Shape extends \WP_Media_Folder\Aws\Api\AbstractModel
{
    /**
     * Get a concrete shape for the given definition.
     *
     * @param array    $definition
     * @param ShapeMap $shapeMap
     *
     * @return mixed
     * @throws \RuntimeException if the type is invalid
     */
    public static function create(array $definition, \WP_Media_Folder\Aws\Api\ShapeMap $shapeMap)
    {
        static $map = ['structure' => 'WP_Media_Folder\\Aws\\Api\\StructureShape', 'map' => 'WP_Media_Folder\\Aws\\Api\\MapShape', 'list' => 'WP_Media_Folder\\Aws\\Api\\ListShape', 'timestamp' => 'WP_Media_Folder\\Aws\\Api\\TimestampShape', 'integer' => 'WP_Media_Folder\\Aws\\Api\\Shape', 'double' => 'WP_Media_Folder\\Aws\\Api\\Shape', 'float' => 'WP_Media_Folder\\Aws\\Api\\Shape', 'long' => 'WP_Media_Folder\\Aws\\Api\\Shape', 'string' => 'WP_Media_Folder\\Aws\\Api\\Shape', 'byte' => 'WP_Media_Folder\\Aws\\Api\\Shape', 'character' => 'WP_Media_Folder\\Aws\\Api\\Shape', 'blob' => 'WP_Media_Folder\\Aws\\Api\\Shape', 'boolean' => 'WP_Media_Folder\\Aws\\Api\\Shape'];
        if (isset($definition['shape'])) {
            return $shapeMap->resolve($definition);
        }
        if (!isset($map[$definition['type']])) {
            throw new \RuntimeException('Invalid type: ' . print_r($definition, true));
        }
        $type = $map[$definition['type']];
        return new $type($definition, $shapeMap);
    }
    /**
     * Get the type of the shape
     *
     * @return string
     */
    public function getType()
    {
        return $this->definition['type'];
    }
    /**
     * Get the name of the shape
     *
     * @return string
     */
    public function getName()
    {
        return $this->definition['name'];
    }
}
