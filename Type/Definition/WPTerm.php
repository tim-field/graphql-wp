<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;
use GraphQLRelay\Relay;

class WPTerm extends WPInterfaceType {

    const TYPE = 'WP_Term';
    const DEFAULT_TYPE = 'category';

    static $instances;

    static function init() {
        static::$instances = apply_filters('graphql-wp/get_post_types',[
            'category' => new Category,
            'tag' => new Tag,
            'post_format' => new PostFormat
        ]);
    }

    static function resolveType($obj) {
        if($obj instanceOf \WP_Term){
            return isset(static::$instances[$obj->taxonomy]) ? static::$instances[$obj->taxonomy] : static::$instances[self::DEFAULT_TYPE];
        }
    }

    static function getDescription() {
        return 'Base class for taxonomies such as Category & Tag';
    }

    static function getFieldSchema() {
        return [
            'id' => Relay::globalIdField(self::TYPE, function($term) {
                return $term->term_id;
            }),
            'term_id' => ['type' => Type::string()],
            'name' => ['type' => Type::string()],
            'slug' => ['type' => Type::string()],
            'term_taxonomy_id' => ['type' => Type::string()],
            'taxonomy' => ['type' => Type::string()],
            'description' => ['type' => Type::string()],
            'parent' => [
                'type' => function() {
                    return static::getInstance();
                }
            ],
            'children' => [
                'type' => function() {
                    return new ListOfType(static::getInstance());
                },
                'description' => 'retrieves children of the term',
                'resolve' => function($term) {
                    return array_map(function($id) use ($term) {
                        return get_term( $id, $term->taxonomy);
                    }, get_term_children($term->term_id,$term->taxonomy));
                }
            ],
            'image' => [
                'type' => function() {
                    return Attachment::getInstance();
                },
                'resolve' => function($term) {
                    if($thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true )){
                        return get_post($thumbnail_id);
                    }
                }
            ]
        ];
    }
}
