<?php

namespace Mohiohio\GraphQLWP;

use GraphQLRelay\Relay;

class WPType {

    private static $types;

    static function get($className) {
        return self::$types[$className] ?? (self::$types[$className] = self::initType($className));
    }

    static function getEdge($className, $nodeType) {
        // error_log($className.' '.print_r(self::$types, true));
        return self::$types[$className.'Edge'] ??
          (self::$types[$className.'Edge'] = Relay::edgeType(['nodeType' => $nodeType]));
    }

    static function getConnection($className, $nodeType) {
      $key = $className.'Connection';
      return self::$types[$key] ??
          (self::$types[$key] = Relay::connectionType([
            'nodeType' => $nodeType,
            'edgeType' => static::getEdge($className, $nodeType)
            //'edgeType' => $nodeType::getEdgeInstance()
          ]));
    }

    protected static function initType($className) {
      if(!class_exists($className)){
        return null;
      }

      return new $className();
    }
}
