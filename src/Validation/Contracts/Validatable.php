<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Contracts;

use Illuminate\Contracts\Support\MessageBag;

interface Validatable
{
    public static function getRules(): array;

    public static function getMessages(): array;

    // public function beforeValidate(): bool;

    public function validate(string|array|null $attributeNames = null, bool $clearErrors = true): bool;

    // public function afterValidate(Validator $validator): void;

    /**
     * Get the first error for each attribute.
     *
     * @return array<string, string>
     */
    public function getFirstErrors(): array;

    public function errors(): MessageBag;

    public function setAttributes(array $values, bool $safeOnly = true): void;

    /**
     * Returns attribute values.
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array;

    /**
     * Returns the list of attribute names.
     *
     * By default, this method returns all public non-static properties of the class.
     * You may override this method to change the default behavior.
     *
     * @return string[] list of attribute names.
     */
    public function attributes(): array;
}
