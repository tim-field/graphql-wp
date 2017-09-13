<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQLRelay\Relay;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ResolveInfo;
use Mohiohio\GraphQLWP\Schema;

class WPQuery extends WPObjectType {

  const TYPE = 'WP_Query';

  static function getDescription() {
    return 'deals with the intricacies of a post request on a WordPress blog';
  }

  // function resolveType($obj, $context, ResolveInfo $info) {
  //   if($obj instanceOf \WP_Query) {
  //     global $wp_query;
  //     return $wp_query;
  //   }
  // }

  static function getSchemaInterfaces() {
    return [Schema::getNodeDefinition()['nodeInterface']];
  }

  static function getFieldSchema() {

    $relayAgs = Relay::connectionArgs();

    $schema = [
      'id' => Relay::globalIdField(self::TYPE, function(){
        return 0;
      }),
      'posts' => [
        'type' => WPPost::getConnectionInstance(),
        'args' => static::extendArgs($relayAgs),
        'resolve' => function($root, $args) {
          return static::getPosts($args);
        }
      ],
    ];

    if(Schema::withWooCommerce()) {

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
            ] + $relayAgs
          ]),
          'resolve' => function($root, $args) {

            if(!isset($args['post_type'])) {
              $args['post_type'] = 'product';
            }

            if(isset($args['category_name'])){
              $args['tax_query'] = [
                [
                  'taxonomy' => 'product_cat',
                  'field' => 'slug',
                  'terms' => $args['category_name']
                ]
              ];
              unset($args['category_name']);
            }

            return static::getPosts($args);
          }
        ];

        $orderConnection = Relay::connectionDefinitions([
          'nodeType' => Order::getInstance()
        ]);

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
              ] + $relayAgs
            ]),
            'resolve' => function($root, $args) {

              if(!is_user_logged_in()){
                return [];
              }

              if(!isset($args['post_type'])) {
                $args['post_type'] = 'shop_order';
              }

              if(isset($args['order_status'])) {
                $args['post_status'] = 'wc-'.$args['order_status'];
              }

              if(!isset($args['post_status'])) {
                $args['post_status'] = 'any';
              }

              //if(!is_super_admin()){ //TODO
              $args['meta_query'][] = [
                'key' => '_customer_user',
                'value' => get_current_user_id(),
              ];
              //}

              return static::getPosts($args);
            }
        ];
      }

      return $schema;
    }

    static function getPosts($args) {
      $relayKeys = array_keys(Relay::connectionArgs());
      $postArgs = array_diff_key($args, $relayKeys);
      $relayArgs = array_intersect_key($args, $relayKeys);
      $posts = get_posts($postArgs);
      return Relay::connectionFromArray($posts, $relayArgs);
    }

    static function extendArgs($args) {
      return $args + static::getWPQueryArgs();
    }

    static function getWPQueryArgs() {
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
      ];
    }


}
