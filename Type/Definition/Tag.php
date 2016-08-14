<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQL\Type\Definition\ObjectType;
use Mohiohio\GraphQLWP\Schema as WPSchema;
use function Stringy\create as s;

class Tag extends ObjectType {

    function __construct($config=[]) {
        parent::__construct($this->getSchema($config));
    }

    function getSchema($config) {

        return apply_filters('graphql-wp/get_term_'.$this->getType().'_schema', array_replace_recursive([
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
        return "The \'post_tag\' taxonomy is similar to categories, but more free form.";
    }

    function getType() {
        return s($this->getName())->underscored();
    }

    function getFieldSchema() {
        return WPTerm::fields();
    }

    function getInterfaces() {
        return [WPSchema::getTermInterfaceType(), WPSchema::getNodeDefinition()['nodeInterface']];
    }
}
