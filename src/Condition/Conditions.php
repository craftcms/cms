<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use InvalidArgumentException;
use ReflectionException;
use ReflectionProperty;

#[Singleton]
final readonly class Conditions
{
    /**
     * Creates a condition instance.
     *
     * @template T of ConditionInterface
     *
     * @param  array|class-string<T>  $config  The condition class or configuration array
     *
     * @phpstan-param array{class:class-string<T>}|class-string<T> $config
     *
     * @throws InvalidArgumentException if the condition does not implement [[ConditionInterface]]
     */
    public function createCondition(array|string $config): ConditionInterface
    {
        if (is_string($config)) {
            $config = ['class' => $config];
        }

        $class = Arr::pull($config, 'class');

        if (! is_subclass_of($class, ConditionInterface::class)) {
            throw new InvalidArgumentException("Invalid condition class: $class");
        }

        // The base config will be JSON-encoded within a `config` key if this came from a condition builder
        if (isset($config['config']) && Str::isJson($config['config'])) {
            $config = array_merge(
                Json::decode(Arr::pull($config, 'config')),
                $config
            );
        }

        // Set condition rules last, in case any available rules are dependent on the condition config
        $rules = Arr::pull($config, 'conditionRules', []);

        return app()->make($class, [
            'attributes' => $config,
            'conditionRules' => $rules,
        ]);
    }

    /**
     * Creates a condition rule instance.
     *
     * @param  array|string  $config  The condition class or configuration array
     *
     * @phpstan-param array{class: string}|array{type:string}|string $config The condition class or configuration array
     *
     * @throws InvalidArgumentException if the condition rule does not implement [[ConditionRuleInterface]]
     */
    public function createConditionRule(array|string $config): ConditionRuleInterface
    {
        if (is_string($config)) {
            $config = ['class' => $config];
        }

        $class = Arr::pull($config, 'class');

        // Merge `type` in, if this is coming from a condition builder
        if (isset($config['type'])) {
            $newConfig = Json::decodeIfJson(Arr::pull($config, 'type'));

            if (is_string($newConfig)) {
                $newConfig = ['class' => $newConfig];
            }

            $newClass = Arr::pull($newConfig, 'class');

            // Make sure the condition is being passed to the condition rule when it's being constructed
            if (isset($config['condition'])) {
                $newConfig['condition'] = $config['condition'];
            }

            // Is the type changing?
            if ($class !== null && $newClass !== $class) {
                // Remove any config attributes that aren't defined by the same class between both types
                $config = array_filter($config, function ($attribute) use ($class, $newClass) {
                    try {
                        $r1 = new ReflectionProperty($class, $attribute);
                        $r2 = new ReflectionProperty($newClass, $attribute);

                        return $r1->getDeclaringClass()->name === $r2->getDeclaringClass()->name;
                    } catch (ReflectionException) {
                        return false;
                    }
                }, ARRAY_FILTER_USE_KEY);
            }

            $class = $newClass;
            $config += $newConfig;
        }

        if (! is_subclass_of($class, ConditionRuleInterface::class)) {
            throw new InvalidArgumentException("Invalid condition rule class: $class");
        }

        return app()->make($class, ['attributes' => $config]);
    }
}
