<?php

namespace Mohiohio\GraphQLWP;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;
use GraphQLRelay\Relay;

use Mohiohio\GraphQLWP\Type\Definition\WPQuery;
use Mohiohio\GraphQLWP\Type\Definition\WPPost;
use Mohiohio\GraphQLWP\Type\Definition\WPTerm;

use Mohiohio\GraphQLWP\Type\Definition\Post;
use Mohiohio\GraphQLWP\Type\Definition\Page;
use Mohiohio\GraphQLWP\Type\Definition\Attachment;
use Mohiohio\GraphQLWP\Type\Definition\Product;
use Mohiohio\GraphQLWP\Type\Definition\Order;
use Mohiohio\GraphQLWP\Type\Definition\Category;
use Mohiohio\GraphQLWP\Type\Definition\Tag;
use Mohiohio\GraphQLWP\Type\Definition\User;
use Mohiohio\GraphQLWP\Type\Definition\PostFormat;
// use Mohiohio\GraphQLWP\Type\Definition\PostInput;
use Mohiohio\GraphQLWP\Type\Definition\MenuItem;
use Mohiohio\GraphQLWP\Type\Definition\BlogInfo;

use Mohiohio\GraphQLWP\Mutations\Post as PostMutation;
use Mohiohio\GraphQLWP\Mutations\Term as TermMutation;
use Mohiohio\GraphQLWP\Mutations\Login;
use Mohiohio\GraphQLWP\Mutations\RefreshToken;

class Schema
{
  static protected $query = null;
  static protected $mutation = null;
  static protected $nodeDefinition = null;

  static function build()
  {
    // Add WooCommerce Schema if required
    add_filter('graphql-wp/schema-types', function ($types) {
      if (self::withWooCommerce()) {
        return $types + [
          Order::getInstance(),
          Product::getInstance(),
        ] + $types;
      }
      return $types;
    });

    return new \GraphQL\Type\Schema([
      'query' => static::getQuery(),
      'mutation' => static::getMutation(),
      'types' => apply_filters('graphql-wp/get_post_types', apply_filters('graphql-wp/schema-types', [
        WPPost::getInstance(),
        WPTerm::getInstance(),
        Post::getInstance(),
        Page::getInstance(),
        Attachment::getInstance(),
        Category::getInstance(),
        Tag::getInstance(),
        PostFormat::getInstance(),
        User::getInstance()
      ]))
    ]);
  }

  static function withWooCommerce()
  {
    return (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))));
  }

  static function getNodeDefinition()
  {

    return static::$nodeDefinition ?: static::$nodeDefinition = Relay::nodeDefinitions(
      function ($globalID) {

        $idComponents = Relay::fromGlobalId($globalID);

        switch ($idComponents['type']) {
          case WPPost::TYPE;
            return get_post($idComponents['id']);
          case WPTerm::TYPE;
            return get_term($idComponents['id']);
          case User::TYPE;
            return get_user_by('id', $idComponents['id']);
            // case WPQuery::TYPE;
            // global $wp_query;
            // return $wp_query;
          default;
            return null;
        }
      },
      function ($obj) {

        if ($obj instanceof \WP_Post) {
          return WPPost::resolveType($obj);
        }
        if ($obj instanceof \WP_Term) {
          return WPTerm::resolveType($obj);
        }
      }
    );
  }

  static function getQuery()
  {
    return static::$query ?: static::$query = new ObjectType(static::getQuerySchema());
  }

  static function getQuerySchema()
  {

    $schema = apply_filters('graphql-wp/get_query_schema', [
      'name' => 'Query',
      'fields' => function () {
        return [
          'id' => Relay::globalIdField('Query', function () {
            return 0;
          }),
          'wp_query' => [
            'type' => WPQuery::getInstance(),
            'resolve' => function ($root, $args) {
              global $wp_query;
              return $wp_query;
            }
          ],
          'wp_post' => [
            'type' => WPPost::getInstance(),
            'args' => [
              'ID' => [
                'name' => 'ID',
                'description' => 'id of the post',
                'type' => Type::int()
              ],
              'slug' => [
                'name' => 'slug',
                'description' => 'name of the post',
                'type' => Type::string()
              ],
              'post_type' => [
                'name' => 'post_type',
                'description' => 'type of the post',
                'type' => Type::string()
              ]
            ],
            'resolve' =>  function ($root, $args) {
              if (isset($args['ID'])) {
                return get_post($args['ID']);
              }
              return get_page_by_path($args['slug'], 'OBJECT', isset($args['post_type']) ? $args['post_type'] : WPPost::DEFAULT_TYPE);
            }
          ],
          'term' => [
            'type' => WPTerm::getInstance(),
            'args' => [
              'id' => [
                'type' => Type::string(),
                'description' => 'Term id'
              ]
            ],
            'resolve' => function ($root, $args) {
              return get_term($args['id']);
            }
          ],
          'menu' => [
            'type' => new ListOfType(MenuItem::getInstance()),
            'args' => [
              'name' => [
                'type' => Type::nonNull(Type::string()),
                'description' => "Menu 'id','name' or 'slug'"
              ]
            ],
            'resolve' => function ($root, $args) {
              return wp_get_nav_menu_items($args['name']) ?: [];
            }
          ],
          'bloginfo' => [
            'type' => BlogInfo::getInstance(),
            'resolve' => function ($root, $args) {
              return isset($args['filter']) ? $args['filter'] : 'raw';
            }
          ],
          'home_page' => [
            'type' => WPPost::getInstance(),
            'resolve' => function () {
              return get_post(get_option('page_on_front'));
            }
          ],
          'terms' => [
            'type' => WPTerm::getConnectionInstance(),
            'description' => 'Retrieve the terms in a given taxonomy or list of taxonomies. ',
            'args' => WPTerm::getArgs(),
            'resolve' => function ($root, $args) {
              return WPTerm::resolve($root, $args);
            }
          ],
          'current_user'  => [
            'type' => User::getInstance(),
            'resolve' => function () {
              $user = wp_get_current_user();
              if ($user->ID) {
                return $user;
              }
            }
          ],
          'query' => [
            'type' => static::getQuery(),
            'description' => 'Query node one level deep, makes working with Relay easier',
            'resolve' => function () {
              return static::getQuery();
            }
          ],
          'node' => static::getNodeDefinition()['nodeField'],
        ];
      }
    ]);

    return $schema;
  }

  static function getMutation()
  {
    return static::$mutation ?: static::$mutation = new ObjectType(static::getMutationSchema());
  }

  static function getMutationSchema()
  {
    return apply_filters('graphql-wp/get_mutation_schema', [
      'name' => 'Mutation',
      'fields' => function () {
        return [
          'save_post' => PostMutation::init(),
          'save_term' => TermMutation::init(),
          'login' => Login::init(),
          'refresh_token' => RefreshToken::init()
        ];
      }
    ]);
  }
}
