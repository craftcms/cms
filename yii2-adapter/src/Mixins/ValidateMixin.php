<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Mixins;

use Closure;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Yii2Adapter\Validation\LegacyElementRules;

final readonly class ValidateMixin
{
    public function hasErrors(): Closure
    {
        return function(?string $attribute = null): bool {
            Deprecator::log($this::class . '->hasErrors', 'Calling `->hasErrors` is deprecated. Use `->errors()->has($attribute)` or `->errors()->isNotEmpty()` instead.');

            /**
             * @var \CraftCms\Cms\Element\Element|\CraftCms\Cms\Field\Field|\CraftCms\Cms\Field\Field $this
             * @phpstan-ignore-next-line
             */
            return is_null($attribute)
                ? $this->errors()->isNotEmpty()
                : $this->errors()->has($attribute);
        };
    }

    public function getErrors(): Closure
    {
        return function(?string $attribute = null): array {
            Deprecator::log($this::class . '->getErrors', 'Calling `->getErrors` is deprecated. Use `->errors()->get($attribute)` or `->errors()->getMessages()` instead.');

            /**
             * @var \CraftCms\Cms\Element\Element|\CraftCms\Cms\Field\Field $this
             * @phpstan-ignore-next-line
             */
            return is_null($attribute)
                ? $this->errors()->getMessages()
                : $this->errors()->get($attribute);
        };
    }

    public function addErrors(): Closure
    {
        return function(string $attribute, string $error = ''): void {
            Deprecator::log($this::class . '->addErrors', 'Calling `->addErrors` is deprecated. Use `->errors()->add($attribute, $message)` instead.');

            /**
             * @var \CraftCms\Cms\Element\Element|\CraftCms\Cms\Field\Field $this
             * @phpstan-ignore-next-line
             */
            $this->errors()->add($attribute, $error);
        };
    }

    public function addError(): Closure
    {
        return function(string $attribute, string $error = ''): void {
            Deprecator::log($this::class . '->addError', 'Calling `->addError` is deprecated. Use `->errors()->add($attribute, $message)` instead.');

            /**
             * @var \CraftCms\Cms\Asset\Data\Volume|\CraftCms\Cms\Dashboard\Widgets\Widget|\CraftCms\Cms\Element\Element|\CraftCms\Cms\Field\Field|\CraftCms\Cms\FieldLayout\FieldLayoutComponent|\CraftCms\Cms\Filesystem\Filesystems\Filesystem $this
             * @phpstan-ignore-next-line
             */
            $this->errors()->add($attribute, $error);
        };
    }

    public function clearErrors(): Closure
    {
        return function($attribute = null): void {
            Deprecator::log($this::class . '->clearErrors', 'Calling `->clearErrors` is deprecated. Use `->errors()->forget()` instead.');

            if ($attribute === null) {
                /**
                 * @var \CraftCms\Cms\Element\Element|\CraftCms\Cms\Field\Field $this
                 * @phpstan-ignore-next-line
                 */
                foreach ($this->errors()->getMessages() as $key => $messages) {
                    /** @phpstan-ignore-next-line */
                    $this->errors()->forget($key);
                }

                return;
            }

            /**
             * @var \CraftCms\Cms\Element\Element|\CraftCms\Cms\Field\Field $this
             * @phpstan-ignore-next-line
             */
            $this->errors()->forget($attribute);
        };
    }

    public function getFirstError(): Closure
    {
        return function(string $attribute): ?string {
            Deprecator::log($this::class . '->getFirstError', 'Calling `->getFirstError` is deprecated. Use `->getFirstErrors()` instead.');

            /**
             * @var \CraftCms\Cms\Element\Element|\CraftCms\Cms\Field\Field $this
             * @phpstan-ignore-next-line
             */
            return Arr::get($this->getFirstErrors(), $attribute);
        };
    }

    public function getAttributeLabel(): Closure
    {
        return function(string $attribute): string {
            Deprecator::log($this::class . '->getAttributeLabel', 'Calling `->getAttributeLabel` is deprecated. Use `->attributeLabels()` instead.');

            /**
             * @var \CraftCms\Cms\Asset\Data\Volume|\CraftCms\Cms\Dashboard\Widgets\Widget|\CraftCms\Cms\Element\Element|\CraftCms\Cms\Field\Field|\CraftCms\Cms\FieldLayout\FieldLayoutComponent|\CraftCms\Cms\Filesystem\Filesystems\Filesystem $this
             * @phpstan-ignore-next-line
             */
            return $this->attributeLabels()[$attribute] ?? $attribute;
        };
    }

    public function rulesClass(): Closure
    {
        return function(): string {
            return LegacyElementRules::class;
        };
    }
}
