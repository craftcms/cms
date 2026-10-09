<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Data;

use CraftCms\Cms\Component\Component;

/**
 * @since 6.0.0
 */
class EntryTypeIndexData extends Component
{
    public int $id;

    public string $handle;

    public string $title;

    public string $chip;

    public string $usages;
}
