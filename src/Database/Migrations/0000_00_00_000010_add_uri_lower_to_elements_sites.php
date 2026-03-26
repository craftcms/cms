<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tpetry\QueryExpressions\Function\String\Lower;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn(Table::ELEMENTS_SITES, 'uriLower')) {
            return;
        }

        Schema::table(Table::ELEMENTS_SITES, function (Blueprint $table) {
            $table->string('uriLower')
                ->after('uri')
                ->nullable()
                ->storedAs(new Lower('uri'))
                ->invisible();
        });

        Schema::createIndex(Table::ELEMENTS_SITES, ['uriLower', 'siteId']);
    }

    public function down(): void
    {
        if (! Schema::hasColumn(Table::ELEMENTS_SITES, 'uriLower')) {
            return;
        }

        Schema::table(Table::ELEMENTS_SITES, function (Blueprint $table) {
            $table->dropIndex(['uriLower', 'siteId']);
            $table->dropColumn('uriLower');
        });
    }
};
