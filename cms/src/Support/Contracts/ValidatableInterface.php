<?php

namespace CraftCms\Cms\Support\Contracts;

interface ValidatableInterface
{
    public function validate(string|array|null $attributeNames = null, bool $clearErrors = true): bool;

    public function hasErrors(?string $attribute = null): bool;

    public function getErrors(?string $attribute = null): array;

    public function getFirstErrors(): array;

    public function getFirstError(string $attribute): ?string;

    public function getErrorSummary(bool $showAllErrors): array;

    public function addError($attribute, $error = ''): void;

    public function addErrors(array $errors): void;

    public function clearErrors(?string $attribute = null): void;
}
