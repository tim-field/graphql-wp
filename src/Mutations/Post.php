<?php
namespace Mohiohio\GraphQLWP\Mutations;

use Mohiohio\GraphQLWP\Type\Definition\WPPost;
use Mohiohio\GraphQLWP\Type\Definition\WPQuery;
use Mohiohio\GraphQLWP\Type\Definition\PostInput;
use GraphQLRelay\Connection\ArrayConnection;

class Post extends MutationInterface {

  static function getInputFields() {
    return PostInput::getFieldSchema();
  }

  static function getOutputFields() {
    return [
      'post' => [
        'type' => WPPost::getInstance(),
        'resolve' => function($payload) {
          return get_post($payload['postID']);
        }
      ],
      'postEdge' => [
        'type' => WPPost::getEdgeInstance(),
        'resolve' => function($payload) {
          return [
            'node' => get_post($payload['postID']),
            'cursor' => ArrayConnection::offsetToCursor($payload['postID']),
          ];
        }
      ],
      'wp_query' => [
        'type' => WPQuery::getInstance(),
        'resolve' => function() {
          global $wp_query;
          return $wp_query;
        }
      ]
    ];
  }

  static function mutateAndGetPayload ($input) {
    $res = wp_insert_post($input, true);
    if(is_wp_error($res)) {
      throw new \Exception($res->get_error_message());
    }
    return [
      'postID' => $res
    ];
  }
}
