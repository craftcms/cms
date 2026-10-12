<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Serializers;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Release\Models\Release;
use CraftCms\Cms\Release\Models\ReleaseItem;
use Illuminate\Support\Facades\DB;

/** @since 6.0.0 */
readonly class ReleaseSerializer
{
    public function __construct(private McpActor $actor) {}

    /** @return array<string, mixed> */
    public function serialize(Release $release, bool $includeItems = true): array
    {
        $actor = $this->actor->user();
        if ($release->items_count === null) {
            $release->loadCount('items');
        }
        $data = [
            'id' => $release->id,
            'uid' => $release->uid,
            'name' => $release->name,
            'description' => $release->description,
            'status' => $release->status->value,
            'plannedAt' => $release->plannedAt?->toIso8601String(),
            'publishedAt' => $release->publishedAt?->toIso8601String(),
            'createdById' => $release->createdById,
            'scheduledById' => $release->scheduledById,
            'dateCreated' => $release->dateCreated->toIso8601String(),
            'dateUpdated' => $release->dateUpdated->toIso8601String(),
            'itemCount' => $release->items_count,
            'lastError' => $actor->can('publishReleases') ? $release->lastError : null,
            'publishAttempts' => $release->publishAttempts,
            'readOnly' => ! $release->status->isMutable(),
        ];

        if (! $includeItems) {
            return $data;
        }

        $items = $release->items()->get();
        $rows = DB::table(Table::ELEMENTS)
            ->whereIn('draftId', $items->pluck('draftId')->filter()->all())
            ->orWhereIn('id', $items->filter(fn (ReleaseItem $item): bool => $item->draftId === null)
                ->map(fn (ReleaseItem $item): int => $item->publishedElementId ?? $item->canonicalId)->all())
            ->get(['id', 'draftId', 'type']);
        $elements = [];

        foreach ($rows->groupBy('type') as $type => $typeRows) {
            if (! is_string($type) || ! is_a($type, ElementInterface::class, true)) {
                continue;
            }

            foreach ($type::find()->id($typeRows->pluck('id')->all())->drafts(null)->provisionalDrafts(null)->revisions(null)->site('*')->status(null)->all() as $element) {
                $elements[$element->id][] = $element;
            }
        }

        $draftElementIds = $rows->whereNotNull('draftId')->pluck('id', 'draftId');
        $data['items'] = $items->map(function (ReleaseItem $item) use ($elements, $draftElementIds, $actor): array {
            $elementId = $item->draftId === null ? $item->publishedElementId ?? $item->canonicalId : $draftElementIds->get($item->draftId);
            $localized = $elements[$elementId] ?? [];

            return [
                'id' => $item->id,
                'draftId' => $item->draftId,
                'canonicalId' => $item->canonicalId,
                'publishedElementId' => $item->publishedElementId,
                'publishedAt' => $item->publishedAt?->toIso8601String(),
                'elements' => array_map(fn (ElementInterface $element): array => [
                    'id' => $element->id,
                    'uid' => $element->uid,
                    'type' => $element::class,
                    'title' => $element->title,
                    'draftName' => $element->draftName,
                    'siteId' => $element->siteId,
                    'site' => $element->getSite()->getName(),
                    'canView' => $actor->can('view', $element),
                    'cpEditUrl' => $actor->can('view', $element) ? $element->getCpEditUrl() : null,
                ], $localized),
            ];
        })->all();

        return $data;
    }
}
