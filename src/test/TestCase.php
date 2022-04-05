<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test;

use Codeception\Test\Unit;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionObject;
use yii\test\Fixture;

/**
 * Class TestCase
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2.0
 */
class TestCase extends Unit
{
    /**
     * @return array[]
     * @phpstan-return array{class:class-string<Fixture>}[]
     */
    public function _fixtures(): array
    {
        return [];
    }

    /**
     * Returns a callable that checks whether the passed in object is an instance of the $class param
     *
     * @param string $class
     * @phpstan-param class-string $class
     * @return callable
     */
    public function assertObjectIsInstanceOfClassCallback(string $class): callable
    {
        return function($object) use ($class) {
            $this->assertSame($class, get_class($object));
        };
    }

    /**
     * Sets an inaccessible object property to a designated value.
     *
     * @param object|string $object
     * @param string $propertyName
     * @param mixed $value
     * @param bool $revoke whether to make property inaccessible after setting
     * @throws ReflectionException
     * @credit https://github.com/yiisoft/yii2/blob/master/tests/TestCase.php#L155
     */
    protected function setInaccessibleProperty(object|string $object, string $propertyName, mixed $value, bool $revoke = true): void
    {
        $class = new ReflectionClass($object);

        while (!$class->hasProperty($propertyName)) {
            $class = $class->getParentClass();
        }

        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);

        if ($revoke) {
            $property->setAccessible(false);
        }
    }

    /**
     * Gets an inaccessible object property.
     *
     * @param object|string $object
     * @param string $propertyName
     * @param bool $revoke whether to make property inaccessible after getting
     * @return mixed
     * @throws ReflectionException
     * @credit https://github.com/yiisoft/yii2/blob/master/tests/TestCase.php#L176
     */
    protected function getInaccessibleProperty(object|string $object, string $propertyName, bool $revoke = true): mixed
    {
        $class = new ReflectionClass($object);

        while (!$class->hasProperty($propertyName)) {
            $class = $class->getParentClass();
        }

        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);
        $result = $property->getValue($object);

        if ($revoke) {
            $property->setAccessible(false);
        }

        return $result;
    }

    /**
     * Invokes an inaccessible method on an object
     *
     * @param object|string $object
     * @param string $method
     * @param array $args
     * @param bool $revoke whether to make method inaccessible after execution
     * @return mixed
     * @throws ReflectionException
     * @credit https://github.com/yiisoft/yii2/blob/master/tests/TestCase.php#L134
     */
    protected function invokeMethod(object|string $object, string $method, array $args = [], bool $revoke = true): mixed
    {
        $method = (new ReflectionObject($object))->getMethod($method);
        return $this->_invokeMethodInternal($method, $object, $args, $revoke);
    }

    /**
     * Invokes an inaccessible static method on a class
     *
     * @param object|string $className
     * @param string $method
     * @param array $args
     * @param bool $revoke whether to make method inaccessible after execution
     * @return mixed
     * @throws ReflectionException
     * @credit https://github.com/yiisoft/yii2/blob/master/tests/TestCase.php#L134
     */
    protected function invokeStaticMethod(object|string $className, string $method, array $args = [], bool $revoke = true): mixed
    {
        $method = (new ReflectionClass($className))->getMethod($method);
        return $this->_invokeMethodInternal($method, null, $args, $revoke);
    }

    /**
     * @param ReflectionMethod $method
     * @param object|null $object
     * @param array $args
     * @param bool $revoke
     * @return mixed
     * @throws ReflectionException
     */
    private function _invokeMethodInternal(ReflectionMethod $method, ?object $object = null, array $args = [], bool $revoke = true): mixed
    {
        $method->setAccessible(true);
        $result = $method->invokeArgs($object, $args);

        if ($revoke) {
            $method->setAccessible(false);
        }
        return $result;
    }
}
