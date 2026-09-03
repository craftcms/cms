<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\Models;

use Carbon\CarbonImmutable;
use CraftCms\Cms\Activity\Data\ActivityActor;
use CraftCms\Cms\Activity\Data\ActivityChange;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\Enums\ActivityActorType;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Site\Data\Site;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use InvalidArgumentException;

/**
 * @property string $id
 * @property string $eventType
 * @property string $source
 * @property ActivityActorType $actorType
 * @property int|null $actorId
 * @property string|null $subjectType
 * @property string|null $subjectId
 * @property int|null $siteId
 * @property array{snapshots: array<string, array<string, int|string>>, changes: list<array<string, mixed>>, data: array<string, mixed>} $payload
 * @property array<string, array<string, int|string>> $snapshots
 * @property list<ActivityChange> $changes
 * @property array<string, mixed> $data
 * @property CarbonImmutable $occurredAt
 */
class ActivityEvent extends BaseModel
{
    #[\Override]
    protected $table = Table::ACTIVITYEVENTS;

    #[\Override]
    public $timestamps = false;

    #[\Override]
    protected function casts(): array
    {
        return [
            'id' => 'string',
            'actorType' => ActivityActorType::class,
            'actorId' => 'integer',
            'siteId' => 'integer',
            'payload' => 'array',
            'occurredAt' => 'immutable_datetime',
        ];
    }

    /** @return Attribute<array<string, array<string, int|string>>, never> */
    protected function snapshots(): Attribute
    {
        return Attribute::get(fn () => $this->payload['snapshots']);
    }

    /** @return Attribute<list<ActivityChange>, never> */
    protected function changes(): Attribute
    {
        return Attribute::get(fn () => array_map(
            ActivityChange::fromArray(...),
            $this->payload['changes'],
        ));
    }

    /** @return Attribute<array<string, mixed>, never> */
    protected function data(): Attribute
    {
        return Attribute::get(fn () => $this->payload['data']);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function subject(Builder $query, ActivitySubject $subject): Builder
    {
        return $query
            ->where('subjectType', $subject->type)
            ->where('subjectId', $subject->id);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function site(Builder $query, Site|int $site): Builder
    {
        $siteId = $site instanceof Site ? $site->id : $site;

        if ($siteId === null) {
            throw new InvalidArgumentException('Activity site criteria require a saved site.');
        }

        return $query->where(fn (Builder $query) => $query
            ->whereNull('siteId')
            ->orWhere('siteId', $siteId));
    }

    /**
     * @param  Builder<static>  $query
     * @param  string|list<string>  $eventTypes
     * @return Builder<static>
     */
    #[Scope]
    protected function eventTypes(Builder $query, string|array $eventTypes): Builder
    {
        $eventTypes = (array) $eventTypes;

        if ($eventTypes === []) {
            throw new InvalidArgumentException('Activity event type criteria cannot be empty.');
        }

        return $query->whereIn('eventType', $eventTypes);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function actor(Builder $query, ActivityActor $actor): Builder
    {
        return $query
            ->where('actorType', $actor->type)
            ->where('actorId', $actor->id);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function source(Builder $query, string $source): Builder
    {
        if ($source === '') {
            throw new InvalidArgumentException('Activity source criteria cannot be empty.');
        }

        return $query->where('source', $source);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function occurredFrom(Builder $query, DateTimeInterface $date): Builder
    {
        return $query->where('occurredAt', '>=', $date);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function occurredUntil(Builder $query, DateTimeInterface $date): Builder
    {
        return $query->where('occurredAt', '<=', $date);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function newestFirst(Builder $query): Builder
    {
        return $query
            ->latest('occurredAt')
            ->orderByDesc('id');
    }
}
