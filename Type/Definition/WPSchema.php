<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use Mohiohio\GraphQLWP\WPType;
use function Stringy\create as s;

trait WPSchema {

    function __construct($config=[]) {
        parent::__construct(static::getSchema($config));
    }

    abstract static function getFieldSchema();

    static function getInstance($config=[]) {
        return WPType::get(get_called_class());
    }

    static function getName() {
        return (new \ReflectionClass(get_called_class()))->getShortName();
    }

    static function getEdgeInstance() {
        return WPType::getEdge(get_called_class(), static::getInstance());
    }

    static function getConnectionInstance() {
        return WPType::getConnection(get_called_class(), static::getInstance());
    }

    static function getSchema($config=[]) {
        return static::getWPSchema($config);
    }

    static function getWPSchema($config=[]) {
        return apply_filters('graphql-wp/get_'.static::getType().'_schema', array_replace_recursive([
            'name' => static::getName(),
            'description' => static::getDescription(),
            'fields' => function() {
                return static::getFieldSchema();
            }
        ],$config));
    }

    static function getDescription() {
        return null;
    }

    static function getType() {
        return (string) str_replace('w_p_','wp_', s(static::getName())->underscored());
    }
}
