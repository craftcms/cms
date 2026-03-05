<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Events;

/**
 * The event that is triggered when page rendering ends.
 *
 * Listeners can populate the nullable HTML properties to override
 * the default output from {@see \CraftCms\Cms\View\HtmlStack}.
 * When a property is left `null`, the page lifecycle falls back to
 * `HtmlStack::headHtml()`, `bodyBeginHtml()`, or `bodyEndHtml()`
 * respectively.
 *
 * In practice, the yii2-adapter's `View::registerEvents()` listener
 * always sets all three properties via `View::placeholderHtml()`,
 * so the `HtmlStack` fallback only applies in a pure Laravel
 * context (without the yii2-adapter).
 */
final class EndPage
{
    public function __construct(
        /** Override for `<head>` assets. `null` = fall back to HtmlStack. */
        public ?string $headHtml = null,
        /** Override for assets at the start of `<body>`. `null` = fall back to HtmlStack. */
        public ?string $bodyBeginHtml = null,
        /** Override for assets at the end of `<body>`. `null` = fall back to HtmlStack. */
        public ?string $bodyEndHtml = null,
    ) {}
}
