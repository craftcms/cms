<?php

declare(strict_types=1);

namespace CraftCms\Cms;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Updates;
use CraftCms\Cms\Support\Facades\Users;
use Illuminate\Database\SQLiteDatabaseDoesNotExistException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use IntlDateFormatter;
use IntlException;
use PDOException;
use Throwable;

readonly class Cms
{
    public const string NAME = 'Craft CMS';

    public const string VERSION = '6.0.0-alpha.17';

    public const string SCHEMA_VERSION = '6.0.0.9';

    public const string MIN_VERSION_REQUIRED = '5.9.0';

    public static function name(): string
    {
        return self::NAME;
    }

    public static function config(): GeneralConfig
    {
        return app(GeneralConfig::class) ?? GeneralConfig::create();
    }

    public static function version(): string
    {
        return self::VERSION;
    }

    public static function schemaVersion(): string
    {
        return self::SCHEMA_VERSION;
    }

    public static function minVersionRequired(): string
    {
        return self::MIN_VERSION_REQUIRED;
    }

    public static function timezone(): string
    {
        $timezone = null;

        // If the user is logged in *and* has a preferred time zone, use that
        // (don't actually try to fetch the user, as plugins haven't been loaded yet)
        if (request()->isCpRequest() && Auth::hasUser() && $id = Auth::id()) {
            $timezone = Users::getUserPreference($id, 'timeZone');
        }

        if (! $timezone) {
            return self::systemTimezone();
        }

        return self::validatedTimezone($timezone);
    }

    public static function systemTimezone(): string
    {
        $timezone = Cms::config()->timezone ?? ProjectConfig::get('system.timeZone') ?? config('app.timezone', 'UTC');

        return self::validatedTimezone($timezone);
    }

    public static function setDefaultTimezone(?string $timezone = null): void
    {
        $timezone = Cms::config()->timezone ?? $timezone ?? ProjectConfig::get('system.timeZone') ?? config('app.timezone', 'UTC');

        date_default_timezone_set(self::validatedTimezone($timezone));
    }

    private static function validatedTimezone(?string $timezone): string
    {
        $timezone = Env::parse($timezone);

        if (! $timezone) {
            return 'UTC';
        }

        if ($timezone !== 'UTC') {
            // Make sure that ICU supports this timezone
            try {
                $formatter = new IntlDateFormatter(app()->getLocale(), IntlDateFormatter::NONE, IntlDateFormatter::NONE);
                if (! $formatter->setTimeZone($timezone)) {
                    $timezone = 'UTC';
                }
            } catch (IntlException) {
                Log::warning("Time zone “{$timezone}” does not appear to be supported by ICU: ".intl_get_error_message());
                $timezone = 'UTC';
            }
        }

        return $timezone;
    }

    public static function targetLanguage(?Request $request = null): string
    {
        $request ??= request();

        if (! self::isInstalled()) {
            return self::fallbackLanguage($request);
        }

        if (Updates::isCraftUpdatePending()) {
            return self::fallbackLanguage($request);
        }

        if (! $request->isCpRequest()) {
            return Sites::getCurrentSite()->getLanguage();
        }

        $user = currentUser();

        if (
            ($id = $user?->getAuthIdentifier()) &&
            ($language = Users::getUserPreference($id, 'language')) !== null &&
            I18N::validateAppLocaleId($language)
        ) {
            return $language;
        }

        return self::config()->defaultCpLanguage ?? self::fallbackLanguage($request);
    }

    private static function fallbackLanguage(Request $request): string
    {
        return I18N::normalizeLanguage($request->getPreferredLanguage(I18N::getAppLocaleIds()->all()));
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

    public static function systemUid(): ?string
    {
        return Info::fetch()->uid;
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
        } catch (PDOException|SQLiteDatabaseDoesNotExistException $e) {
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
