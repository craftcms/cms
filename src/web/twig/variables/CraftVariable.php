<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;
use craft\console\Application as ConsoleApplication;
use craft\db\Query;
use craft\elements\Address;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\db\AddressQuery;
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
use craft\web\Application as WebApplication;
use yii\di\ServiceLocator;

/**
 * Craft defines the `craft` global template variable.
 *
 * @property Cp $cp
 * @property Routes $routes
 * @property Io $io
 * @property Rebrand $rebrand
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class CraftVariable extends ServiceLocator
{
    /**
     * @event \yii\base\Event The event that is triggered after the component's init cycle
     * @see init()
     */
    public const EVENT_INIT = 'init';

    /**
     * @event DefineBehaviorsEvent The event that is triggered when defining the class behaviors
     * @see behaviors()
     */
    public const EVENT_DEFINE_BEHAVIORS = 'defineBehaviors';

    /**
     * @var WebApplication|ConsoleApplication|null The Craft application class
     */
    public null|WebApplication|ConsoleApplication $app = null;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Set the core components
        $components = [
            'cp' => Cp::class,
            'io' => Io::class,
            'routes' => Routes::class,
        ];

        if (Craft::$app->getEdition() === Craft::Pro) {
            $components = array_merge($components, [
                'rebrand' => Rebrand::class,
            ]);
        }

        $config['components'] = $components;

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init(): void
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
            Craft::$app->getDeprecator()->log("CraftVariable::$name()", "`craft.$name()` is no longer a function. Use `craft.$name` instead (without the parentheses).");
            return $this->get($name);
        }

        return parent::__call($name, $params);
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
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
    public function canGetProperty($name, $checkVars = true, $checkBehaviors = true): bool
    {
        // Check the services
        if ($this->has($name)) {
            return true;
        }

        return parent::canGetProperty($name, $checkVars, $checkBehaviors);
    }

    // Queries
    // -------------------------------------------------------------------------

    /**
     * Returns a new [address query](https://craftcms.com/docs/4.x/addresses.html#querying-addresses).
     *
     * @param array $criteria
     * @return AddressQuery
     */
    public function addresses(array $criteria = []): AddressQuery
    {
        $query = Address::find();
        Craft::configure($query, $criteria);
        return $query;
    }

    /**
     * Returns a new [asset query](https://craftcms.com/docs/4.x/assets.html#querying-assets).
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
     * Returns a new [category query](https://craftcms.com/docs/4.x/categories.html#querying-categories).
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
     * Returns a new [entry query](https://craftcms.com/docs/4.x/entries.html#querying-entries).
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
     * Returns a new [global set query](https://craftcms.com/docs/4.x/globals.html#querying-globals).
     *
     * @param array $criteria
     * @return GlobalSetQuery
     * @since 3.0.4
     */
    public function globalSets(array $criteria = []): GlobalSetQuery
    {
        $query = GlobalSet::find();
        Craft::configure($query, $criteria);
        return $query;
    }

    /**
     * Returns a new [Matrix block query](https://craftcms.com/docs/4.x/matrix-blocks.html#querying-matrix-blocks).
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
     * Returns a new generic query.
     *
     * @return Query
     * @since 3.0.19
     */
    public function query(): Query
    {
        return new Query();
    }

    /**
     * Returns a new [tag query](https://craftcms.com/docs/4.x/tags.html#querying-tags).
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
     * Returns a new [user query](https://craftcms.com/docs/4.x/users.html#querying-users).
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
