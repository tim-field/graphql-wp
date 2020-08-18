<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQL\Type\Definition\Type;

class OrderLineItem extends WPObjectType {

    static function getFieldSchema() {

        return [
            'id'  => ['type' => Type::int()],
            'name'  => ['type' => Type::string()],
            'title' => [
                'type' => Type::string(),
                'resolve' => function($lineData) {

                    $title =  $lineData['name'];

                    if(isset($lineData['meta'])){

                        $metaValues = array_map(function($meta){
                            return $meta['value'];
                        },$lineData['meta']);

                        if($metaValues) {
                            $title .= '; '.implode(', ',$metaValues);
                        }
                    }

                    return $title;
                }
            ],
            'sku'  => ['type' => Type::string()],
            'product_id'  => ['type' => Type::int()],
            //'product'  => ['type' => Type::string()],// Todo
            'variation_id'  => ['type' => Type::int()],
            //'variation'  => ['type' => Type::int()],// Todo
            'quantity'  => ['type' => Type::int()],
            'tax_class'  => ['type' => Type::string()],
            'price'  => ['type' => Type::string()],
            'subtotal'  => ['type' => Type::string()],
            'subtotal_tax'  => ['type' => Type::string()],
            'total'  => ['type' => Type::string()],
            'total_tax'  => ['type' => Type::string()],
            //'taxes'  => ['type' => Type::string()], todo
			//'meta'         => $item_meta todo
        ];
    }

}
