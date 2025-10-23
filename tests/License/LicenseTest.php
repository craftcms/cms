<?php

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\License\License;
use CraftCms\Cms\Shared\Enums\LicenseKeyStatus;
use CraftCms\Cms\Updates\Updates;
use CraftCms\Cms\User\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->license = app(License::class);
});

it('can get the key path', function () {
    expect($this->license->keyPath())->toBeString();
});

it('can get key', function () {
    File::delete(config_path('craft/license.key'));
    expect($this->license->key())->toBeNull();

    /**
     * It can get key from file
     */
    File::put(
        config_path('craft/license.key'),
        $key = Str::random(250),
    );

    expect($this->license->key())->toBe($key);
    File::delete(config_path('craft/license.key'));
    expect($this->license->key())->toBeNull();

    /**
     * It can get key from constant
     */
    define('CRAFT_LICENSE_KEY', $key);
    expect($this->license->key())->toBe($key);
});

it('can get the shun cookie name', function () {
    expect($this->license->shunCookieName())->toEndWith('_license_shun');
});

it('can create a hash of issues', function () {
    expect($this->license->issuesHash([
        'foo',
    ]))->toBe('c4f909c8a202d8596fa19bccaa608956');
});

test('issues is empty when not logged in', function () {
    expect($this->license->issues())->toBe([]);
});

it('can get invalid license issues for craft', function () {
    actingAs(User::first());

    Cache::put(Updates::class, []);

    Cache::put(License::CACHE_KEY_LICENSE_INFO, [
        'craft' => [
            'id' => 'craft',
            'edition' => 'pro',
            'status' => LicenseKeyStatus::Invalid->value,
        ],
    ]);

    expect($this->license->issues())->toBe([
        [
            'Craft',
            'The Craft license is invalid.',
            null,
        ],
    ]);
});

it('can get trial license issues for craft', function () {
    actingAs(User::first());

    Cache::put(Updates::class, []);

    Cache::put(License::CACHE_KEY_LICENSE_INFO, [
        'craft' => [
            'id' => 'craft',
            'edition' => 'pro',
            'status' => LicenseKeyStatus::Trial->value,
        ],
    ]);

    expect($this->license->issues())->toBe([
        [
            'Craft Solo',
            'Craft requires purchase.',
            [
                'type' => 'cms-edition',
                'licenseId' => 'craft',
                'edition' => 'solo',
            ],
        ],
    ]);
});

it('can get mismatched license issues for craft', function () {
    actingAs(User::first());

    Cache::put(Updates::class, []);

    Cache::put(License::CACHE_KEY_LICENSE_INFO_HOST, 'localhost');
    Cache::put('licensedDomain', 'craftcms.com');

    Cache::put(License::CACHE_KEY_LICENSE_INFO, [
        'craft' => [
            'id' => 'craft',
            'edition' => 'pro',
            'status' => LicenseKeyStatus::Mismatched->value,
        ],
    ]);

    expect($this->license->issues())->toBe([]);

    /**
     * Make sure it doesn't detect the console
     */
    $reflection = new ReflectionClass(Application::class);
    $property = $reflection->getProperty('isRunningInConsole');
    $property->setValue(app(), false);

    expect($this->license->issues())->toBe([
        [
            'Craft',
            'The Craft CMS license key in use belongs to <a href="http://craftcms.com" rel="noopener" target="_blank">craftcms.com</a> <a class="go" href="https://craftcms.com/support/resolving-mismatched-licenses">Learn more</a>',
            null,
        ],
    ]);
});

it('can get astray license issues for craft', function () {
    actingAs(User::first());

    Cache::put(Updates::class, []);

    Cache::put(License::CACHE_KEY_LICENSE_INFO, [
        'craft' => [
            'id' => 'craft',
            'edition' => 'pro',
            'status' => LicenseKeyStatus::Astray->value,
        ],
    ]);

    $version = Cms::VERSION;

    expect($this->license->issues())->toBe([
        [
            "Craft $version",
            "Craft isn’t licensed to run version $version.",
            [
                'type' => 'cms-renewal',
                'licenseId' => 'craft',
            ],
        ],
    ]);
});

it('can get wrong edition license issues for craft', function () {
    actingAs(User::first());

    Cache::put(Updates::class, []);

    Cache::put(License::CACHE_KEY_LICENSE_INFO, [
        'craft' => [
            'id' => 'craft',
            'edition' => 'solo',
            'status' => LicenseKeyStatus::Unknown->value,
        ],
    ]);

    $edition = Edition::get();
    Edition::set(Edition::Pro);

    expect($this->license->issues())->toBe([
        [
            'Craft Pro',
            'Craft is licensed for the Solo edition, but the Pro edition is installed.',
            [
                'type' => 'cms-edition',
                'edition' => 'pro',
                'licenseId' => 'craft',
            ],
        ],
    ]);

    Edition::set($edition);
});
