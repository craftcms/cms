<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Events;

/**
 * Fired before the {@see \CraftCms\Cms\View\HtmlStack} renders registered assets.
 *
 * Listeners should use this event to flush any pending asset
 * registrations into the registry before rendering occurs.
 */
final class RenderingAssets {}
