<?php
/**
* Plugin Name: WordPress GraphQL
* Plugin URI: http://www.mohiohio.com/
* Description: GraphQL for WordPress
* Version: 0.3.0
* Author: Tim Field
* Author URI: http://www.mohiohio.com/
* License: GPL-3
*/
namespace Mohiohio\GraphQLWP;

use GraphQL\GraphQL;
use Mohiohio\WordPress\Router;

const ENDPOINT = '/graphql/';

Router::routes([

    ENDPOINT => function() {

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: content-type');
        header('Content-Type: application/json');

        $contentTypeIsJson = (isset($_SERVER['HTTP_CONTENT_TYPE']) && $_SERVER['HTTP_CONTENT_TYPE'] == 'application/json')
            ||  (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/json');

        log('contentTypeIsJson', $contentTypeIsJson);

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

        $context = null;
        $requestString = isset($data['query']) ? $data['query'] : null;
        $operationName = isset($data['operation']) ? $data['operation'] : null;
        $variableValues = isset($data['variables']) ?
            ( is_array($data['variables']) ?
                $data['variables'] :
                json_decode($data['variables'],true) ) :
            null;

        if($requestString) {
            try {
                // Define your schema:
                $schema = Schema::build();
                $result = GraphQL::execute(
                    $schema,
                    $requestString,
                    /* $rootValue */ null,
                    $context,
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
            //log('result', $result);
            jsonResponse($result);
        }
        jsonResponse(['errors' => ['message' => 'Wrong query format or empty query. Either send raw query _with_ Content-Type: \'application/json\' header or send query by posting www-form-data with a query="query{}..." parameter']]);
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

/**
 * Log a message to the SAPi (terminal) (only when WP_DEBUG is set to true)
 * @param  string $message The message to log to terminal
 * @return [type]          [description]
 */
function log($message)  {
    if (!WP_DEBUG) {
      return;
    }
    $function_args = func_get_args();
    // The first is a simple string message, the others should be var_exported
    array_shift($function_args);

    foreach($function_args as $argument) {
        $message .= ' ' . var_export($argument, true);
    }

    // send to sapi
    error_log($message, 4);

}
