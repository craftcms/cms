<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use craft\helpers\App;
use craft\helpers\StringHelper;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use yii\base\BaseObject;

abstract class Config extends BaseObject
{
    public const ENV_PREFIX = null;

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->normalize();
    }

    /**
     * Normalize the config object, including any environment variable overrides.
     * @return void
     */
    protected function normalize(): void
    {
        Craft::configure($this, $this->_getNormalizedProperties());
    }

    private function _getNormalizedProperties(): array
    {
        $reflect = new ReflectionClass($this);

        return Collection::make($reflect->getProperties(ReflectionProperty::IS_PUBLIC))
            ->flatMap(function(ReflectionProperty $prop) {
                $name = $prop->getName();
                $value = $this->_getConfigValueFromEnv($name) ?? $prop->getValue($this);

                if (is_string($value) &&
                    str_contains($value, ',') &&
                    $this->_getPropertyTypes($prop)->contains('array')
                ) {
                    $value = StringHelper::split($value);
                }

                return [$name => $value];
            })
            ->all();
    }

    private function _getConfigValueFromEnv($name)
    {
        if (static::ENV_PREFIX === null) {
            return null;
        }

        $prefix = static::ENV_PREFIX ? StringHelper::ensureRight(static::ENV_PREFIX, '_') : '';
        $envName = $prefix . strtoupper(StringHelper::toSnakeCase($name));

        return App::env($envName);
    }

    private function _getPropertyTypes(ReflectionProperty $property): Collection
    {
        return Collection::make([$property->getType()])

            // Checking for getTypes, as ReflectionIntersectionType isn't available until 8.1
            ->filter(fn($type) => $type && ($type instanceof ReflectionNamedType || method_exists($type, 'getTypes')))
            ->flatMap(function($type) {
                if ($type instanceof ReflectionNamedType) {
                    return [$type->getName()];
                }

                /**
                 * Ignoring PHPStan errors, as we've already filtered on `getTypes`.
                 */
                $types = $type->getTypes(); // @phpstan-ignore-line

                return Collection::make($types)->map(fn($type) => $type->getName());
            });
    }
}
