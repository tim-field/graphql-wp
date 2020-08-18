<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQL\Type\Definition\Type;

class BlogInfo extends WPObjectType {

    static function getFieldSchema() {

        $type = ['type' => Type::string(), 'resolve' => function($filter, $args, $resolveInfo) {
            return get_bloginfo($resolveInfo->fieldName, isset($args['filter']) ?: $filter );
        }];

        return [
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
        ];
    }
}
