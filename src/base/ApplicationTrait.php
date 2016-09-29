<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\base;

use Craft;
use craft\app\dates\DateTime;
use craft\app\db\Connection;
use craft\app\db\MigrationManager;
use craft\app\db\Query;
use craft\app\errors\DbConnectException;
use craft\app\events\EditionChangeEvent;
use craft\app\helpers\App;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\Db;
use craft\app\helpers\StringHelper;
use craft\app\i18n\Formatter;
use craft\app\i18n\I18N;
use craft\app\i18n\Locale;
use craft\app\models\Info;
use craft\app\services\Config;
use craft\app\services\Security;
use craft\app\web\Application as WebApplication;
use craft\app\web\AssetManager;
use craft\app\web\View;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;

/**
 * ApplicationTrait
 *
 * @property AssetManager                        $assetManager       The asset manager component
 * @property \craft\app\services\Assets          $assets             The assets service
 * @property \craft\app\services\AssetIndexer    $assetIndexing      The asset indexer service
 * @property \craft\app\services\AssetTransforms $assetTransforms    The asset transforms service
 * @property boolean                             $canTestEditions    Whether Craft is running on a domain that is eligible to test out the editions
 * @property boolean                             $canUpgradeEdition  Whether Craft is eligible to be upgraded to a different edition
 * @property \craft\app\services\Categories      $categories         The categories service
 * @property \craft\app\services\Config          $config             The config service
 * @property \craft\app\services\Content         $content            The content service
 * @property \craft\app\db\MigrationManager      $contentMigrator    The content migration manager
 * @property \craft\app\services\Dashboard       $dashboard          The dashboard service
 * @property Connection                          $db                 The database connection component
 * @property \craft\app\services\Deprecator      $deprecator         The deprecator service
 * @property \craft\app\services\ElementIndexes  $elementIndexes     The element indexes service
 * @property \craft\app\services\Elements        $elements           The elements service
 * @property \craft\app\services\EmailMessages   $emailMessages      The email messages service
 * @property \craft\app\services\Entries         $entries            The entries service
 * @property \craft\app\services\EntryRevisions  $entryRevisions     The entry revisions service
 * @property \craft\app\services\Et              $et                 The E.T. service
 * @property \craft\app\services\Feeds           $feeds              The feeds service
 * @property \craft\app\services\Fields          $fields             The fields service
 * @property Formatter                           $formatter          The formatter component
 * @property \craft\app\services\Globals         $globals            The globals service
 * @property boolean                             $hasWrongEdition    Whether Craft is running with the wrong edition
 * @property I18N                                $i18n               The internationalization (i18n) component
 * @property \craft\app\services\Images          $images             The images service
 * @property boolean                             $sInMaintenanceMode Whether the system is in maintenance mode
 * @property boolean                             $isInstalled        Whether Craft is installed
 * @property boolean                             $sMultiSite         Whether this site has multiple sites
 * @property boolean                             $isUpdating         Whether Craft is in the middle of updating itself
 * @property boolean                             $isSystemOn         Whether the front end is accepting HTTP requests
 * @property \craft\app\i18n\Locale              $locale             The Locale object for the target language
 * @property \craft\app\mail\Mailer              $mailer             The mailer component
 * @property \craft\app\services\Matrix          $matrix             The matrix service
 * @property \craft\app\db\MigrationManager      $migrator           The application’s migration manager
 * @property \craft\app\services\Path            $path               The path service
 * @property \craft\app\services\Plugins         $plugins            The plugins service
 * @property \craft\app\services\Relations       $relations          The relations service
 * @property \craft\app\services\Resources       $resources          The resources service
 * @property \craft\app\services\Routes          $routes             The routes service
 * @property \craft\app\services\Search          $search             The search service
 * @property Security                            $security           The security component
 * @property \craft\app\services\Sections        $sections           The sections service
 * @property \craft\app\services\Sites           $sites              The sites service
 * @property \craft\app\services\Structures      $structures         The structures service
 * @property \craft\app\services\SystemSettings  $systemSettings     The system settings service
 * @property \craft\app\services\Tags            $tags               The tags service
 * @property \craft\app\services\Tasks           $tasks              The tasks service
 * @property \craft\app\services\TemplateCaches  $templateCaches     The template caches service
 * @property \craft\app\services\Tokens          $tokens             The tokens service
 * @property \craft\app\services\Updates         $updates            The updates service
 * @property \craft\app\services\UserGroups      $userGroups         The user groups service
 * @property \craft\app\services\UserPermissions $userPermissions    The user permissions service
 * @property \craft\app\services\Users           $users              The users service
 * @property View                                $view               The view component
 * @property \craft\app\services\Volumes         $volumes            The volumes service
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
     * @var
     */
    private $_siteName;

    /**
     * @var
     */
    private $_siteUrl;

    /**
     * @var bool
     */
    private $_isDbConfigValid;

    /**
     * @var bool
     */
    private $_isDbConnectionValid;

    /**
     * @var bool
     */
    private $_gettingLanguage = false;

    /**
     * @var string Craft’s build number.
     */
    public $build;

    /**
     * @var string Craft’s schema version number.
     */
    public $schemaVersion;

    /**
     * @var DateTime Craft’s release date.
     */
    public $releaseDate;

    /**
     * @var string The minimum Craft build number required to update to this build.
     */
    public $minBuildRequired;

    /**
     * @var string The URL to download the minimum Craft version.
     * @see $minBuildRequired
     */
    public $minBuildUrl;

    /**
     * @var string The release track Craft is running on.
     */
    public $track;

    /**
     * @var string The stored version
     * @todo Remove this after the next breakpoint
     */
    private $_storedVersion;

    // Public Methods
    // =========================================================================

    /**
     * Returns the target app language.
     *
     * @param boolean $useUserLanguage Whether the user's preferred language should be used.
     *
     * @return string|null
     */
    public function getTargetLanguage($useUserLanguage = true)
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
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
                if ($language == 'auto') {
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
                    if ($defaultCpLanguage = Craft::$app->getConfig()->get('defaultCpLanguage')) {
                        // Make sure it's one of the site languages
                        $defaultCpLanguage = StringHelper::toLowerCase($defaultCpLanguage);

                        if (in_array($defaultCpLanguage, $siteLanguages)) {
                            return $defaultCpLanguage;
                        }
                    }

                    // Otherwise check if the browser's preferred language matches any of the site languages
                    if (!$request->getIsConsoleRequest()) {
                        $browserLanguages = $request->getAcceptableLanguages();

                        if ($browserLanguages) {
                            foreach ($browserLanguages as $browserLanguage) {
                                if (in_array($browserLanguage, $siteLanguages)) {
                                    return $browserLanguage;
                                }
                            }
                        }
                    }
                } // Is it set to a valid site language?
                else if (in_array($language, $siteLanguages)) {
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
     * @return boolean
     */
    public function getIsInstalled()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        if (!isset($this->_isInstalled)) {
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

            $this->_isInstalled = (bool)($this->getRequest()->getIsConsoleRequest() || $this->getDb()->tableExists('{{%info}}',
                    false));
        }

        return $this->_isInstalled;
    }

    /**
     * Tells Craft that it's installed now.
     *
     * @return void
     */
    public function setIsInstalled()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        // If you say so!
        $this->_isInstalled = true;
    }

    /**
     * Returns whether Craft is in the middle of updating itself.
     *
     * @return boolean
     */
    public function getIsUpdating()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
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
                $actionSegments == ['update', 'cleanUp'] ||
                $actionSegments == ['update', 'rollback']
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether this Craft install has multiple sites.
     *
     * @return boolean
     */
    public function getIsMultiSite()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        if (!isset($this->_isMultiSite)) {
            $this->_isMultiSite = (count($this->getSites()->getAllSites()) > 1);
        }

        return $this->_isMultiSite;
    }

    /**
     * Returns the Craft edition.
     *
     * @return integer
     */
    public function getEdition()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return (int)$this->getInfo('edition');
    }

    /**
     * Returns the name of the Craft edition.
     *
     * @return string
     */
    public function getEditionName()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return App::getEditionName($this->getEdition());
    }

    /**
     * Returns the edition Craft is actually licensed to run in.
     *
     * @return integer|null
     */
    public function getLicensedEdition()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
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
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        $licensedEdition = $this->getLicensedEdition();

        if ($licensedEdition !== null) {
            return App::getEditionName($licensedEdition);
        }

        return null;
    }

    /**
     * Returns whether Craft is running with the wrong edition.
     *
     * @return boolean
     */
    public function getHasWrongEdition()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        $licensedEdition = $this->getLicensedEdition();

        return ($licensedEdition !== null && $licensedEdition != $this->getEdition() && !$this->getCanTestEditions());
    }

    /**
     * Sets the Craft edition.
     *
     * @param integer $edition The edition to set.
     *
     * @return boolean
     */
    public function setEdition($edition)
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
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
     * @param integer $edition  The Craft edition to require.
     * @param boolean $orBetter If true, makes $edition the minimum edition required.
     *
     * @return void
     * @throws BadRequestHttpException if attempting to do something not allowed by the current Craft edition
     */
    public function requireEdition($edition, $orBetter = true)
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        if ($this->getIsInstalled()) {
            $installedEdition = $this->getEdition();

            if (($orBetter && $installedEdition < $edition) || (!$orBetter && $installedEdition !== $edition)) {
                $editionName = App::getEditionName($edition);
                throw new BadRequestHttpException("Craft {$editionName} is required for this");
            }
        }
    }

    /**
     * Returns whether Craft is eligible to be upgraded to a different edition.
     *
     * @return boolean
     */
    public function getCanUpgradeEdition()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
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
     * @return boolean
     */
    public function getCanTestEditions()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        $request = $this->getRequest();

        return (!$request->getIsConsoleRequest() && $this->getCache()->get('editionTestableDomain@'.$request->getHostName()) == 1);
    }

    /**
     * Returns the system's UID.
     *
     * @return string
     */
    public function getSystemUid()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->getInfo('uid');
    }

    /**
     * Returns whether the front end is accepting HTTP requests.
     *
     * @return boolean
     */
    public function getIsSystemOn()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        if (is_bool($on = $this->getConfig()->get('isSystemOn'))) {
            return $on;
        }

        return (bool)$this->getInfo('on');
    }

    /**
     * Returns whether the system is in maintenance mode.
     *
     * @return boolean
     */
    public function getIsInMaintenanceMode()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return (bool)$this->getInfo('maintenance');
    }

    /**
     * Enables Maintenance Mode.
     *
     * @return boolean
     */
    public function enableMaintenanceMode()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->_setMaintenanceMode(1);
    }

    /**
     * Disables Maintenance Mode.
     *
     * @return boolean
     */
    public function disableMaintenanceMode()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->_setMaintenanceMode(0);
    }

    /**
     * Returns the info model, or just a particular attribute.
     *
     * @param string|null $attribute
     *
     * @return Info|string
     * @throws ServerErrorHttpException if the info table is missing its row
     */
    public function getInfo($attribute = null)
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        if (!isset($this->_info)) {
            if ($this->getIsInstalled()) {
                $row = (new Query())
                    ->from('{{%info}}')
                    ->one();

                if (!$row) {
                    $tableName = $this->getDb()->getSchema()->getRawTableName('{{%info}}');
                    throw new ServerErrorHttpException("The {$tableName} table is missing its row");
                }

                // TODO: Remove this after the next breakpoint
                $this->_storedVersion = $row['version'];

                // Prevent an infinite loop in toDateTime.
                $row['releaseDate'] = DateTimeHelper::toDateTime($row['releaseDate'], false, false);

                $this->_info = Info::create($row);
            } else {
                $this->_info = new Info();
            }
        }

        if ($attribute) {
            return $this->_info->$attribute;
        }

        return $this->_info;
    }

    /**
     * Updates the info row.
     *
     * @param Info $info
     *
     * @return boolean
     */
    public function saveInfo(Info $info)
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        if ($info->validate()) {
            $attributes = Db::prepareValuesForDb($info);

            if ($this->getIsInstalled()) {
                // TODO: Remove this after the next breakpoint
                if (version_compare($this->_storedVersion, '3.0', '<')) {
                    unset($attributes['fieldVersion']);
                }

                $this->getDb()->createCommand()
                    ->update('{{%info}}', $attributes)
                    ->execute();
            } else {
                $this->getDb()->createCommand()
                    ->insert('{{%info}}', $attributes)
                    ->execute();

                // Set the new id
                $info->id = $this->getDb()->getLastInsertID();
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
     * Make sure the basics are in place in the db connection file before we
     * actually try to connect later on.
     *
     * @throws DbConnectException
     * @return boolean Whether the config file is valid
     */
    public function validateDbConfigFile()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        if ($this->_isDbConfigValid === null) {
            $messages = [];

            $databaseServerName = $this->getConfig()->get('server', Config::CATEGORY_DB);
            $databaseAuthName = $this->getConfig()->get('user', Config::CATEGORY_DB);
            $databaseName = $this->getConfig()->get('database', Config::CATEGORY_DB);
            $databasePort = $this->getConfig()->get('port', Config::CATEGORY_DB);
            $databaseCharset = $this->getConfig()->get('charset', Config::CATEGORY_DB);
            $databaseCollation = $this->getConfig()->get('collation', Config::CATEGORY_DB);

            if (!$databaseServerName) {
                $messages[] = Craft::t('app', 'The database server name isn’t set in your db config file.');
            }

            if (!$databaseAuthName) {
                $messages[] = Craft::t('app', 'The database user name isn’t set in your db config file.');
            }

            if (!$databaseName) {
                $messages[] = Craft::t('app', 'The database name isn’t set in your db config file.');
            }

            if (!$databasePort) {
                $messages[] = Craft::t('app', 'The database port isn’t set in your db config file.');
            }

            if (!$databaseCharset) {
                $messages[] = Craft::t('app', 'The database charset isn’t set in your db config file.');
            }

            if (!$databaseCollation) {
                $messages[] = Craft::t('app', 'The database collation isn’t set in your db config file.');
            }

            if (!empty($messages)) {
                $this->_isDbConfigValid = false;
                throw new DbConnectException(Craft::t('app', 'Database configuration errors: {errors}', ['errors' => implode(PHP_EOL, $messages)]));
            }

            $this->_isDbConfigValid = true;
        }

        return $this->_isDbConfigValid;
    }

    /**
     * Don't even think of moving this check into Connection->init().
     *
     * @return boolean
     */
    public function getIsDbConnectionValid()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        if (!isset($this->_isDbConnectionValid)) {
            try {
                $this->getDb()->open();
                $this->_isDbConnectionValid = true;

            } catch (DbConnectException $e) {
                $this->_isDbConnectionValid = false;
            }
        }

        return $this->_isDbConnectionValid;
    }

    /**
     * Don't even think of moving this check into Connection->init().
     *
     * @param $value
     */
    public function setIsDbConnectionValid($value)
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        $this->_isDbConnectionValid = $value;
    }

    // Service Getters
    // -------------------------------------------------------------------------

    /**
     * Returns the assets service.
     *
     * @return \craft\app\services\Assets The assets service
     */
    public function getAssets()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('assets');
    }

    /**
     * Returns the asset indexing service.
     *
     * @return \craft\app\services\AssetIndexer The asset indexing service
     */
    public function getAssetIndexer()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('assetIndexer');
    }

    /**
     * Returns the asset transforms service.
     *
     * @return \craft\app\services\AssetTransforms The asset transforms service
     */
    public function getAssetTransforms()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('assetTransforms');
    }

    /**
     * Returns the categories service.
     *
     * @return \craft\app\services\Categories The categories service
     */
    public function getCategories()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('categories');
    }

    /**
     * Returns the config service.
     *
     * @return \craft\app\services\Config The config service
     */
    public function getConfig()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('config');
    }

    /**
     * Returns the content service.
     *
     * @return \craft\app\services\Content The content service
     */
    public function getContent()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('content');
    }

    /**
     * Returns the content migration manager.
     *
     * @return MigrationManager The content migration manager
     */
    public function getContentMigrator()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('contentMigrator');
    }

    /**
     * Returns the dashboard service.
     *
     * @return \craft\app\services\Dashboard The dashboard service
     */
    public function getDashboard()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('dashboard');
    }

    /**
     * Returns the deprecator service.
     *
     * @return \craft\app\services\Deprecator The deprecator service
     */
    public function getDeprecator()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('deprecator');
    }

    /**
     * Returns the element indexes service.
     *
     * @return \craft\app\services\ElementIndexes The element indexes service
     */
    public function getElementIndexes()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('elementIndexes');
    }

    /**
     * Returns the elements service.
     *
     * @return \craft\app\services\Elements The elements service
     */
    public function getElements()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('elements');
    }

    /**
     * Returns the email messages service.
     *
     * @return \craft\app\services\EmailMessages The email messages service
     */
    public function getEmailMessages()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('emailMessages');
    }

    /**
     * Returns the entries service.
     *
     * @return \craft\app\services\Entries The entries service
     */
    public function getEntries()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('entries');
    }

    /**
     * Returns the entry revisions service.
     *
     * @return \craft\app\services\EntryRevisions The entry revisions service
     */
    public function getEntryRevisions()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('entryRevisions');
    }

    /**
     * Returns the E.T. service.
     *
     * @return \craft\app\services\Et The E.T. service
     */
    public function getEt()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('et');
    }

    /**
     * Returns the feeds service.
     *
     * @return \craft\app\services\Feeds The feeds service
     */
    public function getFeeds()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('feeds');
    }

    /**
     * Returns the fields service.
     *
     * @return \craft\app\services\Fields The fields service
     */
    public function getFields()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('fields');
    }

    /**
     * Returns the globals service.
     *
     * @return \craft\app\services\Globals The globals service
     */
    public function getGlobals()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('globals');
    }

    /**
     * Returns the images service.
     *
     * @return \craft\app\services\Images The images service
     */
    public function getImages()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('images');
    }

    /**
     * Returns a Locale object for the target language.
     *
     * @return Locale The Locale object for the target language
     */
    public function getLocale()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('locale');
    }

    /**
     * Returns the current mailer.
     *
     * @return \craft\app\mail\Mailer The mailer component
     */
    public function getMailer()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('mailer');
    }

    /**
     * Returns the matrix service.
     *
     * @return \craft\app\services\Matrix The matrix service
     */
    public function getMatrix()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('matrix');
    }

    /**
     * Returns the application’s migration manager.
     *
     * @return MigrationManager The application’s migration manager
     */
    public function getMigrator()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('migrator');
    }

    /**
     * Returns the path service.
     *
     * @return \craft\app\services\Path The path service
     */
    public function getPath()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('path');
    }

    /**
     * Returns the plugins service.
     *
     * @return \craft\app\services\Plugins The plugins service
     */
    public function getPlugins()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('plugins');
    }

    /**
     * Returns the relations service.
     *
     * @return \craft\app\services\Relations The relations service
     */
    public function getRelations()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('relations');
    }

    /**
     * Returns the resources service.
     *
     * @return \craft\app\services\Resources The resources service
     */
    public function getResources()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('resources');
    }

    /**
     * Returns the routes service.
     *
     * @return \craft\app\services\Routes The routes service
     */
    public function getRoutes()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('routes');
    }

    /**
     * Returns the search service.
     *
     * @return \craft\app\services\Search The search service
     */
    public function getSearch()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('search');
    }

    /**
     * Returns the sections service.
     *
     * @return \craft\app\services\Sections The sections service
     */
    public function getSections()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('sections');
    }

    /**
     * Returns the sites service.
     *
     * @return \craft\app\services\Sites The sites service
     */
    public function getSites()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('sites');
    }

    /**
     * Returns the structures service.
     *
     * @return \craft\app\services\Structures The structures service
     */
    public function getStructures()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('structures');
    }

    /**
     * Returns the system settings service.
     *
     * @return \craft\app\services\SystemSettings The system settings service
     */
    public function getSystemSettings()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('systemSettings');
    }

    /**
     * Returns the tags service.
     *
     * @return \craft\app\services\Tags The tags service
     */
    public function getTags()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('tags');
    }

    /**
     * Returns the tasks service.
     *
     * @return \craft\app\services\Tasks The tasks service
     */
    public function getTasks()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('tasks');
    }

    /**
     * Returns the template cache service.
     *
     * @return \craft\app\services\TemplateCaches The template caches service
     */
    public function getTemplateCaches()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('templateCaches');
    }

    /**
     * Returns the tokens service.
     *
     * @return \craft\app\services\Tokens The tokens service
     */
    public function getTokens()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('tokens');
    }

    /**
     * Returns the updates service.
     *
     * @return \craft\app\services\Updates The updates service
     */
    public function getUpdates()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('updates');
    }

    /**
     * Returns the user groups service.
     *
     * @return \craft\app\services\UserGroups The user groups service
     */
    public function getUserGroups()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('userGroups');
    }

    /**
     * Returns the user permissions service.
     *
     * @return \craft\app\services\UserPermissions The user permissions service
     */
    public function getUserPermissions()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('userPermissions');
    }

    /**
     * Returns the users service.
     *
     * @return \craft\app\services\Users The users service
     */
    public function getUsers()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        return $this->get('users');
    }

    /**
     * Returns the volumes service.
     *
     * @return \craft\app\services\Volumes The volumes service
     */
    public function getVolumes()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
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

        // If there is a custom appId set, apply it here.
        if ($appId = $this->getConfig()->get('appId')) {
            $this->id = $appId;
        }

        // Validate some basics on the database configuration file.
        $this->validateDbConfigFile();

        // Set the edition components
        $this->_setEditionComponents();

        // Set the timezone
        $this->_setTimeZone();

        // Set the language
        $this->_setLanguage();

        // Load the plugins
        $this->getPlugins()->loadPlugins();

        // Fire an 'afterInit' event
        $this->trigger(WebApplication::EVENT_AFTER_INIT);
    }

    /**
     * Sets the target application language.
     */
    private function _setLanguage()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        // Defend against an infinite _setLanguage() loop
        if (!$this->_gettingLanguage) {
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
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        $timezone = $this->getConfig()->get('timezone');

        if (!$timezone) {
            $timezone = $this->getInfo('timezone');
        }

        if ($timezone) {
            $this->setTimeZone($timezone);
        }
    }

    /**
     * Enables or disables Maintenance Mode
     *
     * @param boolean $value
     *
     * @return boolean
     */
    private function _setMaintenanceMode($value)
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
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
    private function _getFallbackLanguage()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        if ($this instanceof \craft\app\web\Application) {
            // See if we have the CP translated in one of the user's browsers preferred language(s)
            $language = $this->getTranslatedBrowserLanguage();
        }

        // Default to the source language.
        if (empty($language)) {
            $language = $this->sourceLanguage;
        }

        return $language;
    }

    /**
     * Sets the edition components.
     *
     * @return void
     */
    private function _setEditionComponents()
    {
        /** @var \craft\app\web\Application|\craft\app\console\Application $this */
        // Set the appropriate edition components
        $edition = $this->getEdition();

        if ($edition == Craft::Client || $edition == Craft::Pro) {
            $pathService = $this->getPath();

            $this->setComponents(require $pathService->getAppPath().'/config/components/client.php');

            if ($edition == Craft::Pro) {
                $this->setComponents(require $pathService->getAppPath().'/config/components/pro.php');
            }
        }
    }
}
