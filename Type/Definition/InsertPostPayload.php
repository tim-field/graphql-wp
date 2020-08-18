<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use \GraphQL\Type\Definition\ObjectType;

class InsertPostPayload extends WPObjectType {
  static function getFieldSchema() {
    return [
      'post' => [
        'type' => WPPost::getInstance(),
      ]
    ];
  }
}
