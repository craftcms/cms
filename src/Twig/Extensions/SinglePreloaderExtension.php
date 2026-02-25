<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Extensions;

use CraftCms\Cms\Twig\NodeVisitors\SinglePreloader;
use Override;
use Twig\Extension\AbstractExtension;

class SinglePreloaderExtension extends AbstractExtension
{
    #[Override]
    public function getNodeVisitors(): array
    {
        return [
            new SinglePreloader,
        ];
    }
}
