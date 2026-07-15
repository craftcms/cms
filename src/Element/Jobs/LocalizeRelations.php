<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Jobs;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\DB;
use Override;

/**
 * Localizes relations for a field that has changed from global to site-specific.
 */
class LocalizeRelations extends Job
{
    public function __construct(
        public int $fieldId,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $relations = DB::table(Table::RELATIONS)
            ->select(['id', 'sourceId', 'sourceSiteId', 'targetId', 'sortOrder'])
            ->where('fieldId', $this->fieldId)
            ->whereNull('sourceSiteId');

        $totalRelations = $relations->count();
        $primarySiteId = Sites::getPrimarySite()->id;
        $otherSiteIds = Sites::getAllSiteIds()
            ->reject(fn (int $siteId) => $siteId === $primarySiteId)
            ->all();

        $now = now();

        foreach ($relations->lazyById() as $i => $relation) {
            $this->setProgress((int) (($i / max($totalRelations, 1)) * 100));

            DB::transaction(function () use ($relation, $primarySiteId, $otherSiteIds, $now): void {
                $relation = DB::table(Table::RELATIONS)
                    ->select(['id', 'sourceId', 'targetId', 'sortOrder'])
                    ->where('id', $relation->id)
                    ->whereNull('sourceSiteId')
                    ->lockForUpdate()
                    ->first();

                if ($relation === null) {
                    return;
                }

                DB::table(Table::RELATIONS)
                    ->where('id', $relation->id)
                    ->update([
                        'sourceSiteId' => $primarySiteId,
                        'dateUpdated' => $now,
                    ]);

                if ($otherSiteIds === []) {
                    return;
                }

                DB::table(Table::RELATIONS)->insert(array_map(fn (int $siteId) => [
                    'fieldId' => $this->fieldId,
                    'sourceId' => $relation->sourceId,
                    'sourceSiteId' => $siteId,
                    'targetId' => $relation->targetId,
                    'sortOrder' => $relation->sortOrder,
                    'uid' => Str::uuid(),
                    'dateCreated' => $now,
                    'dateUpdated' => $now,
                ], $otherSiteIds));
            });
        }
    }

    #[Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Localizing relations');
    }
}
