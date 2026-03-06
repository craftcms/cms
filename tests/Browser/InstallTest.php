<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->artisan('db:wipe', ['--force' => true]);
    Cms::setIsInstalled(false);
    Context::forgetHidden('craft.info');
    app()->forgetInstance(ProjectConfig::class);
    Cache::lock(ProjectConfig::MUTEX_NAME)->forceRelease();
    DB::reconnect();
});

afterEach(function () {
    // The browser install creates its own schema with tables but potentially
    // incomplete data (e.g. empty sites table). Wipe the DB so the next
    // test/run doesn't fail during app boot when Yii2 queries empty tables.
    $this->artisan('db:wipe', ['--force' => true]);
    RefreshDatabaseState::$migrated = false;
});

it('can install Craft CMS', function () {
    $page = $this->visit('/admin/install');

    $page->click('Install Craft CMS')
        ->click('Got it')
        ->fill('Username', 'admin')
        ->fill('Email', 'playwright@craftcms.com')
        ->fill('Password', 'NewPassword')
        ->click('Next')
        ->fill('System Name', 'Craft 6 Pest Browser')
        ->fill('Base URL', '$APP_URL')
        ->select('Language', 'en')
        ->click('Finish up')
        ->assertPathBeginsWith('/admin');

    assertDatabaseHas('users', [
        'username' => 'admin',
        'email' => 'playwright@craftcms.com',
    ]);

    assertDatabaseHas('sites', [
        'name' => 'Craft 6 Pest Browser',
        'handle' => 'default',
        'primary' => 1,
        'enabled' => 'true', // This seems wrong
        'hasUrls' => 1,
        'language' => 'en',
    ]);
});
