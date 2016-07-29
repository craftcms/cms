<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

use Craft;
use craft\app\elements\Asset;
use craft\app\elements\Category;
use craft\app\elements\db\AssetQuery;
use craft\app\elements\db\CategoryQuery;
use craft\app\elements\db\EntryQuery;
use craft\app\elements\db\TagQuery;
use craft\app\elements\db\UserQuery;
use craft\app\elements\Entry;
use craft\app\elements\Tag;
use craft\app\elements\User;
use yii\di\ServiceLocator;

/**
 * Craft defines the `craft` global template variable.
 *
 * @property Config          $config
 * @property ElementIndexes  $elementIndexes
 * @property Elements        $elements
 * @property CategoryGroups  $categoryGroups
 * @property Cp              $cp
 * @property Deprecator      $deprecator
 * @property Fields          $fields
 * @property Feeds           $feeds
 * @property Globals         $globals
 * @property Plugins         $plugins
 * @property HttpRequest     $request
 * @property Routes          $routes
 * @property Sections        $sections
 * @property SystemSettings  $systemSettings
 * @property Tasks           $tasks
 * @property Updates         $updates
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
     * @var \craft\app\web\Application|\craft\app\console\Application The Craft application class
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
            'cp' => 'craft\app\web\twig\variables\Cp',
            'io' => 'craft\app\web\twig\variables\Io',
            'routes' => 'craft\app\web\twig\variables\Routes',

            // Deprecated
            'categoryGroups' => 'craft\app\web\twig\variables\CategoryGroups',
            'config' => 'craft\app\web\twig\variables\Config',
            'deprecator' => 'craft\app\web\twig\variables\Deprecator',
            'elementIndexes' => 'craft\app\web\twig\variables\ElementIndexes',
            'entryRevisions' => 'craft\app\web\twig\variables\EntryRevisions',
            'feeds' => 'craft\app\web\twig\variables\Feeds',
            'fields' => 'craft\app\web\twig\variables\Fields',
            'globals' => 'craft\app\web\twig\variables\Globals',
            'request' => 'craft\app\web\twig\variables\HttpRequest',
            'i18n' => 'craft\app\web\twig\variables\I18N',
            'plugins' => 'craft\app\web\twig\variables\Plugins',
            'sections' => 'craft\app\web\twig\variables\Sections',
            'systemSettings' => 'craft\app\web\twig\variables\SystemSettings',
            'tasks' => 'craft\app\web\twig\variables\Tasks',
            'updates' => 'craft\app\web\twig\variables\Updates',
            'session' => 'craft\app\web\twig\variables\UserSession',
        ];

        switch (Craft::$app->getEdition()) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case Craft::Pro: {
                $config['components'] = array_merge($config['components'], [
                    // Deprecated
                    'userGroups' => 'craft\app\web\twig\variables\UserGroups',
                ]);
            }
            case Craft::Client: {
                $config['components'] = array_merge($config['components'], [
                    'rebrand' => 'craft\app\web\twig\variables\Rebrand',

                    // Deprecated
                    'emailMessages' => 'craft\app\web\twig\variables\EmailMessages',
                    'userPermissions' => 'craft\app\web\twig\variables\UserPermissions',
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
     */
    public function getLocale()
    {
        return Craft::$app->language;
    }

    /**
     * Returns the system timezone.
     *
     * @return string
     */
    public function getTimeZone()
    {
        return Craft::$app->getTimeZone();
    }

    /**
     * Returns whether this site has multiple locales.
     *
     * @return boolean
     */
    public function getIsLocalized()
    {
        return Craft::$app->getIsLocalized();
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
