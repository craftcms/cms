<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable(Table::ASSETCONTENTS)) {
            return;
        }

        Schema::create(Table::ASSETCONTENTS, function (Blueprint $table): void {
            $table->integer('assetId')->primary();
            // Laravel's MySQL binary type is a 64 KB blob
            if (DB::isMysql()) {
                $table->rawColumn('content', 'longblob');
            } else {
                $table->binary('content');
            }
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->foreign('assetId')->references('id')->on(Table::ASSETS)->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Table::ASSETCONTENTS);
    }
};
