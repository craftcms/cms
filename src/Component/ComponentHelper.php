<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component;

use CraftCms\Cms\Component\Contracts\ComponentInterface;
use CraftCms\Cms\Component\Exceptions\MissingComponentException;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Support\Utils;
use DateTime;
use ReflectionNamedType;
use ReflectionProperty;
use RuntimeException;

class ComponentHelper
{
    /**
     * Returns whether a component class exists, is an instance of a given interface,
     * and doesn't belong to a disabled plugin.
     *
     * @param  class-string<ComponentInterface>  $class  The component’s class name.
     * @param  class-string<ComponentInterface>|null  $instanceOf  The class or interface that the component must be an instance of.
     * @param  bool  $throwException  Whether an exception should be thrown if an issue is encountered
     *
     * @throws RuntimeException if $config doesn’t contain a `type` value, or the type isn’t compatible with|null $instanceOf.
     * @throws MissingComponentException if the class specified by $config doesn’t exist, or belongs to an uninstalled plugin
     */
    public static function validateComponentClass(string $class, ?string $instanceOf = null, bool $throwException = false): bool
    {
        // Validate the class
        if (! class_exists($class)) {
            if (! $throwException) {
                return false;
            }
            throw new MissingComponentException("Unable to find component class '$class'.");
        }

        if (! is_subclass_of($class, ComponentInterface::class)) {
            if (! $throwException) {
                return false;
            }

            throw new RuntimeException("Component class '$class' does not implement ComponentInterface.");
        }

        if ($instanceOf !== null && ! is_subclass_of($class, $instanceOf)) {
            if (! $throwException) {
                return false;
            }

            throw new RuntimeException("Component class '$class' is not an instance of '$instanceOf'.");
        }

        // If it comes from a plugin, make sure the plugin is installed
        $pluginsService = app(Plugins::class);
        $pluginHandle = $pluginsService->getPluginHandleByClass($class);

        if (is_null($pluginHandle)) {
            return true;
        }

        if ($pluginsService->isPluginEnabled($pluginHandle)) {
            return true;
        }

        if (! $throwException) {
            return false;
        }

        $pluginInfo = $pluginsService->getComposerPluginInfo($pluginHandle);
        $pluginName = $pluginInfo['name'] ?? $pluginHandle;

        $message = $pluginsService->isPluginInstalled($pluginHandle)
            ? "Component class '$class' belongs to a disabled plugin ($pluginName)."
            : "Component class '$class' belongs to an uninstalled plugin ($pluginName).";

        throw new MissingComponentException($message);
    }

    /**
     * Instantiates and populates a component, and ensures that it is an instance of a given interface.
     *
     * @template T of ComponentInterface
     *
     * @param  class-string<T>|array  $config  The component’s class name, or its config, with a `type` value and optionally a `settings` value.
     * @param  class-string<T>|null  $instanceOf  The class or interface that the component must be an instance of.
     *
     * @phpstan-param class-string<T>|array{type:class-string<T>,__class?:string} $config
     *
     * @return T The component
     *
     * @throws RuntimeException if $config doesn’t contain a `type` value, or the type isn’t compatible with|null $instanceOf.
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
                throw new RuntimeException('The config passed into Component::createComponent() did not specify a class: '.Json::encode($config));
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

        return app()->make($class, ['config' => $config]);
    }

    /**
     * Extracts settings from a given component config, and returns a new config array with the settings merged in.
     */
    public static function mergeSettings(array $config): array
    {
        if (($settings = Arr::pull($config, 'settings')) === null) {
            return $config;
        }

        if (is_string($settings)) {
            $settings = Json::decode($settings);

            if (! is_array($settings)) {
                return $config;
            }
        }

        return array_merge($config, $settings);
    }

    /**
     * Return all DateTime attributes for given model.
     */
    public static function datetimeAttributes(object $model): array
    {
        $datetimeAttributes = [];

        $attributes = Utils::getPublicReflectionProperties($model, function (ReflectionProperty $property) {
            $type = $property->getType();

            return $type instanceof ReflectionNamedType && $type->getName() === DateTime::class;
        });

        foreach ($attributes as $property) {
            $datetimeAttributes[] = $property->getName();
        }

        // Include datetimeAttributes() for now
        return $datetimeAttributes;
    }
}
