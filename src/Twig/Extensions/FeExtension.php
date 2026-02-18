<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Extensions;

use Illuminate\Support\Collection;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Front-end Twig extension
 */
class FeExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * {@inheritdoc}
     */
    public function getGlobals(): array
    {
        return [
            '_globals' => Collection::make(),
        ];
    }
}
