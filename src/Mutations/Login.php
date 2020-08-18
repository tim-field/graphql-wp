<?php

namespace Mohiohio\GraphQLWP\Mutations;

use GraphQL\Type\Definition\Type;
use Mohiohio\GraphQLWP\Type\Definition\User;
use Mohiohio\GraphQLWP\Type\Definition\WPError;
use ReallySimpleJWT\Token;
use Ramsey\Uuid\Uuid;


class Login extends MutationInterface
{
    const REFRESH_TOKEN_META_KEY = 'graphql-wp-refresh-token';

    static function getInputFields()
    {
        return [
            'username' => [
                'type' => Type::nonNull(Type::string())
            ],
            'password' => [
                'type' => Type::nonNull(Type::string())
            ],
        ];
    }

    static function getOutputFields()
    {
        return [
            'token' => [
                'type' => Type::string(),
                'resolve' => function ($payload) {
                    $user = $payload['user'];
                    return static::get_token($user);
                }
            ],
            'refresh_token' => [
                'type' => Type::string(),
                'resolve' => function ($payload) {
                    $user = $payload['user'];
                    return static::get_refresh_token($user);
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

    static function get_token_expire_time()
    {
        return apply_filters('graphql-wp-token-expire-seconds', HOUR_IN_SECONDS);
    }

    static function get_token($user)
    {
        $secret = getenv('JWT_SECRET', true);
        if ($secret && $user && $user->ID) {
            // https://github.com/RobDWaller/ReallySimpleJWT
            $token = Token::create($user->ID, $secret, time() + static::get_token_expire_time(), getenv('WP_HOME'));

            return $token;
        }
        return null;
    }

    static function get_refresh_token($user)
    {
        $secret = getenv('JWT_SECRET', true);
        if ($secret && $user && $user->ID) {
            $key = get_user_meta($user->ID, self::REFRESH_TOKEN_META_KEY, true);
            if (!$key) {
                $key = Uuid::uuid4()->toString();
                update_user_meta($user->ID, self::REFRESH_TOKEN_META_KEY, $key);
            }
            // https://github.com/RobDWaller/ReallySimpleJWT
            $token = Token::create($key, $secret, time() + DAY_IN_SECONDS * 365, getenv('WP_HOME'));

            return $token;
        }
        return null;
    }

    static function mutateAndGetPayload($input)
    {

        $secret = getenv('JWT_SECRET', true);
        if (!$secret) {
            throw new \Exception('JWT_SECRET environment variable not set');
        }
        $res = wp_authenticate($input['username'], $input['password']);
        $is_error = is_wp_error($res);


        return [
            'user' => $is_error ? null : $res,
            'error' => $is_error ? $res : null
        ];
    }
}
