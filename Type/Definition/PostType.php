<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use \Mohiohio\GraphQLWP\Schema;

abstract class PostType extends WPObjectType {

    abstract static function getPostType();

    static function getInstance() { // TODO smells bad
        return WPPost::getTypes(static::getPostType());
    }

    static function getFieldSchema() {
        return WPPost::getFieldSchema();
    }

    static function getSchemaInterfaces() {
        return [WPPost::getInstance(), Schema::getNodeDefinition()['nodeInterface']];
    }
}
