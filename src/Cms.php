<?php

declare(strict_types=1);

namespace CraftCms\Cms;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDOException;
use Throwable;

readonly class Cms
{
    public const string NAME = 'Craft CMS';

    public const string VERSION = '6.0.0';

    public const string SCHEMA_VERSION = '6.0.0.0';

    public const string MIN_VERSION_REQUIRED = '5.9.0';

    public static function config(): GeneralConfig
    {
        return app(GeneralConfig::class) ?? GeneralConfig::create();
    }

    public static function systemName(): string
    {
        $name = Env::parse(ProjectConfig::get('system.name'));

        if ($name !== null) {
            return $name;
        }

        try {
            $name = Sites::getPrimarySite()->getName();
        } catch (SiteNotFoundException) {
            $name = null;
        }

        return $name ?: config('app.name', 'Craft');
    }

    public static function isInstalled(bool $strict = false): bool
    {
        if ($strict) {
            Context::forgetHidden('craft.isInstalled');
            Context::forgetHidden('craft.info');
        } elseif (Context::hasHidden('craft.isInstalled')) {
            return Context::getHidden('craft.isInstalled');
        }

        try {
            DB::connection()->getPdo();
        } catch (PDOException $e) {
            if (! app()->runningInConsole()) {
                Log::error('There was a problem connecting to the database: '.$e->getMessage(), [__METHOD__]);
                report($e);
            }

            Context::addHidden('craft.isInstalled', false);

            return false;
        }

        try {
            if ($strict) {
                $isInstalled = Info::query()
                    ->where('id', 1)
                    ->exists();

                Context::addHidden('craft.isInstalled', $isInstalled);

                return $isInstalled;
            }

            $isInstalled = ! empty(Info::fetch(true)->id);

            Context::addHidden('craft.isInstalled', $isInstalled);

            return $isInstalled;
        } catch (Throwable $e) {
            Log::error('There was a problem fetching the info row: '.$e->getMessage(), [__METHOD__]);
            Context::addHidden('craft.isInstalled', false);

            report($e);

            return false;
        }
    }

    public static function setIsInstalled(bool $isInstalled = true): void
    {
        Context::addHidden('craft.isInstalled', $isInstalled);
    }
}
