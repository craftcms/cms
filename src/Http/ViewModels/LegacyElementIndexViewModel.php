<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

/**
 * The Inertia payload for an element index declared by a Twig template.
 *
 * {@see ContentIndexViewModel} is already generic — everything type-specific it
 * exposes it asks the element type for — so a screen that is just
 * `{% extends "_layouts/elementindex" %}` needs nothing from it but the one
 * thing the base can't know: where this index lives.
 *
 * The base leaves {@see ContentIndexViewModel::indexUrl()} null, which a screen
 * with a tidier URL of its own overrides. Here the index's URL is simply the
 * URL being requested, whatever route the plugin registered for it.
 *
 * @internal
 *
 * @deprecated Exists only while Twig-declared element indexes render through the Vue index.
 */
class LegacyElementIndexViewModel extends ContentIndexViewModel
{
    #[\Override]
    protected function indexUrl(): ?string
    {
        return $this->request->url();
    }
}
