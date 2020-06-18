<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;

class WPError extends WPObjectType
{
    const TYPE = 'WP_Error';

    static function getFieldSchema()
    {
        return [
            'errors' => [
                'type' => new ListOfType(Type::nonNull(Type::string())),
                'args' => [
                    'code' => [
                        'description' => 'Retrieve messages matching code, if exists.',
                        'type' => Type::string()
                    ]
                ],
                'resolve' => function (\WP_Error $error, $args) {
                    return $error->get_error_messages($args['code'] ?? '');
                }
            ],
            'error' => [
                'description' => 'Get error message',
                'type' => Type::string(),
                'args' => [
                    'code' => [
                        'description' => 'Retrieve messages matching code, if exists.',
                        'type' => Type::string()
                    ]
                ],
                'resolve' => function (\WP_Error $error, $args) {
                    return $error->get_error_message($args['code'] ?? '');
                }
            ]
        ];
    }
}
