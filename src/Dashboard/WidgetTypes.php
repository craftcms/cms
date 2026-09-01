<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\Widgets\CraftSupport;
use CraftCms\Cms\Dashboard\Widgets\Feed;
use CraftCms\Cms\Dashboard\Widgets\MyDrafts;
use CraftCms\Cms\Dashboard\Widgets\NewUsers;
use CraftCms\Cms\Dashboard\Widgets\QuickPost;
use CraftCms\Cms\Dashboard\Widgets\RecentEntries;
use CraftCms\Cms\Dashboard\Widgets\Updates;
use Illuminate\Container\Attributes\Singleton;

/**
 * Registers widget type classes available on the dashboard.
 *
 * ```php
 * public function boot(WidgetTypes $widgetTypes): void
 * {
 *     $widgetTypes->register(MyWidget::class);
 * }
 * ```
 *
 * @extends TypeRegistry<WidgetInterface>
 */
#[Singleton]
class WidgetTypes extends TypeRegistry
{
    protected const string CONTRACT = WidgetInterface::class;

    protected const array DEFAULT_TYPES = [
        Feed::class,
        CraftSupport::class,
        NewUsers::class,
        QuickPost::class,
        RecentEntries::class,
        MyDrafts::class,
        Updates::class,
    ];
}
