<?php

declare(strict_types=1);

namespace CraftCms\Cms\Providers;

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\GarbageCollection\GarbageCollection;
use CraftCms\Cms\Http\Mixins\RequestMixin;
use CraftCms\Cms\Http\Mixins\SessionMixin;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\CmsAssets;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Updates;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Update\Data\Update as UpdateData;
use CraftCms\Cms\Update\Data\UpdateRelease;
use CraftCms\Cms\Update\Data\Updates as UpdatesData;
use CraftCms\Cms\User\Validation\Rules\UserPasswordRule;
use GuzzleHttp\Utils;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Override;
use ReflectionClass;
use RuntimeException;
use stdClass;

use function CraftCms\Cms\action_url;
use function CraftCms\Cms\t;

class AppServiceProvider extends ServiceProvider
{
    public static int $minPasswordLength = UserPasswordRule::MIN_PASSWORD_LENGTH;

    public static int $maxPasswordLength = UserPasswordRule::MAX_PASSWORD_LENGTH;

    private string $root = __DIR__.'/../..';

    #[Override]
    public function register(): void
    {
        $this->registerMacros();
        $this->registerSerializableClasses();
        $this->registerThrottleExceptionHandler();
    }

    public function boot(): void
    {
        AuthenticationException::redirectUsing(function () {
            $loginPath = Cms::config()->getLoginPath();

            if (! request()->isCpRequest() && $loginPath !== false) {
                return Url::siteUrl($loginPath);
            }

            return Url::cpUrl(CpAuthPath::Login->value);
        });

        Event::listen(LocaleUpdated::class, function (LocaleUpdated $event) {
            setlocale(
                LC_COLLATE,
                str_replace('-', '_', $event->locale), // target language
                'C.UTF-8',  // libc >= 2.13
                'C.utf8', // different spelling
            );
        });

        AboutCommand::add('Craft CMS', fn () => [
            'Edition' => Edition::get()->name,
            'Schema' => Cms::SCHEMA_VERSION,
            'Version' => Cms::VERSION,
        ]);

        $this->setNamespace();
        $this->bootAliases();
        Cms::setDefaultTimezone();

        $this->app->booted(function () {
            /**
             * Users can register their own password rules,
             * but we provide a sensible default.
             */
            if (! Password::$defaultCallback) {
                Password::defaults(fn () => Password::min(self::$minPasswordLength)->max(self::$maxPasswordLength));
            }

            if (Cms::isInstalled() && ! Updates::isCraftUpdatePending()) {
                app(GarbageCollection::class)->queue();
            }
        });

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            CmsAssets::resourcesPath('build') => public_path('vendor/craft/build'),
            CmsAssets::resourcesPath('legacy') => public_path('vendor/craft/legacy'),
        ], ['craftcms', 'craftcms-assets']);

        foreach (glob(CmsAssets::resourcesPath('icons/*'), GLOB_ONLYDIR) ?: [] as $path) {
            $this->publishes([
                $path => public_path('vendor/craft/icons/'.basename($path)),
            ], ['craftcms', 'craftcms-assets', 'craftcms-icons']);
        }
    }

    private function registerMacros(): void
    {
        Application::macro('isLive', function (): bool {
            if (is_bool($live = Cms::config()->isSystemLive)) {
                return $live;
            }

            return Env::parseBoolean(app(ProjectConfig::class)->get('system.live')) ?? false;
        });

        Application::macro('isEphemeral', fn (): bool => Env::parseBoolean('$CRAFT_EPHEMERAL') === true);

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

        Request::mixin(new RequestMixin);
        SessionStore::mixin(new SessionMixin);

        Response::macro('setNoCacheHeaders', function (bool $replace = true) {
            $this->header('Expires', '0', $replace);
            $this->header('Pragma', 'no-cache', $replace);
            $this->header('Cache-Control', 'no-cache, no-store, must-revalidate', $replace);

            return $this;
        });

        UrlGenerator::macro('defaultReturnUrl', function (): string {
            if (request()->isCpRequest() && Gate::check('accessCp')) {
                return Url::cpUrl(Cms::config()->getPostCpLoginRedirect());
            }

            return Url::siteUrl(Cms::config()->getPostLoginRedirect());
        });

        UrlGenerator::macro('returnUrl', function (?string $defaultUrl = null): string {
            $defaultUrl ??= Auth::guest()
                ? action_url('users/redirect')
                : $this->defaultReturnUrl();

            $url = Redirect::getIntendedUrl() ?? $defaultUrl;

            // Strip out any tags that may have gotten in there by accident
            // i.e. if there was a {siteUrl} tag in the Site URL setting, but no matching environment variable,
            // so they ended up on something like http://example.com/%7BsiteUrl%7D/some/path
            return str_replace(['{', '}'], '', $url);
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

    private function registerSerializableClasses(): void
    {
        $existing = $this->app->make(Repository::class)->get('cache.serializable_classes');

        if ($existing === null || $existing === true) {
            return;
        }

        $existing = is_array($existing) ? $existing : [];

        $this->app->make(Repository::class)->set('cache.serializable_classes', array_merge($existing, [
            Carbon::class,
            Collection::class,
            ElementCollection::class,
            stdClass::class,
            UpdatesData::class,
            UpdateData::class,
            UpdateRelease::class,
        ]));
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
        Aliases::set('@craftcms', File::normalizePath($this->root));
        Aliases::set('@cmsAssets', CmsAssets::packagePath());
        Aliases::set('@package', '@craftcms/src');
        Aliases::set('@resources', "{$this->root}/resources");
        Aliases::set('@vendor', '@root/vendor');
        Aliases::set('@storage', $this->app->storagePath());
        Aliases::set('@runtime', '@storage/runtime');

        if (Aliases::get('@templates', false) === false) {
            Aliases::set('@templates', is_dir($this->app->resourcePath('views'))
                ? $this->app->resourcePath('views')
                : $this->app->basePath('templates'));
        }

    }

    private function registerThrottleExceptionHandler(): void
    {
        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            $handler->renderable(function (ThrottleRequestsException $e, $request) {
                if ($request->inertia()) {
                    return back()->with('error', t('Too many requests. Please wait a moment before trying again.'));
                }
            });
        });
    }
}
