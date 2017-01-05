<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use \GraphQL\Type\Definition\Type;
use \GraphQL\Type\Definition\ListOfType;

class MenuItem extends WPObjectType {

    private static $instance;

    static function getInstance($config=[]) {
        return static::$instance ?: static::$instance = new static($config);
    }

    static function getDescription() {
        return 'Items in a navigation menu';
    }

    static function getFieldSchema() {

        return [
            'id' => [
                'type' => Type::string(),
                'description' => 'Unique id for menu item',
                'resolve' => function($item) {
                    return $item->object_id;
                }
            ],
            'caption' => [
                'type' => Type::string(),
                'description' => 'File caption',
                'resolve' => function($item) {
                    return $item->caption;
                }
            ],
            'title' => [
                'type' => Type::string(),
                'description' => 'File title',
                'resolve' => function($item) {
                    return $item->title;
                }
            ],
            'menu_order' => [
                'type' => Type::string(),
                'description' => 'Item order',
                'resolve' => function($item) {
                    return $item->menu_order;
                }
            ],
            'menu_item_parent' => [
                'type' => Type::string(),
                'description' => 'Item parent',
                'resolve' => function($item) {
                    return $item->menu_item_parent;
                }
            ],
            'target' => [
                'type' => Type::string(),
                'description' => 'Link target',
                'resolve' => function($item) {
                    return $item->target;
                }
            ],
            'url' => [
                'type' => Type::string(),
                'description' => 'Menu url',
                'resolve' => function($item) {
                    return $item->url;
                }
            ],
            'description' => [
                'type' => Type::string(),
                'description' => 'Link description',
                'resolve' => function($item) {
                    return $item->description;
                }
            ],
            'classes' => [
                'type' => new ListOfType(Type::string()),
                'description' => 'CSS class names for this item',
                'resolve' => function($item) {
                    return $item->classes;
                }
            ]
        ];
    }
}
