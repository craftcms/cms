<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\base\ComponentInterface;
use craft\base\ElementInterface;
use craft\base\Model;
use craft\errors\MissingComponentException;
use DateTime;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use yii\base\InvalidConfigException;

/**
 * Component helper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Component
{
    /**
     * Returns whether a component class exists, is an instance of a given interface,
     * and doesn't belong to a disabled plugin.
     *
     * @param class-string<ComponentInterface> $class The component’s class name.
     * @param class-string<ComponentInterface>|null $instanceOf The class or interface that the component must be an instance of.
     * @param bool $throwException Whether an exception should be thrown if an issue is encountered
     * @return bool
     * @throws InvalidConfigException if $config doesn’t contain a `type` value, or the type isn’t compatible with|null $instanceOf.
     * @throws MissingComponentException if the class specified by $config doesn’t exist, or belongs to an uninstalled plugin
     * @since 3.2.0
     */
    public static function validateComponentClass(string $class, ?string $instanceOf = null, bool $throwException = false): bool
    {
        // Validate the class
        if (!class_exists($class)) {
            if (!$throwException) {
                return false;
            }
            throw new MissingComponentException("Unable to find component class '$class'.");
        }

        if (!is_subclass_of($class, ComponentInterface::class)) {
            if (!$throwException) {
                return false;
            }
            throw new InvalidConfigException("Component class '$class' does not implement ComponentInterface.");
        }

        if ($instanceOf !== null && !is_subclass_of($class, $instanceOf)) {
            if (!$throwException) {
                return false;
            }
            throw new InvalidConfigException("Component class '$class' is not an instance of '$instanceOf'.");
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
                $message = "Component class '$class' belongs to a disabled plugin ($pluginName).";
            } else {
                $message = "Component class '$class' belongs to an uninstalled plugin ($pluginName).";
            }
            throw new MissingComponentException($message);
        }

        return true;
    }

    /**
     * Cleanses a component config of any `on X` or `as X` keys.
     *
     * @param array $config
     * @return array
     * @since 4.4.15
     */
    public static function cleanseConfig(array $config): array
    {
        foreach ($config as $key => $value) {
            if (is_string($key) && (str_starts_with($key, 'on ') || str_starts_with($key, 'as '))) {
                unset($config[$key]);
                continue;
            }
            if (is_array($value)) {
                $config[$key] = static::cleanseConfig($value);
            }
        }
        return $config;
    }

    /**
     * Instantiates and populates a component, and ensures that it is an instance of a given interface.
     *
     * @template T of ComponentInterface
     * @param class-string<T>|array $config The component’s class name, or its config, with a `type` value and optionally a `settings` value.
     * @phpstan-param class-string<T>|array{type:class-string<T>,__class?:string} $config
     * @param class-string<T>|null $instanceOf The class or interface that the component must be an instance of.
     * @return T The component
     * @throws InvalidConfigException if $config doesn’t contain a `type` value, or the type isn’t compatible with|null $instanceOf.
     * @throws MissingComponentException if the class specified by $config doesn’t exist, or belongs to an uninstalled plugin
     */
    public static function createComponent(string|array $config, ?string $instanceOf = null): ComponentInterface
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
            unset($config['type'], $config['__class']);
        }

        // Validate the component class
        static::validateComponentClass($class, $instanceOf, true);

        // Merge the settings sub-key into the main config
        $config = self::mergeSettings($config);

        // Typecast the properties
        Typecast::properties($class, $config);

        // Instantiate and return
        $config['class'] = $class;
        return Craft::createObject(static::cleanseConfig($config));
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

    /**
     * Returns an SVG icon’s contents, namespaced and with `aria-hidden="true"` added to it.
     *
     * @param string|null $icon The path to the SVG icon, or the actual SVG contents
     * @param string $label The label of the component
     * @return string
     * @since 3.5.0
     * @deprecated in 5.0.0. [[Cp::iconSvg()]] or [[Cp::fallbackIconSvg()]] should be used instead.
     */
    public static function iconSvg(?string $icon, string $label): string
    {
        if ($icon === null) {
            return Cp::fallbackIconSvg($label);
        }

        return Cp::iconSvg($icon, $label);
    }

    /**
     * Return all DateTime attributes for given model.
     *
     * @param Model|ElementInterface $model
     * @return array
     */
    public static function datetimeAttributes(Model|ElementInterface $model): array
    {
        $datetimeAttributes = [];
        foreach ((new ReflectionClass($model))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $type = $property->getType();
                if ($type instanceof ReflectionNamedType && $type->getName() === DateTime::class) {
                    $datetimeAttributes[] = $property->getName();
                }
            }
        }

        // Include datetimeAttributes() for now
        return array_unique(array_merge($datetimeAttributes, $model->datetimeAttributes()));
    }
}
