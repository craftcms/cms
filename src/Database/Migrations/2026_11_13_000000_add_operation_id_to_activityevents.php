<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn(Table::ACTIVITYEVENTS, 'operationId')) {
            return;
        }

        Schema::table(Table::ACTIVITYEVENTS, function (Blueprint $table): void {
            $table->string('operationId')->nullable()->after('rootEventId');
        });
        Schema::createIndex(Table::ACTIVITYEVENTS, ['operationId', 'occurredAt', 'id']);
    }

    public function down(): void {}
};
