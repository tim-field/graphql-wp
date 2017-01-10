<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);

use \GraphQL\Type\Definition\InterfaceType;

abstract class WPInterfaceType extends InterfaceType {

    use WPSchema;

    static function getSchema($config=[]) {
        return static::getWPSchema(array_replace_recursive([
            'resolveType' => [get_called_class(), 'resolveType']
        ],$config));
    }

    abstract static function resolveType($obj);
}
