<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use yii\base\InvalidArgumentException;

/**
 * Add fluent getters/setters to a class, allowing the fluent model to work if fluent model methods are not provided
 * It is preferred to add actual methods to the class, to avoid the magic method overhead and to provide proper
 * inline documentation, but this is useful as a fallback
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.2.0
 */
trait FluentModelTrait
{
    /**
     * Add fluent getters/setters for this class
     *
     * @param string $method The method name (property name)
     * @param array $args The arguments list
     *
     * @return mixed The value of the property or the object itself or null
     */
    public function __call($method, $args)
    {
        try {
            $reflector = new \ReflectionClass(static::class);
        } catch (\ReflectionException $e) {
            Craft::error(
                $e->getMessage(),
                __METHOD__
            );

            return null;
        }
        if (!$reflector->hasProperty($method)) {
            throw new InvalidArgumentException("Property {$method} doesn't exist");
        }
        $property = $reflector->getProperty($method);
        if (empty($args)) {
            // Return the property
            return $property->getValue();
        }
        // Set the property
        $value = $args[0];
        $property->setValue($this, $value);

        // Make it chainable
        return $this;
    }
}
