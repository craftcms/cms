<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Concerns;

use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Validation\Contracts\ValidatableWithRuleset;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Validator;

/**
 * @internal
 */
trait InteractsWithValidator
{
    private ?Validator $validator = null;

    abstract protected function getValidator(): Validator;

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
        return $this->getValidator()->errors();
    }

    /**
     * TODO: Add types to method signature once components no longer rely
     * on craft/base/Model
     *
     * @param  array|string|null  $attributeNames
     * @param  bool  $clearErrors
     */
    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        $previousErrors = ! $clearErrors && isset($this->validator)
            ? $this->errors()->getMessages()
            : [];

        if ($this instanceof ValidatableWithRuleset) {
            $this->getRuleset()->prepareForValidation($attributeNames);
        }

        $result = $this->getValidator($attributeNames, fresh: $clearErrors)
            ->after(fn ($validator) => $this->afterValidate($validator))
            ->passes();

        $this->errors()->merge($previousErrors);

        return $result && $this->errors()->isEmpty();
    }
}
