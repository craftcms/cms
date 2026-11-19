<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Widgets\CraftSupport;
use CraftCms\Cms\Dashboard\Widgets\Feed;
use CraftCms\Cms\Dashboard\Widgets\MissingWidget;
use CraftCms\Cms\Dashboard\Widgets\MyDrafts;
use CraftCms\Cms\Dashboard\Widgets\NewUsers;
use CraftCms\Cms\Dashboard\Widgets\QuickPost;
use CraftCms\Cms\Dashboard\Widgets\RecentEntries;
use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, string> */
    private array $map = [
        'craft\widgets\CraftSupport' => CraftSupport::class,
        'craft\widgets\Feed' => Feed::class,
        'craft\widgets\MissingWidget' => MissingWidget::class,
        'craft\widgets\MyDrafts' => MyDrafts::class,
        'craft\widgets\NewUsers' => NewUsers::class,
        'craft\widgets\QuickPost' => QuickPost::class,
        'craft\widgets\RecentEntries' => RecentEntries::class,
        // Removed since; 2026_11_17_000001_replace_updates_widget_with_about_craft replaces these with About Craft CMS widgets.
        'craft\widgets\Updates' => 'CraftCms\\Cms\\Dashboard\\Widgets\\Updates',
    ];

    public function up(): void
    {
        foreach ($this->map as $old => $new) {
            DB::table(Table::WIDGETS)
                ->where('type', $old)
                ->update(['type' => $new]);
        }
    }

    public function down(): void
    {
        foreach ($this->map as $old => $new) {
            DB::table(Table::WIDGETS)
                ->where('type', $new)
                ->update(['type' => $old]);
        }
    }
};
