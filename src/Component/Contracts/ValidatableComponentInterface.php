<?php

namespace CraftCms\Cms\Component\Contracts;

interface ValidatableComponentInterface
{
    public static function getRules(): array;

    public function validate(string|array|null $attributeNames = null, bool $clearErrors = true): bool;

    public function hasErrors(?string $attribute = null): bool;

    public function getErrors(?string $attribute = null): array;

    public function getFirstErrors(): array;

    public function getFirstError(string $attribute): ?string;

    public function setAttributes(array $values, bool $safeOnly = true): void;

    public function getAttributes(): array;
}
