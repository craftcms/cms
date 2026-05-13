<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\DeletionBlockers;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Element\DeletionBlockers\Contracts\DeletionBlockerInterface;
use CraftCms\Cms\Element\ElementCollection;

abstract class BaseDeletionBlocker extends Component implements DeletionBlockerInterface
{
    public function __construct(
        protected readonly ElementCollection $elements,
        protected readonly bool $hardDelete,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function getDetails(): ?string
    {
        return null;
    }
}
