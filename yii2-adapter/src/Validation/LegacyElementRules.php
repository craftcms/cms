<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Validation;

use CraftCms\Cms\Element\Validation\ElementRules;
use ReflectionClass;

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

        return LegacyYiiRules::mergeWildcardRules(
            rules: $rules,
            target: $this->component,
            yiiRules: $yiiRules,
        );
    }
}
