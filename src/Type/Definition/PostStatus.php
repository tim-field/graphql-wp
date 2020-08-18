<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use \GraphQL\Type\Definition\EnumType;

class PostStatus extends EnumType {

    private static $instance;

    static function getInstance($config=[]) {
        return static::$instance ?: static::$instance = new static($config);
    }

    function __construct($config=[]) {
        parent::__construct(static::getSchema($config));
    }

    static function getSchema($config) {
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
