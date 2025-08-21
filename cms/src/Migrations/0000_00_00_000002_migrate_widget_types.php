<?php

use craft\widgets\CraftSupport;
use craft\widgets\Feed;
use craft\widgets\MissingWidget;
use craft\widgets\MyDrafts;
use craft\widgets\NewUsers;
use craft\widgets\QuickPost;
use craft\widgets\RecentEntries;
use craft\widgets\Updates;
use CraftCms\Cms\Db\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use yii\db\MigrationInterface;

/**
 * @since 6.0.0
 */
return new class extends Migration implements MigrationInterface
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

    public function up(): bool
    {
        Schema::table(Table::WIDGETS, function (Blueprint $table) {
            $table->rememberToken();
        });

        foreach ($this->map as $old => $new) {
            DB::table(Table::WIDGETS)
                ->where('type', $old)
                ->update(['type' => $new]);
        }

        return true;
    }

    public function down(): bool
    {
        foreach ($this->map as $old => $new) {
            DB::table(Table::WIDGETS)
                ->where('type', $new)
                ->update(['type' => $old]);
        }

        return true;
    }
};
