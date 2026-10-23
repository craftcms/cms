<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use Override;

/**
 * The Inertia payload for an element type's index at whatever route an app or plugin registered for it.
 *
 * @since 6.0.0
 */
class ElementTypeIndexViewModel extends ContentIndexViewModel
{
    #[Override]
    protected function indexUrl(): ?string
    {
        return $this->request->url();
    }
}
