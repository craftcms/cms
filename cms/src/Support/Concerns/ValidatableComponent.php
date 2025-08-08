<?php

namespace CraftCms\Cms\Support\Concerns;

use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;

/**
 * @since 6.0.0
 */
trait ValidatableComponent
{
    private ?Validator $validator = null;

    public static function getRules(): array
    {
        return [];
    }

    public function getValidationData(): array
    {
        return [];
    }

    protected function getValidator(): Validator
    {
        return $this->validator ??= ValidatorFacade::make($this->getValidationData(), static::getRules());
    }

    public function validate(array|string|null $attributeNames = null, bool $clearErrors = true): bool
    {
        return $this->getValidator()->passes();
    }

    public function hasErrors(?string $attribute = null): bool
    {
        if (! $attribute) {
            return $this->getValidator()->fails();
        }

        return $this->getValidator()->errors()->has($attribute);
    }

    public function getErrors(?string $attribute = null): array
    {
        if (! $attribute) {
            return $this->getValidator()->errors()->all();
        }

        return $this->getValidator()->errors()->get($attribute);
    }

    public function getFirstErrors(): array
    {
        return $this->getErrors();
    }

    public function getFirstError(string $attribute): ?string
    {
        return $this->getValidator()->errors()->first($attribute);
    }
}
