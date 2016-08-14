<?php
/**
* Plugin Name: WordPress GraphQL
* Plugin URI: http://www.mohiohio.com/
* Description: GraphQL for WordPress
* Version: 0.1.1
* Author: Tim Field
* Author URI: http://www.mohiohio.com/
* License: BSD
*/
namespace Mohiohio\GraphQLWP;

use \GraphQL\GraphQL;
use \Mohiohio\WordPress\Router;

const ENDPOINT = '/graphql/';

Router::routes([

    ENDPOINT => function() {

        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');

        if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
            $rawBody = file_get_contents('php://input');
            $data = json_decode($rawBody ?: '', true);
        } else {
            $data = $_POST;
        }

        //\Analog::log(print_r($data,true),\Analog::DEBUG);

        $requestString = isset($data['query']) ? $data['query'] : null;
        $operationName = isset($data['operation']) ? $data['operation'] : null;
        $variableValues = !empty($data['variables']) ? $data['variables'] : null;

        if($requestString) {
            try {
                // Define your schema:
                $schema = Schema::build();
                $result = GraphQL::execute(
                    $schema,
                    $requestString,
                    /* $rootValue */ null,
                    $variableValues,
                    $operationName
                );
            } catch (\Exception $exception) {
                $result = [
                    'errors' => [
                        ['message' => $exception->getMessage()]
                    ]
                ];
            }
            echo json_encode($result);
        }

    }
]);
