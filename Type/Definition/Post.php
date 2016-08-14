<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use \Mohiohio\GraphQLWP\Schema as WPSchema;

class Post extends WPObjectType {

    use Instance;

    static function getDescription() {
        return 'A standard WordPress blog post';
    }

    static function getFieldSchema() {
        return WPPost::getFieldSchema();
    }

    static function getSchemaInterfaces(){
        \Analog::log('calling getSchemaInterfaces from Post');
        return [WPSchema::getPostInterfaceType(), WPSchema::getNodeDefinition()['nodeInterface']];
    }
}
