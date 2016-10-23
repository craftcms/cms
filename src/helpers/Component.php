<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\helpers;

use craft\app\base\ComponentInterface;
use craft\app\base\SavableComponentInterface;
use craft\app\errors\MissingComponentException;
use yii\base\InvalidConfigException;

/**
 * Class Element
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Component
{
    // Public Methods
    // =========================================================================

    /**
     * Instantiates and populates a component, and ensures that it is an instance of a given interface.
     *
     * @param mixed  $config     The component’s class name, or its config, with a `type` value and optionally a `settings` value.
     * @param string $instanceOf The class or interface that the component must be an instance of.
     *
     * @return ComponentInterface The component
     * @throws InvalidConfigException if $config doesn’t contain a `type` value, or the type isn’s compatible with $instanceOf.
     * @throws MissingComponentException if the class specified by $config doesn’t exist
     */
    public static function createComponent($config, $instanceOf = null)
    {
        // Normalize the config
        if (is_string($config)) {
            $class = $config;
            $config = [];
        } else {
            $config = ArrayHelper::toArray($config);

            if (empty($config['type'])) {
                throw new InvalidConfigException('The config passed into Component::createComponent() did not specify a class: '.Json::encode($config));
            }

            $class = $config['type'];
            unset($config['type']);
        }

        // Validate the class
        if (!class_exists($class)) {
            throw new MissingComponentException("Unable to find component class '$class'.");
        }

        if (!is_subclass_of($class, ComponentInterface::class)) {
            throw new InvalidConfigException("Component class '$class' does not implement ComponentInterface.");
        }

        if ($instanceOf && !is_subclass_of($class, $instanceOf)) {
            throw new InvalidConfigException("Component class '$class' is not an instance of '$instanceOf'.");
        }

        // Expand the settings and merge with the rest of the config
        if (is_subclass_of($class, SavableComponentInterface::class) && !empty($config['settings'])) {
            $settings = $config['settings'];

            if (is_string($settings)) {
                $settings = Json::decode($settings);
            }

            $config = array_merge($config, $settings);
        }

        // Unset $config['settings'] even if it was empty
        unset($config['settings']);

        // Instantiate and return
        return new $class($config);
    }
}
