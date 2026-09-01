<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\TracksReferencesFieldInterface;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;

#[Singleton]
readonly class FieldReferences
{
    public function updateReferences(TracksReferencesFieldInterface $field, ElementInterface $element): void
    {
        if (! isset($field->id, $field->layoutElement->uid, $element->id, $element->siteId)) {
            return;
        }

        $targetIds = $field->getReferenceTargetIds($element);
        $sourceSiteId = $element->siteId;

        DB::transaction(function () use ($field, $element, $sourceSiteId, $targetIds) {
            DB::table(Table::FIELDREFERENCES)
                ->where('fieldId', $field->id)
                ->where('fieldInstanceUid', $field->layoutElement->uid)
                ->where('sourceId', $element->id)
                ->where('sourceSiteId', $sourceSiteId)
                ->delete();

            if ($targetIds === []) {
                return;
            }

            DB::table(Table::FIELDREFERENCES)->insert(array_map(fn (int $targetId) => [
                'fieldId' => $field->id,
                'fieldInstanceUid' => $field->layoutElement->uid,
                'sourceId' => $element->id,
                'sourceSiteId' => $sourceSiteId,
                'targetId' => $targetId,
            ], $targetIds));
        });
    }

    public function deleteReferencesForSourceFieldSite(TracksReferencesFieldInterface $field, ElementInterface $element): void
    {
        if (! isset($field->id, $element->id, $element->siteId)) {
            return;
        }

        DB::table(Table::FIELDREFERENCES)
            ->where('fieldId', $field->id)
            ->where('sourceId', $element->id)
            ->where('sourceSiteId', $element->siteId)
            ->delete();
    }

    public function deleteReferencesForField(FieldInterface $field): void
    {
        if (! isset($field->id)) {
            return;
        }

        DB::table(Table::FIELDREFERENCES)
            ->where('fieldId', $field->id)
            ->delete();
    }

    /**
     * @param  array{tabs?:list<array{elements?:list<array{type?:string, uid?:string, ...}>}>}|null  $previousConfig
     * @param  array{tabs?:list<array{elements?:list<array{type?:string, uid?:string, ...}>}>}|null  $currentConfig
     */
    public function deleteReferencesForRemovedInstances(?array $previousConfig, ?array $currentConfig): void
    {
        $removedUids = array_values(array_diff(
            $this->customFieldUidsInConfig($previousConfig),
            $this->customFieldUidsInConfig($currentConfig),
        ));

        if ($removedUids === []) {
            return;
        }

        DB::table(Table::FIELDREFERENCES)
            ->whereIn('fieldInstanceUid', $removedUids)
            ->delete();
    }

    /**
     * @param  int[]  $targetIds
     * @return Collection<string, Collection<int, int>>
     */
    public function referenceIdsByTypeForTargets(array $targetIds): Collection
    {
        if ($targetIds === []) {
            return Collection::make();
        }

        return $this->referencesToTargetsQuery($targetIds)
            ->select(['e.type', 'fr.sourceId'])
            ->distinct()
            ->get()
            ->groupBy('type')
            ->map(fn (Collection $rows) => $rows->pluck('sourceId')->map(fn ($id) => (int) $id)->values());
    }

    /**
     * @param  int[]  $targetIds
     */
    public function referenceCountForTargets(array $targetIds): int
    {
        if ($targetIds === []) {
            return 0;
        }

        return $this->referencesToTargetsQuery($targetIds)
            ->distinct()
            ->count('fr.sourceId');
    }

    /**
     * @param  int[]  $targetIds
     * @return Collection<string, Collection<string, Collection<int, object>>>
     */
    public function replacementGroupsForTargets(array $targetIds): Collection
    {
        if ($targetIds === []) {
            return Collection::make();
        }

        /** @var Collection<string, Collection<string, Collection<int, object>>> $groups */
        $groups = $this->referencesToTargetsQuery($targetIds)
            ->select(['fr.fieldInstanceUid', 'fr.sourceId', 'fr.sourceSiteId', 'e.type'])
            ->distinct()
            ->orderBy('e.type')
            ->orderBy('fr.sourceSiteId')
            ->orderBy('fr.sourceId')
            ->get()
            ->groupBy([
                'type',
                fn (object $row) => $row->sourceSiteId === null ? '*' : (string) $row->sourceSiteId,
            ]);

        return $groups;
    }

    /**
     * @param  int[]  $targetIds
     */
    private function referencesToTargetsQuery(array $targetIds): Builder
    {
        return DB::table(Table::FIELDREFERENCES, 'fr')
            ->join(new Alias(Table::ELEMENTS, 'e'), 'e.id', 'fr.sourceId')
            ->whereIn('fr.targetId', $targetIds)
            ->whereNull('e.dateDeleted')
            ->whereNull('e.revisionId');
    }

    /**
     * @param  array{tabs?:list<array{elements?:list<array{type?:string, uid?:string, ...}>}>}|null  $config
     * @return list<string>
     */
    private function customFieldUidsInConfig(?array $config): array
    {
        if ($config === null) {
            return [];
        }

        $uids = [];

        foreach ($config['tabs'] ?? [] as $tab) {
            foreach ($tab['elements'] ?? [] as $element) {
                if (
                    is_array($element) &&
                    ($element['type'] ?? null) === CustomField::class &&
                    isset($element['uid'])
                ) {
                    $uids[] = $element['uid'];
                }
            }
        }

        return array_values(array_unique($uids));
    }
}
