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
use InvalidArgumentException;
use stdClass;
use Tpetry\QueryExpressions\Language\Alias;

/**
 * @since 6.0.0
 */
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
     * @return Collection<class-string<ElementInterface>, Collection<int|string, Collection<int, stdClass&object{fieldInstanceUid: string, sourceId: int, sourceSiteId: int|null, type: class-string<ElementInterface>}>>>
     */
    public function replacementGroupsForTargets(array $targetIds): Collection
    {
        if ($targetIds === []) {
            return Collection::make();
        }

        $references = $this->referencesToTargetsQuery($targetIds)
            ->select(['fr.fieldInstanceUid', 'fr.sourceId', 'fr.sourceSiteId', 'e.type'])
            ->distinct()
            ->orderBy('e.type')
            ->orderBy('fr.sourceSiteId')
            ->orderBy('fr.sourceId')
            ->get()
            ->map($this->normalizeReference(...));

        $groups = [];

        foreach ($references as $reference) {
            $siteKey = $reference->sourceSiteId ?? '*';
            $groups[$reference->type][$siteKey][] = $reference;
        }

        return collect($groups)->map(fn (array $sites) => collect($sites)->map(fn (array $rows) => collect($rows)));
    }

    /** @return stdClass&object{fieldInstanceUid: string, sourceId: int, sourceSiteId: int|null, type: class-string<ElementInterface>} */
    private function normalizeReference(stdClass $row): stdClass
    {
        if (! is_string($row->type) || ! is_subclass_of($row->type, ElementInterface::class)) {
            throw new InvalidArgumentException('Field references must belong to an element type.');
        }

        return (object) [
            'fieldInstanceUid' => (string) $row->fieldInstanceUid,
            'sourceId' => (int) $row->sourceId,
            'sourceSiteId' => $row->sourceSiteId === null ? null : (int) $row->sourceSiteId,
            'type' => $row->type,
        ];
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
