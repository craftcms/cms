<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\base\ComponentInterface;
use craft\errors\MissingComponentException;
use yii\base\InvalidConfigException;

/**
 * Component helper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Component
{
    // Public Methods
    // =========================================================================

    /**
     * Returns whether a component class exists, is an instance of a given interface,
     * and doesn't belong to a disabled plugin.
     *
     * @param string $class The component’s class name.
     * @param string|null $instanceOf The class or interface that the component must be an instance of.
     * @param bool $throwException Whether an exception should be thrown if an issue is encountered
     * @return bool
     * @throws InvalidConfigException if $config doesn’t contain a `type` value, or the type isn’s compatible with|null $instanceOf.
     * @throws MissingComponentException if the class specified by $config doesn’t exist, or belongs to an uninstalled plugin
     */
    public static function validateComponentClass(string $class, string $instanceOf = null, $throwException = false): bool
    {
        // Validate the class
        if (!class_exists($class)) {
            if (!$throwException) {
                return false;
            }
            throw new MissingComponentException("Unable to find component class '{$class}'.");
        }

        if (!is_subclass_of($class, ComponentInterface::class)) {
            if (!$throwException) {
                return false;
            }
            throw new InvalidConfigException("Component class '{$class}' does not implement ComponentInterface.");
        }

        if ($instanceOf !== null && !is_subclass_of($class, $instanceOf)) {
            if (!$throwException) {
                return false;
            }
            throw new InvalidConfigException("Component class '{$class}' is not an instance of '{$instanceOf}'.");
        }

        // If it comes from a plugin, make sure the plugin is installed
        $pluginsService = Craft::$app->getPlugins();
        $pluginHandle = $pluginsService->getPluginHandleByClass($class);
        if ($pluginHandle !== null && !$pluginsService->isPluginEnabled($pluginHandle)) {
            if (!$throwException) {
                return false;
            }
            $pluginInfo = $pluginsService->getComposerPluginInfo($pluginHandle);
            $pluginName = $pluginInfo['name'] ?? $pluginHandle;
            if ($pluginsService->isPluginInstalled($pluginHandle)) {
                $message = "Component class '{$class}' belongs to a disabled plugin ({$pluginName}).";
            } else {
                $message = "Component class '{$class}' belongs to an uninstalled plugin ({$pluginName}).";
            }
            throw new MissingComponentException($message);
        }

        return true;
    }

    /**
     * Instantiates and populates a component, and ensures that it is an instance of a given interface.
     *
     * @param mixed $config The component’s class name, or its config, with a `type` value and optionally a `settings` value.
     * @param string|null $instanceOf The class or interface that the component must be an instance of.
     * @return ComponentInterface The component
     * @throws InvalidConfigException if $config doesn’t contain a `type` value, or the type isn’s compatible with|null $instanceOf.
     * @throws MissingComponentException if the class specified by $config doesn’t exist, or belongs to an uninstalled plugin
     */
    public static function createComponent($config, string $instanceOf = null): ComponentInterface
    {
        // Normalize the config
        if (is_string($config)) {
            $class = $config;
            $config = [];
        } else {
            if (empty($config['type'])) {
                throw new InvalidConfigException('The config passed into Component::createComponent() did not specify a class: ' . Json::encode($config));
            }

            $class = $config['type'];
            unset($config['type']);
        }

        // Validate the component class
        static::validateComponentClass($class, $instanceOf, true);

        // Merge the settings sub-key into the main config
        $config = self::mergeSettings($config);

        // Instantiate and return
        return new $class($config);
    }

    /**
     * Extracts settings from a given component config, and returns a new config array with the settings merged in.
     *
     * @param array $config
     * @return array
     */
    public static function mergeSettings(array $config): array
    {
        if (($settings = ArrayHelper::remove($config, 'settings')) === null) {
            return $config;
        }

        if (is_string($settings)) {
            $settings = Json::decode($settings);
            if (!is_array($settings)) {
                return $config;
            }
        }

        return array_merge($config, $settings);
    }
}
