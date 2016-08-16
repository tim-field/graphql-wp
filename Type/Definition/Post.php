<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use \Mohiohio\GraphQLWP\Schema;

class Post extends WPObjectType {

    static function getDescription() {
        return 'A standard WordPress blog post';
    }

    static function getFieldSchema() {
        return WPPost::getFieldSchema();
    }

    static function getSchemaInterfaces() {
        return [WPPost::getInstance(), Schema::getNodeDefinition()['nodeInterface']];
    }
}
