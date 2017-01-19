<?php

namespace Mohiohio\GraphQLWP;

class WPType {

    private static $types;

    static function get($className) {
        return self::$types[$className] ?? (self::$types[$className] = self::initType($className));
    }

    protected static function initType($className) {
        if(!class_exists($className)){
            //trigger_error('Unable to find type '.$className, E_USER_WARNING);
            return null;
        }

        return new $className();
    }
}
