<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Mixins;

use Closure;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Yii2Adapter\Validation\LegacyElementRules;

final readonly class ElementMixin
{
    public function hasErrors(): Closure
    {
        return function(?string $attribute = null): bool {
            Deprecator::log('Element->hasErrors', 'Calling `->hasErrors` on an Element is deprecated. Use `->errors()->has($attribute)` or `->errors()->isNotEmpty()` instead.');

            /**
             * @var \CraftCms\Cms\Element\Element $this
             * @phpstan-ignore-next-line
             */
            return is_null($attribute)
                ? $this->errors()->isNotEmpty()
                : $this->errors()->has($attribute);
        };
    }

    public function addErrors(): Closure
    {
        return function(string $attribute, string $error = ''): void {
            Deprecator::log('Element->addErrors', 'Calling `->addErrors` on an Element is deprecated. Use `->errors()->add($attribute, $message)` instead.');

            /**
             * @var \CraftCms\Cms\Element\Element $this
             * @phpstan-ignore-next-line
             */
            $this->errors()->add($attribute, $error);
        };
    }

    public function clearErrors(): Closure
    {
        return function($attribute = null): void {
            Deprecator::log('Element->clearErrors', 'Calling `->clearErrors` on an Element is deprecated. Use `->errors()->forget()` instead.');

            if ($attribute === null) {
                /**
                 * @var \CraftCms\Cms\Element\Element $this
                 * @phpstan-ignore-next-line
                 */
                foreach ($this->errors()->getMessages() as $key => $messages) {
                    /** @phpstan-ignore-next-line */
                    $this->errors()->forget($key);
                }

                return;
            }

            /**
             * @var \CraftCms\Cms\Element\Element $this
             * @phpstan-ignore-next-line
             */
            $this->errors()->forget($attribute);
        };
    }

    public function getFirstError(): Closure
    {
        return function(string $attribute): ?string {
            Deprecator::log('Element->getFirstError', 'Calling `->getFirstError` on an Element is deprecated. Use `->getFirstErrors()` instead.');

            /**
             * @var \CraftCms\Cms\Element\Element $this
             * @phpstan-ignore-next-line
             */
            return Arr::get($this->getFirstErrors(), $attribute);
        };
    }

    public function rulesClass(): Closure
    {
        return function(): string {
            return LegacyElementRules::class;
        };
    }
}
