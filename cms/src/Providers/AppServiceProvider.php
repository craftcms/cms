<?php

namespace CraftCms\Cms\Providers;

use Craft;
use craft\helpers\FileHelper;
use CraftCms\Aliases\Facades\Aliases;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\Middleware\CheckForUpdates;
use CraftCms\Cms\Http\Middleware\CheckRequirements;
use CraftCms\Cms\Http\Middleware\CheckSchemaVersion;
use CraftCms\Cms\Http\Middleware\ExtractNamespace;
use CraftCms\Cms\Http\Middleware\FlushProjectConfig;
use CraftCms\Cms\Http\Middleware\HandleActionRequest;
use CraftCms\Cms\Http\Middleware\RequireCpRequest;
use CraftCms\Cms\Http\Middleware\SendPoweredByHeader;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\User\Models\User;
use GuzzleHttp\Utils;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

/** @since 6.0.0 */
final class AppServiceProvider extends ServiceProvider
{
    private string $root = __DIR__.'/../..';

    public function register(): void
    {
        /**
         * Make sure we're using Craft's User model
         *
         * @var class-string<\Illuminate\Contracts\Auth\Authenticatable> $model
         */
        $model = Config::get('auth.providers.users.model');
        if (! class_exists($model) || ! is_a($model, User::class, true)) {
            Config::set('auth.providers.users.model', User::class);
        }

        /**
         * HandleActionRequest is special and needs to run
         * before any other middleware as it rewrites
         * which path needs to get used.
         */
        $kernel = $this->app->get(HttpKernel::class);
        $kernel->setGlobalMiddleware(array_merge([
            HandleActionRequest::class,
        ], $kernel->getGlobalMiddleware()));

        Authenticate::redirectUsing(fn () => app(GeneralConfig::class)->loginPath);

        Request::macro('isCpRequest', fn (): bool => $this->is(
            app(GeneralConfig::class)->cpTrigger, // /admin
            app(GeneralConfig::class)->cpTrigger.'/*' // /admin/foo
            // NOT /adminsarefun
        ));

        Request::macro('isActionRequest', fn (): bool => ! empty($this->actionSegments()));

        Request::macro('actionSegments', function (): array {
            $actionTrigger = app(GeneralConfig::class)->actionTrigger;

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

        Factory::macro('create', function (array $options = []) {
            $generalConfig = app(GeneralConfig::class);

            return $this->throw()
                ->withUserAgent('Craft/'.Craft::$app->getVersion().' '.Utils::defaultUserAgent())
                ->when(
                    Config::has('craft.guzzle'),
                    fn (PendingRequest $pendingRequest) => $pendingRequest->withOptions(Config::get('craft.guzzle')),
                )
                ->when(
                    $options,
                    fn (PendingRequest $pendingRequest) => $pendingRequest->withOptions($options),
                )
                ->when(
                    $generalConfig->httpProxy,
                    fn (PendingRequest $pendingRequest) => $pendingRequest->withOptions([
                        'proxy' => $generalConfig->httpProxy,
                    ]),
                );
        });
    }

    public function boot(): void
    {
        Aliases::set('@root', Env::get('CRAFT_ROOT_PATH', $this->app->basePath()));
        Aliases::set('@craftcms', FileHelper::normalizePath($this->root.'/../'));
        Aliases::set('@packageRoot', '@craftcms/cms');
        Aliases::set('@package', '@packageRoot/src');

        if ($webUrl = Env::get('CRAFT_WEB_URL')) {
            Aliases::set('@web', $webUrl);
        }

        AboutCommand::add('Craft CMS', fn () => [
            'Edition' => Craft::$app->edition->name,
            'Schema' => Craft::$app->schemaVersion,
            'Version' => Craft::$app->getVersion(),
        ]);

        $this->bootMiddleware();

        $this->loadRoutesFrom("{$this->root}/routes/routes.php");
    }

    protected function bootMiddleware(): void
    {
        $router = $this->app->make(Router::class);

        collect([
            CheckSchemaVersion::class,
            CheckForUpdates::class,
            ExtractNamespace::class,
            SendPoweredByHeader::class,
            FlushProjectConfig::class,
        ])->each(fn ($middleware) => $router->pushMiddlewareToGroup('craft', $middleware));

        collect([
            RequireCpRequest::class,
            CheckRequirements::class,
        ])->each(fn ($middleware) => $router->pushMiddlewareToGroup('craft.cp', $middleware));

        collect([
            'web',
        ])->each(fn ($middleware) => $router->pushMiddlewareToGroup('craft.web', $middleware));
    }
}
