<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\User\Models\User;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;

use function CraftCms\Cms\craftAuth;

uses(DatabaseTestCase::class);

it('acts as a user model set on the Craft guard without logging them in', function() {
    Cms::config()->authGuard = 'craft';
    $user = User::factory()->create();
    $logins = 0;
    Event::listen(Login::class, function() use (&$logins) {
        $logins++;
    });

    craftAuth()->setUser($user);

    expect(craftAuth()->id())->toBe($user->id)
        ->and(app('Craft')->getUser()->getIdentity()?->id)->toBe($user->id)
        ->and($logins)->toBe(0)
        ->and($user->fresh()->lastLoginDate)->toBeNull();
});
