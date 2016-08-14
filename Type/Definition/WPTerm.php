<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use \GraphQL\Type\Definition\InterfaceType;
use \GraphQL\Type\Definition\Type;
use \GraphQL\Type\Definition\ListOfType;
use \GraphQLRelay\Relay;

use function Stringy\create as s;

class WPTerm extends WPInterfaceType {

    const TYPE = 'WP_Term';

    static function getDescription() {
        return 'Base class for taxonomies such as Category & Tag';
    }

    static function resolveType($obj) {
        \Analog::log('resolving type for '.var_export($obj,true));
        \Analog::log('static::$instance is '.var_export((static::$instance)->getName(),true));

        $ObjectType = __NAMESPACE__.'\\'.s($obj->taxonomy)->upperCamelize();

        \Analog::log('Type is '.$ObjectType);

        if(class_exists($ObjectType)){
            return $ObjectType::getInstance();
        }
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
            ]
        ];
    }
}
