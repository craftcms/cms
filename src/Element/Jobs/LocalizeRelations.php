<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Jobs;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Localizes relations for a field that has changed from global to site-specific.
 *
 * @since 6.0.0
 */
final class LocalizeRelations extends Job
{
    /**
     * Creates a new LocalizeRelations job.
     *
     * @param  int  $fieldId  The field ID whose relations should be localized.
     */
    public function __construct(
        public int $fieldId,
    ) {}

    public function handle(): void
    {
        $relations = DB::table(Table::RELATIONS)
            ->select(['id', 'sourceId', 'sourceSiteId', 'targetId', 'sortOrder'])
            ->where('fieldId', $this->fieldId)
            ->whereNull('sourceSiteId')
            ->get();

        $totalRelations = count($relations);
        $allSiteIds = Sites::getAllSiteIds()->all();
        $primarySiteId = array_shift($allSiteIds);

        $now = now();

        foreach ($relations as $i => $relation) {
            $this->setProgress((int) (($i / max($totalRelations, 1)) * 100));

            // Set the existing relation to the primary site
            DB::table(Table::RELATIONS)
                ->where('id', $relation->id)
                ->update([
                    'sourceSiteId' => $primarySiteId,
                    'dateUpdated' => $now,
                ]);

            // Duplicate it for the other sites
            foreach ($allSiteIds as $siteId) {
                DB::table(Table::RELATIONS)
                    ->insert([
                        'fieldId' => $this->fieldId,
                        'sourceId' => $relation->sourceId,
                        'sourceSiteId' => $siteId,
                        'targetId' => $relation->targetId,
                        'sortOrder' => $relation->sortOrder,
                        'uid' => Str::uuid(),
                        'dateCreated' => $now,
                        'dateUpdated' => $now,
                    ]);
            }
        }
    }

    #[\Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Localizing relations');
    }
}
