<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Concerns;

use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Support\Utils;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Validator;

trait Validates
{
    private ?Validator $validator = null;

    private bool $validated = false;

    public static function getRules(): array
    {
        return [];
    }

    public static function getMessages(): array
    {
        return [];
    }

    protected function getValidator(): Validator
    {
        return $this->validator ??= ValidatorFacade::make(
            data: $this->getAttributes(),
            rules: static::getRules(),
            messages: static::getMessages(),
            attributes: $this->attributeLabels(),
        );
    }

    public function validate(array|string|null $attributeNames = null, bool $clearErrors = true): bool
    {
        if (! $this->beforeValidate()) {
            return false;
        }

        $result = $this->getValidator()
            ->after(fn ($validator) => $this->afterValidate($validator))
            ->passes();

        $this->validated = true;

        return $result;
    }

    public function beforeValidate(): bool
    {
        return true;
    }

    public function afterValidate(Validator $validator): void {}

    public function getFirstErrors(): array
    {
        return array_map(fn (array $messages) => Arr::first($messages), $this->errors()->getMessages());
    }

    public function errors(): MessageBag
    {
        return $this->getValidator()->errors();
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
