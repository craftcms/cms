<?php

declare(strict_types=1);

namespace craft\gql\base;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Gql\Contracts\GeneratorInterface} instead.
     */
    interface GeneratorInterface extends \CraftCms\Cms\Gql\Contracts\GeneratorInterface
    {
    }
}

class_alias(\CraftCms\Cms\Gql\Contracts\GeneratorInterface::class, GeneratorInterface::class);
