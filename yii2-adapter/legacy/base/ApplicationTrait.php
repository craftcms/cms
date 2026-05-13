<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\console\Application as ConsoleApplication;
use craft\db\Connection;
use craft\db\mysql\Schema;
use craft\errors\DbConnectException;
use craft\events\DeleteSiteEvent;
use craft\i18n\Formatter;
use craft\i18n\I18N;
use craft\log\Dispatcher;
use craft\mail\Mailer;
use craft\markdown\GithubMarkdown;
use craft\markdown\Markdown;
use craft\markdown\MarkdownExtra;
use craft\markdown\PreEncodedMarkdown;
use craft\models\Info;
use craft\queue\QueueInterface;
use craft\services\Addresses;
use craft\services\AssetIndexer;
use craft\services\Assets;
use craft\services\Auth;
use craft\services\Categories;
use craft\services\Config;
use craft\services\Dashboard;
use craft\services\Deprecator;
use craft\services\Drafts;
use craft\services\Elements;
use craft\services\ElementSources;
use craft\services\Entries;
use craft\services\Fields;
use craft\services\Fs;
use craft\services\Gc;
use craft\services\Globals;
use craft\services\Gql;
use craft\services\Images;
use craft\services\ImageTransforms;
use craft\services\Path;
use craft\services\Plugins;
use craft\services\Relations;
use craft\services\Revisions;
use craft\services\Routes;
use craft\services\Search;
use craft\services\Security;
use craft\services\Sites;
use craft\services\Sso;
use craft\services\Structures;
use craft\services\SystemMessages;
use craft\services\Tags;
use craft\services\TemplateCaches;
use craft\services\Tokens;
use craft\services\UserGroups;
use craft\services\UserPermissions;
use craft\services\Users;
use craft\services\Utilities;
use craft\services\Volumes;
use craft\services\Webpack;
use craft\web\Application as WebApplication;
use craft\web\AssetManager;
use craft\web\UrlManager;
use craft\web\View;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Announcement\Announcements;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Edition;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Composer;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Deprecator as DeprecatorFacade;
use CraftCms\Cms\Translation\Locale;
use CraftCms\Cms\Update\Updates;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Symfony\Component\VarDumper\Dumper\AbstractDumper;
use Yii;
use yii\base\Application;
use yii\base\ErrorHandler;
use yii\base\Event;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\ColumnSchemaBuilder;
use yii\db\Exception as DbException;
use craft\helpers\Markdown as MarkdownHelper;
use yii\mutex\Mutex;
use yii\queue\Queue;
use yii\web\ServerErrorHttpException;

