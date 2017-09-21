<?php
namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQL\Error\Error;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use Mohiohio\GraphQLWP\WPType;


class JSONType extends ScalarType {

  public $description =
'The `JSON` scalar type can be used to represent any complex types that can`t be represented by other scalar types.';

	static function getInstance($config=[]) {
		return WPType::get(get_called_class());
	}

	public function serialize($value) {
		return json_encode($value);
	}

	public function parseValue($value) {
		return $this->decode($value);
	}

	public function parseLiteral($valueNode) {
		if (!$valueNode instanceof StringValueNode) {
			throw new Error('Query error: Can only parse strings got: ' . $valueNode->kind, [$valueNode]);
		}

		try {
			$json = $this->decode($valueNode->value);
		}
		catch (Error $e) {
			throw new Error('Query error: Can\'t parse JSON: ' . $valueNode->value, [$valueNode]);
		}

		return $json;
	}

	protected function decode($value) {
		$json = json_decode($value);

		if(is_null($json)) {
			throw new Error('Query error: Can\'t parse JSON: ' . $value);
		}

		return $json;
	}
}
