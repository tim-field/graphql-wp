<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQLRelay\Relay;

//TODO getTypes should move to parent class and define a getTypes abstract method
class WPPost extends WPInterfaceType {

    const TYPE = 'WP_Post';
    const DEFAULT_TYPE = 'post';

    private static $internalTypes;
    private static $instance;

    static function getInstance($config=[]) {
        return static::$instance ?: static::$instance = new static($config);
    }

    static function init() {
        static::getTypes();
    }

    static function resolveType($obj) {
        if($obj instanceOf \WP_Post){
            return isset(self::$internalTypes[$obj->post_type]) ? self::$internalTypes[$obj->post_type] : self::$internalTypes[self::DEFAULT_TYPE];
        }
    }

    static function getTypes($name = null) {
        if (null === self::$internalTypes) {
            self::$internalTypes = apply_filters('graphql-wp/get_post_types',[ //TODO move to parent
                Post::getPostType() => new Post(),
                Page::getPostType() => new Page(),
                Attachment::getPostType() => new Attachment(),
                Product::getPostType() => new Product(),
                Order::getPostType() => new Order()
            ]);
        }
        return $name ? self::$internalTypes[$name] : self::$internalTypes;
    }

    static function getDescription() {
        return 'The base WordPress post type';
    }

    static function getFieldSchema() {
        $ImageMeta = new ObjectType([
          'name' => 'ImageMeta',
          'isTypeOf' => function() {return true;},
          'fields' => [
            'name' => ['type' => Type::string()],
            'src' => ['type' => Type::string()],
            'classNames' => ['type' => Type::string()]
          ],
          'interfaces' => []
        ]);

        $BodyParagraphs = new ObjectType([
          'name' => 'BodyParagraphs',
          'isTypeOf' => function() {return true;},
          'fields' => [
            'type' => ['type' => Type::string()],
            'content' => ['type' => Type::string()],
            'imageMeta' => ['type' => $ImageMeta]
          ],
          'interfaces' => []
        ]);

        static $schema;
        return $schema ?: $schema = [
            'id' => Relay::globalIdField(self::TYPE, function($post){
                return $post->ID;
            }),
            'ID' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The ID of the post',
            ],
            'name' => [
                'type' => Type::string(),
                'description' => 'The post\'s slug',
                'resolve' => function($post) {
                    return $post->post_name;
                }
            ],
            'title' => [
                'type' => Type::string(),
                'description' => 'The title of the post',
                'resolve' => function($post) {
                    return get_the_title($post);
                }
            ],
            'content' => [
                'type' => Type::string(),
                'description' => 'The full content of the post',
                'resolve' => function($post) {
                    return apply_filters('the_content', get_post_field('post_content', $post));
                }
            ],
            'body' => [
                'type' => Type::listOf($BodyParagraphs),
                'description' => 'The full content of the post',
                'resolve' => function($post) {
                    $rawContent = apply_filters('the_content', get_post_field('post_content', $post));
                    $rawContentFiltered = str_replace('</p>', '', $rawContent);
                    $array = explode('<p>', $rawContentFiltered);
                    $bodyParagraphs = [];
                    foreach ($array as $value) {
                      if (strcmp($value, '')) {
                        $type = 'text';
                        $imageMeta = [];

                        if (strpos($value, '<img') !== false) {
                          $type = 'image';
                          $attrArray = current((array) new \SimpleXMLElement($value));

                          $parts = explode('/', $attrArray['src']);
                          $lastPart = array_pop($parts);
                          if (strpos($lastPart, '-') !== false) {
                            $parts = explode('-', $lastPart);
                          } else if (strpos($lastPart, '.') !== false) {
                            $parts = explode('.', $lastPart);
                          }

                          $imageMeta = [
                            'name' => $parts[0],
                            'src' => $attrArray['src'],
                            'classNames' => $attrArray['class']
                          ];
                        }

                        $bodyParagraphs[] = [
                          'type' => $type,
                          'content' => $value,
                          'imageMeta' => $imageMeta
                        ];
                      }
                    }
                    return $bodyParagraphs;
                }
            ],
            'excerpt' => [
                'type' => Type::string(),
                'description' => 'User-defined post except',
                'args' => [
                    'always' => [
                        'type' => Type::boolean(),
                        'desciption' => 'If true will create an excerpt from post content'
                    ]
                ],
                'resolve' => function($post, $args) {

                    $excerpt = apply_filters('the_excerpt',get_post_field('post_excerpt', $post));

                    if(empty($excerpt) && !empty($args['always'])) {
                        $excerpt = apply_filters('the_excerpt', wp_trim_words( strip_shortcodes( $post->post_content )));
                    }

                    return $excerpt;
                }
            ],
            'date' => [
                'type' => Type::string(),
                'description' => 'Format: 0000-00-00 00:00:00',
                'args' => [
                    'format' => ['type' => Type::string()]
                ],
                'resolve' => function($post, $args) {
                    return !empty($args['format']) ? date($args['format'],strtotime($post->post_date)) : $post->post_date;
                }
            ],
            'date_gmt' => [
                'type' => Type::string(),
                'description' => 'Format: 0000-00-00 00:00:00',
                'args' => [
                    'format' => ['type' => Type::string()]
                ],
                'resolve' => function($post, $args) {
                    return !empty($args['format']) ? date($args['format'],strtotime($post->post_date_gmt)) : $post->post_date_gmt;
                }
            ],
            'status' => [
                'type' => PostStatus::getInstance(),
                'description' => 'Status of the post',
                'resolve' => function($post) {
                    return $post->post_status;
                }
            ],
            'parent' => [
                'type' => function() {
                    return static::getInstance();
                },
                'description' => 'Parent of this post',
                'resolve' => function($post) {
                    return $post->post_parent ? get_post($post->post_parent) : null;
                }
            ],
            'modified' => [
                'type' => Type::string(),
                'description' => 'Format: 0000-00-00 00:00:00',
                'args' => [
                    'format' => ['type' => Type::string()]
                ],
                'resolve' => function($post, $args) {
                    return !empty($args['format']) ? date($args['format'],strtotime($post->post_modified)) : $post->post_modified;
                }
            ],
            'modified_gmt' => [
                'type' => Type::string(),
                'description' => 'Format: 0000-00-00 00:00:00',
                'args' => [
                    'format' => ['type' => Type::string()]
                ],
                'resolve' => function($post, $args) {
                    return !empty($args['format']) ? date($args['format'],strtotime($post->post_modified_gmt)) : $post->post_modified_gmt;
                }
            ],
            'comment_count' => [
                'type' => Type::int(),
                'description' => 'Number of comments on post',
                'resolve' => function($post) {
                    return $post->comment_count;
                }
            ],
            'menu_order' => [
                'type' => Type::int(),
                'resolve' => function($post) {
                    return $post->menu_order;
                }
            ],
            'permalink' => [
                'description' => "Retrieve full permalink for current post ",
                'type' => Type::string(),
                'resolve' => function($post) {
                    return get_permalink($post);
                }
            ],
            'thumbnail_url' => [
                'description' => 'Retrieve the post thumbnail.',
                'type' => Type::string(),
                'args' => [
                    'size' => ['type' => Type::string()]
                ],
                'resolve' => function($post) {
                    return get_the_post_thumbnail_url( $post, isset($args['size']) ? $args['size'] : 'post-thumbnail') ?: null;
                }
            ],
            'attached_media' => [
                'type' => function(){
                    return new ListOfType(Attachment::getInstance());
                },
                'args' => [
                    'type' => ['type' => Type::nonNull(Type::string())]
                ],
                'resolve' => function($post, $args) {
                    return get_attached_media($args['type'], $post->ID);
                }
            ],
            'terms' => [
                'type' => new ListOfType(WPTerm::getInstance()),
                'description' => 'Terms ( Categories, Tags etc ) or this post',
                'args' => [
                    'taxonomy' => [
                        'description' => 'The taxonomy for which to retrieve terms. Defaults to post_tag.',
                        'type' => Type::string(),
                    ],
                    'orderby' => [
                        'description' => "Defaults to name",
                        'type' => Type::string(),
                    ],
                    'order' => [
                        'description' => "Defaults to ASC",
                        'type' => Type::string(),
                    ]
                ],
                'resolve' => function($post, $args) {

                    $args += [
                        'taxonomy' => null,
                        'orderby'=>'name',
                        'order' => 'ASC',
                    ];
                    extract($args);

                    $res = wp_get_post_terms($post->ID, $taxonomy, ['orderby'=>$orderby,'order'=>$order]);

                    return is_wp_error($res) ? [] : $res;
                }
            ],
            'meta_value' => [
                'type' => Type::string(),
                'args' => [
                    'key' => [
                        'description' => 'Post meta key',
                        'type' => Type::nonNull(Type::string()),
                    ]
                ],
                'resolve' => function($post, $args) {
                    return get_post_meta($post->ID,$args['key'],true);
                }
            ]
        ];
    }
}
