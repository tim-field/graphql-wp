<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQLRelay\Relay;
use GraphQLRelay\Connection\ArrayConnection;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;
use Mohiohio\GraphQLWP\Schema;

class WPQuery extends WPObjectType
{

  const TYPE = 'WP_Query';

  static function getDescription()
  {
    return 'deals with the intricacies of a post request on a WordPress blog';
  }

  static function getSchemaInterfaces()
  {
    return [Schema::getNodeDefinition()['nodeInterface']];
  }

  static function getFieldSchema()
  {

    $relayArgs = Relay::connectionArgs();

    $schema = [
      'id' => Relay::globalIdField(self::TYPE, function () {
        return 0;
      }),
      'posts' => [
        'type' => Type::nonNull(WPPost::getConnectionInstance()),
        'args' => static::extendArgs($relayArgs),
        'resolve' => function ($root, $args) {
          return static::resolve($root, $args);
        }
      ],
    ];

    if (Schema::withWooCommerce()) {

      $schema['products'] = [
        'type' => Product::getConnectionInstance(),
        'args' => static::extendArgs([
          'post_type' => [
            'description' => "Retrieves posts by Post Types, default value is 'product'.",
            'type' => new ListOfType(Type::string()),
          ],
          'category_name' => [
            'description' => "Show in this product category slug",
            'type' => Type::string()
          ] + $relayArgs
        ]),
        'resolve' => function ($root, $args) {

          if (!isset($args['post_type'])) {
            $args['post_type'] = 'product';
          }

          if (isset($args['category_name'])) {
            $args['tax_query'] = [
              [
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $args['category_name']
              ]
            ];
            unset($args['category_name']);
          }

          return static::resolve($root, $args);
        }
      ];

      $schema['orders'] = [
        'type' => Order::getConnectionInstance(),
        'args' => static::extendArgs([
          'post_type' => [
            'description' => "Retrieves posts by Post Types, default value is 'shop_order'.",
            'type' => new ListOfType(Type::string()),
          ],
          'order_status' => [
            'description' => "Status of the order, see wc_get_order_statuses()",
            'type' => new ListOfType(Type::string()),
          ] + $relayArgs
        ]),
        'resolve' => function ($root, $args) {

          if (!is_user_logged_in()) {
            return [];
          }

          if (!isset($args['post_type'])) {
            $args['post_type'] = 'shop_order';
          }

          if (isset($args['order_status'])) {
            $args['post_status'] = 'wc-' . $args['order_status'];
          }

          if (!isset($args['post_status'])) {
            $args['post_status'] = 'any';
          }

          //if(!is_super_admin()){ //TODO
          $args['meta_query'][] = [
            'key' => '_customer_user',
            'value' => get_current_user_id(),
          ];
          //}

          return static::resolve($root, $args);
        }
      ];
    }

    return $schema;
  }

  static function resolve($root, $args)
  {
    $relayKeys = array_keys(Relay::connectionArgs());
    $postArgs = array_diff_key($args, $relayKeys);
    $relayArgs = array_intersect_key($args, array_flip($relayKeys));

    if (isset($relayArgs['first'])) {
      $paging = [
        'posts_per_page' => $relayArgs['first'],
        'offset' => isset($relayArgs['after'])
          ? ArrayConnection::cursorToOffset($relayArgs['after']) + 1
          : 0,
      ];
    } else {
      $paging = []; // TODO
    }

    $queryArgs = $postArgs + $paging;

    $res = apply_filters('graphql-wp/get_posts', ['posts' => null, 'total' => 0], $queryArgs);
    $posts = $res['posts'];
    $total = $res['total'];
    if (!is_array($posts)) {
      $get_posts = new \WP_Query;
      $posts = $get_posts->query($queryArgs);
      $total = $get_posts->found_posts;
    }

    return Relay::connectionFromArraySlice($posts, $relayArgs, [
      'sliceStart' => $paging['offset'] ?? 0,
      'arrayLength' => $total
    ]);
  }

  static function extendArgs($args)
  {
    return $args + static::getWPQueryArgs();
  }

  static function getWPQueryArgs()
  {
    static $params;
    return $params ?: $params = [
      'post_type' => [
        'description' => "Retrieves posts by Post Types, default value is 'post'.",
        'type' => new ListOfType(Type::string())
      ],
      'post_status' => [
        'description' => "Default value is 'publish', but if the user is logged in, 'private' is added",
        'type' => new ListOfType(Type::string()) // choosing to keep this as a string instead of Enum to ensure custom post status aren't extra work here.
      ],
      'name' => [
        'description' => "Retrieves post by name",
        'type' => Type::string(),
      ],
      'order' => [
        'description' => "Designates the ascending or descending order of the 'orderby' parameter. Defaults to 'DESC'. An array can be used for multiple order/orderby sets.",
        'type' => Type::string()
      ],
      'orderby' => [
        'description' => "Sort retrieved posts by parameter. Defaults to 'date (post_date)'. One or more options can be passed.",
        'type' => Type::string()
      ],
      's' => [
        'description' => "Show posts based on a keyword search.",
        'type' => Type::string()
      ],
      'cat' => [
        'description' => "Show in this category id",
        'type' => Type::int()
      ],
      'category_name' => [
        'description' => "Show in this category slug",
        'type' => Type::string()
      ],
      'tag' => [
        'description' => "Show in this tag slug",
        'type' => Type::string()
      ],
      'tag_id' => [
        'description' => "Show in this tag id",
        'type' => Type::int()
      ],
      'tax_query' => [
        'type' => AssociativeArrayType::getInstance(),
        'description' => 'Use taxonomy parameters, see https://codex.wordpress.org/Class_Reference/WP_Query#Taxonomy_Parameters'
      ],
      'meta_query' => [
        'type' => AssociativeArrayType::getInstance(),
        'description' => 'Use taxonomy parameters, see https://codex.wordpress.org/Class_Reference/WP_Meta_Query'
      ],
      'post__in' => [
        'description' => 'Array of posts to retrieve by id',
        'type' => new ListOfType(Type::int())
      ],
      'post_parent' => [
        'description' => "Use page id to return only child pages. Set to 0 to return only top-level entries. ",
        'type' => Type::int()
      ]
    ];
  }
}
