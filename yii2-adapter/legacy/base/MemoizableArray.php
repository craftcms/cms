<?php

namespace craft\base;

use CraftCms\Cms\Support\MemoizableArray as BaseMemoizableArray;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @template T
     * @extends BaseMemoizableArray<T>
     * @since 3.5.8
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\MemoizableArray} instead.
     */
    class MemoizableArray extends BaseMemoizableArray
    {
    }
}

class_alias(BaseMemoizableArray::class, MemoizableArray::class);
