<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\db\AssetQuery;
use craft\elements\db\CategoryQuery;
use craft\elements\db\EntryQuery;
use craft\elements\db\GlobalSetQuery;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\db\TagQuery;
use craft\elements\db\UserQuery;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\MatrixBlock;
use craft\elements\Tag;
use craft\elements\User;
use craft\events\DefineBehaviorsEvent;
use craft\events\DefineComponentsEvent;
use yii\di\ServiceLocator;

/**
 * Craft defines the `craft` global template variable.
 *
 * @property Config $config
 * @property ElementIndexes $elementIndexes
 * @property CategoryGroups $categoryGroups
 * @property Cp $cp
 * @property Deprecator $deprecator
 * @property Fields $fields
 * @property Feeds $feeds
 * @property Globals $globals
 * @property Request $request
 * @property Routes $routes
 * @property Sections $sections
 * @property SystemSettings $systemSettings
 * @property UserSession $session
 * @property I18n $i18n
 * @property Io $io
 * @property UserGroups $userGroups
 * @property UserPermissions $userPermissions
 * @property EmailMessages $emailMessages
 * @property EntryRevisions $entryRevisions
 * @property Rebrand $rebrand
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CraftVariable extends ServiceLocator
{
    // Constants
    // =========================================================================

    /**
     * @event \yii\base\Event The event that is triggered after the component's init cycle
     * @see init()
     */
    const EVENT_INIT = 'init';

    /**
     * @event DefineBehaviorsEvent The event that is triggered when defining the class behaviors
     * @see behaviors()
     */
    const EVENT_DEFINE_BEHAVIORS = 'defineBehaviors';

    /**
     * @event DefineComponentsEvent The event that is triggered when defining the Service Locator components.
     * @see __construct()
     * @deprecated since 3.0.0-beta.23
     */
    const EVENT_DEFINE_COMPONENTS = 'defineComponents';

    // Properties
    // =========================================================================

    /**
     * @var \craft\web\Application|\craft\console\Application|null The Craft application class
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
        /** @noinspection PhpDeprecationInspection */
        $components = [
            'cp' => Cp::class,
            'io' => Io::class,
            'routes' => Routes::class,

            // Deprecated
            'categoryGroups' => CategoryGroups::class,
            'config' => Config::class,
            'deprecator' => Deprecator::class,
            'elementIndexes' => ElementIndexes::class,
            'entryRevisions' => EntryRevisions::class,
            'feeds' => Feeds::class,
            'fields' => Fields::class,
            'globals' => Globals::class,
            'i18n' => I18N::class,
            'request' => Request::class,
            'sections' => Sections::class,
            'systemSettings' => SystemSettings::class,
            'session' => UserSession::class,
        ];

        if (Craft::$app->getEdition() === Craft::Pro) {
            /** @noinspection PhpDeprecationInspection */
            /** @noinspection PhpDeprecationInspection */
            /** @noinspection SuspiciousAssignmentsInspection */
            $components = array_merge($components, [
                'rebrand' => Rebrand::class,

                // Deprecated
                'emailMessages' => EmailMessages::class,
                'userGroups' => UserGroups::class,
                'userPermissions' => UserPermissions::class,
            ]);
        }

        // todo: remove all this before 3.0 GA
        // Let plugins add their own components
        $event = new DefineComponentsEvent([
            'components' => $components,
        ]);
        if ($this->hasEventHandlers(self::EVENT_DEFINE_COMPONENTS)) {
            Craft::$app->getDeprecator()->log('CraftVariable::defineComponents', 'The `defineComponents` event on CraftVariable has been deprecated. Use the `init` event to register custom components instead.');
            $this->trigger(self::EVENT_DEFINE_COMPONENTS, $event);
        }
        $components = $event->components;

        $config['components'] = $components;

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->app = Craft::$app;

        if ($this->hasEventHandlers(self::EVENT_INIT)) {
            $this->trigger(self::EVENT_INIT);
        }
    }

    /**
     * @inheritdoc
     */
    public function __call($name, $params)
    {
        // Are they calling one of the components as if it's still a function?
        if ($params === [] && $this->has($name)) {
            Craft::$app->getDeprecator()->log("CraftVariable::{$name}()", "craft.{$name}() is no longer a function. Use “craft.{$name}” instead (without the parentheses).");
            return $this->get($name);
        }

        return parent::__call($name, $params);
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        // Fire a 'defineBehaviors' event
        $event = new DefineBehaviorsEvent();
        $this->trigger(self::EVENT_DEFINE_BEHAVIORS, $event);
        return $event->behaviors;
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
    public function locale(): string
    {
        Craft::$app->getDeprecator()->log('craft.locale()', 'craft.locale() has been deprecated. Use craft.app.language instead.');
        return Craft::$app->language;
    }

    /**
     * Returns whether this site has multiple locales.
     *
     * @return bool
     * @deprecated in 3.0. Use craft.app.isMultiSite instead
     */
    public function isLocalized(): bool
    {
        Craft::$app->getDeprecator()->log('craft.isLocalized', 'craft.isLocalized has been deprecated. Use craft.app.isMultiSite instead.');
        return Craft::$app->getIsMultiSite();
    }

    // Element queries
    // -------------------------------------------------------------------------

    /**
     * Returns a new AssetQuery instance.
     *
     * @param array $criteria
     * @return AssetQuery
     */
    public function assets(array $criteria = []): AssetQuery
    {
        $query = Asset::find();
        Craft::configure($query, $criteria);
        return $query;
    }

    /**
     * Returns a new CategoryQuery instance.
     *
     * @param array $criteria
     * @return CategoryQuery
     */
    public function categories(array $criteria = []): CategoryQuery
    {
        $query = Category::find();
        Craft::configure($query, $criteria);
        return $query;
    }

    /**
     * Returns a new EntryQuery instance.
     *
     * @param array $criteria
     * @return EntryQuery
     */
    public function entries(array $criteria = []): EntryQuery
    {
        $query = Entry::find();
        Craft::configure($query, $criteria);
        return $query;
    }

    /**
     * Returns a new GlobalSetQuery instance.
     *
     * @param array $criteria
     * @return GlobalSetQuery
     */
    public function globalSets(array $criteria = []): GlobalSetQuery
    {
        $query = GlobalSet::find();
        Craft::configure($query, $criteria);
        return $query;
    }

    /**
     * Returns a new MatrixBlockQuery instance.
     *
     * @param array $criteria
     * @return MatrixBlockQuery
     */
    public function matrixBlocks(array $criteria = []): MatrixBlockQuery
    {
        $query = MatrixBlock::find();
        Craft::configure($query, $criteria);
        return $query;
    }

    /**
     * Returns a new TagQuery instance.
     *
     * @param array $criteria
     * @return TagQuery
     */
    public function tags(array $criteria = []): TagQuery
    {
        $query = Tag::find();
        Craft::configure($query, $criteria);
        return $query;
    }

    /**
     * Returns a new UserQuery instance
     *
     * @param array $criteria
     * @return UserQuery
     */
    public function users(array $criteria = []): UserQuery
    {
        $query = User::find();
        Craft::configure($query, $criteria);
        return $query;
    }
}
