<?php

declare(strict_types=1);

namespace CraftCms\Cms\Livewire;

use Livewire\Livewire;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Lets Twig templates embed Livewire components, such as `{{ livewire('craft::cart', {entry}) }}`.
 *
 * @since 6.0.0
 */
class LivewireTwigExtension extends AbstractExtension
{
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'livewire',
                fn (string $name, array $params = [], ?string $key = null): string => Livewire::mount($name, $params, $key),
                ['is_safe' => ['html']],
            ),
        ];
    }
}
