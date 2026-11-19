<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Data;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * Holds a link’s eager loader and eager-loaded element. Clones of the link share it, and it serializes empty.
 *
 * @internal
 *
 * @since 6.0.0
 */
class LinkEagerLoadState
{
    public ?Closure $loader = null;

    public ?ElementInterface $element = null;

    /**
     * @return array{}
     */
    public function __serialize(): array
    {
        return [];
    }
}
