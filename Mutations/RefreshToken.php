<?php

namespace Mohiohio\GraphQLWP\Mutations;

use GraphQL\Type\Definition\Type;
use ReallySimpleJWT\Token;

class RefreshToken extends MutationInterface
{
    static function getInputFields()
    {
        return [
            'refresh_token' => [
                'type' => Type::string()
            ]
        ];
    }

    static function getOutputFields()
    {
        return [
            'token' => [
                'type' => Type::string(),
            ]
        ];
    }

    static function mutateAndGetPayload($input)
    {
        $secret = getenv('JWT_SECRET', true);
        if (!$secret) {
            throw new \Exception('JWT_SECRET environment variable not set');
        }
        $token = $input['refresh_token'];
        if (Token::validate($token, $secret)) {
            $payload = Token::getPayload($token, $secret);
            if ($payload['user_id']) {
                $res = get_users([
                    'meta_key' => Login::REFRESH_TOKEN_META_KEY,
                    'meta_value' => $payload['user_id'],
                    'number' => 1,
                    'count_total' => false,
                    'fields' => 'id'
                ]);
                if ($res[0]) {
                    $user_id = $res[0];
                    return [
                        'token' => $user_id ? Token::create($user_id, $secret, time() + Login::get_token_expire_time(), getenv('WP_HOME')) : null
                    ];
                }
            }
        }
    }
}
