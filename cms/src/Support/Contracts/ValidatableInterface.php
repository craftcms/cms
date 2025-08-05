<?php

namespace CraftCms\Cms\Support\Contracts;

interface ValidatableInterface
{
    public function validate(string|array|null $attributeNames = null, bool $clearErrors = true): bool;

    public function hasErrors(?string $attribute = null): bool;

    public function getErrors(?string $attribute = null): array;

    public function getFirstErrors(): array;

    public function getFirstError(string $attribute): ?string;
}
