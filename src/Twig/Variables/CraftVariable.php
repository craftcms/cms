<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Variables;

use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Route\Routes;
use CraftCms\Cms\Section\Sections;
use CraftCms\Cms\Site\SiteGroups;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Translation\I18N;
use CraftCms\Cms\User\UserGroups;
use CraftCms\Cms\User\UserPermissions;
use CraftCms\Cms\View\HtmlStack;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Traits\Macroable;

class CraftVariable
{
    use Macroable;

    public function __construct(
        public readonly Io $io,
        public readonly Cp $cp,
    ) {}

    public function config(): array
    {
        return Config::all();
    }

    // Queries
    // -------------------------------------------------------------------------

    /**
     * Returns a new [address query](https://craftcms.com/docs/5.x/reference/element-types/addresses.html#querying-addresses).
     */
    public function addresses(array $criteria = []): AddressQuery
    {
        return new AddressQuery($criteria);
    }

    /**
     * Returns a new [asset query](https://craftcms.com/docs/5.x/reference/element-types/assets.html#querying-assets).
     */
    public function assets(array $criteria = []): AssetQuery
    {
        return new AssetQuery($criteria);
    }

    /**
     * Returns a new [entry query](https://craftcms.com/docs/5.x/reference/element-types/entries.html#querying-entries).
     */
    public function entries(array $criteria = []): EntryQuery
    {
        return new EntryQuery($criteria);
    }

    public function users(array $criteria = []): UserQuery
    {
        return new UserQuery($criteria);
    }

    public function query(?string $table = null): Builder
    {
        return DB::query()->when($table, fn ($query) => $query->from($table));
    }

    // Services
    // -------------------------------------------------------------------------

    public function auth(): Auth
    {
        return app(Auth::class);
    }

    public function elementSources(): ElementSources
    {
        return app(ElementSources::class);
    }

    public function entryTypes(): EntryTypes
    {
        return app(EntryTypes::class);
    }

    public function fields(): Fields
    {
        return app(Fields::class);
    }

    public function htmlStack(): HtmlStack
    {
        return app(HtmlStack::class);
    }

    public function i18n(): I18N
    {
        return app(I18N::class);
    }

    public function oauth(): OAuth
    {
        return app(OAuth::class);
    }

    public function routes(): Routes
    {
        return app(Routes::class);
    }

    public function sections(): Sections
    {
        return app(Sections::class);
    }

    public function siteGroups(): SiteGroups
    {
        return app(SiteGroups::class);
    }

    public function sites(): Sites
    {
        return app(Sites::class);
    }

    public function userGroups(): UserGroups
    {
        return app(UserGroups::class);
    }

    public function userPermissions(): UserPermissions
    {
        return app(UserPermissions::class);
    }
}
