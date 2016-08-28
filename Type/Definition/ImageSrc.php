<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQL\Type\Definition\Type;

class ImageSrc extends WPObjectType {

    private static $instance;

    static function getInstance($config=[]) {
        return static::$instance ?: static::$instance = new static($config);
    }

    static function getFieldSchema() {

        return [
            'url' => ['type'=>Type::string()],
            'width' => ['type'=>Type::string()],
            'height' => ['type'=>Type::string()],
            'is_intermediate' => ['type'=>Type::boolean()],
        ];
    }

    static function getDescription() {
        return " A mime icon for files, thumbnail or intermediate size for images.
        see https://developer.wordpress.org/reference/functions/wp_get_attachment_image_src";
    }
}
