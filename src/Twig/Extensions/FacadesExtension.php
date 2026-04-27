<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Extensions;

use CraftCms\Cms\Twig\Variables\Facade as TwigFacadeVariable;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Facade;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class FacadesExtension extends AbstractExtension implements GlobalsInterface
{
    public function getGlobals(): array
    {
        return collect(AliasLoader::getInstance()->getAliases())
            ->filter(fn (string $class) => class_exists($class) && is_subclass_of($class, Facade::class))
            ->map(fn (string $class) => new TwigFacadeVariable($class))
            ->all();
    }
}
