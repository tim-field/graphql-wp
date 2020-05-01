<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\ListOfType;
use GraphQLRelay\Relay;
use Mohiohio\GraphQLWP\WPType;

class WPPost extends WPInterfaceType
{

    const TYPE = 'WP_Post';
    const DEFAULT_TYPE = 'post';

    function resolveType($obj, $context, ResolveInfo $info)
    {
        if ($obj instanceof \WP_Post) {
            return WPType::get(__NAMESPACE__ . '\\' . ucfirst($obj->post_type)) ?? WPType::get(__NAMESPACE__ . '\\' . ucfirst(self::DEFAULT_TYPE));
        }
    }

    static function getDescription()
    {
        return 'The base WordPress post type';
    }

    static function getFieldSchema()
    {
        static $schema;
        return $schema ?: $schema = [
            'id' => Relay::globalIdField(self::TYPE, function ($post) {
                return $post->ID;
            }),
            'ID' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The ID of the post',
            ],
            'author' => [
                'type' => User::getInstance(),
                'description' => 'The author of the post',
                'resolve' => function ($post) {
                    return get_user_by('id', $post->post_author);
                }
            ],
            'name' => [
                'type' => Type::string(),
                'description' => 'The post\'s slug',
                'resolve' => function ($post) {
                    return $post->post_name;
                }
            ],
            'title' => [
                'type' => Type::string(),
                'description' => 'The title of the post',
                'resolve' => function ($post) {
                    return get_the_title($post);
                }
            ],
            'content' => [
                'type' => Type::string(),
                'description' => 'The full content of the post',
                'args' => [
                    'no_filter' => ['type' => Type::boolean()]
                ],
                'resolve' => function ($post, $args) {
                    if (!empty($args['no_filter'])) {
                        return get_post_field('post_content', $post);
                    }
                    return apply_filters('the_content', get_post_field('post_content', $post));
                }
            ],
            'excerpt' => [
                'type' => Type::string(),
                'description' => 'User-defined post excerpt',
                'args' => [
                    'always' => [
                        'type' => Type::boolean(),
                        'desciption' => 'If true will create an excerpt from post content'
                    ]
                ],
                'resolve' => function ($post, $args) {

                    $excerpt = apply_filters('the_excerpt', get_post_field('post_excerpt', $post));

                    if (empty($excerpt) && !empty($args['always'])) {
                        $excerpt = apply_filters('the_excerpt', wp_trim_words(strip_shortcodes($post->post_content)));
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
                'resolve' => function ($post, $args) {
                    return !empty($args['format']) ? date($args['format'], strtotime($post->post_date)) : $post->post_date;
                }
            ],
            'date_gmt' => [
                'type' => Type::string(),
                'description' => 'Format: 0000-00-00 00:00:00',
                'args' => [
                    'format' => ['type' => Type::string()]
                ],
                'resolve' => function ($post, $args) {
                    return !empty($args['format']) ? date($args['format'], strtotime($post->post_date_gmt)) : $post->post_date_gmt;
                }
            ],
            'status' => [
                'type' => static::getInstance(),
                'description' => 'Status of the post',
                'resolve' => function ($post) {
                    return $post->post_status;
                }
            ],
            'parent' => [
                'type' => static::getInstance(),
                'description' => 'Parent of this post',
                'resolve' => function ($post) {
                    return $post->post_parent ? get_post($post->post_parent) : null;
                }
            ],
            'children' => [
                'type' => new ListOfType(static::getInstance()),
                'description' => 'retrieves attachments, revisions, or sub-pages, by post parent.',
                'args' => [
                    'number_posts' => ['type' => Type::int()],
                    'post_type' => ['type' => Type::string()],
                    'post_status' => ['type' => Type::string()],
                    'post_mime_type' => ['type' => Type::string()],
                ],
                'resolve' => function ($post, $args) {
                    $args['post_parent'] = $post->ID;
                    if (empty($args['post_status'])) {
                        $args['post_status'] = 'any';
                    }
                    return get_children($args);
                }
            ],
            'modified' => [
                'type' => Type::string(),
                'description' => 'Format: 0000-00-00 00:00:00',
                'args' => [
                    'format' => ['type' => Type::string()]
                ],
                'resolve' => function ($post, $args) {
                    return !empty($args['format']) ? date($args['format'], strtotime($post->post_modified)) : $post->post_modified;
                }
            ],
            'modified_gmt' => [
                'type' => Type::string(),
                'description' => 'Format: 0000-00-00 00:00:00',
                'args' => [
                    'format' => ['type' => Type::string()]
                ],
                'resolve' => function ($post, $args) {
                    return !empty($args['format']) ? date($args['format'], strtotime($post->post_modified_gmt)) : $post->post_modified_gmt;
                }
            ],
            'comment_count' => [
                'type' => Type::int(),
                'description' => 'Number of comments on post',
                'resolve' => function ($post) {
                    return $post->comment_count;
                }
            ],
            'menu_order' => [
                'type' => Type::int(),
                'resolve' => function ($post) {
                    return $post->menu_order;
                }
            ],
            'permalink' => [
                'description' => "Retrieve full permalink for current post ",
                'type' => Type::string(),
                'resolve' => function ($post) {
                    return get_permalink($post);
                }
            ],
            'thumbnail_url' => [
                'description' => 'Retrieve the post thumbnail.',
                'type' => Type::string(),
                'args' => [
                    'size' => ['type' => Type::string()]
                ],
                'resolve' => function ($post, $args) {
                    return get_the_post_thumbnail_url($post, isset($args['size']) ? $args['size'] : 'post-thumbnail') ?: null;
                }
            ],
            'attached_media' => [
                'type' => new ListOfType(Attachment::getInstance()),
                'args' => [
                    'type' => ['type' => Type::nonNull(Type::string())]
                ],
                'resolve' => function ($post, $args) {
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
                'resolve' => function ($post, $args) {

                    $args += [
                        'taxonomy' => null,
                        'orderby' => 'name',
                        'order' => 'ASC',
                    ];
                    extract($args);

                    $res = wp_get_post_terms($post->ID, $taxonomy, ['orderby' => $orderby, 'order' => $order]);

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
                'resolve' => function ($post, $args) {
                    return get_post_meta($post->ID, $args['key'], true);
                }
            ]
        ];
    }
}
