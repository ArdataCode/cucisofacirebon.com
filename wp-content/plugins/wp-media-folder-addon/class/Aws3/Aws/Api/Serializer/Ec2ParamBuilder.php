<?php

namespace WP_Media_Folder\Aws\Api\Serializer;

use WP_Media_Folder\Aws\Api\Shape;
use WP_Media_Folder\Aws\Api\ListShape;
/**
 * @internal
 */
class Ec2ParamBuilder extends \WP_Media_Folder\Aws\Api\Serializer\QueryParamBuilder
{
    protected function queryName(\WP_Media_Folder\Aws\Api\Shape $shape, $default = null)
    {
        return $shape['queryName'] ?: ucfirst($shape['locationName']) ?: $default;
    }
    protected function isFlat(\WP_Media_Folder\Aws\Api\Shape $shape)
    {
        return false;
    }
    protected function format_list(\WP_Media_Folder\Aws\Api\ListShape $shape, array $value, $prefix, &$query)
    {
        // Handle empty list serialization
        if (!$value) {
            $query[$prefix] = false;
        } else {
            $items = $shape->getMember();
            foreach ($value as $k => $v) {
                $this->format($items, $v, $prefix . '.' . ($k + 1), $query);
            }
        }
    }
}
