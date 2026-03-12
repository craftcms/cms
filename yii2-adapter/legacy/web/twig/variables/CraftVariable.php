<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use BadMethodCallException;
use Craft;
use craft\console\Application as ConsoleApplication;
use craft\db\Query;
use craft\elements\Category;
use craft\elements\db\AddressQuery;
use craft\elements\db\AssetQuery;
use craft\elements\db\CategoryQuery;
use craft\elements\db\EntryQuery;
use craft\elements\db\GlobalSetQuery;
use craft\elements\db\TagQuery;
use craft\elements\GlobalSet;
use craft\elements\Tag;
use craft\events\DefineBehaviorsEvent;
use craft\web\Application as WebApplication;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Route\Routes;
use CraftCms\Cms\Section\Sections;
use CraftCms\Cms\Site\SiteGroups;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Translation\I18N;
use CraftCms\Cms\Twig\Variables\Io;
use CraftCms\Cms\Twig\Variables\Rebrand;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\UserGroups;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Support\Facades\Config;
use yii\di\ServiceLocator;

/**
 * Craft defines the `craft` global template variable.
 *
 * @property Cp $cp
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

    public ?Rebrand $rebrand = null;
    public Io $io;
    public Cp $cp;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        $this->app = Craft::$app;

        if (Edition::isAtLeast(Edition::Pro)) {
            $this->rebrand = app()->make(Rebrand::class);
        }

        $this->io = app()->make(Io::class);
        $this->cp = app()->make(Cp::class);

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        if ($this->hasEventHandlers(self::EVENT_INIT)) {
            $this->trigger(self::EVENT_INIT);
        }
    }

    /**
     * @inheritdoc
     */
    public function __call($name, $params)
    {
        if (method_exists(app(), $name)) {
            return app()->$name(...$params);
        }

        throw new BadMethodCallException("Method $name does not exist on CraftVariable.");
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        // Fire a 'defineBehaviors' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_BEHAVIORS)) {
            $event = new DefineBehaviorsEvent();
            $this->trigger(self::EVENT_DEFINE_BEHAVIORS, $event);
            return $event->behaviors;
        }

        return [];
    }

    // General info
    // -------------------------------------------------------------------------

    public function config(): array
    {
        return Config::all();
    }

    // Queries
    // -------------------------------------------------------------------------

    /**
     * Returns a new [address query](https://craftcms.com/docs/5.x/reference/element-types/addresses.html#querying-addresses).
     *
     * @param array $criteria
     * @return AddressQuery
     */
    public function addresses(array $criteria = []): AddressQuery
    {
        $query = new AddressQuery(Address::class);
        Typecast::configure($query, $criteria);
        return $query;
    }

    /**
     * Returns a new [asset query](https://craftcms.com/docs/5.x/reference/element-types/assets.html#querying-assets).
     *
     * @param array $criteria
     * @return AssetQuery
     */
    public function assets(array $criteria = []): AssetQuery
    {
        $query = new AssetQuery(Asset::class);
        Typecast::configure($query, $criteria);
        return $query;
    }

    public function auth(): Auth
    {
        return app(Auth::class);
    }

    /**
     * Returns a new [category query](https://craftcms.com/docs/5.x/reference/element-types/categories.html#querying-categories).
     *
     * @param array $criteria
     * @return CategoryQuery
     * @deprecated in 6.0.0
     */
    public function categories(array $criteria = []): CategoryQuery
    {
        $query = Category::find();
        Typecast::configure($query, $criteria);
        return $query;
    }

    public function elementSources(): ElementSources
    {
        return app(ElementSources::class);
    }

    /**
     * Returns a new [entry query](https://craftcms.com/docs/5.x/reference/element-types/entries.html#querying-entries).
     *
     * @param array $criteria
     * @return EntryQuery
     */
    public function entries(array $criteria = []): EntryQuery
    {
        $query = new EntryQuery(Entry::class);
        Typecast::configure($query, $criteria);
        return $query;
    }

    public function entryTypes(): EntryTypes
    {
        return app(EntryTypes::class);
    }

    /**
     * Returns the fields service.
     *
     * @return \CraftCms\Cms\Field\Fields
     */
    public function fields(): Fields
    {
        return app(Fields::class);
    }

    /**
     * Returns a new [global set query](https://craftcms.com/docs/5.x/reference/element-types/globals.html#querying-globals).
     *
     * @param array $criteria
     * @return GlobalSetQuery
     * @since 3.0.4
     * @deprecated in 6.0.0
     */
    public function globalSets(array $criteria = []): GlobalSetQuery
    {
        $query = GlobalSet::find();
        Typecast::configure($query, $criteria);
        return $query;
    }

    public function i18n(): I18N
    {
        return app(I18N::class);
    }

    public function sections(): Sections
    {
        return app(Sections::class);
    }

    public function routes(): Routes
    {
        return app(Routes::class);
    }

    public function sites(): Sites
    {
        return app(Sites::class);
    }

    public function siteGroups(): SiteGroups
    {
        return app(SiteGroups::class);
    }

    public function userGroups(): UserGroups
    {
        return app(UserGroups::class);
    }

    public function userPermissions(): UserPermissions
    {
        return app(UserPermissions::class);
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
     * Returns a new [tag query](https://craftcms.com/docs/5.x/reference/element-types/tags.html#querying-tags).
     *
     * @param array $criteria
     * @return TagQuery
     * @deprecated in 6.0.0
     */
    public function tags(array $criteria = []): TagQuery
    {
        $query = Tag::find();
        Typecast::configure($query, $criteria);
        return $query;
    }

    /**
     * Returns a new [user query](https://craftcms.com/docs/5.x/reference/element-types/users.html#querying-users).
     *
     * @param array $criteria
     * @return \CraftCms\Cms\Element\Queries\UserQuery
     */
    public function users(array $criteria = []): UserQuery
    {
        $query = User::find();
        Typecast::configure($query, $criteria);
        return $query;
    }
}
