<?php
namespace Mohiohio\GraphQLWP;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQLRelay\Relay;

use Mohiohio\GraphQLWP\Type\Definition\WPQuery;
use Mohiohio\GraphQLWP\Type\Definition\WPPost;
use Mohiohio\GraphQLWP\Type\Definition\WPSetting;
use Mohiohio\GraphQLWP\Type\Definition\WPTerm;
use Mohiohio\GraphQLWP\Type\Definition\WPObjectType;

class Schema
{
    static protected $query = null;
    static protected $nodeDefinition = null;

    static function build() {
        static::init();
        return new \GraphQL\Schema([
          'query' => static::getQuery(),
          'mutation' => null,
          'types' => static::getArrayOfTypesWithInterfaces()
        ]);
    }

    static function init() {
        WPPost::init();
        WPTerm::init();
        do_action('graphql-wp/schema_init');
    }

    static function withWooCommerce() {
        return (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins',get_option( 'active_plugins' ))));
    }

    static function getNodeDefinition() {

        return static::$nodeDefinition ?: static::$nodeDefinition = Relay::nodeDefinitions(
        function($globalID) {

            $idComponents = Relay::fromGlobalId($globalID);

            switch ($idComponents['type']){
                case WPPost::TYPE;
                return get_post($idComponents['id']);
                case WPTerm::TYPE;
                return get_term($idComponents['id']);
                default;
                return null;
            }
        },
        function($obj) {

            if ($obj instanceOf \WP_Post) {
                return WPPost::resolveType($obj);
            }
            if ($obj instanceOf \WP_Term) {
                return WPTerm::resolveType($obj);
            }
        });
    }

    static function getArrayOfTypesWithInterfaces() {
      // see: https://github.com/webonyx/graphql-php/blob/master/UPGRADE.md
      return [];
    }

    static function getQuery() {
        return static::$query ?: static::$query = new ObjectType(static::getQuerySchema());
    }

    static function getQuerySchema() {
        $ImageStyle = new ObjectType([
          'name' => 'ImageStyle',
          'isTypeOf' => function() {return true;},
          'fields' => [
            'h' => ['type' => Type::int()],
            'w' => ['type' => Type::int()]
          ],
          'interfaces' => []
        ]);

        $MediaSetting = new ObjectType([
          'name' => 'MediaSetting',
          'isTypeOf' => function() {return true;},
          'fields' => [
            'thumb' => ['type' => $ImageStyle],
            'medium' => ['type' => $ImageStyle],
            'large' => ['type' => $ImageStyle]
          ],
          'interfaces' => []
        ]);

        $Setting = new ObjectType([
          'name' => 'Setting',
          'isTypeOf' => function() {return true;},
          'fields' => [
            'media' => ['type' => $MediaSetting]
          ],
          'interfaces' => []
        ]);

        $schema = apply_filters('graphql-wp/get_query_schema',[
            'name' => 'Query',
            'fields' => [
                'wp_query' => [
                    'type' => WPQuery::getInstance(),
                    'resolve' => function($root, $args) {
                        global $wp_query;
                        return $wp_query;
                    }
                ],
                'wp_settings' => [
                    'type' => $Setting,
                    'resolve' => function($root, $args) {
                        return [
                          'media' => [
                            'thumb' => [
                              'w' => get_option('thumbnail_size_w'),
                              'h' => get_option('thumbnail_size_h')
                            ],
                            'medium' => [
                              'w' => get_option('medium_size_w'),
                              'h' => get_option('medium_size_h')
                            ],
                            'large' => [
                              'w' => get_option('large_size_w'),
                              'h' => get_option('large_size_h')
                            ]
                          ]
                        ];
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
                        if(isset($args['ID'])){
                            return get_post($args['ID']);
                        }
                        return get_page_by_path( $args['slug'], \OBJECT, isset($args['post_type']) ? $args['post_type'] : WPPost::DEFAULT_TYPE );
                    }
                ],
                'term' => [
                    'type' => WPTerm::getInstance(),
                    'args' => [
                        'id' => [
                            'type' => Type::string(),
                            'desciption' => 'Term id'
                        ]
                    ],
                    'resolve' => function($root, $args) {
                        return get_term($args['id']);
                    }
                ],
                'node' => static::getNodeDefinition()['nodeField']
            ]
        ]);

        return $schema;
    }
}