/**
 * ApplicationTrait
 *
 * @property bool $isInstalled Whether Craft is installed
 * @property-read Addresses $addresses The addresses service
 * @property-read Announcements $announcements The announcements service
 * @property-read AssetIndexer $assetIndexer The asset indexer service
 * @property-read AssetManager $assetManager The asset manager component
 * @property-read Assets $assets The assets service
 * @property-read Auth $auth The user authentication service
 * @property-read Categories $categories The categories service
 * @property-read Conditions $conditions The conditions service
 * @property-read Config $config The config service
 * @property-read Connection $db The database connection component
 * @property-read Connection $db2 The database connection component used for mutex locks and element bulk op records
 * @property-read Dashboard $dashboard The dashboard service
 * @property-read Deprecator $deprecator The deprecator service
 * @property-read Drafts $drafts The drafts service
 * @property-read ElementSources $elementSources The element sources service
 * @property-read Elements $elements The elements service
 * @property-read Entries $entries The entries service
 * @property-read Fields $fields The fields service
 * @property-read Formatter $formatter The formatter component
 * @property-read Fs $fs The filesystems service
 * @property-read Gc $gc The garbage collection service
 * @property-read Globals $globals The globals service
 * @property-read Gql $gql The GraphQl service
 * @property-read I18N $i18n The internationalization (i18n) component
 * @property-read Images $images The images service (deprecated)
 * @property-read ImageTransforms $imageTransforms The image transforms service
 * @property-read Locale $formattingLocale The Locale object that should be used to define the formatter
 * @property-read Locale $locale The Locale object for the target language
 * @property-read Mailer $mailer The mailer component
 * @property-read Mutex $mutex The application’s mutex service
 * @property-read Path $path The path service
 * @property-read Plugins $plugins The plugins service
 * @property-read \craft\services\ProjectConfig $projectConfig The project config service
 * @property-read Queue|QueueInterface $queue The job queue
 * @property-read Relations $relations The relations service (deprecated)
 * @property-read Revisions $revisions The revisions service
 * @property-read Routes $routes The routes service
 * @property-read Search $search The search service
 * @property-read Security $security The security component
 * @property-read Sites $sites The sites service
 * @property-read Sso $sso The SSO service
 * @property-read Structures $structures The structures service
 * @property-read SystemMessages $systemMessages The system email messages service
 * @property-read Tags $tags The tags service
 * @property-read TemplateCaches $templateCaches The template caches service
 * @property-read Tokens $tokens The tokens service
 * @property-read UrlManager $urlManager The URL manager for this application
 * @property-read UserGroups $userGroups The user groups service
 * @property-read UserPermissions $userPermissions The user permissions service
 * @property-read Users $users The users service
 * @property-read Utilities $utilities The utilities service
 * @property-read View $view The view component
 * @property-read Volumes $volumes The volumes service
 * @property-read Webpack $webpack The webpack service
 * @property-read bool $canTestEditions Whether Craft is running on a domain that is eligible to test out the editions
 * @property-read bool $canUpgradeEdition Whether Craft is eligible to be upgraded to a different edition
 * @property-read bool $hasWrongEdition Whether Craft is running with the wrong edition
 * @property-read bool $isInMaintenanceMode Whether someone is currently performing a system update
 * @property-read bool $isInitialized Whether Craft is fully initialized
 * @property-read bool $isMultiSite Whether this site has multiple sites
 * @property-read bool $isSystemLive Whether the system is live
 * @property-read string $installedSchemaVersion The installed schema version
 *
 * @method AssetManager getAssetManager() Returns the asset manager component.
 * @method Connection getDb() Returns the database connection component.
 * @method Dispatcher getLog() Returns the log dispatcher component.
 * @method Formatter getFormatter() Returns the formatter component.
 * @method I18N getI18n() Returns the internationalization (i18n) component.
 * @method Security getSecurity() Returns the security component.
 * @method UrlManager getUrlManager() Returns the URL manager for this application.
 * @method View getView() Returns the view component.
 *
 * @mixin WebApplication
 * @mixin ConsoleApplication
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 */
trait ApplicationTrait
{
    /**
     * @var string Craft’s schema version number.
     */
    public string $schemaVersion {
        get => Cms::SCHEMA_VERSION;
        set(string $value) => $this->schemaVersion = $value;
    }

    /**
     * @var string The minimum Craft build number required to update to this build.
     */
    public string $minVersionRequired {
        get => Cms::MIN_VERSION_REQUIRED;
        set(string $value) => $this->minVersionRequired = $value;
    }

    /**
     * @var string|null The environment ID Craft is currently running in.
     */
    public ?string $env {
        get => app()->environment();
        set(null|string $value) => $this->env = $value;
    }

    /**
     * @var Edition The installed Craft CMS edition.
     *
     * @since 5.0.0
     * @deprecated 6.0.0 use `Edition::get()` instead.
     */
    public Edition $edition {
        get => Edition::get();
    }

    /**
     * @var bool Whether the application is fully initialized yet
     *
     * @see getIsInitialized()
     */
    private bool $_isInitialized = false;

    private bool $_gettingLanguage = false;

    /**
     * @var bool Whether we’re listening for the request end, to update the application info
     *
     * @see saveInfoAfterRequest()
     */
    private bool $_waitingToSaveInfo = false;

    /**
     * @var callable[]
     *
     * @see onAfterRequest()
     */
    private array $afterRequestCallbacks = [];

