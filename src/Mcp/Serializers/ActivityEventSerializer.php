<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Serializers;

use CraftCms\Cms\Activity\ActivityTimelinePresenter;
use CraftCms\Cms\Activity\Data\ActivityChange;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;

/** @since 6.0.0 */
readonly class ActivityEventSerializer
{
    public function __construct(private ActivityTimelinePresenter $presenter) {}

    /**
     * @param  Collection<int, ActivityEvent>  $events
     * @return list<array<string, mixed>>
     */
    public function serialize(Collection $events, User $viewer): array
    {
        return $this->presenter->events($events, $viewer)
            ->map(static function (array $event): array {
                $serialized = Arr::only($event, [
                    'id', 'type', 'occurredAt', 'actor', 'impersonator', 'source',
                    'subject', 'site', 'origin', 'description', 'changes', 'comment',
                ]);
                $serialized['actor'] = Arr::except($serialized['actor'], ['url']);
                $serialized['impersonator'] = $serialized['impersonator'] !== null
                    ? Arr::except($serialized['impersonator'], ['url'])
                    : null;
                $serialized['changes'] = array_map(static fn (ActivityChange $change): array => $change->toArray(), $event['changes']);

                if ($serialized['comment'] !== null) {
                    $serialized['comment'] = Arr::except($serialized['comment'], ['canEdit', 'canDelete']);
                }

                return $serialized;
            })
            ->values()
            ->all();
    }
}
