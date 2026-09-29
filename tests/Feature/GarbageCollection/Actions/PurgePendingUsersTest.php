<?php

declare(strict_types=1);

use Carbon\CarbonInterval;
use CraftCms\Cms\Cms;
use CraftCms\Cms\GarbageCollection\Actions\PurgePendingUsers;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

beforeEach(function () {
    $this->user = User::find()->id(UserModel::factory()->createElement(['pending' => true])->id)->one();

    Password::broker()->createToken($this->user);
    DB::table('password_reset_tokens')
        ->where('email', $this->user->email)
        ->update(['created_at' => now()->subDays(10)]);
});

it('purges pending users with expired activation tokens', function () {
    Cms::config()->purgePendingUsersDuration = CarbonInterval::day()->totalSeconds;

    app(PurgePendingUsers::class)();

    expect(User::find()->id($this->user->id)->status(null)->exists())->toBeFalse();
});

it('does not purge pending users when the purge duration is zero', function () {
    Cms::config()->purgePendingUsersDuration = 0;

    app(PurgePendingUsers::class)();

    expect(User::find()->id($this->user->id)->status(null)->exists())->toBeTrue();
});
