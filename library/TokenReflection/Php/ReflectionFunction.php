<?php
/**
 * PHP Token Reflection
 *
 * Version 1.0 beta 2
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this library in the file license.txt.
 *
 * @author Ondřej Nešpor <andrew@andrewsville.cz>
 * @author Jaroslav Hanslík <kukulich@kukulich.cz>
 */

namespace TokenReflection\Php;
use TokenReflection;

use TokenReflection\Broker, TokenReflection\Exception;
use Reflector, ReflectionFunction as InternalReflectionFunction, ReflectionParameter as InternalReflectionParameter;

/**
 * Reflection of a not tokenized but defined function.
 *
 * Descendant of the internal reflection with additional features.
 */
class ReflectionFunction extends InternalReflectionFunction implements IReflection, TokenReflection\IReflectionFunction
{
	/**
	 * Reflection broker.
	 *
	 * @var \TokenReflection\Broker
	 */
	private $broker;

	/**
	 * Function parameter reflections.
	 *
	 * @var array
	 */
	private $parameters;

	/**
	 * Constructor.
	 *
	 * @param string $functionName Function name
	 * @param \TokenReflection\Broker $broker Reflection broker
	 */
	public function __construct($functionName, Broker $broker)
	{
		parent::__construct($functionName);
		$this->broker = $broker;
	}

	/**
	 * Returns the reflection broker used by this reflection object.
	 *
	 * @return \TokenReflection\Broker
	 */
	public function getBroker()
	{
		return $this->broker;
	}

	/**
	 * Magic __get method.
	 *
	 * @param string $key Variable name
	 * @return mixed
	 */
	final public function __get($key)
	{
		return TokenReflection\ReflectionBase::get($this, $key);
	}

	/**
	 * Magic __isset method.
	 *
	 * @param string $key Variable name
	 * @return boolean
	 */
	final public function __isset($key) {
		return TokenReflection\ReflectionBase::exists($this, $key);
	}

	/**
	 * Returns the PHP extension reflection.
	 *
	 * @return \TokenReflection\Php\IReflectionExtension
	 */
	public function getExtension()
	{
		return ReflectionExtension::create(parent::getExtension(), $this->broker);
	}

	/**
	 * Returns function parameters.
	 *
	 * @return array
	 */
	public function getParameters()
	{
		if (null === $this->parameters) {
			$broker = $this->broker;
			$parent = $this;
			$this->parameters = array_map(function(InternalReflectionParameter $parameter) use($broker, $parent) {
				return ReflectionParameter::create($parameter, $broker, $parent);
			}, parent::getParameters());
		}

		return $this->parameters;
	}

	/**
	 * Returns a particular parameter.
	 *
	 * @param integer|string $parameter Parameter name or position
	 * @return \TokenReflection\Php\ReflectionParameter
	 * @throws \TokenReflection\Exception\Runtime If there is no parameter of the given name
	 * @throws \TokenReflection\Exception\Runtime If there is no parameter at the given position
	 */
	public function getParameter($parameter)
	{
		$parameters = $this->getParameters();

		if (is_numeric($parameter)) {
			if (isset($parameters[$parameter])) {
				return $parameters[$parameter];
			} else {
				throw new Exception\Runtime(sprintf('There is no parameter at position "%d" in function "%s".', $parameter, $this->getName()), Exception\Runtime::DOES_NOT_EXIST);
			}
		} else {
			foreach ($parameters as $reflection) {
				if ($reflection->getName() === $parameter) {
					return $reflection;
				}
			}

			throw new Exception\Runtime(sprintf('There is no parameter "%s" in function "%s".', $parameter, $this->getName()), Exception\Runtime::DOES_NOT_EXIST);
		}
	}

	/**
	 * Returns the docblock definition of the function.
	 *
	 * @return string|false
	 */
	public function getInheritedDocComment()
	{
		return $this->getDocComment();
	}

	/**
	 * Returns parsed docblock.
	 *
	 * @return array
	 */
	public function getAnnotations()
	{
		return array();
	}

	/**
	 * Returns a particular annotation value.
	 *
	 * @param string $name Annotation name
	 * @param boolean $forceArray Always return values as array
	 * @return string|array|null
	 */
	public function getAnnotation($name)
	{
		return null;
	}

	/**
	 * Checks if there is a particular annotation.
	 *
	 * @param string $name Annotation name
	 * @return boolean
	 */
	public function hasAnnotation($name)
	{
		return false;
	}

	/**
	 * Returns if the current reflection comes from a tokenized source.
	 *
	 * @return boolean
	 */
	public function isTokenized()
	{
		return false;
	}

	/**
	 * Creates a reflection instance.
	 *
	 * @param \ReflectionFunction Internal reflection instance
	 * @param \TokenReflection\Broker Reflection broker instance
	 * @return \TokenReflection\Php\ReflectionFunction
	 * @throws \TokenReflection\Exception\Runtime If an invalid internal reflection object was provided
	 */
	public static function create(Reflector $internalReflection, Broker $broker)
	{
		if (!$internalReflection instanceof InternalReflectionFunction) {
			throw new Exception\Runtime(sprintf('Invalid reflection instance provided: "%s", ReflectionFunction expected.', get_class($internalReflection)), Exception\Runtime::INVALID_ARGUMENT);
		}

		return $broker->getFunction($internalReflection->getName());
	}
}