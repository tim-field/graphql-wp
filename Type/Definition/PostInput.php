<?php
namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;

class PostInput extends InputObjectType {

  // NOTE use of trait here
  use WPSchema;

  static function getFieldSchema() {
    static $schema;
    return $schema ?: $schema = [
      'ID' => [
        'type' => Type::int(),
        'description' => 'The post ID. If equal to something other than 0, the post with that ID will be updated.'
      ],
      'post_author' => [
        'type' => Type::int(),
        'description' => 'The ID of the user who added the post. Default is the current user ID'
      ],
      'post_content' => [
        'type' => Type::string(),
        'description' => 'The post content. Default empty.'
      ],
      'post_content_filtered' => [
        'type' => Type::string(),
        'description' => 'The filtered post content. Default empty.'
      ],
      'post_title' => [
        'type' => Type::string(),
        'description' => 'The post title. Default empty.'
      ],
      'post_excerpt' => [
        'type' => Type::string(),
        'description' => 'The post excerpt. Default empty.'
      ],
      'post_status' => [
        'type' => PostStatus::getInstance(),
        'description' => 'The post status. Default \'draft\'.'
      ],
      'post_type' => [
        'type' => Type::string(),
        'description' => 'The post type. Default \'post\'.'
      ],
      'comment_status' => [
        'type' => Type::string(),
        'description' => 'Whether the post can accept comments. Accepts \'open\' or \'closed\'. Default is the value of \'default_comment_status\' option.'
      ],
      'ping_status' => [
        'type' => Type::string(),
        'description' => 'Whether the post can accept pings. Accepts \'open\' or \'closed\'. Default is the value of \'default_ping_status\' option.'
      ],
      'post_password' => [
        'type' => Type::string(),
        'description' => 'The password to access the post. Default empty.',
      ],
      'post_name' => [
        'type' => Type::string(),
        'description' => 'The post name. Default is the sanitized post title when creating a new post.',
      ],
      'to_ping' => [
        'type' => Type::string(),
        'description' => 'Space or carriage return-separated list of URLs to ping. Default empty.',
      ],
      'pinged' => [
        'type' => Type::string(),
        'description' => 'Space or carriage return-separated list of URLs that have been pinged. Default empty.',
      ],
      'post_modified' => [
        'type' => Type::string(),
        'description' => 'The date when the post was last modified. Default is the current time.',
      ],
      'post_modified_gmt' => [
        'type' => Type::string(),
        'description' => 'The date when the post was last modified in the GMT timezone. Default is the current time.',
      ],
      'post_parent' => [
        'type' => Type::int(),
        'description' => 'Set this for the post it belongs to, if any. Default 0.',
      ],
      'menu_order' => [
        'type' => Type::string(),
        'description' => 'The order the post should be displayed in. Default 0.',
      ],
      'post_mime_type' => [
        'type' => Type::string(),
        'description' => 'The mime type of the post. Default empty.',
      ],
      'guid' => [
        'type' => Type::string(),
        'description' => 'Global Unique ID for referencing the post. Default empty.',
      ],
      'post_category' => [
        'type' => new ListOfType(Type::string()),
        'description' => 'Array of category names, slugs, or IDs. Defaults to value of the \'default_category\' option.',
      ],
      'tax_input' => [
        'type' => AssociativeArrayType::getInstance(),
        'description' => 'Map of taxonomy terms keyed by their taxonomy name. Default empty.',
      ],
      'meta_input' => [
        'type' => AssociativeArrayType::getInstance(),
        'description' => 'Map of post meta values keyed by their post meta key. Default empty.',
      ]
    ];
  }
}
