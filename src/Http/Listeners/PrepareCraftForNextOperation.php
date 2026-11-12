<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Listeners;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\NestedElementManager;
use CraftCms\Cms\Field\LinkTypes\BaseElementLinkType;
use CraftCms\Cms\FieldLayout\LayoutElements\Template as TemplateLayoutElement;
use CraftCms\Cms\Mcp\StdioEventDispatcher;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Route\ProjectConfigRoutes;
use CraftCms\Cms\Support\Template;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateRoots;
use Illuminate\Contracts\Foundation\Application;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\TaskReceived;
use Laravel\Octane\Events\TickReceived;

/**
 * Resets Craft’s state before Laravel Octane handles the next request, task, or tick.
 *
 * Octane boots the app once per worker and reuses it, while Craft keeps some state for the life of a request, and other
 * workers can change the project config in the meantime. {@see StdioEventDispatcher} also calls {@see prepare()} before
 * each request to the stdio MCP server, which is one long-running command.
 *
 * @internal
 *
 * @since 6.0.0
 */
class PrepareCraftForNextOperation
{
    public function handle(RequestReceived|TaskReceived|TickReceived $event): void
    {
        $this->prepare($event->sandbox);

        if ($event instanceof RequestReceived) {
            // Resolved from the root app, so it keeps the booted view finder between requests.
            $event->app->make(TemplateRoots::class)->resetViewFactory($event->sandbox, TemplateMode::forRequest($event->request));
        }
    }

    /**
     * Resets the state that every long-lived process, such as an Octane worker or the stdio MCP server, keeps between
     * the operations it handles.
     */
    public function prepare(Application $app): void
    {
        // Memoized for a request, and they depend on its user, site, or language, or on content that can change.
        Element::forgetSources();
        BaseElementLinkType::forgetFetchedElements();
        NestedElementManager::forgetRenderedPropagationKeys();
        Template::forgetFallbacks();
        TemplateLayoutElement::forgetTwig();

        // Another process may have changed the project config since this one loaded it.
        $app->make(ProjectConfig::class)->reset();
        Cms::setDefaultTimezone();
        $app->make(ProjectConfigRoutes::class)->refresh();
    }
}
