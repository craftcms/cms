<?php

use craft\db\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration implements \yii\db\MigrationInterface
{
    private array $map = [
        \craft\widgets\CraftSupport::class => \CraftCms\Cms\Dashboard\Widgets\CraftSupport::class,
        \craft\widgets\Feed::class => \CraftCms\Cms\Dashboard\Widgets\Feed::class,
        \craft\widgets\MissingWidget::class => \CraftCms\Cms\Dashboard\Widgets\MissingWidget::class,
        \craft\widgets\MyDrafts::class => \CraftCms\Cms\Dashboard\Widgets\MyDrafts::class,
        \craft\widgets\NewUsers::class => \CraftCms\Cms\Dashboard\Widgets\NewUsers::class,
        \craft\widgets\QuickPost::class => \CraftCms\Cms\Dashboard\Widgets\QuickPost::class,
        \craft\widgets\RecentEntries::class => \CraftCms\Cms\Dashboard\Widgets\RecentEntries::class,
        \craft\widgets\Updates::class => \CraftCms\Cms\Dashboard\Widgets\Updates::class,
    ];

    public function up(): bool
    {
        Schema::table(Table::withoutYiiPlaceholder(Table::WIDGETS), function (Blueprint $table) {
            $table->rememberToken();
        });

        foreach ($this->map as $old => $new) {
            DB::table(Table::withoutYiiPlaceholder(Table::WIDGETS))
                ->where('type', $old)
                ->update(['type' => $new]);
        }

        return true;
    }

    public function down(): bool
    {
        foreach ($this->map as $old => $new) {
            DB::table(Table::withoutYiiPlaceholder(Table::WIDGETS))
                ->where('type', $new)
                ->update(['type' => $old]);
        }

        return true;
    }
};
