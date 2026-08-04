<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\ProjectConfig\ProjectConfig;

beforeEach(function () {
    Cms::config()->isSystemLive = null;
});

it('fails to take the system offline when isSystemLive is config overridden', function () {
    Cms::config()->isSystemLive = true;

    $this->artisan('craft:off')
        ->expectsOutputToContain('It\'s not possible to toggle the system status when the `isSystemLive` config setting is set.')
        ->assertExitCode(1);
});

it('fails to take the system online when isSystemLive is config overridden', function () {
    Cms::config()->isSystemLive = false;

    $this->artisan('craft:on')
        ->expectsOutputToContain('It\'s not possible to toggle the system status when the `isSystemLive` config setting is set.')
        ->assertExitCode(1);
});

it('reports when the system is already offline', function () {
    setProjectConfigValue('system.live', false);

    $this->artisan('craft:off')
        ->expectsOutputToContain('The system is already offline.')
        ->assertSuccessful();
});

it('reports when the system is already online', function () {
    setProjectConfigValue('system.live', true);

    $this->artisan('craft:on')
        ->expectsOutputToContain('The system is already online.')
        ->assertSuccessful();
});

it('takes the system offline', function () {
    setProjectConfigValue('system.live', true);

    $this->artisan('craft:off')
        ->expectsOutputToContain('The system is now offline.')
        ->assertSuccessful();

    expect(app(ProjectConfig::class)->get('system.live'))->toBeFalse();
});

it('takes the system online', function () {
    setProjectConfigValue('system.live', false);

    $this->artisan('craft:on')
        ->expectsOutputToContain('The system is now online.')
        ->assertSuccessful();

    expect(app(ProjectConfig::class)->get('system.live'))->toBeTrue();
});

it('sets the retry duration when taking the system offline', function () {
    setProjectConfigValue('system.live', true);

    $this->artisan('craft:off --retry=60')
        ->expectsOutputToContain('The system is now offline.')
        ->expectsOutputToContain('The retry duration is now set to 60.')
        ->assertSuccessful();

    expect(app(ProjectConfig::class)->get('system.live'))->toBeFalse()
        ->and(app(ProjectConfig::class)->get('system.retryDuration'))->toBe(60);
});

it('removes the retry duration when retry is zero', function () {
    setProjectConfigValue('system.live', true);
    setProjectConfigValue('system.retryDuration', 120);

    $this->artisan('craft:off --retry=0')
        ->expectsOutputToContain('The system is now offline.')
        ->expectsOutputToContain('The retry duration has been removed.')
        ->assertSuccessful();

    expect(app(ProjectConfig::class)->get('system.live'))->toBeFalse()
        ->and(app(ProjectConfig::class)->get('system.retryDuration'))->toBeNull();
});

it('does not change retry duration when already offline', function () {
    setProjectConfigValue('system.live', false);
    setProjectConfigValue('system.retryDuration', 120);

    $this->artisan('craft:off --retry=60')
        ->expectsOutputToContain('The system is already offline.')
        ->assertSuccessful();

    expect(app(ProjectConfig::class)->get('system.retryDuration'))->toBe(120);
});

it('leaves the retry duration untouched when taking the system online', function () {
    setProjectConfigValue('system.live', false);
    setProjectConfigValue('system.retryDuration', 120);

    $this->artisan('craft:on')
        ->expectsOutputToContain('The system is now online.')
        ->assertSuccessful();

    expect(app(ProjectConfig::class)->get('system.live'))->toBeTrue()
        ->and(app(ProjectConfig::class)->get('system.retryDuration'))->toBe(120);
});

function setProjectConfigValue(string $path, mixed $value): void
{
    $projectConfig = app(ProjectConfig::class);
    $originalReadOnly = $projectConfig->readOnly;
    $originalWriteYamlAutomatically = $projectConfig->writeYamlAutomatically;

    try {
        $projectConfig->readOnly = false;
        $projectConfig->writeYamlAutomatically = false;
        $projectConfig->set($path, $value, null, false);
        $projectConfig->saveModifiedConfigData();
        $projectConfig->reset();
    } finally {
        $projectConfig->readOnly = $originalReadOnly;
        $projectConfig->writeYamlAutomatically = $originalWriteYamlAutomatically;
    }
}
