<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

trait Instance {

    static function getInstance($config=[]) {
        static $instance;
        return $instance ?: $instance = new static($config);
    }
}
