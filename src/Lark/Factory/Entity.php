<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Factory;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

/**
 * Entity factory
 *
 * @author Shay Anderson
 */
abstract class Entity
{
	/**
	 * Construct
	 *
	 * @param array $data
	 */
	public function __construct(array $data = null)
	{
		if ($data)
		{
			$this->fromArray($data);
		}
	}

	/**
	 * Validate and get prop type
	 *
	 * @param ReflectionProperty $prop
	 * @param string $class
	 * @return string
	 */
	private static function __propType(ReflectionProperty &$prop, string $class): string
	{
		$type = $prop->getType();

		# must have type declaration
		if ($type === null)
		{
			throw new EntityException(
				'Entity property ' . $class . '::$' . $prop->name
					. ' must have a type declaration'
			);
		}

		# unsupported type
		if (!$type instanceof ReflectionNamedType)
		{
			throw new EntityException(
				'Entity property ' . $class . '::$' . $prop->name
					. ' type is unsupported'
			);
		}

		return $type->getName();
	}

	/**
	 * Validate RC is subclass of Entity
	 *
	 * @param ReflectionClass $rc
	 * @param string $class
	 * @param string $prop
	 * @return void
	 */
	private static function __rcValidateSubclassEntity(
		ReflectionClass &$rc,
		string $class,
		string $prop
	): void
	{
		# must be a subclass of Entity
		if (!$rc->isSubclassOf(Entity::class))
		{
			throw new EntityException(
				'Entity property ' . $class . '::$' . $prop
					. ' has type declaration ' . $rc->name
					. ', which must be subclass of ' . Entity::class
			);
		}
	}

	/**
	 * From array
	 *
	 * @param Entity $object
	 * @param array $data
	 * @return void
	 */
	private static function __fromArray(Entity &$object, array $data): void
	{
		$rc = new ReflectionClass($object);

		foreach ($data as $k => $v)
		{
			# prop must exist
			if (!$rc->hasProperty($k))
			{
				throw new EntityException(
					'Entity property ' . $object::class . '::$' . $k . ' does not exist'
				);
			}

			$prop = $rc->getProperty($k);

			# prop must be public
			if (!$prop->isPublic())
			{
				throw new EntityException(
					'Entity property ' . $object::class . '::$' . $k
						. ' must have public accessibility'
				);
			}

			# null or scalar
			if ($v === null || is_scalar($v))
			{
				$object->{$k} = $v;
			}
			# array (nested)
			else if (is_array($v))
			{
				$propType = self::__propType($prop, $object::class);

				if ($propType === 'array')
				{
					# array type
					$object->{$k} = $v;
					continue;
				}

				# prop class from type (class name)
				$propClass = new ReflectionClass($propType);

				# must be a subclass of Entity (for using Entity::fromArray)
				self::__rcValidateSubclassEntity($propClass, $object::class, $k);

				# create prop object from prop class
				$propObject = $propClass->newInstance();

				$object->{$k} = &$propObject;
				$object->{$k}->fromArray($v);
			}
			# invalid type
			else
			{
				throw new EntityException(
					'Entity property ' . $object::class . '::$' . $k
						. ' value type in array must be null, scalar or array'
				);
			}
		}

		# ensure all object props have been initialized from array data
		foreach ($rc->getProperties() as $prop)
		{
			if (!$prop->isPublic())
			{
				continue;
			}

			if (!$prop->isInitialized($object))
			{
				throw new EntityException(
					'Entity required property ' . $object::class . '::$' . $prop->name
						. ' has not been initialized from array',
					context: [
						'array' => $data
					]
				);
			}
		}
	}

	/**
	 * To array
	 *
	 * @param Entity $object
	 * @param ReflectionClass|null $rc
	 * @return array
	 */
	private static function &__toArray(Entity &$object, ReflectionClass $rc = null): array
	{
		$a = [];

		if (!$rc)
		{
			# root
			$rc = new ReflectionClass($object);
		}

		foreach ($rc->getProperties() as $prop)
		{
			# prop must be public
			if (!$prop->isPublic())
			{
				continue;
			}

			$type = self::__propType($prop, $object::class);

			# prop must be initialized
			if (!$prop->isInitialized($object))
			{
				throw new EntityException(
					'Entity property ' . $object::class . '::$' . $prop->name
						. ' must not be accessed before initialization'
				);
			}

			# basic built-in types
			if (in_array($type, ['array', 'bool', 'float', 'int', 'string']))
			{
				$a[$prop->name] = $prop->getValue($object);
				continue;
			}

			# unsupported types
			if (in_array($type, ['object', 'stdClass']))
			{
				throw new EntityException(
					'Entity property ' . $object::class . '::$' . $prop->name
						. ' type "' . $type . '" is unsupported'
				);
			}

			$propObj = $prop->getValue($object);

			# prop value must be object
			if (!is_object($propObj))
			{
				throw new EntityException(
					'Entity property ' . $object::class . '::$' . $prop->name
						. ' type "' . gettype($propObj) . '" is unsupported'
				);
			}

			$propObjRc = new ReflectionClass($propObj);

			# must be a subclass of Entity
			self::__rcValidateSubclassEntity($propObjRc, $object::class, $prop->name);

			$a[$prop->name] = self::__toArray($propObj, $propObjRc);
		}

		return $a;
	}

	/**
	 * Data used to populate object properties
	 *
	 * @param array $data
	 * @return void
	 */
	final public function fromArray(array $data): void
	{
		self::__fromArray($this, $data);
	}

	/**
	 * Return object properties as array
	 *
	 * @return array
	 */
	final public function &toArray(): array
	{
		return self::__toArray($this);
	}
}
