<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Elements\ContentBlock;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, class-string> */
    private array $map = [
        'craft\elements\Address' => Address::class,
        'craft\elements\Asset' => Asset::class,
        'craft\elements\ContentBlock' => ContentBlock::class,
        'craft\elements\Entry' => Entry::class,
        'craft\elements\User' => User::class,
    ];

    /** @var array<string, string> */
    private array $tables = [
        Table::ELEMENTS => 'type',
        Table::FIELDLAYOUTS => 'type',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table => $column) {
            foreach ($this->map as $old => $new) {
                DB::table($table)
                    ->where($column, $old)
                    ->update([$column => $new]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table => $column) {
            foreach ($this->map as $old => $new) {
                DB::table($table)
                    ->where($column, $new)
                    ->update([$column => $old]);
            }
        }
    }
};
