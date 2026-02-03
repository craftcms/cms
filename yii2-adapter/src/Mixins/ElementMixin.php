<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Mixins;

use Closure;
use CraftCms\Cms\Support\Facades\Deprecator;

final class ElementMixin
{
    public function getSourceId(): Closure
    {
        return function(): ?int {
            Deprecator::log(__METHOD__, 'Elements’ `getSourceId()` method has been deprecated. Use `getCanonicalId()` instead.');

            /**
             * @var \CraftCms\Cms\Element\Element $this
             * @phpstan-ignore-next-line
             */
            return $this->getCanonicalId();
        };
    }

    public function getSourceUid(): Closure
    {
        return function(): ?string {
            Deprecator::log(__METHOD__, 'Elements’ `getSourceUid()` method has been deprecated. Use `getCanonicalUid()` instead.');

            /**
             * @var \CraftCms\Cms\Element\Element $this
             * @phpstan-ignore-next-line
             */
            return $this->getCanonicalUid();
        };
    }
}
