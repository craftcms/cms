<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use Closure;
use CraftCms\Cms\Activity\Contracts\ActivityEventTypeInterface;
use CraftCms\Cms\Activity\Data\ActivityActor;
use CraftCms\Cms\Activity\Data\ActivityChange;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Support\Json;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use JsonException;

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
        $changes = array_map(
            fn (ActivityChange $change) => $change->toArray(),
            $event->changes(),
        );

        $this->validatePayload($data, $changes, $event::rules());

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
                'changes' => $changes,
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

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $changes
     * @param  array<string, mixed>  $rules
     */
    private function validatePayload(array $data, array $changes, array $rules): void
    {
        $validJson = static function (string $attribute, mixed $value, Closure $fail): void {
            try {
                Json::encode($value, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $fail("The $attribute must be valid JSON.");
            }
        };

        Validator::make(['data' => $data, 'changes' => $changes], [
            'data' => [
                'array',
                static function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value !== [] && array_is_list($value)) {
                        $fail('The activity event data must be a JSON object.');
                    }
                },
                $validJson,
            ],
            'changes' => ['list', $validJson],
            ...Arr::prependKeysWith($rules, 'data.'),
        ])->validate();
    }
}
