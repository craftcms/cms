<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\License\License;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;

use function Pest\Laravel\actingAs;

it('can initialize from a handle', function () {
    expect(Edition::fromHandle('solo'))->toBe(Edition::Solo);

    $this->expectException(InvalidArgumentException::class);

    Edition::fromHandle('invalid');
});

it('can get the handle', function () {
    expect(Edition::Solo->handle())->toBe('solo');
});

it('can get the current edition', function () {
    expect(Edition::get())->toBe(Edition::Pro); // Default

    /**
     * It gets from project config
     */
    Context::forgetHidden(Edition::class);
    ProjectConfig::set('system.edition', 'solo');
    expect(Edition::get())->toBe(Edition::Solo);

    ProjectConfig::reset();

    /**
     * It gets from env
     */
    Context::forgetHidden(Edition::class);
    $_SERVER['CRAFT_EDITION'] = 'team';

    expect(Edition::get())->toBe(Edition::Team);
});

it('can set the current edition', function () {
    expect(Edition::get())->toBe(Edition::Pro);

    Edition::set(Edition::Enterprise);

    expect(Edition::get())->toBe(Edition::Enterprise);

    Edition::set(0);

    expect(Edition::get())->toBe(Edition::Solo);
});

it('can get the current licensed edition', function () {
    expect(Edition::getLicensed())->toBeNull();

    Cache::put(License::CACHE_KEY_LICENSE_INFO, [
        'craft' => [
            'edition' => 'team',
        ],
    ]);

    expect(Edition::getLicensed())->toBe(Edition::Team);
});

it('can determine if the current edition is wrong', function () {
    Edition::set(Edition::Pro);

    Cache::put(License::CACHE_KEY_LICENSE_INFO, [
        'craft' => [
            'edition' => 'team',
        ],
    ]);

    $_SERVER['CRAFT_NO_TRIALS'] = false;

    expect(Edition::isWrong())->toBeFalse();

    $_SERVER['CRAFT_NO_TRIALS'] = true;

    expect(Edition::isWrong())->toBeTrue();

    // Reset
    unset($_SERVER['CRAFT_NO_TRIALS']);
});

it('can determine if the edition can be tested', function () {
    $_SERVER['CRAFT_NO_TRIALS'] = false;

    expect(Edition::canTest())->toBeTrue();

    $_SERVER['CRAFT_NO_TRIALS'] = true;

    expect(Edition::canTest())->toBeFalse();

    unset($_SERVER['CRAFT_NO_TRIALS']);
});

it('determines if socialite is available for the current edition', function () {
    Edition::set(Edition::Team);
    expect(Edition::get()->oAuthAvailable())->toBeFalse();

    Edition::set(Edition::Pro);
    expect(Edition::get()->oAuthAvailable())->toBeFalse();

    Cms::config()->oAuthProviders(['foo']);

    Edition::set(Edition::Pro);
    expect(Edition::get()->oAuthAvailable())->toBeTrue();
});

it('determines if the edition can be upgraded', function () {
    Edition::set(Edition::Solo);

    // Not logged in
    expect(Edition::canUpgrade())->toBefalse();

    actingAs(new User(['admin' => false]));

    // Not an admin
    expect(Edition::canUpgrade())->toBefalse();

    actingAs(new User(['admin' => true]));

    expect(Edition::canUpgrade())->toBeTrue();

    Cms::config()->allowAdminChanges = false;

    // No admin changes
    expect(Edition::canUpgrade())->toBeFalse();

    Cms::config()->allowAdminChanges = true;

    Edition::set(Edition::Pro);

    // Pro is already the "max"
    expect(Edition::canUpgrade())->toBeFalse();
});

it('can require a certain edition', function (Edition $edition, Edition|int $requiredEdition, bool $orBetter, bool $throws) {
    Edition::set($edition);

    Cms::setIsInstalled();
    ProjectConfig::reset();

    $thrown = false;
    try {
        Edition::require($requiredEdition, $orBetter);
    } catch (Edition\Exceptions\WrongEditionException) {
        $thrown = true;
    }

    expect($thrown)->toBe($throws);
})->with([
    [Edition::Solo, Edition::Solo, 'orBetter' => false, 'throws' => false],
    [Edition::Solo, Edition::Solo, 'orBetter' => true, 'throws' => false],
    [Edition::Solo, Edition::Team, 'orBetter' => true, 'throws' => true],
    [Edition::Solo, Edition::Team, 'orBetter' => false, 'throws' => true],
    [Edition::Team, Edition::Solo, 'orBetter' => true, 'throws' => false],
    [Edition::Team, Edition::Solo, 'orBetter' => false, 'throws' => true],
]);
