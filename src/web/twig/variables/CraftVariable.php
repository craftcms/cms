<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\twig\variables;

use Craft;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\db\AssetQuery;
use craft\elements\db\CategoryQuery;
use craft\elements\db\EntryQuery;
use craft\elements\db\TagQuery;
use craft\elements\db\UserQuery;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\elements\User;
use yii\di\ServiceLocator;

/**
 * Craft defines the `craft` global template variable.
 *
 * @property Config          $config
 * @property ElementIndexes  $elementIndexes
 * @property CategoryGroups  $categoryGroups
 * @property Cp              $cp
 * @property Deprecator      $deprecator
 * @property Fields          $fields
 * @property Feeds           $feeds
 * @property Globals         $globals
 * @property Request         $request
 * @property Routes          $routes
 * @property Sections        $sections
 * @property SystemSettings  $systemSettings
 * @property Tasks           $tasks
 * @property UserSession     $session
 * @property I18n            $i18n
 * @property Io              $io
 * @property UserGroups      $userGroups
 * @property UserPermissions $userPermissions
 * @property EmailMessages   $emailMessages
 * @property EntryRevisions  $entryRevisions
 * @property Rebrand         $rebrand
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class CraftVariable extends ServiceLocator
{
    // Properties
    // =========================================================================

    /**
     * @var \craft\web\Application|\craft\console\Application The Craft application class
     */
    public $app;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Set the core components
        $config['components'] = [
            'cp' => \craft\web\twig\variables\Cp::class,
            'io' => \craft\web\twig\variables\Io::class,
            'routes' => \craft\web\twig\variables\Routes::class,

            // Deprecated
            'categoryGroups' => \craft\web\twig\variables\CategoryGroups::class,
            'config' => \craft\web\twig\variables\Config::class,
            'deprecator' => \craft\web\twig\variables\Deprecator::class,
            'elementIndexes' => \craft\web\twig\variables\ElementIndexes::class,
            'entryRevisions' => \craft\web\twig\variables\EntryRevisions::class,
            'feeds' => \craft\web\twig\variables\Feeds::class,
            'fields' => \craft\web\twig\variables\Fields::class,
            'globals' => \craft\web\twig\variables\Globals::class,
            'i18n' => \craft\web\twig\variables\I18N::class,
            'request' => \craft\web\twig\variables\Request::class,
            'sections' => \craft\web\twig\variables\Sections::class,
            'systemSettings' => \craft\web\twig\variables\SystemSettings::class,
            'tasks' => \craft\web\twig\variables\Tasks::class,
            'session' => \craft\web\twig\variables\UserSession::class,
        ];

        switch (Craft::$app->getEdition()) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case Craft::Pro: {
                $config['components'] = array_merge($config['components'], [
                    // Deprecated
                    'userGroups' => \craft\web\twig\variables\UserGroups::class,
                ]);
            }
            case Craft::Client: {
                $config['components'] = array_merge($config['components'], [
                    'rebrand' => \craft\web\twig\variables\Rebrand::class,

                    // Deprecated
                    'emailMessages' => \craft\web\twig\variables\EmailMessages::class,
                    'userPermissions' => \craft\web\twig\variables\UserPermissions::class,
                ]);
            }
        }

        // Add plugin components
        foreach (Craft::$app->getPlugins()->getAllPlugins() as $handle => $plugin) {
            if (!isset($config['components'][$handle])) {
                $component = $plugin->getVariableDefinition();

                if ($component !== null) {
                    $config['components'][$handle] = $component;
                }
            }
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->app = Craft::$app;
    }

    /**
     * @inheritdoc
     */
    public function __call($name, $params)
    {
        // Are they calling one of the components as if it's still a function?
        if ($params === [] && $this->has($name)) {
            Craft::$app->getDeprecator()->log('CraftVariable::__call()', "craft.{$name}() is no longer a function. Use “craft.{$name}” instead (without the parentheses).");

            return $this->get($name);
        }

        return parent::__call($name, $params);
    }

    // General info
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function canGetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        // Check the services
        if ($this->has($name)) {
            return true;
        }

        return parent::canGetProperty($name, $checkVars, $checkBehaviors);
    }

    /**
     * Gets the current language in use.
     *
     * @return string
     * @deprecated in 3.0
     */
    public function getLocale()
    {
        Craft::$app->getDeprecator()->log('craft.getLocale()', 'craft.getLocale() has been deprecated. Use craft.app.language instead.');

        return Craft::$app->language;
    }

    /**
     * Returns the system timezone.
     *
     * @return string
     * @deprecated in 3.0
     */
    public function getTimeZone()
    {
        Craft::$app->getDeprecator()->log('craft.getTimeZone()', 'craft.getTimeZone() has been deprecated. Use craft.app.getTimeZone() instead.');

        return Craft::$app->getTimeZone();
    }

    /**
     * Returns whether this site has multiple locales.
     *
     * @return boolean
     * @deprecated in 3.0. Use craft.app.isMultiSite instead
     */
    public function isLocalized()
    {
        Craft::$app->getDeprecator()->log('craft.isLocalized', 'craft.isLocalized has been deprecated. Use craft.app.isMultiSite instead.');

        return Craft::$app->getIsMultiSite();
    }

    // Element queries
    // -------------------------------------------------------------------------

    /**
     * Returns a new AssetQuery instance.
     *
     * @param mixed $criteria
     *
     * @return AssetQuery
     */
    public function getAssets($criteria = null)
    {
        return Asset::find()->configure($criteria);
    }

    /**
     * Returns a new CategoryQuery instance.
     *
     * @param mixed $criteria
     *
     * @return CategoryQuery
     */
    public function getCategories($criteria = null)
    {
        return Category::find()->configure($criteria);
    }

    /**
     * Returns a new EntryQuery instance.
     *
     * @param mixed $criteria
     *
     * @return EntryQuery
     */
    public function getEntries($criteria = null)
    {
        return Entry::find()->configure($criteria);
    }

    /**
     * Returns a new TagQuery instance.
     *
     * @param mixed $criteria
     *
     * @return TagQuery
     */
    public function getTags($criteria = null)
    {
        return Tag::find()->configure($criteria);
    }

    /**
     * Returns a new UserQuery instance
     *
     * @param mixed $criteria
     *
     * @return UserQuery
     */
    public function getUsers($criteria = null)
    {
        return User::find()->configure($criteria);
    }
}
