<?php

use craft\db\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use yii\db\MigrationInterface;

/** @since 6.0.0 */
return new class extends Migration implements MigrationInterface
{
    public function up(): bool
    {
        Schema::table(Table::withoutYiiPlaceholder(Table::USERS), function (Blueprint $table) {
            $table->rememberToken();
        });

        return true;
    }

    public function down(): bool
    {
        Schema::dropColumns(Table::withoutYiiPlaceholder(Table::USERS), 'remember_token');

        return true;
    }
};
