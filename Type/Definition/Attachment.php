<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQL\Type\Definition\Type;

class Attachment extends Post {

    static function getFieldSchema() {

        return [
            'url' => [
                'type' => Type::string(),
                'resolve' => function($post) {
                    return wp_get_attachment_url($post->ID);
                }
            ],
            'image_src' => [
                'type' => ImageSrc::getInstance(),
                'args' => [
                    'size' => ['type' => Type::string()],
                    'icon' => ['type' => Type::boolean()]
                ],
                'resolve' => function($post, $args) {
                    $args += [
                        'size' => 'thumbnail',
                        'icon' => false,
                    ];
                    extract($args);

                    if($res = wp_get_attachment_image_src($post->ID, $size, $icon)){
                        return array_combine(['url','width','height','is_intermediate'],$res);
                    }
                }
            ]
        ] + parent::getFieldSchema();
    }
}
