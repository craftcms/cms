<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use Closure;
use CraftCms\Cms\Activity\Data\ActivityActor;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use JsonException;

use function CraftCms\Cms\currentUserElement;

#[Scoped]
class ActivityEventRecorder
{
    public function __construct(
        private readonly ActivityEventTypes $eventTypes,
        private readonly Impersonation $impersonation,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $changes
     */
    public function record(
        string $eventType,
        ElementInterface|ActivitySubject|null $subject = null,
        User|ActivityActor|null $actor = null,
        ?Site $site = null,
        array $data = [],
        array $changes = [],
    ): ActivityEvent {
        $registration = $this->eventTypes->get($eventType);

        $this->validatePayload($data, $changes, $registration['rules']);

        $subject = $subject instanceof ElementInterface ? ActivitySubject::fromElement($subject) : $subject;
        $actor = $this->resolveActor($actor);

        if ($site !== null && $site->id === null) {
            throw new InvalidArgumentException('Activity sites must be saved.');
        }

        $snapshots = [
            'actor' => ['label' => $actor->label],
            'source' => ['label' => $registration['source']->label],
            'event' => ['label' => $this->eventTypes->label($eventType, app()->getLocale())],
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
            'eventType' => $eventType,
            'source' => $registration['source']->id,
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

    private function resolveActor(User|ActivityActor|null $actor): ActivityActor
    {
        if ($actor instanceof User) {
            return ActivityActor::user($actor);
        }

        if ($actor !== null) {
            return $actor;
        }

        if (($user = currentUserElement()) !== null) {
            return ActivityActor::user($user);
        }

        return ActivityActor::system();
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
            'changes.*' => ['array:type,id,label,old,new'],
            'changes.*.type' => ['required', 'string'],
            'changes.*.id' => ['required', 'string'],
            'changes.*.label' => ['required', 'string'],
            ...Arr::prependKeysWith($rules, 'data.'),
        ])->validate();
    }
}
