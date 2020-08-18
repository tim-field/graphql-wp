<?php
namespace Mohiohio\GraphQLWP\Mutations;

use Mohiohio\GraphQLWP\Type\Definition\WPTerm;
use Mohiohio\GraphQLWP\Schema;
use Mohiohio\GraphQLWP\Type\Definition\TermInput;
use GraphQLRelay\Connection\ArrayConnection;

class Term extends MutationInterface {

  static function getInputFields() {
    return TermInput::getFieldSchema();
  }

  static function getOutputFields() {
    return [
      'term' => [
        'type' => WPTerm::getInstance(),
        'resolve' => function($payload) {
          return get_term($payload['term_id']);
        }
      ],
      'termEdge' => [
        'type' => WPTerm::getEdgeInstance(),
        'resolve' => function($payload) {
          return [
            'node' => get_term($payload['term_id']),
            'cursor' => ArrayConnection::offsetToCursor($payload['term_id']),
          ];
        }
      ],
      'query' => [
        'type' => Schema::getQuery(),
        'resolve' => function() {
          return Schema::getQuery();
        }
      ],
    ];
  }

  static function mutateAndGetPayload ($input) {
    if (!empty($input['term_id'])) {
      $res = wp_update_term($input['term_id'], $input['taxonomy'] ?? null, $input);
    } else {
      $res = wp_insert_term($input['term'], $input['taxonomy'] ?? '', $input);
    }

    if(is_wp_error($res)) {
      error_log($res->get_error_message());
      throw new \Exception($res->get_error_message());
    }
    return $res;
  }
}
