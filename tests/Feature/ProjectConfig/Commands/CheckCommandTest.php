<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

it('requires an existing Craft installation', function () {
    Context::addHidden('craft.isInstalled', false);

    $this->artisan('craft:project-config:check')
        ->expectsOutputToContain('This check requires an existing Craft installation.')
        ->assertFailed();
});

it('fails when project.yaml is missing', function () {
    $this->artisan('craft:project-config:check')
        ->expectsOutputToContain('Project config file `project.yaml` was not found. Schema compatibility could not be checked.')
        ->assertFailed();
});

it('skips the check when project.yaml is missing and YAML is not required', function () {
    $this->artisan('craft:project-config:check', ['--require-yaml' => '0'])
        ->expectsOutputToContain('Project config file `project.yaml` was not found. Schema compatibility check skipped.')
        ->assertSuccessful();
});

it('fails when there were file write issues', function () {
    $projectConfig = app(ProjectConfig::class);
    $projectConfig->regenerateExternalConfig();
    $projectConfig->writeYamlAutomatically = true;
    Cache::put(ProjectConfig::FILE_ISSUES_CACHE_KEY, true);

    $this->artisan('craft:project-config:check')
        ->expectsOutputToContain('Resolve project config file-write errors before checking schema compatibility.')
        ->assertFailed();
});

it('fails when an enabled plugin cannot be loaded', function () {
    $projectConfig = app(ProjectConfig::class);

    DB::table(Table::PLUGINS)->insert([
        'handle' => 'missing-plugin',
        'version' => '1.0.0',
        'schemaVersion' => '1.0.0',
        'installDate' => now(),
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => Str::uuid()->toString(),
    ]);

    $projectConfig->muteEvents = true;
    $projectConfig->set(ProjectConfig::PATH_PLUGINS.'.missing-plugin', [
        'enabled' => true,
        'schemaVersion' => '1.0.0',
    ]);
    $projectConfig->muteEvents = false;
    $projectConfig->regenerateExternalConfig();

    app()->forgetInstance(Plugins::class);

    $this->artisan('craft:project-config:check')
        ->expectsOutputToContain('Enabled plugin "missing-plugin" could not be loaded.')
        ->assertFailed();
});

it('fails when schema versions are incompatible', function () {
    $projectConfig = app(ProjectConfig::class);
    $projectConfig->regenerateExternalConfig();

    $projectConfig->muteEvents = true;
    $projectConfig->set(ProjectConfig::PATH_SCHEMA_VERSION, '0.0.0.1');
    $projectConfig->muteEvents = false;
    $projectConfig->writeYamlFiles(true);

    $this->artisan('craft:project-config:check')
        ->expectsOutputToContain('Your project config files were created for different versions of Craft and/or plugins than what’s currently installed.')
        ->assertFailed();
});

it('passes when schema versions are compatible', function () {
    app(ProjectConfig::class)->regenerateExternalConfig();

    $this->artisan('craft:project-config:check')
        ->expectsOutputToContain('Project config schema versions are compatible with installed Craft and enabled plugins.')
        ->assertSuccessful();
});
