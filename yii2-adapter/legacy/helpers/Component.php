<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use craft\base\ElementInterface;
use craft\base\Model;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Component\Contracts\ComponentInterface;
use CraftCms\Cms\Component\Exceptions\MissingComponentException;
use CraftCms\Cms\Cp\Icons;
use DateTime;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use RuntimeException;
use yii\base\InvalidConfigException;

/**
 * Component helper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see ComponentHelper} instead.
 */
class Component extends ComponentHelper
{
    public static function validateComponentClass(
        string $class,
        ?string $instanceOf = null,
        bool $throwException = false,
    ): bool {
        try {
            return parent::validateComponentClass($class, $instanceOf, $throwException);
        } catch (MissingComponentException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            throw new InvalidConfigException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Cleanses a component config of any `on X` or `as X` keys.
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

    public static function createComponent(array|string $config, ?string $instanceOf = null): ComponentInterface
    {
        if (is_array($config)) {
            $config = static::cleanseConfig($config);
        }

        try {
            return parent::createComponent($config, $instanceOf);
        } catch (MissingComponentException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            throw new InvalidConfigException($e->getMessage(), $e->getCode(), $e);
        }
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
            return Icons::fallbackSvg($label);
        }

        return Icons::svg($icon, $label);
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
