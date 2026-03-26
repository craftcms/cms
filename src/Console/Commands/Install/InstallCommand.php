<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Install;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\LaravelMigrations;
use CraftCms\Cms\Database\Migrations\Install;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Site\Concerns\SiteDefaults;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Translation\I18N;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use PDOException;
use Throwable;

use function Laravel\Prompts\form;
use function Laravel\Prompts\password;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\text;

class InstallCommand extends Command
{
    use CraftCommand;
    use SiteDefaults;

    #[\Override]
    protected $signature = 'craft:install
        {--email= : The default email address for the first user to create during install.}
        {--username= : The default username for the first user to create during install.}
        {--password= : The default password for the first user to create during install.}
        {--siteName= : The default site name for the first site to create during install.}
        {--siteUrl= : The default site URL for the first site to create during install.}
        {--language= : The default language for the first site to create during install.}
    ';

    #[\Override]
    protected $description = 'Craft CMS CLI installer.';

    #[\Override]
    protected $aliases = ['install:craft', 'install/craft'];

    public function handle(
        GeneralConfig $generalConfig,
        I18N $i18n,
        Migrator $migrator,
        LaravelMigrations $laravelMigrations,
    ): int {
        if (Cms::isInstalled(true)) {
            $this->components->warn('Craft is already installed!');

            return self::SUCCESS;
        }

        try {
            DB::clearResolvedInstances();
            DB::reconnect()->getPdo();
        } catch (PDOException) {
            return $this->call('craft:setup:welcome');
        }

        $defaultSiteName = $this->defaultSiteName();
        $defaultSiteUrl = $this->defaultSiteUrl();
        $defaultSiteLanguage = $this->defaultSiteLanguage();

        $responses = form()
            ->addIf(! $generalConfig->useEmailAsUsername && ! $this->option('username'), fn ($form) => text(
                label: 'What is the username?',
                required: true,
                validate: ['alpha_dash']
            ), 'username')
            ->addIf(! $this->option('email'), fn () => text(
                label: 'What is the email address?',
                required: true,
                validate: [
                    'email:strict',
                ]
            ), 'email')
            ->addIf(! $this->option('password'), fn () => password(
                label: 'What is the password?',
                required: true,
                validate: [
                    Password::required(),
                ]
            ), 'password')
            ->addIf(! $this->option('siteName'), fn () => text(
                label: 'What is the site name?',
                default: $defaultSiteName ?? '',
                required: true,
                validate: [
                    'max:255',
                ]
            ), 'siteName')
            ->addIf(! $this->option('siteUrl'), fn () => text(
                label: 'What is the site url?',
                default: $defaultSiteUrl ?? '',
                required: true,
                validate: [
                    'max:255',
                ]
            ), 'siteUrl')
            ->addIf(! $this->option('language'), fn () => suggest(
                label: 'What is the site language?',
                options: fn (string $value) => $i18n->getAllLocaleIds()
                    ->filter(fn (string $locale) => str_contains($locale, $value))
                    ->all(),
                default: $defaultSiteLanguage,
                validate: function (string $value) use ($i18n) {
                    try {
                        $value = $i18n->normalizeLanguage($value);
                    } catch (Throwable) {
                        return "$value is not a valid language.";
                    }

                    if (! $value) {
                        return 'The site language is required.';
                    }

                    if ($i18n->getAllLocaleIds()->doesntContain($value)) {
                        return "$value is not a valid language.";
                    }

                    return null;
                }
            ), 'language')
            ->submit();

        $username = $this->option('username') ?? $responses['username'];
        $email = $this->option('email') ?? $responses['email'];
        $password = $this->option('password') ?? $responses['password'];
        $siteName = $this->option('siteName') ?? $responses['siteName'];
        $siteUrl = $this->option('siteUrl') ?? $responses['siteUrl'];
        $language = $this->option('language') ?? $responses['language'];

        if ($generalConfig->useEmailAsUsername) {
            $username = $email;
        }

        $site = new Site([
            'name' => $siteName,
            'handle' => 'default',
            'language' => $language,
            'baseUrl' => $siteUrl,
            'hasUrls' => true,
        ]);

        // Try to save the site URL to a APP_URL environment variable
        // if it’s not already set to an alias or environment variable
        if (! in_array($site->getBaseUrl(false)[0], ['@', '$'])) {
            try {
                Env::writeVariable('APP_URL', $site->getBaseUrl(), app()->environmentFilePath());
                $site->setBaseUrl('$APP_URL');
            } catch (Throwable) {
                // that's fine, we'll just store the entered URL
            }
        }

        $this->components->info('Installing Craft');

        $migrator->runMigration(new Install(
            username: $username,
            password: $password,
            email: $email,
            site: $site,
        ), 'up');

        $migrator->getRepository()->log('Install', 1);

        $this->components->success('Installed Craft successfully');

        // Mark all existing migrations as applied
        foreach ($migrator->getPendingMigrations() as $file) {
            $migrator->getRepository()->log($migrator->getMigrationName($file), 1);
        }

        $laravelMigrations->install($migrator);

        $this->ensureProjectConfigFileExists();

        return self::SUCCESS;
    }
}
