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
        Schema::dropIfExists('craftidtokens');
    }

    public function down(): void
    {
        Schema::create('craftidtokens', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('userId');
            $table->text('accessToken');
            $table->dateTime('expiryDate')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::table('craftidtokens', fn (Blueprint $table) => $table->foreign('userId')->references('id')->on(Table::USERS)->cascadeOnDelete());
    }
};
