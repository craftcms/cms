<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Components;

class Icon extends ViewComponent
{
    #[\Override]
    protected string $view = 'components.icon';

    public ?string $name = null;

    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
