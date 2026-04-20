<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Concerns;

use CraftCms\Cms\Component\Exceptions\InvalidCallException;
use CraftCms\Cms\Component\Exceptions\UnknownPropertyException;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Support\Utils;
use CraftCms\Cms\Validation\Contracts\Validatable;
use CraftCms\RulesetValidation\Concerns\HasRuleset;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;

/**
 * @mixin Validatable
 */
trait Validates
{
    use HasRuleset;
    use HasScenarios;
    use InteractsWithValidator;

    public function getRules(): array
    {
        return [];
    }

    public function getMessages(): array
    {
        return [];
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        Typecast::properties(static::class, $values);

        foreach ($values as $name => $value) {
            try {
                $this->$name = $value;
            } catch (UnknownPropertyException|InvalidCallException|\yii\base\UnknownPropertyException) {
                // Property or setter doesn't exist
            }
        }
    }

    public function validationData(): array
    {
        return $this->getAttributes();
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

    protected function getValidator(?array $attributeNames = null): Validator
    {
        if ($ruleset = $this->ruleset) {
            return $ruleset
                ->when(! is_null($attributeNames), fn ($ruleset) => $ruleset->only($attributeNames))
                ->getValidator();
        }

        $rules = is_null($attributeNames)
            ? $this->getRules()
            : Arr::only($this->getRules(), $attributeNames);

        return ValidatorFacade::make([], [])
            ->setData($this->validationData())
            ->setCustomMessages($this->getMessages())
            ->setAttributeNames($this->attributeLabels())
            ->setRules($rules);
    }
}
