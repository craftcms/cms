<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Mixins;

use Closure;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Dashboard\Widgets\Widget;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\FieldLayout\FieldLayoutComponent;
use CraftCms\Cms\Filesystem\Filesystems\Filesystem;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Yii2Adapter\Validation\LegacyElementRules;

readonly class ValidateMixin
{
    public function hasErrors(): Closure
    {
        return function(?string $attribute = null): bool {
            Deprecator::log($this::class . '->hasErrors', 'Calling `->hasErrors` is deprecated. Use `->errors()->has($attribute)` or `->errors()->isNotEmpty()` instead.');

            /**
             * @var Element|Field|Field $this
             *
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
             * @var Element|Field $this
             *
             * @phpstan-ignore-next-line
             */
            return is_null($attribute)
                ? $this->errors()->getMessages()
                : $this->errors()->get($attribute);
        };
    }

    /** @return Closure(array<string, string|string[]>): void */
    public function addErrors(): Closure
    {
        return function(array $items): void {
            Deprecator::log($this::class . '->addErrors', 'Calling `->addErrors` is deprecated. Use `->errors()->merge($items)` instead.');

            /**
             * @var Element|Field $this
             *
             * @phpstan-ignore-next-line
             */
            $items = array_map(static fn (mixed $messages) => Arr::wrap($messages), $items);
            $this->errors()->merge($items);
        };
    }

    public function addError(): Closure
    {
        return function(string $attribute, string $error = ''): void {
            Deprecator::log($this::class . '->addError', 'Calling `->addError` is deprecated. Use `->errors()->add($attribute, $message)` instead.');

            /**
             * @var Volume|Widget|Element|Field|FieldLayoutComponent|Filesystem $this
             *
             * @phpstan-ignore-next-line
             */
            $this->errors()->add($attribute, $error);
        };
    }

    public function getFirstError(): Closure
    {
        return function(string $attribute): ?string {
            Deprecator::log($this::class . '->getFirstError', 'Calling `->getFirstError` is deprecated. Use `->getFirstErrors()` instead.');

            /**
             * @var Element|Field $this
             *
             * @phpstan-ignore-next-line
             */
            return Arr::get($this->getFirstErrors(), $attribute);
        };
    }

    public function getErrorSummary(): Closure
    {
        return function($showAllErrors = false) {
            Deprecator::log($this::class . '->getErrorSummary', 'Calling `->getErrorSummary` is deprecated. Use `->errors()->all()` instead.');

            /**
             * @var \CraftCms\Cms\Validation\Contracts\Validatable $this
             *
             * @phpstan-ignore-next-line
             */
            return $this->errors()->all();
        };
    }

    public function rulesClass(): Closure
    {
        return function(): string {
            return LegacyElementRules::class;
        };
    }

    public function setScenario(): Closure
    {
        return function(string $scenario) {
            Deprecator::log($this::class . '->setScenario', 'Calling `->setScenario` is deprecated. Use `->ruleset->useScenario()` instead.');

            /**
             * @var \CraftCms\RulesetValidation\Contracts\ValidatesWithRuleset $this
             *
             * @phpstan-ignore-next-line
             */
            return $this->ruleset->useScenario($scenario);
        };
    }

    public function getScenario(): Closure
    {
        return function() {
            Deprecator::log($this::class . '->getScenario', 'Calling `->getScenario` is deprecated. Use `->ruleset->getScenario()` instead.');

            /**
             * @var \CraftCms\RulesetValidation\Contracts\ValidatesWithRuleset $this
             *
             * @phpstan-ignore-next-line
             */
            return $this->ruleset->getScenario();
        };
    }
}
