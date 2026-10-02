<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Field\LinkTypes;
use CraftCms\Cms\Field\LinkTypes\BaseLinkType;
use CraftCms\Cms\Plugin\Plugin;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasLinkTypes
{
    /**
     * Array of link types to register.
     *
     * @var class-string<BaseLinkType>[]
     */
    protected array $linkTypes = [];

    public function bootHasLinkTypes(): void
    {
        $this->app->make(LinkTypes::class)->register(...$this->linkTypes);
    }
}
