<?php

declare(strict_types=1);

use CraftCms\Cms\Console\TerminalCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

afterEach(function () {
    unset($_SERVER['IS_DDEV_PROJECT']);
});

it('uses the plain commands outside of local development environments', function () {
    $commands = app(TerminalCommands::class);

    expect($commands->craft('passport:keys'))->toBe('php craft passport:keys')
        ->and($commands->composer('install'))->toBe('composer install')
        ->and($commands->environment())->toBeNull();
});

it('uses DDEV’s wrappers in DDEV projects', function (string $value, ?string $environment, string $craft, string $composer) {
    $_SERVER['IS_DDEV_PROJECT'] = $value;
    $commands = app(TerminalCommands::class);

    expect($commands->environment())->toBe($environment)
        ->and($commands->craft('passport:keys'))->toBe("$craft passport:keys")
        ->and($commands->composer('install'))->toBe("$composer install");
})->with([
    'true' => ['true', 'ddev', 'ddev craft', 'ddev composer'],
    'false' => ['false', null, 'php craft', 'composer'],
]);

it('uses Valet’s wrappers for Valet sites isolated to a PHP version', function () {
    // Valet’s front controller defines VALET_HOME_PATH, so this stays defined for the rest of the process
    $home = sys_get_temp_dir().'/craft-valet-'.uniqid();
    File::ensureDirectoryExists("$home/Nginx");
    File::put("$home/Nginx/isolated.test", "# ISOLATED_PHP_VERSION=php@8.4\nserver {}\n");
    File::put("$home/Nginx/secure.test", "server {}\n");
    defined('VALET_HOME_PATH') || define('VALET_HOME_PATH', $home);
    $commands = app(TerminalCommands::class);
    $commandsFor = function (string $host) use ($commands): array {
        app()->instance('request', Request::create("https://$host/"));

        return [$commands->craft('update all'), $commands->composer('install')];
    };

    try {
        expect($commandsFor('isolated.test'))->toBe(['valet php craft update all', 'valet composer install'])
            ->and($commandsFor('secure.test'))->toBe(['php craft update all', 'composer install'])
            ->and($commandsFor('unconfigured.test'))->toBe(['php craft update all', 'composer install']);

        // DDEV wins, since a DDEV project could also be linked in Valet
        $_SERVER['IS_DDEV_PROJECT'] = 'true';
        expect($commandsFor('isolated.test'))->toBe(['ddev craft update all', 'ddev composer install']);
    } finally {
        File::deleteDirectory($home);
    }
});
