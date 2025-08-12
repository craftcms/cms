<?php

use CraftCms\Cms\Db\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** @since 6.0.0 */
return new class extends Migration implements \yii\db\MigrationInterface
{
    public function up(): bool
    {
        Schema::table(Table::USERS, function (Blueprint $table) {
            $table->rememberToken();
        });

        return true;
    }

    public function down(): bool
    {
        Schema::dropColumns(Table::USERS, 'remember_token');

        return true;
    }
};
