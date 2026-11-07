<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Benchmark;

use CraftCms\Cms\Cms;
use CraftCms\Cms\User\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Orchestra\Testbench\Foundation\Application as Testbench;
use Orchestra\Testbench\Foundation\Config;
use Orchestra\Testbench\Workbench\Workbench;
use PDO;
use RuntimeException;

/**
 * Boots the workbench that `scripts/benchmark-setup.sh` builds, the way `vendor/bin/testbench` does.
 *
 * phpbench runs each iteration in its own process, so a request benchmark boots a fresh application each time, like
 * a PHP-FPM worker handling one request, and no memoized state carries over between iterations.
 */
abstract class BenchCase
{
    /**
     * The isolated workbench that scripts/benchmark-setup.sh builds.
     */
    public static function basePath(): string
    {
        return getenv('CRAFT_BENCHMARK_PATH') ?: dirname(__DIR__, 2).'/.phpbench/laravel';
    }

    protected function createApplication(): Application
    {
        $config = Config::loadFromYaml(dirname(__DIR__, 2));

        $app = Testbench::create(
            basePath: self::basePath(),
            resolvingCallback: function (Application $app) use ($config): void {
                Workbench::startWithProviders($app, $config);
                Workbench::discoverRoutes($app, $config);
            },
            options: ['load_environment_variables' => true, 'extra' => $config->getExtraAttributes()],
        );

        // The front end may not be built, and rendering its tags isn't what's being measured
        $app->instance(Vite::class, new class extends Vite
        {
            public function __invoke($entrypoints, $buildDirectory = null): HtmlString
            {
                return new HtmlString('');
            }

            /** @param array<int, mixed> $parameters */
            public function __call($method, $parameters): string
            {
                return '';
            }

            public function asset($asset, $buildDirectory = null): string
            {
                return '';
            }

            public function content($asset, $buildDirectory = null): string
            {
                return '';
            }

            /** @return array<string, mixed> */
            public function preloadedAssets(): array
            {
                return [];
            }
        });

        return $app;
    }

    /**
     * Handles a control panel request as the given user, failing unless it succeeds.
     */
    protected function request(string $path, string $username): void
    {
        $app = $this->createApplication();
        $user = User::query()->where('username', $username)->firstOrFail();
        $this->actingAs($app, $user);

        $kernel = $app->make(Kernel::class);
        $request = Request::create('/'.Cms::config()->cpTrigger.'/'.ltrim($path, '/'));
        $response = $kernel->handle($request);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(sprintf('%s returned %d for %s.', $path, $response->getStatusCode(), $username));
        }

        $kernel->terminate($request, $response);
    }

    /**
     * Logs the user in for the rest of the process.
     */
    protected function actingAs(Application $app, User $user): void
    {
        $guard = Cms::config()->getAuthGuard();
        $auth = $app->make(AuthFactory::class);
        $auth->guard($guard)->setUser($user);
        $auth->shouldUse($guard);
    }

    /**
     * Returns a value straight from the benchmark database, without booting the application.
     */
    protected static function query(string $sql): mixed
    {
        $pdo = new PDO('sqlite:'.self::basePath().'/database/database.sqlite');

        return $pdo->query($sql)->fetchColumn();
    }
}
