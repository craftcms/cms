<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Validation;

use CraftCms\Cms\Element\Validation\ElementRules;
use ReflectionClass;

class LegacyElementRules extends ElementRules
{
    public function rules(): array
    {
        $rules = parent::rules();

        $reflectionClass = new ReflectionClass($this->subject);

        if (!$reflectionClass->hasMethod('defineRules')) {
            return $rules;
        }

        $method = $reflectionClass->getMethod('defineRules');
        $yiiRules = $method->invoke($this->subject);

        return LegacyYiiRules::mergeWildcardRules(
            rules: $rules,
            target: $this->subject,
            yiiRules: $yiiRules,
        );
    }
}
