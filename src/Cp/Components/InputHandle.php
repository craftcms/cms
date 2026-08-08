<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

/**
 * PHP counterpart to the `<craft-input-handle>` web component.
 */
class InputHandle extends Input
{
    #[\Override]
    protected bool $autocorrect = false;

    #[\Override]
    protected bool $autocapitalize = false;

    #[\Override]
    protected function tagName(): string
    {
        return 'craft-input-handle';
    }
}
