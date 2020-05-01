<?php

namespace Mohiohio\GraphQLWP\Mutations;

use GraphQL\Type\Definition\Type;
use Mohiohio\GraphQLWP\Type\Definition\User;
use ReallySimpleJWT\Token;


class Login extends MutationInterface
{
    static function getInputFields()
    {
        return [
            'username' => [
                'type' => Type::string()
            ],
            'password' => [
                'type' => Type::string()
            ]
        ];
    }

    static function getOutputFields()
    {
        return [
            'token' => [
                'type' => Type::string(),
                'resolve' => function ($payload) {
                    $secret = getenv('JWT_SECRET', true);
                    if (!$secret) {
                        throw new \Exception('JWT_SECRET environment variable not set');
                    }

                    $user = $payload['user'];
                    // https://github.com/RobDWaller/ReallySimpleJWT
                    $token = $user ? Token::create($user->ID, $secret, time() + 3600, getenv('WP_HOME')) : null;

                    return $token;
                }
            ],
            'user' => [
                'type' => User::getInstance(),
                'resolve' => function ($payload) {
                    return $payload['user'];
                }
            ]
        ];
    }

    static function mutateAndGetPayload($input)
    {
        $res = wp_authenticate($input['username'], $input['password']);
        $is_error = is_wp_error($res);

        return [
            'user' => $is_error ? null : $res,
            'error' => $is_error ? $res : null
        ];
    }
}
