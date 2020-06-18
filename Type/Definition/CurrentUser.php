<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQL\Type\Definition\Type;
use Mohiohio\GraphQLWP\Mutations\Login;

class CurrentUser extends User
{
    const TYPE = 'WP_CurrentUser';

    static function getFieldSchema()
    {
        return [
            'token' => [
                'type' => Type::string(),
                'resolve' => function ($user) {
                    return $user ? Login::get_token($user) : null;
                }
            ],
            'refresh_token' => [
                'type' => Type::string(),
                'resolve' => function ($user) {
                    return $user ? Login::get_refresh_token($user) : null;
                }
            ],
        ] + parent::getFieldSchema();
    }
}
