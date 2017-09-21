<?php
namespace Mohiohio\GraphQLWP\Type\Definition;

class AssociativeArrayType extends JSONType {

  public $description =
'The `AssociativeArray` scalar type can be used to represent PHP\'s Associative Arrays ( Maps ). Pass a JSON string which will be decoded with the assoc flag';

	protected function decode($value) {
		$json = json_decode($value, true);

		if(is_null($json)) {
			throw new Error('Query error: Can\'t parse JSON: ' . $value);
		}

		return $json;
	}
}
