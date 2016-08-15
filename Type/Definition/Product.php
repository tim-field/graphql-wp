<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use \Mohiohio\GraphQLWP\Schema as WPSchema;

class Product extends WPObjectType {

    static function getDescription() {
        return 'The WooCommerce product class handles individual product data.';
    }

    static function getFieldSchema() {
        return WCProduct::getFieldSchema();
    }

    static function getSchemaInterfaces() {
        return [WCProduct::getInstance(), WPSchema::getNodeDefinition()['nodeInterface']];
    }
}
