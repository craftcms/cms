<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use craft\console\Application as ConsoleApplication;
use craft\console\Request as ConsoleRequest;
use craft\db\Connection;
use craft\db\MigrationManager;
use craft\db\mysql\Schema;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Address;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\errors\DbConnectException;
use craft\errors\SiteNotFoundException;
use craft\errors\WrongEditionException;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\DeleteSiteEvent;
use craft\events\EditionChangeEvent;
use craft\fieldlayoutelements\addresses\AddressField;
use craft\fieldlayoutelements\addresses\CountryCodeField;
use craft\fieldlayoutelements\addresses\LabelField;
use craft\fieldlayoutelements\addresses\LatLongField;
use craft\fieldlayoutelements\addresses\OrganizationField;
use craft\fieldlayoutelements\addresses\OrganizationTaxIdField;
use craft\fieldlayoutelements\assets\AltField;
use craft\fieldlayoutelements\assets\AssetTitleField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fieldlayoutelements\FullNameField;
use craft\fieldlayoutelements\TitleField;
use craft\fieldlayoutelements\users\AffiliatedSiteField;
use craft\fieldlayoutelements\users\EmailField;
use craft\fieldlayoutelements\users\FullNameField as UserFullNameField;
use craft\fieldlayoutelements\users\PhotoField;
use craft\fieldlayoutelements\users\UsernameField;
use craft\helpers\App;
use craft\helpers\Db;
use craft\helpers\Session;
use craft\i18n\Formatter;
use craft\i18n\I18N;
use craft\i18n\Locale;
use craft\log\Dispatcher;
use craft\mail\Mailer;
use craft\markdown\GithubMarkdown;
use craft\markdown\Markdown;
use craft\markdown\MarkdownExtra;
use craft\markdown\PreEncodedMarkdown;
use craft\models\FieldLayout;
use craft\models\Info;
use craft\queue\QueueInterface;
use craft\services\Addresses;
use craft\services\Announcements;
use craft\services\Api;
use craft\services\AssetIndexer;
use craft\services\Assets;
use craft\services\Auth;
use craft\services\Categories;
use craft\services\Composer;
use craft\services\Conditions;
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
use craft\services\PluginStore;
use craft\services\ProjectConfig;
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
use craft\services\Updates;
use craft\services\UserGroups;
use craft\services\UserPermissions;
use craft\services\Users;
use craft\services\Utilities;
use craft\services\Volumes;
use craft\services\Webpack;
use craft\web\Application as WebApplication;
use craft\web\AssetManager;
use craft\web\Request as WebRequest;
use craft\web\UrlManager;
use craft\web\User as UserSession;
use craft\web\View;
use Illuminate\Support\Collection;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\AbstractDumper;
use Symfony\Component\VarDumper\VarDumper;
use Yii;
use yii\base\Application;
use yii\base\ErrorHandler;
use yii\base\Event;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\caching\Cache;
use yii\db\ColumnSchemaBuilder;
use yii\db\Exception as DbException;
use yii\db\Expression;
use yii\helpers\Markdown as MarkdownHelper;
use yii\mutex\Mutex;
use yii\queue\Queue;
use yii\web\ServerErrorHttpException;

