<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Illuminate\Contracts\Support\Htmlable;
use Stringable;

class MissingComponent extends ViewComponent
{
    private string $error = '';

    private ?string $pluginName = null;

    protected function tagName(): string
    {
        return 'craft-missing-component';
    }

    public function error(string $error): static
    {
        $this->error = $error;

        return $this;
    }

    public function pluginName(?string $pluginName): static
    {
        $this->pluginName = $pluginName;

        return $this;
    }

    public function icon(string|Htmlable|Stringable|null $icon): static
    {
        $this->slots['icon'] = $icon;

        return $this;
    }

    public function action(string|Htmlable|Stringable|null $action): static
    {
        $this->slots['action'] = $action;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'error' => $this->error,
            'plugin-name' => $this->pluginName,
        ];
    }
}
