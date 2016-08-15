<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use \GraphQL\Type\Definition\ObjectType;

abstract class WPObjectType extends ObjectType {

    use WPSchema;

    static function getSchemaInterfaces() {
        return [];
    }

    static function getSchema($config=[]) {
        return static::getWPSchema(array_replace_recursive([
            'interfaces' => function() {
                return static::getSchemaInterfaces();
            }
        ],$config));
    }
}
