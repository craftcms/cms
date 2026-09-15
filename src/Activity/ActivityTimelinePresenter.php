<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use CraftCms\Cms\Activity\Data\ActivityActor;
use CraftCms\Cms\Activity\EventTypes\CommentCreated;
use CraftCms\Cms\Activity\EventTypes\CommentDeleted;
use CraftCms\Cms\Activity\EventTypes\CommentEdited;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Translation\Formatter;
use CraftCms\Cms\Translation\Locale;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class ActivityTimelinePresenter
{
    public function __construct(
        private readonly Activities $activities,
        private readonly ActivityComments $comments,
    ) {}

    /**
     * @template TKey of array-key
     *
     * @param  Collection<int, ActivityEvent>  $events
     * @param  Collection<TKey, ActivityEvent>|null  $selectedVersions
     * @return Collection<int, array<string, mixed>>
     */
    public function events(
        Collection $events,
        User $viewer,
        ?Collection $selectedVersions = null,
    ): Collection {
        $selectedVersions ??= $this->latestVersions($events);
        $versions = $events->mapWithKeys(fn (ActivityEvent $event): array => [
            $event->id => $selectedVersions->get($event->id, $event),
        ]);
        $actors = $this->actors($versions);
        $mentionedUsers = $this->comments->mentionedUsers($versions);
        $formatter = I18N::getFormatter();

        return $events->map(fn (ActivityEvent $root): array => $this->event(
            $root,
            $versions->get($root->id),
            $viewer,
            $actors,
            $mentionedUsers,
            $formatter,
        ));
    }

    /**
     * @param  Collection<int, ActivityEvent>  $events
     * @return Collection<string, ActivityEvent>
     */
    private function latestVersions(Collection $events): Collection
    {
        $commentIds = $events
            ->where('eventType', CommentCreated::class)
            ->pluck('id');

        if ($commentIds->isEmpty()) {
            return collect();
        }

        return ActivityEvent::query()
            ->whereIn('rootEventId', $commentIds)
            ->newestFirst()
            ->groupLimit(1, 'rootEventId')
            ->get()
            ->keyBy('rootEventId');
    }

    /**
     * @param  Collection<array-key, ActivityEvent>  $events
     * @return Collection<int|string, User>
     */
    private function actors(Collection $events): Collection
    {
        $ids = $events
            ->flatMap(fn (ActivityEvent $event): array => [
                $event->actorType === ActivityActor::TYPE_USER ? $event->actorId : null,
                $event->snapshots['impersonator']['id'] ?? null,
            ])
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return collect();
        }

        return User::find()
            ->id($ids)
            ->status(null)
            ->collect()
            ->keyBy('id');
    }

    /**
     * @param  Collection<int|string, User>  $actors
     * @param  Collection<int|string, User>  $mentionedUsers
     * @return array<string, mixed>
     */
    private function event(
        ActivityEvent $root,
        ActivityEvent $event,
        User $viewer,
        Collection $actors,
        Collection $mentionedUsers,
        Formatter $formatter,
    ): array {
        $actor = $event->actorType === ActivityActor::TYPE_USER && $event->actorId !== null
            ? $actors->get($event->actorId)
            : null;
        $impersonatorSnapshot = $event->snapshots['impersonator'] ?? null;
        $impersonator = $impersonatorSnapshot === null
            ? null
            : $actors->get($impersonatorSnapshot['id']);
        $isComment = $root->eventType === CommentCreated::class;
        $formatted = $this->activities->format($event);
        $deleted = $isComment && $event->eventType === CommentDeleted::class;
        $canEdit = $isComment && ! $deleted && $root->actorId === $viewer->id;
        $occurredAt = $root->occurredAt->setTimezone($formatter->timeZone);

        return [
            'id' => $root->id,
            'icon' => $this->activities->icon($event),
            'occurredAt' => $root->occurredAt->toIso8601String(),
            'formattedOccurredAt' => [
                'date' => $occurredAt->toDateString(),
                'dateLabel' => $formatter->asDate($occurredAt, Locale::LENGTH_MEDIUM),
                'time' => $formatter->asTime($occurredAt, Locale::LENGTH_SHORT),
                'full' => $formatter->asDateTime($occurredAt, Locale::LENGTH_LONG, true),
            ],
            'actor' => [
                'label' => $event->snapshots['actor']['label'],
                'url' => $actor && Gate::forUser($viewer)->allows('view', $actor) ? $actor->getCpEditUrl() : null,
                'deleted' => $event->actorType === ActivityActor::TYPE_USER
                    && $event->actorId !== null
                    && $actor === null,
            ],
            'impersonator' => $impersonatorSnapshot === null ? null : [
                'label' => $impersonatorSnapshot['label'],
                'url' => $impersonator && Gate::forUser($viewer)->allows('view', $impersonator)
                    ? $impersonator->getCpEditUrl()
                    : null,
                'deleted' => $impersonator === null,
            ],
            'source' => [
                'label' => $event->snapshots['source']['label'],
            ],
            'description' => [
                'text' => $formatted instanceof Htmlable ? null : $formatted,
                'html' => $formatted instanceof Htmlable ? $formatted->toHtml() : null,
            ],
            'changes' => $event->changes,
            'comment' => $isComment ? [
                'html' => $deleted ? null : $this->comments->render($event, $viewer, $mentionedUsers)->toHtml(),
                'markdown' => $canEdit ? $event->data['markdown'] : null,
                'edited' => $event->eventType === CommentEdited::class,
                'deleted' => $deleted,
                'canEdit' => $canEdit,
                'canDelete' => ! $deleted && ($root->actorId === $viewer->id || $viewer->admin),
            ] : null,
        ];
    }
}
