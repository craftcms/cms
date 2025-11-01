<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use Craft;
use craft\helpers\App;
use craft\web\assets\installer\InstallerAsset;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Migrations\Install;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Shared\Rules\LanguageRule;
use CraftCms\Cms\Site\Concerns\SiteDefaults;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use PDOException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * The InstallController class is a controller that directs all installation-related tasks
 * such as creating the database schema and default content for a Craft installation.
 *
 * Note that all actions in the controller are open and do not require an
 * authenticated Craft session to execute.
 */
final readonly class InstallController
{
    use SiteDefaults;

    public function __construct()
    {
        // Return a 404 if Craft is already installed
        abort_if(! app()->hasDebugModeEnabled() && Craft::$app->getIsInstalled(), 404, 'Craft is already installed');
    }

    public function index(): Response
    {
        try {
            DB::reconnect()->getPdo();
            $showDbScreen = false;
        } catch (PDOException $e) {
            if ($this->canControlDbConfig()) {
                $showDbScreen = true;
            } else {
                throw $e;
            }
        }

        Craft::$app->getView()->registerAssetBundle(InstallerAsset::class);

        // Grab the license text
        $licensePath = Aliases::get('@craftcms/LICENSE.md');
        $license = file_get_contents($licensePath);

        // Guess the site name based on the server name
        $defaultSystemName = $this->defaultSiteName();
        $defaultSiteUrl = $this->defaultSiteUrl();
        $defaultSiteLanguage = $this->defaultSiteLanguage();

        return response(Craft::$app->getView()->renderPageTemplate('_special/install/index.twig', compact(
            'showDbScreen',
            'license',
            'defaultSystemName',
            'defaultSiteUrl',
            'defaultSiteLanguage',
        )));
    }

    public function validateDb(Request $request): Response
    {
        try {
            $data = $this->validateDbData($request);
        } catch (ValidationException $e) {
            /**
             * @TODO: Laravel uses status code 422, the frontend should be adjusted to handle this
             */
            return new JsonResponse([
                'errors' => $e->errors(),
            ], 400);
        }

        Config::set("database.connections.{$data['driver']}", array_merge(
            Config::get("database.connections.{$data['driver']}"),
            $data,
        ));

        // Test the connection
        $errors = [];

        try {
            DB::reconnect()->getPdo();
        } catch (PDOException $e) {
            $attr = match ($e->getCode()) {
                1045 => 'user',
                1049 => 'database',
                2002 => 'server',
                default => '*',
            };

            $errors[$attr][] = 'PDO exception: '.$e->getMessage();
        }

        $validates = empty($errors);

        return $validates ?
            new JsonResponse :
            new JsonResponse([
                'errors' => $errors,
            ], 400); // TODO: adjust frontend for 422
    }

    public function validateAccount(Request $request, GeneralConfig $generalConfig): Response
    {
        try {
            $request->validate([
                'email' => ['required', 'email:strict'],
                'username' => [Rule::requiredIf(! $generalConfig->useEmailAsUsername), 'string', 'max:255', 'alpha_num'],
                'password' => Password::required(),
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse([
                'errors' => $e->errors(),
            ], 400); // @todo, support 422 on frontend
        }

        return new JsonResponse;
    }

    public function validateSite(Request $request): Response
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'baseUrl' => ['nullable', 'string', 'max:255'],
                'language' => ['required', 'string', 'max:255', new LanguageRule(onlySiteLanguages: false)],
            ]);

            $baseUrl = Env::parse($request->input('baseUrl'));

            Validator::validate(compact('baseUrl'), ['baseUrl' => 'url']);
        } catch (ValidationException $e) {
            return new JsonResponse([
                'errors' => $e->errors(),
            ], 400); // @todo support 422
        }

        return new JsonResponse;
    }

    public function install(Request $request, Migrator $migrator): Response
    {
        $path = app()->environmentFilePath();

        // Should we set the new DB config values?
        if ($request->has('db-driver')) {
            $data = $this->validateDbData($request, 'db-');

            // Set and save the new DB config values
            // If there's a DB_DSN environment variable, go with that
            Env::writeVariable('DB_CONNECTION', $data['driver'], $path);
            Env::writeVariable('DB_HOST', $data['host'], $path);
            Env::writeVariable('DB_PORT', (string) $data['port'], $path);
            Env::writeVariable('DB_DATABASE', $data['database'], $path);

            Env::writeVariable('DB_USERNAME', $data['username'], $path);
            Env::writeVariable('DB_PASSWORD', $data['password'], $path);
            isset($data['schema']) && Env::writeVariable('DB_SCHEMA', $data['schema'], $path);
            isset($data['prefix']) && Env::writeVariable('DB_TABLE_PREFIX', $data['prefix'], $path);

            // Update the db component based on new values
            Config::set('database.default', $data['driver']);
            Config::set("database.connections.{$data['driver']}", array_merge(
                Config::get("database.connections.{$data['driver']}"),
                $data,
            ));

            DB::setDefaultConnection($data['driver']);
            Config::set('database.connections.db2', array_merge(DB::connection()->getConfig(), [
                'name' => 'db2',
            ]));
            DB::reconnect($data['driver']);
            DB::reconnect('db2');
        }

        $email = $request->input('account-email');
        $username = $request->input('account-username', $email);
        $siteUrl = $request->input('site-baseUrl');

        // Don't save @web even if they chose it
        if ($siteUrl === '@web') {
            $siteUrl = Craft::getAlias($siteUrl);
        }

        // Try to save the site URL to a APP_URL environment variable
        // if it’s not already set to an alias or environment variable
        if ($siteUrl[0] !== '@' && $siteUrl[0] !== '$' && ! App::isEphemeral()) {
            try {
                Env::writeVariable('APP_URL', $siteUrl, $path);
                $siteUrl = '$APP_URL';
            } catch (Throwable) {
                // that's fine, we'll just store the entered URL
            }
        }

        $site = new Site(
            name: $request->input('site-name'),
            handle: 'default',
            language: $request->input('site-language'),
            baseUrl: $siteUrl,
            hasUrls: true,
        );

        $migration = new Install(
            username: $username,
            password: $request->input('account-password'),
            email: $email,
            site: $site,
        );

        // Run the install migration
        $migrator->track('craft');

        try {
            $migrator->runMigration($migration, 'up');
            $migrator->getRepository()->log('Install', 1);
        } catch (Throwable $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 400);
        }

        // Mark all existing migrations as applied
        foreach ($migrator->getPendingMigrations() as $file) {
            $migrator->getRepository()->log($migrator->getMigrationName($file), 1);
        }

        return new JsonResponse;
    }

    private function canControlDbConfig(): bool
    {
        // If this is ephemeral storage, then we can't be writing to a .env file
        if (App::isEphemeral()) {
            return false;
        }

        // If the .env file doesn't exist, we definitely can't do anything about it
        if (! file_exists(app()->environmentFilePath())) {
            return false;
        }

        // Nothing else to worry about, thanks to `CRAFT_X` environment variable overrides
        return true;
    }

    public function validateDbData(Request $request, $prefix = ''): array
    {
        $data = $request->validate([
            $prefix.'driver' => ['required', 'string', Rule::in('mysql', 'pgsql')],
            $prefix.'host' => ['nullable', 'string'],
            $prefix.'database' => ['required', 'string'],
            $prefix.'port' => ['nullable', 'integer'],
            $prefix.'username' => ['nullable', 'string'],
            $prefix.'password' => ['nullable', 'string'],
            $prefix.'prefix' => ['nullable', 'string', 'max:5'],
            $prefix.'schema' => ['nullable', 'string'],
        ]);

        $defaultPort = $data[$prefix.'driver'] === 'mysql' ? 3306 : 5432;

        $data[$prefix.'host'] ??= Config::get("database.connections.{$data[$prefix.'driver']}.host") ?: '127.0.0.1';
        $data[$prefix.'port'] ??= Config::get("database.connections.{$data[$prefix.'driver']}.port") ?: $defaultPort;
        $data[$prefix.'username'] ??= Config::get("database.connections.{$data[$prefix.'driver']}.username") ?: 'root';
        $data[$prefix.'password'] ??= Config::get("database.connections.{$data[$prefix.'driver']}.password");
        $data[$prefix.'prefix'] ??= Config::get("database.connections.{$data[$prefix.'driver']}.prefix");

        return collect($data)->mapWithKeys(fn (mixed $value, string $key) => [Str::after($key, $prefix) => $value])->all();
    }
}
