<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Concerns;

use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Utils;
use CraftCms\RulesetValidation\Concerns\HasRuleset;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Validator;

trait Validates
{
    use HasRuleset;

    private ?MessageBag $errors = null;

    public function getFirstErrors(): array
    {
        return array_map(fn (array $messages) => Arr::first($messages), $this->errors()->getMessages());
    }

    public function errors(): MessageBag
    {
        return $this->errors ??= new MessageBag;
    }

    /**
     * TODO: Add types to method signature once components no longer rely
     * on craft/base/Model
     *
     * @param  array|string|null  $attributeNames
     * @param  bool  $clearErrors
     */
    public function validate($attributeNames = null, $clearErrors = true, bool $throw = false): bool
    {
        if ($clearErrors) {
            $this->errors = new MessageBag;
        }

        if (is_string($attributeNames)) {
            $attributeNames = [$attributeNames];
        }

        $ruleset = $this->ruleset;

        if (! is_null($attributeNames)) {
            $ruleset->only($attributeNames);
        }

        if ($throw) {
            $ruleset->validate();
        }

        $result = $ruleset->passes();

        $this->errors()->merge($ruleset->getValidator()->errors());

        return $result && $this->errors()->isEmpty();
    }

    public function getRules(): array
    {
        return [];
    }

    public function getMessages(): array
    {
        return [];
    }

    public function attributeLabels(): array
    {
        return [];
    }

    public function prepareForValidation(): void {}

    public function passedValidation(): void {}

    public function afterValidate(?Validator $validator = null): void {}

    public function validationData(): array
    {
        return Utils::getPublicProperties($this);
    }
}
