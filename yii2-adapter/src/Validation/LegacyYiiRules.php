<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Validation;

use Closure;
use CraftCms\Cms\Support\Arr;
use ReflectionFunction;
use ReflectionMethod;
use yii\validators\Validator;

class LegacyYiiRules
{
    public static function mergeAttributeRules(
        array $rules,
        object $target,
        array $yiiRules,
        ?callable $validatorTarget = null,
        bool $allowMethodValidators = false,
        bool $copyErrors = true,
        bool $preserveOptionKeys = true,
    ): array {
        $legacyAttributes = [];

        foreach ($yiiRules as $rule) {
            if (!is_array($rule) || !isset($rule[0], $rule[1])) {
                continue;
            }

            foreach ((array)$rule[0] as $attribute) {
                if (is_string($attribute) && $attribute !== '') {
                    $legacyAttributes[$attribute] = true;
                }
            }
        }

        foreach (array_keys($legacyAttributes) as $legacyAttribute) {
            $rules[$legacyAttribute] ??= [];
            $rules[$legacyAttribute] = Arr::wrap($rules[$legacyAttribute]);

            array_unshift($rules[$legacyAttribute], self::validatorClosure(
                target: $target,
                yiiRules: $yiiRules,
                validatorTarget: $validatorTarget,
                allowMethodValidators: $allowMethodValidators,
                copyErrors: $copyErrors,
                preserveOptionKeys: $preserveOptionKeys,
            ));
        }

        return $rules;
    }

    public static function mergeWildcardRules(
        array $rules,
        object $target,
        array $yiiRules,
        ?callable $validatorTarget = null,
        bool $allowMethodValidators = false,
        bool $copyErrors = false,
        bool $preserveOptionKeys = false,
    ): array {
        $rules['*'] ??= [];
        $rules['*'] = Arr::wrap($rules['*']);

        array_unshift($rules['*'], self::validatorClosure(
            target: $target,
            yiiRules: $yiiRules,
            validatorTarget: $validatorTarget,
            allowMethodValidators: $allowMethodValidators,
            copyErrors: $copyErrors,
            preserveOptionKeys: $preserveOptionKeys,
        ));

        return $rules;
    }

    private static function validatorClosure(
        object $target,
        array $yiiRules,
        ?callable $validatorTarget,
        bool $allowMethodValidators,
        bool $copyErrors,
        bool $preserveOptionKeys,
    ): Closure {
        return function($attribute, $value, $fail) use ($target, $yiiRules, $validatorTarget, $allowMethodValidators, $copyErrors, $preserveOptionKeys): void {
            foreach ($yiiRules as $rule) {
                if (!is_array($rule) || !isset($rule[0], $rule[1])) {
                    continue;
                }

                $attributes = (array)$rule[0];
                $type = $rule[1];
                $options = array_slice($rule, 2, null, $preserveOptionKeys);

                if (!in_array($attribute, $attributes, true)) {
                    continue;
                }

                if ($allowMethodValidators && is_string($type) && method_exists($target, $type)) {
                    $type = self::methodValidator($target, $type);
                }

                if (isset($options['when']) && is_callable($options['when'])) {
                    $options['when'] = self::normalizeWhenCallback($options['when'], $target);
                }

                $validationTarget = $validatorTarget ? $validatorTarget() : $target;
                $validator = Validator::createValidator($type, $validationTarget, $attributes, $options);
                $validator->validateAttribute($validationTarget, $attribute);

                if (!$copyErrors || !method_exists($validationTarget, 'getErrors')) {
                    continue;
                }

                foreach ($validationTarget->getErrors($attribute) as $error) {
                    $fail((string)$error);
                }
            }
        };
    }

    private static function methodValidator(object $target, string $method): Closure
    {
        return function(string $attribute, ?array $params, Validator $validator, mixed $current) use ($method, $target): void {
            $parameterCount = (new ReflectionMethod($target, $method))->getNumberOfParameters();

            match (true) {
                $parameterCount === 0 => $target->$method(),
                $parameterCount === 1 => $target->$method($attribute),
                $parameterCount === 2 => $target->$method($attribute, $params),
                $parameterCount === 3 => $target->$method($attribute, $params, $validator),
                default => $target->$method($attribute, $params, $validator, $current),
            };
        };
    }

    private static function normalizeWhenCallback(callable $callback, object $target): Closure
    {
        return function($model, string $attribute) use ($callback, $target): bool {
            $callback = Closure::fromCallable($callback);
            $parameterCount = (new ReflectionFunction($callback))->getNumberOfParameters();

            return match (true) {
                $parameterCount === 0 => (bool)$callback(),
                $parameterCount === 1 => (bool)$callback($target),
                default => (bool)$callback($target, $attribute),
            };
        };
    }
}
