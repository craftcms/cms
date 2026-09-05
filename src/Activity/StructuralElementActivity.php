<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\EventTypes\ElementDuplicated;
use CraftCms\Cms\Activity\EventTypes\ElementMerged;
use CraftCms\Cms\Activity\EventTypes\ElementMoved;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Support\Facades\Activities;
use CraftCms\Cms\Support\Facades\Sites;

/** @internal */
class StructuralElementActivity
{
    public static function recordDuplicated(ElementInterface $source, ElementInterface $duplicate): void
    {
        if (! ElementActivity::shouldRecord($duplicate)) {
            return;
        }

        Activities::record(new ElementDuplicated(
            subject: $duplicate,
            site: $duplicate->siteId ? Sites::getSiteById($duplicate->siteId) : null,
            source: $source,
        ));
    }

    /**
     * @param  array<string, mixed>  $origin
     * @param  array<string, mixed>  $destination
     */
    public static function recordMoved(ElementInterface $element, array $origin, array $destination): void
    {
        if ($origin === $destination) {
            return;
        }

        Activities::record(new ElementMoved(
            subject: $element,
            site: $element->siteId ? Sites::getSiteById($element->siteId) : null,
            origin: $origin,
            destination: $destination,
        ));
    }

    public static function recordMerged(ActivitySubject $merged, ActivitySubject $prevailing): void
    {
        Activities::record(new ElementMerged(
            subject: $merged,
            role: 'merged',
            other: $prevailing,
        ));

        Activities::record(new ElementMerged(
            subject: $prevailing,
            role: 'prevailing',
            other: $merged,
        ));
    }

    /** @return array{structure: string, parent: array{type: string, id: string, label: string}|null, previousSibling: array{type: string, id: string, label: string}|null} */
    public static function position(string $structureUid, ElementInterface $element): array
    {
        return [
            'structure' => $structureUid,
            'parent' => self::reference($element->getParent()),
            'previousSibling' => self::reference($element->getPrevSibling()),
        ];
    }

    /** @return array{type: string, id: string, label: string}|null */
    private static function reference(?ElementInterface $element): ?array
    {
        if ($element === null) {
            return null;
        }

        $subject = ActivitySubject::fromElement($element);

        return [
            'type' => $subject->type,
            'id' => $subject->id,
            'label' => $subject->label,
        ];
    }
}
