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
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\errors\DbConnectException;
use craft\errors\SiteNotFoundException;
use craft\errors\WrongEditionException;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\DeleteSiteEvent;
use craft\events\EditionChangeEvent;
use craft\events\FieldEvent;
use craft\fieldlayoutelements\AssetTitleField;
use craft\fieldlayoutelements\EntryTitleField;
use craft\fieldlayoutelements\TitleField;
use craft\helpers\App;
use craft\helpers\Db;
use craft\helpers\Session;
use craft\i18n\Formatter;
use craft\i18n\I18N;
use craft\i18n\Locale;
use craft\models\FieldLayout;
use craft\models\Info;
use craft\queue\Queue;
use craft\queue\QueueInterface;
use craft\services\AssetTransforms;
use craft\services\Categories;
use craft\services\Fields;
use craft\services\Globals;
use craft\services\Gql;
use craft\services\Matrix;
use craft\services\Sections;
use craft\services\Security;
use craft\services\Sites;
use craft\services\Tags;
use craft\services\UserGroups;
use craft\services\Users;
use craft\services\Volumes;
use craft\web\Application as WebApplication;
use craft\web\AssetManager;
use craft\web\Request as WebRequest;
use craft\web\View;
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
use yii\web\ServerErrorHttpException;

/**
 * ApplicationTrait
 *
 * @property bool $isInstalled Whether Craft is installed
 * @property int $edition The active Craft edition
 * @property-read \craft\db\MigrationManager $contentMigrator The content migration manager
 * @property-read \craft\db\MigrationManager $migrator The application’s migration manager
 * @property-read \craft\feeds\Feeds $feeds The feeds service
 * @property-read \craft\i18n\Locale $formattingLocale The Locale object that should be used to define the formatter
 * @property-read \craft\i18n\Locale $locale The Locale object for the target language
 * @property-read \craft\mail\Mailer $mailer The mailer component
 * @property-read \craft\services\Announcements $announcements The announcements service
 * @property-read \craft\services\Api $api The API service
 * @property-read \craft\services\AssetIndexer $assetIndexer The asset indexer service
 * @property-read \craft\services\Assets $assets The assets service
 * @property-read \craft\services\AssetTransforms $assetTransforms The asset transforms service
 * @property-read \craft\services\Categories $categories The categories service
 * @property-read \craft\services\Composer $composer The Composer service
 * @property-read \craft\services\Config $config The config service
 * @property-read \craft\services\Content $content The content service
 * @property-read \craft\services\Dashboard $dashboard The dashboard service
 * @property-read \craft\services\Deprecator $deprecator The deprecator service
 * @property-read \craft\services\Drafts $drafts The drafts service
 * @property-read \craft\services\ElementIndexes $elementIndexes The element indexes service
 * @property-read \craft\services\Elements $elements The elements service
 * @property-read \craft\services\Entries $entries The entries service
 * @property-read \craft\services\Fields $fields The fields service
 * @property-read \craft\services\Gc $gc The garbage collection service
 * @property-read \craft\services\Globals $globals The globals service
 * @property-read \craft\services\Gql $gql The GraphQl service
 * @property-read \craft\services\Images $images The images service
 * @property-read \craft\services\Matrix $matrix The matrix service
 * @property-read \craft\services\Path $path The path service
 * @property-read \craft\services\Plugins $plugins The plugins service
 * @property-read \craft\services\PluginStore $pluginStore The plugin store service
 * @property-read \craft\services\ProjectConfig $projectConfig The project config service
 * @property-read \craft\services\Relations $relations The relations service
 * @property-read \craft\services\Revisions $revisions The revisions service
 * @property-read \craft\services\Routes $routes The routes service
 * @property-read \craft\services\Search $search The search service
 * @property-read \craft\services\Sections $sections The sections service
 * @property-read \craft\services\Sites $sites The sites service
 * @property-read \craft\services\Structures $structures The structures service
 * @property-read \craft\services\SystemMessages $systemMessages The system email messages service
 * @property-read \craft\services\SystemSettings $systemSettings The system settings service
 * @property-read \craft\services\Tags $tags The tags service
 * @property-read \craft\services\TemplateCaches $templateCaches The template caches service
 * @property-read \craft\services\Tokens $tokens The tokens service
 * @property-read \craft\services\Updates $updates The updates service
 * @property-read \craft\services\UserGroups $userGroups The user groups service
 * @property-read \craft\services\UserPermissions $userPermissions The user permissions service
 * @property-read \craft\services\Users $users The users service
 * @property-read \craft\services\Utilities $utilities The utilities service
 * @property-read \craft\services\Volumes $volumes The volumes service
 * @property-read \craft\services\Webpack $webpack The webpack service
 * @property-read \yii\mutex\Mutex $mutex The application’s mutex service
 * @property-read AssetManager $assetManager The asset manager component
 * @property-read bool $canTestEditions Whether Craft is running on a domain that is eligible to test out the editions
 * @property-read bool $canUpgradeEdition Whether Craft is eligible to be upgraded to a different edition
 * @property-read bool $hasWrongEdition Whether Craft is running with the wrong edition
 * @property-read bool $isInitialized Whether Craft is fully initialized
 * @property-read bool $isInMaintenanceMode Whether someone is currently performing a system update
 * @property-read bool $isMultiSite Whether this site has multiple sites
 * @property-read bool $isSystemLive Whether the system is live
 * @property-read Connection $db The database connection component
 * @property-read Formatter $formatter The formatter component
 * @property-read I18N $i18n The internationalization (i18n) component
 * @property-read Queue|QueueInterface $queue The job queue
 * @property-read Security $security The security component
 * @property-read string $installedSchemaVersion The installed schema version
 * @property-read View $view The view component
 * @method AssetManager getAssetManager() Returns the asset manager component.
 * @method Connection getDb() Returns the database connection component.
 * @method Formatter getFormatter() Returns the formatter component.
 * @method I18N getI18n() Returns the internationalization (i18n) component.
 * @method Security getSecurity() Returns the security component.
 * @method View getView() Returns the view component.
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
trait ApplicationTrait
{
    /**
     * @var string|null Craft’s schema version number.
     */
    public $schemaVersion;

    /**
     * @var string|null The minimum Craft build number required to update to this build.
     */
    public $minVersionRequired;

    /**
     * @var string|null The environment ID Craft is currently running in.
     */
    public $env;

    /**
     * @var string The base Craftnet API URL to use.
     * @since 3.3.16
     * @internal
     */
    public $baseApiUrl = 'https://api.craftcms.com/v1/';

    /**
     * @var string[]|null Query params that should be appended to Craftnet API requests.
     * @since 3.3.16
     * @internal
     */
    public $apiParams;

    /**
     * @var
     */
    private $_isInstalled;

    /**
     * @var bool Whether the application is fully initialized yet
     * @see getIsInitialized()
     */
    private $_isInitialized = false;

    /**
     * @var bool
     * @see getIsMultiSite()
     */
    private $_isMultiSite;

    /**
     * @var bool
     * @see getIsMultiSite()
     */
    private $_isMultiSiteWithTrashed;

    /**
     * @var int|null The Craft edition
     * @see getEdition()
     */
    private $_edition;

    /**
     * @var
     */
    private $_info;

    /**
     * @var bool|null
     */
    private $_isDbConfigValid;

    /**
     * @var bool
     */
    private $_gettingLanguage = false;

    /**
     * @var bool Whether we’re listening for the request end, to update the application info
     * @see saveInfoAfterRequest()
     */
    private $_waitingToSaveInfo = false;

    /**
     * Sets the target application language.
     *
     * @param bool|null $useUserLanguage Whether the user's preferred language should be used.
     * If null, the user’s preferred language will be used if this is a control panel request or a console request.
     */
    public function updateTargetLanguage(bool $useUserLanguage = null)
    {
        /** @var WebApplication|ConsoleApplication $this */
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
     * @param bool $useUserLanguage Whether the user's preferred language should be used.
     * @return string
     */
    public function getTargetLanguage(bool $useUserLanguage = true): string
    {
        /** @var WebApplication|ConsoleApplication $this */
        // Use the fallback language for console requests, or if Craft isn't installed or is updating
        if (
            $this instanceof ConsoleApplication ||
            !$this->getIsInstalled() ||
            $this->getUpdates()->getIsCraftDbMigrationNeeded()
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

            // Fall back on the default CP language, if there is one, otherwise the browser language
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
        } elseif ($this->_isInstalled !== null) {
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
        } catch (DbException | ServerErrorHttpException $e) {
            // yii2-redis awkwardly throws yii\db\Exception's rather than their own exception class.
            if ($e instanceof DbException && strpos($e->getMessage(), 'Redis') !== false) {
                throw $e;
            }

            Craft::error('There was a problem fetching the info row: ' . $e->getMessage(), __METHOD__);
            /** @var ErrorHandler $errorHandler */
            $errorHandler = $this->getErrorHandler();
            $errorHandler->logException($e);
            return $this->_isInstalled = false;
        }
    }

    /**
     * Sets Craft's record of whether it's installed
     *
     * @param bool|null $value
     */
    public function setIsInstalled($value = true)
    {
        /** @var WebApplication|ConsoleApplication $this */
        $this->_isInstalled = $value;
    }

    /**
     * Returns the installed schema version.
     *
     * @return string
     * @since 3.2.0
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
     * Returns whether this Craft install has multiple sites.
     *
     * @param bool $refresh Whether to ignore the cached result and check again
     * @param bool $withTrashed Whether to factor in soft-deleted sites
     * @return bool
     */
    public function getIsMultiSite(bool $refresh = false, bool $withTrashed = false): bool
    {
        /** @var WebApplication|ConsoleApplication $this */
        if ($withTrashed) {
            if (!$refresh && $this->_isMultiSiteWithTrashed !== null) {
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

        if (!$refresh && $this->_isMultiSite !== null) {
            return $this->_isMultiSite;
        }
        return $this->_isMultiSite = (count($this->getSites()->getAllSites(true)) > 1);
    }

    /**
     * Returns the Craft edition.
     *
     * @return int
     */
    public function getEdition(): int
    {
        /** @var WebApplication|ConsoleApplication $this */
        if ($this->_edition === null) {
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
        /** @var WebApplication|ConsoleApplication $this */
        return App::editionName($this->getEdition());
    }

    /**
     * Returns the edition Craft is actually licensed to run in.
     *
     * @return int|null
     */
    public function getLicensedEdition()
    {
        /** @var WebApplication|ConsoleApplication $this */
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
    public function getLicensedEditionName()
    {
        /** @var WebApplication|ConsoleApplication $this */
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
        /** @var WebApplication|ConsoleApplication $this */
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
        /** @var WebApplication|ConsoleApplication $this */
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
    public function requireEdition(int $edition, bool $orBetter = true)
    {
        /** @var WebApplication|ConsoleApplication $this */
        if ($this->getIsInstalled() && !$this->getProjectConfig()->getIsApplyingYamlChanges()) {
            $installedEdition = $this->getEdition();

            if (($orBetter && $installedEdition < $edition) || (!$orBetter && $installedEdition !== $edition)) {
                $editionName = App::editionName($edition);
                throw new WrongEditionException("Craft {$editionName} is required for this");
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
        /** @var WebApplication|ConsoleApplication $this */
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
        /** @var WebApplication|ConsoleApplication $this */
        $request = $this->getRequest();
        if ($request->getIsConsoleRequest()) {
            return false;
        }

        /** @var Cache $cache */
        $cache = $this->getCache();
        return $cache->get('editionTestableDomain@' . $request->getHostName());
    }

    /**
     * Returns the system's UID.
     *
     * @return string|null
     */
    public function getSystemUid()
    {
        /** @var WebApplication|ConsoleApplication $this */
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
        /** @var WebApplication|ConsoleApplication $this */
        if (is_bool($live = $this->getConfig()->getGeneral()->isSystemLive)) {
            return $live;
        }

        return App::parseBooleanEnv($this->getProjectConfig()->get('system.live')) ?? false;
    }

    /**
     * Returns whether the system is currently live.
     *
     * @return bool
     * @deprecated in 3.1.0. Use [[getIsLive()]] instead.
     */
    public function getIsSystemOn(): bool
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->getIsLive();
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
        /** @var WebApplication|ConsoleApplication $this */
        return (bool)$this->getInfo()->maintenance;
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
        /** @var WebApplication|ConsoleApplication $this */
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
        /** @var WebApplication|ConsoleApplication $this */
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
        /** @var WebApplication|ConsoleApplication $this */
        if ($this->_info !== null) {
            return $this->_info;
        }

        try {
            $row = (new Query())
                ->from([Table::INFO])
                ->where(['id' => 1])
                ->one();
        } catch (DbException | DbConnectException $e) {
            if ($throwException) {
                throw $e;
            }
            return $this->_info = new Info();
        }

        if (!$row) {
            $tableName = $this->getDb()->getSchema()->getRawTableName(Table::INFO);
            throw new ServerErrorHttpException("The {$tableName} table is missing its row");
        }

        // TODO: Remove this after the next breakpoint
        if (isset($row['build'])) {
            $version = $row['version'];

            switch ($row['track']) {
                case 'dev':
                    $version .= '.0-alpha.' . $row['build'];
                    break;
                case 'beta':
                    $version .= '.0-beta.' . $row['build'];
                    break;
                default:
                    $version .= '.' . $row['build'];
                    break;
            }

            $row['version'] = $version;
        }
        unset($row['edition'], $row['name'], $row['timezone'], $row['on'], $row['siteName'], $row['siteUrl'], $row['build'], $row['releaseDate'], $row['track'], $row['config'], $row['configMap']);

        return $this->_info = new Info($row);
    }

    /**
     * Updates the info row at the end of the request.
     *
     * @since 3.1.33
     */
    public function saveInfoAfterRequest()
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
    public function saveInfoAfterRequestHandler()
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
    public function saveInfo(Info $info, array $attributeNames = null): bool
    {
        /** @var WebApplication|ConsoleApplication $this */

        if ($attributeNames === null) {
            $attributeNames = ['version', 'schemaVersion', 'maintenance', 'configVersion', 'fieldVersion'];
        }

        if (!$info->validate($attributeNames)) {
            return false;
        }

        $attributes = $info->getAttributes($attributeNames);

        // TODO: Remove these after the next breakpoint
        if (version_compare($info['version'], '3.5.6', '<')) {
            unset($attributes['configVersion']);

            if (version_compare($info['version'], '3.1', '<')) {
                unset($attributes['config'], $attributes['configMap']);

                if (version_compare($info['version'], '3.0', '<')) {
                    unset($attributes['fieldVersion']);
                }
            }
        }

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
        } catch (SiteNotFoundException $e) {
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
        return \Yii::getVersion();
    }

    /**
     * Returns whether the DB connection settings are valid.
     *
     * @return bool
     * @internal Don't even think of moving this check into Connection->init().
     */
    public function getIsDbConnectionValid(): bool
    {
        /** @var WebApplication|ConsoleApplication $this */
        try {
            $this->getDb()->open();
        } catch (DbConnectException | InvalidConfigException $e) {
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
     * Returns the announcements service.
     *
     * @return \craft\services\Announcements The announcements service
     * @since 3.7.0
     */
    public function getAnnouncements()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('announcements');
    }

    /**
     * Returns the API service.
     *
     * @return \craft\services\Api The API service
     */
    public function getApi()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('api');
    }

    /**
     * Returns the assets service.
     *
     * @return \craft\services\Assets The assets service
     */
    public function getAssets()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('assets');
    }

    /**
     * Returns the asset indexing service.
     *
     * @return \craft\services\AssetIndexer The asset indexing service
     */
    public function getAssetIndexer()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('assetIndexer');
    }

    /**
     * Returns the asset transforms service.
     *
     * @return \craft\services\AssetTransforms The asset transforms service
     */
    public function getAssetTransforms()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('assetTransforms');
    }

    /**
     * Returns the categories service.
     *
     * @return \craft\services\Categories The categories service
     */
    public function getCategories()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('categories');
    }

    /**
     * Returns the Composer service.
     *
     * @return \craft\services\Composer The Composer service
     */
    public function getComposer()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('composer');
    }

    /**
     * Returns the config service.
     *
     * @return \craft\services\Config The config service
     */
    public function getConfig()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('config');
    }

    /**
     * Returns the content service.
     *
     * @return \craft\services\Content The content service
     */
    public function getContent()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('content');
    }

    /**
     * Returns the content migration manager.
     *
     * @return MigrationManager The content migration manager
     */
    public function getContentMigrator(): MigrationManager
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('contentMigrator');
    }

    /**
     * Returns the dashboard service.
     *
     * @return \craft\services\Dashboard The dashboard service
     */
    public function getDashboard()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('dashboard');
    }

    /**
     * Returns the deprecator service.
     *
     * @return \craft\services\Deprecator The deprecator service
     */
    public function getDeprecator()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('deprecator');
    }

    /**
     * Returns the drafts service.
     *
     * @return \craft\services\Drafts The drafts service
     * @since 3.2.0
     */
    public function getDrafts()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('drafts');
    }

    /**
     * Returns the element indexes service.
     *
     * @return \craft\services\ElementIndexes The element indexes service
     */
    public function getElementIndexes()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('elementIndexes');
    }

    /**
     * Returns the elements service.
     *
     * @return \craft\services\Elements The elements service
     */
    public function getElements()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('elements');
    }

    /**
     * Returns the system email messages service.
     *
     * @return \craft\services\SystemMessages The system email messages service
     */
    public function getSystemMessages()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('systemMessages');
    }

    /**
     * Returns the entries service.
     *
     * @return \craft\services\Entries The entries service
     */
    public function getEntries()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('entries');
    }

    /**
     * Returns the entry revisions service.
     *
     * @return \craft\services\EntryRevisions The entry revisions service
     * @deprecated in 3.2.0.
     */
    public function getEntryRevisions()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('entryRevisions');
    }

    /**
     * Returns the feeds service.
     *
     * @return \craft\feeds\Feeds The feeds service
     * @deprecated in 3.4.24
     */
    public function getFeeds()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('feeds');
    }

    /**
     * Returns the fields service.
     *
     * @return \craft\services\Fields The fields service
     */
    public function getFields()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('fields');
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
     * @return \craft\services\Gc The garbage collection service
     */
    public function getGc()
    {
        return $this->get('gc');
    }

    /**
     * Returns the globals service.
     *
     * @return \craft\services\Globals The globals service
     */
    public function getGlobals()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('globals');
    }

    /**
     * Returns the GraphQL service.
     *
     * @return \craft\services\Gql The GraphQL service
     * @since 3.3.0
     */
    public function getGql()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('gql');
    }

    /**
     * Returns the images service.
     *
     * @return \craft\services\Images The images service
     */
    public function getImages()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('images');
    }

    /**
     * Returns a Locale object for the target language.
     *
     * @return Locale The Locale object for the target language
     */
    public function getLocale(): Locale
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('locale');
    }

    /**
     * Returns the current mailer.
     *
     * @return \craft\mail\Mailer The mailer component
     */
    public function getMailer()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('mailer');
    }

    /**
     * Returns the matrix service.
     *
     * @return \craft\services\Matrix The matrix service
     */
    public function getMatrix()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('matrix');
    }

    /**
     * Returns the application’s migration manager.
     *
     * @return MigrationManager The application’s migration manager
     */
    public function getMigrator(): MigrationManager
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('migrator');
    }

    /**
     * Returns the application’s mutex service.
     *
     * @return Mutex The application’s mutex service
     */
    public function getMutex(): Mutex
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('mutex');
    }

    /**
     * Returns the path service.
     *
     * @return \craft\services\Path The path service
     */
    public function getPath()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('path');
    }

    /**
     * Returns the plugins service.
     *
     * @return \craft\services\Plugins The plugins service
     */
    public function getPlugins()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('plugins');
    }

    /**
     * Returns the plugin store service.
     *
     * @return \craft\services\PluginStore The plugin store service
     */
    public function getPluginStore()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('pluginStore');
    }

    /**
     * Returns the queue service.
     *
     * @return Queue|QueueInterface The queue service
     */
    public function getQueue()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('queue');
    }

    /**
     * Returns the relations service.
     *
     * @return \craft\services\Relations The relations service
     */
    public function getRelations()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('relations');
    }

    /**
     * Returns the revisions service.
     *
     * @return \craft\services\Revisions The revisions service
     * @since 3.2.0
     */
    public function getRevisions()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('revisions');
    }

    /**
     * Returns the routes service.
     *
     * @return \craft\services\Routes The routes service
     */
    public function getRoutes()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('routes');
    }

    /**
     * Returns the search service.
     *
     * @return \craft\services\Search The search service
     */
    public function getSearch()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('search');
    }

    /**
     * Returns the sections service.
     *
     * @return \craft\services\Sections The sections service
     */
    public function getSections()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('sections');
    }

    /**
     * Returns the sites service.
     *
     * @return \craft\services\Sites The sites service
     */
    public function getSites()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('sites');
    }

    /**
     * Returns the structures service.
     *
     * @return \craft\services\Structures The structures service
     */
    public function getStructures()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('structures');
    }

    /**
     * Returns the system config service.
     *
     * @return \craft\services\ProjectConfig The system config service
     */
    public function getProjectConfig()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('projectConfig');
    }

    /**
     * Returns the system settings service.
     *
     * @return \craft\services\SystemSettings The system settings service
     */
    public function getSystemSettings()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('systemSettings');
    }

    /**
     * Returns the tags service.
     *
     * @return \craft\services\Tags The tags service
     */
    public function getTags()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('tags');
    }

    /**
     * Returns the template cache service.
     *
     * @return \craft\services\TemplateCaches The template caches service
     */
    public function getTemplateCaches()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('templateCaches');
    }

    /**
     * Returns the tokens service.
     *
     * @return \craft\services\Tokens The tokens service
     */
    public function getTokens()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('tokens');
    }

    /**
     * Returns the updates service.
     *
     * @return \craft\services\Updates The updates service
     */
    public function getUpdates()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('updates');
    }

    /**
     * Returns the user groups service.
     *
     * @return \craft\services\UserGroups The user groups service
     */
    public function getUserGroups()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('userGroups');
    }

    /**
     * Returns the user permissions service.
     *
     * @return \craft\services\UserPermissions The user permissions service
     */
    public function getUserPermissions()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('userPermissions');
    }

    /**
     * Returns the users service.
     *
     * @return \craft\services\Users The users service
     */
    public function getUsers()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('users');
    }

    /**
     * Returns the utilities service.
     *
     * @return \craft\services\Utilities The utilities service
     */
    public function getUtilities()
    {
        /** @var \craft\web\Application|\craft\console\Application $this */
        return $this->get('utilities');
    }

    /**
     * Returns the volumes service.
     *
     * @return \craft\services\Volumes The volumes service
     */
    public function getVolumes()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('volumes');
    }

    /**
     * Returns the webpack service.
     *
     * @return \craft\services\Webpack The volumes service
     * @since 3.7.22
     */
    public function getWebpack()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('webpack');
    }

    /**
     * Initializes things that should happen before the main Application::init()
     */
    private function _preInit()
    {
        // Add support for MySQL-specific column types
        ColumnSchemaBuilder::$typeCategoryMap[Schema::TYPE_TINYTEXT] = ColumnSchemaBuilder::CATEGORY_STRING;
        ColumnSchemaBuilder::$typeCategoryMap[Schema::TYPE_MEDIUMTEXT] = ColumnSchemaBuilder::CATEGORY_STRING;
        ColumnSchemaBuilder::$typeCategoryMap[Schema::TYPE_LONGTEXT] = ColumnSchemaBuilder::CATEGORY_STRING;
        ColumnSchemaBuilder::$typeCategoryMap[Schema::TYPE_ENUM] = ColumnSchemaBuilder::CATEGORY_STRING;

        // Load the request before anything else, so everything else can safely check Craft::$app->has('request', true)
        // to avoid possible recursive fatal errors in the request initialization
        $request = $this->getRequest();
        $this->getLog();

        // Set the timezone
        $this->_setTimeZone();

        // Set the language
        $this->updateTargetLanguage();

        // Prevent browser caching if this is a control panel request
        if ($request->getIsCpRequest()) {
            $this->getResponse()->setNoCacheHeaders();
        }
    }

    /**
     * Initializes things that should happen after the main Application::init()
     */
    private function _postInit()
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

        if ($this->getIsInstalled() && !$this->getUpdates()->getIsCraftDbMigrationNeeded()) {
            // Possibly run garbage collection
            $this->getGc()->run();
        }
    }

    /**
     * Sets the system timezone.
     */
    private function _setTimeZone()
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
        /** @var WebApplication|ConsoleApplication $this */
        $info = $this->getInfo();
        if ((bool)$info->maintenance === $value) {
            return true;
        }
        $info->maintenance = $value;
        return $this->saveInfo($info);
    }

    /**
     * Tries to find a language match with the browser's preferred language(s).
     *
     * If not uses the app's sourceLanguage.
     *
     * @return string
     */
    private function _getFallbackLanguage(): string
    {
        /** @var WebApplication|ConsoleApplication $this */
        // See if we have the CP translated in one of the user's browsers preferred language(s)
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
    private function _registerFieldLayoutListener()
    {
        Event::on(FieldLayout::class, FieldLayout::EVENT_DEFINE_STANDARD_FIELDS, function(DefineFieldLayoutFieldsEvent $event) {
            /** @var FieldLayout $fieldLayout */
            $fieldLayout = $event->sender;

            switch ($fieldLayout->type) {
                case Category::class:
                case Tag::class:
                    $event->fields[] = TitleField::class;
                    break;
                case Asset::class:
                    $event->fields[] = AssetTitleField::class;
                    break;
                case Entry::class:
                    $event->fields[] = EntryTitleField::class;
                    break;
            }
        });
    }

    /**
     * Register event listeners for config changes.
     */
    private function _registerConfigListeners()
    {
        $this->getProjectConfig()
            // Field groups
            ->onAdd(Fields::CONFIG_FIELDGROUP_KEY . '.{uid}', $this->_proxy('fields', 'handleChangedGroup'))
            ->onUpdate(Fields::CONFIG_FIELDGROUP_KEY . '.{uid}', $this->_proxy('fields', 'handleChangedGroup'))
            ->onRemove(Fields::CONFIG_FIELDGROUP_KEY . '.{uid}', $this->_proxy('fields', 'handleDeletedGroup'))
            // Fields
            ->onAdd(Fields::CONFIG_FIELDS_KEY . '.{uid}', $this->_proxy('fields', 'handleChangedField'))
            ->onUpdate(Fields::CONFIG_FIELDS_KEY . '.{uid}', $this->_proxy('fields', 'handleChangedField'))
            ->onRemove(Fields::CONFIG_FIELDS_KEY . '.{uid}', $this->_proxy('fields', 'handleDeletedField'))
            // Block types
            ->onAdd(Matrix::CONFIG_BLOCKTYPE_KEY . '.{uid}', $this->_proxy('matrix', 'handleChangedBlockType'))
            ->onUpdate(Matrix::CONFIG_BLOCKTYPE_KEY . '.{uid}', $this->_proxy('matrix', 'handleChangedBlockType'))
            ->onRemove(Matrix::CONFIG_BLOCKTYPE_KEY . '.{uid}', $this->_proxy('matrix', 'handleDeletedBlockType'))
            // Volumes
            ->onAdd(Volumes::CONFIG_VOLUME_KEY . '.{uid}', $this->_proxy('volumes', 'handleChangedVolume'))
            ->onUpdate(Volumes::CONFIG_VOLUME_KEY . '.{uid}', $this->_proxy('volumes', 'handleChangedVolume'))
            ->onRemove(Volumes::CONFIG_VOLUME_KEY . '.{uid}', $this->_proxy('volumes', 'handleDeletedVolume'))
            // Transforms
            ->onAdd(AssetTransforms::CONFIG_TRANSFORM_KEY . '.{uid}', $this->_proxy('assetTransforms', 'handleChangedTransform'))
            ->onUpdate(AssetTransforms::CONFIG_TRANSFORM_KEY . '.{uid}', $this->_proxy('assetTransforms', 'handleChangedTransform'))
            ->onRemove(AssetTransforms::CONFIG_TRANSFORM_KEY . '.{uid}', $this->_proxy('assetTransforms', 'handleDeletedTransform'))
            // Site groups
            ->onAdd(Sites::CONFIG_SITEGROUP_KEY . '.{uid}', $this->_proxy('sites', 'handleChangedGroup'))
            ->onUpdate(Sites::CONFIG_SITEGROUP_KEY . '.{uid}', $this->_proxy('sites', 'handleChangedGroup'))
            ->onRemove(Sites::CONFIG_SITEGROUP_KEY . '.{uid}', $this->_proxy('sites', 'handleDeletedGroup'))
            // Sites
            ->onAdd(Sites::CONFIG_SITES_KEY . '.{uid}', $this->_proxy('sites', 'handleChangedSite'))
            ->onUpdate(Sites::CONFIG_SITES_KEY . '.{uid}', $this->_proxy('sites', 'handleChangedSite'))
            ->onRemove(Sites::CONFIG_SITES_KEY . '.{uid}', $this->_proxy('sites', 'handleDeletedSite'))
            // Tags
            ->onAdd(Tags::CONFIG_TAGGROUP_KEY . '.{uid}', $this->_proxy('tags', 'handleChangedTagGroup'))
            ->onUpdate(Tags::CONFIG_TAGGROUP_KEY . '.{uid}', $this->_proxy('tags', 'handleChangedTagGroup'))
            ->onRemove(Tags::CONFIG_TAGGROUP_KEY . '.{uid}', $this->_proxy('tags', 'handleDeletedTagGroup'))
            // Categories
            ->onAdd(Categories::CONFIG_CATEGORYROUP_KEY . '.{uid}', $this->_proxy('categories', 'handleChangedCategoryGroup'))
            ->onUpdate(Categories::CONFIG_CATEGORYROUP_KEY . '.{uid}', $this->_proxy('categories', 'handleChangedCategoryGroup'))
            ->onRemove(Categories::CONFIG_CATEGORYROUP_KEY . '.{uid}', $this->_proxy('categories', 'handleDeletedCategoryGroup'))
            // User group permissions
            ->onAdd(UserGroups::CONFIG_USERPGROUPS_KEY . '.{uid}.permissions', $this->_proxy('userPermissions', 'handleChangedGroupPermissions'))
            ->onUpdate(UserGroups::CONFIG_USERPGROUPS_KEY . '.{uid}.permissions', $this->_proxy('userPermissions', 'handleChangedGroupPermissions'))
            ->onRemove(UserGroups::CONFIG_USERPGROUPS_KEY . '.{uid}.permissions', $this->_proxy('userPermissions', 'handleChangedGroupPermissions'))
            // User groups
            ->onAdd(UserGroups::CONFIG_USERPGROUPS_KEY . '.{uid}', $this->_proxy('userGroups', 'handleChangedUserGroup'))
            ->onUpdate(UserGroups::CONFIG_USERPGROUPS_KEY . '.{uid}', $this->_proxy('userGroups', 'handleChangedUserGroup'))
            ->onRemove(UserGroups::CONFIG_USERPGROUPS_KEY . '.{uid}', $this->_proxy('userGroups', 'handleDeletedUserGroup'))
            // User field layout
            ->onAdd(Users::CONFIG_USERLAYOUT_KEY, $this->_proxy('users', 'handleChangedUserFieldLayout'))
            ->onUpdate(Users::CONFIG_USERLAYOUT_KEY, $this->_proxy('users', 'handleChangedUserFieldLayout'))
            ->onRemove(Users::CONFIG_USERLAYOUT_KEY, $this->_proxy('users', 'handleChangedUserFieldLayout'))
            // Global sets
            ->onAdd(Globals::CONFIG_GLOBALSETS_KEY . '.{uid}', $this->_proxy('globals', 'handleChangedGlobalSet'))
            ->onUpdate(Globals::CONFIG_GLOBALSETS_KEY . '.{uid}', $this->_proxy('globals', 'handleChangedGlobalSet'))
            ->onRemove(Globals::CONFIG_GLOBALSETS_KEY . '.{uid}', $this->_proxy('globals', 'handleDeletedGlobalSet'))
            // Sections
            ->onAdd(Sections::CONFIG_SECTIONS_KEY . '.{uid}', $this->_proxy('sections', 'handleChangedSection'))
            ->onUpdate(Sections::CONFIG_SECTIONS_KEY . '.{uid}', $this->_proxy('sections', 'handleChangedSection'))
            ->onRemove(Sections::CONFIG_SECTIONS_KEY . '.{uid}', $this->_proxy('sections', 'handleDeletedSection'))
            // Entry types
            ->onAdd(Sections::CONFIG_ENTRYTYPES_KEY . '.{uid}', $this->_proxy('sections', 'handleChangedEntryType'))
            ->onUpdate(Sections::CONFIG_ENTRYTYPES_KEY . '.{uid}', $this->_proxy('sections', 'handleChangedEntryType'))
            ->onRemove(Sections::CONFIG_ENTRYTYPES_KEY . '.{uid}', $this->_proxy('sections', 'handleDeletedEntryType'))
            // GraphQL schemas
            ->onAdd(Gql::CONFIG_GQL_SCHEMAS_KEY . '.{uid}', $this->_proxy('gql', 'handleChangedSchema'))
            ->onUpdate(Gql::CONFIG_GQL_SCHEMAS_KEY . '.{uid}', $this->_proxy('gql', 'handleChangedSchema'))
            ->onRemove(Gql::CONFIG_GQL_SCHEMAS_KEY . '.{uid}', $this->_proxy('gql', 'handleDeletedSchema'))
            // GraphQL public token
            ->onAdd(Gql::CONFIG_GQL_PUBLIC_TOKEN_KEY, $this->_proxy('gql', 'handleChangedPublicToken'))
            ->onUpdate(Gql::CONFIG_GQL_PUBLIC_TOKEN_KEY, $this->_proxy('gql', 'handleChangedPublicToken'));

        // Prune deleted fields from their layouts
        Event::on(Fields::class, Fields::EVENT_AFTER_DELETE_FIELD, function(FieldEvent $event) {
            $this->getVolumes()->pruneDeletedField($event);
            $this->getTags()->pruneDeletedField($event);
            $this->getCategories()->pruneDeletedField($event);
            $this->getUsers()->pruneDeletedField($event);
            $this->getGlobals()->pruneDeletedField($event);
            $this->getSections()->pruneDeletedField($event);
        });

        // Prune deleted sites from site settings
        Event::on(Sites::class, Sites::EVENT_AFTER_DELETE_SITE, function(DeleteSiteEvent $event) {
            $this->getRoutes()->handleDeletedSite($event);
            $this->getCategories()->pruneDeletedSite($event);
            $this->getSections()->pruneDeletedSite($event);
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
