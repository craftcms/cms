<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Events;

/**
 * Lets listeners add to or change the Control Panel config that may vary with each request.
 *
 * `$data` leaves out the values that only change with the project config, the user, their permissions or the locale,
 * such as `sites` and `publishableSections`. A value set here replaces one of those.
 *
 * @since 6.0.0
 */
class CpDataResolving
{
    /** @param array<string, mixed> $data */
    public function __construct(
        public array $data = [],
    ) {}
}
