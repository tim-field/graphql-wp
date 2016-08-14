<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use \GraphQL\Type\Definition\InterfaceType;
use \GraphQL\Type\Definition\Type;
use \GraphQL\Type\Definition\ListOfType;
use \GraphQLRelay\Relay;
use \Mohiohio\GraphQLWP\Schema as WPSchema;

class WPTerm extends InterfaceType {

    const TYPE = 'WP_Term';

    function __construct($config) {
        parent::__construct($this->getSchema($config));
    }

    function getSchema($config) {

        return apply_filters('graphql-wp/get_term_interface_schema', array_replace_recursive([
            'name' => self::TYPE,
            'description' => 'Base class for taxonomies such as Category & Tag',
            'fields' => static::fields()
        ],$config));
    }

    static function fields() {
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
                'type' => function(){
                    return WPSchema::getTermInterfaceType();
                }
            ],
            'children' => [
                'type' => function() {
                    return new ListOfType(WPSchema::getTermInterfaceType());
                },
                'description' => 'retrieves children of the term',
                'resolve' => function($term) {
                    return array_map(function($id) use ($term) {
                        return get_term( $id, $term->taxonomy);
                    }, get_term_children($term->term_id,$term->taxonomy));
                }
            ]
        ];
    }
}
