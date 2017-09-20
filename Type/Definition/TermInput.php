<?php
namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;

class TermInput extends InputObjectType {

  // NOTE use of trait here
  use WPSchema;

  static function getFieldSchema() {
    static $schema;
    return $schema ?: $schema = [
      'term_id' => [
        'type' => Type::int(),
        'description' => 'The ID of the term.',
      ],
      'term' => [
        'type' => Type::nonNull(Type::string()),
        'description' => 'The term to add or update.'
      ],
      'taxonomy' => [
        'type' => Type::nonNull(Type::string()),
        'description' => 'The taxonomy to which to add the term.'
      ],
      'alias_of' => [
        'type' => Type::string(),
        'description' => 'If exists, will be added to the database along with the term.'
      ],
      'parent' => [
        'type' => Type::int(),
        'description' => 'Will assign value of \'parent\' to the term.'
      ],
      'slug' => [
        'type' => Type::string(),
      ]
    ];
  }
}
