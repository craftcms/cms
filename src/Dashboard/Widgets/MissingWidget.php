<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use CraftCms\Cms\Component\Concerns\MissingComponentTrait;
use CraftCms\Cms\Component\Contracts\MissingComponentInterface;
use Override;

/**
 * @since 6.0.0
 */
class MissingWidget extends Widget implements MissingComponentInterface
{
    use MissingComponentTrait;

    #[Override]
    public function component(): ?string
    {
        return null;
    }
}
