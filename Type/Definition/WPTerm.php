<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use Mohiohio\GraphQLWP\WPType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\ListOfType;
use GraphQLRelay\Relay;

class WPTerm extends WPInterfaceType {

    const TYPE = 'WP_Term';
    const DEFAULT_TYPE = 'category';

    function resolveType($obj, $context, ResolveInfo $info) {
        if($obj instanceOf \WP_Term){
            return WPType::get(__NAMESPACE__.'\\'.ucfirst($obj->taxonomy)) ?? WPType::get(__NAMESPACE__.'\\'.ucfirst(self::DEFAULT_TYPE));
        }
    }

    static function getDescription() {
        return 'Base class for taxonomies such as Category & Tag';
    }

    static function getFieldSchema() {
        static $schema;
        return $schema ?: $schema = [
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
                'type' => static::getInstance()
            ],
            'children' => [
                'type' => new ListOfType(static::getInstance()),
                'description' => 'retrieves children of the term',
                'resolve' => function($term) {
                    return array_map(function($id) use ($term) {
                        return get_term( $id, $term->taxonomy);
                    }, get_term_children($term->term_id,$term->taxonomy));
                }
            ],
            'image' => [
                'type' => Attachment::getInstance(),
                'args' => [
                    'meta_key' => ['type' => Type::string()]
                ],
                'resolve' => function($term, $args) {
                    $args += ['meta_key' => 'thumbnail_id'];
                    extract($args);
                    if($thumbnail_id = get_term_meta( $term->term_id, $meta_key, true )){
                        return get_post($thumbnail_id);
                    }
                }
            ],
            'ancestors' => [
                'type' => new ListOfType(static::getInstance()),
                'description' => 'retrieves ancestors of the term',
                'resolve' => function($term) {
                    return array_map(function($id) use ($term) {
                        return get_term( $id, $term->taxonomy);
                    }, get_ancestors($term->term_id, $term->taxonomy, 'taxonomy'));
                }
            ]
        ];
    }
}
