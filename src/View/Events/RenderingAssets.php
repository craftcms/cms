<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Events;

use CraftCms\Cms\View\HtmlStack;

/**
 * Fired before the {@see HtmlStack} renders registered assets.
 *
 * Listeners should use this event to flush any pending asset
 * registrations into the registry before rendering occurs.
 */
final class RenderingAssets {}
