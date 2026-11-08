<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console;

use CraftCms\Cms\Support\Env;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Http\Request;

/**
 * Returns terminal commands as they should be run for the current environment.
 *
 * Commands use the local development environment’s wrappers when Craft runs in one:
 *
 * - [DDEV](https://ddev.com): `ddev craft` and `ddev composer`.
 * - [Laravel Valet](https://laravel.com/docs/valet), for sites isolated to a PHP version with `valet isolate`:
 *   `valet php craft` and `valet composer`, which run the site’s PHP version. Other Valet sites use the PHP version
 *   that’s on the `PATH`, so they get the plain commands.
 *
 * Otherwise they use `php craft` and `composer`.
 *
 * @since 6.0.0
 */
#[Singleton]
class TerminalCommands
{
    /**
     * Returns the local development environment Craft is running in, if any: `ddev` or `valet`.
     *
     * Valet is only returned for sites isolated to a PHP version.
     */
    public function environment(): ?string
    {
        return match (true) {
            Env::parseBoolean(Env::get('IS_DDEV_PROJECT')) === true => 'ddev',
            $this->isIsolatedValetSite() => 'valet',
            default => null,
        };
    }

    /**
     * Returns a Craft console command.
     *
     * ```php
     * $terminalCommands->craft('passport:keys'); // php craft passport:keys, or ddev craft passport:keys on DDEV
     * ```
     *
     * @param  string  $command  The command and its arguments, without the `craft` executable
     */
    public function craft(string $command): string
    {
        return "{$this->craftExecutable()} $command";
    }

    /**
     * Returns a Composer command.
     *
     * ```php
     * $terminalCommands->composer('install'); // composer install, or ddev composer install on DDEV
     * ```
     *
     * @param  string  $command  The command and its arguments, without the `composer` executable
     */
    public function composer(string $command): string
    {
        return "{$this->composerExecutable()} $command";
    }

    /**
     * Returns how the `craft` executable is run, such as `php craft`.
     */
    public function craftExecutable(): string
    {
        return match ($this->environment()) {
            'ddev' => 'ddev craft',
            'valet' => 'valet php craft',
            default => 'php craft',
        };
    }

    /**
     * Returns how Composer is run, such as `composer`.
     */
    public function composerExecutable(): string
    {
        return match ($this->environment()) {
            'ddev' => 'ddev composer',
            'valet' => 'valet composer',
            default => 'composer',
        };
    }

    /**
     * Returns whether the current web request is for a Valet site isolated to a PHP version.
     *
     * Valet’s front controller defines `VALET_HOME_PATH`, and `valet isolate` starts the site’s Nginx config with an
     * `ISOLATED_PHP_VERSION` comment, which is how `valet isolated` lists isolated sites.
     */
    private function isIsolatedValetSite(): bool
    {
        if (! defined('VALET_HOME_PATH') || ! app()->bound('request')) {
            return false;
        }

        $host = app(Request::class)->getHost();

        if ($host === '' || str_contains($host, '/') || str_contains($host, '\\')) {
            return false;
        }

        $config = @fopen(constant('VALET_HOME_PATH')."/Nginx/$host", 'rb');

        if ($config === false) {
            return false;
        }

        try {
            return str_starts_with((string) fgets($config), '# ISOLATED_PHP_VERSION=');
        } finally {
            fclose($config);
        }
    }
}
