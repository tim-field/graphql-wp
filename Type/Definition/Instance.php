<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

trait Instance {
    
    static $instance;

    static function getInstance($config=[]) {
        return static::$instance ?: static::$instance = new static($config);

    }
}
