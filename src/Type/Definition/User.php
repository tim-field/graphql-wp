<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQLRelay\Relay;
use GraphQL\Type\Definition\ListOfType;
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
                'type' => Type::nonNull(Type::int()),
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
            ],
            'roles' => [
                'type' => Type::nonNull(new ListOfType(Type::string())),
                'description' => 'The roles this use is part of'
            ],
            'allcaps' => [
                'type' => Type::nonNull(new ListOfType(Type::string())),
                'description' => 'All capabilities the user has, including individual and role based.'
            ],
            'user_login' => [
                'type' => Type::nonNull(Type::string()),
            ],
            'user_nicename' => [
                'type' => Type::nonNull(Type::string()),
            ],
            'user_email' => [
                'type' => Type::nonNull(Type::string()),
            ],
            'user_url' => [
                'type' => Type::string(),
            ],
            'user_status' => [
                'type' => Type::string(),
            ],
            'is_admin' => [
                'type' => Type::boolean(),
                'resolve' => function ($user) {
                    return in_array('administrator', $user->roles);
                }
            ]
        ];
    }
}
