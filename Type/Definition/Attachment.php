<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQL\Type\Definition\Type;

class Attachment extends Post {

    static function getFieldSchema() {

        return [
            'src' => [
                'type' => Type::nonNull(Type::string()),
                'resolve' => function($post) {
                    return wp_get_attachment_url($post->ID);
                }
            ]
        ] + parent::getFieldSchema();
    }
}
