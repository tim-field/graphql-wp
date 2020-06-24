<?php

namespace Mohiohio\GraphQLWP\Mutations;

use GraphQL\Type\Definition\Type;
use Mohiohio\GraphQLWP\Type\Definition\User;
use Mohiohio\GraphQLWP\Type\Definition\WPError;

class Register extends MutationInterface
{
    const REFRESH_TOKEN_META_KEY = 'graphql-wp-refresh-token';

    static function getInputFields()
    {
        return [
            'user_pass' => [
                'type' => Type::nonNull(Type::string())
            ],
            'user_email' => [
                'type' => Type::nonNull(Type::string())
            ],
            'user_login' => [
                'type' => Type::string()
            ],
            'display_name' => [
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
                    $user = $payload['user'];
                    return Login::get_token($user);
                }
            ],
            'refresh_token' => [
                'type' => Type::string(),
                'resolve' => function ($payload) {
                    $user = $payload['user'];
                    return Login::get_refresh_token($user);
                }
            ],
            'user' => [
                'type' => User::getInstance()
            ],
            'error' => [
                'type' => WPError::getInstance()
            ]
        ];
    }

    static function mutateAndGetPayload($userdata)
    {
        if (empty($userdata['user_login'])) {
            $userdata['user_login'] = $userdata['user_email'];
        }

        $res = wp_insert_user($userdata);
        $is_error = is_wp_error($res);

        $is_error = is_wp_error($res);
        if (!$is_error) {
            $res = wp_authenticate($userdata['user_email'], $userdata['user_pass']);
            error_log(print_r($res, true));
        }

        return [
            'user' => $is_error ? null : $res,
            'error' => $is_error ? $res : null
        ];
    }
}
