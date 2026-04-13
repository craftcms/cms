<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Concerns;

use CraftCms\Cms\Support\Arr;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Validator;

/**
 * @internal
 */
trait InteractsWithValidator
{
    private ?MessageBag $errors = null;

    abstract protected function getValidator(?array $attributeNames = null): Validator;

    public function beforeValidate(): bool
    {
        return true;
    }

    /**
     * TODO: Remove optionality of validator after components no longer rely
     * on craft/base/Model
     */
    public function afterValidate(?Validator $validator = null): void {}

    public function getFirstErrors(): array
    {
        return array_map(fn (array $messages) => Arr::first($messages), $this->errors()->getMessages());
    }

    public function errors(): MessageBag
    {
        return $this->errors ??= new MessageBag;
    }

    public function clearErrors($attribute = null): void
    {
        if (is_null($attribute)) {
            $this->errors = new MessageBag;

            return;
        }

        $this->errors->forget($attribute);
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

        if ($ruleset = $this->getRuleset()) {
            $ruleset->prepareForValidation($attributeNames);
        }

        $validator = $this->getValidator($attributeNames)
            ->after(fn ($validator) => $this->afterValidate($validator));

        if ($throw) {
            $validator->validate();
        }

        $result = $validator->passes();

        $this->errors()->merge($validator->errors()->getMessages());

        return $result && $this->errors()->isEmpty();
    }
}
