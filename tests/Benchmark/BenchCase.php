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
use Tally\ScratchDirectory;

/**
 * Boots the workbench that `scripts/benchmark-setup.sh` builds, the way `vendor/bin/testbench` does.
 *
 * Tally runs each case in its own process, so a request benchmark boots a fresh application, like a PHP-FPM worker
 * handling one request, and no memoized state carries over between cases.
 *
 * Tally also runs cases in parallel, so each case gets its own copy of the workbench database. Pages such as the
 * dashboard write to it on a user's first visit, which would otherwise lock the database for the other cases.
 */
abstract class BenchCase
{
    public function __construct(
        private readonly ScratchDirectory $scratch,
    ) {}

    /**
     * The isolated workbench that scripts/benchmark-setup.sh builds.
     */
    public static function basePath(): string
    {
        return getenv('CRAFT_BENCHMARK_PATH') ?: dirname(__DIR__, 2).'/.benchmarks/laravel';
    }

    /**
     * Returns this case's copy of the workbench database, copying it on first use.
     *
     * Tally runs a case natively and then under its CPU simulation, with the same scratch directory, so the counted run
     * sees what the native run wrote, as a returning user's request would.
     */
    protected function databasePath(): string
    {
        $path = $this->scratch->path().'/database.sqlite';

        if (! is_file($path)) {
            $workbenchDatabase = self::basePath().'/database/database.sqlite';

            if (! is_file($workbenchDatabase)) {
                throw new RuntimeException('No benchmark workbench at '.self::basePath().'. Run scripts/benchmark-setup.sh first.');
            }

            copy($workbenchDatabase, $path);
        }

        return $path;
    }

    protected function createApplication(): Application
    {
        $database = $this->databasePath();
        putenv("DB_DATABASE=$database");
        $_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = $database;

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
        $request = Request::create('/'.Cms::config()->routing->cpTrigger.'/'.ltrim($path, '/'));
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
        $guard = Cms::config()->security->getAuthGuard();
        $auth = $app->make(AuthFactory::class);
        $auth->guard($guard)->setUser($user);
        $auth->shouldUse($guard);
    }

    /**
     * Returns a value straight from the case's database, without booting the application.
     */
    protected function query(string $sql): mixed
    {
        $pdo = new PDO('sqlite:'.$this->databasePath());

        return $pdo->query($sql)->fetchColumn();
    }
}
