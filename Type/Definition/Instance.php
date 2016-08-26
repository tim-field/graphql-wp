<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

trait Instance {

    static function getInstance($config=[]) {
        \Mohiohio\GraphQLWP\log("getting Instance in ".get_called_class());
        static $instance;
        \Mohiohio\GraphQLWP\log("have instance ".get_class($instance));
        return $instance ?: $instance = new static($config);
    }
}
