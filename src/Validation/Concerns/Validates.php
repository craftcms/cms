<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Concerns;

use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Support\Utils;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;

trait Validates
{
    use InteractsWithValidator;

    public static function getRules(): array
    {
        return [];
    }

    public static function getMessages(): array
    {
        return [];
    }

    protected function getValidator(?array $attributeNames = null): Validator
    {
        return ValidatorFacade::make([], [])
            ->setData($this->getAttributes())
            ->setCustomMessages(static::getMessages())
            ->setAttributeNames($this->attributeLabels())
            ->setRules(is_null($attributeNames)
                ? static::getRules()
                : Arr::only(static::getRules(), $attributeNames)
            );
    }

    public function setAttributes(array $values, bool $safeOnly = true): void
    {
        Typecast::properties(static::class, $values);

        foreach ($values as $name => $value) {
            $this->$name = $value;
        }
    }

    public function getAttributes(): array
    {
        return Utils::getPublicProperties($this);
    }

    public function attributes(): array
    {
        return Utils::getPublicAttributes($this);
    }

    public function attributeLabels(): array
    {
        return [];
    }
}
