<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static int createRevision(\CraftCms\Cms\Element\Contracts\ElementInterface $canonical, int|null $creatorId = null, string|null $notes = null, array $newAttributes = [], bool $force = false)
 * @method static \CraftCms\Cms\Element\Contracts\ElementInterface revertToRevision(\CraftCms\Cms\Element\Contracts\ElementInterface $revision, int $creatorId)
 *
 * @see \CraftCms\Cms\Element\Revisions
 */
class Revisions extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Element\Revisions::class;
    }
}
