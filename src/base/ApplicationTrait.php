<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\base;

use Craft;
use craft\console\Application as ConsoleApplication;
use craft\db\Connection;
use craft\db\MigrationManager;
use craft\db\Query;
use craft\errors\DbConnectException;
use craft\events\EditionChangeEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\i18n\Formatter;
use craft\i18n\I18N;
use craft\i18n\Locale;
use craft\models\Info;
use craft\services\Security;
use craft\web\Application as WebApplication;
use craft\web\AssetManager;
use craft\web\View;
use yii\mutex\FileMutex;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;

/**
 * ApplicationTrait
 *
 * @property AssetManager                    $assetManager       The asset manager component
 * @property \craft\services\Assets          $assets             The assets service
 * @property \craft\services\AssetIndexer    $assetIndexing      The asset indexer service
 * @property \craft\services\AssetTransforms $assetTransforms    The asset transforms service
 * @property bool                            $canTestEditions    Whether Craft is running on a domain that is eligible to test out the editions
 * @property bool                            $canUpgradeEdition  Whether Craft is eligible to be upgraded to a different edition
 * @property \craft\services\Categories      $categories         The categories service
 * @property \craft\services\Config          $config             The config service
 * @property \craft\services\Content         $content            The content service
 * @property \craft\db\MigrationManager      $contentMigrator    The content migration manager
 * @property \craft\services\Dashboard       $dashboard          The dashboard service
 * @property Connection                      $db                 The database connection component
 * @property \craft\services\Deprecator      $deprecator         The deprecator service
 * @property \craft\services\ElementIndexes  $elementIndexes     The element indexes service
 * @property \craft\services\Elements        $elements           The elements service
 * @property \craft\services\Entries         $entries            The entries service
 * @property \craft\services\EntryRevisions  $entryRevisions     The entry revisions service
 * @property \craft\services\Et              $et                 The E.T. service
 * @property \craft\feeds\Feeds              $feeds              The feeds service
 * @property \craft\services\Fields          $fields             The fields service
 * @property Formatter                       $formatter          The formatter component
 * @property \craft\services\Globals         $globals            The globals service
 * @property bool                            $hasWrongEdition    Whether Craft is running with the wrong edition
 * @property I18N                            $i18n               The internationalization (i18n) component
 * @property \craft\services\Images          $images             The images service
 * @property bool                            $sInMaintenanceMode Whether the system is in maintenance mode
 * @property bool                            $isInstalled        Whether Craft is installed
 * @property bool                            $sMultiSite         Whether this site has multiple sites
 * @property bool                            $isUpdating         Whether Craft is in the middle of updating itself
 * @property bool                            $isSystemOn         Whether the front end is accepting HTTP requests
 * @property \craft\i18n\Locale              $locale             The Locale object for the target language
 * @property \craft\mail\Mailer              $mailer             The mailer component
 * @property \craft\services\Matrix          $matrix             The matrix service
 * @property \craft\db\MigrationManager      $migrator           The application’s migration manager
 * @property \craft\services\Path            $path               The path service
 * @property \craft\services\Plugins         $plugins            The plugins service
 * @property \craft\services\Relations       $relations          The relations service
 * @property \craft\services\Resources       $resources          The resources service
 * @property \craft\services\Routes          $routes             The routes service
 * @property \craft\services\Search          $search             The search service
 * @property Security                        $security           The security component
 * @property \craft\services\Sections        $sections           The sections service
 * @property \craft\services\Sites           $sites              The sites service
 * @property \craft\services\Structures      $structures         The structures service
 * @property \craft\services\SystemMessages  $systemMessages     The system email messages service
 * @property \craft\services\SystemSettings  $systemSettings     The system settings service
 * @property \craft\services\Tags            $tags               The tags service
 * @property \craft\services\Tasks           $tasks              The tasks service
 * @property \craft\services\TemplateCaches  $templateCaches     The template caches service
 * @property \craft\services\Tokens          $tokens             The tokens service
 * @property \craft\services\Updates         $updates            The updates service
 * @property \craft\services\UserGroups      $userGroups         The user groups service
 * @property \craft\services\UserPermissions $userPermissions    The user permissions service
 * @property \craft\services\Users           $users              The users service
 * @property \craft\services\Utilities       $utilities          The utilities service
 * @property View                            $view               The view component
 * @property \craft\services\Volumes         $volumes            The volumes service
 *
 * @method AssetManager getAssetManager() Returns the asset manager component.
 * @method Connection   getDb()           Returns the database connection component.
 * @method Formatter    getFormatter()    Returns the formatter component.
 * @method I18N         getI18n()         Returns the internationalization (i18n) component.
 * @method Security     getSecurity()     Returns the security component.
 * @method View         getView()         Returns the view component.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
trait ApplicationTrait
{
    // Properties
    // =========================================================================

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
     * @var
     */
    private $_isInstalled;

    /**
     * @var
     */
    private $_isMultiSite;

    /**
     * @var
     */
    private $_info;

    /**
     * @var bool|null
     */
    private $_isDbConfigValid;

    /**
     * @var bool|null
     */
    private $_isDbConnectionValid;

    /**
     * @var bool
     */
    private $_gettingLanguage = false;

    /**
     * @var string|null The stored version
     * @todo Remove this after the next breakpoint
     */
    private $_storedVersion;

    // Public Methods
    // =========================================================================

    /**
     * Returns the target app language.
     *
     * @param bool $useUserLanguage Whether the user's preferred language should be used.
     *
     * @return string|null
     */
    public function getTargetLanguage(bool $useUserLanguage = true)
    {
        /** @var WebApplication|ConsoleApplication $this */
        if ($this->getIsInstalled()) {
            $request = $this->getRequest();
            $currentSite = $this->getSites()->currentSite;

            // Will any site validation be necessary here?
            if ($useUserLanguage || $currentSite) {
                if ($useUserLanguage) {
                    $language = 'auto';
                } else {
                    $language = $currentSite->language;
                }

                // Get the list of actual site languages
                $siteLanguages = $this->getI18n()->getSiteLocaleIds();

                // Is it set to "auto"?
                if ($language === 'auto') {
                    // Place this within a try/catch in case userSession is being fussy.
                    try {
                        // If the user is logged in *and* has a primary language set, use that
                        $user = $this->getUser()->getIdentity();

                        if ($user && ($preferredLanguage = $user->getPreferredLanguage()) !== null) {
                            return $preferredLanguage;
                        }
                    } catch (\Exception $e) {
                        Craft::error('Tried to determine the user’s preferred language, but got this exception: '.$e->getMessage(), __METHOD__);
                    }

                    // Is there a default CP language?
                    if ($defaultCpLanguage = Craft::$app->getConfig()->getGeneral()->defaultCpLanguage) {
                        // Make sure it's one of the site languages
                        $defaultCpLanguage = StringHelper::toLowerCase($defaultCpLanguage);

                        if (in_array($defaultCpLanguage, $siteLanguages, true)) {
                            return $defaultCpLanguage;
                        }
                    }

                    // Otherwise check if the browser's preferred language matches any of the site languages
                    if (!$request->getIsConsoleRequest()) {
                        $browserLanguages = $request->getAcceptableLanguages();

                        if ($browserLanguages) {
                            foreach ($browserLanguages as $browserLanguage) {
                                if (in_array($browserLanguage, $siteLanguages, true)) {
                                    return $browserLanguage;
                                }
                            }
                        }
                    }
                } // Is it set to a valid site language?
                else if (in_array($language, $siteLanguages, true)) {
                    return $language;
                }
            }

            if (!$this->getIsUpdating()) {
                // Use the primary site's language by default
                return $this->getSites()->getPrimarySite()->language;
            }
        }

        return $this->_getFallbackLanguage();
    }

    /**
     * Returns whether Craft is installed.
     *
     * @return bool
     */
    public function getIsInstalled(): bool
    {
        /** @var WebApplication|ConsoleApplication $this */
        if ($this->_isInstalled !== null) {
            return $this->_isInstalled;
        }

        try {
            // Initialize the DB connection
            $this->getDb();

            // If the db config isn't valid, then we'll assume it's not installed.
            if (!$this->getIsDbConnectionValid()) {
                return false;
            }
        } catch (DbConnectException $e) {
            return false;
        }

        return $this->_isInstalled = (bool)($this->getRequest()->getIsConsoleRequest() || $this->getDb()->tableExists('{{%info}}', false));
    }

    /**
     * Tells Craft that it's installed now.
     *
     * @return void
     */
    public function setIsInstalled()
    {
        /** @var WebApplication|ConsoleApplication $this */
        // If you say so!
        $this->_isInstalled = true;
    }

    /**
     * Returns whether Craft is in the middle of updating itself.
     *
     * @return bool
     */
    public function getIsUpdating(): bool
    {
        /** @var WebApplication|ConsoleApplication $this */
        if ($this->getUpdates()->getIsCraftDbMigrationNeeded()) {
            return true;
        }

        $request = $this->getRequest();

        if ($this->getIsInMaintenanceMode() && $request->getIsCpRequest()) {
            return true;
        }

        if (!$request->getIsConsoleRequest()) {
            $actionSegments = $request->getActionSegments();

            if (
                $actionSegments === ['update', 'cleanUp'] ||
                $actionSegments === ['update', 'rollback']
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether this Craft install has multiple sites.
     *
     * @return bool
     */
    public function getIsMultiSite(): bool
    {
        /** @var WebApplication|ConsoleApplication $this */
        if ($this->_isMultiSite !== null) {
            return $this->_isMultiSite;
        }

        return $this->_isMultiSite = (count($this->getSites()->getAllSites()) > 1);
    }

    /**
     * Returns the Craft edition.
     *
     * @return int
     */
    public function getEdition(): int
    {
        /** @var WebApplication|ConsoleApplication $this */
        return (int)$this->getInfo()->edition;
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
     *
     * @return bool
     */
    public function setEdition(int $edition): bool
    {
        /** @var WebApplication|ConsoleApplication $this */
        $info = $this->getInfo();
        $oldEdition = $info->edition;
        $info->edition = $edition;

        $success = $this->saveInfo($info);

        if ($success === true && !$this->getRequest()->getIsConsoleRequest()) {
            // Fire an 'afterEditionChange' event
            $this->trigger(WebApplication::EVENT_AFTER_EDITION_CHANGE,
                new EditionChangeEvent([
                    'oldEdition' => $oldEdition,
                    'newEdition' => $edition
                ]));
        }

        return $success;
    }

    /**
     * Requires that Craft is running an equal or better edition than what's passed in
     *
     * @param int  $edition  The Craft edition to require.
     * @param bool $orBetter If true, makes $edition the minimum edition required.
     *
     * @return void
     * @throws BadRequestHttpException if attempting to do something not allowed by the current Craft edition
     */
    public function requireEdition(int $edition, bool $orBetter = true)
    {
        /** @var WebApplication|ConsoleApplication $this */
        if ($this->getIsInstalled()) {
            $installedEdition = $this->getEdition();

            if (($orBetter && $installedEdition < $edition) || (!$orBetter && $installedEdition !== $edition)) {
                $editionName = App::editionName($edition);
                throw new BadRequestHttpException("Craft {$editionName} is required for this");
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
        // Only admins can upgrade Craft
        if ($this->getUser()->getIsAdmin()) {
            // Are they either *using* or *licensed to use* something < Craft Pro?
            $activeEdition = $this->getEdition();
            $licensedEdition = $this->getLicensedEdition();

            return (
                ($activeEdition < Craft::Pro) ||
                ($licensedEdition !== null && $licensedEdition < Craft::Pro)
            );
        } else {
            return false;
        }
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

        return (!$request->getIsConsoleRequest() && $this->getCache()->get('editionTestableDomain@'.$request->getHostName()) === 1);
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
     * Returns whether the front end is accepting HTTP requests.
     *
     * @return bool
     */
    public function getIsSystemOn(): bool
    {
        /** @var WebApplication|ConsoleApplication $this */
        if (is_bool($on = $this->getConfig()->getGeneral()->isSystemOn)) {
            return $on;
        }

        return (bool)$this->getInfo()->on;
    }

    /**
     * Returns whether the system is in maintenance mode.
     *
     * @return bool
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
     */
    public function disableMaintenanceMode(): bool
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->_setMaintenanceMode(false);
    }

    /**
     * Returns the info model, or just a particular attribute.
     *
     * @return Info
     * @throws ServerErrorHttpException if the info table is missing its row
     */
    public function getInfo(): Info
    {
        /** @var WebApplication|ConsoleApplication $this */
        if ($this->_info !== null) {
            return $this->_info;
        }

        if (!$this->getIsInstalled()) {
            return new Info();
        }

        $row = (new Query())
            ->from(['{{%info}}'])
            ->one();

        if (!$row) {
            $tableName = $this->getDb()->getSchema()->getRawTableName('{{%info}}');
            throw new ServerErrorHttpException("The {$tableName} table is missing its row");
        }

        // TODO: Remove this after the next breakpoint
        $this->_storedVersion = $row['version'];
        if (isset($row['build'])) {
            $version = $row['version'];

            switch ($row['track']) {
                case 'dev':
                    $version .= '.0-alpha.'.$row['build'];
                    break;
                case 'beta':
                    $version .= '.0-beta.'.$row['build'];
                    break;
                default:
                    $version .= '.'.$row['build'];
                    break;
            }

            $row['version'] = $version;
        }
        if (isset($row['siteName'])) {
            $row['name'] = $row['siteName'];
        }
        unset($row['siteName'], $row['siteUrl'], $row['build'], $row['releaseDate'], $row['track']);

        return $this->_info = new Info($row);
    }

    /**
     * Updates the info row.
     *
     * @param Info $info
     *
     * @return bool
     */
    public function saveInfo(Info $info): bool
    {
        /** @var WebApplication|ConsoleApplication $this */
        if ($info->validate()) {
            $attributes = Db::prepareValuesForDb($info);

            // TODO: Remove this after the next breakpoint
            unset($attributes['build'], $attributes['releaseDate'], $attributes['track']);

            if (array_key_exists('id', $attributes) && $attributes['id'] === null) {
                unset($attributes['id']);
            }

            if ($this->getIsInstalled()) {
                // TODO: Remove this after the next breakpoint
                if (version_compare($this->_storedVersion, '3.0', '<')) {
                    $infoTable = $this->getDb()->getTableSchema('{{%info}}');

                    if ($infoTable->getColumn('siteName')) {
                        $siteName = $attributes['name'];
                        $attributes['siteName'] = $siteName;
                        unset($attributes['name']);
                    }

                    unset($attributes['fieldVersion']);
                }

                $this->getDb()->createCommand()
                    ->update('{{%info}}', $attributes)
                    ->execute();
            } else {
                $this->getDb()->createCommand()
                    ->insert('{{%info}}', $attributes)
                    ->execute();

                if (Craft::$app->getIsInstalled()) {
                    // Set the new id
                    $info->id = $this->getDb()->getLastInsertID('{{%info}}');
                }
            }

            // Use this as the new cached Info
            $this->_info = $info;

            return true;
        }

        return false;
    }

    /**
     * Returns the Yii framework version.
     *
     * @return mixed
     */
    public function getYiiVersion()
    {
        return \Yii::getVersion();
    }

    /**
     * Don't even think of moving this check into Connection->init().
     *
     * @return bool
     */
    public function getIsDbConnectionValid(): bool
    {
        /** @var WebApplication|ConsoleApplication $this */
        if ($this->_isDbConnectionValid !== null) {
            return $this->_isDbConnectionValid;
        }

        try {
            $this->getDb()->open();

            return $this->_isDbConnectionValid = true;
        } catch (DbConnectException $e) {
            return $this->_isDbConnectionValid = false;
        }
    }

    // Service Getters
    // -------------------------------------------------------------------------

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
     */
    public function getEntryRevisions()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('entryRevisions');
    }

    /**
     * Returns the E.T. service.
     *
     * @return \craft\services\Et The E.T. service
     */
    public function getEt()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('et');
    }

    /**
     * Returns the feeds service.
     *
     * @return \craft\feeds\Feeds The feeds service
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
     * @return FileMutex The application’s mutex service
     */
    public function getMutex(): FileMutex
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
     * Returns the resources service.
     *
     * @return \craft\services\Resources The resources service
     */
    public function getResources()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('resources');
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
     * Returns the tasks service.
     *
     * @return \craft\services\Tasks The tasks service
     */
    public function getTasks()
    {
        /** @var WebApplication|ConsoleApplication $this */
        return $this->get('tasks');
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

    // Private Methods
    // =========================================================================

    /**
     * Initializes the application component
     */
    private function _init()
    {
        $this->getLog();

        // Set the edition components
        $this->_setEditionComponents();

        // Set the timezone
        $this->_setTimeZone();

        // Load the plugins
        // (this has to happen before setting the language, so plugin class aliases are registered in time)
        $this->getPlugins()->loadPlugins();

        // Set the language
        $this->_setLanguage();

        // Fire an 'afterInit' event
        $this->trigger(WebApplication::EVENT_AFTER_INIT);
    }

    /**
     * Sets the target application language.
     */
    private function _setLanguage()
    {
        /** @var WebApplication|ConsoleApplication $this */
        // Defend against an infinite _setLanguage() loop
        if ($this->_gettingLanguage === false) {
            $this->_gettingLanguage = true;
            $request = $this->getRequest();
            $useUserLanguage = $request->getIsConsoleRequest() || $request->getIsCpRequest();
            $targetLanguage = $this->getTargetLanguage($useUserLanguage);
            $this->language = $targetLanguage;
        } else {
            // We tried to get the language, but something went wrong. Use fallback to prevent infinite loop.
            $fallbackLanguage = $this->_getFallbackLanguage();
            $this->_gettingLanguage = false;
            $this->language = $fallbackLanguage;
        }
    }

    /**
     * Sets the system timezone.
     */
    private function _setTimeZone()
    {
        /** @var WebApplication|ConsoleApplication $this */
        $timezone = $this->getConfig()->getGeneral()->timezone;

        if (!$timezone) {
            $timezone = $this->getInfo()->timezone;
        }

        if ($timezone) {
            $this->setTimeZone($timezone);
        }
    }

    /**
     * Enables or disables Maintenance Mode
     *
     * @param bool $value
     *
     * @return bool
     */
    private function _setMaintenanceMode(bool $value): bool
    {
        /** @var WebApplication|ConsoleApplication $this */
        $info = $this->getInfo();
        $info->maintenance = $value;

        return $this->saveInfo($info);
    }

    /**
     * Tries to find a language match with the user's browser's preferred language(s).
     * If not uses the app's sourceLanguage.
     *
     * @return string
     */
    private function _getFallbackLanguage(): string
    {
        /** @var WebApplication|ConsoleApplication $this */
        // See if we have the CP translated in one of the user's browsers preferred language(s)
        if (
            $this instanceof WebApplication &&
            ($language = $this->getTranslatedBrowserLanguage()) !== false
        ) {
            return $language;
        }

        // Default to the source language.
        return $this->sourceLanguage;
    }

    /**
     * Sets the edition components.
     *
     * @return void
     */
    private function _setEditionComponents()
    {
        /** @var WebApplication|ConsoleApplication $this */
        // Set the appropriate edition components
        $edition = $this->getEdition();

        if ($edition === Craft::Client || $edition === Craft::Pro) {
            $basePath = $this->getBasePath().'/config/app';
            $config = ArrayHelper::merge(
                require $basePath.'/client.php',
                $edition === Craft::Pro ? require $basePath.'/pro.php' : []
            );
            Craft::configure($this, $config);
        }
    }
}
