<?php

namespace Mohiohio\GraphQLWP\Mutations;

use Exception;
use GraphQL\Type\Definition\Type;
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
                    return $payload['token'];
                }
            ]
        ];
    }

    static function mutateAndGetPayload($input)
    {
        $secret = getenv('JWT_SECRET', true);
        if (!$secret) {
            throw new Exception('JWT_SECRET environment variable not set');
        }

        $res = wp_authenticate($input['username'], $input['password']);
        $is_error = is_wp_error($res);
        // https://github.com/RobDWaller/ReallySimpleJWT
        $token = $is_error ? null : Token::customPayload([
            'iat' => time(),
            'uid' => 1,
            'exp' => time() + 3600,
            'uid' => $res->ID,
            'data' => [
                'ID' => $res->ID,
                'caps' => $res->caps,
                'caps_key' => $res->caps_key,
                'roles' => $res->roles,
                // 'allcaps' => $res->allcaps,
                'first_name' => $res->first_name,
                'last_name' => $res->last_name
            ]
        ], $secret);

        return [
            'token' => $token,
            'user' => $is_error ? null : $res,
            'error' => $is_error ? $res : null
        ];
    }
}
