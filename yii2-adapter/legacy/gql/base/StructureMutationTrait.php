<?php

declare(strict_types=1);

namespace craft\gql\base;

use CraftCms\Cms\Gql\Concerns\PerformsStructureMutations;

/**
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Gql\Concerns\PerformsStructureMutations} instead.
 */
trait StructureMutationTrait
{
    use PerformsStructureMutations;
}
