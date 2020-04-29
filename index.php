<?php

/**
 * Plugin Name: WordPress GraphQL
 * Plugin URI: http://www.mohiohio.com/
 * Description: GraphQL for WordPress
 * Version: 0.3.1
 * Author: Tim Field
 * Author URI: http://www.mohiohio.com/
 * License: GPL-3
 */

namespace Mohiohio\GraphQLWP;

use GraphQL\GraphQL;
use Mohiohio\WordPress\Router;
use ReallySimpleJWT\Token;

const ENDPOINT = '/graphql/';

if (file_exists(__DIR__ . '/vendor')) {
    // echo 'autoloading vendor';
    require __DIR__ . '/vendor/autoload.php';
}

Router::routes([

    ENDPOINT => function () {

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return '';
        }

        header('Content-Type: application/json');

        $contentTypeIsJson = (isset($_SERVER['HTTP_CONTENT_TYPE']) && $_SERVER['HTTP_CONTENT_TYPE'] == 'application/json')
            ||  (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/json');

        if ($contentTypeIsJson) {
            $rawBody = file_get_contents('php://input');

            try {
                $data = json_decode($rawBody, true);
            } catch (\Exception $exception) {
                jsonResponse(['errors' => ['message' => 'Decoding body failed. Be sure to send valid json request.']]);
            }

            // Decoded response is still empty
            if (strlen($rawBody) > 0 && null === $data) {
                jsonResponse(['errors' => ['message' => 'Decoding body failed. Be sure to send valid json request. Check for line feeds in json (replace them with "\n" or remove them)']]);
            }
        } else {
            $data = $_POST;
        }

        $requestString = isset($data['query']) ? $data['query'] : null;
        $operationName = isset($data['operation']) ? $data['operation'] : null;
        $variableValues = isset($data['variables']) ?
            (is_array($data['variables']) ?
                $data['variables'] :
                json_decode($data['variables'], true)) :
            null;

        if ($requestString) {
            try {
                do_action('graphql-wp/before-execute', $requestString);
                // Define your schema:
                $schema = Schema::build();
                $result = GraphQL::executeQuery(
                    $schema,
                    $requestString,
                    /* $rootValue */
                    null,
                    /* $contextValue */
                    null,
                    $variableValues,
                    $operationName
                )->toArray();
                do_action('graphql-wp/after-execute', $result);
            } catch (\Exception $exception) {
                $result = [
                    'errors' => [
                        ['message' => $exception->getMessage()]
                    ]
                ];
            }
            //log('result', $result);
            jsonResponse($result);
        }
        jsonResponse(['errors' => ['message' => 'Wrong query format or empty query. Either send raw query _with_ Content-Type: \'application/json\' header or send query by posting www-form-data with a query="query{}..." parameter']]);
    },

    '/graphiql/' => function () {
        // todo check login level
        if (current_user_can('administrator')) {
            include __DIR__ . '/graphiql.html';
        } else {
            header("HTTP/1.1 401 Unauthorized");
        }
    },

    'debugit' => function () {
        $secret = getenv('JWT_SECRET', true);
        if ($_SERVER['HTTP_AUTHORIZATION'] && $secret) {
            $token = explode(' ', $_SERVER['HTTP_AUTHORIZATION'])[1];

            print_r($_SERVER);

            // See https://github.com/RobDWaller/ReallySimpleJWT#error-messages-and-codes
            if (Token::validate($token, $secret)) {
                $result = Token::getPayload($token, $secret);
                $user = new \WP_User($result['data']['ID']);
                print_r($user);
                return $user;
            }
        }
    }

]);

add_filter('authenticate', function ($user) {
    $secret = getenv('JWT_SECRET', true);
    if (!empty($_SERVER['HTTP_AUTHORIZATION']) && $secret) {
        $token = explode(' ', $_SERVER['HTTP_AUTHORIZATION'])[1];

        // See https://github.com/RobDWaller/ReallySimpleJWT#error-messages-and-codes
        if ($token && Token::validate($token, $secret)) {
            $payload = Token::getPayload($token, $secret);
            $user = new \WP_User($payload['data']['ID']);
            return $user;
        }
    }
}, 10, 3);

add_action('after_setup_theme', function () {
    $secret = getenv('JWT_SECRET', true);
    if (!empty($_SERVER['HTTP_AUTHORIZATION']) && $secret) {
        $res = wp_signon([], false);
        if ($res && !is_wp_error($res)) {
            wp_set_current_user($res->ID);
        }
    }
});

/**
 * Sends a json object to the client
 * @param  array  $resp response object
 * @return [type]       [description]
 */
function jsonResponse(array $resp)
{
    try {
        $jsonResponse = json_encode($resp);
    } catch (\Exception $exception) {
        jsonResponse(['errors' => ['message' => 'Failed to encode to JSON the response.']]);
    }

    echo $jsonResponse;
    exit;
}
