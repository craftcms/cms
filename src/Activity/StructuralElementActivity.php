<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Support\Facades\Activities;
use CraftCms\Cms\Support\Facades\Sites;

/** @internal */
class StructuralElementActivity
{
    public static function shouldRecord(ElementInterface $element): bool
    {
        return ElementActivity::shouldRecord($element);
    }

    public static function shouldRecordMovement(ElementInterface $element): bool
    {
        return ElementActivity::shouldRecordWrite($element);
    }

    public static function recordDuplicated(ElementInterface $source, ElementInterface $duplicate): void
    {
        if (! self::shouldRecord($duplicate)) {
            return;
        }

        Activities::record(
            'craft.element.duplicated',
            subject: $duplicate,
            site: $duplicate->siteId ? Sites::getSiteById($duplicate->siteId) : null,
            data: ['source' => self::reference($source)],
        );
    }

    /**
     * @param  array<string, mixed>  $origin
     * @param  array<string, mixed>  $destination
     */
    public static function recordMoved(ElementInterface $element, array $origin, array $destination): void
    {
        if (! self::shouldRecordMovement($element) || $origin === $destination) {
            return;
        }

        Activities::record(
            'craft.element.moved',
            subject: $element,
            site: $element->siteId ? Sites::getSiteById($element->siteId) : null,
            data: compact('origin', 'destination'),
        );
    }

    public static function recordMerged(ActivitySubject $merged, ActivitySubject $prevailing): void
    {
        Activities::record(
            'craft.element.merged',
            subject: $merged,
            data: [
                'role' => 'merged',
                'other' => self::subjectReference($prevailing),
            ],
        );

        Activities::record(
            'craft.element.merged',
            subject: $prevailing,
            data: [
                'role' => 'prevailing',
                'other' => self::subjectReference($merged),
            ],
        );
    }

    /** @return array{structure: string, parent: array{type: string, id: string, label: string}|null, previousSibling: array{type: string, id: string, label: string}|null} */
    public static function position(string $structureUid, ElementInterface $element): array
    {
        return [
            'structure' => $structureUid,
            'parent' => self::nullableReference($element->getParent()),
            'previousSibling' => self::nullableReference($element->getPrevSibling()),
        ];
    }

    /** @return array{type: string, id: string, label: string} */
    private static function reference(ElementInterface $element): array
    {
        return self::subjectReference(ActivitySubject::fromElement($element));
    }

    /** @return array{type: string, id: string, label: string}|null */
    private static function nullableReference(?ElementInterface $element): ?array
    {
        return $element ? self::reference($element) : null;
    }

    /** @return array{type: string, id: string, label: string} */
    private static function subjectReference(ActivitySubject $subject): array
    {
        return [
            'type' => $subject->type,
            'id' => $subject->id,
            'label' => $subject->label,
        ];
    }
}
