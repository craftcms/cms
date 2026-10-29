<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * The index page each element type was last viewed at, so leaving an element's
 * editor lands back on that source, page, search and sort instead of wherever
 * the element type's bare index happens to open.
 *
 * Index URLs carry all of that state, so the URL itself is what's kept.
 *
 * @since 6.0.0
 */
final class ElementIndexReturnUrl
{
    private const string SESSION_KEY = 'craft.elementIndexReturnUrls';

    /** @param  class-string<ElementInterface>  $elementType */
    public static function remember(string $elementType, Request $request): void
    {
        // A prefetch is a link being hovered, not the index being viewed.
        if (! $request->isMethod('GET') || $request->prefetch() || ! $request->hasSession()) {
            return;
        }

        $urls = Session::get(self::SESSION_KEY, []);
        $urls[$elementType] = $request->fullUrl();
        Session::put(self::SESSION_KEY, $urls);
    }

    /** @param  class-string<ElementInterface>  $elementType */
    public static function get(string $elementType): ?string
    {
        $url = Session::get(self::SESSION_KEY, [])[$elementType] ?? null;

        return is_string($url) ? $url : null;
    }
}
