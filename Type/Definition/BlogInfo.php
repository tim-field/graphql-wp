<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;

class BlogInfo extends ObjectType {

    function __construct($config=[]) {
        parent::__construct($this->getSchema($config));
    }

    function getSchema($config) {

        $type = ['type' => Type::string(), 'resolve' => function($filter, $args, $resolveInfo) {
            return get_bloginfo($resolveInfo->fieldName, isset($args['filter']) ?: $filter );
        }];

        return apply_filters('graphql-wp/get_wp_query_schema', array_replace_recursive([
            'name' => 'BlogInfo',
            'description' => 'blog info stuff',
            'fields' => [
                'url' => $type,
                'wpurl' => $type,
                'description' => $type,
                'rdf_url' => $type,
                'rss_url' => $type,
                'atom_url' => $type,
                'comments_atom_url' => $type,
                'comments_rss2_url' => $type,
                'admin_email' => $type,
                'blogname' => $type,
            ]
        ],$config));
    }
}
