<?php

declare(strict_types=1);

namespace CraftCms\Cms\Providers;

use Craft;
use craft\helpers\FileHelper;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\GarbageCollection\GarbageCollection;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Updates;
use GuzzleHttp\Utils;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use IntlDateFormatter;
use IntlException;
use Override;
use ReflectionClass;
use RuntimeException;

use function CraftCms\Cms\t;

final class AppServiceProvider extends ServiceProvider
{
    private string $root = __DIR__.'/../..';

    #[Override]
    public function register(): void
    {
        $this->registerMacros();

        Authenticate::redirectUsing(function () {
            if (\request()->isCpRequest()) {
                return Cms::config()->cpTrigger.'/login';
            }

            return Cms::config()->loginPath;
        });
    }

    public function boot(): void
    {
        Event::listen(LocaleUpdated::class, function (LocaleUpdated $event) {
            setlocale(
                LC_COLLATE,
                str_replace('-', '_', $event->locale), // target language
                'C.UTF-8',  // libc >= 2.13
                'C.utf8' // different spelling
            );
        });

        AboutCommand::add('Craft CMS', fn () => [
            'Edition' => Edition::get()->name,
            'Schema' => Cms::SCHEMA_VERSION,
            'Version' => Cms::VERSION,
        ]);

        $this->setTimezone();
        $this->setNamespace();
        $this->bootAliases();

        $this->app->booted(function () {
            if (Info::isInstalled() && ! Updates::isCraftUpdatePending()) {
                // Possibly run garbage collection
                app(GarbageCollection::class)->run();
            }
        });

        $this->publishes([
            "{$this->root}/resources/build/" => public_path('vendor/craft'),
        ], ['craftcms', 'craftcms-assets']);

        // @TODO Remove when rebrand assets are refactored
        config([
            'filesystems.disks.rebrand' => [
                'driver' => 'local',
                'root' => storage_path('rebrand'),
                'url' => implode('/', [
                    config('app.url'),
                    Cms::config()->cpTrigger,
                    'rebrand',
                ]),
                'visibility' => 'public',
                'throw' => false,
                'report' => false,
            ],
        ]);
    }

