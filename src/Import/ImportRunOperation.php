<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use CraftCms\Cms\Activity\Contracts\ActivityOperationTypeInterface;
use CraftCms\Cms\Activity\Data\ActivityOperation;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Gate;

/**
 * One run of an import plan, as an activity operation.
 *
 * @since 6.0.0
 */
class ImportRunOperation implements ActivityOperationTypeInterface
{
    public static function make(ImportPlanData $importPlan, string $runId): ActivityOperation
    {
        return new ActivityOperation(
            type: self::class,
            id: $runId,
            label: (string) $importPlan->name,
            data: ['planUid' => (string) $importPlan->uid],
        );
    }

    public static function url(string $id, array $snapshot, User $viewer): ?string
    {
        $importPlan = app(ImportPlans::class)->getImportPlanByUid((string) ($snapshot['planUid'] ?? ''));

        if (! $importPlan?->handle || ! $importPlan->isEditable() || ! Gate::forUser($viewer)->allows('viewImportPlans')) {
            return null;
        }

        return Url::cpUrl("import/$importPlan->handle");
    }
}
