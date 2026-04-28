<?php

declare(strict_types=1);

namespace craft\base;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * GqlInlineFragmentInterface defines the common interface to be implemented by GraphQL inline fragments contained by fields.
     *
     * @since 3.3.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Gql\Contracts\GqlInlineFragmentInterface} instead.
     */
    interface GqlInlineFragmentInterface
    {
    }
}

class_alias(\CraftCms\Cms\Gql\Contracts\GqlInlineFragmentInterface::class, GqlInlineFragmentInterface::class);
