<?php

declare(strict_types=1);

use CraftCms\Cms\Console\TerminalCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->isDdevProject = Illuminate\Support\Facades\Request::server('IS_DDEV_PROJECT') ?? null;
    $_SERVER['IS_DDEV_PROJECT'] = 'false';
});

afterEach(function () {
    if ($this->isDdevProject === null) {
        unset($_SERVER['IS_DDEV_PROJECT']);
    } else {
        $_SERVER['IS_DDEV_PROJECT'] = $this->isDdevProject;
    }
});

it('uses the plain commands outside of local development environments', function () {
    $commands = app(TerminalCommands::class);

    expect($commands->craft('update:info'))->toBe('php craft update:info')
        ->and($commands->artisan('passport:keys'))->toBe('php artisan passport:keys')
        ->and($commands->composer('install'))->toBe('composer install')
        ->and($commands->environment())->toBeNull();
});

it('uses DDEV’s wrappers in DDEV projects', function (string $value, ?string $environment, string $craft, string $artisan, string $composer) {
    $_SERVER['IS_DDEV_PROJECT'] = $value;
    $commands = app(TerminalCommands::class);

    expect($commands->environment())->toBe($environment)
        ->and($commands->craft('update:info'))->toBe($craft)
        ->and($commands->artisan('passport:keys'))->toBe($artisan)
        ->and($commands->composer('install'))->toBe($composer);
})->with([
    'true' => ['true', 'ddev', 'ddev artisan craft:update:info', 'ddev artisan passport:keys', 'ddev composer install'],
    'false' => ['false', null, 'php craft update:info', 'php artisan passport:keys', 'composer install'],
]);

it('uses Valet’s wrappers for Valet sites isolated to a PHP version', function () {
    // Valet’s front controller defines VALET_HOME_PATH, so this stays defined for the rest of the process
    $home = sys_get_temp_dir().'/craft-valet-'.uniqid();
    File::ensureDirectoryExists("$home/Nginx");
    File::put("$home/Nginx/isolated.test", "# ISOLATED_PHP_VERSION=php@8.4\nserver {}\n");
    File::put("$home/Nginx/api.isolated.test", "server {}\n");
    File::put("$home/Nginx/secure.test", "server {}\n");
    defined('VALET_HOME_PATH') || define('VALET_HOME_PATH', $home);
    $commands = app(TerminalCommands::class);
    $commandsFor = function (string $host) use ($commands): array {
        app()->instance('request', Request::create("https://$host/"));

        return [$commands->craft('update all'), $commands->artisan('passport:keys'), $commands->composer('install')];
    };

    try {
        $isolated = ['valet php craft update all', 'valet php artisan passport:keys', 'valet composer install'];
        $plain = ['php craft update all', 'php artisan passport:keys', 'composer install'];

        expect($commandsFor('isolated.test'))->toBe($isolated)
            ->and($commandsFor('www.isolated.test'))->toBe($isolated)
            ->and($commandsFor('a.b.isolated.test'))->toBe($isolated)
            ->and($commandsFor('v1.api.isolated.test'))->toBe($plain)
            ->and($commandsFor('secure.test'))->toBe($plain)
            ->and($commandsFor('unconfigured.test'))->toBe($plain);

        // DDEV wins, since a DDEV project could also be linked in Valet
        $_SERVER['IS_DDEV_PROJECT'] = 'true';
        expect($commandsFor('isolated.test'))->toBe(['ddev artisan craft:update all', 'ddev artisan passport:keys', 'ddev composer install']);
    } finally {
        File::deleteDirectory($home);
    }
});
