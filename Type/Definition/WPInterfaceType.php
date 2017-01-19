<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use \GraphQL\Type\Definition\InterfaceType;

abstract class WPInterfaceType extends InterfaceType {

    use WPSchema;

    static function getSchema($config=[]) {
        return static::getWPSchema(array_replace_recursive([
            'resolveType' => [get_called_class(), 'resolveType']
        ],$config));
    }

    //abstract static function resolveWPType($obj);
    //public function resolveType($objectValue, $context, ResolveInfo $info)
}
