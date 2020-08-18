<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQL\Type\Definition\Type;

class ProductAttribute extends WPObjectType {

    static function getFieldSchema() {

        return [
            'id' => [
                'type'=>Type::nonNull(Type::int()),
            ],
            'name' => [
                'type'=>Type::nonNull(Type::string()),
            ],
            'option' => [
                'type'=>Type::nonNull(Type::string()),
            ]
        ];
    }
}
