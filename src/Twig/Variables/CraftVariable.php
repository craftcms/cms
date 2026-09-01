<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Variables;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Traits\Macroable;

class CraftVariable
{
    use Macroable {
        __call as macroCall;
    }

    public function __construct(
        public readonly Io $io,
        public readonly Cp $cp,
    ) {}

    // Queries
    // -------------------------------------------------------------------------

    /**
     * Returns a new [address query](https://craftcms.com/docs/5.x/reference/element-types/addresses.html#querying-addresses).
     */
    /** @param array<string, mixed> $criteria */
    public function addresses(array $criteria = []): AddressQuery
    {
        return new AddressQuery($criteria);
    }

    /**
     * Returns a new [asset query](https://craftcms.com/docs/5.x/reference/element-types/assets.html#querying-assets).
     */
    /** @param array<string, mixed> $criteria */
    public function assets(array $criteria = []): AssetQuery
    {
        return new AssetQuery($criteria);
    }

    /**
     * Returns a new [entry query](https://craftcms.com/docs/5.x/reference/element-types/entries.html#querying-entries).
     */
    /**
     * @param  array<string, mixed>  $criteria
     * @return EntryQuery<Entry>
     */
    public function entries(array $criteria = []): EntryQuery
    {
        return new EntryQuery($criteria);
    }

    /** @param array<string, mixed> $criteria */
    public function users(array $criteria = []): UserQuery
    {
        return new UserQuery($criteria);
    }

    public function query(?string $table = null): Builder
    {
        return DB::query()->when($table, fn ($query) => $query->from($table));
    }

    /** @param list<mixed> $parameters */
    public function __call(string $method, array $parameters): mixed
    {
        if (method_exists(Cms::class, $method)) {
            return Cms::$method(...$parameters);
        }

        if (method_exists(app(), $method)) {
            return app()->$method(...$parameters);
        }

        return $this->macroCall($method, $parameters);
    }
}
