<?php

namespace CraftCms\Cms\Component\Contracts;

/**
 * @since 6.0.0
 */
interface ValidatableComponentInterface
{
    public static function getRules(): array;

    public function getValidationData(): array;

    public function validate(string|array|null $attributeNames = null, bool $clearErrors = true): bool;

    public function hasErrors(?string $attribute = null): bool;

    public function getErrors(?string $attribute = null): array;

    public function getFirstErrors(): array;

    public function getFirstError(string $attribute): ?string;
}
