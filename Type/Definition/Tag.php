<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use Mohiohio\GraphQLWP\Schema;

class Tag extends WPObjectType {

    static function getDescription() {
        return "The \'post_tag\' taxonomy is similar to categories, but more free form.";
    }

    static function getFieldSchema() {
        return WPTerm::getFieldSchema();
    }

    static function getSchemaInterfaces() {
        return [WPTerm::getInstance(), Schema::getNodeDefinition()['nodeInterface']];
    }
}
