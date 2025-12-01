<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Concerns;

use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Support\Utils;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;

trait ValidatableComponent
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
        return $this->validator ??= ValidatorFacade::make($this->getAttributes(), static::getRules(), static::getMessages());
    }

    public function validate(array|string|null $attributeNames = null, bool $clearErrors = true): bool
    {
        $result = $this->getValidator()
            ->after(fn ($validator) => $this->afterValidate($validator))
            ->passes();

        $this->validated = true;

        return $result;
    }

    public function afterValidate(Validator $validator): void {}

    public function hasErrors(?string $attribute = null): bool
    {
        if (! $this->validated) {
            return false;
        }

        if (! $attribute) {
            return $this->getValidator()->fails();
        }

        return $this->getValidator()->errors()->has($attribute);
    }

    public function addErrors(array $errors): void
    {
        foreach ($errors as $attribute => $message) {
            $this->getValidator()->errors()->add($attribute, $message);
        }
    }

    public function clearErrors(?string $attribute = null): void
    {
        if (is_null($attribute)) {
            foreach ($this->getValidator()->errors()->all() as $attribute => $messages) {
                $this->getValidator()->errors()->forget($attribute);
            }

            return;
        }

        $this->getValidator()->errors()->forget($attribute);
    }

    public function getErrors(?string $attribute = null): array
    {
        if (! $this->validated) {
            return [];
        }

        if (! $attribute) {
            return array_map(
                fn (array $messages) => $messages[0],
                $this->getValidator()->errors()->getMessages(),
            );
        }

        return $this->getValidator()->errors()->get($attribute);
    }

    public function getFirstErrors(): array
    {
        return $this->getErrors();
    }

    public function getFirstError(string $attribute): ?string
    {
        if (! $this->validated) {
            return null;
        }

        return $this->getValidator()->errors()->first($attribute);
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
}
