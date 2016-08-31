<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;
use GraphQLRelay\Relay;

class WPTerm extends WPInterfaceType {

    const TYPE = 'WP_Term';
    const DEFAULT_TYPE = 'category';

    private static $instance;
    private static $internalTypes;

    static function getInstance($config=[]) {
        return static::$instance ?: static::$instance = new static($config);
    }

    static function resolveType($obj) {
        if($obj instanceOf \WP_Term){
            return isset(self::$internalTypes[$obj->taxonomy]) ? self::$internalTypes[$obj->taxonomy] : self::$internalTypes[self::DEFAULT_TYPE];
        }
    }

    static function init() {
        static::getTypes();
    }

    static function getTypes($name = null) {
        if (null === self::$internalTypes) {
            // Warning never call Category::getInstance() here, getInstance calls this function
            self::$internalTypes = apply_filters('graphql-wp/get_term_types',[ //TODO move to parent
                Category::TAXONOMY => new Category,
                Tag::TAXONOMY => new Tag,
                PostFormat::TAXONOMY => new PostFormat
            ]);
        }
        return $name ? self::$internalTypes[$name] : self::$internalTypes;
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
            ]
        ];
    }
}
