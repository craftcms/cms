<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use Closure;
use CraftCms\Cms\Activity\Data\ActivitySource;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use UnexpectedValueException;

use function CraftCms\Cms\t;

#[Singleton]
class ActivityEventTypes
{
    /** @var array<string, array{source: ActivitySource, label: string, icon: string, rules: array<string, mixed>, formatter: (Closure(ActivityEvent, string): (string|Htmlable))|null}> */
    private array $eventTypes = [];

    public function __construct()
    {
        $source = new ActivitySource('craft', 'Craft', 'app');

        $this->register('craft.element.created', $source, 'Created', icon: 'plus');
        $this->register('craft.element.updated', $source, 'Updated', icon: 'pencil');
        $this->register('craft.element.status-changed', $source, 'Status changed', 'circle-half-stroke', [
            'oldStatus' => ['required', 'string'],
            'newStatus' => ['required', 'string'],
        ], formatter: fn (ActivityEvent $event, string $locale): string => t(
            'Status changed from {oldStatus} to {newStatus}.',
            [
                'oldStatus' => Str::headline($event->data['oldStatus']),
                'newStatus' => Str::headline($event->data['newStatus']),
            ],
            locale: $locale,
        ));
        $referenceRules = [
            'type' => ['required', 'string'],
            'id' => ['required', 'string'],
            'label' => ['required', 'string'],
        ];
        $this->register('craft.element.duplicated', $source, 'Duplicated', 'copy', [
            'source' => ['required', 'array:type,id,label'],
            ...Arr::prependKeysWith($referenceRules, 'source.'),
        ], formatter: fn (ActivityEvent $event, string $locale): string => t(
            'Duplicated from {source}.',
            ['source' => $event->data['source']['label']],
            locale: $locale,
        ));
        $this->register('craft.element.moved', $source, 'Moved', 'arrows-up-down-left-right', [
            'origin' => ['required', 'array:structure,parent,previousSibling'],
            'destination' => ['required', 'array:structure,parent,previousSibling'],
            'origin.structure' => ['required', 'uuid'],
            'destination.structure' => ['required', 'uuid'],
            'origin.parent' => ['nullable', 'array:type,id,label'],
            'destination.parent' => ['nullable', 'array:type,id,label'],
            'origin.previousSibling' => ['nullable', 'array:type,id,label'],
            'destination.previousSibling' => ['nullable', 'array:type,id,label'],
            'origin.parent.*' => ['string'],
            'destination.parent.*' => ['string'],
            'origin.previousSibling.*' => ['string'],
            'destination.previousSibling.*' => ['string'],
        ], formatter: self::formatMovement(...));
        $this->register('craft.element.merged', $source, 'Merged', 'code-merge', [
            'role' => ['required', 'in:merged,prevailing'],
            'other' => ['required', 'array:type,id,label'],
            ...Arr::prependKeysWith($referenceRules, 'other.'),
        ], formatter: self::formatMerge(...));
        $this->register('craft.draft.created', $source, 'Draft created', icon: 'scribble');
        $this->register('craft.draft.saved', $source, 'Draft saved', icon: 'floppy-disk');
        $this->register('craft.draft.applied', $source, 'Draft applied', icon: 'check');
        $this->register('craft.draft.discarded', $source, 'Draft discarded', icon: 'trash');
        $this->register('craft.revision.restored', $source, 'Revision restored', 'rotate-left', [
            'revisionNum' => ['required', 'integer'],
        ], formatter: fn (ActivityEvent $event, string $locale): string => t(
            'Restored revision {revision}.',
            ['revision' => $event->data['revisionNum']],
            locale: $locale,
        ));
        $this->register('craft.asset.file-replaced', $source, 'File replaced', 'file-arrow-up', [
            'oldFilename' => ['required', 'string'],
            'newFilename' => ['required', 'string'],
            'oldMimeType' => ['nullable', 'string'],
            'newMimeType' => ['nullable', 'string'],
            'oldSize' => ['nullable', 'integer'],
            'newSize' => ['nullable', 'integer'],
        ], formatter: self::formatFileReplacement(...));
        $this->register('craft.element.trashed', $source, 'Trashed', icon: 'trash');
        $this->register('craft.element.restored', $source, 'Restored', icon: 'rotate-left');
        $this->register('craft.element.deleted', $source, 'Deleted', icon: 'trash');
        $this->register(
            'craft.element.site-added',
            $source,
            'Added to site',
            'circle-plus',
            formatter: fn (ActivityEvent $event, string $locale): string => t(
                'Added to {site}.',
                ['site' => $event->snapshots['site']['name']],
                locale: $locale,
            ),
        );
        $this->register(
            'craft.element.site-removed',
            $source,
            'Removed from site',
            'circle-minus',
            formatter: fn (ActivityEvent $event, string $locale): string => t(
                'Removed from {site}.',
                ['site' => $event->snapshots['site']['name']],
                locale: $locale,
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $rules
     * @param  (Closure(ActivityEvent, string): (string|Htmlable))|null  $formatter
     */
    public function register(
        string $eventType,
        ActivitySource $source,
        string $label,
        string $icon = 'wave-pulse',
        array $rules = [],
        ?Closure $formatter = null,
    ): void {
        if ($label === '') {
            throw new InvalidArgumentException('Activity event types require a label.');
        }

        if ($icon === '') {
            throw new InvalidArgumentException('Activity event types require an icon.');
        }

        if (isset($this->eventTypes[$eventType])) {
            throw new LogicException("The [$eventType] activity event type is already registered.");
        }

        $this->eventTypes[$eventType] = [
            'source' => $source,
            'label' => $label,
            'icon' => $icon,
            'rules' => $rules,
            'formatter' => $formatter,
        ];
    }

    /** @return array{source: ActivitySource, label: string, icon: string, rules: array<string, mixed>, formatter: (Closure(ActivityEvent, string): (string|Htmlable))|null} */
    public function get(string $eventType): array
    {
        return $this->eventTypes[$eventType] ?? throw new InvalidArgumentException(
            "The [$eventType] activity event type is not registered.",
        );
    }

    /** @return array{source: ActivitySource, label: string, icon: string, rules: array<string, mixed>, formatter: (Closure(ActivityEvent, string): (string|Htmlable))|null}|null */
    public function find(string $eventType): ?array
    {
        return $this->eventTypes[$eventType] ?? null;
    }

    public function icon(string $eventType): string
    {
        return $this->find($eventType)['icon'] ?? 'wave-pulse';
    }

    public function label(string $eventType, string $locale): string
    {
        $registration = $this->get($eventType);
        $label = t(
            $registration['label'],
            category: $registration['source']->translationCategory,
            locale: $locale,
        );

        if ($label === '') {
            throw new UnexpectedValueException('Activity event labels cannot be empty.');
        }

        return $label;
    }

    private static function formatMovement(ActivityEvent $event, string $locale): string
    {
        return t(
            'Moved from {origin} to {destination}.',
            [
                'origin' => self::positionDescription($event->data['origin'], $locale),
                'destination' => self::positionDescription($event->data['destination'], $locale),
            ],
            locale: $locale,
        );
    }

    private static function positionDescription(mixed $position, string $locale): string
    {
        if (! is_array($position)) {
            throw new UnexpectedValueException('Activity movement positions must be arrays.');
        }

        $parent = $position['parent']['label'] ?? null;
        $previousSibling = $position['previousSibling']['label'] ?? null;

        return match (true) {
            $parent !== null && $previousSibling !== null => t(
                'the position after {previousSibling} in {parent}',
                compact('parent', 'previousSibling'),
                locale: $locale,
            ),
            $parent !== null => t(
                'the first position in {parent}',
                compact('parent'),
                locale: $locale,
            ),
            $previousSibling !== null => t(
                'the position after {previousSibling} at the top level',
                compact('previousSibling'),
                locale: $locale,
            ),
            default => t('the first position at the top level', locale: $locale),
        };
    }

    private static function formatMerge(ActivityEvent $event, string $locale): string
    {
        $other = $event->data['other']['label'];

        return match ($event->data['role']) {
            'merged' => t('Merged into {other}.', compact('other'), locale: $locale),
            'prevailing' => t('Merged {other} into this element.', compact('other'), locale: $locale),
            default => throw new UnexpectedValueException('Unknown activity merge role.'),
        };
    }

    private static function formatFileReplacement(ActivityEvent $event, string $locale): string
    {
        $oldFile = self::fileDescription($event, 'old');
        $newFile = self::fileDescription($event, 'new');

        return t(
            'Replaced {oldFile} with {newFile}.',
            compact('oldFile', 'newFile'),
            locale: $locale,
        );
    }

    private static function fileDescription(ActivityEvent $event, string $version): string
    {
        $details = array_filter([
            $event->data["{$version}MimeType"],
            isset($event->data["{$version}Size"]) ? "{$event->data["{$version}Size"]} B" : null,
        ]);
        $filename = $event->data["{$version}Filename"];

        return $details === [] ? $filename : sprintf('%s (%s)', $filename, implode(', ', $details));
    }
}
