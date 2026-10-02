<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table(Table::RECOVERYCODES)
            ->whereNotNull('recoveryCodes')
            ->lazyById()
            ->each(fn (object $record) => DB::table(Table::RECOVERYCODES)
                ->where('id', $record->id)
                ->update(['recoveryCodes' => Crypt::encryptString((string) $record->recoveryCodes)]));
    }

    public function down(): void
    {
        DB::table(Table::RECOVERYCODES)
            ->whereNotNull('recoveryCodes')
            ->lazyById()
            ->each(fn (object $record) => DB::table(Table::RECOVERYCODES)
                ->where('id', $record->id)
                ->update(['recoveryCodes' => Crypt::decryptString((string) $record->recoveryCodes)]));
    }
};
