<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable(Table::PASSWORD_RESET_TOKENS)) {
            return;
        }

        Schema::create(Table::PASSWORD_RESET_TOKENS, function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::table(Table::USERS, function (Blueprint $table) {
            $table->dropColumn(['verificationCode', 'verificationCodeIssuedDate']);
        });
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
