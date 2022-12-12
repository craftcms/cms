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
use craft\fieldlayoutelements\users\AddressesField;
use craft\helpers\App;
use craft\helpers\Db;
use craft\helpers\Session;
use craft\i18n\Formatter;
use craft\i18n\I18N;
use craft\i18n\Locale;
use craft\mail\Mailer;
use craft\models\FieldLayout;
use craft\models\Info;
use craft\queue\QueueInterface;
use craft\services\Addresses;
use craft\services\Announcements;
use craft\services\Api;
use craft\services\AssetIndexer;
use craft\services\Assets;
use craft\services\Categories;
use craft\services\Composer;
use craft\services\Conditions;
use craft\services\Config;
use craft\services\Content;
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
use craft\services\Matrix;
use craft\services\Path;
use craft\services\Plugins;
use craft\services\PluginStore;
use craft\services\ProjectConfig;
use craft\services\Relations;
use craft\services\Revisions;
use craft\services\Routes;
use craft\services\Search;
use craft\services\Sections;
use craft\services\Security;
use craft\services\Sites;
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
use craft\web\View;
use Illuminate\Support\Collection;
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
use yii\mutex\Mutex;
use yii\queue\Queue;
use yii\web\ServerErrorHttpException;

/**
 * ApplicationTrait
 *
 * @property bool $isInstalled Whether Craft is installed
 * @property int $edition The active Craft edition
 * @property-read Addresses $addresses The addresses service
 * @property-read Announcements $announcements The announcements service
 * @property-read Api $api The API service
 * @property-read AssetIndexer $assetIndexer The asset indexer service
 * @property-read AssetManager $assetManager The asset manager component
 * @property-read Assets $assets The assets service
 * @property-read Categories $categories The categories service
 * @property-read Composer $composer The Composer service
 * @property-read Conditions $conditions The conditions service
 * @property-read Config $config The config service
 * @property-read Connection $db The database connection component
 * @property-read Content $content The content service
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
 * @property-read Matrix $matrix The matrix service
 * @property-read MigrationManager $contentMigrator The content migration manager
 * @property-read MigrationManager $migrator The application’s migration manager
 * @property-read Mutex $mutex The application’s mutex service
 * @property-read Path $path The path service
 * @property-read PluginStore $pluginStore The plugin store service
 * @property-read Plugins $plugins The plugins service
 * @property-read ProjectConfig $projectConfig The project config service
 * @property-read Queue|QueueInterface $queue The job queue
 * @property-read Relations $relations The relations service
 * @property-read Revisions $revisions The revisions service
 * @property-read Routes $routes The routes service
 * @property-read Search $search The search service
 * @property-read Sections $sections The sections service
 * @property-read Security $security The security component
 * @property-read Sites $sites The sites service
 * @property-read Structures $structures The structures service
 * @property-read SystemMessages $systemMessages The system email messages service
 * @property-read Tags $tags The tags service
 * @property-read TemplateCaches $templateCaches The template caches service
 * @property-read Tokens $tokens The tokens service
 * @property-read Updates $updates The updates service
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
 * @method Formatter getFormatter() Returns the formatter component.
 * @method I18N getI18n() Returns the internationalization (i18n) component.
 * @method Security getSecurity() Returns the security component.
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
     * @var int The Craft edition
     * @see getEdition()
     */
    private int $_edition;

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
            $id = Session::get($this->getUser()->idParam);
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
     * Invokes a callback method when Craft is fully initialized.
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
     * Returns the Craft edition.
     *
     * @return int
     */
    public function getEdition(): int
    {
        if (!isset($this->_edition)) {
            $handle = $this->getProjectConfig()->get('system.edition') ?? 'solo';
            $this->_edition = App::editionIdByHandle($handle);
        }
        return $this->_edition;
    }

    /**
     * Returns the name of the Craft edition.
     *
     * @return string
     */
    public function getEditionName(): string
    {
        return App::editionName($this->getEdition());
    }

    /**
     * Returns the edition Craft is actually licensed to run in.
     *
     * @return int|null
     */
    public function getLicensedEdition(): ?int
    {
        $licensedEdition = $this->getCache()->get('licensedEdition');

        if ($licensedEdition !== false) {
            return (int)$licensedEdition;
        }

        return null;
    }

    /**
     * Returns the name of the edition Craft is actually licensed to run in.
     *
     * @return string|null
     */
    public function getLicensedEditionName(): ?string
    {
        $licensedEdition = $this->getLicensedEdition();

        if ($licensedEdition !== null) {
            return App::editionName($licensedEdition);
        }

        return null;
    }

    /**
     * Returns whether Craft is running with the wrong edition.
     *
     * @return bool
     */
    public function getHasWrongEdition(): bool
    {
        $licensedEdition = $this->getLicensedEdition();

        return ($licensedEdition !== null && $licensedEdition !== $this->getEdition() && !$this->getCanTestEditions());
    }

    /**
     * Sets the Craft edition.
     *
     * @param int $edition The edition to set.
     * @return bool
     */
    public function setEdition(int $edition): bool
    {
        $oldEdition = $this->getEdition();
        $this->getProjectConfig()->set('system.edition', App::editionHandle($edition), "Craft CMS edition change");
        $this->_edition = $edition;

        // Fire an 'afterEditionChange' event
        /** @var WebRequest|ConsoleRequest $request */
        $request = $this->getRequest();
        if (!$request->getIsConsoleRequest() && $this->hasEventHandlers(WebApplication::EVENT_AFTER_EDITION_CHANGE)) {
            $this->trigger(WebApplication::EVENT_AFTER_EDITION_CHANGE, new EditionChangeEvent([
                'oldEdition' => $oldEdition,
                'newEdition' => $edition,
            ]));
        }

        return true;
    }

    /**
     * Requires that Craft is running an equal or better edition than what's passed in
     *
     * @param int $edition The Craft edition to require.
     * @param bool $orBetter If true, makes $edition the minimum edition required.
     * @throws WrongEditionException if attempting to do something not allowed by the current Craft edition
     */
    public function requireEdition(int $edition, bool $orBetter = true): void
    {
        if ($this->getIsInstalled() && !$this->getProjectConfig()->getIsApplyingExternalChanges()) {
            $installedEdition = $this->getEdition();

            if (($orBetter && $installedEdition < $edition) || (!$orBetter && $installedEdition !== $edition)) {
                $editionName = App::editionName($edition);
                throw new WrongEditionException("Craft $editionName is required for this");
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
            $activeEdition = $this->getEdition();
            $licensedEdition = $this->getLicensedEdition();

            return (
                ($activeEdition < Craft::Pro) ||
                ($licensedEdition !== null && $licensedEdition < Craft::Pro)
            );
        }

        return false;
    }

    /**
     * Returns whether Craft is running on a domain that is eligible to test out the editions.
     *
     * @return bool
     */
    public function getCanTestEditions(): bool
    {
        if (!$this instanceof WebApplication) {
            return false;
        }

        /** @var Cache $cache */
        $cache = $this->getCache();
        return $cache->get(sprintf('editionTestableDomain@%s', $this->getRequest()->getHostName()));
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

            // If the request is already over, trigger this immediately
            if (in_array($this->state, [
                Application::STATE_AFTER_REQUEST,
                Application::STATE_SENDING_RESPONSE,
                Application::STATE_END,
            ], true)) {
                $this->saveInfoAfterRequestHandler();
            } else {
                Craft::$app->on(WebApplication::EVENT_AFTER_REQUEST, [$this, 'saveInfoAfterRequestHandler']);
            }
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
        if (($name = Craft::$app->getProjectConfig()->get('system.name')) !== null) {
            return App::parseEnv($name);
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
            Craft::error('There was a problem connecting to the database: ' . $e->getMessage(), __METHOD__);
            /** @var ErrorHandler $errorHandler */
            $errorHandler = $this->getErrorHandler();
            $errorHandler->logException($e);
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('announcements');
    }

    /**
     * Returns the API service.
     *
     * @return Api The API service
     */
    public function getApi(): Api
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('api');
    }

    /**
     * Returns the assets service.
     *
     * @return Assets The assets service
     */
    public function getAssets(): Assets
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('assets');
    }

    /**
     * Returns the asset indexing service.
     *
     * @return AssetIndexer The asset indexing service
     */
    public function getAssetIndexer(): AssetIndexer
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('assetIndexer');
    }

    /**
     * Returns the image transforms service.
     *
     * @return ImageTransforms The asset transforms service
     */
    public function getImageTransforms(): ImageTransforms
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('imageTransforms');
    }

    /**
     * Returns the categories service.
     *
     * @return Categories The categories service
     */
    public function getCategories(): Categories
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('categories');
    }

    /**
     * Returns the Composer service.
     *
     * @return Composer The Composer service
     */
    public function getComposer(): Composer
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('conditions');
    }

    /**
     * Returns the config service.
     *
     * @return Config The config service
     */
    public function getConfig(): Config
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('config');
    }

    /**
     * Returns the content service.
     *
     * @return Content The content service
     */
    public function getContent(): Content
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('content');
    }

    /**
     * Returns the content migration manager.
     *
     * @return MigrationManager The content migration manager
     */
    public function getContentMigrator(): MigrationManager
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('contentMigrator');
    }

    /**
     * Returns the dashboard service.
     *
     * @return Dashboard The dashboard service
     */
    public function getDashboard(): Dashboard
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('dashboard');
    }

    /**
     * Returns the deprecator service.
     *
     * @return Deprecator The deprecator service
     */
    public function getDeprecator(): Deprecator
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('drafts');
    }

    /**
     * Returns the element indexes service.
     *
     * @return ElementSources The element indexes service
     */
    public function getElementSources(): ElementSources
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('elementSources');
    }

    /**
     * Returns the elements service.
     *
     * @return Elements The elements service
     */
    public function getElements(): Elements
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('elements');
    }

    /**
     * Returns the system email messages service.
     *
     * @return SystemMessages The system email messages service
     */
    public function getSystemMessages(): SystemMessages
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('systemMessages');
    }

    /**
     * Returns the entries service.
     *
     * @return Entries The entries service
     */
    public function getEntries(): Entries
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('entries');
    }

    /**
     * Returns the fields service.
     *
     * @return Fields The fields service
     */
    public function getFields(): Fields
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('formattingLocale');
    }

    /**
     * Returns the garbage collection service.
     *
     * @return Gc The garbage collection service
     */
    public function getGc(): Gc
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('gc');
    }

    /**
     * Returns the globals service.
     *
     * @return Globals The globals service
     */
    public function getGlobals(): Globals
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('gql');
    }

    /**
     * Returns the images service.
     *
     * @return Images The images service
     */
    public function getImages(): Images
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('images');
    }

    /**
     * Returns a Locale object for the target language.
     *
     * @return Locale The Locale object for the target language
     */
    public function getLocale(): Locale
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('locale');
    }

    /**
     * Returns the current mailer.
     *
     * @return Mailer The mailer component
     */
    public function getMailer(): Mailer
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('mailer');
    }

    /**
     * Returns the matrix service.
     *
     * @return Matrix The matrix service
     */
    public function getMatrix(): Matrix
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('matrix');
    }

    /**
     * Returns the application’s migration manager.
     *
     * @return MigrationManager The application’s migration manager
     */
    public function getMigrator(): MigrationManager
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('migrator');
    }

    /**
     * Returns the application’s mutex service.
     *
     * @return Mutex The application’s mutex service
     */
    public function getMutex(): Mutex
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('mutex');
    }

    /**
     * Returns the path service.
     *
     * @return Path The path service
     */
    public function getPath(): Path
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('path');
    }

    /**
     * Returns the plugins service.
     *
     * @return Plugins The plugins service
     */
    public function getPlugins(): Plugins
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('plugins');
    }

    /**
     * Returns the plugin store service.
     *
     * @return PluginStore The plugin store service
     */
    public function getPluginStore(): PluginStore
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('pluginStore');
    }

    /**
     * Returns the system config service.
     *
     * @return ProjectConfig The system config service
     */
    public function getProjectConfig(): ProjectConfig
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('projectConfig');
    }

    /**
     * Returns the queue service.
     *
     * @return Queue The queue service
     */
    public function getQueue(): Queue
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('queue');
    }

    /**
     * Returns the relations service.
     *
     * @return Relations The relations service
     */
    public function getRelations(): Relations
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('revisions');
    }

    /**
     * Returns the routes service.
     *
     * @return Routes The routes service
     */
    public function getRoutes(): Routes
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('routes');
    }

    /**
     * Returns the search service.
     *
     * @return Search The search service
     */
    public function getSearch(): Search
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('search');
    }

    /**
     * Returns the sections service.
     *
     * @return Sections The sections service
     */
    public function getSections(): Sections
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('sections');
    }

    /**
     * Returns the sites service.
     *
     * @return Sites The sites service
     */
    public function getSites(): Sites
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('sites');
    }

    /**
     * Returns the structures service.
     *
     * @return Structures The structures service
     */
    public function getStructures(): Structures
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('structures');
    }

    /**
     * Returns the tags service.
     *
     * @return Tags The tags service
     */
    public function getTags(): Tags
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('tags');
    }

    /**
     * Returns the template cache service.
     *
     * @return TemplateCaches The template caches service
     */
    public function getTemplateCaches(): TemplateCaches
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('templateCaches');
    }

    /**
     * Returns the tokens service.
     *
     * @return Tokens The tokens service
     */
    public function getTokens(): Tokens
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('tokens');
    }

    /**
     * Returns the updates service.
     *
     * @return Updates The updates service
     */
    public function getUpdates(): Updates
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('updates');
    }

    /**
     * Returns the user groups service.
     *
     * @return UserGroups The user groups service
     */
    public function getUserGroups(): UserGroups
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('userGroups');
    }

    /**
     * Returns the user permissions service.
     *
     * @return UserPermissions The user permissions service
     */
    public function getUserPermissions(): UserPermissions
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('userPermissions');
    }

    /**
     * Returns the users service.
     *
     * @return Users The users service
     */
    public function getUsers(): Users
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('users');
    }

    /**
     * Returns the utilities service.
     *
     * @return Utilities The utilities service
     */
    public function getUtilities(): Utilities
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('utilities');
    }

    /**
     * Returns the volumes service.
     *
     * @return Volumes The volumes service
     */
    public function getVolumes(): Volumes
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
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

        // Register Collection::one() as an alias of first(), for consistency with yii\db\Query.
        Collection::macro('one', function() {
            /** @var Collection $this */
            return $this->first(...func_get_args());
        });

        // Load the request before anything else, so everything else can safely check Craft::$app->has('request', true)
        // to avoid possible recursive fatal errors in the request initialization
        $request = $this->getRequest();
        $this->getLog();

        // Set the timezone
        $this->_setTimeZone();

        // Set the language
        $this->updateTargetLanguage();

        // Prevent browser caching if this is a control panel request
        if ($this instanceof WebApplication && $request->getIsCpRequest()) {
            $this->getResponse()->setNoCacheHeaders();
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
        /** @var WebApplication|ConsoleApplication $this */
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
                    $event->fields[] = AddressesField::class;
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
            // Field groups
            ->onAdd(ProjectConfig::PATH_FIELD_GROUPS . '.{uid}', $this->_proxy('fields', 'handleChangedGroup'))
            ->onUpdate(ProjectConfig::PATH_FIELD_GROUPS . '.{uid}', $this->_proxy('fields', 'handleChangedGroup'))
            ->onRemove(ProjectConfig::PATH_FIELD_GROUPS . '.{uid}', $this->_proxy('fields', 'handleDeletedGroup'))
            // Fields
            ->onAdd(ProjectConfig::PATH_FIELDS . '.{uid}', $this->_proxy('fields', 'handleChangedField'))
            ->onUpdate(ProjectConfig::PATH_FIELDS . '.{uid}', $this->_proxy('fields', 'handleChangedField'))
            ->onRemove(ProjectConfig::PATH_FIELDS . '.{uid}', $this->_proxy('fields', 'handleDeletedField'))
            // Block types
            ->onAdd(ProjectConfig::PATH_MATRIX_BLOCK_TYPES . '.{uid}', $this->_proxy('matrix', 'handleChangedBlockType'))
            ->onUpdate(ProjectConfig::PATH_MATRIX_BLOCK_TYPES . '.{uid}', $this->_proxy('matrix', 'handleChangedBlockType'))
            ->onRemove(ProjectConfig::PATH_MATRIX_BLOCK_TYPES . '.{uid}', $this->_proxy('matrix', 'handleDeletedBlockType'))
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
            ->onAdd(ProjectConfig::PATH_SECTIONS . '.{uid}', $this->_proxy('sections', 'handleChangedSection'))
            ->onUpdate(ProjectConfig::PATH_SECTIONS . '.{uid}', $this->_proxy('sections', 'handleChangedSection'))
            ->onRemove(ProjectConfig::PATH_SECTIONS . '.{uid}', $this->_proxy('sections', 'handleDeletedSection'))
            // Entry types
            ->onAdd(ProjectConfig::PATH_ENTRY_TYPES . '.{uid}', $this->_proxy('sections', 'handleChangedEntryType'))
            ->onUpdate(ProjectConfig::PATH_ENTRY_TYPES . '.{uid}', $this->_proxy('sections', 'handleChangedEntryType'))
            ->onRemove(ProjectConfig::PATH_ENTRY_TYPES . '.{uid}', $this->_proxy('sections', 'handleDeletedEntryType'))
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
                $this->getSections()->pruneDeletedSite($event);
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
}
