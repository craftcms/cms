<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Validation;

use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Support\Arr;
use ReflectionClass;
use yii\validators\Validator;

class LegacyElementRules extends ElementRules
{
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $reflectionClass = new ReflectionClass($this->component);

        if (!$reflectionClass->hasMethod('defineRules')) {
            return $rules;
        }

        $method = $reflectionClass->getMethod('defineRules');
        $yiiRules = $method->invoke($this->component);

        // Ensure it's set and an array
        $rules['*'] ??= [];
        $rules['*'] = Arr::wrap($rules['*']);

        array_unshift($rules['*'], function($attribute, $value, $fail) use ($yiiRules) {
            foreach ($yiiRules as $rule) {
                $attributes = (array) $rule[0];
                $type = $rule[1];
                $options = array_slice($rule, 2);

                if (!in_array($attribute, $attributes, true)) {
                    continue;
                }

                $validator = Validator::createValidator($type, $this->component, $attributes, $options);
                $validator->validateAttribute($this->component, $attribute);
            }
        });

        return $rules;
    }
}
