<?php

namespace TheFold\GraphQLWP\Type\Definition;

use \GraphQL\Type\Definition\Type;

class ACFImage extends ACFFile {

    function getConfig($config) {
        
        return array_replace_recursive([
            'description' => 'An image from the ACF plugin',

            'fields' => [
                'caption' => [
                    'description' => 'Image caption',
                ],
                'title' => [
                    'description' => 'Image title',
                ],
                'url' => [
                    'description' => 'Image path',
                    'args' => [
                        'size' => [
                            'description' => 'size of the image',
                            'type' => Type::string()
                        ],
                    ],
                    'resolve' => function($field, $args) {
                        $args += [
                            'size' => 'medium'
                        ];
                        $size = $args['size']; 
                       
                        if($size == 'full'){
                            return $field['url'];
                        }
                        
                        return $field['sizes'][$size];
                    }
                ],
                'width' => [
                    'type' => Type::int(),
                    'description' => 'Image width',
                    'args' => [
                        'size' => [
                            'description' => 'size of the image',
                            'type' => Type::string()
                        ],
                    ],
                    'resolve' => function($field) {
                        $args += [
                            'size' => 'medium'
                        ];
                        $size = $args['size']; 

                        return $field['sizes'][$size.'-width'];
                    }
                ],
                'height' => [
                    'type' => Type::int(),
                    'description' => 'Image width',
                    'args' => [
                        'size' => [
                            'description' => 'size of the image',
                            'type' => Type::string()
                        ],
                    ],
                    'resolve' => function($post) {
                        $args += [
                            'size' => 'medium'
                        ];

                        $size = $args['size']; 

                        return $field['sizes'][$size.'-height'];
                    }
                ],
            ]
        ], parent::getConfig($config));
    }

}
