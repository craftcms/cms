<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

test('rejects passwords that do not satisfy the password policy', function () {
    $user = User::factory()->create();
    $originalPassword = $user->password;

    $this->artisan('craft:users:set-password', [
        'user' => $user->id,
        'password' => 'short',
    ])
        ->expectsOutputToContain('should contain at least 8 characters')
        ->assertFailed();

    expect($user->fresh()->password)->toBe($originalPassword);
});

test('fails when the user cannot be saved', function () {
    $user = User::factory()->create();
    $originalPassword = $user->password;

    Event::listen(function (ElementSaving $event) use ($user) {
        if ($event->element->id === $user->id) {
            $event->isValid = false;
        }
    });

    $this->artisan('craft:users:set-password', [
        'user' => $user->id,
        'password' => 'new-password',
    ])->assertFailed();

    expect($user->fresh()->password)->toBe($originalPassword);
});

test('revokes existing sessions after changing the password', function () {
    $user = User::factory()->create([
        'rememberToken' => 'old-remember-token',
    ]);

    DB::table(Table::SESSIONS)->insert([
        'id' => 'existing-session',
        'user_id' => $user->id,
        'payload' => '',
        'last_activity' => now()->timestamp,
    ]);

    $this->artisan('craft:users:set-password', [
        'user' => $user->id,
        'password' => 'new-password',
    ])->assertSuccessful();

    $user->refresh();

    expect(Hash::check('new-password', $user->password))->toBeTrue()
        ->and($user->rememberToken)->not()->toBe('old-remember-token')
        ->and(DB::table(Table::SESSIONS)->where('user_id', $user->id)->exists())->toBeFalse();
});
