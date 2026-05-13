<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\Element\DeletionBlockers\RelationDeletionBlocker;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Events\DefineDeletionBlockers;
use CraftCms\Cms\Entry\Elements\Entry;

trait HasDeletionBlockers
{
    public static function deletionBlockers(ElementCollection $elements, bool $hardDelete): array
    {
        $blockers = [
            new RelationDeletionBlocker(Entry::class, $elements, $hardDelete, [
                'elementIndexSettings' => [
                    'defaultTableColumns' => [
                        ['section'],
                    ],
                    'defaultSort' => ['section', 'asc'],
                ],
            ]),
        ];

        event($event = new DefineDeletionBlockers(
            elementType: static::class,
            elements: $elements,
            hardDelete: $hardDelete,
            blockers: $blockers,
        ));

        return $event->blockers;
    }
}
