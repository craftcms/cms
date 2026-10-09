<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migrations\Install;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('replaces an empty Laravel starter users table and keeps password reset tokens', function () {
    Schema::create(Table::USERS, function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create(Table::PASSWORD_RESET_TOKENS, function (Blueprint $table) {
        $table->string('email')->primary();
        $table->string('token');
        $table->timestamp('created_at')->nullable();
    });

    (new Install)->silent()->createTables();

    expect(Schema::hasColumn(Table::USERS, 'dateCreated'))->toBeTrue()
        ->and(Schema::hasColumn(Table::USERS, 'remember_token'))->toBeFalse()
        ->and(Schema::hasColumn(Table::PASSWORD_RESET_TOKENS, 'created_at'))->toBeTrue();
});

it('fails when the Laravel starter users table contains rows', function () {
    Schema::create(Table::USERS, function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });

    DB::table(Table::USERS)->insert([
        'name' => 'Existing User',
        'email' => 'existing@example.com',
        'password' => 'password',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => (new Install)->silent()->createTables())
        ->toThrow(RuntimeException::class, 'Craft cannot be installed because the existing [users] table contains rows.');
});
