<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use \GraphQL\Type\Definition\EnumType;
use \GraphQL\Type\Definition\Type;
use \GraphQL\Type\Definition\ListOfType;
use \GraphQLRelay\Relay;

class PostStatus extends EnumType {

    function __construct($config=[]) {
        parent::__construct($this->getSchema($config));
    }

    function getSchema($config) {
        return apply_filters('grapql-wp/get_post_status_schema',array_replace_recursive([
          'name' => 'PostStatus',
          'description' => 'A valid post status',
          'values' => [
              'publish' => [
                  'value' => 'publish',
                  'description' => 'A published post or page'
              ],
              'pending' => [
                  'value' => 'pending',
                  'description' => 'post is pending review'
              ],
              'draft' => [
                  'value' => 'draft',
                  'description' => 'a post in draft status'
              ],
              'autodraft' => [
                  'name' => 'autodraft',
                  'value' => 'auto-draft',
                  'description' => 'a newly created post, with no content'
              ],
              'future' => [
                  'value' => 'future',
                  'description' => 'a post to publish in the future',
              ],
              'private' => [
                  'value' => 'private',
                  'description' => 'not visible to users who are not logged in'
              ],
              'inherit' => [
                  'value' => 'inherit',
                  'description' => 'a revision.'
              ],
              'trash' => [
                  'value' => 'trash',
                  'description' => 'post is in trashbin'
              ]
          ]
      ],$config));
    }
}
