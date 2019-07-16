<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test;

use Closure;
use Codeception\Test\Unit;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;

/**
 * Class TestCase
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class TestCase extends Unit
{

    // Public Methods
    // =========================================================================

    /**
     * Returns a callback/Closure that checks whether the passed in object is an instance of the $class param
     *
     * @param string $class
     * @return Closure
     */
    public function assertObjectIsInstanceOfClassCallback(string $class): callable
    {
        return function($object) use ($class) {
            $this->assertSame($class, get_class($object));
        };
    }

    // Protected Methods
    // =========================================================================

    /**
     * Sets an inaccessible object property to a designated value.
     *
     * @param $object
     * @param $propertyName
     * @param $value
     * @param bool $revoke whether to make property inaccessible after setting
     * @throws ReflectionException
     * @credit https://github.com/yiisoft/yii2/blob/master/tests/TestCase.php#L155
     */
    protected function setInaccessibleProperty($object, $propertyName, $value, $revoke = true)
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
     * @param $object
     * @param $propertyName
     * @param bool $revoke whether to make property inaccessible after getting
     * @return mixed
     * @throws ReflectionException
     * @credit https://github.com/yiisoft/yii2/blob/master/tests/TestCase.php#L176
     */
    protected function getInaccessibleProperty($object, $propertyName, $revoke = true)
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
     * Invokes a inaccessible method.
     *
     * @param $object
     * @param $method
     * @param array $args
     * @param bool $revoke whether to make method inaccessible after execution
     * @return mixed
     * @throws ReflectionException
     * @credit https://github.com/yiisoft/yii2/blob/master/tests/TestCase.php#L134
     */
    protected function invokeMethod($object, $method, $args = [], $revoke = true)
    {
        $method = (new ReflectionObject($object))->getMethod($method);
        $method->setAccessible(true);
        $result = $method->invokeArgs($object, $args);

        if ($revoke) {
            $method->setAccessible(false);
        }

        return $result;
    }
}
