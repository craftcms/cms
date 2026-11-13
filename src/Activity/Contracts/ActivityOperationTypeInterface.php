<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\Contracts;

use CraftCms\Cms\User\Elements\User;

/**
 * @since 6.0.0
 */
interface ActivityOperationTypeInterface
{
    /**
     * Returns where the viewer can see the operation, or null if they can’t.
     *
     * @param  array<string, int|string>  $snapshot  What the event kept of the operation.
     */
    public static function url(string $id, array $snapshot, User $viewer): ?string;
}
