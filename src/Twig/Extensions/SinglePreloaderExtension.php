<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Extensions;

use craft\web\twig\nodevisitors\SinglePreloader;
use Override;
use Twig\Extension\AbstractExtension;

class SinglePreloaderExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getNodeVisitors(): array
    {
        return [
            new SinglePreloader,
        ];
    }
}
