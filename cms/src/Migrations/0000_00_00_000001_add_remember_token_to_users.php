<?php

use craft\db\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration implements \yii\db\MigrationInterface
{
    public function up(): void
    {
        Schema::table(Table::withoutYiiPlaceholder(Table::USERS), function (Blueprint $table) {
            $table->rememberToken();
        });
    }

    public function down(): void
    {
        Schema::dropColumns(Table::withoutYiiPlaceholder(Table::USERS), 'remember_token');
    }
};
