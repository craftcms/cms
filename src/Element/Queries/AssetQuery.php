<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Concerns\Asset\EagerloadsTransforms;
use CraftCms\Cms\Element\Queries\Concerns\Asset\QueriesAlt;
use CraftCms\Cms\Element\Queries\Concerns\Asset\QueriesAssetLocation;
use CraftCms\Cms\Element\Queries\Concerns\Asset\QueriesAssetProperties;
use CraftCms\Cms\Element\Queries\Concerns\Asset\QueriesSizes;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Volumes;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Override;
use Tpetry\QueryExpressions\Language\Alias;

/**
 * @extends ElementQuery<Asset>
 */
class AssetQuery extends ElementQuery
{
    use EagerloadsTransforms;
    use QueriesAlt;
    use QueriesAssetLocation;
    use QueriesAssetProperties;
    use QueriesSizes;

    #[Override]
    protected string $table = Table::ASSETS;

    #[Override]
    protected array $defaultOrderBy = [
        'assets.dateCreated' => SORT_DESC,
        'assets.id' => SORT_DESC,
    ];

    /**
     * @var bool|null Whether to only return assets that the user has permission to view.
     *
     * @used-by editable()
     */
    public ?bool $editable = null;

    /**
     * @var bool|null Whether to only return entries that the user has permission to save.
     *
     * @used-by savable()
     */
    public ?bool $savable = null;

    public function __construct(array $config = [])
    {
        parent::__construct(Asset::class, $config);

        $this->query->addSelect([
            'assets.volumeId as volumeId',
            'assets.folderId as folderId',
            'assets.uploaderId as uploaderId',
            'assets.filename as filename',
            'assets.kind as kind',
            'assets.width as width',
            'assets.height as height',
            'assets.size as size',
            'assets.alt as alt',
            'assets.focalPoint as focalPoint',
            'assets.keptFile as keptFile',
            'assets.dateModified as dateModified',
            'assets.mimeType as mimeType',
            'assets_sites.alt as siteAlt',
            'volumeFolders.path as folderPath',
        ]);

        $this->beforeQuery(function (self $elementQuery) {
            $elementQuery->query->leftJoin(new Alias(Table::ASSETS_SITES, 'assets_sites'), function (JoinClause $join) {
                $join->on('assets_sites.assetId', '=', 'assets.id')
                    ->whereColumn('assets_sites.siteId', '=', 'elements_sites.siteId');
            });

            $elementQuery->applyAuthParam($elementQuery->editable, 'viewAssets', 'viewPeerAssets');
            $elementQuery->applyAuthParam($elementQuery->savable, 'saveAssets', 'savePeerAssets');
        });
    }

    /**
     * Sets the [[$editable]] property.
     *
     * @uses $editable
     */
    public function editable(?bool $value = true): self
    {
        $this->editable = $value;

        return $this;
    }

    /**
     * Sets the [[$savable]] property.
     *
     * @uses $savable
     */
    public function savable(?bool $value = true): self
    {
        $this->savable = $value;

        return $this;
    }

    private function applyAuthParam(?bool $value, string $permissionPrefix, string $peerPermissionPrefix): void
    {
        if ($value === null) {
            return;
        }

        $user = Auth::user();

        if (! $user) {
            throw new QueryAbortedException;
        }

        $fullyAuthorizedVolumeIds = [];
        $partiallyAuthorizedVolumeIds = [];
        $unauthorizedVolumeIds = [];

        foreach (Volumes::getAllVolumes() as $volume) {
            if ($user->can("$peerPermissionPrefix:$volume->uid")) {
                $fullyAuthorizedVolumeIds[] = $volume->id;
            } elseif ($user->can("$permissionPrefix:$volume->uid")) {
                $partiallyAuthorizedVolumeIds[] = $volume->id;
            } else {
                $unauthorizedVolumeIds[] = $volume->id;
            }
        }

        if ($value) {
            if (! $fullyAuthorizedVolumeIds && ! $partiallyAuthorizedVolumeIds) {
                throw new QueryAbortedException;
            }

            $this->where(function (Builder $query) use ($user, $fullyAuthorizedVolumeIds, $partiallyAuthorizedVolumeIds) {
                if ($fullyAuthorizedVolumeIds) {
                    $query->orWhereIn('assets.volumeId', $fullyAuthorizedVolumeIds);
                }

                if ($partiallyAuthorizedVolumeIds) {
                    $query->orWhere(fn (Builder $query) => $query
                        ->whereIn('assets.volumeId', $partiallyAuthorizedVolumeIds)
                        ->where('assets.uploaderId', $user->id),
                    );
                }
            });

            return;
        }

        if (! $unauthorizedVolumeIds && ! $partiallyAuthorizedVolumeIds) {
            throw new QueryAbortedException;
        }

        $this->where(function (Builder $query) use ($user, $unauthorizedVolumeIds, $partiallyAuthorizedVolumeIds) {
            if ($unauthorizedVolumeIds) {
                $query->orWhereIn('assets.volumeId', $unauthorizedVolumeIds);
            }

            if ($partiallyAuthorizedVolumeIds) {
                $query->orWhere(function (Builder $query) use ($user, $partiallyAuthorizedVolumeIds) {
                    $query->whereIn('assets.volumeId', $partiallyAuthorizedVolumeIds)
                        ->where(function (Builder $query) use ($user) {
                            $query->where('assets.uploaderId', '!=', $user->id)
                                ->orWhereNull('assets.uploaderId');
                        });
                });
            }
        });
    }

    #[Override]
    public function createElement(array $row): ElementInterface
    {
        // Use the site-specific alt text, if set
        $siteAlt = Arr::pull($row, 'siteAlt');

        if ($siteAlt !== null) {
            $row['alt'] = $siteAlt;
        }

        return parent::createElement($row);
    }

    #[Override]
    protected function cacheTags(): array
    {
        $tags = [];

        if ($this->volumeId && $this->volumeId !== ':empty:') {
            foreach ($this->volumeId as $volumeId) {
                $tags[] = "volume:$volumeId";
            }
        }

        return $tags;
    }

    #[Override]
    protected function fieldLayouts(): Collection
    {
        if (! $this->volumeId) {
            return parent::fieldLayouts();
        }

        if ($this->volumeId === ':empty:') {
            return parent::fieldLayouts();
        }

        $fieldLayouts = [];

        foreach (Arr::wrap($this->volumeId) as $volumeId) {
            if ($volume = Volumes::getVolumeById((int) $volumeId)) {
                $fieldLayouts[] = $volume->getFieldLayout();
            }
        }

        return new Collection($fieldLayouts);
    }
}
