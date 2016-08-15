<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use function Stringy\create as s;

trait WPSchema {

    use Instance;

    function __construct($config=[]) {
        parent::__construct(static::getSchema($config));
    }

    abstract static function getFieldSchema();

    static function getSchema($config=[]) {
        return static::getWPSchema($config);
    }

    static function getWPSchema($config=[]) {
        return apply_filters('graphql-wp/get_'.static::getType().'_schema', array_replace_recursive([
            'name' => static::getName(),
            'description' => static::getDescription(),
            'fields' => static::getFieldSchema(),
        ],$config));
    }

    static function getName() {
        return (new \ReflectionClass(get_called_class()))->getShortName();
    }

    static function getDescription() {
        return null;
    }

    static function getType() {
        return s(static::getName())->underscored();
    }
}
