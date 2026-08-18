<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Twig;

use CraftCms\Aliases\Aliases;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AliasesExtension extends AbstractExtension
{
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('alias', Aliases::get(...)),
        ];
    }
}
