<?php

declare(strict_types=1);

use CraftCms\Cms\Database\LaravelMigrations;
use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        app(LaravelMigrations::class)->ensurePasswordResetTokensTable();

        $columns = array_filter([
            'verificationCode',
            'verificationCodeIssuedDate',
        ], fn (string $column) => Schema::hasColumn(Table::USERS, $column));

        if ($columns !== []) {
            Schema::table(Table::USERS, function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(Table::PASSWORD_RESET_TOKENS);

        Schema::table(Table::USERS, function (Blueprint $table) {
            $table->string('verificationCode')->nullable();
            $table->dateTime('verificationCodeIssuedDate')->nullable();
        });
        Schema::createIndex(Table::USERS, ['verificationCode']);
    }
};
