<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @phpstan-type TimelineEventPayload array{
 *     id: string,
 *     type: string,
 *     subject: array{type: string|null, uid: string, label: string|null}|null,
 *     site: array{id: int, name: string|null}|null,
 *     origin: string|null,
 *     component: string,
 *     props: array<string, mixed>,
 *     icon: string|null,
 *     occurredAt: string,
 *     formattedOccurredAt: array{date: string, dateLabel: string, time: string, full: string},
 *     actor: array{type: string, id: int|null, label: string, url: string|null, deleted: bool},
 *     impersonator: array{id: int, label: string, url: string|null, deleted: bool}|null,
 *     source: array{id: string, label: string},
 *     description: array{text: string|null, html: string|null},
 *     changes: list<ActivityChange>,
 *     comment: array{html: string|null, markdown: string|null, edited: bool, deleted: bool, canEdit: bool, canDelete: bool}|null
 * }
 *
 * @implements Arrayable<string, mixed>
 *
 * @since 6.0.0
 */
readonly class ActivityTimelineEventData implements Arrayable, JsonSerializable
{
    /**
     * @param  array{type: string|null, uid: string, label: string|null}|null  $subject
     * @param  array{id: int, name: string|null}|null  $site
     * @param  array<string, mixed>  $props
     * @param  array{date: string, dateLabel: string, time: string, full: string}  $formattedOccurredAt
     * @param  array{type: string, id: int|null, label: string, url: string|null, deleted: bool}  $actor
     * @param  array{id: int, label: string, url: string|null, deleted: bool}|null  $impersonator
     * @param  array{id: string, label: string}  $source
     * @param  array{text: string|null, html: string|null}  $description
     * @param  list<ActivityChange>  $changes
     * @param  array{html: string|null, markdown: string|null, edited: bool, deleted: bool, canEdit: bool, canDelete: bool}|null  $comment
     */
    public function __construct(
        public string $id,
        public string $type,
        public ?array $subject,
        public ?array $site,
        public ?string $origin,
        public string $component,
        public array $props,
        public ?string $icon,
        public string $occurredAt,
        public array $formattedOccurredAt,
        public array $actor,
        public ?array $impersonator,
        public array $source,
        public array $description,
        public array $changes,
        public ?array $comment,
    ) {}

    /** @return TimelineEventPayload */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'subject' => $this->subject,
            'site' => $this->site,
            'origin' => $this->origin,
            'component' => $this->component,
            'props' => $this->props,
            'icon' => $this->icon,
            'occurredAt' => $this->occurredAt,
            'formattedOccurredAt' => $this->formattedOccurredAt,
            'actor' => $this->actor,
            'impersonator' => $this->impersonator,
            'source' => $this->source,
            'description' => $this->description,
            'changes' => $this->changes,
            'comment' => $this->comment,
        ];
    }

    /** @return TimelineEventPayload */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
