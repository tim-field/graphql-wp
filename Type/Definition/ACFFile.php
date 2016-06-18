<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use \GraphQL\Type\Definition\ObjectType;
use \GraphQL\Type\Definition\Type;

class ACFFile extends ObjectType {

    function __construct($config) {
        parent::__construct($this->getConfig($config));
    }

    function getConfig($config) {

        return array_replace_recursive([
            'description' => 'A file from the ACF plugin',
            'fields' => [
                'id' => [
                    'type' => Type::string(),
                    'description' => 'File ID'
                ],
                'caption' => [
                    'type' => Type::string(),
                    'description' => 'File caption'
                ],
                'title' => [
                    'type' => Type::string(),
                    'description' => 'File title'
                ],
                'url' => [
                    'type' => Type::string(),
                    'description' => 'File path'
                ],
                'size' => [
                    'type' => Type::string(),
                    'description' => 'File Size',
                    'resolve' => function($file) {
                        return @size_format(filesize( get_attached_file( $file['ID'] )));
                    }
                ],
                'mime_type' => [
                    'type' => Type::string(),
                ],
                'icon' => [
                    'type' => Type::string()
                ]
            ]
        ], $config);
    }

}
