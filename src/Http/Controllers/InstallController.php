<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Database\ConnectionConfig;
use CraftCms\Cms\Database\LaravelMigrations;
use CraftCms\Cms\Database\Migrations\Install;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Site\Concerns\SiteDefaults;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Validation\Rules\EnvValueRule;
use CraftCms\Cms\Validation\Rules\LanguageRule;
use CraftCms\Cms\Validation\Rules\TimezoneRule;
use Illuminate\Database\SQLiteDatabaseDoesNotExistException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use PDOException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * The InstallController class is a controller that directs all installation-related tasks
 * such as creating the database schema and default content for a Craft installation.
 *
 * Note that all actions in the controller are open and do not require an
 * authenticated Craft session to execute.
 */
readonly class InstallController
{
    use SiteDefaults;

    private const array DbDrivers = ['mysql', 'mariadb', 'pgsql', 'sqlite'];

    private const string DefaultSqliteDatabase = 'database.sqlite';

    public function __construct()
    {
        if (! app()->hasDebugModeEnabled()) {
            abort(503, 'Debug mode must be enabled to install Craft.');
        }

        // Return a 404 if Craft is already installed
        if (Cms::isInstalled()) {
            abort(404, 'Craft is already installed');
        }
    }

    public function index(GeneralConfig $generalConfig): \Inertia\Response
    {
        try {
            DB::reconnect()->getPdo();
            $showDbScreen = false;
        } catch (PDOException|SQLiteDatabaseDoesNotExistException $e) {
            if ($this->canControlDbConfig()) {
                $showDbScreen = true;
            } else {
                throw $e;
            }
        }

        // Guess the site name based on the server name
        $defaultSystemName = $this->defaultSiteName();
        $defaultSiteUrl = $this->defaultSiteUrl();
        $defaultSiteLanguage = $this->defaultSiteLanguage();
        $dbConfig = DB::getConfig();
        $postCpLoginRedirect = Cms::config()->postCpLoginRedirect;

        return Inertia::render('install/Install', [
            'showDbScreen' => $showDbScreen,
            'postCpLoginRedirect' => $postCpLoginRedirect,
            'licenseHtml' => Inertia::defer(function () {
                $licensePath = Path::package('LICENSE.md');
                $license = file_get_contents($licensePath);

                return Str::markdown($license);
            }),
            'localeOptions' => Inertia::defer(fn () => I18N::getAllLocales()->map(fn ($locale) => [
                'id' => $locale->id,
                'value' => $locale->id,
                'label' => $locale->getDisplayName(app()->getLocale()),
                'selected' => $locale->id === $defaultSiteLanguage,
            ])),
            'timezone' => Inertia::defer(function () {
                $timezoneOptions = SelectOptions::getTimeZoneOptions();

                return array_merge($timezoneOptions, SelectOptions::getEnvOptions(array_column($timezoneOptions, 'value')));
            }),
            'baseUrlTextExpanderTriggers' => SelectOptions::getEnvTextExpanderTriggers(fn ($value) => Str::isUrl($value)),
            'defaultSystemName' => $defaultSystemName,
            'defaultSiteUrl' => $defaultSiteUrl,
            'defaultSiteLanguage' => $defaultSiteLanguage,
            'useEmailAsUsername' => $generalConfig->useEmailAsUsername,
            'dbConfig' => [
                'driver' => $this->dbDriver($dbConfig['driver'] ?? null),
            ],
            'dbDefaults' => $this->dbDefaults(),
        ]);
    }

    public function validateDb(Request $request): Response
    {
        $data = $this->validateDbData($request->input());

        $errors = [];

        try {
            if ($data['driver'] === 'sqlite') {
                ConnectionConfig::ensureSqliteDatabaseFile($data['database']);
            }

            DB::build($data)->select('SELECT 1');
        } catch (PDOException $e) {
            $attr = match ($e->getCode()) {
                1045 => 'user',
                1049 => 'database',
                2002 => 'server',
                default => '*',
            };

            $errors[$attr][] = 'PDO exception: '.$e->getMessage();
        } catch (SQLiteDatabaseDoesNotExistException $e) {
            $errors['database'][] = 'PDO exception: '.$e->getMessage();
        } catch (RuntimeException $e) {
            $errors['database'][] = $e->getMessage();
        }

        if (empty($errors)) {
            return new JsonResponse;
        }

        return new JsonResponse([
            'message' => 'Could not connect to the database.',
            'errors' => $errors,
        ], 422);
    }

    public function validateAccount(Request $request, GeneralConfig $generalConfig): Response
    {
        $request->validate([
            'email' => ['required', 'email:strict'],
            'username' => [Rule::excludeIf($generalConfig->useEmailAsUsername), 'required', 'string', 'max:255', 'alpha_num'],
            'password' => ['required', Password::default()],
        ]);

        return new JsonResponse;
    }

    public function validateSite(Request $request): Response
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'baseUrl' => [new EnvValueRule(['nullable', 'url', 'max:255'])],
            'language' => ['required', 'string', 'max:255', new LanguageRule(onlySiteLanguages: false)],
            'timezone' => [new EnvValueRule([new TimezoneRule])],
        ]);

        return new JsonResponse;
    }

    public function install(Request $request, Migrator $migrator, LaravelMigrations $laravelMigrations): Response
    {
        $path = app()->environmentFilePath();

        // Should we set the new DB config values?
        if ($request->has('db.driver')) {
            $data = $this->validateDbData($request->input('db'));

            if ($data['driver'] === 'sqlite') {
                try {
                    ConnectionConfig::ensureSqliteDatabaseFile($data['database']);
                } catch (RuntimeException $e) {
                    return new JsonResponse([
                        'message' => $e->getMessage(),
                    ], 400);
                }
            }

            // Set and save the new DB config values
            $this->writeDbEnv($data, $path);

            // Update the db component based on new values
            Config::set('database.default', $data['driver']);
            Config::set("database.connections.{$data['driver']}", array_merge(
                Config::get("database.connections.{$data['driver']}") ?? [],
                $data,
            ));

            DB::purge($data['driver']);
            DB::setDefaultConnection($data['driver']);
            DB::reconnect($data['driver']);

            if ($data['driver'] === 'sqlite') {
                ConnectionConfig::useDefaultConnectionForBulkOps(DB::connection());
            } else {
                Config::set('database.connections.db2', array_merge(DB::connection()->getConfig(), [
                    'name' => 'db2',
                ]));
                DB::purge('db2');
                DB::reconnect('db2');
            }
        }

        $email = $request->input('account.email');
        $username = $request->input('account.username', $email);
        $siteUrl = $request->input('site.baseUrl');

        if (! in_array($siteUrl[0], ['@', '$']) && ! app()->isEphemeral()) {
            try {
                Env::writeVariable('APP_URL', $siteUrl, $path, overwrite: true);
                $siteUrl = '$APP_URL';
            } catch (Throwable) {
                // that's fine, we'll just store the entered URL
            }
        }

        $site = new Site([
            'name' => $request->input('site.name'),
            'handle' => 'default',
            'language' => $request->input('site.language'),
            'baseUrl' => $siteUrl,
            'hasUrls' => true,
        ]);

        $migration = new Install(
            username: $username,
            password: $request->input('account.password'),
            email: $email,
            site: $site,
            timezone: $request->input('site.timezone'),
        )->silent();

        // Run the install migration
        try {
            $migrator->track('craft')->runMigration($migration, 'up');
            $migrator->getRepository()->log('install/Install', 1);
        } catch (Throwable $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 400);
        }

        // Mark all existing migrations as applied
        foreach ($migrator->getPendingMigrations() as $file) {
            $migrator->getRepository()->log($migrator->getMigrationName($file), 1);
        }

        $redirect = Cms::config()->postCpLoginRedirect;

        return new JsonResponse(['redirect' => Url::cpUrl($redirect)]);
    }

    private function canControlDbConfig(): bool
    {
        // If this is ephemeral storage, then we can't be writing to a .env file
        if (app()->isEphemeral()) {
            return false;
        }

        // If the .env file doesn't exist, we definitely can't do anything about it
        if (! file_exists(app()->environmentFilePath())) {
            return false;
        }

        // Nothing else to worry about, thanks to `CRAFT_X` environment variable overrides
        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, bool|int|string|null>
     */
    public function validateDbData(array $data): array
    {
        $data = Validator::validate($data, [
            'driver' => ['required', 'string', Rule::in('mysql', 'mariadb', 'pgsql', 'sqlite')],
            'host' => ['nullable', 'string'],
            'database' => ['nullable', 'string'],
            'port' => ['nullable', 'integer'],
            'username' => ['nullable', 'string'],
            'password' => ['nullable', 'string'],
            'prefix' => ['nullable', 'string', 'max:5'],
            'schema' => ['nullable', 'string'],
        ]);

        $defaults = $this->dbDefaultsForDriver($data['driver']);
        $data['database'] = ($data['database'] ?? null) ?: $defaults['database'];

        if ($data['driver'] === 'sqlite') {
            return ConnectionConfig::normalize($data);
        }

        $data['host'] ??= $defaults['host'];
        $data['port'] ??= (int) $defaults['port'];
        $data['username'] ??= $defaults['username'];
        $data['password'] ??= '';
        $data['prefix'] ??= $defaults['prefix'];

        return collect($data)->mapWithKeys(fn (mixed $value, string $key) => [$key => $value])->all();
    }

    private function dbDriver(mixed $driver): string
    {
        if (is_string($driver) && in_array($driver, self::DbDrivers, true)) {
            return $driver;
        }

        return 'sqlite';
    }

    /** @return array<string, array<string, string>> */
    private function dbDefaults(): array
    {
        return collect(self::DbDrivers)
            ->mapWithKeys(fn (string $driver) => [$driver => $this->dbDefaultsForDriver($driver)])
            ->all();
    }

    /** @return array<string, string> */
    private function dbDefaultsForDriver(string $driver): array
    {
        if ($driver === 'sqlite') {
            return [
                'database' => self::DefaultSqliteDatabase,
                'prefix' => '',
            ];
        }

        $defaultPort = in_array($driver, ['mysql', 'mariadb'], true) ? 3306 : 5432;

        return [
            'host' => '127.0.0.1',
            'port' => (string) $defaultPort,
            'database' => 'laravel',
            'username' => 'root',
            'prefix' => '',
        ];
    }

    /** @param array<string, bool|int|string|null> $data */
    private function writeDbEnv(array $data, string $path): void
    {
        Env::writeVariable('DB_CONNECTION', $data['driver'], $path, overwrite: true);
        Env::writeVariable('DB_DATABASE', $data['database'], $path, overwrite: true);

        if ($data['driver'] === 'sqlite') {
            Env::writeVariable('DB_FOREIGN_KEYS', 'true', $path, overwrite: true);

            foreach (['DB_HOST', 'DB_PORT', 'DB_USERNAME', 'DB_PASSWORD', 'DB_SCHEMA'] as $variable) {
                Env::removeVariable($variable, $path);
            }
        } else {
            Env::writeVariable('DB_HOST', $data['host'], $path, overwrite: true);
            Env::writeVariable('DB_PORT', (string) $data['port'], $path, overwrite: true);
            Env::writeVariable('DB_USERNAME', $data['username'], $path, overwrite: true);
            Env::writeVariable('DB_PASSWORD', $data['password'], $path, overwrite: true);
            isset($data['schema']) && Env::writeVariable('DB_SCHEMA', $data['schema'], $path, overwrite: true);
        }

        isset($data['prefix']) && Env::writeVariable('DB_TABLE_PREFIX', $data['prefix'], $path, overwrite: true);
    }
}
