<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use \GraphQL\Type\Definition\Type;

class Query extends WPObjectType
{

    static function getFieldSchema()
    {
        return [
            'wp_query' => [
                'type' => static::getWPQuery(),
                'resolve' => function ($root, $args) {
                    global $wp_query;
                    return $wp_query;
                }
            ],
            'wp_post' => [
                'type' => static::getPostInterfaceType(),
                'args' => static::getQueryArgsPost(),
                'resolve' => function ($root, $args) {
                    return (static::getPostInterfaceType())::resolve($root, $args);
                }
            ],
            'term' => [
                'type' => static::getTermInterfaceType(),
                'args' => [
                    'id' => [
                        'type' => Type::string(),
                        'description' => 'Term id'
                    ]
                ],
                'resolve' => function ($root, $args) {
                    return get_term($args['id']);
                }
            ],
            'node' => static::getNodeDefinition()['nodeField']
        ];
    }
}
