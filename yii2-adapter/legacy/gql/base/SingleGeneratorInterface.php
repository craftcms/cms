<?php

declare(strict_types=1);

namespace craft\gql\base;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Gql\Contracts\SingleGeneratorInterface} instead.
     */
    interface SingleGeneratorInterface extends \CraftCms\Cms\Gql\Contracts\SingleGeneratorInterface
    {
    }
}

class_alias(\CraftCms\Cms\Gql\Contracts\SingleGeneratorInterface::class, SingleGeneratorInterface::class);
