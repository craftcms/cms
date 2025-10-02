<?php

namespace CraftCms\Cms\Component\Concerns;

use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Support\Utils;
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
        return $this->getValidator()
            ->after(fn ($validator) => $this->afterValidate($validator))
            ->passes();
    }

    public function afterValidate(Validator $validator): void {}

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
