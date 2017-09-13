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

    static function getArgs() {
        return [
          'taxonomies' => [
            'description' => 'Array of Taxonomy names. Overides taxonomy argument',
            'type' => new ListOfType(Type::string()),
          ],
          'taxonomy' => [
            'description' => 'The taxonomy for which to retrieve terms. Defaults to category',
            'type' => Type::string(),
          ],
          'orderby' => [
            'description' => "Field(s) to order terms by. Accepts term fields ('name', 'slug', 'term_group', 'term_id', 'id', 'description'), 'count' for term taxonomy count, 'include' to match the 'order' of the include param, or 'none' to skip ORDER BY. Defaults to 'name'",
            'type' => Type::string()
          ],
          'order' => [
            'description' => "Whether to order terms in ascending or descending order. Accepts 'ASC' (ascending) or 'DESC' (descending). Default 'ASC",
            'type' => Type::string()
          ],
          'hide_empty' => [
            'description' => "Whether to order terms in ascending or descending order. Accepts 'ASC' (ascending) or 'DESC' (descending). Default 'ASC'",
            'type' => Type::string()
          ],
          'include' => [
            'description' => "Array of term ids to include. Default empty array",
            'type' => new ListOfType(Type::int()),
          ],
          'exclude' => [
            'description' => "Array of term ids to exclude. Default empty array",
            'type' => new ListOfType(Type::int())
          ],
          'exclude_tree' => [
            'description' => "Term ids to exclude along with all of their descendant terms. If include is non-empty, exclude_tree is ignored",
            'type' => new ListOfType(Type::int())
          ],
          'number' => [
            'description' => "Maximum number of terms to return. Default 0 (all)",
            'type' => Type::int()
          ],
          'offset' => [
            'description' => "The number by which to offset the terms query.",
            'type' => Type::int()
          ],
          'name' => [
            'description' => "Array of names to return terms for",
            'type' => new ListOfType(Type::string())
          ],
          'slug' => [
            'description' => "Array of slugs to return terms for",
            'type' => new ListOfType(Type::string())
          ],
          'hierarchical' => [
            'description' => "Whether to include terms that have non-empty descendants (even if hide_empty is set to true). Default true",
            'type' => new ListOfType(Type::boolean())
          ],
          'search' => [
            'description' => "Search criteria to match terms. Will be SQL-formatted with wildcards before and after.",
            'type' => Type::string()
          ],
          'name__like' => [
            'description' => "Retrieve terms with criteria by which a term is LIKE name__like",
            'type' => Type::string()
          ],
          'description__like' => [
            'description' => "Retrieve terms where the description is LIKE description__like",
            'type' => Type::string()
          ],
          'pad_counts' => [
            'description' => "Whether to pad the quantity of a term's children in the quantity of each term's \"count\" object variable. Default false",
            'type' => Type::boolean()
          ],
          'get' => [
            'description' => "Whether to return terms regardless of ancestry or whether the terms are empty. Accepts 'all' or empty (disabled).",
            'type' => Type::boolean()
          ],
          'child_of' => [
            'description' => "Term ID to retrieve child terms of. If multiple taxonomies are passed, child_of is ignored. Default 0",
            'type' => Type::int()
          ],
          'parent' => [
            'description' => "Parent term ID to retrieve direct-child terms of.",
            'type' => Type::int()
          ],
          'childless' => [
            'description' => "True to limit results to terms that have no children. This parameter has no effect on non-hierarchical taxonomies. Default false.",
            'type' => Type::boolean()
          ],
        ];
      }
}
