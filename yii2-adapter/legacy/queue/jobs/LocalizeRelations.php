<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use craft\queue\BaseJob;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * LocalizeRelations job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class LocalizeRelations extends BaseJob
{
    /**
     * @var int|null The field ID whose data should be localized
     */
    public ?int $fieldId = null;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
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
            $this->setProgress($queue, $i / $totalRelations);

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

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return I18N::prep('Localizing relations');
    }
}
