<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Responses;

use CraftCms\Cms\Cp\LegacyTabsShim;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\ViewModels\LegacyElementIndexViewModel;
use CraftCms\Cms\View\LegacyReadyShim;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\template;

/**
 * Renders a Craft 5-era control panel screen inside the Inertia shell.
 *
 * Screens reach this from three directions — an `asCpScreen()` response, a
 * legacy controller's `renderTemplate()`, and a plain template extending
 * `_layouts/cp` — and they differ only in how their fragments were gathered.
 * From here they're the same thing: server-rendered strings for the shell's
 * slots.
 *
 * Both paths carry a `bridged` prop naming which one drew the screen, so the
 * header bar can say so under dev mode.
 *
 * @internal
 *
 * @since 6.0.0
 * @deprecated Exists only while Craft 5-era screens render inside the Inertia shell.
 */
final class BridgedScreen
{
    /** The shell's fallback page: it draws server-rendered HTML into the slots. */
    public const string PAGE = 'cp/Screen';

    /** The generic element index, driven by {@see LegacyElementIndexViewModel}. */
    public const string ELEMENT_INDEX_PAGE = 'cp/ElementIndex';

    /**
     * Marks `_layouts/elementindex`'s own content block.
     *
     * A child template that replaces that block replaces this with it, which is
     * how a screen that only looks like an element index is told apart from one
     * that is. Emitted as a comment so it costs nothing if it ever does reach
     * the browser.
     */
    public const string CONTENT_SENTINEL = '<!--craft-element-index-->';

    /**
     * The shell wrapped around `$variables`.
     *
     * `$variables['tabs']` is the raw tab config, as both the screen response
     * and `_layouts/cp` hold it; it's rendered here.
     *
     * @param  array<string, mixed>  $variables
     */
    public static function response(Request $request, array $variables): Response
    {
        /**
         * An element index is checked first, and deliberately before the
         * hard-visit rule below: it renders as a real Vue page with no legacy
         * JS to accommodate, so it keeps client-side navigation like any other
         * ported screen.
         */
        if (($index = self::elementIndex($request, $variables)) !== null) {
            return $index;
        }

        /**
         * Otherwise this is server-rendered legacy markup, and legacy JS
         * expects the environment a normal document build gives it: the
         * libraries (jQuery, Garnish, `cp.js` — all `Position::BodyEnd`) loaded
         * in source order, before anything that uses them. A client-side visit
         * can't offer that, so it becomes a hard visit.
         *
         * The rule is deliberately total: bridged screen + Inertia visit → hard
         * visit, with no partial case to get wrong.
         */
        if ($request->inertia()) {
            return Inertia::location($request->fullUrl());
        }

        /**
         * Legacy ready-JS can't trust `DOMContentLoaded` here — Vue mounts this
         * screen's markup after the document parses. See that class for how to
         * drop this, per screen or altogether.
         */
        LegacyReadyShim::register();

        $variables['tabs'] = self::tabs($variables['tabs'] ?? null);

        return Inertia::render(self::PAGE)
            ->with($variables)
            ->with(['screen' => ['mode' => 'page'], 'bridged' => 'screen'])
            ->toResponse($request);
    }

    /**
     * The real element index, for a screen that declared itself one.
     *
     * `_layouts/elementindex` declares it, since that layout is the one place
     * that knows the element type — and everything else the Vue index needs it
     * asks the element type for, the same way the legacy index did. So a screen
     * that is just `{% extends "_layouts/elementindex" %}` gets the new index
     * with no changes of its own.
     *
     * Returns null, leaving the legacy markup to be drawn, when the screen
     * isn't one or has replaced the index's content with its own.
     *
     * @param  array<string, mixed>  $variables
     */
    private static function elementIndex(Request $request, array $variables): ?Response
    {
        $declared = $variables['elementIndex'] ?? null;
        $elementType = is_array($declared) ? ($declared['elementType'] ?? null) : null;

        if (! is_string($elementType) || ! is_subclass_of($elementType, ElementInterface::class)) {
            return null;
        }

        /**
         * The layout put the sentinel in its own content block. Gone means a
         * child replaced that block, so this screen has content of its own that
         * the Vue index would throw away.
         */
        if (! str_contains((string) ($variables['content'] ?? ''), self::CONTENT_SENTINEL)) {
            return null;
        }

        $viewModel = new LegacyElementIndexViewModel(
            elementType: $elementType,
            request: ElementIndexRequest::createFrom($request),
            page: is_string($declared['page'] ?? null) ? $declared['page'] : null,
        );

        /**
         * Whatever a child added to the toolbar — a plugin's own New … button,
         * typically. The layout renders nothing into that block itself when the
         * Vue index is taking over, so this is the child's markup alone.
         */
        $toolbar = trim((string) ($variables['toolbar'] ?? ''));

        return Inertia::render(self::ELEMENT_INDEX_PAGE, [$viewModel])
            ->with([
                'indexUrl' => $request->url(),
                'toolbarHtml' => $toolbar !== '' ? $toolbar : null,
                'bodyClass' => $variables['bodyClass'] ?? null,
                /**
                 * A ported index puts its sources in the navigation, which it
                 * reaches through its own nav item. A Craft 5 screen has none,
                 * so it carries them itself and the shell draws them in the
                 * same place — the secondary nav, as the sidebar used to.
                 */
                'subnav' => $viewModel->sourceNavItems(),
                'elementType' => $elementType,
                'page' => $viewModel->page(),
                'sourceKey' => $viewModel->source()['key'] ?? null,
                'bridged' => 'elementIndex',
            ])
            ->toResponse($request);
    }

    /**
     * The tab strip, rendered.
     *
     * `cp/Screen` draws HTML into the shell's slots, so tabs arrive rendered
     * rather than as the raw config the Twig layout took. `<craft-tabs>` is
     * preferred: it pairs the screen's own panes to its tabs, so a legacy
     * screen gets the same tab component as the rest of the control panel
     * without its author touching anything. The shim returns null whenever it
     * isn't certain, and the legacy strip covers that.
     */
    private static function tabs(mixed $tabs): ?string
    {
        if (! is_array($tabs)) {
            return null;
        }

        return LegacyTabsShim::apply($tabs)
            ?? (count($tabs) > 1
                ? template('_includes/tabs', [
                    'tabs' => $tabs,
                ], templateMode: TemplateMode::Cp)
                : null);
    }
}