    /**
     * Returns the application ID combined with the environment name.
     *
     * @since 5.4.0
     * @see id
     * @see env
     */
    public function getEnvId(): string
    {
        return $this->env ? sprintf('%s--%s', $this->id, $this->env) : $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setVendorPath($path): void
    {
        parent::setVendorPath($path);

        // Override the @bower and @npm aliases if using asset-packagist.org
        // todo: remove this whenever Yii is updated with support for asset-packagist.org
        $altBowerPath = $this->getVendorPath().DIRECTORY_SEPARATOR.'bower-asset';
        $altNpmPath = $this->getVendorPath().DIRECTORY_SEPARATOR.'npm-asset';
        if (is_dir($altBowerPath)) {
            Aliases::set('@bower', $altBowerPath);
        }
        if (is_dir($altNpmPath)) {
            Aliases::set('@npm', $altNpmPath);
        }

        // Override where Yii should find its asset deps
        Aliases::set('@bower/jquery/dist', '@app/web/assets/jquery/dist');
        Aliases::set('@bower/inputmask/dist', '@app/web/assets/inputmask/dist');
        Aliases::set('@bower/punycode', '@app/web/assets/punycode/dist');
        Aliases::set('@bower/yii2-pjax', '@app/web/assets/yii2pjax/dist');
    }

    /**
     * Sets the target application language.
     *
     * @param  bool|null  $useUserLanguage  Whether the user’s preferred language should be used.
     *                                      If null, the user’s preferred language will be used if this is a control panel request or a console request.
     */
    public function updateTargetLanguage(?bool $useUserLanguage = null): void
    {
        // Defend against an infinite updateTargetLanguage() loop
        if ($this->_gettingLanguage === true) {
            // We tried to get the language, but something went wrong. Use fallback to prevent infinite loop.
            $fallbackLanguage = $this->_getFallbackLanguage();
            $this->_gettingLanguage = false;
            app()->setLocale($fallbackLanguage);

            return;
        }

        $this->_gettingLanguage = true;

        if ($useUserLanguage === null) {
            $useUserLanguage = $this->getRequest()->getIsCpRequest();
        }

        app()->setLocale($this->getTargetLanguage($useUserLanguage));

        $this->_gettingLanguage = false;
    }

    /**
     * Returns the target app language.
     *
     * @param  bool  $useUserLanguage  Whether the user’s preferred language should be used.
     */
    public function getTargetLanguage(bool $useUserLanguage = true): string
    {
        // Use the fallback language for console requests, or if Craft isn't installed or is updating
        if (
            $this instanceof ConsoleApplication ||
            ! Cms::isInstalled() ||
            app(Updates::class)->isCraftUpdatePending()
        ) {
            return $this->_getFallbackLanguage();
        }

        if ($useUserLanguage) {
            // If the user is logged in *and* has a primary language set, use that
            // (don't actually try to fetch the user, as plugins haven't been loaded yet)
            $id = Session::get($this->getUser()->idParam);
            if (
                $id &&
                ($language = \CraftCms\Cms\Support\Facades\Users::getUserPreference($id, 'language')) !== null &&
                \CraftCms\Cms\Support\Facades\I18N::validateAppLocaleId($language)
            ) {
                return $language;
            }

            // Fall back on the default control panel language, if there is one, otherwise the browser language
            return Cms::config()->defaultCpLanguage ?? $this->_getFallbackLanguage();
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        return \CraftCms\Cms\Support\Facades\Sites::getCurrentSite()->getLanguage();
    }

    /**
     * Returns whether Craft is installed.
     *
     * @param  bool  $strict  Whether to ignore the cached value and explicitly check from the default schema.
     *
     * @deprecated 6.0.0 use {@see Cms::isInstalled()} instead.
     */
    public function getIsInstalled(bool $strict = false): bool
    {
        return Cms::isInstalled($strict);
    }

    /**
     * Sets Craft's record of whether it's installed
     *
     * @deprecated 6.0.0 use {@see Cms::setIsInstalled()} instead.
     */
    public function setIsInstalled(?bool $value = true): void
    {
        Cms::setIsInstalled($value);
    }

    /**
     * Returns the installed schema version.
     *
     * @since 3.2.0
     * @deprecated in 4.0.0
     */
    public function getInstalledSchemaVersion(): string
    {
        return \CraftCms\Cms\Shared\Models\Info::fetch()->schemaVersion ?: $this->schemaVersion;
    }

    /**
     * Returns whether Craft has been fully initialized.
     *
     * @since 3.0.13
     */
    public function getIsInitialized(): bool
    {
        return $this->_isInitialized;
    }

    /**
     * Invokes a callback function when Craft is fully initialized.
     *
     * If Craft is already fully initialized, the callback will be invoked immediately.
     *
     * @since 4.3.5
     */
    public function onInit(callable $callback): void
    {
        if ($this->_isInitialized) {
            $callback();
        } else {
            $this->on(WebApplication::EVENT_INIT, function () use ($callback) {
                $callback();
            });
        }
    }

    /**
     * Invokes a callback function at the end of the request.
     *
     * If the request is already ending, the callback will be invoked immediately.
     *
     * @since 4.5.11
     */
    public function onAfterRequest(callable $callback): void
    {
        if (in_array($this->state, [
            Application::STATE_AFTER_REQUEST,
            Application::STATE_SENDING_RESPONSE,
            Application::STATE_END,
        ], true)) {
            $callback();
        } else {
            $this->afterRequestCallbacks[] = $callback;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function trigger($name, ?Event $event = null)
    {
        // call the onAfterRequest() callbacks directly
        if ($name === self::EVENT_AFTER_REQUEST && ! empty($this->afterRequestCallbacks)) {
            $event ??= new Event;
            $event->sender = $this;
            $event->name = $name;
            while ($callback = array_shift($this->afterRequestCallbacks)) {
                $callback($event);
            }
        }

        parent::trigger($name, $event);
    }

    /**
     * Returns whether this Craft install has multiple sites.
     *
     * @param  bool  $refresh  Whether to ignore the cached result and check again
     * @param  bool  $withTrashed  Whether to factor in soft-deleted sites
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Site\Sites::isMultiSite} instead.
     */
    public function getIsMultiSite(bool $refresh = false, bool $withTrashed = false): bool
    {
        DeprecatorFacade::log('Craft::$app->getIsMultiSite()', 'Craft::$app->getIsMultiSite() is deprecated. Use Sites::isMultiSite() or craft.sites.isMultiSite() instead.');

        return \CraftCms\Cms\Support\Facades\Sites::isMultiSite($refresh, $withTrashed);
    }

    /**
     * @deprecated in 6.0.0. Use Laravel's container instead
     */
    public function getComposer(): Composer
    {
        return app(Composer::class);
    }

    /**
     * Returns the installed Craft CMS edition’s ID.
     *
     * @deprecated in 6.0.0. `Edition::get()->value` should be used instead.
     */
    public function getEdition(): int
    {
        return Edition::get()->value;
    }

    /**
     * Returns the name of the Craft edition.
     *
     * @deprecated in 6.0.0. `Edition::get()->name` should be used instead.
     */
    public function getEditionName(): string
    {
        return Edition::get()->name;
    }

    /**
     * Returns the handle of the Craft edition.
     *
     * @since 4.4.0
     * @deprecated in 6.0.0. `Edition::get()->handle()` should be used instead.
     */
    public function getEditionHandle(): string
    {
        return Edition::get()->handle();
    }

    /**
     * Returns the edition Craft is actually licensed to run in.
     *
     * @deprecated in 6.0.0. `Edition::getLicensed()` should be used instead.
     */
    public function getLicensedEdition(): ?Edition
    {
        return Edition::getLicensed();
    }

    /**
     * Returns the name of the edition Craft is actually licensed to run in.
     *
     * @deprecated in 6.0.0. `Edition::getLicensed()?->name` should be used instead.
     */
    public function getLicensedEditionName(): ?string
    {
        return Edition::getLicensed()?->name;
    }

    /**
     * Returns whether Craft is running with the wrong edition.
     *
     * @deprecated in 6.0.0. `Edition::isWrong()` should be used instead.
     */
    public function getHasWrongEdition(): bool
    {
        return Edition::isWrong();
    }

    /**
     * Sets the installed Craft CMS edition.
     *
     * @param  Edition|int  $edition  The edition to set.
     *
     * @deprecated in 6.0.0. `Edition::set($edition)` should be used instead.
     */
    public function setEdition(Edition|int $edition): bool
    {
        Edition::set($edition);

        return true;
    }

    /**
     * Requires that Craft is running an equal or better edition than what's passed in
     *
     * @param  Edition|int  $edition  The Craft edition to require.
     * @param  bool  $orBetter  If true, makes $edition the minimum edition required.
     *
     * @deprecated in 6.0.0. {@see Edition::require()} should be used instead.
     */
    public function requireEdition(Edition|int $edition, bool $orBetter = true): void
    {
        Edition::require($edition, $orBetter);
    }

    /**
     * Returns whether Craft is eligible to be upgraded to a different edition.
     *
     * @deprecated in 6.0.0. {@see Edition::canUpgrade()} should be used instead.
     */
    public function getCanUpgradeEdition(): bool
    {
        return Edition::canUpgrade();
    }

    /**
     * Returns whether Craft is running on a domain that is eligible to test
     * unlicensed Craft and plugin editions/updates.
     *
     * @internal
     *
     * @deprecated in 6.0.0. {@see Edition::canTest()} should be used instead.
     */
    public function getCanTestEditions(): bool
    {
        return Edition::canTest();
    }

    /**
     * Returns the system's UID.
     */
    public function getSystemUid(): ?string
    {
        return \CraftCms\Cms\Shared\Models\Info::fetch()->uid;
    }

    /**
     * Returns whether the system is currently live.
     *
     * @since 3.1.0
     * @deprecated 6.0.0 use `app()->isLive()` instead.
     */
    public function getIsLive(): bool
    {
        return app()->isLive();
    }

    /**
     * Returns whether someone is currently performing a system update.
     *
     * @see enableMaintenanceMode()
     * @see disableMaintenanceMode()
     */
    public function getIsInMaintenanceMode(): bool
    {
        return app()->isDownForMaintenance();
    }

    /**
     * Enables Maintenance Mode.
     *
     * @see getIsInMaintenanceMode()
     * @see disableMaintenanceMode()
     */
    public function enableMaintenanceMode(): bool
    {
        app()->maintenanceMode()->activate([]);

        return true;
    }

    /**
     * Disables Maintenance Mode.
     *
     * @see getIsInMaintenanceMode()
     * @see disableMaintenanceMode()
     */
    public function disableMaintenanceMode(): bool
    {
        app()->maintenanceMode()->deactivate();

        return true;
    }

    /**
     * Returns the info model, or just a particular attribute.
     *
     * @param  bool  $throwException  Whether an exception should be thrown if the `info` table doesn't exist
     *
     * @throws DbException if the `info` table doesn’t exist yet and `$throwException` is `true`
     * @throws ServerErrorHttpException if the info table is missing its row
     */
    public function getInfo(bool $throwException = false): Info
    {
        $info = \CraftCms\Cms\Shared\Models\Info::fetch($throwException);

        return new Info($info->toArray());
    }

    /**
     * Updates the info row at the end of the request.
     *
     * @since 3.1.33
     */
    public function saveInfoAfterRequest(): void
    {
        if (! $this->_waitingToSaveInfo) {
            $this->_waitingToSaveInfo = true;

            $this->onAfterRequest(function () {
                $this->saveInfoAfterRequestHandler();
            });
        }
    }

    /**
     * @throws Exception
     * @throws ServerErrorHttpException
     *
     * @since 3.1.33
     *
     * @internal
     */
    public function saveInfoAfterRequestHandler(): void
    {
        $info = \CraftCms\Cms\Shared\Models\Info::fetch();

        if (! $info->save()) {
            throw new Exception('Unable to save new application info');
        }

        $this->_waitingToSaveInfo = false;
    }

    /**
     * Updates the info row.
     *
     * @param  string[]|null  $attributeNames  The attributes to save
     */
    public function saveInfo(Info $info, ?array $attributeNames = null): bool
    {
        if ($attributeNames === null) {
            $attributeNames = ['version', 'schemaVersion', 'maintenance', 'configVersion'];
        }

        if (! $info->validate($attributeNames)) {
            return false;
        }

        $attributes = $info->getAttributes($attributeNames);

        \CraftCms\Cms\Shared\Models\Info::updateOrCreate(
            ['id' => 1],
            $attributes,
        );

        Cms::setIsInstalled();

        return true;
    }

    /**
     * Returns the system name.
     *
     * @since 3.1.4
     * @deprecated 6.0.0 use {@see Cms::systemName()} instead.
     */
    public function getSystemName(): string
    {
        return Cms::systemName();
    }

    /**
     * Returns the Yii framework version.
     */
    public function getYiiVersion(): string
    {
        return Yii::getVersion();
    }

    /**
     * Returns whether the DB connection settings are valid.
     *
     * @internal Don't even think of moving this check into Connection->init().
     */
    public function getIsDbConnectionValid(): bool
    {
        try {
            $this->getDb()->open();
        } catch (DbConnectException|InvalidConfigException $e) {

            // Only log for web requests
            if ($this instanceof WebApplication) {
                Log::error('There was a problem connecting to the database: '.$e->getMessage(), [__METHOD__]);
                /** @var ErrorHandler $errorHandler */
                $errorHandler = $this->getErrorHandler();
                $errorHandler->logException($e);
            }

            return false;
        }

        return true;
    }

    // Service Getters
    // -------------------------------------------------------------------------

    /**
     * Returns the addresses service.
     *
     * @return Addresses The addresses service
     *
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Address\Addresses} instead.
     */
    public function getAddresses(): Addresses
    {
        return $this->get('addresses');
    }

    /**
     * Returns the announcements service.
     *
     * @return Announcements The announcements service
     *
     * @since 3.7.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Announcement\Announcements} instead.
     */
    public function getAnnouncements(): Announcements
    {
        return $this->get('announcements');
    }

    /**
     * Returns the assets service.
     *
     * @return Assets The assets service
     * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\Asset\Assets} or {@see \CraftCms\Cms\Asset\Folders} instead.
     */
    public function getAssets(): Assets
    {
        return $this->get('assets');
    }

    /**
     * Returns the asset indexing service.
     *
     * @return AssetIndexer The asset indexing service
     */
    public function getAssetIndexer(): AssetIndexer
    {
        return $this->get('assetIndexer');
    }

    /**
     * Returns the user authentication service.
     *
     * @return Auth The Auth service
     *
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Auth\AuthMethods} instead.
     */
    public function getAuth(): Auth
    {
        return $this->get('auth');
    }

    /**
     * Returns the image transforms service.
     *
     * @return ImageTransforms The asset transforms service
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Image\ImageTransforms} instead.
     */
    public function getImageTransforms(): ImageTransforms
    {
        return $this->get('imageTransforms');
    }

    /**
     * Returns the categories service.
     *
     * @return Categories The categories service
     *
     * @deprecated in 6.0.0
     */
    public function getCategories(): Categories
    {
        return $this->get('categories');
    }

    /**
     * Returns the conditions service.
     *
     * @return Conditions The conditions service
     *
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Condition\Conditions} instead.
     */
    public function getConditions(): Conditions
    {
        return $this->get('conditions');
    }

    /**
     * Returns the config service.
     *
     * @return Config The config service
     */
    public function getConfig(): Config
    {
        return $this->get('config');
    }

    /**
     * Returns the dashboard service.
     *
     * @return Dashboard The dashboard service
     *
     * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\Dashboard\Dashboard} instead.
     */
    public function getDashboard(): Dashboard
    {
        return $this->get('dashboard');
    }

    /**
     * Returns the database connection used for mutex locks and element bulk op records.
     *
     * This helps avoid erratic behavior when locks are used during transactions
     * (see https://makandracards.com/makandra/17437-mysql-careful-when-using-database-locks-in-transactions).
     *
     * @since 5.3.0
     */
    public function getDb2(): Connection
    {
        return $this->get('db2');
    }

    /**
     * Returns the deprecator service.
     *
     * @return Deprecator The deprecator service
     *
     * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\Deprecator\Deprecator} instead.
     */
    public function getDeprecator(): Deprecator
    {
        return $this->get('deprecator');
    }

    /**
     * Returns the drafts service.
     *
     * @return Drafts The drafts service
     *
     * @since 3.2.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Drafts} instead.
     */
    public function getDrafts(): Drafts
    {
        return $this->get('drafts');
    }

    /**
     * Returns the variable dumper.
     *
     * @since 4.4.2
     */
    public function getDumper(): AbstractDumper
    {
        return $this->get('dumper');
    }

    /**
     * Returns the element indexes service.
     *
     * @return ElementSources The element indexes service
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementSources} instead.
     */
    public function getElementSources(): ElementSources
    {
        return $this->get('elementSources');
    }

    /**
     * Returns the elements service.
     *
     * @return Elements The elements service
     */
    public function getElements(): Elements
    {
        return $this->get('elements');
    }

    /**
     * Returns the system email messages service.
     *
     * @return SystemMessages The system email messages service
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\SystemMessage\SystemMessages} instead.
     */
    public function getSystemMessages(): SystemMessages
    {
        return $this->get('systemMessages');
    }

    /**
     * Returns the entries service.
     *
     * @return Entries The entries service
     *
     * @deprecated 6.0.0. Use {@see \CraftCms\Cms\Entry\EntryTypes}, {@see \CraftCms\Cms\Section\Sections} or {@see \CraftCms\Cms\Entry\Entries} instead.
     */
    public function getEntries(): Entries
    {
        return $this->get('entries');
    }

    /**
     * Returns the fields service.
     *
     * @return Fields The fields service
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Fields} instead.
     */
    public function getFields(): Fields
    {
        DeprecatorFacade::log('Craft::$app->fields', 'Craft::$app->fields is deprecated. Use app(Fields::class) or craft.fields instead.');

        return $this->get('fields');
    }

    /**
     * Returns the filesystems service.
     *
     * @return Fs The filesystems service
     *
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Filesystem\Filesystems} instead.
     */
    public function getFs(): Fs
    {
        return $this->get('fs');
    }

    /**
     * Returns the locale that should be used to define the formatter.
     *
     * @since 3.6.0
     * @deprecated 6.0.0 use {I18N::getFormattingLocale()} instead.
     */
    public function getFormattingLocale(): \craft\i18n\Locale
    {
        DeprecatorFacade::log('Craft::$app->getFormattingLocale()', 'Craft::$app->getFormattingLocale() is deprecated. Use I18N::getFormattingLocale() or craft.i18n.getFormattingLocale instead.');

        return $this->get('formattingLocale');
    }

    /**
     * Returns the garbage collection service.
     *
     * @return Gc The garbage collection service
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\GarbageCollection\GarbageCollection} instead.
     */
    public function getGc(): Gc
    {
        return $this->get('gc');
    }

    /**
     * Returns the globals service.
     *
     * @return Globals The globals service
     *
     * @deprecated in 6.0.0
     */
    public function getGlobals(): Globals
    {
        return $this->get('globals');
    }

    /**
     * Returns the GraphQL service.
     *
     * @return Gql The GraphQL service
     *
     * @since 3.3.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Gql\Gql} instead.
     */
    public function getGql(): Gql
    {
        return $this->get('gql');
    }

    /**
     * Returns the images service.
     *
     * @return Images The images service
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Image\Images} instead.
     */
    public function getImages(): Images
    {
        return $this->get('images');
    }

    /**
     * Returns a Locale object for the target language.
     *
     * @return \craft\i18n\Locale The Locale object for the target language
     */
    public function getLocale(): \craft\i18n\Locale
    {
        DeprecatorFacade::log('Craft::$app->getLocale()', 'Craft::$app->getLocale() is deprecated. Use I18N::getLocale() or craft.i18n.getLocale instead.');

        return $this->get('locale');
    }

    /**
     * Returns the current mailer.
     *
     * @return Mailer The mailer component
     * @deprecated 6.0.0 use Laravel mailers/drivers and system-message mailables.
     */
    public function getMailer(): Mailer
    {
        return $this->get('mailer');
    }

    /**
     * Returns the application’s mutex service.
     *
     * @return Mutex The application’s mutex service
     *
     * @deprecated in 6.0.0. Use `\Illuminate\Support\Facades\Cache::lock()` instead.
     */
    public function getMutex(): Mutex
    {
        return $this->get('mutex');
    }

    /**
     * Returns the path service.
     *
     * @return Path The path service
     */
    public function getPath(): Path
    {
        return $this->get('path');
    }

    /**
     * Returns the plugins service.
     *
     * @return Plugins The plugins service
     *
     * @deprecated 6.0.0 Use {@see \CraftCms\Cms\Plugin\Plugins} instead.
     */
    public function getPlugins(): Plugins
    {
        return $this->get('plugins');
    }

    /**
     * Returns the system config service.
     *
     * @return \craft\services\ProjectConfig The system config service
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\ProjectConfig\ProjectConfig} instead.
     */
    public function getProjectConfig(): \craft\services\ProjectConfig
    {
        return $this->get('projectConfig');
    }

    /**
     * Returns the queue service.
     *
     * @return Queue|QueueInterface The queue service
     * @deprecated 6.0.0. Use Laravel's Queue system instead.
     */
    public function getQueue(): Queue|QueueInterface
    {
        return $this->get('queue');
    }

    /**
     * Returns the relations service.
     *
     * @return Relations The relations service
     *
     * @deprecated in 5.3.0
     */
    public function getRelations(): Relations
    {
        return $this->get('relations');
    }

    /**
     * Returns the revisions service.
     *
     * @return Revisions The revisions service
     *
     * @since 3.2.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Revisions} instead.
     */
    public function getRevisions(): Revisions
    {
        return $this->get('revisions');
    }

    /**
     * Returns the routes service.
     *
     * @return Routes The routes service
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Route\Routes} instead.
     */
    public function getRoutes(): Routes
    {
        return $this->get('routes');
    }

    /**
     * Returns the search service.
     *
     * @return Search The search service
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Search\Search} instead.
     */
    public function getSearch(): Search
    {
        return $this->get('search');
    }

    /**
     * Returns the sites service.
     *
     * @return Sites The sites service
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Site\Sites} instead.
     */
    public function getSites(): Sites
    {
        DeprecatorFacade::log('Craft::$app->sites', 'Craft::$app->sites is deprecated. Use app(Sites::class), app(SiteGroups::class) or craft.sites / craft.siteGroups instead.');

        return $this->get('sites');
    }

    /**
     * Returns the SSO service.
     *
     * @return Sso The SSO service
     *
     * @since 5.3.0
     * @deprecated 6.0.0 use the Laravel Socialite {@see \CraftCms\Cms\Auth\OAuth\OAuth} implementation instead.
     */
    public function getSso(): Sso
    {
        return $this->get('sso');
    }

    /**
     * Returns the structures service.
     *
     * @return Structures The structures service
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Structure\Structures} instead.
     */
    public function getStructures(): Structures
    {
        return $this->get('structures');
    }

    /**
     * Returns the tags service.
     *
     * @return Tags The tags service
     *
     * @deprecated in 6.0.0
     */
    public function getTags(): Tags
    {
        return $this->get('tags');
    }

    /**
     * Returns the template cache service.
     *
     * @return TemplateCaches The template caches service
     * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\View\TemplateCaches} instead.
     */
    public function getTemplateCaches(): TemplateCaches
    {
        return $this->get('templateCaches');
    }

    /**
     * Returns the tokens service.
     *
     * @return Tokens The tokens service
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\RouteToken\RouteTokens} instead.
     */
    public function getTokens(): Tokens
    {
        return $this->get('tokens');
    }

    /**
     * Returns the user groups service.
     *
     * @return UserGroups The user groups service
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\UserGroups} instead.
     */
    public function getUserGroups(): UserGroups
    {
        return $this->get('userGroups');
    }

    /**
     * Returns the user permissions service.
     *
     * @return UserPermissions The user permissions service
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\UserPermissions} instead.
     */
    public function getUserPermissions(): UserPermissions
    {
        return $this->get('userPermissions');
    }

    /**
     * Returns the users service.
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\Users} instead.
     *
     * @return Users The users service
     */
    public function getUsers(): Users
    {
        return $this->get('users');
    }

    /**
     * Returns the utilities service.
     *
     * @deprecated in 6.0.0. [[app(\CraftCms\Cms\Utility\Utilities)]] should be used instead.
     *
     * @return Utilities The utilities service
     */
    public function getUtilities(): Utilities
    {
        return $this->get('utilities');
    }

    /**
     * Returns the volumes service.
     *
     * @return Volumes The volumes service
     */
    public function getVolumes(): Volumes
    {
        return $this->get('volumes');
    }

    /**
     * Returns the webpack service.
     *
     * @return Webpack The volumes service
     *
     * @since 3.7.22
     */
    public function getWebpack(): Webpack
    {
        return $this->get('webpack');
    }

    /**
     * Initializes things that should happen before the main Application::init()
     */
    private function _preInit(): void
    {
        // Add support for MySQL-specific column types
        ColumnSchemaBuilder::$typeCategoryMap[Schema::TYPE_TINYTEXT] = ColumnSchemaBuilder::CATEGORY_STRING;
        ColumnSchemaBuilder::$typeCategoryMap[Schema::TYPE_MEDIUMTEXT] = ColumnSchemaBuilder::CATEGORY_STRING;
        ColumnSchemaBuilder::$typeCategoryMap[Schema::TYPE_LONGTEXT] = ColumnSchemaBuilder::CATEGORY_STRING;
        ColumnSchemaBuilder::$typeCategoryMap[Schema::TYPE_ENUM] = ColumnSchemaBuilder::CATEGORY_STRING;

        // Load the request before anything else, so everything else can safely check Craft::$app->has('request', true)
        // to avoid possible recursive fatal errors in the request initialization
        $this->getRequest();
        $this->getLog();

        $this->language = app()->getLocale();
        $this->setTimeZone(Cms::timezone());

        // Use our own Markdown parser classes
        $flavors = [
            'original' => Markdown::class,
            'pre-encoded' => PreEncodedMarkdown::class,
            'gfm' => GithubMarkdown::class,
            'gfm-comment' => GithubMarkdown::class,
            'extra' => MarkdownExtra::class,
        ];

        foreach ($flavors as $flavor => $class) {
            if (! isset(MarkdownHelper::$flavors[$flavor]) || ! is_object(MarkdownHelper::$flavors[$flavor])) {
                MarkdownHelper::$flavors[$flavor]['class'] = $class;
            }
        }
    }

    /**
     * Initializes things that should happen after the main Application::init()
     */
    private function _postInit(): void
    {
        // Register all the listeners for config items
        $this->_registerConfigListeners();

        $this->_isInitialized = true;

        // Fire an 'init' event
        if ($this->hasEventHandlers(WebApplication::EVENT_INIT)) {
            $this->trigger(WebApplication::EVENT_INIT);
        }
    }

    /**
     * Tries to find a language match with the browser’s preferred language(s).
     *
     * If not uses the app’s sourceLanguage.
     */
    private function _getFallbackLanguage(): string
    {
        // See if we have the control panel translated in one of the user’s browsers preferred language(s)
        if ($this instanceof WebApplication) {
            $languages = \CraftCms\Cms\Support\Facades\I18N::getAppLocaleIds();

            return $this->getRequest()->getPreferredLanguage($languages->all());
        }

        // Default to the source language.
        return $this->sourceLanguage;
    }

    /**
     * Register event listeners for config changes.
     */
    private function _registerConfigListeners(): void
    {
        // Prune deleted sites from site settings
        Event::on(Sites::class, Sites::EVENT_AFTER_DELETE_SITE, function (DeleteSiteEvent $event) {
            if (! app(ProjectConfig::class)->isApplyingExternalChanges) {
                $this->getCategories()->pruneDeletedSite($event);
            }
        });
    }
}
