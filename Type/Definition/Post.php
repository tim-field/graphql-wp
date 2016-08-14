<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use \GraphQL\Type\Definition\ObjectType;
use \Mohiohio\GraphQLWP\Schema as WPSchema;

class Post extends ObjectType {

    function __construct($config=[]) {
        parent::__construct($this->getSchema($config));
    }

    function getSchema($config) {

        return apply_filters('graphql-wp/get_'.$this->getType().'_schema', array_replace_recursive([
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'fields' => $this->getFieldSchema(),
            'interfaces' => $this->getInterfaces()
        ],$config));
    }

    function getName() {
        return (new \ReflectionClass($this))->getShortName();
    }

    function getDescription() {
        return 'A standard WordPress blog post';
    }

    function getType() {
        return strtolower($this->getName());
    }

    function getFieldSchema() {
        return WPPost::fields();
    }

    function getInterfaces() {
        return [WPSchema::getPostInterfaceType(), WPSchema::getNodeDefinition()['nodeInterface']];
    }
}
