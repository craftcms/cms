<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\UpdateLocale;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Users;
use Illuminate\Http\Request;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->middleware = app(UpdateLocale::class);
});

it('sets locale for site request based on site language', function () {
    $request = Request::create('/site-page');

    $this->middleware->handle($request, fn () => 'passed');

    expect(app()->getLocale())->toBe(Sites::getCurrentSite()->getLanguage());
});

it('sets locale for CP request based on user preference', function () {
    $user = User::findOne();
    actingAs($user);

    app(Users::class)->saveUserPreferences($user, ['language' => 'de']);

    $request = Request::create('/'.Cms::config()->cpTrigger.'/dashboard');
    $request->setUserResolver(fn () => $user);

    $this->middleware->handle($request, fn () => 'passed');

    expect(app()->getLocale())->toBe('de');
});

it('uses defaultCpLanguage when user has no language preference', function () {
    $user = UserModel::factory()->create()->asElement();
    actingAs($user);

    Cms::config()->defaultCpLanguage = 'fr';

    $request = Request::create('/'.Cms::config()->cpTrigger.'/dashboard');
    $request->setUserResolver(fn () => $user);

    $this->middleware->handle($request, fn () => 'passed');

    expect(app()->getLocale())->toBe('fr');
});

it('uses fallback locale when user preference is invalid', function () {
    $user = User::findOne();
    actingAs($user);

    app(Users::class)->saveUserPreferences($user, ['language' => 'invalid-locale-xyz']);

    Cms::config()->defaultCpLanguage = 'es';

    $request = Request::create('/'.Cms::config()->cpTrigger.'/dashboard');
    $request->setUserResolver(fn () => $user);

    $this->middleware->handle($request, fn () => 'passed');

    expect(app()->getLocale())->toBe('es');
});
