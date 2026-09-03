<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use CraftCms\Cms\Activity\Contracts\ActivityEventTypeInterface;
use CraftCms\Cms\Activity\Data\ActivityActor;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Auth\Impersonation;
use Illuminate\Container\Attributes\Scoped;

use function CraftCms\Cms\currentUserElement;
use function CraftCms\Cms\t;

#[Scoped]
class ActivityEventRecorder
{
    public function __construct(
        private readonly Impersonation $impersonation,
    ) {}

    public function record(ActivityEventTypeInterface $event): ActivityEvent
    {
        $data = $event->data();

        $subject = $event->subject();
        $actor = $this->resolveActor($event->actor());
        $site = $event->site();

        $source = $event::source();

        $snapshots = [
            'actor' => ['label' => $actor->label],
            'source' => ['label' => $source->label],
            'event' => ['label' => t($event::label(), category: $source->translationCategory)],
        ];

        if ($subject !== null) {
            $snapshots['subject'] = ['label' => $subject->label];
        }

        if ($site !== null) {
            $snapshots['site'] = ['name' => $site->getName(false)];
        }

        if (($impersonator = $this->impersonation->getImpersonator()) !== null) {
            $snapshots['impersonator'] = ['id' => $impersonator->id, 'label' => $impersonator->name];
        }

        return ActivityEvent::query()->create([
            'eventType' => $event::class,
            'source' => $source->id,
            'actorType' => $actor->type->value,
            'actorId' => $actor->id,
            'subjectType' => $subject?->type,
            'subjectId' => $subject?->id,
            'siteId' => $site?->id,
            'payload' => [
                'snapshots' => $snapshots,
                'changes' => collect($event->changes())->toArray(),
                'data' => $data === [] ? (object) [] : $data,
            ],
            'occurredAt' => now(),
        ]);
    }

    private function resolveActor(?ActivityActor $actor): ActivityActor
    {
        if ($actor !== null) {
            return $actor;
        }

        if (($user = currentUserElement()) !== null) {
            return ActivityActor::user($user);
        }

        $isHttpRequest = ! app()->runningInConsole()
            || (app()->bound('request') && request()->route() !== null);

        return $isHttpRequest
            ? ActivityActor::anonymous()
            : ActivityActor::system();
    }
}