/**
 * ApplicationTrait
 *
 * @property bool $isInstalled Whether Craft is installed
 * @property-read Addresses $addresses The addresses service
 * @property-read Announcements $announcements The announcements service
 * @property-read Api $api The API service
 * @property-read AssetIndexer $assetIndexer The asset indexer service
 * @property-read AssetManager $assetManager The asset manager component
 * @property-read Assets $assets The assets service
 * @property-read Auth $auth The user authentication service
 * @property-read Categories $categories The categories service
 * @property-read Composer $composer The Composer service
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
 * @property-read Images $images The images service
 * @property-read ImageTransforms $imageTransforms The image transforms service
 * @property-read Locale $formattingLocale The Locale object that should be used to define the formatter
 * @property-read Locale $locale The Locale object for the target language
 * @property-read Mailer $mailer The mailer component
 * @property-read MigrationManager $contentMigrator The content migration manager
 * @property-read MigrationManager $migrator The application’s migration manager
 * @property-read Mutex $mutex The application’s mutex service
 * @property-read Path $path The path service
 * @property-read PluginStore $pluginStore The plugin store service
 * @property-read Plugins $plugins The plugins service
 * @property-read ProjectConfig $projectConfig The project config service
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
 * @property-read Updates $updates The updates service
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
 * @method AssetManager getAssetManager() Returns the asset manager component.
 * @method Connection getDb() Returns the database connection component.
 * @method Dispatcher getLog() Returns the log dispatcher component.
 * @method Formatter getFormatter() Returns the formatter component.
 * @method I18N getI18n() Returns the internationalization (i18n) component.
 * @method Security getSecurity() Returns the security component.
 * @method UrlManager getUrlManager() Returns the URL manager for this application.
 * @method View getView() Returns the view component.
 * @mixin WebApplication
 * @mixin ConsoleApplication
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
trait ApplicationTrait
{
    /**
     * @var string Craft’s schema version number.
     */
    public string $schemaVersion;

    /**
     * @var string The minimum Craft build number required to update to this build.
     */
    public string $minVersionRequired;

    /**
     * @var string|null The environment ID Craft is currently running in.
     */
    public ?string $env = null;

    /**
     * @var CmsEdition The installed Craft CMS edition.
     * @since 5.0.0
     */
    public CmsEdition $edition;

    /**
     * @var string The base Craftnet API URL to use.
     * @since 3.3.16
     * @internal
     */
    public string $baseApiUrl = 'https://api.craftcms.com/v1/';

    /**
     * @var string[]|null Query params that should be appended to Craftnet API requests.
     * @since 3.3.16
     * @internal
     */
    public ?array $apiParams = null;

    /**
     * @var bool|null
     */
    private ?bool $_isInstalled = null;

    /**
     * @var bool Whether the application is fully initialized yet
     * @see getIsInitialized()
     */
    private bool $_isInitialized = false;

    /**
     * @var bool
     * @see getIsMultiSite()
     */
    private bool $_isMultiSite;

    /**
     * @var bool
     * @see getIsMultiSite()
     */
    private bool $_isMultiSiteWithTrashed;

    /**
     * @var Info|null
     */
    private ?Info $_info = null;

    /**
     * @var bool
     */
    private bool $_gettingLanguage = false;

    /**
     * @var bool Whether we’re listening for the request end, to update the application info
     * @see saveInfoAfterRequest()
     */
    private bool $_waitingToSaveInfo = false;

    /**
     * @var callable[]
     * @see onAfterRequest()
     */
    private array $afterRequestCallbacks = [];

    /**
     * Returns the application ID combined with the environment name.
     *
     * @return string
     * @since 5.4.0
     * @see id
     * @see env
     */
    public function getEnvId(): string
    {
        return $this->env ? sprintf('%s--%s', $this->id, $this->env) : $this->id;
    }

    /**
     * @inheritdoc
     */
    public function setVendorPath($path): void
    {
        parent::setVendorPath($path);

        // Override the @bower and @npm aliases if using asset-packagist.org
        // todo: remove this whenever Yii is updated with support for asset-packagist.org
        $altBowerPath = $this->getVendorPath() . DIRECTORY_SEPARATOR . 'bower-asset';
        $altNpmPath = $this->getVendorPath() . DIRECTORY_SEPARATOR . 'npm-asset';
        if (is_dir($altBowerPath)) {
            Craft::setAlias('@bower', $altBowerPath);
        }
        if (is_dir($altNpmPath)) {
            Craft::setAlias('@npm', $altNpmPath);
        }

        // Override where Yii should find its asset deps
        $assetsPath = Craft::getAlias('@app/web/assets');
        Craft::setAlias('@bower/jquery/dist', $assetsPath . '/jquery/dist');
        Craft::setAlias('@bower/inputmask/dist', $assetsPath . '/inputmask/dist');
        Craft::setAlias('@bower/punycode', $assetsPath . '/punycode/dist');
        Craft::setAlias('@bower/yii2-pjax', $assetsPath . '/yii2pjax/dist');
    }

    /**
     * Sets the target application language.
     *
     * @param bool|null $useUserLanguage Whether the user’s preferred language should be used.
     * If null, the user’s preferred language will be used if this is a control panel request or a console request.
     */
    public function updateTargetLanguage(?bool $useUserLanguage = null): void
    {
        // Defend against an infinite updateTargetLanguage() loop
        if ($this->_gettingLanguage === true) {
            // We tried to get the language, but something went wrong. Use fallback to prevent infinite loop.
            $fallbackLanguage = $this->_getFallbackLanguage();
            $this->_gettingLanguage = false;
            $this->language = $fallbackLanguage;
            return;
        }

        $this->_gettingLanguage = true;

        if ($useUserLanguage === null) {
            $useUserLanguage = $this->getRequest()->getIsCpRequest();
        }

        $this->language = $this->getTargetLanguage($useUserLanguage);
        setlocale(
            LC_COLLATE,
            str_replace('-', '_', $this->language), // target language
            'C.UTF-8',  // libc >= 2.13
            'C.utf8' // different spelling
        );
        $this->_gettingLanguage = false;
    }

    /**
     * Returns the target app language.
     *
     * @param bool $useUserLanguage Whether the user’s preferred language should be used.
     * @return string
     */
    public function getTargetLanguage(bool $useUserLanguage = true): string
    {
        // Use the fallback language for console requests, or if Craft isn't installed or is updating
        if (
            $this instanceof ConsoleApplication ||
            !$this->getIsInstalled() ||
            $this->getUpdates()->getIsCraftUpdatePending()
        ) {
            return $this->_getFallbackLanguage();
        }

        if ($useUserLanguage) {
            // If the user is logged in *and* has a primary language set, use that
            // (don't actually try to fetch the user, as plugins haven't been loaded yet)
            /** @var UserSession $user */
            $user = $this->getUser();
            $id = Session::get($user->idParam);
            if (
                $id &&
                ($language = $this->getUsers()->getUserPreference($id, 'language')) !== null &&
                Craft::$app->getI18n()->validateAppLocaleId($language)
            ) {
                return $language;
            }

            // Fall back on the default control panel language, if there is one, otherwise the browser language
            return Craft::$app->getConfig()->getGeneral()->defaultCpLanguage ?? $this->_getFallbackLanguage();
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->getSites()->getCurrentSite()->language;
    }

    /**
     * Returns whether Craft is installed.
     *
     * @param bool $strict Whether to ignore the cached value and explicitly check from the default schema.
     * @return bool
     */
    public function getIsInstalled(bool $strict = false): bool
    {
        if ($strict) {
            $this->_isInstalled = null;
            $this->_info = null;
        } elseif (isset($this->_isInstalled)) {
            return $this->_isInstalled;
        }

        if (!$this->getIsDbConnectionValid()) {
            return $this->_isInstalled = false;
        }

        try {
            if ($strict) {
                $db = Craft::$app->getDb();
                if ($db->getIsPgsql()) {
                    // Look for the `info` row, explicitly in the default schema.
                    return $this->_isInstalled = (new Query())
                        ->from([sprintf('%s.%s', $db->getSchema()->defaultSchema, Table::INFO)])
                        ->where(['id' => 1])
                        ->exists();
                }
            }

            $info = $this->getInfo(true);
            return $this->_isInstalled = !empty($info->id);
        } catch (DbException|ServerErrorHttpException $e) {
            // yii2-redis awkwardly throws yii\db\Exception's rather than their own exception class.
            if ($e instanceof DbException && str_contains($e->getMessage(), 'Redis')) {
                throw $e;
            }

            // Allow console requests to bypass error
            if ($this instanceof WebApplication) {
                Craft::error('There was a problem fetching the info row: ' . $e->getMessage(), __METHOD__);
                /** @var ErrorHandler $errorHandler */
                $errorHandler = $this->getErrorHandler();
                $errorHandler->logException($e);
            }
            return $this->_isInstalled = false;
        }
    }

    /**
     * Sets Craft's record of whether it's installed
     *
     * @param bool|null $value
     */
    public function setIsInstalled(?bool $value = true): void
    {
        $this->_isInstalled = $value;
    }

    /**
     * Returns the installed schema version.
     *
     * @return string
     * @since 3.2.0
     * @deprecated in 4.0.0
     */
    public function getInstalledSchemaVersion(): string
    {
        return $this->getInfo()->schemaVersion ?: $this->schemaVersion;
    }

    /**
     * Returns whether Craft has been fully initialized.
     *
     * @return bool
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
     * @param callable $callback
     * @since 4.3.5
     */
    public function onInit(callable $callback): void
    {
        if ($this->_isInitialized) {
            $callback();
        } else {
            $this->on(WebApplication::EVENT_INIT, function() use ($callback) {
                $callback();
            });
        }
    }

    /**
     * Invokes a callback function at the end of the request.
     *
     * If the request is already ending, the callback will be invoked immediately.
     *
     * @param callable $callback
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
     * @inheritdoc
     */
    public function trigger($name, Event $event = null)
    {
        // call the onAfterRequest() callbacks directly
        if ($name === self::EVENT_AFTER_REQUEST && !empty($this->afterRequestCallbacks)) {
            $event ??= new Event();
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
     * @param bool $refresh Whether to ignore the cached result and check again
     * @param bool $withTrashed Whether to factor in soft-deleted sites
     * @return bool
     */
    public function getIsMultiSite(bool $refresh = false, bool $withTrashed = false): bool
    {
        if ($withTrashed) {
            if (!$refresh && isset($this->_isMultiSiteWithTrashed)) {
                return $this->_isMultiSiteWithTrashed;
            }
            // This is a ridiculous microoptimization for the `sites` table, but all we need to know is whether there is
            // 1 or "more than 1" rows, and this is the fastest way to do it.
            // (https://stackoverflow.com/a/14916838/1688568)
            return $this->_isMultiSiteWithTrashed = (new Query())
                    ->from([
                        'x' => (new Query())
                            ->select([new Expression('1')])
                            ->from([Table::SITES])
                            ->limit(2),
                    ])
                    ->count() != 1;
        }

        if (!$refresh && isset($this->_isMultiSite)) {
            return $this->_isMultiSite;
        }
        return $this->_isMultiSite = count($this->getSites()->getAllSites(true)) > 1;
    }

    /**
     * Returns the installed Craft CMS edition’s ID.
     *
     * @return int
     * @deprecated in 5.0.0. [[$edition]] should be used instead.
     */
    public function getEdition(): int
    {
        return $this->edition->value;
    }

    /**
     * Returns the name of the Craft edition.
     *
     * @return string
     * @deprecated in 5.0.0. [[$edition]] should be used instead.
     */
    public function getEditionName(): string
    {
        return $this->edition->name;
    }

    /**
     * Returns the handle of the Craft edition.
     *
     * @return string
     * @since 4.4.0
     * @deprecated in 5.0.0. [[$edition]] should be used instead.
     */
    public function getEditionHandle(): string
    {
        return $this->edition->handle();
    }

    /**
     * Returns the edition Craft is actually licensed to run in.
     *
     * @return CmsEdition|null
     */
    public function getLicensedEdition(): ?CmsEdition
    {
        $licenseInfo = $this->getCache()->get(App::CACHE_KEY_LICENSE_INFO) ?: [];

        if (!isset($licenseInfo['craft']['edition'])) {
            return null;
        }

        return CmsEdition::fromHandle($licenseInfo['craft']['edition']);
    }

    /**
     * Returns the name of the edition Craft is actually licensed to run in.
     *
     * @return string|null
     * @deprecated in 5.0.0. [[getLicensedEdition()]] should be used instead.
     */
    public function getLicensedEditionName(): ?string
    {
        return $this->getLicensedEdition()?->name;
    }

    /**
     * Returns whether Craft is running with the wrong edition.
     *
     * @return bool
     */
    public function getHasWrongEdition(): bool
    {
        $licensedEdition = $this->getLicensedEdition();
        return (
            $licensedEdition !== null &&
            $licensedEdition !== $this->edition &&
            !$this->getCanTestEditions()
        );
    }

    /**
     * Sets the installed Craft CMS edition.
     *
     * @param CmsEdition|int $edition The edition to set.
     * @return bool
     */
    public function setEdition(CmsEdition|int $edition): bool
    {
        if (is_int($edition)) {
            $edition = CmsEdition::from($edition);
        }

        $oldEdition = $this->edition ?? CmsEdition::Solo;
        $this->getProjectConfig()->set('system.edition', $edition->handle(), 'Craft CMS edition change');
        $this->edition = $edition;

        // Fire an 'afterEditionChange' event
        /** @var WebRequest|ConsoleRequest $request */
        $request = $this->getRequest();
        if (!$request->getIsConsoleRequest() && $this->hasEventHandlers(WebApplication::EVENT_AFTER_EDITION_CHANGE)) {
            $this->trigger(WebApplication::EVENT_AFTER_EDITION_CHANGE, new EditionChangeEvent([
                'oldEdition' => $oldEdition->value,
                'newEdition' => $edition->value,
            ]));
        }

        return true;
    }

    /**
     * Requires that Craft is running an equal or better edition than what's passed in
     *
     * @param CmsEdition|int $edition The Craft edition to require.
     * @param bool $orBetter If true, makes $edition the minimum edition required.
     * @throws WrongEditionException if attempting to do something not allowed by the current Craft edition
     */
    public function requireEdition(CmsEdition|int $edition, bool $orBetter = true): void
    {
        if (is_int($edition)) {
            $edition = CmsEdition::from($edition);
        }

        if ($this->getIsInstalled() && !$this->getProjectConfig()->getIsApplyingExternalChanges()) {
            if (!match ($orBetter) {
                true => $this->edition->value >= $edition->value,
                false => $this->edition === $edition
            }) {
                throw new WrongEditionException("Craft $edition->name is required for this.");
            }
        }
    }

    /**
     * Returns whether Craft is eligible to be upgraded to a different edition.
     *
     * @return bool
     */
    public function getCanUpgradeEdition(): bool
    {
        // Only admin accounts can upgrade Craft
        if (
            $this->getUser()->getIsAdmin() &&
            Craft::$app->getConfig()->getGeneral()->allowAdminChanges
        ) {
            // Are they either *using* or *licensed to use* something < Craft Pro?
            $licensedEdition = $this->getLicensedEdition();

            return (
                ($this->edition->value < CmsEdition::Pro->value) ||
                ($licensedEdition !== null && $licensedEdition->value < CmsEdition::Pro->value)
            );
        }

        return false;
    }

    /**
     * Returns whether Craft is running on a domain that is eligible to test
     * unlicensed Craft and plugin editions/updates.
     *
     * @return bool
     * @internal
     */
    public function getCanTestEditions(): bool
    {
        if (App::env('CRAFT_NO_TRIALS')) {
            return false;
        }

        if (!$this instanceof WebApplication) {
            return false;
        }

        /** @var Cache $cache */
        $cache = $this->getCache();
        $cacheKey = sprintf('editionTestableDomain@%s', $this->getRequest()->getHostName());
        if (!$cache->exists($cacheKey)) {
            // err on the side of allowing it
            return true;
        }
        return (bool)$cache->get($cacheKey);
    }

    /**
     * Returns the system's UID.
     *
     * @return string|null
     */
    public function getSystemUid(): ?string
    {
        return $this->getInfo()->uid;
    }

    /**
     * Returns whether the system is currently live.
     *
     * @return bool
     * @since 3.1.0
     */
    public function getIsLive(): bool
    {
        if (is_bool($live = $this->getConfig()->getGeneral()->isSystemLive)) {
            return $live;
        }

        return App::parseBooleanEnv($this->getProjectConfig()->get('system.live')) ?? false;
    }

    /**
     * Returns whether someone is currently performing a system update.
     *
     * @return bool
     * @see enableMaintenanceMode()
     * @see disableMaintenanceMode()
     */
    public function getIsInMaintenanceMode(): bool
    {
        return $this->getInfo()->maintenance;
    }

    /**
     * Enables Maintenance Mode.
     *
     * @return bool
     * @see getIsInMaintenanceMode()
     * @see disableMaintenanceMode()
     */
    public function enableMaintenanceMode(): bool
    {
        return $this->_setMaintenanceMode(true);
    }

    /**
     * Disables Maintenance Mode.
     *
     * @return bool
     * @see getIsInMaintenanceMode()
     * @see disableMaintenanceMode()
     */
    public function disableMaintenanceMode(): bool
    {
        return $this->_setMaintenanceMode(false);
    }

    /**
     * Returns the info model, or just a particular attribute.
     *
     * @param bool $throwException Whether an exception should be thrown if the `info` table doesn't exist
     * @return Info
     * @throws DbException if the `info` table doesn’t exist yet and `$throwException` is `true`
     * @throws ServerErrorHttpException if the info table is missing its row
     */
    public function getInfo(bool $throwException = false): Info
    {
        if (isset($this->_info)) {
            return $this->_info;
        }

        try {
            $row = (new Query())
                ->from([Table::INFO])
                ->where(['id' => 1])
                ->one();
        } catch (DbException|DbConnectException $e) {
            if ($throwException) {
                throw $e;
            }
            return $this->_info = new Info();
        }

        if (!$row) {
            $tableName = $this->getDb()->getSchema()->getRawTableName(Table::INFO);
            throw new ServerErrorHttpException("The $tableName table is missing its row");
        }

        return $this->_info = new Info($row);
    }

    /**
     * Updates the info row at the end of the request.
     *
     * @since 3.1.33
     */
    public function saveInfoAfterRequest(): void
    {
        if (!$this->_waitingToSaveInfo) {
            $this->_waitingToSaveInfo = true;

            $this->onAfterRequest(function() {
                $this->saveInfoAfterRequestHandler();
            });
        }
    }

    /**
     * @throws Exception
     * @throws ServerErrorHttpException
     * @since 3.1.33
     * @internal
     */
    public function saveInfoAfterRequestHandler(): void
    {
        $info = $this->getInfo();
        if (!$this->saveInfo($info)) {
            throw new Exception("Unable to save new application info: " . implode(', ', $info->getErrorSummary(true)));
        }
        $this->_waitingToSaveInfo = false;
    }

    /**
     * Updates the info row.
     *
     * @param Info $info
     * @param string[]|null $attributeNames The attributes to save
     * @return bool
     */
    public function saveInfo(Info $info, ?array $attributeNames = null): bool
    {
        if ($attributeNames === null) {
            $attributeNames = ['version', 'schemaVersion', 'maintenance', 'configVersion', 'fieldVersion'];
        }

        if (!$info->validate($attributeNames)) {
            return false;
        }

        $attributes = $info->getAttributes($attributeNames);

        $infoRowExists = (new Query())
            ->from([Table::INFO])
            ->where(['id' => 1])
            ->exists();

        if ($infoRowExists) {
            Db::update(Table::INFO, $attributes, [
                'id' => 1,
            ]);
        } else {
            Db::insert(Table::INFO, $attributes + [
                    'id' => 1,
                ]);
        }

        $this->setIsInstalled();

        // Use this as the new cached Info
        $this->_info = $info;

        return true;
    }

    /**
     * Returns the system name.
     *
     * @return string
     * @since 3.1.4
     */
    public function getSystemName(): string
    {
        $name = App::parseEnv(Craft::$app->getProjectConfig()->get('system.name'));
        if ($name !== null) {
            return $name;
        }

        try {
            $name = $this->getSites()->getPrimarySite()->getName();
        } catch (SiteNotFoundException) {
            $name = null;
        }

        return $name ?: 'Craft';
    }

    /**
     * Returns the Yii framework version.
     *
     * @return string
     */
    public function getYiiVersion(): string
    {
        return Yii::getVersion();
    }

    /**
     * Returns whether the DB connection settings are valid.
     *
     * @return bool
     * @internal Don't even think of moving this check into Connection->init().
     */
    public function getIsDbConnectionValid(): bool
    {
        try {
            $this->getDb()->open();
        } catch (DbConnectException|InvalidConfigException $e) {

            // Only log for web requests
            if ($this instanceof WebApplication) {
                Craft::error('There was a problem connecting to the database: ' . $e->getMessage(), __METHOD__);
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
     * @since 4.0.0
     */
    public function getAddresses(): Addresses
    {
        return $this->get('addresses');
    }

    /**
     * Returns the announcements service.
     *
     * @return Announcements The announcements service
     * @since 3.7.0
     */
    public function getAnnouncements(): Announcements
    {
        return $this->get('announcements');
    }

    /**
     * Returns the API service.
     *
     * @return Api The API service
     */
    public function getApi(): Api
    {
        return $this->get('api');
    }

    /**
     * Returns the assets service.
     *
     * @return Assets The assets service
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
     * @since 5.0.0
     */
    public function getAuth(): Auth
    {
        return $this->get('auth');
    }

    /**
     * Returns the image transforms service.
     *
     * @return ImageTransforms The asset transforms service
     */
    public function getImageTransforms(): ImageTransforms
    {
        return $this->get('imageTransforms');
    }

    /**
     * Returns the categories service.
     *
     * @return Categories The categories service
     */
    public function getCategories(): Categories
    {
        return $this->get('categories');
    }

    /**
     * Returns the Composer service.
     *
     * @return Composer The Composer service
     */
    public function getComposer(): Composer
    {
        return $this->get('composer');
    }

    /**
     * Returns the conditions service.
     *
     * @return Conditions The conditions service
     * @since 4.0.0
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
     * Returns the content migration manager.
     *
     * @return MigrationManager The content migration manager
     */
    public function getContentMigrator(): MigrationManager
    {
        return $this->get('contentMigrator');
    }

    /**
     * Returns the dashboard service.
     *
     * @return Dashboard The dashboard service
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
     * @return Connection
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
     */
    public function getDeprecator(): Deprecator
    {
        return $this->get('deprecator');
    }

    /**
     * Returns the drafts service.
     *
     * @return Drafts The drafts service
     * @since 3.2.0
     */
    public function getDrafts(): Drafts
    {
        return $this->get('drafts');
    }

    /**
     * Returns the variable dumper.
     *
     * @return AbstractDumper
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
     */
    public function getSystemMessages(): SystemMessages
    {
        return $this->get('systemMessages');
    }

    /**
     * Returns the entries service.
     *
     * @return Entries The entries service
     */
    public function getEntries(): Entries
    {
        return $this->get('entries');
    }

    /**
     * Returns the fields service.
     *
     * @return Fields The fields service
     */
    public function getFields(): Fields
    {
        return $this->get('fields');
    }

    /**
     * Returns the filesystems service.
     *
     * @return Fs The filesystems service
     * @since 4.0.0
     */
    public function getFs(): Fs
    {
        return $this->get('fs');
    }

    /**
     * Returns the locale that should be used to define the formatter.
     *
     * @return Locale
     * @since 3.6.0
     */
    public function getFormattingLocale(): Locale
    {
        return $this->get('formattingLocale');
    }

    /**
     * Returns the garbage collection service.
     *
     * @return Gc The garbage collection service
     */
    public function getGc(): Gc
    {
        return $this->get('gc');
    }

    /**
     * Returns the globals service.
     *
     * @return Globals The globals service
     */
    public function getGlobals(): Globals
    {
        return $this->get('globals');
    }

    /**
     * Returns the GraphQL service.
     *
     * @return Gql The GraphQL service
     * @since 3.3.0
     */
    public function getGql(): Gql
    {
        return $this->get('gql');
    }

    /**
     * Returns the images service.
     *
     * @return Images The images service
     */
    public function getImages(): Images
    {
        return $this->get('images');
    }

    /**
     * Returns a Locale object for the target language.
     *
     * @return Locale The Locale object for the target language
     */
    public function getLocale(): Locale
    {
        return $this->get('locale');
    }

    /**
     * Returns the current mailer.
     *
     * @return Mailer The mailer component
     */
    public function getMailer(): Mailer
    {
        return $this->get('mailer');
    }

    /**
     * Returns the application’s migration manager.
     *
     * @return MigrationManager The application’s migration manager
     */
    public function getMigrator(): MigrationManager
    {
        return $this->get('migrator');
    }

    /**
     * Returns the application’s mutex service.
     *
     * @return Mutex The application’s mutex service
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
     */
    public function getPlugins(): Plugins
    {
        return $this->get('plugins');
    }

    /**
     * Returns the plugin store service.
     *
     * @return PluginStore The plugin store service
     */
    public function getPluginStore(): PluginStore
    {
        return $this->get('pluginStore');
    }

    /**
     * Returns the system config service.
     *
     * @return ProjectConfig The system config service
     */
    public function getProjectConfig(): ProjectConfig
    {
        return $this->get('projectConfig');
    }

    /**
     * Returns the queue service.
     *
     * @return Queue The queue service
     */
    public function getQueue(): Queue
    {
        return $this->get('queue');
    }

    /**
     * Returns the relations service.
     *
     * @return Relations The relations service
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
     * @since 3.2.0
     */
    public function getRevisions(): Revisions
    {
        return $this->get('revisions');
    }

    /**
     * Returns the routes service.
     *
     * @return Routes The routes service
     */
    public function getRoutes(): Routes
    {
        return $this->get('routes');
    }

    /**
     * Returns the search service.
     *
     * @return Search The search service
     */
    public function getSearch(): Search
    {
        return $this->get('search');
    }

    /**
     * Returns the sites service.
     *
     * @return Sites The sites service
     */
    public function getSites(): Sites
    {
        return $this->get('sites');
    }

    /**
     * Returns the SSO service.
     *
     * @return Sso The SSO service
     * @since 5.3.0
     */
    public function getSso(): Sso
    {
        return $this->get('sso');
    }

    /**
     * Returns the structures service.
     *
     * @return Structures The structures service
     */
    public function getStructures(): Structures
    {
        return $this->get('structures');
    }

    /**
     * Returns the tags service.
     *
     * @return Tags The tags service
     */
    public function getTags(): Tags
    {
        return $this->get('tags');
    }

    /**
     * Returns the template cache service.
     *
     * @return TemplateCaches The template caches service
     */
    public function getTemplateCaches(): TemplateCaches
    {
        return $this->get('templateCaches');
    }

    /**
     * Returns the tokens service.
     *
     * @return Tokens The tokens service
     */
    public function getTokens(): Tokens
    {
        return $this->get('tokens');
    }

    /**
     * Returns the updates service.
     *
     * @return Updates The updates service
     */
    public function getUpdates(): Updates
    {
        return $this->get('updates');
    }

    /**
     * Returns the user groups service.
     *
     * @return UserGroups The user groups service
     */
    public function getUserGroups(): UserGroups
    {
        return $this->get('userGroups');
    }

    /**
     * Returns the user permissions service.
     *
     * @return UserPermissions The user permissions service
     */
    public function getUserPermissions(): UserPermissions
    {
        return $this->get('userPermissions');
    }

    /**
     * Returns the users service.
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

        // Register Collection::set() as an alias of put() - with support for bulk-setting values
        Collection::macro('set', function(mixed $values) {
            assert($this instanceof Collection);
            if (is_array($values)) {
                foreach ($values as $key => $value) {
                    $this->put($key, $value);
                }
            } else {
                $this->put(...func_get_args());
            }
            return $this;
        });

        // Register Collection::one() as an alias of first(), for consistency with yii\db\Query.
        Collection::macro('one', function() {
            assert($this instanceof Collection);
            return $this->first(...func_get_args());
        });

        // Set the Craft edition
        $this->_setCraftEdition();

        // Load the request before anything else, so everything else can safely check Craft::$app->has('request', true)
        // to avoid possible recursive fatal errors in the request initialization
        $this->getRequest();
        $this->getLog();

        // Set the timezone
        $this->_setTimeZone();

        // Set the language
        $this->updateTargetLanguage();

        // Register the variable dumper
        VarDumper::setHandler(function($var) {
            $cloner = new VarCloner();
            $cloner->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);
            $this->getDumper()->dump($cloner->cloneVar($var));
        });

        // Use our own Markdown parser classes
        $flavors = [
            'original' => Markdown::class,
            'pre-encoded' => PreEncodedMarkdown::class,
            'gfm' => GithubMarkdown::class,
            'gfm-comment' => GithubMarkdown::class,
            'extra' => MarkdownExtra::class,
        ];

        foreach ($flavors as $flavor => $class) {
            if (!isset(MarkdownHelper::$flavors[$flavor]) || !is_object(MarkdownHelper::$flavors[$flavor])) {
                MarkdownHelper::$flavors[$flavor]['class'] = $class;
            }
        }
    }

    /**
     * Initializes things that should happen after the main Application::init()
     */
    private function _postInit(): void
    {
        // Register field layout listeners
        $this->_registerFieldLayoutListener();

        // Register all the listeners for config items
        $this->_registerConfigListeners();

        // Load the plugins
        $this->getPlugins()->loadPlugins();

        $this->_isInitialized = true;

        // Fire an 'init' event
        if ($this->hasEventHandlers(WebApplication::EVENT_INIT)) {
            $this->trigger(WebApplication::EVENT_INIT);
        }

        if ($this->getIsInstalled() && !$this->getUpdates()->getIsCraftUpdatePending()) {
            // Possibly run garbage collection
            $this->getGc()->run();
        }
    }

    /**
     * Sets the system timezone.
     */
    private function _setTimeZone(): void
    {
        $timeZone = $this->getConfig()->getGeneral()->timezone ?? $this->getProjectConfig()->get('system.timeZone');

        if ($timeZone) {
            $this->setTimeZone(App::parseEnv($timeZone));
        }
    }

    /**
     * Enables or disables Maintenance Mode
     *
     * @param bool $value
     * @return bool
     */
    private function _setMaintenanceMode(bool $value): bool
    {
        $info = $this->getInfo();
        if ($info->maintenance === $value) {
            return true;
        }
        $info->maintenance = $value;
        return $this->saveInfo($info);
    }

    /**
     * Tries to find a language match with the browser’s preferred language(s).
     *
     * If not uses the app’s sourceLanguage.
     *
     * @return string
     */
    private function _getFallbackLanguage(): string
    {
        // See if we have the control panel translated in one of the user’s browsers preferred language(s)
        if ($this instanceof WebApplication) {
            $languages = $this->getI18n()->getAppLocaleIds();
            return $this->getRequest()->getPreferredLanguage($languages);
        }

        // Default to the source language.
        return $this->sourceLanguage;
    }

    /**
     * Register event listeners for field layouts.
     */
    private function _registerFieldLayoutListener(): void
    {
        Event::on(FieldLayout::class, FieldLayout::EVENT_DEFINE_NATIVE_FIELDS, function(DefineFieldLayoutFieldsEvent $event) {
            /** @var FieldLayout $fieldLayout */
            $fieldLayout = $event->sender;

            switch ($fieldLayout->type) {
                case Category::class:
                case Tag::class:
                    $event->fields[] = TitleField::class;
                    break;
                case Address::class:
                    $event->fields[] = LabelField::class;
                    $event->fields[] = OrganizationField::class;
                    $event->fields[] = OrganizationTaxIdField::class;
                    $event->fields[] = FullNameField::class;
                    $event->fields[] = CountryCodeField::class;
                    $event->fields[] = AddressField::class;
                    $event->fields[] = LatLongField::class;
                    break;
                case Asset::class:
                    $event->fields[] = AssetTitleField::class;
                    $event->fields[] = AltField::class;
                    break;
                case Entry::class:
                    $event->fields[] = EntryTitleField::class;
                    break;
                case User::class:
                    if (!$this->getConfig()->getGeneral()->useEmailAsUsername) {
                        $event->fields[] = UsernameField::class;
                    }
                    $event->fields[] = UserFullNameField::class;
                    $event->fields[] = PhotoField::class;
                    $event->fields[] = EmailField::class;
                    if (Craft::$app->getIsMultiSite()) {
                        $event->fields[] = AffiliatedSiteField::class;
                    }
                    break;
            }
        });
    }

    /**
     * Register event listeners for config changes.
     */
    private function _registerConfigListeners(): void
    {
        $this->getProjectConfig()
            // Address field layout
            ->onAdd(ProjectConfig::PATH_ADDRESS_FIELD_LAYOUTS, $this->_proxy('addresses', 'handleChangedAddressFieldLayout'))
            ->onUpdate(ProjectConfig::PATH_ADDRESS_FIELD_LAYOUTS, $this->_proxy('addresses', 'handleChangedAddressFieldLayout'))
            ->onRemove(ProjectConfig::PATH_ADDRESS_FIELD_LAYOUTS, $this->_proxy('addresses', 'handleChangedAddressFieldLayout'))
            // Fields
            ->onAdd(ProjectConfig::PATH_FIELDS . '.{uid}', $this->_proxy('fields', 'handleChangedField'))
            ->onUpdate(ProjectConfig::PATH_FIELDS . '.{uid}', $this->_proxy('fields', 'handleChangedField'))
            ->onRemove(ProjectConfig::PATH_FIELDS . '.{uid}', $this->_proxy('fields', 'handleDeletedField'))
            // Volumes
            ->onAdd(ProjectConfig::PATH_VOLUMES . '.{uid}', $this->_proxy('volumes', 'handleChangedVolume'))
            ->onUpdate(ProjectConfig::PATH_VOLUMES . '.{uid}', $this->_proxy('volumes', 'handleChangedVolume'))
            ->onRemove(ProjectConfig::PATH_VOLUMES . '.{uid}', $this->_proxy('volumes', 'handleDeletedVolume'))
            // Transforms
            ->onAdd(ProjectConfig::PATH_IMAGE_TRANSFORMS . '.{uid}', $this->_proxy('imageTransforms', 'handleChangedTransform'))
            ->onUpdate(ProjectConfig::PATH_IMAGE_TRANSFORMS . '.{uid}', $this->_proxy('imageTransforms', 'handleChangedTransform'))
            ->onRemove(ProjectConfig::PATH_IMAGE_TRANSFORMS . '.{uid}', $this->_proxy('imageTransforms', 'handleDeletedTransform'))
            // Site groups
            ->onAdd(ProjectConfig::PATH_SITE_GROUPS . '.{uid}', $this->_proxy('sites', 'handleChangedGroup'))
            ->onUpdate(ProjectConfig::PATH_SITE_GROUPS . '.{uid}', $this->_proxy('sites', 'handleChangedGroup'))
            ->onRemove(ProjectConfig::PATH_SITE_GROUPS . '.{uid}', $this->_proxy('sites', 'handleDeletedGroup'))
            // Sites
            ->onAdd(ProjectConfig::PATH_SITES . '.{uid}', $this->_proxy('sites', 'handleChangedSite'))
            ->onUpdate(ProjectConfig::PATH_SITES . '.{uid}', $this->_proxy('sites', 'handleChangedSite'))
            ->onRemove(ProjectConfig::PATH_SITES . '.{uid}', $this->_proxy('sites', 'handleDeletedSite'))
            // Tags
            ->onAdd(ProjectConfig::PATH_TAG_GROUPS . '.{uid}', $this->_proxy('tags', 'handleChangedTagGroup'))
            ->onUpdate(ProjectConfig::PATH_TAG_GROUPS . '.{uid}', $this->_proxy('tags', 'handleChangedTagGroup'))
            ->onRemove(ProjectConfig::PATH_TAG_GROUPS . '.{uid}', $this->_proxy('tags', 'handleDeletedTagGroup'))
            // Categories
            ->onAdd(ProjectConfig::PATH_CATEGORY_GROUPS . '.{uid}', $this->_proxy('categories', 'handleChangedCategoryGroup'))
            ->onUpdate(ProjectConfig::PATH_CATEGORY_GROUPS . '.{uid}', $this->_proxy('categories', 'handleChangedCategoryGroup'))
            ->onRemove(ProjectConfig::PATH_CATEGORY_GROUPS . '.{uid}', $this->_proxy('categories', 'handleDeletedCategoryGroup'))
            // User group permissions
            ->onAdd(ProjectConfig::PATH_USER_GROUPS . '.{uid}.permissions', $this->_proxy('userPermissions', 'handleChangedGroupPermissions'))
            ->onUpdate(ProjectConfig::PATH_USER_GROUPS . '.{uid}.permissions', $this->_proxy('userPermissions', 'handleChangedGroupPermissions'))
            ->onRemove(ProjectConfig::PATH_USER_GROUPS . '.{uid}.permissions', $this->_proxy('userPermissions', 'handleChangedGroupPermissions'))
            // User groups
            ->onAdd(ProjectConfig::PATH_USER_GROUPS . '.{uid}', $this->_proxy('userGroups', 'handleChangedUserGroup'))
            ->onUpdate(ProjectConfig::PATH_USER_GROUPS . '.{uid}', $this->_proxy('userGroups', 'handleChangedUserGroup'))
            ->onRemove(ProjectConfig::PATH_USER_GROUPS . '.{uid}', $this->_proxy('userGroups', 'handleDeletedUserGroup'))
            // User field layout
            ->onAdd(ProjectConfig::PATH_USER_FIELD_LAYOUTS, $this->_proxy('users', 'handleChangedUserFieldLayout'))
            ->onUpdate(ProjectConfig::PATH_USER_FIELD_LAYOUTS, $this->_proxy('users', 'handleChangedUserFieldLayout'))
            ->onRemove(ProjectConfig::PATH_USER_FIELD_LAYOUTS, $this->_proxy('users', 'handleChangedUserFieldLayout'))
            // Global sets
            ->onAdd(ProjectConfig::PATH_GLOBAL_SETS . '.{uid}', $this->_proxy('globals', 'handleChangedGlobalSet'))
            ->onUpdate(ProjectConfig::PATH_GLOBAL_SETS . '.{uid}', $this->_proxy('globals', 'handleChangedGlobalSet'))
            ->onRemove(ProjectConfig::PATH_GLOBAL_SETS . '.{uid}', $this->_proxy('globals', 'handleDeletedGlobalSet'))
            // Sections
            ->onAdd(ProjectConfig::PATH_SECTIONS . '.{uid}', $this->_proxy('entries', 'handleChangedSection'))
            ->onUpdate(ProjectConfig::PATH_SECTIONS . '.{uid}', $this->_proxy('entries', 'handleChangedSection'))
            ->onRemove(ProjectConfig::PATH_SECTIONS . '.{uid}', $this->_proxy('entries', 'handleDeletedSection'))
            // Entry types
            ->onAdd(ProjectConfig::PATH_ENTRY_TYPES . '.{uid}', $this->_proxy('entries', 'handleChangedEntryType'))
            ->onUpdate(ProjectConfig::PATH_ENTRY_TYPES . '.{uid}', $this->_proxy('entries', 'handleChangedEntryType'))
            ->onRemove(ProjectConfig::PATH_ENTRY_TYPES . '.{uid}', $this->_proxy('entries', 'handleDeletedEntryType'))
            // GraphQL schemas
            ->onAdd(ProjectConfig::PATH_GRAPHQL_SCHEMAS . '.{uid}', $this->_proxy('gql', 'handleChangedSchema'))
            ->onUpdate(ProjectConfig::PATH_GRAPHQL_SCHEMAS . '.{uid}', $this->_proxy('gql', 'handleChangedSchema'))
            ->onRemove(ProjectConfig::PATH_GRAPHQL_SCHEMAS . '.{uid}', $this->_proxy('gql', 'handleDeletedSchema'))
            // GraphQL public token
            ->onAdd(ProjectConfig::PATH_GRAPHQL_PUBLIC_TOKEN, $this->_proxy('gql', 'handleChangedPublicToken'))
            ->onUpdate(ProjectConfig::PATH_GRAPHQL_PUBLIC_TOKEN, $this->_proxy('gql', 'handleChangedPublicToken'));

        // Prune deleted sites from site settings
        Event::on(Sites::class, Sites::EVENT_AFTER_DELETE_SITE, function(DeleteSiteEvent $event) {
            if (!Craft::$app->getProjectConfig()->getIsApplyingExternalChanges()) {
                $this->getRoutes()->handleDeletedSite($event);
                $this->getCategories()->pruneDeletedSite($event);
                $this->getEntries()->pruneDeletedSite($event);
            }
        });
    }

    /**
     * Returns a proxy function for calling a component method, based on its ID.
     *
     * The component won’t be fetched until the method is called, avoiding unnecessary component instantiation, and ensuring the correct component
     * is called if it happens to get swapped out (e.g. for a test).
     *
     * @param string $id The component ID
     * @param string $method The method name
     * @return callable
     */
    private function _proxy(string $id, string $method): callable
    {
        return function() use ($id, $method) {
            return $this->get($id)->$method(...func_get_args());
        };
    }

    /**
     * Set Craft's edition
     *
     * @return void
     * @throws Exception
     */
    private function _setCraftEdition(): void
    {
        $edition = App::env('CRAFT_EDITION') ?? $this->getProjectConfig()->get('system.edition');
        $this->edition = $edition ? CmsEdition::fromHandle($edition) : CmsEdition::Solo;
    }

    /**
     * Ensure that edition is set.
     *
     * @return void
     * @throws Exception
     * @since 5.8.18
     */
    public function ensureEdition(): void
    {
        if (!isset($this->edition)) {
            $this->_setCraftEdition();
        }
    }
}
