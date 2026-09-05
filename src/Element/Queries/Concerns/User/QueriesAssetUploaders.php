<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns\User;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Queries\UserQuery;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @internal
 */
trait QueriesAssetUploaders
{
    /**
     * @var bool|null Whether to only return users that have uploaded an asset.
     *                ---
     *                ```php
     *                // fetch all users who have uploaded an asset
     *                $uploaders = \CraftCms\Cms\User\Elements\User::find()
     *                ->assetUploaders()
     *                ->all();
     *                ```
     *                ```twig
     *                {# fetch all users who have uploaded an asset #}
     *                {% set uploaders = users()
     *                .assetUploaders()
     *                .all()%}
     *                ```
     *
     * @used-by assetUploaders()
     */
    public ?bool $assetUploaders = null;

    protected function initQueriesAssetUploaders(): void
    {
        $this->beforeQuery(function (UserQuery $userQuery) {
            static::applyAssetUploaders($userQuery, $userQuery->assetUploaders);
        });
    }

    public static function applyAssetUploaders(Builder $query, ?bool $value): void
    {
        if (! is_bool($value)) {
            return;
        }

        $exists = DB::table(Table::ASSETS)
            ->whereColumn('uploaderId', 'elements.id');

        if ($value) {
            $query->whereExists($exists);
        } else {
            $query->whereNotExists($exists);
        }
    }

    /**
     * Narrows the query results to only users that have uploaded an asset.
     *
     * ---
     *
     * ```twig
     * {# Fetch all users who have uploaded an asset #}
     * {% set {elements-var} = {twig-method}
     *   .assetUploaders()
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch all users who have uploaded an asset
     * ${elements-var} = {element-class}::find()
     *     ->assetUploaders()
     *     ->all();
     * ```
     *
     * @uses $assetUploaders
     */
    public function assetUploaders(?bool $value = true): self
    {
        $this->assetUploaders = $value;

        return $this;
    }
}