    private function registerMacros(): void
    {
        Application::macro('isLive', function (): bool {
            if (is_bool($live = Cms::config()->isSystemLive)) {
                return $live;
            }

            return Env::parseBoolean(app(ProjectConfig::class)->get('system.live')) ?? false;
        });

        Application::macro(
            'getTimezone',
            fn (): string => $this->make(ConfigRepository::class)->get('app.timezone') ?? date_default_timezone_get(),
        );

        // Register Collection::one() as an alias of first()
        Collection::macro('one', fn () => $this->first(...func_get_args()));

        Collection::macro(
            'sentence',
            fn (?string $glue = null): string => $this->join($glue ?? ', ', sprintf(',%s', t(' and '))),
        );

        // Register Collection::set() as an alias of put() - with support for bulk-setting values
        Collection::macro('set', function (mixed $values) {
            if (! is_array($values)) {
                return $this->put(...func_get_args());
            }

            foreach ($values as $key => $value) {
                $this->put($key, $value);
            }

            return $this;
        });

        Request::macro('isCpRequest', fn (): bool => $this->is(
            Cms::config()->cpTrigger, // /admin
            Cms::config()->cpTrigger.'/*' // /admin/foo
            // NOT /adminsarefun
        ));

        Request::macro('isSiteRequest', fn (): bool => ! $this->isCpRequest());

        Request::macro('isActionRequest', fn (): bool => ! empty($this->actionSegments()));

        Request::macro('isPreview', function () {
            $previewParamValue = $this->input('x-craft-preview') ?? $this->input('x-craft-live-preview');

            if (! $previewParamValue) {
                return false;
            }

            if (! Craft::$app->getSecurity()->validateData($previewParamValue)) {
                return false;
            }

            if (! Context::hasHidden(HandleTokenRequest::TOKEN_KEY)) {
                return false;
            }

            return true;
        });

        Request::macro('actionSegments', function (): array {
            $actionTrigger = Cms::config()->actionTrigger;

            $segmentIndex = $this->isCpRequest() ? 2 : 1;

            if ($this->segment($segmentIndex) === $actionTrigger && count($this->segments()) > $segmentIndex) {
                return array_slice($this->segments(), $segmentIndex);
            }

            if ($actionParam = $this->get('action')) {
                if (! is_string($actionParam)) {
                    abort(400, 'Invalid action param');
                }

                return array_values(array_filter(explode('/', $actionParam)));
            }

            return [];
        });

        Request::macro('actionSegmentsToRoute', function (?array $actionSegments = null): string {
            $actionSegments ??= $this->actionSegments();

            return implode('/', array_filter([
                '',
                $this->isCpRequest() ? Cms::config()->cpTrigger : null,
                Cms::config()->actionTrigger,
                ...$actionSegments,
            ], fn ($value) => ! is_null($value)));
        });

        Request::macro('duplicateWithUri', fn (string $newUri, ?array $query = null, array $server = []): Request => $this->duplicate(
            query: $query ?? $this->query->all(),
            server: array_merge($this->server->all(), $server, [
                'REQUEST_URI' => $newUri,
            ]),
        ));

        Request::macro('getSigned', function (string $key, mixed $default = null): mixed {
            $value = $this->get($key);

            if (is_null($value)) {
                return $default;
            }

            $value = Craft::$app->getSecurity()->validateData($value);

            abort_if($value === false, 400, 'Request contained an invalid body param');

            return $value;
        });

        Response::macro('setNoCacheHeaders', function (bool $replace = true) {
            $this->header('Expires', '0', $replace);
            $this->header('Pragma', 'no-cache', $replace);
            $this->header('Cache-Control', 'no-cache, no-store, must-revalidate', $replace);

            return $this;
        });

        Factory::macro('create', fn (array $options = []) => $this->throw()
            ->withUserAgent('Craft/'.Cms::VERSION.' '.Utils::defaultUserAgent())
            ->when(
                Config::has('craft.guzzle'),
                fn (PendingRequest $pendingRequest) => $pendingRequest->withOptions(Config::get('craft.guzzle')),
            )
            ->when(
                $options,
                fn (PendingRequest $pendingRequest) => $pendingRequest->withOptions($options),
            )
            ->when(
                Cms::config()->httpProxy,
                fn (PendingRequest $pendingRequest) => $pendingRequest->withOptions([
                    'proxy' => Cms::config()->httpProxy,
                ]),
            ));
    }

    private function setTimezone(): void
    {
        $timezone = app(ProjectConfig::class)->get('system.timeZone')
            ?? $this->app->make(ConfigRepository::class)->get('app.timezone')
            ?? 'UTC';

        $timezone = Env::parse($timezone);

        if ($timezone !== 'UTC') {
            // Make sure that ICU supports this timezone
            try {
                $formatter = new IntlDateFormatter($this->app->getLocale(), IntlDateFormatter::NONE, IntlDateFormatter::NONE);
                if (! $formatter->setTimeZone($timezone)) {
                    $timezone = 'UTC';
                }
            } catch (IntlException) {
                Log::warning("Time zone “{$timezone}” does not appear to be supported by ICU: ".intl_get_error_message());
                $timezone = 'UTC';
            }
        }

        $this->app->make(ConfigRepository::class)->set('app.timezone', $timezone);
        date_default_timezone_set($timezone);
    }

    private function setNamespace(): void
    {
        /**
         * In a Craft 5 upgraded project, the namespace won't be
         * detected automatically, we set it to "App" here.
         */
        try {
            $this->app->getNamespace();
        } catch (RuntimeException) {
            $reflectionClass = new ReflectionClass($this->app);
            $reflectionProperty = $reflectionClass->getProperty('namespace');
            $reflectionProperty->setValue($this->app, 'App');
        }
    }

    private function bootAliases(): void
    {
        Aliases::set('@root', Env::get('CRAFT_ROOT_PATH', $this->app->basePath()));
        Aliases::set('@craftcms', FileHelper::normalizePath($this->root));
        Aliases::set('@package', '@craftcms/src');
        Aliases::set('@resources', "{$this->root}/resources");

        if ($webUrl = Env::get('CRAFT_WEB_URL')) {
            Aliases::set('@web', $webUrl);
        }
    }
}
