<?php

declare(strict_types=1);

namespace craft\base;

use CraftCms\Cms\Gql\Contracts\GqlInlineFragmentInterface;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.3.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Gql\Contracts\GqlInlineFragmentInterface} instead.
     */
    interface GqlInlineFragmentFieldInterface
    {
    }
}

class_alias(GqlInlineFragmentInterface::class, GqlInlineFragmentFieldInterface::class);
