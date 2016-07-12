<?php
/**
* Plugin Name: WordPress GraphQL
* Plugin URI: http://www.mohiohio.com/
* Description: GraphQL for WordPress
* Version: 0.1.2
* Author: Tim Field
* Author URI: http://www.github.com/tim-field
* License: BSD
*/
namespace Mohiohio\GraphQLWP;

require __DIR__.'/autoload.php';

use \GraphQL\GraphQL;
use \Mohiohio\WordPress\Router;
use Mohiohio\GraphQLWP\Exception\RequestException;

const ENDPOINT = '/graphql/';

Router::routes([

    ENDPOINT => function() {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');

        log('content type', $_SERVER['HTTP_CONTENT_TYPE']);

        $contentTypeIsJson = (isset($_SERVER['HTTP_CONTENT_TYPE']) && $_SERVER['HTTP_CONTENT_TYPE'] == 'application/json')
            ||  (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/json');

        log('contentTypeIsJson', $contentTypeIsJson);

        if ($contentTypeIsJson) {
            $rawBody = file_get_contents('php://input');
            try {
              $data = json_decode($rawBody);
            } catch (\Exception $exception) {
              jsonResponse(['errors' => ['message' => 'Decoding body failed. Be sure to send valid json request.']]);
            }

            log('raw data ' , var_export($data, true) );
        } else {
            $data = $_POST;
            //\Analog::log('post ',\Analog::DEBUG);
        }

        //\Analog::log(var_export($data,true),\Analog::DEBUG);


        $requestString = isset($data['query']) ? $data['query'] : null;
        $operationName = isset($data['operation']) ? $data['operation'] : null;
        $variableValues = isset($data['variables']) ?
            ( is_array($data['variables']) ?
                $data['variables'] :
                json_decode($data['variables'],true) ) :
            null;
        log('post', $_POST);
        log('data', $data);

        if($requestString) {
            log("requestString", $requestString);
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
            log('result', $result);
            jsonResponse($result);
        }
        jsonResponse(['errors' => ['message' => 'wrong query format or emtpy query']]);
    }
]);

/**
 * Sends a json object to the client
 * @param  array  $resp response object
 * @return [type]       [description]
 */
function jsonResponse(array $resp) {
  try {
    $jsonResponse = json_encode($resp);
  } catch(\Exception $exception) {
    jsonResponse(['errors' => ['message' => 'Failed to encode to JSON the response.']]);
  }

  echo $jsonResponse;
  exit;
}

function log($message)  {
    $function_args = func_get_args();
    // The first is a simple string message, the others should be var_exportetd
    array_shift($function_args);

    foreach($function_args as $argument) {
        $message .= ' ' . var_export($argument, true);
    }

    // send to sapi
    error_log($message, 4);

}
