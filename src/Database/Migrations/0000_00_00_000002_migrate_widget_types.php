<?php

declare(strict_types=1);

use craft\widgets\CraftSupport;
use craft\widgets\Feed;
use craft\widgets\MissingWidget;
use craft\widgets\MyDrafts;
use craft\widgets\NewUsers;
use craft\widgets\QuickPost;
use craft\widgets\RecentEntries;
use craft\widgets\Updates;
use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $map = [
        CraftSupport::class => \CraftCms\Cms\Dashboard\Widgets\CraftSupport::class,
        Feed::class => \CraftCms\Cms\Dashboard\Widgets\Feed::class,
        MissingWidget::class => \CraftCms\Cms\Dashboard\Widgets\MissingWidget::class,
        MyDrafts::class => \CraftCms\Cms\Dashboard\Widgets\MyDrafts::class,
        NewUsers::class => \CraftCms\Cms\Dashboard\Widgets\NewUsers::class,
        QuickPost::class => \CraftCms\Cms\Dashboard\Widgets\QuickPost::class,
        RecentEntries::class => \CraftCms\Cms\Dashboard\Widgets\RecentEntries::class,
        Updates::class => \CraftCms\Cms\Dashboard\Widgets\Updates::class,
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
