<?php
namespace Mohiohio\GraphQLWP\Mutations;
use GraphQLRelay\Relay;

abstract class MutationInterface
{
  static function getName() {
    return (new \ReflectionClass(get_called_class()))->getShortName();
  }

  abstract static function getInputFields();

  abstract static function getOutputFields();

  abstract static function mutateAndGetPayload($input);

  static function init() {
    return Relay::mutationWithClientMutationId([
      'name' => static::getName(),
      'inputFields' => static::getInputFields(),
      'outputFields' => static::getOutputFields(),
      'mutateAndGetPayload' => function($input) {
        return static::mutateAndGetPayload($input);
      }
    ]);
  }
}
