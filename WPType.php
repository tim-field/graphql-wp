<?php

namespace Mohiohio\GraphQLWP;

class WPType {

    private static $types;

    static function get($className) {
        return self::$types[$className] ?? (self::$types[$className] = self::initType($className));
    }

    protected static function initType($className) {
        if(!class_exists($className)){
            return null;
        }

        return new $className();
    }
}
