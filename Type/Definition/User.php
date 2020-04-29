<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQLRelay\Relay;

use GraphQL\Type\Definition\Type;

class User extends WPObjectType
{
    const TYPE = 'WP_User';

    static function getFieldSchema()
    {
        return [
            'id' => Relay::globalIdField(self::TYPE, function ($user) {
                return $user->ID;
            }),
            'ID' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The ID of the user',
            ],
            'first_name' => [
                'type' => Type::string(),
                'description' => 'Users first name',
            ],
            'last_name' => [
                'type' => Type::string(),
                'description' => 'Users last name'
            ],
            'display_name' => [
                'type' => Type::string(),
                'description' => 'Users display name'
            ]
        ];
    }
}
