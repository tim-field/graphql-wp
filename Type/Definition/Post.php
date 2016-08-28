<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use \Mohiohio\GraphQLWP\Schema;

//TODO should extend abstract tag post type which enforces getPostType method
class Post extends WPObjectType {

    const POST_TYPE = 'post';

    static function getInstance() { // TODO smells bad
        return WPPost::getTypes(static::POST_TYPE);
    }

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
