<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use Illuminate\Container\Attributes\Scoped;

/**
 * Carries a Twig-rendered control panel screen's fragments out to the response.
 *
 * A screen that came from `asCpScreen()` hands its parts to the shell as props
 * directly. One that is just a template extending `_layouts/cp` can't: it
 * renders a document, and a template returns a string, not a response.
 *
 * So `_layouts/cp` captures its fragments — which it already did, to build that
 * document — and puts them here instead of drawing them, by way of
 * `_layouts/cp-fragments`. The response layer picks them up afterwards and
 * renders the shell around them.
 *
 * Collecting is the default, because every template that extends
 * `_layouts/cp` is a Craft 5-era screen — no Inertia page is a Twig template —
 * and a template that doesn't extend it never reaches the collecting layout. So
 * an unrelated render leaves {@see self::fragments()} null and its document
 * stands as it always did. That is the whole of the detection: there is no
 * inspecting a template to see what it inherits.
 *
 * {@see self::disable()} is the way out, for a screen that has to render as its
 * own document.
 *
 * Removal: drop this class, the two `craft.cp` methods that talk to it,
 * `_layouts/cp-fragments.twig`, the middleware that renders what it collects,
 * and restore `_layouts/cp`'s `{% extends %}` to name `_layouts/basecp.twig`
 * outright.
 *
 * @internal
 *
 * @deprecated Exists only while Twig-rendered screens render inside the Inertia shell.
 */
#[Scoped]
final class LegacyScreenFragments
{
    /** The layout that collects instead of drawing. */
    public const FRAGMENT_LAYOUT = '_layouts/cp-fragments.twig';

    /** The document `_layouts/cp` extends when it isn't collecting. */
    public const DOCUMENT_LAYOUT = '_layouts/basecp.twig';

    private bool $enabled = true;

    /** @var array<string, mixed>|null */
    private ?array $fragments = null;

    /**
     * Makes the next render draw its own document instead of collecting.
     *
     * For a screen that has to be a document in its own right. Lasts until
     * {@see self::reset()}, which the render path calls once it's done with a
     * response.
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    /** The layout `_layouts/cp` should extend for this render. */
    public function layout(): string
    {
        return $this->enabled ? self::FRAGMENT_LAYOUT : self::DOCUMENT_LAYOUT;
    }

    /** @param array<string, mixed> $fragments */
    public function collect(array $fragments): void
    {
        $this->fragments = $fragments;
    }

    /**
     * The collected fragments, or null if this render drew a document.
     *
     * @return array<string, mixed>|null
     */
    public function fragments(): ?array
    {
        return $this->fragments;
    }

    /**
     * Forgets everything, so one render can't be mistaken for the next.
     *
     * A response may render more than one template — an error page after a
     * failure, say — and only the one the response is actually sending should
     * decide how it's sent.
     */
    public function reset(): void
    {
        $this->enabled = true;
        $this->fragments = null;
    }
}
